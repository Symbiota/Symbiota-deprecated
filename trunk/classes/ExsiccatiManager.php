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
			while($r = $rs->fetch_object()){
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
		$sql = 'SELECT DISTINCT et.title, et.abbreviation, et.editor, et.range, et.source, et.notes, '.
			'en.omenid, en.number, CONCAT(o.recordedby, " (", IFNULL(o.recordnumber,"s.n."), ")") as collector '.
			'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
			'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
			'INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'WHERE et.ometid = '.$id.' ORDER BY en.number+1';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(!array_key_exists('ex',$retArr)){
					$retArr['ex']['t'] = $r->title;
					$retArr['ex']['a'] = $r->abbreviation;
					$retArr['ex']['e'] = $r->editor;
					$retArr['ex']['r'] = $r->range;
					$retArr['ex']['s'] = $r->source;
					$retArr['ex']['n'] = $r->notes;
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
		$sql = 'SELECT et.title, et.editor, et.range, en.omenid, en.number, '.
			'o.occid, o.recordedby, o.recordnumber, o.eventdate, i.thumbnailurl, i.url '.
			'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
			'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
			'INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'LEFT JOIN images i ON o.occid = i.occid '.
			'WHERE en.omenid = '.$id.' ORDER BY o.recordedby, o.recordnumber';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(!array_key_exists('t',$retArr)){
					$title = $r->title;
					if($r->editor) $title .= ', '.$r->editor; 
					$title .= ' #'.$r->number;
					$retArr['t'] = $title;
				}
				$retArr[$r->occid]['rb'] = $r->recordedby;
				$retArr[$r->occid]['rn'] = $r->recordnumber;
				$retArr[$r->occid]['d'] = $r->eventdate;
				if($r->thumbnailurl) $retArr[$r->occid]['tn'] = $r->thumbnailurl;
				if($r->url) $retArr[$r->occid]['url'] = $r->url;
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