<?php
declare(strict_types=1);

// Phase 3.14: SELECT-only registry audit. No bootstrap, sessions, services or mutations.
require dirname(__DIR__).'/vendor/autoload.php';
function medicineSelectionQuery(PDO $pdo,string $sql,array $parameters=[]):array {
    if(!preg_match('/\ASELECT\s/i',$sql)||str_contains($sql,';')||preg_match('/\b(INTO|OUTFILE|DUMPFILE|FOR|LOCK|SLEEP|BENCHMARK|GET_LOCK|RELEASE_LOCK)\b/i',$sql)){throw new RuntimeException('Read-only audit query rejected.');}
    $statement=$pdo->prepare($sql);$statement->execute($parameters);return $statement->fetchAll(PDO::FETCH_ASSOC);
}
function medicineSelectionCollisionKey(string $name):string {
    if(!class_exists(Normalizer::class)||!function_exists('mb_convert_case')){throw new RuntimeException('Required Unicode normalization unavailable.');}
    $nfc=Normalizer::normalize($name,Normalizer::FORM_C);if($nfc===false){throw new RuntimeException('Invalid registry UTF-8');}
    return preg_replace('/[\p{P}\p{Z}\s]+/u','',mb_convert_case($nfc,MB_CASE_FOLD,'UTF-8'));
}
function medicineSelectionAnalyze(array $snapshot):array {
    $rows=$snapshot['rows'];$members=[];$nameMembers=[];$forms=[];$groups=[];$exact=[];
    usort($rows,fn($a,$b)=>strlen((string)$a['substance_id'])<=>strlen((string)$b['substance_id'])?:strcmp((string)$a['substance_id'],(string)$b['substance_id']));
    foreach($snapshot['items'] as $item){if($item['substance_id']!==null){$members[(string)$item['substance_id']]=1;}else{$nameMembers[medicineSelectionCollisionKey($item['component_name'])]=1;}}
    foreach($snapshot['forms'] as $form){$forms[(string)$form['substance_id']]=1;}
    foreach($rows as $row){$groups[medicineSelectionCollisionKey($row['canonical_name'])][]=(string)$row['substance_id'];$exact[mb_strtolower($row['canonical_name'],'UTF-8')]=(string)$row['substance_id'];}
    $collision=[];$collisionGroups=0;foreach($groups as $group){if(count($group)>1){$collisionGroups++;foreach($group as $id){$collision[$id]=1;}}}
    $peers=[];foreach($rows as $row){$words=explode(' ',mb_strtolower($row['canonical_name']));while(count($words)>1){array_pop($words);$prefix=implode(' ',$words);if(isset($exact[$prefix])){$peers[$exact[$prefix]][]=(string)$row['substance_id'];}}}
    // Explicit observed near-spelling labels are held, not declared synonyms or merged.
    $near=[];foreach([['Aciclovir','Acyclovir'],['Valaciclovir','Valacyclovir'],['Cilostazol','Cilostazole']] as $pair){if(isset($exact[mb_strtolower($pair[0])],$exact[mb_strtolower($pair[1])])){foreach($pair as $name){$near[$exact[mb_strtolower($name)]]=1;}}}
    $counts=array_fill_keys(['combination','formatting_collision','ambiguous_identity','chemical_form_dependency','malformed_incomplete_name','other'],0);$overlap=$counts;$eligible=[];$assessed=[];
    foreach($rows as $row){$id=(string)$row['substance_id'];$name=$row['canonical_name'];$reasons=[];
        $combination=isset($members[$id])||isset($nameMembers[medicineSelectionCollisionKey($name)])||preg_match('/[+\/]|\b(?:and|with|combination|combo)\b/i',$name);
        $bad=trim($name)===''||preg_match('/[\x00-\x1f\x7f]|\b(?:ds|sr|cr|xl|mr)\b/i',$name)||mb_strlen($name)<4;
        $form=isset($forms[$id])||preg_match('/\b(?:hydrochloride|hydrobromide|bromide|chloride|sulfate|sulphate|phosphate|acetate|succinate|tartrate|maleate|mesylate|mesilate|besilate|citrate|fumarate|sodium|potassium|calcium|hydrate|hydrated|anhydrous|monohydrate|dihydrate|hemihydrate|solvate|ester|palmitate|pamoate|tosylate|tosilate|disulfate|disulphate)\b/i',$name);
        $ambiguous=isset($near[$id])||preg_match('/\b(?:extract|oil|powder|vaccine|immunoglobulin|insulin|lysate|collagen|vitamin|vitamins|peptide|decapeptide|phospholipid|probiotic|bacterial|bacillus|saccharomyces|botulinum|toxin|interferon|erythropoietin|gonadotropin|gonadotrophin|hormone|glycosides|enzymes|enzyme|heparin)\b|(?:mab|cept|ase)\z/i',$name);
        $formulation=preg_match('/\b(?:injection|implant|liposomal|liposome|micronized|dispersion|nanodispersion|concentrate|solution|suspension|tablet|capsule|recombinant)\b/i',$name);
        $peer=$peers[$id]??[];
        if($combination){$reasons[]='combination';}if(isset($collision[$id])){$reasons[]='formatting_collision';}if($ambiguous){$reasons[]='ambiguous_identity';}if($form){$reasons[]='chemical_form_dependency';}if($bad){$reasons[]='malformed_incomplete_name';}if($formulation||(string)$row['is_active']!=='1'||(string)$row['is_searchable']!=='1'){$reasons[]='other';}
        foreach($reasons as $reason){$overlap[$reason]++;}
        $primary=null;foreach(['combination','formatting_collision','malformed_incomplete_name','chemical_form_dependency','ambiguous_identity','other'] as $reason){if(in_array($reason,$reasons,true)){$primary=$reason;break;}}
        if($primary!==null){$counts[$primary]++;}
        $simple=(bool)preg_match('/\A[A-Z][a-z]{3,19}\z/D',$name);$label=(bool)preg_match('/\A[A-Z][A-Za-z ]{3,59}\z/D',$name);
        $parts=['identity_label'=>$simple?20:($label?15:0),'single_ingredient'=>(!$combination?20:0),'clean_name'=>$simple?10:($label?5:0),'no_collision'=>(!isset($collision[$id])?10:0),'no_form_dependency'=>(!$form&&!$formulation?($peer?5:20):0),'model_representation'=>(!$combination&&!$ambiguous&&!$form&&!$formulation&&!$bad?10:0),'appraised_evidence'=>0];
        $entry=$row+['single_substance'=>'name-level single label only; chemical identity unverified','combination_related'=>(bool)$combination,'collision'=>isset($collision[$id]),'chemical_form_status'=>$form||$formulation?'requires_unresolved_chemical_form_model':($peer?'possible_form_issue':'clean'),'peer_ids'=>$peer,'reasons'=>$reasons,'primary_exclusion'=>$primary,'score_parts'=>$parts,'score'=>array_sum($parts)];
        $assessed[$id]=$entry;if($primary===null){$eligible[]=$entry;}
    }
    usort($eligible,fn($a,$b)=>$b['score']<=>$a['score']?:strcmp(hash('sha256',$a['canonical_name_normalized']),hash('sha256',$b['canonical_name_normalized'])));
    $a=[];$b=[];
    foreach(['54','319','15','156','112','481','2064','1685','1776','2219'] as $id){if(!isset($assessed[$id])||$assessed[$id]['primary_exclusion']!==null||$assessed[$id]['score']!==90||$assessed[$id]['chemical_form_status']!=='clean'){throw new RuntimeException('Curated Group A requires renewed audit.');}$a[]=$assessed[$id];}
    foreach(['93','133','1404','1418','2057'] as $id){if(!isset($assessed[$id])||$assessed[$id]['primary_exclusion']!==null){throw new RuntimeException('Curated Group B requires renewed audit.');}$b[]=$assessed[$id];}
    $examples=[];foreach($counts as $reason=>$n){$examples[$reason]=array_slice(array_values(array_filter($assessed,fn($r)=>$r['primary_exclusion']===$reason)),0,3);}
    return ['total'=>count($rows),'primary_exclusions'=>$counts,'overlapping_exclusions'=>$overlap,'eligible_pool'=>count($eligible),'score_distribution'=>array_count_values(array_column($eligible,'score')),'collision_groups'=>$collisionGroups,'collision_rows'=>count($collision),'combination_explicit_rows'=>count($members),'unlinked_combination_members'=>count(array_filter($snapshot['items'],fn($i)=>$i['substance_id']===null)),'group_a'=>$a,'group_b'=>$b,'examples'=>$examples];
}
// Reuse SELECT-only and Unicode collision helpers without running the census CLI.
if (defined('MEDICINE_SELECTION_LIBRARY')) { return; }
$mode=$argv[1]??'';
if($mode==='test'){
    $snapshot=json_decode(file_get_contents(__DIR__.'/.medicine314-snapshot.json'),true,64,JSON_THROW_ON_ERROR);$checks=0;
    $check=function(bool $ok,string $message)use(&$checks){$checks++;if(!$ok){throw new RuntimeException($message);}};
    $a=medicineSelectionAnalyze($snapshot);$b=medicineSelectionAnalyze($snapshot);$check($a===$b,'Repeated scoring differs');
    $reversed=$snapshot;$reversed['rows']=array_reverse($snapshot['rows']);$reversed['items']=array_reverse($snapshot['items']);$check($a===medicineSelectionAnalyze($reversed),'Input-order dependence');
    $candidates=array_merge($a['group_a'],$a['group_b']);$ids=array_map(fn($r)=>(string)$r['substance_id'],$candidates);$check(count(array_unique($ids))===15,'Duplicate shortlist ID');
    foreach($candidates as $row){$check(!$row['combination_related']&&!$row['collision']&&$row['chemical_form_status']==='clean'&&$row['reasons']===[],'Hard exclusion leaked into shortlist');$check($row['score']===array_sum($row['score_parts'])&&$row['score']<=100&&$row['score_parts']['appraised_evidence']===0,'Unreproducible score or invented evidence');}
    $check(array_sum($a['primary_exclusions'])+$a['eligible_pool']===$a['total'],'Primary reasons do not partition registry');
    $check($a['collision_groups']===count($snapshot['collisions'])&&$a['collision_rows']===array_sum(array_map('count',$snapshot['collisions'])),'Collision recomputation differs');
    $check(medicineSelectionCollisionKey('Alpha Glycerylphosphoryl Choline')===medicineSelectionCollisionKey('Alpha Glycerylphosphorylcholine'),'Whitespace collision missed');
    $check(medicineSelectionCollisionKey('L-Glutathione')===medicineSelectionCollisionKey('L Glutathione'),'Punctuation collision missed');
    $collisionIds=[];foreach($snapshot['collisions'] as $group){foreach($group as $row){$collisionIds[]=$row['id'];}}$check(array_intersect($ids,$collisionIds)===[],'Collision shortlist inclusion');
    $members=array_unique(array_map('strval',array_column($snapshot['items'],'substance_id')));$check(array_intersect($ids,$members)===[],'Linked combination shortlist inclusion');
    foreach(['hydrochloride','sodium','monohydrate','implant','+'] as $marker){$mutated=$snapshot;foreach($mutated['rows'] as &$row){if((string)$row['substance_id']===$ids[0]){$row['canonical_name'].=' '.$marker;break;}}unset($row);try{medicineSelectionAnalyze($mutated);throw new LogicException('Injected hard exclusion accepted');}catch(RuntimeException $e){$check($e->getMessage()==='Curated Group A requires renewed audit.','Wrong exclusion failure');}}
    foreach(['UPDATE kobi_substances SET is_active=0','INSERT INTO kobi_substances VALUES(1)','DELETE FROM kobi_substances','CREATE TABLE x(id INT)','ALTER TABLE x ADD id INT','DROP TABLE x','SELECT 1; DELETE FROM x',"SELECT 1 INTO OUTFILE 'x'",'SELECT SLEEP(1)','SELECT * FROM x FOR UPDATE'] as $sql){try{medicineSelectionQuery((new ReflectionClass(PDO::class))->newInstanceWithoutConstructor(),$sql);throw new LogicException('Forbidden query accepted');}catch(RuntimeException $e){$check($e->getMessage()==='Read-only audit query rejected.','Read-only guard failure');}}
    $check(array_sum(array_column($snapshot['rows'],'has_verification_source'))===0&&array_sum(array_column($snapshot['rows'],'has_reviewer'))===0&&array_sum(array_column($snapshot['rows'],'has_review_date'))===0,'Registry evidence assumptions changed');
    echo json_encode(['checks'=>$checks,'failures'=>0,'shortlist_count'=>15,'primary_exclusions'=>$a['primary_exclusions'],'eligible_pool'=>$a['eligible_pool']],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
if($mode==='analyze'){$snapshot=json_decode(file_get_contents(__DIR__.'/.medicine314-snapshot.json'),true,64,JSON_THROW_ON_ERROR);echo json_encode(medicineSelectionAnalyze($snapshot),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;exit;}
if($mode!=='snapshot'){throw new RuntimeException('Use snapshot or analyze for the SELECT-only registry audit.');}
try{$pdo=(new App\Core\Database())->connection();}catch(Throwable $e){throw new RuntimeException('Read-only database connection unavailable; credentials suppressed.');}
$meta=medicineSelectionQuery($pdo,'SELECT DATABASE() database_name, @@version version, @@port port, @@sql_mode sql_mode, UTC_TIMESTAMP(6) observed_at')[0];
if($meta['database_name']!=='medical1'||(int)$meta['port']!==3306){throw new RuntimeException('Expected existing normal registry instance not found.');}
$tables=medicineSelectionQuery($pdo,'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()');$names=array_column($tables,'TABLE_NAME');
$columns=medicineSelectionQuery($pdo,"SELECT TABLE_NAME,COLUMN_NAME,DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('kobi_substances','kobi_combinations','kobi_combination_items','kobi_substance_forms','kobi_form_components') ORDER BY TABLE_NAME,ORDINAL_POSITION");
$rows=medicineSelectionQuery($pdo,"SELECT substance_id,canonical_name,canonical_name_normalized,naming_standard,identity_type,identity_status,is_active,is_searchable,(verification_source IS NOT NULL AND TRIM(verification_source)<>'') has_verification_source,(reviewed_by IS NOT NULL) has_reviewer,(reviewed_at IS NOT NULL) has_review_date FROM kobi_substances ORDER BY substance_id");
$items=medicineSelectionQuery($pdo,'SELECT combination_id,substance_id,form_id,component_name,component_name_normalized FROM kobi_combination_items ORDER BY combination_item_id');
$forms=medicineSelectionQuery($pdo,'SELECT form_id,substance_id,form_name,form_type,form_status FROM kobi_substance_forms ORDER BY form_id');
$counts=[];foreach(['kobi_combinations','kobi_combination_items','kobi_substance_forms','kobi_form_components','kobi_identity_reviews','kobi_who_inn_evidence','kobi_medicine_chemical_refs','kobi_medicine_identities','medicine_authority_grants','medicine_governance_events','medicine_registry_assessments','medicine_registry_lifecycle_events','medicine_pilot_records'] as $table){$counts[$table]=in_array($table,$names,true)?(int)medicineSelectionQuery($pdo,'SELECT COUNT(*) n FROM `'.$table.'`')[0]['n']:null;}
$collisions=[];foreach($rows as $row){$collisions[medicineSelectionCollisionKey($row['canonical_name'])][]=['id'=>(string)$row['substance_id'],'name'=>$row['canonical_name']];}$collisions=array_values(array_filter($collisions,fn($group)=>count($group)>1));
$distribution=medicineSelectionQuery($pdo,'SELECT BINARY identity_status identity_status,BINARY identity_type identity_type,COUNT(*) n FROM kobi_substances GROUP BY BINARY identity_status,BINARY identity_type');
$duplicates=[];foreach(['canonical_name','canonical_name_normalized'] as $column){$duplicates[$column]=medicineSelectionQuery($pdo,'SELECT COUNT(*) groups_found,COALESCE(SUM(n),0) affected_rows FROM (SELECT COUNT(*) n FROM kobi_substances GROUP BY BINARY '.$column.' HAVING COUNT(*)>1) g')[0];}
$after=medicineSelectionQuery($pdo,'SELECT COUNT(*) total, SUM(is_active=1 AND is_searchable=1) active_searchable, @@sql_mode sql_mode, UTC_TIMESTAMP(6) observed_at FROM kobi_substances')[0];
echo json_encode(['meta'=>$meta,'columns'=>$columns,'counts'=>$counts,'distribution'=>$distribution,'duplicates'=>$duplicates,'rows'=>$rows,'items'=>$items,'forms'=>$forms,'collisions'=>$collisions,'after'=>$after],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
