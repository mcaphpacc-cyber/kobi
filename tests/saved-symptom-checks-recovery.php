<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
const SSC_RECOVERY_DB = 'kobi_saved_checks_recovery_20261013';
const SSC_BACKUP_DIR = __DIR__ . '/.saved-checks-recovery';
function sscAssert(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }
function sscIdent(string $name): string { sscAssert((bool)preg_match('/^[a-zA-Z0-9_]+$/D', $name),'Unsupported object identifier.'); return '`'.$name.'`'; }
function sscCapture(PDO $pdo): array {
    $capture=['tables'=>[],'views'=>[],'triggers'=>[], 'mode'=>$pdo->query('SELECT @@sql_mode session_mode,@@global.sql_mode global_mode')->fetch()];
    sscAssert((int)$pdo->query('SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE()')->fetchColumn()===0
        &&(int)$pdo->query('SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA=DATABASE()')->fetchColumn()===0,'Routines/events require a separate recovery procedure; stopped.');
    foreach($pdo->query('SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME')->fetchAll() as $item){
        $name=$item['TABLE_NAME'];$show=$pdo->query('SHOW CREATE TABLE '.sscIdent($name))->fetch();
        if($item['TABLE_TYPE']==='VIEW'){$capture['views'][$name]=$show['Create View'];continue;}
        $columns=$pdo->query('SHOW FULL COLUMNS FROM '.sscIdent($name))->fetchAll();
        $capture['tables'][$name]=['ddl'=>$show['Create Table'],'columns'=>$columns,'rows'=>$pdo->query('SELECT * FROM '.sscIdent($name))->fetchAll()];
    }
    foreach($pdo->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME')->fetchAll() as $item){
        $show=$pdo->query('SHOW CREATE TRIGGER '.sscIdent($item['TRIGGER_NAME']))->fetch();unset($show['Created']);
        $capture['triggers'][$item['TRIGGER_NAME']]=$show;
    }
    $capture['database_defaults']=$pdo->query('SELECT DEFAULT_CHARACTER_SET_NAME,DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')->fetch();
    return $capture;
}
function sscDigest(array $capture,string $database): string {
    foreach($capture['tables'] as &$table){$rows=array_map('serialize',$table['rows']);sort($rows);$table['rows']=$rows;}unset($table);
    // Database identifiers and trigger creation timestamps differ in the isolated copy.
    return hash('sha256',str_replace('`'.$database.'`','`__database__`',serialize($capture)));
}
if (defined('SSC_RECOVERY_LIBRARY')) { return; }
$mode=$argv[1]??'verify';
$pdo=(new App\Core\Database())->connection();
sscAssert($pdo->query('SELECT DATABASE()')->fetchColumn()==='medical1','Normal source identity differs.');
$backup=SSC_BACKUP_DIR.'/before.bin';$certificate=SSC_BACKUP_DIR.'/verified.json';
if($mode==='backup-and-rehearse'){
    sscAssert(!is_file($backup)&&!is_file($certificate),'Recovery evidence already exists; do not overwrite or restart.');
    if(!is_dir(SSC_BACKUP_DIR)){mkdir(SSC_BACKUP_DIR,0700,true);}
    file_put_contents(SSC_BACKUP_DIR.'/.htaccess',"Require all denied\n");
    $normal=sscCapture($pdo);
    sscAssert(!isset($normal['tables']['user_saved_symptom_checks']),'Schema already deployed; stopped.');
    $pdo->beginTransaction();
    try{$normal=sscCapture($pdo);$serialized=serialize($normal);sscAssert(file_put_contents($backup,$serialized)===strlen($serialized),'Backup write incomplete.');}finally{$pdo->rollBack();}
    $restored=unserialize(file_get_contents($backup),['allowed_classes'=>false]);
    sscAssert($restored===$normal&&hash_file('sha256',$backup)===hash('sha256',$serialized),'Backup readback failed.');
    foreach($normal['triggers'] as $trigger){sscAssert($trigger['sql_mode']===$normal['mode']['session_mode'],'Trigger SQL-mode context differs; cannot rehearse without changing SQL mode.');}
    sscAssert((int)$pdo->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".SSC_RECOVERY_DB."'")->fetchColumn()===0,'Recovery database exists; no overwrite.');
    $defaults=$normal['database_defaults'];
    $pdo->exec('CREATE DATABASE '.sscIdent(SSC_RECOVERY_DB).' CHARACTER SET '.sscIdent($defaults['DEFAULT_CHARACTER_SET_NAME']).' COLLATE '.sscIdent($defaults['DEFAULT_COLLATION_NAME']));
    $pdo->exec('USE '.sscIdent(SSC_RECOVERY_DB));
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach($restored['tables'] as $name=>$table){
        $pdo->exec($table['ddl']);
        $columns=array_values(array_filter($table['columns'],fn($column)=>!str_contains($column['Extra'],'GENERATED')));
        $names=array_column($columns,'Field');
        $statement=$pdo->prepare('INSERT INTO '.sscIdent($name).' ('.implode(',',array_map('sscIdent',$names)).') VALUES ('.implode(',',array_fill(0,count($names),'?')).')');
        foreach($table['rows'] as $row){$statement->execute(array_map(fn($column)=>$row[$column],$names));sscAssert($pdo->query('SHOW WARNINGS')->fetchAll()===[],'Recovery row warning; stopped.');}
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    foreach($restored['views'] as $sql){$pdo->exec(str_replace('`medical1`','`'.SSC_RECOVERY_DB.'`',$sql));}
    foreach($restored['triggers'] as $trigger){
        $pdo->exec('SET NAMES '.sscIdent($trigger['character_set_client']).' COLLATE '.sscIdent($trigger['collation_connection']));
        $pdo->exec(str_replace('`medical1`','`'.SSC_RECOVERY_DB.'`',$trigger['SQL Original Statement']));
        sscAssert($pdo->query('SHOW WARNINGS')->fetchAll()===[],'Trigger recovery warning; stopped.');
    }
    $pdo->exec('SET NAMES utf8mb4');
    $copy=sscCapture($pdo);
    sscAssert(sscDigest($copy,SSC_RECOVERY_DB)===sscDigest($normal,'medical1'),'Recovered schema/columns/rows/views/triggers differ; no normal deployment.');
    $pdo->exec('USE `medical1`');
    sscAssert(sscDigest(sscCapture($pdo),'medical1')===sscDigest($normal,'medical1'),'Normal data changed during rehearsal; stopped.');
    $proof=['status'=>'RECOVERY_VERIFIED','backup_sha256'=>hash_file('sha256',$backup),'backup_bytes'=>filesize($backup),'normal_fingerprint'=>sscDigest($normal,'medical1'),'database'=>'medical1','recovery_database'=>SSC_RECOVERY_DB,'tables'=>count($normal['tables']),'views'=>count($normal['views']),'triggers'=>count($normal['triggers']),'sql_mode'=>$normal['mode'],'artifact_sha256'=>hash_file('sha256',dirname(__DIR__).'/database/saved-symptom-checks.sql')];
    file_put_contents($certificate,json_encode($proof,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));echo json_encode($proof,JSON_PRETTY_PRINT),"\n";
}elseif($mode==='final-state'){
    $normal=unserialize(file_get_contents($backup),['allowed_classes'=>false]);$current=sscCapture($pdo);
    $testRow=$pdo->query('SELECT snapshot_json FROM user_saved_symptom_checks WHERE id=1')->fetchColumn();
    sscAssert(is_string($testRow),'Expected single browser test row missing before cleanup.');
    $selected=array_column(json_decode($testRow,true,32,JSON_THROW_ON_ERROR)['selected'],'id');
    unset($current['tables']['user_saved_symptom_checks']);
    $deltas=[];$oldById=array_column($normal['tables']['symptoms']['rows'],null,'id');
    foreach($current['tables']['symptoms']['rows'] as &$row){
        $old=$oldById[$row['id']];$delta=(int)$row['search_count']-(int)$old['search_count'];
        if($delta!==0){sscAssert(in_array((int)$row['id'],$selected,true)&&$delta===1,'Unexpected search-counter delta.');$deltas[]=['symptom_id'=>(int)$row['id'],'increment'=>$delta];}
        $row['search_count']=$old['search_count'];
    }unset($row);
    sscAssert(count($deltas)===count($selected)&&sscDigest($current,'medical1')===sscDigest($normal,'medical1'),'Unrelated normal schema/data differs from recovery checkpoint.');
    echo json_encode(['status'=>'PASS','existing_data_and_schema_preserved_except_intentional_check_counters'=>true,'counter_deltas'=>$deltas,'saved_test_rows'=>(int)$pdo->query('SELECT COUNT(*) FROM user_saved_symptom_checks')->fetchColumn()],JSON_PRETTY_PRINT),"\n";
}elseif($mode==='cleanup'){
    $proof=json_decode(file_get_contents($certificate),true,512,JSON_THROW_ON_ERROR);
    sscAssert($proof['status']==='RECOVERY_VERIFIED'&&$proof['recovery_database']===SSC_RECOVERY_DB&&hash_file('sha256',$backup)===$proof['backup_sha256'],'Verified recovery identity missing.');
    $pdo->exec('DROP DATABASE '.sscIdent(SSC_RECOVERY_DB));echo "PASS: disposable recovery copy removed; backup retained.\n";
}else{
    $proof=json_decode(file_get_contents($certificate),true,512,JSON_THROW_ON_ERROR);
    sscAssert($proof['status']==='RECOVERY_VERIFIED'&&hash_file('sha256',$backup)===$proof['backup_sha256'],'Backup certificate verification failed.');
    echo "PASS: verified backup remains readable and unchanged.\n";
}
