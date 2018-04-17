<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceEditorServices {

	private $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
	}

	public function __destruct(){
		if($this->conn !== false) $this->conn->close();
	}

	//AJAX query calls
	public function getSpeciesSuggest($term){
		$retArr = Array();
		$term = preg_replace('/[^a-zA-Z\- ]+/', '', $term);
		$term = preg_replace('/\s{1}x{1}\s{0,1}$/i', ' _ ', $term);
		$term = preg_replace('/\s{1}x{1}\s{1}/i', ' _ ', $term);
		$sql = 'SELECT DISTINCT tid, sciname FROM taxa WHERE sciname LIKE "'.$term.'%" ';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()){
			$retArr[] = array('id' => $r->tid, 'value' => $r->sciname);
		}
		$rs->free();
		return $retArr;
	}

	//Misc functions
	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>