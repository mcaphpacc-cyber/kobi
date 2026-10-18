<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\Logger;
use App\Services\AuthService;
use App\Services\TreatmentEditorialService;
use App\Services\TreatmentGovernanceRules;
use PDOException;
use RuntimeException;
use Throwable;

/** HTTP boundary only; review predicates/decisions stay in existing services. */
class TreatmentReviewController extends Controller
{
    public function __construct(private TreatmentEditorialService $service,private AuthService $auth,private Session $session,private TreatmentGovernanceRules $rules) {}

    private function handle(callable $action,?string $redirectTo=null): void
    {
        if (!$this->auth->id()) { redirect('/login'); }
        header('Cache-Control: no-store, private'); header('X-Robots-Tag: noindex, nofollow');
        try {
            if ($redirectTo!==null) {
                if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') { throw new RuntimeException('Use the review form to submit this action.'); }
                $token=$_POST['_csrf_token']??null;
                if (!is_string($token) || !$this->session->verifyCsrfToken($token)) { throw new RuntimeException('Invalid security token. Reload the review form and try again.'); }
            }
            $result=$action();
        } catch (RuntimeException $error) {
            $storage=$error instanceof PDOException || $error->getPrevious()!==null;
            $unexpected=!$storage && get_class($error)!==RuntimeException::class;
            if ($storage || $unexpected) { Logger::error('Treatment review storage failure: '.get_class($error).' cause: '.get_class($error->getPrevious()??$error)); }
            $message=$storage ? 'Treatment review storage is unavailable. Please try again after setup is complete.' : ($unexpected ? 'Unable to complete this review request. Please try again.' : $error->getMessage());
            http_response_code($storage ? 503 : ($unexpected ? 500 : (str_contains($message,'access denied') || str_contains($message,'security token') ? 403 : (str_contains($message,'Reload and retry') ? 409 : 422))));
            $this->view('admin/treatment-review/error',['title'=>'Treatment review request','error'=>$message]); return;
        } catch (Throwable $error) {
            Logger::error('Treatment review failure: '.get_class($error)); http_response_code(500);
            $this->view('admin/treatment-review/error',['title'=>'Treatment review request','error'=>'Unable to complete this review request. Please try again.']); return;
        }
        if ($redirectTo!==null) { redirect($redirectTo); }
        $this->view($result['view'],$result['data']);
    }

    public function index(): void { $this->handle(fn()=>['view'=>'admin/treatment-review/index','data'=>$this->service->reviewListing($_GET)+['title'=>'Treatment review queue']]); }
    public function show(string $protocolId): void { $this->handle(fn()=>['view'=>'admin/treatment-review/show','data'=>$this->service->reviewDetail($this->rules->id($protocolId),$_GET)+['title'=>'Treatment review']]); }
    public function riskEligibility(string $protocolId): void { $this->handle(fn()=>$this->service->recordRiskEligibility($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/review'); }
    public function approve(string $protocolId): void { $this->handle(fn()=>$this->service->approveReview($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/review'); }
    public function changesRequired(string $protocolId): void { $this->handle(fn()=>$this->service->requestReviewChanges($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/review'); }
}
