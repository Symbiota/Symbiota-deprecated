<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecProcNlpProfiles.php');
include_once($serverRoot.'/classes/SpecProcNlpParser.php');

class SpecProcNlp{

	protected $conn;
	protected $collId;
	protected $rawText;
	protected $errArr = array();
	
	private $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');
	
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
	 * @param 	Array of parsed term/values. 
	 * 			Key: DwC term; Value: Output text 
	 * @return 	TRUE on success; Array of warning returned on minor error; ERROR thrown on failure;  
	 */
	public function loadParsedData($inArr){
		$warningArr = array();
		if(!is_array($inArr)){
			throw new Exception('input is not an array');
			return false;
		}
		$dwcArr = array_change_key_case($inArr);
		
		//Obtain occid and prlid variables (both required)
		$occid = 0;
		if(!isset($dwcArr['prlid'])){
			throw new Exception('prlid is needed to load parsed data');
			return false;
		}
		$prlid = $dwcArr['prlid'];
		unset($dwcArr['prlid']);
		if(isset($dwcArr['occid'])){
			$occid = $dwcArr['occid'];
			unset($dwcArr['occid']);
		}
		elseif($prlid){
			//Grab occid using the prlid identifier
			$sql = 'SELECT IFNULL(i.occid,r.occid) as occid '.
				'FROM specprocessorrawlabels r LEFT JOIN images i ON r.imgid = i.imgid'. 
				'WHERE r.prlid = '.$prlid;
			if($rs = $this->conn->query($sql)){
				if($r = $r->fetch_object()){
					$occid = $r->occid;
				}
				else{
					throw new Exception('unable to grab occid using prlid ');
					return false;
				}
				$rs->free();
			}
			else{
				throw new Exception('unable to grab occid using prlid ');
				return false;
			}
		}
		else{
			throw new Exception('Missing occurrence identifier (occid) ');
			return false;
		}
		
		//Grab target fields
		$targetFields = array();
		$rsMD = $this->conn->query('SHOW COLUMNS FROM omoccurrences');
		while($r = $rsMD->fetch_object()){
			$targetFields[strtolower($r->Field)] = $r->Type;
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
			return false;
		}

		//Load data
		$occData = array_intersect_key($dwcArr,$targetFields);
		$leftOverData = array_diff_key($dwcArr,$targetFields);
		$sqlFrag = '';
		$finalFields = array();
		//int, double, varchar, text, date 
		foreach($occData as $fieldTerm => $value){
			$valueStr = $this->encodeString($value);
			$valueStr = $this->cleanInStr($valueStr);
			if($valueStr && !$curOccArr[$fieldTerm]){
				//A value does not already exist in existing record, thus OK to populate field 
				$valueIn = '';
				if(strpos($targetFields[$fieldTerm],'int') === 0 || strpos($targetFields[$fieldTerm],'double') === 0 || strpos($targetFields[$fieldTerm],'decimal') === 0){
					//Target field is a numeric data type
					if(is_numeric($valueStr)){
						$valueIn = $valueStr;
					}
					else{
						$warningArr[] = 'WARNING: '.$fieldTerm.' skipped ("'.$valueStr.'" not numeric)';
						//throw new Exception('');
					}
				}
				elseif(strpos($targetFields[$fieldTerm],'date') === 0){
					//Target field is a date data type
					$dateValue = $this->formatDate($valueStr);
					if($dateValue){
						$valueIn = '"'.$dateValue.'"';
					}
					else{
						$warningArr[] = 'WARNING: '.$fieldTerm.' skipped ("'.$valueStr.'" not a valid date)';
					}
				}
				else{
					//Target field is a text data type
					$valueIn = '"'.$valueStr.'"';
				}
				//Add to SQL if has value
				if($valueIn){
					$finalFields[] = $fieldTerm;
					$sqlFrag .= ','.$fieldTerm.'='.$valueIn;
				}
			}
		}

		if($sqlFrag){
			//Load data into existing record
			$sql = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$occid;
			
			//Code that modifies the processing status
			//processingStatus = unprocessed-NLP
			
			

			if($this->conn->query($sql)){
				//Version field that were modified along with the time stamp
				$sql = 'INSERT INTO specprocnlpversion(prlid, archivestr) '.
					'VALUES('.$prlid.',"'.implode(',',$finalFields).'")';
				$this->conn->query($sql);
			}
			else{
				throw new Exception('CRITICAL ERROR: unable to load data; '.$this->conn->error);
				return false;
			}
		}

		if(count($leftOverData)) $warningArr[] = 'Unmatched data fields: '.implode(', ',array_keys($leftOverData)); 
		
		return ($warningArr?$warningArr:true);
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
	private function formatDate($inStr){
		$retDate = '';
		$dateStr = trim($inStr);
		if(!$dateStr) return;
		$t = '';
		$y = '';
		$m = '00';
		$d = '00';
		//Remove time portion if it exists
		if(preg_match('/\d{2}:\d{2}:\d{2}/',$dateStr,$match)){
			$t = $match[0];
		}
		if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})\D*/',$dateStr,$match)){
			//Format: yyyy-m-d or yyyy-mm-dd
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		}
		elseif(preg_match('/^(\d{1,2})[\s\/-]{1}(\D{3,})\.*[\s\/-]{1}(\d{2,4})/',$dateStr,$match)){
			//Format: dd mmm yyyy, d mmm yy, dd-mmm-yyyy, dd-mmm-yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			if(array_key_exists($mStr,$this->monthNames)){
				$m = $this->monthNames[$mStr];
			}
		}
		elseif(preg_match('/^(\d{1,2})-(\D{3,})-(\d{2,4})/',$dateStr,$match)){
			//Format: dd-mmm-yyyy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$dateStr,$match)){
			//Format: mm/dd/yyyy, m/d/yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s{1}(\d{1,2}),{0,1}\s{1}(\d{2,4})/',$dateStr,$match)){
			//Format: mmm dd, yyyy
			$mStr = $match[1];
			$d = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/',$dateStr,$match)){
			//Format: mm-dd-yyyy, mm-dd-yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: mmm yyyy
			$mStr = strtolower(substr($match[1],0,3));
			if(array_key_exists($mStr,$this->monthNames)){
				$m = $this->monthNames[$mStr];
			}
			else{
				$m = '00';
			}
			$y = $match[2];
		}
		elseif(preg_match('/([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: yyyy
			$y = $match[1];
		}
		//Clean, configure, return
		if($y){
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			//Check to see if month is valid
			if($m > 12){
				$m = '00';
				$d = '00';
			}
			//check to see if day is valid for month
			if($d > 31){
				//Bad day for any month
				$d = '00';
			}
			elseif($d == 30 && $m == 2){
				//Bad feb date
				$d = '00';
			}
			elseif($d == 31 && ($m == 4 || $m == 6 || $m == 9 || $m == 11)){
				//Bad date, month w/o 31 days
				$d = '00';
			}
			//Do some cleaning
			if(strlen($y) == 2){ 
				if($y < 20) $y = '20'.$y;
				else $y = '19'.$y;
			}
			//Build
			$retDate = $y.'-'.$m.'-'.$d;
		}
		elseif(($timestamp = strtotime($retDate)) !== false){
			$retDate = date('Y-m-d', $timestamp);
		}
		if($t){
			$retDate .= ' '.$t;
		}
		return $retDate;
	}
	
	protected function encodeString($inStr){
 		global $charset;
 		$retStr = $inStr;
 		if($inStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
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
 