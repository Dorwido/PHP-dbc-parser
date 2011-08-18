<?php

require('./config.php');

define('C_ROOTDIR',$root_dir);
define('C_DBHOST',$db_host);
define('C_DBUSER',$db_user);
define('C_DBPASS',$db_pass);
define('C_DBASE',$db_database);



$db = new mysql();




$menudata = array (	   1 => "Generate raw Structures",
					   2 => "Generate final Structures",
					   3 => "Parse DBC data",
					   //4 => "Parse raw DBC data",
					   7 => "Exit",
					   );


					   
ShowMenu();

function readline($prompt="") {
    echo $prompt;
    $o = "";
    $c = "";
    while ($c!="\r"&&$c!="\n") {
        $o.= $c;
        $c = fread(STDIN, 1);
    }
    fgetc(STDIN);
    return $o;
}

function ShowMenu(){
	global $menudata,$extractdata,$db;
	
	$show = "\r\n";
	$show.= "---------------------------------------------------------------\r\n";
	foreach($menudata as $key => $val){
		$show .=$key.")"." ".$val."\r\n";
	}
	$cmd = readline($show);
	if(isset($menudata[$cmd])){
		switch($menudata[$cmd]){
			case "Parse DBC data":
				$dbcparser = new dbc();
				$dbcparser->ProcessFiles();
				break;
			case "Generate raw Structures":
				$dbcparser = new dbc();
				$dbcparser->MakeTempStructs();
				break;
			case "Parse raw DBC data":
				$dbcparser = new dbc(true);
				$dbcparser->ProcessFiles();
				break;
			case "Generate final Structures":
				$dbcparser = new dbc();
				$dbcparser->MakeFinalStructs();
				die('Please restart so the new final Structures are loaded');
				break;
			case "Exit":
				die;
				break;
	
		}
	}else{
		echo "Command unknown\r\n";
		
	}
	ShowMenu();
}


function __autoload($class_name) {
	if(is_file(C_ROOTDIR.'/'.$class_name . '.class.php')){
		require_once C_ROOTDIR.'/'.$class_name . '.class.php';
	}
}
?>