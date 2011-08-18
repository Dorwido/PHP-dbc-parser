
<?php

class dbcstructs{


	function ReadIt($struct){
		if(method_exists($this,substr($struct,0,-4))){

			return call_user_func(array($this,substr($struct,0,-4)));
		}else{
			die("No definition found for ".$struct."\r\n");
		}
	}	function Faction(){
		$returndata = array (
			$this->GetInt('FactionId','primary'),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetString('FactionName','en'),
			$this->GetUnknown(),
			$this->GetUnknown()
		);
		return $returndata;
	}	function ItemClass(){
		$returndata = array (
			$this->GetUnknown(),
			$this->GetInt('itemClass','primary'),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetString('itemClassName','en'),
		);
		return $returndata;
	}	function ItemSubClass(){
		$returndata = array (
			$this->GetUnknown(),
			$this->GetInt('itemClass','primary'),
			$this->GetInt('itemSubClass','primary'),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetString('shorttext','en'),
			$this->GetString('longtext','en')
		);
		return $returndata;
	}	function SkillLine(){
		$returndata = array (
			$this->GetInt('SkillId','primary'),
			$this->GetUnknown(),
			$this->GetString('SkillName','en'),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown(),
			$this->GetUnknown()
		);
		return $returndata;
	}
}
?>