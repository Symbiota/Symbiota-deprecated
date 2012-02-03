<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ExsiccatiManager {

	private $conn;
	private $ometid;
	private $title;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTitleArr(){
		$retArr = array();
		$sql = 'SELECT et.ometid, et.title, et.abbreviation, et.editor, et.range '.
			'FROM omexsiccatititles et '.
			'ORDER BY et.title';
		if($rs = $this->query($sql)){
			if($r = $rs->fetch_object()){
				$retArr[$r->ometid]['t'] = $r->title;
				$retArr[$r->ometid]['e'] = $r->editor;
				$retArr[$r->ometid]['r'] = $r->range;
			}
			$rs->close();
		}
		return $retArr;
	}

	public function getExsiccateArr($id){
		$this->ometid = $id;
		$retArr = array();
		$sql = 'SELECT et.title, en.omenid, en.number, o.recordedby, o.recordnumber, o.eventdate '.
			'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
			'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
			'INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'WHERE et.ometid = '.$id;
		if($rs = $this->query($sql)){
			if($r = $rs->fetch_object()){
				if(!$this->title) $this->title = $r->title;
				$retArr[$r->omenid]['n'] = $r->number;
				$retArr[$r->omenid]['rb'] = $r->recordedby;
				$retArr[$r->omenid]['rn'] = $r->recordnumber;
				$retArr[$r->omenid]['d'] = $r->eventdate;
			}
			$rs->close();
		}
		return $retArr;
	}

	private function cleanStr($str){
 		$newStr = trim($str);
 		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
 		$newStr = str_replace('"',"'",$newStr);
 		$newStr = $this->clCon->real_escape_string($newStr);
 		return $newStr;
 	}
}
?> 