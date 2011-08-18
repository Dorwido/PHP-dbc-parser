<?php

/**
 * Database handler class
 *
 */
class mysql{
	var $dbhandle;
	var $db;
	var $query_id;
	var $rows="";
	var $fields=""; 
	var $querycount=0;	
	
	
	/**
	 * Connect to the database
	 *
	 * @param string $dbhost
	 * @param string $dbuser
	 * @param string $dbpass
	 * @param string $dbname
	 */
	function dbconnect($dbhost, $dbuser, $dbpass, $dbname) {
		
		$this->db = $dbname;
		$this->dbhandle = @mysql_pconnect($dbhost, $dbuser, $dbpass)
		or die ($this->dberror());
		@mysql_select_db ($this->db, $this->dbhandle)
		or die ($this->dberror());
		@mysql_query('SET NAMES utf8');
	}
	
	function dbdisconnect() {
		if($this->dbhandle)
		{
			if($this->query_id)
			{
				@mysql_free_result($this->query_id);
			}
			$result = @mysql_close($this->dbhandle);
			return $result;
		}
		else
		{
			$this->dberror();
		}
			
	}
	
	function SwitchDB($newdb){
		if(!$this->dbhandle){
			$this->dbconnect(C_DBHOST,C_DBUSER,C_DBPASS,C_DBASE);
		}
		@mysql_select_db ($newdb, $this->dbhandle)
		or die ($this->dberror());
	}
		
	function DoQuery($sqlquery) {
		//echo $sqlquery.";\r\n\r\n";
		//$this->querycount++;
		if(!$this->dbhandle){
			$this->dbconnect(C_DBHOST,C_DBUSER,C_DBPASS,C_DBASE);
		}
		if ($this->query_id) {
			@mysql_free_result($this->query_id);
		}
	
		if(!$query_id=mysql_query($sqlquery, $this->dbhandle)) {
			$this->dberror($sqlquery);
		}
		$this->query_id = $query_id;
		$this->rows=@mysql_num_rows($this->query_id);
		return $this->query_id;
	}
	
	function GetQuery($sqlquery) {
		if (!$this->dbhandle) {
			$this->dberror();
		}
		if ($this->query_id) {
			@mysql_free_result($this->query_id);
		}
		
		if(!$query_id=mysql_query($sqlquery, $this->dbhandle)) {
			$this->dberror();
		}
		$this->query_id = $query_id;
		$this->rows=@mysql_num_rows($this->query_id);
		
		$data=@mysql_fetch_array($this->query_id, MYSQL_ASSOC);
		$this->rows=@mysql_num_rows($this->query_id);
		$this->fields=@mysql_num_fields($this->query_id);
		
		return $data;
	}
	
	/**
	 * Returns a single Database result as an array
	 *
	 * @param integer $type 
	 * 0 = MYSQL_NUM 
	 * 1 = MYSQL_ASSOC 
	 * 2 = MYSQL_BOTH
	 * @return array with db result
	 */
	function GetResult($type)
	{
		if (!$this->dbhandle) {
			$this->dberror();
		}
		if (!$this->query_id) {
			$this->dberror();
		}
		switch($type)
		{
			case 0:
				$type = MYSQL_NUM;
				break;
			case 1:
				$type = MYSQL_ASSOC;
				break;
			case 2:
				$type = MYSQL_BOTH;
				break;
		}
		$data=@mysql_fetch_array($this->query_id, $type);
		$this->rows=@mysql_num_rows($this->query_id);
		$this->fields=@mysql_num_fields($this->query_id);
		return $data;
	}
	
	function GetResultArray($type)
	{
		if (!$this->dbhandle) {
			$this->dberror();
		}
		if (!$this->query_id) {
			$this->dberror();
		}
		switch($type)
		{
			case 0:
				$type = MYSQL_NUM;
				break;
			case 1:
				$type = MYSQL_ASSOC;
				break;
			case 2:
				$type = MYSQL_BOTH;
				break;
		}
		
		$arr = Array();
		while($val = @mysql_fetch_array($this->query_id,$type))
		{
			$arr[] = $val;
		}
		return $arr;

	}
	
/**
	 * Get all rows back formated
	 *
	 * @param int $type 0 = mysql_num,1=mysql_assoc,2=mysql_both
	 * @param field to use to create the index
	 * @return Array with all rows
	 */
	function GetAllRowsFormated($type,$field,$append=0){

		switch($type)
		{
			case 0:
				$type = MYSQL_NUM;
				break;
			case 1:
				$type = MYSQL_ASSOC;
				break;
			case 2:
				$type = MYSQL_BOTH;
				break;
		}

		$arr = Array();
		while($val = mysql_fetch_array($this->query_id,$type))
		{
			if(!is_array($arr[$val[$field]])){
				$arr[$val[$field]] = Array();
			}
			if($append==0){
				$arr[$val[$field]] = $val;
			}elseif($append==1){
				array_push($arr[$val[$field]],$val);
			}
		}
		return $arr;
	}
	
	function GetField(){
		if (!$this->dbhandle) {
			$this->dberror();
		}
		if (!$this->query_id) {
			$this->dberror();
		}
		return mysql_result($this->query_id, 0);
	}
	
	function quote($value)
	{
	    if(!$this->dbhandle){
			$this->dbconnect(C_DBHOST,C_DBUSER,C_DBPASS,C_DBASE);
		}
		// Ueberfluessige Maskierungen entfernen
	    if (get_magic_quotes_gpc()) {
	        $value = stripslashes($value);
	    }
	    // In Anfuehrungszeichen setzen, sofern keine Zahl
	    // oder ein numerischer String vorliegt
	    if (!is_numeric($value)) {
	        $value = "'" . mysql_real_escape_string($value) . "'";
	    }
	    return $value;
	}
	
function quoteall($value)
	{
	    if(!$this->dbhandle){
			$this->dbconnect(C_DBHOST,C_DBUSER,C_DBPASS,C_DBASE);
		}
		// Ueberfluessige Maskierungen entfernen
	    if (get_magic_quotes_gpc()) {
	        $value = stripslashes($value);
	    }
	    // In Anfuehrungszeichen setzen, sofern keine Zahl
	    // oder ein numerischer String vorliegt
	   
	        $value = "'" . mysql_real_escape_string($value) . "'";
	    
	    return $value;
	}

	
	function dberror($sqlquery=0) {
		
	
			echo $sqlquery;
			echo mysql_error();
		
		
		
		die;
	}
	
	function GetLastInsertID() {
		return  mysql_insert_id($this->dbhandle);
	}
	
	function GetRows() {
		return $this->rows;
	}
	
	function Getaffected(){
		return mysql_affected_rows($this->dbhandle);
	}

}

?>