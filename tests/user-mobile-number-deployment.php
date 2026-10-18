<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
define('SSC_RECOVERY_LIBRARY',true);
require __DIR__.'/saved-symptom-checks-recovery.php';
const MC_RECOVERY_DB='kobi_mobile_number_recovery_20261014';
const MC_RECOVERY_DIR=__DIR__.'/.mobile-number-recovery';
$action=$argv[1]??'verify'; $pdo=(new App\Core\Database())->connection();
sscAssert($pdo->query('SELECT DATABASE()')->fetchColumn()==='medical1','Normal database identity differs; stopped.');
$ddl=file_get_contents(dirname(__DIR__).'/database/user-mobile-number.sql');
sscAssert(substr_count($ddl,'ALTER TABLE users')===1 && !preg_match('/\b(?:DROP|INSERT|UPDATE|DELETE FROM)\b|^\s*SET\b/im',$ddl),'Unexpected deployment DDL scope.');
$approved=preg_match('/\R(ALTER TABLE users.*?;)\R```/s',file_get_contents(dirname(__DIR__).'/docs/task-45a-medication-schedule-mobile-readiness.md'),$matches)?$matches[1]:'';
sscAssert(str_replace("\r\n","\n",trim($ddl))===str_replace("\r\n","\n",trim($approved)),'DDL differs from approved 45A design.');
$tables=['users'];
$backup=MC_RECOVERY_DIR.'/before.bin'; $certificate=MC_RECOVERY_DIR.'/verified.json';
if($action==='browser-value'){
    sscAssert($pdo->query('SELECT mobile_number FROM users WHERE id=1')->fetchColumn()==='+12025550101','Browser update did not persist the expected normalized synthetic number.');
    echo "PASS: genuine browser update persisted the expected normalized synthetic number (value not printed).\n";exit;
}
function mcSchema(string $ddl): string { return preg_replace('/ AUTO_INCREMENT=\d+/','',$ddl); }
function mcApply(PDO $pdo,string $ddl): void {
    foreach(explode(';',$ddl) as $statement){if(trim($statement)===''){continue;}$pdo->exec($statement);sscAssert($pdo->query('SHOW WARNINGS')->fetchAll()===[],'Schema statement produced warnings; stopped.');}
}
if($action==='backup-and-rehearse'){
    sscAssert(!is_file($backup)&&!is_file($certificate),'Recovery evidence exists; do not overwrite/restart.');
    $pdo->beginTransaction();try{$normal=sscCapture($pdo);}finally{$pdo->rollBack();}
    sscAssert(!in_array('mobile_number',array_column($normal['tables']['users']['columns'],'Field'),true),'Mobile schema already exists; stopped.');
    sscAssert(str_starts_with($pdo->query('SELECT VERSION()')->fetchColumn(),'10.4.27-MariaDB'),'Target MariaDB version differs; inspect before proceeding.');
    mkdir(MC_RECOVERY_DIR,0700,true);file_put_contents(MC_RECOVERY_DIR.'/.htaccess',"Require all denied\n");
    $serialized=serialize($normal);sscAssert(file_put_contents($backup,$serialized)===strlen($serialized),'Incomplete backup.');
    $restored=unserialize(file_get_contents($backup),['allowed_classes'=>false]);
    sscAssert($restored===$normal&&hash_file('sha256',$backup)===hash('sha256',$serialized),'Backup readback differs.');
    foreach($normal['triggers'] as $trigger){sscAssert($trigger['sql_mode']===$normal['mode']['session_mode'],'Trigger SQL mode differs; recovery cannot proceed without changing mode.');}
    sscAssert((int)$pdo->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".MC_RECOVERY_DB."'")->fetchColumn()===0,'Disposable database exists; no overwrite.');
    $defaults=$normal['database_defaults'];
    $pdo->exec('CREATE DATABASE '.sscIdent(MC_RECOVERY_DB).' CHARACTER SET '.sscIdent($defaults['DEFAULT_CHARACTER_SET_NAME']).' COLLATE '.sscIdent($defaults['DEFAULT_COLLATION_NAME']));
    $pdo->exec('USE '.sscIdent(MC_RECOVERY_DB));$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach($restored['tables'] as $name=>$table){
        $pdo->exec($table['ddl']);$columns=array_values(array_filter($table['columns'],fn($column)=>!str_contains($column['Extra'],'GENERATED')));$names=array_column($columns,'Field');
        $statement=$pdo->prepare('INSERT INTO '.sscIdent($name).' ('.implode(',',array_map('sscIdent',$names)).') VALUES ('.implode(',',array_fill(0,count($names),'?')).')');
        foreach($table['rows'] as $row){$statement->execute(array_map(fn($column)=>$row[$column],$names));sscAssert($pdo->query('SHOW WARNINGS')->fetchAll()===[],'Recovery row warning; stopped.');}
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    foreach($restored['views'] as $sql){$pdo->exec(str_replace('`medical1`','`'.MC_RECOVERY_DB.'`',$sql));}
    foreach($restored['triggers'] as $trigger){
        $pdo->exec('SET NAMES '.sscIdent($trigger['character_set_client']).' COLLATE '.sscIdent($trigger['collation_connection']));
        $pdo->exec(str_replace('`medical1`','`'.MC_RECOVERY_DB.'`',$trigger['SQL Original Statement']));
        sscAssert($pdo->query('SHOW WARNINGS')->fetchAll()===[],'Recovery trigger warning; stopped.');
    }
    $pdo->exec('SET NAMES utf8mb4');
    sscAssert(sscDigest(sscCapture($pdo),MC_RECOVERY_DB)===sscDigest($normal,'medical1'),'Recovered objects, trigger metadata or data differ; no deployment.');
    // Only after full recovery fidelity: validate the exact approved schedule DDL.
    mcApply($pdo,$ddl);$expected=[];
    foreach($tables as $table){$expected[$table]=mcSchema($pdo->query('SHOW CREATE TABLE '.sscIdent($table))->fetch()['Create Table']);}
    $pdo->exec('USE medical1');sscAssert(sscDigest(sscCapture($pdo),'medical1')===sscDigest($normal,'medical1'),'Normal state changed during rehearsal.');
    $proof=['status'=>'RECOVERY_VERIFIED','normal_fingerprint'=>sscDigest($normal,'medical1'),'backup_sha256'=>hash_file('sha256',$backup),
        'artifact_sha256'=>hash('sha256',$ddl),'recovery_database'=>MC_RECOVERY_DB,'expected_schema'=>$expected,'sql_mode'=>$normal['mode']];
    file_put_contents($certificate,json_encode($proof,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    echo "PASS: full backup recovery verified; exact mobile-column DDL accepted by MariaDB 10.4.27 without warnings; normal KOBI unchanged.\n";
}else{
    $proof=json_decode(file_get_contents($certificate),true,512,JSON_THROW_ON_ERROR);
    sscAssert($proof['status']==='RECOVERY_VERIFIED' && hash_file('sha256',$backup)===$proof['backup_sha256'] && hash('sha256',$ddl)===$proof['artifact_sha256'],'Recovery/artifact certificate differs.');
    $normal=unserialize(file_get_contents($backup),['allowed_classes'=>false]);
    if($action==='deploy-normal'){
        sscAssert(is_file(MC_RECOVERY_DIR.'/mariadb-passed.json'),'Required isolated tests not completed.');
        $tests=json_decode(file_get_contents(MC_RECOVERY_DIR.'/mariadb-passed.json'),true,512,JSON_THROW_ON_ERROR);
        sscAssert($tests['status']==='PASS' && $tests['artifact_sha256']===$proof['artifact_sha256'],'Isolated test certificate differs.');
        sscAssert(sscDigest(sscCapture($pdo),'medical1')===$proof['normal_fingerprint'],'Normal checkpoint changed; no deployment.');
        mcApply($pdo,$ddl);
    }elseif($action==='cleanup'){
        sscAssert($proof['recovery_database']===MC_RECOVERY_DB,'Recovery cleanup identity differs.');
        $pdo->exec('DROP DATABASE '.sscIdent(MC_RECOVERY_DB));echo "PASS: disposable recovery/test database removed; verified backup retained.\n";exit;
    }
    $current=sscCapture($pdo);
    sscAssert(mcSchema($current['tables']['users']['ddl'])===$proof['expected_schema']['users'],'Users schema differs from approved isolated definition.');
    $current['tables']['users']['ddl']=$normal['tables']['users']['ddl'];
    $columns=$current['tables']['users']['columns'];
    sscAssert(count($columns)===count($normal['tables']['users']['columns'])+1,'Unexpected column count.');
    $current['tables']['users']['columns']=array_values(array_filter($columns,fn($c)=>$c['Field']!=='mobile_number'));
    $old=array_column($normal['tables']['users']['rows'],null,'id');
    foreach($current['tables']['users']['rows'] as &$row){
        sscAssert($row['mobile_number']===null,'Temporary mobile number remains.'); unset($row['mobile_number']);
        if($action==='final-state' && (int)$row['id']===1){
            // Expected updated_at change from genuine account contact edits; no manual reset.
            $row['updated_at']=$old[$row['id']]['updated_at'];
        }
    }unset($row);
    sscAssert(sscDigest($current,'medical1')===sscDigest($normal,'medical1'),'Existing data/schema or SQL mode differs.');
    echo "PASS: approved nullable non-unique mobile column/CHECK; all existing objects/data and SQL modes preserved.\n";
}
