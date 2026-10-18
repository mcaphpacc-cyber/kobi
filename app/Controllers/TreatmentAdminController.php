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

class TreatmentAdminController extends Controller
{
    public function __construct(
        private TreatmentEditorialService $service,
        private AuthService $auth,
        private Session $session,
        private TreatmentGovernanceRules $rules
    ) {}

    /** Redirects occur outside exception handling so test sentinels cannot be swallowed. */
    private function handle(callable $action,string|callable|null $redirectTo=null): void
    {
        if (!$this->auth->id()) { redirect('/login'); }
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');
        try {
            if ($redirectTo!==null) {
                if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') { throw new RuntimeException('Use the form to submit this action.'); }
                $token=$_POST['_csrf_token']??null;
                if (!is_string($token) || !$this->session->verifyCsrfToken($token)) { throw new RuntimeException('Invalid security token. Please reload the form and try again.'); }
            }
            $result=$action();
        } catch (RuntimeException $error) {
            $databaseFailure=$error instanceof PDOException || $error->getPrevious()!==null;
            $unexpected=!$databaseFailure && get_class($error)!==RuntimeException::class;
            if ($databaseFailure || $unexpected) { Logger::error('Treatment editorial persistence failure: '.get_class($error).' cause: '.get_class($error->getPrevious()??$error)); }
            $message=$databaseFailure ? 'Treatment editorial storage is unavailable. Please try again after setup is complete.' : ($unexpected ? 'Unable to complete this editorial request. Please try again.' : $error->getMessage());
            $status=$databaseFailure ? 503 : ($unexpected ? 500 : (str_contains($message,'access denied') || str_contains($message,'security token') ? 403 : (str_contains($message,'Reload and retry') ? 409 : 422)));
            http_response_code($status);
            $this->view('admin/treatments/error',['title'=>'Treatment editorial request','error'=>$message]);
            return;
        } catch (Throwable $error) {
            Logger::error('Treatment editorial failure: '.get_class($error));
            http_response_code(500);
            $this->view('admin/treatments/error',['title'=>'Treatment editorial request','error'=>'Unable to complete this editorial request. Please try again.']);
            return;
        }
        if ($redirectTo!==null) { redirect(is_callable($redirectTo) ? $redirectTo($result) : $redirectTo); }
        $this->view($result['view'],$result['data']);
    }

    public function index(): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/index','data'=>$this->service->listing($_GET)+['title'=>'Treatment editorial']]);
    }

    public function operations(): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/operations','data'=>$this->service->operationsListing($_GET)+['title'=>'Treatment operations']]);
    }

    public function operation(string $protocolId): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/operation','data'=>$this->service->operationsDetail($this->rules->id($protocolId),$_GET)+['title'=>'Treatment operations and history']]);
    }

    public function withdraw(string $protocolId): void
    {
        $this->handle(fn()=>$this->service->withdrawProtocol($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/operations?withdrawn=1');
    }

    public function resetReview(string $protocolId): void
    {
        $this->handle(fn()=>$this->service->resetProtocolReview($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/operations?reset=1');
    }

    public function edit(string $protocolId): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/edit','data'=>$this->service->detail($this->rules->id($protocolId))+['title'=>'Edit treatment protocol']]);
    }

    public function create(): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/create','data'=>$this->service->draftForm()+['title'=>'Create treatment draft']]);
    }

    public function store(): void
    {
        $this->handle(fn()=>$this->service->createDraft($_POST),fn(int $id)=>'/admin/treatments/'.$id.'/edit?created=1');
    }

    public function createItem(string $protocolId): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/item-create','data'=>$this->service->itemForm($this->rules->id($protocolId))+['title'=>'Add treatment item']]);
    }

    public function storeItem(string $protocolId): void
    {
        $this->handle(fn()=>$this->service->addItem($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/edit?saved=1');
    }

    public function save(string $protocolId): void
    {
        $this->handle(fn()=>$this->service->save($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/edit?saved=1');
    }

    public function submit(string $protocolId): void
    {
        $this->handle(fn()=>$this->service->submit($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/edit?submitted=1');
    }

    public function reference(string $protocolId): void
    {
        $this->handle(fn()=>$this->service->reference($this->rules->id($protocolId),$_POST),'/admin/treatments/'.rawurlencode($protocolId).'/edit?saved=1');
    }

    public function sources(string $systemId): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/sources','data'=>$this->service->sourceListing($this->rules->id($systemId),$_GET)+['title'=>'Treatment sources']]);
    }

    public function createSource(string $systemId): void
    {
        $this->handle(function () use ($systemId) {
            if (array_diff(array_keys($_GET),['replaces_source_id'])) { throw new RuntimeException('Unsupported editorial field.'); }
            $predecessor=isset($_GET['replaces_source_id']) ? $this->rules->id($_GET['replaces_source_id']) : null;
            return ['view'=>'admin/treatments/source-create','data'=>$this->service->sourceForm($this->rules->id($systemId),$predecessor)+['title'=>'Create treatment source']];
        });
    }

    public function storeSource(string $systemId): void
    {
        $this->handle(fn()=>$this->service->createSource($this->rules->id($systemId),$_POST),'/admin/treatment-sources/'.rawurlencode($systemId));
    }

    public function source(string $systemId,string $sourceId): void
    {
        $this->handle(fn()=>['view'=>'admin/treatments/source','data'=>$this->service->sourceDetail($this->rules->id($systemId),$this->rules->id($sourceId))+['title'=>'Treatment source']]);
    }

    public function verifySource(string $systemId,string $sourceId): void
    {
        $this->handle(fn()=>$this->service->verifySource($this->rules->id($systemId),$this->rules->id($sourceId),$_POST),'/admin/treatment-sources/'.rawurlencode($systemId).'/'.rawurlencode($sourceId).'?saved=1');
    }

    public function withdrawSource(string $systemId,string $sourceId): void
    {
        $this->handle(fn()=>$this->service->withdrawSource($this->rules->id($systemId),$this->rules->id($sourceId),$_POST),'/admin/treatment-sources/'.rawurlencode($systemId).'/'.rawurlencode($sourceId).'?saved=1');
    }
}
