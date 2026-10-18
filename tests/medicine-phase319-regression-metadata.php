<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('MEDICINE_OWNER_RECOVERY_LIBRARY',true);
require __DIR__.'/medicine-owner-schema-deployment.php';
$p=new PDO('mysql:host=127.0.0.1;port=3328;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$m=$p->query('SELECT @@port port,@@version version,@@datadir datadir,@@sql_mode mode')->fetch();
oAssert((int)$m['port']===3328&&str_starts_with($m['version'],'10.4.27-')&&$m['mode']===O_MODE&&strcasecmp(rtrim(str_replace('\\','/',$m['datadir']),'/'),str_replace('\\','/',realpath(__DIR__.'/.medicine38-validation/data')))===0,'Disposable regression diagnostic guard failed.');
$source=file_get_contents(dirname(__DIR__).'/database/structure.sql');
preg_match_all('/CREATE TABLE `(kobi_medicine_[^`]+|medicine_[^`]+|health_medicine_identity_links|health_medicine_selected_ingredients)` \((.*?)\) ENGINE=/s',$source,$tables,PREG_SET_ORDER);
$proof=['authoritative_medicine_tables'=>count($tables),'legacy_expected_medicine_tables'=>13,'databases'=>[]];
foreach(['kobi_medicine_phase312_validation_20261009','kobi_medicine_phase313_validation_20261010'] as $db){$p->exec('USE '.$db);$checks=[];foreach($tables as $table){$actual=$p->query('SHOW CREATE TABLE `'.$table[1].'`')->fetch()['Create Table'];preg_match_all('/CONSTRAINT `([^`]+)`/',$table[2],$expected);preg_match_all('/CONSTRAINT `([^`]+)`/',$actual,$found);sort($expected[1]);sort($found[1]);$constraintMatch=$expected[1]===$found[1];preg_match_all('/(?:UNIQUE )?KEY `([^`]+)`/',$table[2],$expected);preg_match_all('/(?:UNIQUE )?KEY `([^`]+)`/',$actual,$found);preg_match_all('/CONSTRAINT `([^`]+)` FOREIGN KEY/',$table[2],$foreign);$indexMatch=array_diff($expected[1],$found[1])===[]&&array_diff($found[1],$expected[1],$foreign[1])===[];oAssert($constraintMatch&&$indexMatch,'Actual imported constraint/index differs: '.$table[1]);$checks[$table[1]]=['constraints_match'=>true,'indexes_match'=>true];}
    $names=$p->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME LIKE 'medicine_%' ORDER BY TRIGGER_NAME")->fetchAll(PDO::FETCH_COLUMN);$proof['databases'][$db]=['tables_checked'=>$checks,'medicine_trigger_names'=>$names,'medicine_trigger_count'=>count($names),'legacy_expected_medicine_trigger_count'=>8,'provisional_trigger_count'=>(int)$p->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME LIKE 'provisional_identity_%'")->fetchColumn()];
}
oSave('phase319-metadata-regression-diagnostic.json',$proof);
echo json_encode(['authoritative_medicine_tables'=>count($tables),'legacy_table_expectation'=>13,'actual_medicine_triggers'=>array_map(fn($r)=>$r['medicine_trigger_count'],$proof['databases']),'legacy_trigger_expectation'=>8,'all_constraint_index_comparisons_match'=>true],JSON_PRETTY_PRINT).PHP_EOL;
