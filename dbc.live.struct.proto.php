
<?php

class dbcstructs{


	function ReadIt($struct){
		if(method_exists($this,substr($struct,0,-4))){

			return call_user_func(array($this,substr($struct,0,-4)));
		}else{
			die("No definition found for ".$struct."\r\n");
		}
	}	
}
?>