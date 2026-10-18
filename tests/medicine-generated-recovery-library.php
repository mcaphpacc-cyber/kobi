<?php
declare(strict_types=1);
// Pure, restricted mysqldump transformation. Never opens a database or writes a backup.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
function g319Assert(bool $ok,string $message):void {if(!$ok){throw new RuntimeException($message);}}
function g319Parts(string $text):array {
    $out=[];$start=0;$depth=0;$quote=null;$length=strlen($text);
    for($i=0;$i<$length;$i++){$c=$text[$i];
        if($quote!==null){if($c==='\\'&&$quote!== '`'){g319Assert($i+1<$length,'Incomplete SQL escape.');$i++;continue;}if($c===$quote){if(($text[$i+1]??null)===$quote){$i++;}else{$quote=null;}}continue;}
        if($c==="'"||$c==='"'||$c==='`'){$quote=$c;continue;}
        if($c==='('){$depth++;}elseif($c===')'){$depth--;g319Assert($depth>=0,'Unbalanced SQL tuple.');}elseif($c===','&&$depth===0){$out[]=substr($text,$start,$i-$start);$start=$i+1;}
    }g319Assert($quote===null&&$depth===0,'Unterminated SQL token.');$out[]=substr($text,$start);return $out;
}
function g319Rows(string $values):array {
    $rows=g319Parts($values);$out=[];foreach($rows as $row){g319Assert(preg_match('/\A(\s*)\((.*)\)(\s*)\z/sD',$row,$m)===1,'Unsupported INSERT VALUES tuple.');$tokens=g319Parts($m[2]);foreach($tokens as $token){$t=trim($token);$literal=preg_match('/\A(?:NULL|DEFAULT|[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?|0x[0-9a-f]+)\z/iD',$t)===1;$string=preg_match('/\A(?:_[a-z0-9]+)?\x27(?:[^\x27\\\\]|\\\\.|\x27\x27)*\x27\z/isD',$t)===1;g319Assert($literal||$string,'Unsupported INSERT expression; no transformation.');}$out[]=['leading'=>$m[1],'trailing'=>$m[3],'tokens'=>$tokens];}return $out;
}
function g319Schema(array $columns,string $definition):array {
    $names=[];$generated=[];preg_match_all('/^\s*`([^`]+)` ([^\r\n]+)\r?$/m',$definition,$lines,PREG_SET_ORDER);g319Assert(count($lines)===count($columns),'Schema column inventory differs.');
    foreach($columns as $i=>$c){$name=$c['COLUMN_NAME'];g319Assert(preg_match('/\A[a-zA-Z0-9_]+\z/D',$name)===1&&$lines[$i][1]===$name&&!in_array($name,$names,true),'Schema column order/name differs.');$names[]=$name;
        // Quoted SQL values cannot impersonate GENERATED attributes.
        $attributes=preg_replace('/\x27(?:[^\x27\\\\]|\\\\.|\x27\x27)*\x27/s',"''",$lines[$i][2]);$ddlGenerated=preg_match('/\bGENERATED ALWAYS AS\b.*\b(STORED|VIRTUAL)\b/i',$attributes,$m)===1;$extra=$c['EXTRA'];$metaGenerated=in_array($extra,['STORED GENERATED','VIRTUAL GENERATED'],true);g319Assert($ddlGenerated===$metaGenerated,'Forged generated-column classification.');
        if($metaGenerated){g319Assert($c['GENERATION_EXPRESSION']!==''&&strtoupper($m[1]).' GENERATED'===$extra,'Generated expression/storage metadata differs.');$generated[]=$name;}else{g319Assert($c['GENERATION_EXPRESSION']===''||$c['GENERATION_EXPRESSION']===null,'Ordinary column has generated expression.');}
    }return ['names'=>$names,'generated'=>$generated];
}
function g319Transform(string $source,array $tables):array {
    $maps=[];foreach($tables as $name=>$t){g319Assert(preg_match('/\A[a-zA-Z0-9_]+\z/D',$name)===1,'Unsafe table name.');$maps[$name]=g319Schema($t['columns'],$t['definition']);}
    $buffer='';$delimiter=';';$output='';$sourceOffset=0;$statement=0;$edits=[];$definitionsSeen=[];
    foreach(preg_split('/(?<=\n)/',$source) as $line){
        if(preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i',$line,$m)){g319Assert(trim(preg_replace('/^\s*--[^\r\n]*$/m','',$buffer))==='','Unexpected buffered SQL at delimiter.');$output.=$buffer.$line;$sourceOffset+=strlen($buffer.$line);$buffer='';$delimiter=$m[1];continue;}
        $buffer.=$line;$trim=rtrim($buffer);if(!str_ends_with($trim,$delimiter)){continue;}$statement++;$new=$buffer;
        foreach($maps as $name=>$map){if(preg_match('/^CREATE TABLE `'.preg_quote($name,'/').'` .*\z/ms',$trim,$m)){g319Assert($delimiter===';'&&str_replace("\r\n","\n",substr($m[0],0,-1))===str_replace("\r\n","\n",$tables[$name]['definition']),'Backup CREATE differs from authoritative metadata.');$definitionsSeen[$name]=true;}}
        if(preg_match('/^INSERT INTO `([^`]+)`/m',$buffer,$target)&&isset($maps[$target[1]])&&$maps[$target[1]]['generated']!==[]){
            $name=$target[1];$map=$maps[$name];g319Assert(isset($definitionsSeen[$name])&&$delimiter===';','Generated INSERT precedes verified CREATE.');
            g319Assert(preg_match('/(^INSERT INTO `'.preg_quote($name,'/').'`)(\s*)(\([^\r\n]*\))?\s+VALUES (.*);([\r\n]*)\z/ms',$buffer,$m,PREG_OFFSET_CAPTURE)===1,'Unsupported generated-table INSERT form.');
            $columnText=$m[3][0];$columnTokens=$columnText!==''?g319Parts(substr($columnText,1,-1)):array_map(fn($n)=>'`'.$n.'`',$map['names']);$names=[];
            foreach($columnTokens as $token){g319Assert(preg_match('/\A\s*`([a-zA-Z0-9_]+)`\s*\z/D',$token,$match)===1&&in_array($match[1],$map['names'],true)&&!in_array($match[1],$names,true),'Unknown/duplicate INSERT column.');$names[]=$match[1];}
            $keep=[];$removed=[];foreach($names as $i=>$n){if(in_array($n,$map['generated'],true)){$removed[]=$n;}else{$keep[]=$i;}}g319Assert($keep!==[],'No ordinary values remain.');
            if($removed!==[]||$columnText===''){$rows=g319Rows($m[4][0]);$rewritten=[];$ordinaryHashes=[];$removedHashes=[];
                foreach($rows as $row){g319Assert(count($row['tokens'])===count($names),'INSERT tuple/column count differs.');$ordinary=array_map(fn($i)=>$row['tokens'][$i],$keep);$ordinaryHashes[]=hash('sha256',serialize($ordinary));$removedHashes[]=hash('sha256',serialize(array_values(array_diff_key($row['tokens'],array_flip($keep)))));$rewritten[]=$row['leading'].'('.implode(',',$ordinary).')'.$row['trailing'];}
                $columns=array_map(fn($i)=>$columnTokens[$i],$keep);$replacement=$m[1][0].' ('.implode(',',$columns).') VALUES '.implode(',',$rewritten).';'.$m[5][0];$start=$m[1][1];$new=substr($buffer,0,$start).$replacement;
                $edits[]=['statement'=>$statement,'source_offset'=>$sourceOffset+$start,'source_bytes'=>strlen(substr($buffer,$start)),'derived_offset'=>strlen($output)+$start,'derived_bytes'=>strlen($replacement),'table'=>$name,'generated_columns_omitted'=>$removed,'rows'=>count($rows),'source_sql_sha256'=>hash('sha256',substr($buffer,$start)),'derived_sql_sha256'=>hash('sha256',$replacement),'ordinary_token_hashes'=>$ordinaryHashes,'omitted_token_hashes'=>$removedHashes];
            }
        }
        $output.=$new;$sourceOffset+=strlen($buffer);$buffer='';
    }$output.=$buffer;foreach($maps as $name=>$map){g319Assert(isset($definitionsSeen[$name]),'Authoritative CREATE missing from backup.');}
    // Verify the untouched byte spans and reconstruct exactly the source from edits.
    $a=0;$b=0;foreach($edits as $edit){g319Assert(substr($source,$a,$edit['source_offset']-$a)===substr($output,$b,$edit['derived_offset']-$b),'Untouched bytes changed.');$a=$edit['source_offset']+$edit['source_bytes'];$b=$edit['derived_offset']+$edit['derived_bytes'];}g319Assert(substr($source,$a)===substr($output,$b),'Trailing untouched bytes changed.');
    return ['sql'=>$output,'manifest'=>['label'=>'DERIVED — NEVER REPLACE AUTHORITATIVE BACKUP','source_sha256'=>hash('sha256',$source),'derived_sha256'=>hash('sha256',$output),'source_bytes'=>strlen($source),'derived_bytes'=>strlen($output),'statements'=>$statement,'edits'=>$edits,'all_untouched_bytes_identical'=>true]];
}
