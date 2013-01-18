<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpecProcessorNlp{

	protected $conn;
	private $collId;
	private $rawText;

	private $dcArr = array();
	private $tokenArr = array();
	private $fragMatches = array();

	private $indicatorTerms = array();
	private $pregMatchTerms = array();
	
	private $occDupes = array();
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$indicatorTerms['exsiccatiTitle'] = array('exs','ccati','lichenes');
		$indicatorTerms['recordedBy'] = array('coll.','leg.','collected by');
		$indicatorTerms['identifiedBy'] = array('det.','determ');
		$indicatorTerms['county'] = array('co.','county');
		$indicatorTerms['verbatimEventDate'] = array('jan','feb','mar','apr','may','jun','jul','aug','sept','oct','nov','dec');
		$pregMatchTerms['verbatimEventDate'] = array('/\D19\d{2}\D/','/\D20\d{2}\D/');
		$pregMatchTerms['exsiccatiNumber'] = array('/\D{0,1}(\d+)\.{1}\s{1,3}[A-Z]{1}[a-z]+/');
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

	//Manage profiles
	public function addProfile($postArr){
		$status = '';
		$sql = 'INSERT INTO specprocnlp(title,sqlfrag,patternmatch,notes,collid) '.
			'VALUES("'.$this->cleanInStr($postArr['title']).'","'.$this->cleanInStr($postArr['sqlfrag']).'","'.
			$this->cleanInStr($postArr['patternmatch']).'","'.$this->cleanInStr($postArr['notes']).'",'.$postArr['collid'].')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to add NLP profile; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function editProfile($postArr){
		$status = '';
		$sql = 'UPDATE specprocnlp SET title = "'.$this->cleanInStr($postArr['title']).'",sqlfrag = "'.$this->cleanInStr($postArr['sqlfrag']).
		'",patternmatch = "'.$this->cleanInStr($postArr['patternmatch']).'",notes = "'.$this->cleanInStr($postArr['notes']).'" '.
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
			'VALUES("'.$this->cleanInStr($postArr['spnlpid']).'","'.$this->cleanInStr($postArr['fieldname']).'","'.
			$this->cleanInStr($postArr['patternmatch']).'","'.$this->cleanInStr($postArr['notes']).'",'.$postArr['sortseq'].')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to add NLP fragment; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function editProfileFrag($postArr){
		$status = '';
		$sql = 'UPDATE specprocnlpfrag SET fieldname = "'.$postArr['fieldname'].'",patternmatch = "'.$this->cleanInStr($postArr['patternmatch']).
			'",notes = "'.$this->cleanInStr($postArr['notes']).'",sortseq = '.$postArr['sortseq'].' '.
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

	//Parsing functions
	private function parseLocal(){
		$lineArr = explode("\n",$this->rawText);
		foreach($lineArr as $str){
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
	}
	
	private function parseRecordedBy(){
		$lineArr = explode("\n",$this->rawText);
		//Locate matching lines
		foreach($lineArr as $line){
			//Test for exsiccati title
			if(isset($indicatorTerms['recordedBy'])){
				foreach($indicatorTerms['recordedBy'] as $term){
					if(stripos($line,$term)) $this->fragMatches['recordedBy'] = trim($line);
				}
			}
			if(isset($pregMatchTerms['recordedBy'])){
				foreach($pregMatchTerms['recordedBy'] as $pattern){
					if(preg_match($pattern,$line,$m)){
						if(count($m) > 1) $this->fragMatches['recordedBy'] = trim($m[1]);
						else $this->fragMatches['recordedBy'] = $m[0];
					}
				}
			}
		}
		//If no match, try digging deeper
		if(!isset($this->fragMatches['recordedBy'])){
			foreach($lineArr as $line){
				if($nameTokens = str_word_count($line,1)){
					$sql = '';
					foreach($nameTodkens as $v){
						$sql .= 'OR familyname = "'.str_replace('"','',$v).'" ';
					}
					$sql = 'SELECT recordedbyid FROM omcollectors WHERE '.substr($sql,2);
					if($rs = $this->conn->query($sql)){
						if($r = $rs->fetch_object()){
							$this->fragMatches['recordedBy'] = trim($line);
						}
						$rs->free();
					}
				}
			}
		}
		//And again a little deeper
		if(!isset($this->fragMatches['recordedBy'])){
			foreach($lineArr as $line){
				if($nameTokens = str_word_count($line,1)){
					$sql = '';
					foreach($nameTodkens as $v){
						$sql .= 'OR familyname SOUNDS LIKE "'.str_replace('"','',$v).'" ';
					}
					$sql = 'SELECT recordedbyid FROM omcollectors WHERE '.substr($sql,2);
					if($rs = $this->conn->query($sql)){
						if($r = $rs->fetch_object()){
							$this->fragMatches['recordedBy'] = trim($line);
						}
						$rs->free();
					}
				}
			}
		}

		//Look for possible occurrence matches
		if(isset($this->fragMatches['recordedby'])){
			if(array_key_exists('exsiccatiNumber',$this->fragMatches)){
				$sql = '';
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$this->occDupes[] = $r->occid;
					}
					$rs->free();
				}
			}
		}
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
	
	private function parseExsiccati(){
		$lineArr = explode("\n",$this->rawText);
		//Locate matching lines
		foreach($lineArr as $line){
			//Test for exsiccati title
			if(isset($indicatorTerms['exsiccatiTitle'])){
				foreach($indicatorTerms['exsiccatiTitle'] as $term){
					if(stripos($line,$term)) $this->fragMatches['exsiccatiTitle'] = trim($line);
				}
			}
			if(isset($pregMatchTerms['exsiccatiTitle'])){
				foreach($pregMatchTerms['exsiccatiTitle'] as $pattern){
					if(preg_match($pattern,$line,$m)){
						if(count($m) > 1) $this->fragMatches['exsiccatiTitle'] = trim($m[1]);
						else $this->fragMatches['exsiccatiTitle'] = $m[0];
					}
				}
			}
			//Test for exsiccati number
			if(isset($indicatorTerms['exsiccatiNumber'])){
				foreach($indicatorTerms['exsiccatiNumber'] as $term){
					if(stripos($line,$term)) $this->fragMatches['exsiccatiNumber'] = trim($line);
				}
			}
			if(isset($pregMatchTerms['exsiccatiNumber'])){
				foreach($pregMatchTerms['exsiccatiNumber'] as $pattern){
					if(preg_match($pattern,$line,$m)){
						if(count($m) > 1) $this->fragMatches['exsiccatiNumber'] = trim($m[1]);
						else $this->fragMatches['exsiccatiNumber'] = $m[0];
					}
				}
			}
		}
		
		//Look for possible occurrence matches
		if(isset($this->fragMatches['exsiccatiTitle'])){
			if(isset($this->fragMatches['exsiccatiNumber'])){
				$exactHits = false;
				$sql = 'SELECT ol.occid '.
					'FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid '.
					'INNER JOIN omexsiccatiocclink ol ON n.omenid = ol.omenid '.
					'WHERE ((t.title = "'.$this->fragMatches['exsiccatiTitle'].'") OR (t.abbreviation = "'.$this->fragMatches['exsiccatiTitle'].'")) '.
					'AND (n.excnumber = "'.$this->fragMatches['exsiccatiNumber'].'")';
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$this->occDupes[] = $r->occid;
						$exactHits = true;
					}
					$rs->free();
				}
				if(!$exactHits){
					//No exact hits, thus lets try to dig deeper
					$titleTokens = explode(' ',str_replace(array(',',';'),' ',$this->fragMatches['exsiccatiTitle']));
					$sql = 'SELECT ol.occid '.
						'FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid '.
						'INNER JOIN omexsiccatiocclink ol ON n.omenid = ol.omenid '.
						'WHERE (n.excnumber = "'.$this->fragMatches['exsiccatiNumber'].'") '.
						'AND ((t.title SOUNDS LIKE "'.$this->fragMatches['exsiccatiTitle'].'") '.
						'OR (t.abbreviation SOUNDS LIKE "'.$this->fragMatches['exsiccatiTitle'].'"))';
					if($rs = $this->conn->query($sql)){
						while($r = $rs->fetch_object()){
							$this->occDupes[] = $r->occid;
						}
						$rs->free();
					}
				}
			}
		}
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
	
	//Setters, getters, and misc
	public function setRawText($rawText){
		$this->rawText = $rawText;
	}
	
	public function setRawTextById($prlid){
		$textBlock = '';
		if(is_numeric($prlid)){
			$conn = MySQLiConnectionFactory::getCon("readonly");
			$sql = 'SELECT rawstr '.
				'FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlid.')';
			$rs = $conn->query($sql);
			if($rs){
				if($r = $rs->fetch_object()){
					$this->rawText = $r->rawstr;
				}
				$rs->close();
				$conn->free();
			}
			else{
				trigger_error('Unable to setRawTextById'.$this->conn->error,E_USER_ERROR);
			}
		}
		$this->tokenizeRawString();
	}
	
	private function tokenizeRawString(){
		$lineArr = explode("\n",$this->rawText);
		foreach($lineArr as $l){
			$this->tokenArr = array_merge($tokens,preg_split('/[\s,;]+/',$l));
		}
	}
	
	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>
 