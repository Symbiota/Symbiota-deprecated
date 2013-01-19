<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecProcNlpProfiles.php');
include_once($serverRoot.'/classes/SpecProcNlpParser.php');

class SpecProcessorNlp{

	protected $conn;
	protected $collId;
	protected $rawText;

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
	
	/*
	 * @param 	$limit: SQL limit (optional)
	 * 			$limitStart: SQL limit start (optional)
	 * 			$innerTerm: inner wildcard preformed on rawstr
	 * 			$processingStatus: processing status set in omoccurrence table
	 * 			$source: source of OCR text string 
	 * @return 	Array of raw OCR text blocks 
	 */
	public function getOcrRawArr($limit = 1000, $limitStart = 0, $innerTerm = '', $processingStatus = 'unprocessed', $source = ''){
		$retArr = array();
		foreach($collArr as $cid){
			$sql = 'SELECT r.prlid, r.rawstr, IFNULL(i.occid,r.occid) as occid '. 
				'FROM specprocessorrawlabels r LEFT JOIN images i ON r.imgid = i.imgid '.
				'INNER JOIN omoccurrences o ON IFNULL(i.occid,r.occid) = o.occid '.
				'WHERE length(r.rawstr) > 20 ';
			if($this->collId) $sql .= 'AND (o.collid = '.$this->collId.') ';
			if($processingStatus) 'AND (o.processingstatus = "'.$processingStatus.'") ';
			if($source) 'AND r.source LIKE "%'.$source.'%" ';
			if($innerTerm) 'AND rawstr LIKE "%'.$innerTerm.'%" ';
			if($limit) $sql .= 'LIMIT '.($limitStart?','.$limitStart:'').$limit;
			if($rs = $this->conn->query($sql)){
				$recCnt = 0;
				while($r = $rs->fetch_object()){
					$retArr[$r->occid][$r->prlid]['rawstr'] = $r->rawstr;
					$retArr[$r->occid][$r->prlid]['source'] = $r->source;
				}
			}
		}
		return $retArr;
	}
	
	/*
	 * @param 	Array of parsed term/values. Key: DwC term; Value: Output text 
	 * 			SQL limit start
	 * @return 	Array of raw OCR text blocks 
	 */
	public function loadParsedData($inArr){
		$retStatus = '';
		if(!is_array($dwcArr)) throw new Exception('input is not an array');
		$dwcArr = array_change_key_case($inArr);
		
		//Set occid and prlid variables (both required)
		$occid = 0;
		if(!isset($dwcArr['prlid'])) throw new Exception('prlid is needed to load parsed data');
		$prlid = $dwcArr['prlid'];
		if(isset($dwcArr['occid'])){
			$occid = $dwcArr['occid'];
			unset($dwcArr['occid']);
		}
		elseif(isset($dwcArr['prlid'])){
			//Grab occid using the prlid identifier
			$sql = 'SELECT IFNULL(i.occid,r.occid) as occid '.
				'FROM specprocessorrawlabels r LEFT JOIN images i ON r.imgid = i.imgid'. 
				'WHERE r.prlid = '.$dwcArr['prlid'];
			if($rs = $this->conn->query($sql)){
				if($r = $r->fetch_object()){
					$occid = $r->occid;
					unset($dwcArr['prlid']);
				}
				else{
					throw new Exception('unable to grab occid using prlid ');
					return;
				}
				$rs->free();
			}
			else{
				throw new Exception('unable to grab occid using prlid ');
				return;
			}
		}
		else{
			throw new Exception('Missing occurrence identifier (occid) ');
			return;
		}
		
		//Grab target fields
		$targetFields = array();
		$rsMD = $this->conn->query('SHOW COLUMNS FROM omoccurrences');
		while($r = $rsMD->fetch_object){
			$targetFields[strtolower($r->Field)] = $r->type;
		}
		$rsMD->free();
		//Remove some internal system fields
		unset($targetFields['occid']);
		unset($targetFields['collid']);
		unset($targetFields['dbpk']);
		unset($targetFields['tidinterpreted']);
		unset($targetFields['instititioncode']);
		unset($targetFields['collectioncode']);
		unset($targetFields['recordedbyid']);
		unset($targetFields['modified']);
		unset($targetFields['observeruid']);
		unset($targetFields['processingstatus']);
		unset($targetFields['recordenteredby']);
		unset($targetFields['dateLastModified']);
		
		//Get existing data
		$curOccArr = array();
		if($rs = $this->conn->query('SELECT * FROM omoccurrences WHERE occid = '.$occid)){
			$curOccArr = array_change_key_case($rs->fetch_assoc());
		}
		else{ 
			throw new Exception('CRITICAL ERROR: unable to populate $curOccArr');
		}

		//Load data
		$occData = array_interset_key($dwcArr,$targetFields);
		$leftOverData = array_diff_key($dwcArr,$targetFields);
		$fieldSql = '';
		$valueSql = '';
		//int, double, varchar, text, date 
		foreach($occData as $fieldTerm => $valueStr){
			//Only load field if data doesn't already exist (IS NULL or empty string)
			$valueIn = '';
			if(strpos($targetFields[$fieldTerm],'int') === 0 || strpos($targetFields[$fieldTerm],'double') === 0){
				if(is_numeric($valueStr)){
					$valueIn = $valueStr;
				}
				else{
					throw new Exception('');
				}
			}
			else{
				
			}
			//Add to SQL if has value
			if($valueIn){
				$fieldSql .= ','.$fieldTerm;
				$valueSql .= $valueIn;
			}
		}

		//Version field that were modified along with the time stamp
		$valueSql = $this->cleanInStr($this->encodeString($valueSql));
		if($valueSql){
			$sql = 'INSERT INTO specprocnlpversion('.substr($fieldSql,1).') '.
				'VALUES('.substr($valueSql,1).')';
		}
		
		if(count($leftOverData)) $retStatus = 'Unmatched data fields: '.implode(', ',array_keys($leftOverData)); 
		
		return $retStatus;
	}

	//Setters, getters
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
	
	//Misc functions
	protected function encodeString($inStr){
 		global $charset;
 		$retStr = $inStr;
 		if($inStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				if(mb_detect_encoding($inStr,'ISO-8859-1,UTF-8') == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($charset) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
 		}
		return $retStr;
	}
	
	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>
 