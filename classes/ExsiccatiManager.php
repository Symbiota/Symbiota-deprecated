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

	public function getTitleArr($mode = 0){
		$retArr = array();
		if($mode){
			//Display full list
			$sql = 'SELECT et.ometid, et.title, et.abbreviation, et.editor, et.range '.
				'FROM omexsiccatititles et '.
				'ORDER BY et.title';
		}
		else{
			//Display only exsiccati that have linked specimens
			$sql = 'SELECT DISTINCT et.ometid, et.title, et.abbreviation, et.editor, et.range '.
				'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
				'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
				'INNER JOIN omoccurrences o ON ol.occid = o.occid '.
				'ORDER BY et.title';
		}
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
			'en.omenid, en.exsnumber, en.notes, '.
			'CONCAT_WS(" ",o.recordedby, CONCAT("(",IFNULL(o.recordnumber,"s.n."),")"),o.eventDate) as collector '.
			'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
			'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
			'INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'WHERE et.ometid = '.$id.' ORDER BY en.exsnumber+1';
		//echo $sql;
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
					$retArr[$r->omenid]['collector'] = $retArr[$r->omenid]['collector'].', '.$r->collector;
				}
				else{
					$retArr[$r->omenid]['number'] = $r->exsnumber;
					$retArr[$r->omenid]['notes'] = $r->notes;
					$retArr[$r->omenid]['collector'] = $r->collector;
				}
			}
			$rs->close();
		}
		return $retArr;
	}

	public function getExsOccArr($id){
		$this->ometid = $id;
		$retArr = array();
		$sql = 'SELECT et.title, et.editor, et.range, en.omenid, en.exsnumber, '.
			'ol.ranking, ol.notes, '.
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
					$title .= ' #'.$r->exsnumber;
					$retArr['t'] = $title;
				}
				if(!array_key_exists($r->occid,$retArr)){
					$retArr[$r->occid]['omenid'] = $r->omenid;
					$retArr[$r->occid]['ranking'] = $r->ranking;
					$retArr[$r->occid]['notes'] = $r->notes;
					$retArr[$r->occid]['recordedby'] = $r->recordedby;
					$retArr[$r->occid]['recordnumber'] = $r->recordnumber;
					$retArr[$r->occid]['eventdate'] = $r->eventdate;
					if($r->url){ 
						$retArr[$r->occid]['url'] = $r->url;
						$retArr[$r->occid]['tnurl'] = ($r->thumbnailurl?$r->thumbnailurl:$r->url);
					}
				}
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