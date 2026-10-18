<?php
declare(strict_types=1);
namespace App\Core {
    final class Logger {
        public static function error(string $message): void {}
        public static function exception(\Throwable $error): void {}
    }
    final class App {
        public function run(): void {
            if (session_status()!==PHP_SESSION_NONE) { throw new \LogicException('Session started'); }
            echo 'UNEXPECTED OUTPUT MUST BE DISCARDED';
            switch ($GLOBALS['bootstrapCase']) {
                case 'exception': throw new \RuntimeException('SQLSTATE secret path password');
                case 'warning': trigger_error('internal warning secret path',E_USER_WARNING);break;
                case 'fatal': trigger_error('internal fatal secret path',E_USER_ERROR);break;
                default: PublicApiResponse::success(['state'=>'session-free']);
            }
        }
    }
}
namespace {
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(!isset($argv[1])) {
    $checks=0;
    foreach(['success','exception','warning','fatal'] as $case){
        $process=proc_open([PHP_BINARY,__FILE__,$case],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($process);
        $json=json_decode($out,true,512,JSON_THROW_ON_ERROR);
        if($err!=='' || str_contains($out,'secret') || str_contains($out,'UNEXPECTED') || str_contains($out,'<pre>')){throw new LogicException('API bootstrap leaks output');}++$checks;
        if($case==='success' ? ($json['data']['state']??null)!=='session-free' : ($json['error']['code']??null)!=='INTERNAL_ERROR'){throw new LogicException('Wrong bootstrap envelope');}++$checks;
    }
    echo "Public API V1 bootstrap: $checks checks passed with web debug enabled; no session/database/log writes.\n";exit;
}
$GLOBALS['bootstrapCase']=$argv[1];
function env(string $key,mixed $default=null): mixed {return match($key){'APP_DEBUG'=>true,'APP_BASE_PATH'=>'/kobi/public',default=>$default};}
function config(?string $key=null): mixed {return $key==='base_path'?'/kobi/public':null;}
spl_autoload_register(function(string $class): void {if(str_starts_with($class,'App\\')){require dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php';}});
$_SERVER['REQUEST_URI']='/kobi/public/api/v1/diseases';$_SERVER['REQUEST_METHOD']='GET';
require dirname(__DIR__).'/bootstrap/app.php';
}
