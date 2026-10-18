<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Logger;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\MedicineGovernanceService;
use App\Services\MedicinePersistenceException;
use Throwable;

class MedicineOwnerBootstrapController extends Controller
{
    public function __construct(private MedicineGovernanceService $service,private AuthService $auth,private Session $session) {}

    private function handle(bool $submit):void
    {
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');
        // Redirect outside exception handling, following the account/controller convention.
        if(!$this->auth->id()){redirect('/login');}
        $result=null;
        try{
            if($this->auth->id()!==1){throw new MedicinePersistenceException('Owner access denied.',403);}
            if(($_SERVER['REQUEST_METHOD']??'GET')!==($submit?'POST':'GET')){throw new MedicinePersistenceException('Method denied.',405);}
            if($submit){
                $token=$_POST['_csrf_token']??null;
                if(!is_string($token)||!$this->session->verifyCsrfToken($token)){throw new MedicinePersistenceException('Security token denied.',403);}
                if($_GET!==[]||array_diff(array_keys($_POST),['_csrf_token','confirm'])!==[]||($_POST['confirm']??null)!=='establish_owner_administrator'){
                    throw new MedicinePersistenceException('Invalid confirmation.',422);
                }
                $this->service->bootstrapOwnerFromSession();
            }else{
                $result=$this->service->ownerBootstrapStatus();
            }
        }catch(Throwable $error){
            $status=$error instanceof MedicinePersistenceException?$error->getCode():500;
            if(!in_array($status,[403,405,409,422,503],true)){$status=500;}
            if($status>=500){Logger::error('Medicine owner bootstrap request failed: '.get_class($error));}
            if($status===405){header('Allow: '.($submit?'POST':'GET'));}
            http_response_code($status);
            $message=match($status){
                403=>'Medicine owner access denied. Sign in with the designated active owner account and use a current form.',
                405=>'Use the owner confirmation page to submit this action.',
                409=>'Medicine authority has already been established. This action cannot be repeated.',
                422=>'The confirmation was not accepted. Reload the form and try again.',
                503=>'Owner setup is not ready. Please try again after the controlled setup is complete.',
                default=>'Unable to complete owner setup. Please try again later.',
            };
            $this->view('admin/medicine/owner-bootstrap-error',['title'=>'Medicine owner setup','error'=>$message]);
            return;
        }
        if($submit){$this->session->put('medicine_owner_bootstrap_success',true);redirect('/admin/medicine/owner-bootstrap');}
        $success=$this->session->get('medicine_owner_bootstrap_success')===true;
        $this->session->remove('medicine_owner_bootstrap_success');
        $this->view('admin/medicine/owner-bootstrap',['title'=>'Medicine owner setup','status'=>$result,'success'=>$success]);
    }

    public function show():void {$this->handle(false);}
    public function store():void {$this->handle(true);}
}
