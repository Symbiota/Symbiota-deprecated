<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ExsiccatiManager {

	private $conn;
	private $ometid;

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
		if($rs = $this->conn->query($sql)){
			if($r = $rs->fetch_object()){
				$retArr[$r->ometid]['t'] = $r->title;
				$retArr[$r->ometid]['e'] = $r->editor;
				$retArr[$r->ometid]['r'] = $r->range;
			}
			$rs->close();
		}
		return $retArr;
	}

	public function getExsNumberArr($id){
		$this->ometid = $id;
		$retArr = array();
		$sql = 'SELECT DISTINCT et.title, et.editor, et.range, en.omenid, en.number, CONCAT(o.recordedby, " (", IFNULL(o.recordnumber,"s.n."), ")") as collector '.
			'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
			'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
			'INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'WHERE et.ometid = '.$id;
		if($rs = $this->conn->query($sql)){
			if($r = $rs->fetch_object()){
				if(!array_key_exists('t',$retArr)){
					$title = $r->title;
					if($r->editor) $title .= ', '.$r->editor; 
					if($r->range) $title .= ', '.$r->range; 
					$retArr['t'] = $title;
				}
				if(array_key_exists($r->omenid,$retArr)){
					$retArr[$r->omenid]['c'] = $retArr[$r->omenid]['c'].', '.$r->collector;
				}
				else{
					$retArr[$r->omenid]['n'] = $r->number;
					$retArr[$r->omenid]['c'] = $r->collector;
				}
			}
			$rs->close();
		}
		return $retArr;
	}

	public function getExsOccArr($id){
		$this->ometid = $id;
		$retArr = array();
		$sql = 'SELECT et.title, et.editor, et.range, en.omenid, en.number, o.recordedby, o.recordnumber, o.eventdate '.
			'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
			'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
			'INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'WHERE et.omenid = '.$id;
		if($rs = $this->conn->query($sql)){
			if($r = $rs->fetch_object()){
				if(!array_key_exists('t',$retArr)){
					$title = $r->title;
					if($r->editor) $title .= ', '.$r->editor; 
					if($r->range) $title .= ', '.$r->range; 
					$retArr['t'] = $title;
				}
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
 		$newStr = $this->conn->real_escape_string($newStr);
 		return $newStr;
 	}
}
?> 