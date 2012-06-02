<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpecProcessorNlp{

	protected $conn;
	private $collId;
	private $dcArr = array();
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function setCollId($collId){
		if(is_numeric($collId)){
			$this->collId = $collId;
		}
	}
	
	public function getProfileArr($spNlpId=0){
		$retArr = array();
		$sql = 'SELECT spnlpid, title, sqlfrag, patternmatch, notes '.
			'FROM specprocnlp ';
		if($spNlpId) $sql .= 'WHERE spnlpid = '.$spNlpId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->spnlpid]['title'] = $r->title;
			$retArr[$r->spnlpid]['sqlfrag'] = $r->sqlfrag;
			$retArr[$r->spnlpid]['patternmatch'] = $r->patternmatch;
			$retArr[$r->spnlpid]['notes'] = $r->notes;
		}
		$rs->close();
		return $retArr;
	}
	
	public function getProfileFragments($spNlpId){
		$retArr = array();
		$sql = 'SELECT spnlpfragid, fieldname, patternmatch, notes, sortseq '.
			'FROM specprocnlpfrag '.
			'WHERE spnlpid = '.$spNlpId.' '.
			'ORDER BY sortseq';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->spnlpfragid]['fieldname'] = $r->fieldname;
			$retArr[$r->spnlpfragid]['patternmatch'] = $r->patternmatch;
			$retArr[$r->spnlpfragid]['notes'] = $r->notes;
		}
		$rs->close();
		return $retArr;
	}

	//Managing profiles
	public function addProfile($postArr){
		$status = '';
		$sql = 'INSERT INTO specprocnlp(title,sqlfrag,patternmatch,notes,collid) '.
			'VALUES("'.$this->cleanStr($postArr['title']).'","'.$this->cleanStr($postArr['sqlfrag']).'","'.
			$this->cleanStr($postArr['patternmatch']).'","'.$this->cleanStr($postArr['notes']).'",'.$postArr['collid'].')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to add NLP profile; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function editProfile($postArr){
		$status = '';
		$sql = 'UPDATE specprocnlp SET title = "'.$this->cleanStr($postArr['title']).'",sqlfrag = "'.$this->cleanStr($postArr['sqlfrag']).
		'",patternmatch = "'.$this->cleanStr($postArr['patternmatch']).'",notes = "'.$this->cleanStr($postArr['notes']).'" '.
			'WHERE spnlpid = '.$postArr['spnlpid'].'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to edit NLP profile; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function deleteProfile($spnlpid){
		$status = '';
		$sql = 'DELETE FROM specprocnlp WHERE spnlpid = '.$spnlpid.'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to delete NLP profile; ERR: '.$this->conn->error;
		}
		return $status;
	}

	public function addProfileFrag($postArr){
		$status = '';
		$sql = 'INSERT INTO specprocnlpfrag(spnlpid,fieldname,patternmatch,notes,sortseq) '.
			'VALUES("'.$this->cleanStr($postArr['spnlpid']).'","'.$this->cleanStr($postArr['fieldname']).'","'.
			$this->cleanStr($postArr['patternmatch']).'","'.$this->cleanStr($postArr['notes']).'",'.$postArr['sortseq'].')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to add NLP fragment; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function editProfileFrag($postArr){
		$status = '';
		$sql = 'UPDATE specprocnlpfrag SET fieldname = "'.$postArr['fieldname'].'",patternmatch = "'.$this->cleanStr($postArr['patternmatch']).
			'",notes = "'.$this->cleanStr($postArr['notes']).'",sortseq = '.$postArr['sortseq'].' '.
			'WHERE spnlpfragid = '.$postArr['spnlpfragid'].'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to edit NLP fragment; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function deleteProfileFrag($spnlpfragid){
		$status = '';
		$sql = 'DELETE FROM specprocnlpfrag WHERE spnlpfragid = '.$spnlpfragid.'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to delete NLP fragment; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	//Batch processes
	public function batchNLP(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
		foreach($collArr as $cid){
			$sql = 'SELECT r.prlid, r.rawstr, o.occid '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'INNER JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
				'WHERE o.processingstatus = "unprocessed" AND length(r.rawstr) > 20 '.
				'AND (o.collid = '.$cid.') ';
			$sql .= 'LIMIT 30 ';
			if($rs = $this->conn->query($sql)){
				$recCnt = 0;
				while($r = $rs->fetch_object()){
					$rawStr = '';
				}
			}
		}
	}
	
	public function parseRawText($prlid){
		$textBlock = '';
		if(is_numeric($prlid)){
			$conn = MySQLiConnectionFactory::getCon("readonly");
			$sql = 'SELECT rawstr '.
				'FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlid.')';
			$rs = $conn->query($sql);
			if($r = $rs->fetch_object()){
				$textBlock = $r->rawstr;
			} 
			$rs->close();
			$conn->close();
		}
		return $this->parseTextBlock($textBlock);
	}
	
	public function parseTextBlock($textBlock){
		//Parse lines
		$lineArr = explode("\n",$textBlock);
		foreach($lineArr as $l){
			$this->parseLine($l);
		}
		return $this->dcArr;
	}
	
	private function parseLine($str){
		if(stripos($str,'herbarium')) return 0;
		if(stripos($str,'university')) return 0;
		
		//Test for country, state via Plants/Lichens of ...   
		if(!array_key_exists('stateprovince',$this->dcArr) && preg_match('/\w{1}\s+of\s+(.*)/',$str,$m)){
			$mStr = trim($m[1]);
			$sql = 'SELECT s.statename, c.countryname '.
				'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.stateid = c.stateid '.
				'WHERE (s.statename SOUNDS LIKE "'.$mStr.') ';
			$rs = $this->conn->query();
			$stStr = '';
			$coStr = '';
			if($r = $rs->fetch_object()){
				$this->dcArr['stateprovince'] = $r->statename;
				if(!array_key_exists('country',$this->dcArr)) $this->dcArr['country'] = $r->countryname;  
			}
			$rs->close();
			if($coStr) $this->dcArr['county'] = $coStr;
			
		}
		
		if(!array_key_exists('county',$this->dcArr) && preg_match('/(\w+)\sCounty|Co\./',$str,$m)){
			//county match
			$words = explode(' ',trim($m[1]));
			$sTerm = array();
			$cnt = 0;
			while($w = array_pop($words)){
				if($cnt < 4) break;
				if($cnt == 0){
					$sTerm[0] = $w;
				}
				else{
					$sTerm[$cnt] = $w.' '.$sTerm[$cnt-1];
				}
				$cnt++;
			}
			$sqlWhere = '';
			foreach($sTerm as $v){
				$sqlWhere .= ' OR c.countyname SOUNDS LIKE "'.$v.'"';
			}
			$sql = 'SELECT c.countyname '.
				'FROM lkupcounty c ';
			if(array_key_exists('stateprovince',$this->dcArr)) $sql .= 'INNER JOIN lkupstateprovince s ON c.stateid = s.stateid ';
			$sql .= 'WHERE ('.substr($sqlWhere,4).') ';
			if(array_key_exists('stateprovince',$this->dcArr)) $sql .= 's.statename = "'.$this->dcArr['stateprovince'].'"';
			$rs = $this->conn->query();
			$coStr = '';
			while($r = $rs->fetch_object()){
				if(strlen($r->countyname) > $coStr) $coStr = $r->countyname;
			}
			$rs->close();
			if($coStr) $this->dcArr['county'] = $coStr;
		}
		//Test for country 
		
		
		//Test for collector, number, date
		
		
	}
	private function parseRecordedBy($lineArr){
		while($l = array_pop($lineArr)){
			
		}
	}
	
	public function parseTextBlockSalix($textBlock){
		$dataMap = array();
		
		return $dataMap;
	}
	
	public function parseCollectorField($collName){
		$lastName = "";
		$lastNameArr = explode(',',$collName);
		$lastNameArr = explode(';',$lastNameArr[0]);
		$lastNameArr = explode('&',$lastNameArr[0]);
		$lastNameArr = explode(' and ',$lastNameArr[0]);
		if(preg_match_all('/[A-Za-z]{3,}/',$lastNameArr[0],$match)){
			if(count($match[0]) == 1){
				$lastName = $match[0][0];
			}
			elseif(count($match[0]) > 1){
				$lastName = $match[0][1];
			}
		}
		
		
	}
	
	//Misc functions
	private function cleanStr($inStr){
		$outStr = trim($inStr);
		$outStr = $this->conn->real_escape_string($outStr);
		return $outStr;
	}
}
?>
 