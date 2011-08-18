<?php
require_once('./dbc.live.struct.proto.php');
		
class dbc extends dbcstructs{
	
	private $filelist = array ();
	
	
	
	private $currentfilestream = array();
	private $currentfile;
	private $currentheader = array();
	private $currentstrings = array();	
	private $currentmaxlength = array();
	private $currentstructure = array();
	private $currentdata = array();
	private $currentsqlbase = '';
	private $currentunknowncounter = 1;
	private $currentflagforchange = array();
	private $langs = array("en");
	private $baselang = "en";
	private $stringdata = array();
	private $sql_stmt;
	private $sql_link;
	private $types;
	private $rawdata;
	private $rawappend = '';
	//private $currentdata = '';
	
	
	function __construct($rawdata=false){
		global $db;
		$this->rawdata = $rawdata;
		
		if($rawdata){
			
			$this->rawappend = '_raw';
		}
		$this->CreateFileList();
		
		$db->SwitchDB(C_DBASE.$this->rawappend);
		
	}
	
	function CreateFileList(){
		
		$d = dir(C_ROOTDIR.'dbc/'.$this->baselang.'/');
		while (false !== ($entry = $d->read())) {
			if(substr($entry,-4) == '.dbc'){
				$this->filelist[$entry] = array("table"=>substr($entry,0,-4));
				
			}
		}
		
		$d->close();

	}
	
	function ResetData(){
		$this->currentfilestream = array();
		$this->currentfile = '';
		$this->currentheader = array();
		$this->currentstrings = array();
		$this->currentmaxlength = array();
		$this->currentstructure = array();
		$this->currentflagforchange = array();
		$this->currentdata = array();
		$this->currentflagforchang = array();
		$this->currentsqlbase='';
		$this->currentunknowncounter =1;
		$this->stringdata = array();
		$this->sql_stmt = '';
		$this->type = array();
		
	}
	// make a base structures with all fields unknown
	//primary used to add new structures
	function MakeTempStructs(){
		
		foreach ($this->filelist as $key => $val){
			$this->currentfile = $key;
			$this->OpenFile($this->baselang);
			//$this->MakeTempStructs($this->currentheader[$this->baselang]['fields']);
			$towrite="\tfunction ".substr($key,0,-4)."(){\r\n";
			$towrite.="\t\t".'$returndata = array ('."\r\n";
			for($i=0;$i<$this->currentheader[$this->baselang]['fields'];$i++){	
				if($i==($this->currentheader[$this->baselang]['fields']-1)){
					$towrite .= "\t\t\t".'$this->GetUnknown()'."\r\n";
				}else{
					$towrite .= "\t\t\t".'$this->GetUnknown(),'."\r\n";
				}
			}
			$towrite .= "\t\t);";
			$towrite .="\r\n\t\t".'return $returndata;';
			$towrite .="\r\n\t}";
			
			file_put_contents(C_ROOTDIR."raw_struct/".$this->currentfile,$towrite);
			$this->ResetData();
	
		}
	}
	
	function MakeFinalStructs(){
		$towrite = '
<?php

class dbcstructs{


	function ReadIt($struct){
		if(method_exists($this,substr($struct,0,-4))){

			return call_user_func(array($this,substr($struct,0,-4)));
		}else{
			die("No definition found for ".$struct."\r\n");
		}
	}';
		$d = dir(C_ROOTDIR.'final_struct/');
		while (false !== ($entry = $d->read())) {
			if(substr($entry,-4) == '.dbc'){
				$towrite=$towrite.file_get_contents(C_ROOTDIR.'final_struct/'.$entry);
				
			}
		}
		
		$d->close();
		$towrite=$towrite."\n}\n?>";
		
		file_put_contents(C_ROOTDIR.'dbc.live.struct.proto.php',$towrite);
	}
	
	
	function ProcessFiles(){
		
		//todo
		$this->sql_link = mysqli_connect(C_DBHOST, C_DBUSER, C_DBPASS, C_DBASE);
	
		if(!mysqli_set_charset($this->sql_link,"utf8")) {
   			die('cant set utf8');
		}
			 
		foreach ($this->filelist as $key => $val){
			$this->currentfile = $key;
			$this->OpenFile($this->baselang);
			//todo
			/*if($this->currentheader[$this->baselang]['blocksize']>1){
				$this->OpenFile("de");
				$this->ReadAllStrings();
				//echo $this->GetString(137068,"en");die;
			}*/
			//echo $this->currentheader[$this->baselang]['records'];die;
			//$this->MakeTempStructs($this->currentheader[$this->baselang]['fields']);
			$this->ReadAllStrings();
			$currentline='';
			
			for($i=0;$i<$this->currentheader[$this->baselang]['records'];$i++){		
				fseek($this->currentfilestream[$this->baselang],$i*$this->currentheader[$this->baselang]['recordsize']+20);
				//$erg = $this->ReadIt($this->currentfile);
				$currentline = $this->ReadIt($this->currentfile);
				if($i==0){
					
					$this->CreateDBStructure();
					$this->CreateBaseSQL2();
					
				}
				//$this->currentdata = $currentdata;
				
				if(isset($this->currentflagforchange['todo']) && count($this->currentflagforchange['todo'])>0){
					   $this->AlternateTable();
				}
				//print_r($currentline);
				//die;
				 $parameters_references = array();
				foreach($currentline as $key => $parameter) {
					$parameters_references[] = &$currentline[$key]; 
				}
				
				call_user_func_array('mysqli_stmt_bind_param', array_merge (array($this->sql_stmt, $this->types), $parameters_references));
				
				
				mysqli_stmt_execute($this->sql_stmt) or die(mysqli_error($this->sql_link));
				$this->currentunknowncounter=1;
				//$this->InsertDBData2($currentread);
				//$this->currentdata = array();
			}
			
			//$this->CreateDBStructure();
			//$this->InsertDBData();
			// file_put_contents('./arrdump',var_export($this->currentdata,true));
			$this->ResetData();
			
			//break;
		}
		
		
	}
	
	function OpenFile($lang){
		if(!$this->currentfilestream[$lang]=fopen(C_ROOTDIR.'/dbc/'.$lang.'/'.$this->currentfile,"rb")){
			die("Couldnt open file: ".$key);
		}
			
		$this->ReadHeader($lang);//die;
	}
	
	function ReadHeader($lang){
		fseek($this->currentfilestream[$lang],0);

		$this->currentheader[$lang]['fileformat'] = fread($this->currentfilestream[$lang], 4);
		$this->currentheader[$lang]['records'] = $this->hex2dec(fread($this->currentfilestream[$lang], 4));
		$this->currentheader[$lang]['fields'] = $this->hex2dec(fread($this->currentfilestream[$lang], 4));
		$this->currentheader[$lang]['recordsize'] = $this->hex2dec(fread($this->currentfilestream[$lang], 4));
		$this->currentheader[$lang]['blocksize'] = $this->hex2dec(fread($this->currentfilestream[$lang], 4));
		if($this->currentheader[$lang]['fileformat']!="WDBC"){
			die("Fileformat not WDBC in file: ".$this->currentfile);
		}
		//somehow the fields are only 23 in the file header which produces problems
		if($this->currentfile=='Achievement_Criteria.dbc'){
			$this->currentheader[$lang]['fields'] = 24;
		}
		
	
		if($this->currentheader[$lang]['recordsize'] % $this->currentheader[$lang]['fields'] <> 0){
			die("Fieldlength is not 4, file: ".$this->currentfile);
		}
		
		
	}
								 	
	function CreateBaseStructs(){
		
	}
	
	function AddStructure($name,$key,$type){
		$this->currentstructure[$name]['key'] = $key;
		$this->currentstructure[$name]['type'] = $type;
		
	}
	
	function GetString($name,$lang,$key=''){
		if($this->rawdata && $key!='primary'){
			return $this->GetUnknown();
		}
		
		/*while (!feof($fp)) {
    	$char = fread($fp,1);
    	//$i2++;
    	if(bin2hex($char) == '00'){
    		break;
    	}else{
    		$tmpstring.=$char;
    	}
    }*/
		//todo while the english stream is at right position the german stream is not an must be set right
		//$pos = bin2dec(fread($this->currentfilestream[$lang]))
    	if($lang==$this->baselang){
			$pos = $this->hex2dec(fread($this->currentfilestream[$this->baselang],4));
    	}else{
    		//get the positon in the mainstream and seek to that in the specific lang stream 
    		//read to devnull to move the pointer in the mainstream
    		$gotopos = ftell ($this->currentfilestream[$this->baselang]);
    		fseek($this->currentfilestream[$lang],$gotopos);
    		$pos = $this->hex2dec(fread($this->currentfilestream[$lang],4));
    		$devnull = fread($this->currentfilestream[$this->baselang],4);
    	}
	/*	if(!isset($this->currentmaxlength[$name])){
    		$this->currentmaxlength[$name] = 0;
    	}*/
		$composetext = '';
    	while(bin2hex($this->stringdata[$lang][$pos])!='00') {
    		$composetext .= $this->stringdata[$lang][$pos];
    		$pos++;
    	}
    	if(strlen($composetext)>250){
    		//to messure easy if something is to change we use a array which was done and which not already
    		if(!isset($this->currentflagforchange['done'][$name])){
    			$this->currentflagforchange['todo'][$name] = 0;
    		}
    	}
    	if(!isset($this->currentstructure[$name])){
    		$this->AddStructure($name,$key,'string');
    	}
    	$this->currentunknowncounter++;
    	return $composetext;

    	
	}
	
	//alternate a varchar to text if the size is too big, due inserting 1 by 1 no more possible to figre the longest length
	function AlternateTable(){
		//ALTER TABLE `itemsubclassmask` CHANGE `name_de` `name_de` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
		//echo 'ALTER TABLE `'.$this->filelist[$this->currentfile]['table'].'` CHANGE `'.$fieldname.'` `'.$fieldname.'` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		foreach ($this->currentflagforchange['todo'] as $fieldname => $val){
	
			mysqli_real_query($this->sql_link,'ALTER TABLE `'.$this->filelist[$this->currentfile]['table'].'` CHANGE `'.$fieldname.'` `'.$fieldname.'` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL') or die(mysqli_error($this->sql_link));
			$this->currentflagforchange['done'][$fieldname] = 1;
		}
		$this->currentflagforchange['todo'] = array();
	
	}
	
	function GetInt($name,$key=''){
		//integer
		if($this->rawdata && $key!='primary'){
			return $this->GetUnknown();
		}
		
		$val = fread($this->currentfilestream[$this->baselang],4);
		if(!isset($this->currentstructure[$name])){
    		$this->AddStructure($name,$key,'integer');
    	}
		$this->currentunknowncounter++;
		return $this->hex2dec($val);
	}
	
	
	function GetUnknown($key=''){
		//binary 4
		/*if($key!=''){
			die('Error in file: '.$this->currentfile);
		}
		$val = fread($this->currentfilestream[$this->baselang],4);
		if(!isset($this->currentstructure['unknown'.$this->currentunknowncounter])){
    		$this->AddStructure('unknown'.$this->currentunknowncounter,$key,'binary');
    	}
    	$this->currentunknowncounter++;
		return $val;*/
		if($key!=''){
			die('Error in file: '.$this->currentfile);
		}
		$val = fread($this->currentfilestream[$this->baselang],4);
		if(!isset($this->currentstructure['unknown'.$this->currentunknowncounter])){
    		$this->AddStructure('unknown'.$this->currentunknowncounter,$key,'integer');
    	}
    	$this->currentunknowncounter++;
		return $this->hex2dec($val);
	}
	
	function GetUInt($name,$key=''){
		//integer
		if($this->rawdata && $key!='primary'){
			return $this->GetUnknown();
		}
		$val = fread($this->currentfilestream[$this->baselang],4);
		if(!isset($this->currentstructure[$name])){
    		$this->AddStructure($name,$key,'uinteger');
    	}
    	$this->currentunknowncounter++;
    	return $this->hex2dec($val);
	}
	
	function GetFloat($name,$key=''){
		if($this->rawdata && $key!='primary'){
			return $this->GetUnknown();
		}
		$val = fread($this->currentfilestream[$this->baselang],4);
		if(!isset($this->currentstructure[$name])){
    		$this->AddStructure($name,$key,'float');
    	}
    	$this->currentunknowncounter++;
    	return $this->bin2float($val);
	}
	
	function CreateBaseSQL2(){
		$currentsqlbase = 'INSERT INTO `'.$this->filelist[$this->currentfile]['table'].'` (';
		$valstring = '(';
		$types = '';
		foreach($this->currentstructure as $field => $opts){
			$currentsqlbase.='`'.$field.'`,';
			$valstring .= '?,';
			$types.='s';
		}
		$valstring = substr($valstring,0,-1).')';
		$currentsqlbase=substr($currentsqlbase,0,-1).') VALUES '.$valstring;
		$this->currentsqlbase = $currentsqlbase;
		$this->types = $types;
		$this->sql_stmt = mysqli_prepare ($this->sql_link, $this->currentsqlbase); 
		
		
		//var_dump( array_merge (array($this->sql_stmt, $this->types), $this->currentdata));die;
		//call_user_func_array('mysqli_stmt_bind_param', array_merge (array($this->sql_stmt, $this->types), $this->currentdata));
	}
	
 
	
	function CreateBaseSQL(){
		$this->currentsqlbase = 'INSERT INTO `'.$this->filelist[$this->currentfile]['table'].'` (';
		foreach($this->currentstructure as $field => $opts){
			$this->currentsqlbase.='`'.$field.'`,';
		}
		$this->currentsqlbase=substr($this->currentsqlbase,0,-1).') VALUES ';
	}
	
	function InsertDBData($insertdata){
		global $db;
			$sql='(';
			
			foreach($insertdata as $val){
				$sql.=$db->quote($val).',';
			}
			$sql=substr($sql,0,-1).')';
			$db->DoQuery($this->currentsqlbase.$sql);

		
	}
	
	function CreateDBStructure(){
		global $db;
		
		$primarylist=array();
		$sql = 'CREATE TABLE `'.$this->filelist[$this->currentfile]['table'].'` (';
		foreach($this->currentstructure as $field => $opts){
			// `basetime` int(11) NOT NULL,
		 	$sql.='`'.$field.'`';
		 	switch ($opts['type']){
		 		case 'integer':
		 			$sql.=' int(11)';
		 			break;
		 		case 'binary':
		 			$sql.=' BINARY( 4 )';
		 			break;
		 		case 'string':
		 			//if($this->currentmaxlength[$field]<220){
		 				$sql.= ' VARCHAR( 255 )';
		 				//$sql.= ' VARCHAR( 255 )';
		 			//}else{
		 		//		$sql.=' TEXT';
		 			//}
		 			
		 			break;
		 		case 'float':
		 				$sql.=' FLOAT';
		 			break;
		 	}
		 	$sql.=' NOT NULL,';	 	
		 	if($opts['key']=='primary'){
		 		array_push($primarylist,$field);
		 	}elseif($opts['key']!=''){
		 		die('wrong key set in file: '.$this->currentfile.' key:'.$opts['key']);
		 	}
		}

		if(count($primarylist)>0){
			//
			$sql.='PRIMARY KEY (';
			foreach($primarylist as $primkey){
				$sql.='`'.$primkey.'`,';
			}
			$sql = substr($sql,0,-1);
			$sql.='),';	
		}
		$sql = substr($sql,0,-1);
		$sql.=') ENGINE=MyISAM DEFAULT CHARSET=utf8';
		
		$db->SwitchDB(C_DBASE.$this->rawappend);
		$db->DoQuery($sql);
	}
	
	function ReadAllStrings(){

		foreach($this->langs as $lang){
		
			fseek($this->currentfilestream[$lang],($this->currentheader[$lang]['records']*$this->currentheader[$lang]['recordsize'])+20);
			$this->stringdata[$lang] = fread($this->currentfilestream[$lang],filesize(C_ROOTDIR.'dbc/'.$lang.'/'.$this->currentfile));
			
		}
		
	
	}
	
	function hex2dec($hex){
		$hex = bin2hex($hex);
		$count = strlen($hex);
		if($count % 2 <> 0){
			$hex = $hex.'0';
		}
		$res = '';
		for($i=0;$i<strlen($hex);$i=$i+2){
			$res = substr($hex,$i,2).$res;
		}
		return (int) hexdec($res);
		
	}
	
	function bin2float ($bin) {
	   $float = (float) 0;
	
	   // Read Exponent and Sign (+/-)
	   $exponent = ord ($bin{3});
	   if ($sign = $exponent & 128) $exponent -= 128;
	   $exponent <<= 1;
	
	   // Read the remaining bit for Exponent and loop through Mantissa, calculating the Fraction
	   $fraction = (float) 1;
	   $div = 1;
	   for ($x=2; $x>=0; $x--) {
	       $byte = ord ($bin{$x});
	       for ($y=7; $y>=0; $y--) {
	         if ($x==2 && $y==7) {
	           if ($byte & (1 << $y)) $exponent += 1;
	         } else {
	           $div *= 0.5;
	           if ($byte & (1 << $y)) $fraction += $div;
	         }
	       }
	   }
	
	   // 0 value check
	   if (!$exponent && $fraction == 1) return 0;
	
	   // Final calc, returning the converted float
	   $exponent -= 127;
	
	   $float = pow (2, $exponent) * $fraction;
	   if ($sign) $float = -($float);
	
	   return $float;
	}
	
	
}

?>