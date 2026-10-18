<?php
declare(strict_types=1);
define('MEDICINE_OWNER_RECOVERY_LIBRARY', true);
require __DIR__.'/medicine-owner-schema-deployment.php';
$p=oConnect(false);
$cases=[];
foreach ([
    'block footer'=>["DO 1;\n/*End Medicine Phase 3.19. */\n",true],
    'line footer'=>["DO 1;\n-- end\n",true],
    'empty footer'=>["DO 1;\n",true],
    'incomplete SQL'=>["DO 1;\nSELECT 2",false],
    'unexecuted executable comment'=>["DO 1;\n/*!40101 SET @footer_test=1 */",false],
    'unterminated comment'=>["DO 1;\n/* end",false],
] as $name=>[$sql,$accept]) {
    $passed=false;
    try {$r=oImport($p,$sql);$passed=$accept&&$r['statements']===1&&$r['warnings']===[];}
    catch(RuntimeException $e){$passed=!$accept&&$e->getMessage()==='Incomplete dump import.';}
    $cases[]=['name'=>$name,'passed'=>$passed];
}
$entries=[];
$r=oImport($p,"DO CAST('footer-warning' AS UNSIGNED);\n/* end */",null,function(array $e)use(&$entries){$entries[]=$e;});
$cases[]=['name'=>'warning remains captured','passed'=>count($entries)===1&&$entries[0]['condition_count']===1&&$entries[0]['warnings'][0]['Code']===1292&&count($r['warnings'])===1];
$proof=['cases'=>count($cases),'failures'=>count(array_filter($cases,fn($c)=>!$c['passed'])),'results'=>$cases];
oSave('phase319-final-footer-tests.json',$proof);
echo json_encode($proof,JSON_PRETTY_PRINT).PHP_EOL;
exit($proof['failures']?1:0);
