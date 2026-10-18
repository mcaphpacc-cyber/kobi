<?php
declare(strict_types=1);

// Validation utility only. No application bootstrap or existing database selected.
require dirname(__DIR__).'/app/Helpers/functions.php';
$name='kobi_phase2c_validation_20261008';
$root=dirname(__DIR__);
$schema=$root.'/database/structure.sql';
$mode='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
$omissions=[
    "/*!40101 SET SQL_MODE=''*/;",
    "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;",
    '/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;',
];
$report=['database'=>$name,'schema_sha256'=>hash_file('sha256',$schema),'omitted'=>[],'executed'=>[],'status'=>'STARTED'];
$save=static function () use (&$report,$root): void {
    file_put_contents($root.'/docs/TREATMENT_PHASE_2C_IMPORT_RESULTS.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n");
};
try {
    $pdo=new PDO('mysql:host='.env('DB_HOST','localhost').';port='.env('DB_PORT','3306').';charset=utf8mb4',env('DB_USERNAME'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $report['host']=env('DB_HOST','localhost').':'.env('DB_PORT','3306');
    $report['server']=$pdo->query('SELECT VERSION() AS version, @@sql_mode AS session_sql_mode, @@global.sql_mode AS global_sql_mode')->fetch();
    if ($report['server']['version']!=='10.4.27-MariaDB' || $report['server']['session_sql_mode']!==$mode || $report['server']['global_sql_mode']!==$mode) { throw new RuntimeException('Unexpected server version or SQL mode; stopped before DDL.'); }
    $check=$pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=:name'); $check->execute(['name'=>$name]);
    if ($check->fetch()) { throw new RuntimeException('Isolated database already exists; refusing to overwrite it.'); }
    // Parse the dump's client DELIMITER commands without changing SQL objects.
    $statements=[]; $buffer=''; $delimiter=';'; $start=1;
    foreach (file($schema) as $index=>$line) {
        $trim=trim($line);
        if (in_array($trim,$omissions,true)) { $report['omitted'][]=['line'=>$index+1,'sql'=>$trim]; continue; }
        if (preg_match('/^DELIMITER\s+(\S+)$/',$trim,$match)) { if (trim($buffer)!=='') { throw new RuntimeException('Unexpected pending SQL at DELIMITER; no import executed.'); } $delimiter=$match[1]; continue; }
        if ($buffer==='') { $start=$index+1; }
        $buffer.=$line;
        if ($trim!=='' && str_ends_with($trim,$delimiter)) {
            $statement=substr(rtrim($buffer),0,-strlen($delimiter));
            $statements[]=['line'=>$start,'sql'=>$statement]; $buffer='';
        }
    }
    if (trim($buffer)!=='' || count($report['omitted'])!==3) { throw new RuntimeException('Unexpected dump parsing/omission count; no import executed.'); }
    $report['parsed_statement_count']=count($statements);
    $pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    $report['database_created']=true;
    $pdo->exec('USE `'.$name.'`');
    if ($pdo->query('SELECT DATABASE()')->fetchColumn()!==$name) { throw new RuntimeException('Wrong selected database.'); }
    foreach ($statements as $number=>$statement) {
        try { $pdo->exec($statement['sql']); }
        catch (PDOException $error) {
            $report['status']='SCHEMA_IMPORT_FAILED';
            $report['failed_statement']=['number'=>$number+1]+$statement;
            $report['mariadb_error']=$error->errorInfo;
            $report['after']=$pdo->query('SELECT DATABASE() AS selected_database, @@sql_mode AS session_sql_mode, @@global.sql_mode AS global_sql_mode')->fetch();
            $save(); echo json_encode(['status'=>$report['status'],'executed'=>count($report['executed']),'failed'=>$report['failed_statement'],'error'=>$report['mariadb_error'],'after'=>$report['after']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n"; exit(2);
        }
        $report['executed'][]=['number'=>$number+1]+$statement;
    }
    $report['after']=$pdo->query('SELECT DATABASE() AS selected_database, @@sql_mode AS session_sql_mode, @@global.sql_mode AS global_sql_mode')->fetch();
    $report['status']='SCHEMA_IMPORTED'; $save();
    echo json_encode(['status'=>$report['status'],'executed'=>count($report['executed']),'omitted'=>$report['omitted'],'after'=>$report['after']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $error) {
    $report['status']='PRECHECK_OR_IMPORT_TOOL_FAILED';
    $report['error_class']=get_class($error); $report['error_code']=$error->getCode();
    if ($error instanceof RuntimeException && !$error instanceof PDOException) { $report['reason']=$error->getMessage(); }
    $save(); echo json_encode(['status'=>$report['status'],'class'=>$report['error_class'],'code'=>$report['error_code'],'reason'=>$report['reason']??'Connection failed; credentials suppressed'])."\n"; exit(1);
}
