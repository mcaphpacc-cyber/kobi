<?php
declare(strict_types=1);

// Phase 3.16 operator CLI. Normal DB: SELECT/metadata and exact additive DDL only.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/vendor/autoload.php';
const M316_DIR=__DIR__.'/../storage/logs/medicine316-recovery';
const M316_RESTORE='kobi_medicine_phase316_restore_20261010';
const M316_MODE='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
const M316_SOURCE='b7829ac3e1463d959386c714fdbd364a09ede97bb3c9c90549d0f1036d85f7f1';
function m316Save(string $name,array $value):void {file_put_contents(M316_DIR.'/'.$name,json_encode($value,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));}
function m316Load(string $name):array {return json_decode(file_get_contents(M316_DIR.'/'.$name),true,512,JSON_THROW_ON_ERROR);}
function m316Assert(bool $ok,string $message):void {if(!$ok){throw new RuntimeException($message);}}
function m316Source():array {
    $source=file_get_contents(dirname(__DIR__).'/database/structure.sql');m316Assert(hash('sha256',$source)===M316_SOURCE,'Authoritative source changed; stop.');
    preg_match_all('/CREATE TABLE `(kobi_medicine_[^`]+|medicine_[^`]+|health_medicine_identity_links|health_medicine_selected_ingredients)` \(.*?\) ENGINE=[^;]+;/s',$source,$tables,PREG_OFFSET_CAPTURE);
    preg_match_all('/CREATE TRIGGER `(medicine_[^`]+)`.*?END\$\$/s',$source,$triggers,PREG_OFFSET_CAPTURE);
    m316Assert(count($tables[0])===12&&count($triggers[0])===8,'Unexpected authoritative object inventory.');$objects=[];
    foreach($tables[0] as $n=>$match){$objects[]=['name'=>$tables[1][$n][0],'kind'=>'table','offset'=>$match[1],'sql'=>$match[0]];}
    foreach($triggers[0] as $n=>$match){$objects[]=['name'=>$triggers[1][$n][0],'kind'=>'trigger','offset'=>$match[1],'sql'=>substr($match[0],0,-2)];}
    usort($objects,fn($a,$b)=>$a['offset']<=>$b['offset']);
    foreach($objects as $object){m316Assert(!preg_match('/\b(?:DROP\s+(?:TABLE|TRIGGER|DATABASE)|TRUNCATE\s|ALTER\s+TABLE|SQL_MODE\s*=|FOREIGN_KEY_CHECKS\s*=|GRANT\s+(?:ALL|SELECT|INSERT|UPDATE))\b/i',$object['sql']),'Unexpected destructive/session/privilege statement.');}
    // Reconcile the authoritative Phase 3.13 upgrade against the fresh table constraint.
    $doc=file_get_contents(dirname(__DIR__).'/docs/MEDICINE_PHASE_3_13_CONTROLLED_PILOT.md');
    preg_match('/ADD CONSTRAINT chk_medicine_event_action (CHECK \(.*?\));/s',$doc,$approved);
    preg_match('/CONSTRAINT `chk_medicine_event_action` (CHECK[^\r\n]+),/',$source,$current);
    $normalize=fn($s)=>preg_replace('/\s+/','',str_replace('`','',$s));
    m316Assert(isset($approved[1],$current[1])&&$normalize($approved[1])===$normalize($current[1]),'Phase 3.13 action CHECK mismatch.');
    // Phase 3.12's earlier action CHECK is intentionally superseded by Phase 3.13.
    $doc12=file_get_contents(dirname(__DIR__).'/docs/MEDICINE_PHASE_3_12_GOVERNANCE_POLICY.md');
    preg_match('/CREATE TABLE `medicine_pilot_records` \(.*?\) ENGINE=[^;]+;/s',$doc12,$pilot);
    $found=array_values(array_filter($objects,fn($o)=>$o['name']==='medicine_pilot_records'))[0];
    m316Assert(isset($pilot[0])&&$normalize($pilot[0])===$normalize($found['sql']),'Phase 3.12 pilot table mismatch.');
    return $objects;
}
function m316Connect(bool $normal):PDO {
    try{$pdo=$normal?(new App\Core\Database())->connection():new PDO('mysql:host=127.0.0.1;port=3328;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}catch(Throwable $e){throw new RuntimeException('Connection unavailable; credentials suppressed.');}
    $meta=$pdo->query('SELECT DATABASE() database_name,@@version version,@@port port,@@datadir datadir,@@sql_mode sql_mode,@@global.sql_mode global_sql_mode')->fetch();
    m316Assert(str_starts_with($meta['version'],'10.4.27-')&&$meta['sql_mode']===M316_MODE,'Version or SQL mode differs; stop.');
    if($normal){m316Assert($meta['database_name']==='medical1'&&(int)$meta['port']===3306,'Unexpected normal target.');}
    else{$expected=realpath(__DIR__.'/.medicine38-validation/data');m316Assert((int)$meta['port']===3328&&$expected!==false&&strcasecmp(rtrim(str_replace('\\','/',$meta['datadir']),'/'),str_replace('\\','/',$expected))===0,'Disposable isolation failed.');}
    return $pdo;
}
function m316Absent(PDO $pdo,array $objects):void {
    $tables=$pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
    $triggers=$pdo->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
    foreach(array_merge($tables,$triggers) as $name){m316Assert(!str_starts_with($name,'medicine_')&&!str_starts_with($name,'kobi_medicine_')&&!in_array($name,['health_medicine_identity_links','health_medicine_selected_ingredients'],true),'Unexpected existing Medicine object: '.$name);}
    foreach($objects as $object){m316Assert(!in_array($object['name'],array_merge($tables,$triggers),true),'Object already exists: '.$object['name']);}
    foreach(['ROUTINES'=>'ROUTINE_NAME','EVENTS'=>'EVENT_NAME'] as $catalog=>$column){$schema=$catalog==='ROUTINES'?'ROUTINE_SCHEMA':'EVENT_SCHEMA';foreach($pdo->query("SELECT $column FROM information_schema.$catalog WHERE $schema=DATABASE()") as $row){m316Assert(!str_starts_with($row[$column],'medicine_')&&!str_starts_with($row[$column],'kobi_medicine_'),'Unexpected existing Medicine routine/event.');}}
}
function m316Data(PDO $pdo,?array $names=null):array {
    $tables=$pdo->query("SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME")->fetchAll();$result=[];
    foreach($tables as $table){$name=$table['TABLE_NAME'];if($table['TABLE_TYPE']!=='BASE TABLE'||($names!==null&&!in_array($name,$names,true))){continue;}m316Assert((bool)preg_match('/\A[a-zA-Z0-9_]+\z/D',$name),'Unsupported table identifier.');
        $rows=$pdo->query('SELECT * FROM `'.$name.'`');$hashes=[];while($row=$rows->fetch(PDO::FETCH_ASSOC)){$hashes[]=hash('sha256',serialize($row));}sort($hashes,SORT_STRING);
        $result[$name]=['count'=>count($hashes),'sha256'=>hash('sha256',implode('', $hashes))];
    }return $result;
}
function m316ExistingMetadata(PDO $pdo):array {
    $result=[];foreach($pdo->query('SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME') as $table){$name=$table['TABLE_NAME'];$row=$pdo->query('SHOW CREATE TABLE `'.$name.'`')->fetch();$sql=$row[$table['TABLE_TYPE']==='VIEW'?'Create View':'Create Table'];$result[$name]=hash('sha256',$sql);}return $result;
}
function m316Metadata(PDO $pdo,array $objects):array {
    $tables=array_column(array_filter($objects,fn($o)=>$o['kind']==='table'),'name');$marks=implode(',',array_fill(0,count($tables),'?'));$out=[];
    $queries=[
        'columns'=>"SELECT TABLE_NAME,COLUMN_NAME,ORDINAL_POSITION,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($marks) ORDER BY TABLE_NAME,ORDINAL_POSITION",
        'indexes'=>"SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,COLLATION,SUB_PART,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($marks) ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX",
        'constraints'=>"SELECT TABLE_NAME,CONSTRAINT_NAME,CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ($marks) ORDER BY TABLE_NAME,CONSTRAINT_NAME",
        'checks'=>"SELECT TABLE_NAME,CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ($marks) ORDER BY TABLE_NAME,CONSTRAINT_NAME",
        'foreign_keys'=>"SELECT TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME IN ($marks) ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION",
        'foreign_rules'=>"SELECT TABLE_NAME,CONSTRAINT_NAME,UPDATE_RULE,DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ($marks) ORDER BY TABLE_NAME,CONSTRAINT_NAME",
        'tables'=>"SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($marks) ORDER BY TABLE_NAME"];
    foreach($queries as $kind=>$sql){$q=$pdo->prepare($sql);$q->execute($tables);$out[$kind]=$q->fetchAll();}
    $out['triggers']=$pdo->query("SELECT TRIGGER_NAME,EVENT_MANIPULATION,EVENT_OBJECT_TABLE,ACTION_TIMING,ACTION_STATEMENT,SQL_MODE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME LIKE 'medicine\\_%' ORDER BY TRIGGER_NAME")->fetchAll();
    return $out;
}
function m316Import(PDO $pdo,string $source):array {
    $buffer='';$delimiter=';';$omitted=[];$n=0;
    foreach(preg_split('/\r?\n/',$source) as $line){
        if(preg_match('/SQL_MODE\s*=/i',$line)){$omitted[]=trim($line);continue;}
        if(preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i',$line,$m)){$delimiter=$m[1];continue;}
        $buffer.=$line."\n";$trim=rtrim($buffer);if(str_ends_with($trim,$delimiter)){$pdo->exec(substr($trim,0,-strlen($delimiter)));$n++;$buffer='';}
    }m316Assert(trim(preg_replace('/^\s*--[^\r\n]*$/m','',$buffer))==='','Incomplete backup import.');return ['statements'=>$n,'omitted_sql_mode_directives'=>array_values(array_unique($omitted))];
}
function m316DDL(PDO $pdo,array $objects,string $log):void {
    $results=[];foreach($objects as $object){$start=microtime(true);try{$pdo->exec($object['sql']);$warnings=$pdo->query('SHOW WARNINGS')->fetchAll();$results[]=['name'=>$object['name'],'kind'=>$object['kind'],'sql_sha256'=>hash('sha256',$object['sql']),'elapsed_seconds'=>microtime(true)-$start,'warnings'=>$warnings,'status'=>'executed'];m316Save($log,$results);m316Assert(!$warnings,'DDL warning on '.$object['name'].'; stopped for assessment.');}
        catch(Throwable $e){$results[]=['name'=>$object['name'],'status'=>'STOPPED','error'=>$e->getMessage()];m316Save($log,$results);throw $e;}}
}
$objects=m316Source();$mode=$argv[1]??'';m316Assert(in_array($mode,['prepare','rehearse','deploy','verify','cleanup_restore'],true),'Use prepare/rehearse/deploy/verify/cleanup_restore.');
if($mode==='cleanup_restore'){$isolated=m316Connect(false);$isolated->exec('DROP DATABASE IF EXISTS '.M316_RESTORE);m316Assert((int)$isolated->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".M316_RESTORE."'")->fetchColumn()===0,'Disposable recovery cleanup failed.');echo "Disposable restore database removed; normal DB untouched.\n";exit;}
$pdo=m316Connect(true);
if(in_array($mode,['prepare','rehearse'],true)){
    m316Absent($pdo,$objects);
    if($mode==='prepare'){
    m316Assert(!file_exists(M316_DIR.'/preflight.json')&&!file_exists(M316_DIR.'/medical1-before.sql'),'Existing backup/preflight; stop rather than overwrite.');
    $pre=['meta'=>$pdo->query('SELECT DATABASE() database_name,@@version version,@@port port,@@sql_mode sql_mode,@@global.sql_mode global_sql_mode,UTC_TIMESTAMP(6) observed_at')->fetch(),'source_sha256'=>M316_SOURCE,'data'=>m316Data($pdo),'metadata'=>m316ExistingMetadata($pdo),'triggers'=>$pdo->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME')->fetchAll(PDO::FETCH_COLUMN)];
    $pre['grants_sha256']=hash('sha256',serialize($pdo->query('SHOW GRANTS')->fetchAll()));m316Save('preflight.json',$pre);
    file_put_contents(dirname(__DIR__).'/database/medicine-phase316-deployment.sql',implode("\n\n",array_map(fn($o)=>$o['kind']==='trigger'?"DELIMITER $$\n".$o['sql']."$$\nDELIMITER ;":$o['sql'],$objects))."\n");
    $ini=M316_DIR.'/client.cnf';$quote=function($value){m316Assert(!preg_match('/[\r\n\x00]/',(string)$value),'Unsupported credential encoding.');return '"'.str_replace(['\\','"'],['\\\\','\\"'],(string)$value).'"';};
    file_put_contents($ini,"[client]\nuser=".$quote(env('DB_USERNAME'))."\npassword=".$quote(env('DB_PASSWORD'))."\nhost=".$quote(env('DB_HOST','localhost'))."\nport=3306\n");
    try{$process=proc_open(['D:/xampp/mysql/bin/mysqldump.exe','--defaults-extra-file='.realpath($ini),'--single-transaction','--skip-lock-tables','--skip-add-locks','--skip-add-drop-table','--routines','--events','--triggers','--hex-blob','--result-file='.realpath(M316_DIR).'/medical1-before.sql','medical1'],[0=>['pipe','r'],1=>['file',M316_DIR.'/dump.stdout','w'],2=>['file',M316_DIR.'/dump.stderr','w']],$pipes);m316Assert(is_resource($process),'Backup process unavailable.');fclose($pipes[0]);$exit=proc_close($process);m316Assert($exit===0,'Backup failed; inspect protected diagnostics.');}finally{if(file_exists($ini)){unlink($ini);}}
    }else{$pre=m316Load('preflight.json');m316Assert($pre['source_sha256']===M316_SOURCE,'Preflight source changed.');}
    $backup=M316_DIR.'/medical1-before.sql';m316Assert(is_file($backup)&&filesize($backup)>0,'Backup absent/empty.');
    m316Assert($pre['data']===m316Data($pdo),'Existing data changed during backup; stop.');
    $isolated=m316Connect(false);m316Assert((int)$isolated->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".M316_RESTORE."'")->fetchColumn()===0,'Restore database already exists.');
    $isolated->exec('CREATE DATABASE '.M316_RESTORE.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$isolated->exec('USE '.M316_RESTORE);
    $import=m316Import($isolated,file_get_contents($backup));m316Assert($pre['data']===m316Data($isolated),'Restored backup data differs.');
    m316Absent($isolated,$objects);m316DDL($isolated,$objects,'rehearsal-ddl.json');$expected=m316Metadata($isolated,$objects);m316Assert(count($expected['tables'])===12&&count($expected['triggers'])===8,'Rehearsal objects missing.');m316Save('expected-metadata.json',$expected);
    $proof=['backup_bytes'=>filesize($backup),'backup_sha256'=>hash_file('sha256',$backup),'restored_tables'=>count($pre['data']),'restore'=> $import,'restored_data_matches'=>true,'schema_rehearsal'=>true,'deployment_file_sha256'=>hash_file('sha256',dirname(__DIR__).'/database/medicine-phase316-deployment.sql')];m316Save('backup-verification.json',$proof);
    echo json_encode(['status'=>'BACKUP_AND_REHEARSAL_PASSED','target'=>$pre['meta'],'existing_tables'=>count($pre['data']),'backup_bytes'=>$proof['backup_bytes'],'rehearsed_objects'=>count($objects)],JSON_PRETTY_PRINT).PHP_EOL;exit;
}
$pre=m316Load('preflight.json');$proof=m316Load('backup-verification.json');m316Assert($proof['restored_data_matches']&&$proof['schema_rehearsal']&&hash_file('sha256',M316_DIR.'/medical1-before.sql')===$proof['backup_sha256'],'Verified recovery backup unavailable.');
if($mode==='deploy'){
    m316Assert(!file_exists(M316_DIR.'/normal-ddl.json'),'Deployment already attempted; no automatic retry.');m316Absent($pdo,$objects);
    m316Assert($pre['data']===m316Data($pdo)&&$pre['metadata']===m316ExistingMetadata($pdo),'Normal pre-deployment state changed; stop.');
    m316Assert(hash_file('sha256',dirname(__DIR__).'/database/medicine-phase316-deployment.sql')===$proof['deployment_file_sha256'],'Reviewed deployment artifact changed.');
    $pre['existing_triggers_sha256']=hash('sha256',serialize($pdo->query("SELECT TRIGGER_NAME,EVENT_MANIPULATION,EVENT_OBJECT_TABLE,ACTION_TIMING,ACTION_STATEMENT,SQL_MODE,DEFINER FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME")->fetchAll()));m316Save('preflight.json',$pre);
    m316DDL($pdo,$objects,'normal-ddl.json');
}
$actual=m316Metadata($pdo,$objects);m316Assert($actual===m316Load('expected-metadata.json'),'Actual metadata differs from exact-source rehearsal.');
$after=m316Data($pdo,array_keys($pre['data']));m316Assert($after===$pre['data'],'Existing data integrity differs; stop.');
$existing=m316ExistingMetadata($pdo);foreach($pre['metadata'] as $name=>$hash){m316Assert(($existing[$name]??null)===$hash,'Existing schema/view changed: '.$name);}
$newTables=array_column($actual['tables'],'TABLE_NAME');$allTables=$pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);m316Assert(!array_diff($allTables,array_merge(array_keys($pre['metadata']),$newTables))&&count($allTables)===count($pre['metadata'])+12,'Unexpected new/missing table/view.');
$allTriggers=$pdo->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);m316Assert(count($allTriggers)===count($pre['triggers'])+8&&!array_diff($allTriggers,array_merge($pre['triggers'],array_column($actual['triggers'],'TRIGGER_NAME'))),'Unexpected trigger inventory.');
$oldTriggers=$pdo->query("SELECT TRIGGER_NAME,EVENT_MANIPULATION,EVENT_OBJECT_TABLE,ACTION_TIMING,ACTION_STATEMENT,SQL_MODE,DEFINER FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME NOT LIKE 'medicine\\_%' ORDER BY TRIGGER_NAME")->fetchAll();m316Assert(hash('sha256',serialize($oldTriggers))===$pre['existing_triggers_sha256'],'Existing triggers changed.');
$empty=m316Data($pdo,$newTables);foreach($empty as $name=>$stats){m316Assert($stats['count']===0,'New governance table not empty: '.$name);}
$meta=$pdo->query('SELECT @@sql_mode sql_mode,@@global.sql_mode global_sql_mode,UTC_TIMESTAMP(6) observed_at')->fetch();m316Assert($meta['sql_mode']===$pre['meta']['sql_mode']&&$meta['global_sql_mode']===$pre['meta']['global_sql_mode'],'SQL mode changed.');m316Assert(hash('sha256',serialize($pdo->query('SHOW GRANTS')->fetchAll()))===$pre['grants_sha256'],'Database privileges changed.');
$config=require dirname(__DIR__).'/app/Config/Config.php';m316Assert($config['medicine_governance']['pilot_enabled']===false&&$config['medicine_governance']['pilot_internal_enabled']===false&&$config['medicine_governance']['pilot_activation']===[],'Pilot no longer disabled.');
$db=(new ReflectionClass(App\Core\Database::class))->newInstanceWithoutConstructor();(new ReflectionProperty(App\Core\Database::class,'pdo'))->setValue($db,$pdo);$repo=new App\Repositories\MedicineGovernanceRepository($db);m316Assert($repo->grants(0,'reviewer','chemical',false)===[]&&$repo->pilotRecords('phase316_read_only_probe','chemical')===[],'Repository read smoke failed.');
$out=['status'=>'DEPLOYED_AND_VERIFIED','meta'=>$meta,'tables'=>12,'triggers'=>8,'columns'=>count($actual['columns']),'indexes'=>count($actual['indexes']),'checks'=>count($actual['checks']),'foreign_keys'=>count($actual['foreign_rules']),'existing_data'=>$after,'new_tables'=>$empty,'metadata_matches_exact_source_rehearsal'=>true,'existing_metadata_unchanged'=>true,'privileges_unchanged'=>true,'repository_read_smoke'=>true,'pilot_enabled'=>false,'public_medicine_published'=>false];m316Save('post-deployment.json',$out);
echo json_encode(array_diff_key($out,array_flip(['existing_data','new_tables'])),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
