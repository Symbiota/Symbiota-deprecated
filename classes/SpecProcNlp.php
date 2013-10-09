<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecProcNlpProfiles.php');
include_once($serverRoot.'/classes/SpecProcNlpParser.php');
include_once($serverRoot.'/classes/SpecProcNlpParserLBCCCommon.php');

class SpecProcNlp{

	protected $conn;
	protected $collId;
	protected $occid;
	protected $prlid;
	protected $catalogNumber;
	protected $url;
	protected $ocrSource;
	private $printMode = null;		//0 = database, 1 = report, 2 = csv
	private $logErrors = 0;
	private $totalStats = array();
	private $csvHeaderArr = array();

	private $outFH;
	private $logFH;
	private $outFilePath;

	private $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->outFilePath = $GLOBALS['serverRoot'].(substr($GLOBALS['serverRoot'],-1)=='/'?'':'/')."temp/logs/LbccParser_".date('Y-m-d_his');
		set_time_limit(7200);
	}

	function __destruct(){
		if($this->printMode == 1){
			$this->printSummary();
			$this->outToReport('Processing finished: '.date('Y-m-d h:i:s A')."\n\n");
			echo '<div style="margin-left:10px;">Output file: <a href="'.$this->outFilePath.'.txt">'.$this->outFilePath.'.txt</a></div>';
			echo '<div style="margin-left:10px;">Log file: <a href="'.$this->outFilePath.'.log">'.$this->outFilePath.'.log</a></div>';
			if($this->outFH) fclose($this->outFH);
		}
		elseif($this->printMode == 2){
			//Create new final file and prime with header
			fclose($this->outFH);
			$outFinalFH = fopen($this->outFilePath.'.csv', 'w');
			fputcsv($outFinalFH,array_keys($this->csvHeaderArr));
			//Append data from temp file to final file
			$outTempFH = fopen($this->outFilePath.'_temp.csv', 'r');
			while (!feof($outTempFH)) {
				$contents = fread($outTempFH,8192);
				fwrite($outFinalFH,$contents);
			}
			fclose($outTempFH);
			unlink($this->outFilePath.'_temp.csv');
			fclose($outFinalFH);

			$this->logMsg($this->totalStats['collmeta']['totalcnt'].' records output to CSV');
			echo '<div style="margin-left:10px;">Output file: <a href="'.$this->outFilePath.'.csv">'.$this->outFilePath.'.csv</a></div>';
		}
		elseif($this->printMode === 0){
			$this->logMsg($this->totalStats['collmeta']['totalcnt'].' records processed and databased');
		}
		if($this->logFH){
			$this->logMsg('Processing finished: '.date('Y-m-d h:i:s A')."\n");
			fclose($this->logFH);
		}
		if(!($this->conn === false)) $this->conn->close();
	}

	public function parseTextBlock($rawStr){
		//Parse and return
		$dwcArr = array_change_key_case($this->parse($rawStr));
		if(array_key_exists('scientificname',$dwcArr) && !array_key_exists('sciname',$dwcArr)){
			$dwcArr['sciname'] = $dwcArr['scientificname'];
			unset($dwcArr['scientificname']);
		}
		foreach($dwcArr as $k => $v){
			if($v){
				//If is a latin character set, convert to UTF-8
				if(mb_detect_encoding($v,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$dwcArr[$k] = utf8_encode($v);
					//$dwcArr[$k] = iconv("ISO-8859-1//TRANSLIT","UTF-8",$v);
				}
			}
			else{
				unset($dwcArr[$k]);
			}
		}

		/*
		if(is_numeric($prlid)){
			$rawStr = '';
			//Get raw OCR string
			$sql = 'SELECT r.prlid, r.rawstr, r.source, o.occid, o.catalognumber, IFNULL(i.originalurl,i.url) AS url '.
				'FROM specprocessorrawlabels r LEFT JOIN images i ON r.imgid = i.imgid '.
				'INNER JOIN omoccurrences o ON IFNULL(i.occid,r.occid) = o.occid '.
				'WHERE (r.prlid = '.$prlid.')';
			//echo $sql;
			$cnt = 0;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->ocrSource = $r->source;
				$this->url = $r->url;
				$this->prlid = $r->prlid;
				$this->occid = $r->occid;
				$this->catalogNumber = $r->catalognumber;
				$rawStr = $r->rawstr;
			}
			$rs->free();
			//Parse and return
			$dwcArr = array_change_key_case($this->parse($rawStr));
			if(array_key_exists('scientificname',$dwcArr) && !array_key_exists('sciname',$dwcArr)){
				$dwcArr['sciname'] = $dwcArr['scientificname'];
				unset($dwcArr['scientificname']);
			}
			foreach($dwcArr as $k => $v){
				if($v){
					//If is a latin character set, convert to UTF-8
					if(mb_detect_encoding($v,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
						$dwcArr[$k] = utf8_encode($v);
						//$dwcArr[$k] = iconv("ISO-8859-1//TRANSLIT","UTF-8",$v);
					}
				}
				else{
					unset($dwcArr[$k]);
				}
			}
		}
		*/
		return json_encode($dwcArr);
	}

	public function batchProcess($collTarget, $source = 'abbyy'){
		$this->setCollectionMetadata($collTarget);
		$collArr = explode(',',$collTarget);
		$totalCnt = 0;
		foreach($collArr as $collId){
			$this->setCollId($collId);
			$sql = 'SELECT r.prlid, r.rawstr, r.source, o.occid, o.catalognumber, IFNULL(i.originalurl,i.url) AS url '.
				'FROM specprocessorrawlabels r LEFT JOIN images i ON r.imgid = i.imgid '.
				'INNER JOIN omoccurrences o ON IFNULL(i.occid,r.occid) = o.occid '.
				'WHERE length(r.rawstr) > 20 AND (o.processingstatus = "unprocessed") ';
			if($this->collId) $sql .= 'AND (o.collid = '.$this->collId.') ';
			if($source) $sql .= 'AND r.source LIKE "%'.$source.'%" ';
			$sql .= 'LIMIT 10';
			//echo $sql;
			$cnt = 0;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$rawStr = $r->rawstr;
				$this->ocrSource = $r->source;
				$this->url = $r->url;
				$this->prlid = $r->prlid;
				$this->occid = $r->occid;
				$this->catalogNumber = $r->catalognumber;

				//Process string and load results into $dwcArr
				//Exceptions must be caught in try/catch blocks
				$dwcArr = array();
				try{
					$dwcArr = $this->parse($rawStr);
				}
				catch(Exception $e){
					$eStr = 'ERROR: '.$e->getMessage();
					//echo $eStr;
					$this->logMsg($eStr);
					if($this->printMode == 1) $this->outToReport($eStr);
				}

				if($this->printMode == 1){
					//Output to report file
					$this->printResult($rawStr,$dwcArr);
				}
				elseif($this->printMode == 2){
					$dwcArr['occid'] = $this->occid;
					$dwcArr['prlid'] = $this->prlid;
					$dwcArr['ocrsource'] = $this->ocrSource;
					$dwcArr['imageurl'] = $this->url;
					if(!array_key_exists('catalogNumber', $dwcArr)) $dwcArr['catalogNumber'] = $this->catalogNumber;
					//Output to csv file
					$this->printCsv($dwcArr,$totalCnt);
				}
				else{
					//Output to database
					$this->loadParsedData($dwcArr);
				}
				$cnt++;
			}
			$this->totalStats['collmeta'][$collId]['cnt'] = $cnt;
			$totalCnt += $cnt;
		}
		$this->totalStats['collmeta']['totalcnt'] = $totalCnt;
	}

	protected function parseRecordedBy(){
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

	//Misc functions
	protected function getPoliticalUnits($countrySeed = '', $stateSeed = '', $countySeed = '', $wildStr = ''){
		$retArr = array();
		$cnt = 0;
		$bestMatch = 0;
		$sqlBase = 'SELECT cr.countryname, sp.statename, c.countyname '.
			'FROM lkupcountry cr INNER JOIN lkupstateprovince sp ON cr.countryid = sp.countryid '.
			'LEFT JOIN lkupcounty c ON sp.stateid = c.stateid WHERE ';
		if($countrySeed || $stateSeed || $countySeed){
			//First look for exact match
			$sqlWhere = '';
			if($countrySeed){
				$sqlWhere .= 'AND (cr.countryName = "'.$countrySeed.'") ';
			}
			if($stateSeed){
				$sqlWhere .= 'AND (sp.stateName = "'.$stateSeed.'") ';
			}
			if($countySeed){
				$sqlWhere .= 'AND ((c.countyname = "'.$stateSeed.'") OR (c.countyname LIKE "'.$stateSeed.'%")) ';
			}
			$rs = $this->conn->query($sqlBase.substr($sqlWhere,4));
			while($r = $rs->fetch_object()){
				$retArr[$cnt]['country'] = $r->countryname;
				$retArr[$cnt]['state'] = $r->statename;
				$retArr[$cnt]['county'] = $r->countyname;
				$cnt++;
			}
			$rs->free();
			if(!$retArr){
				$sqlWhere = '';
				//Nothing returns so lets go deeper
				if($countrySeed){
					$sqlWhere .= 'AND (SOUNDEX(cr.countryName) = SOUNDEX("'.$countrySeed.'")) ';
				}
				if($stateSeed){
					$sqlWhere .= 'AND (SOUNDEX(sp.stateName) = SOUNDEX("'.$stateSeed.'")) ';
				}
				if($countySeed){
					$sqlWhere .= 'AND (SOUNDEX(c.countyname) = SOUNDEX("'.$stateSeed.'")) ';
				}
				$rs = $this->conn->query($sqlBase.substr($sqlWhere,4));
				while($r = $rs->fetch_object()){
					$retArr[$cnt]['country'] = $r->countryname;
					$retArr[$cnt]['state'] = $r->statename;
					$retArr[$cnt]['county'] = $r->countyname;
					$cnt++;
				}
				$rs->free();
			}

			//Check to see if we have more than one possible matches
			if(count($retArr) > 1){
				//Locate the best match
				$rateArr = array();
				foreach($retArr as $k => $vArr){
					$rating = 0;
					if($countrySeed) $rating += levenshtein($countrySeed,$vArr['country']);
					if($stateSeed) $rating += levenshtein($stateSeed,$vArr['state']);
					if($countySeed) $rating += levenshtein($countySeed,$vArr['county']);
					$rateArr[$k] = $rating;
					asort($rateArr,SORT_NUMERIC);
					$bestMatch = key($rateArr);
				}
			}
		}
		elseif($wildStr){
			//Look for possible matches
			$sqlWhere = '';
			//Split string into words separated by commas, semi-colons, or white spaces
			$wildArr = preg_split("/[\s,;]+/",$wildStr);
			foreach($wildArr as $k => $v){
				//Clean values
				$wildArr[$k] = trim($v);
				$sqlWhere .= 'OR (SOUNDEX(cr.countryName) = SOUNDEX("'.$wildArr[$k].'")) '.
					'OR (SOUNDEX(sp.stateName) = SOUNDEX("'.$wildArr[$k].'")) '.
					'OR (SOUNDEX(c.countyName) = SOUNDEX("'.$wildArr[$k].'")) ';
			}
			if($sqlWhere){
				$rs = $this->conn->query($sqlBase.substr($sqlWhere,3));
				while($r = $rs->fetch_object()){
					$retArr[$cnt]['country'] = $r->countryname;
					$retArr[$cnt]['state'] = $r->statename;
					$retArr[$cnt]['county'] = $r->countyname;
					$cnt++;
				}
				$rs->free();
			}
			//Now let's see if we can figure out the best match
			if(count($retArr) > 1){
				$rateArr = array();
				$unitArr = array('country','state','county');
				foreach($retArr as $mk => $mArr){
					$rating = 0;
					foreach($wildArr as $wk => $wv){
						foreach($unitArr as $uv){
							$r = levenshtein($mArr[$uv],$wv);
							if($r == 0) $rating += 3;
							if($r == 1) $rating += 1;
						}
					}
					$rateArr[$mk] = $rating;
				}
				asort($rateArr,SORT_NUMERIC);
				end($rateArr);
				$bestMatch = key($rateArr);
			}
		}
		return (isset($retArr[$bestMatch])?$retArr[$bestMatch]:null);
	}

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

	//Setters, getters
	public function setCollId($collId){
		if(is_numeric($collId)){
			$this->collId = $collId;
		}
	}

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

	private function setCollectionMetadata($collTarget){
		$sql = 'SELECT collid, collectionname FROM omcollections ';
		if(preg_match('/^[\d,]+$/',$collTarget)) $sql .= 'WHERE collid IN('.$collTarget.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->totalStats['collmeta'][$r->collid]['name'] = $r->collectionname;
		}
	}

	public function setPrintMode($m){
		if(is_numeric($m)) $this->printMode = $m;
		if($this->printMode && !$this->outFH){
			//$outFilePath = '';
			if($this->printMode == 1){
				$this->outFH = fopen($this->outFilePath.'.txt', 'w');
			}
			elseif($this->printMode == 2){
				$this->outFH = fopen($this->outFilePath.'_temp.csv', 'w');
			}
			if($this->printMode == 1){
				$this->outToReport('Start time: '.date('Y-m-d h:i:s A')."\n\n");
			}
		}
	}

	public function setLogErrors($l){
		$this->logErrors = $l;
	}

	//Ouput functions
	private function printCsv($dwcArr){
		//Add new header names to header array
		$newHeadArr = array_diff_key($dwcArr, $this->csvHeaderArr);
		foreach($newHeadArr as $newK => $nV){
			$this->csvHeaderArr[$newK] = '';
		}
		//Output values in exact order as header array
		$outputArr = $this->csvHeaderArr;
		foreach($dwcArr as $k => $v){
			$outputArr[$k] = $v;
		}
		if($this->outFH) fputcsv($this->outFH,$outputArr);
	}

	private function printResult($rawStr,$dwcArr){
		$this->outToReport("collid: ".$this->collId.", occid: ".$this->occid);
		$this->outToReport($this->url."\n");
		//If string is UTF-8, convert to latin1
		if(mb_detect_encoding($rawStr,'UTF-8,ISO-8859-1') == "UTF-8"){
			$rawStr = utf8_decode($rawStr);
		}
		$this->outToReport("OCR string:\n".$rawStr."\n");
		$this->outToReport("Results:");
		foreach($dwcArr as $fieldName => $fieldValue) {
			$this->outToReport("\t".$fieldName.": ".$fieldValue);
			//Collect field stats for final report
			if(isset($this->totalStats[$fieldName][$this->collId])) {
				$num = ++$this->totalStats[$fieldName][$this->collId];
				$this->totalStats[$fieldName][$this->collId] = $num;
			}
			else{
				$this->totalStats[$fieldName][$this->collId] = 1;
			}
		}
	}

	private function printSummary(){
		$collArr = $this->totalStats['collmeta'];
		$this->outToReport("------------------------------------------------------------------------------------------");
		$this->outToReport("\n\nSummary of ".$collArr['totalcnt']." labels:");
		unset($this->totalStats['collmeta']);
		//Show stats for all collections
		foreach($this->totalStats as $fieldName => $fieldArr){
			$tCnt = 0;
			foreach($fieldArr as $collId => $fCnt){
				$tCnt += $fCnt;
			}
			$perc = ($collArr['totalcnt']?round(100*$tCnt/$collArr['totalcnt']):0);
			$this->outToReport("\t".$fieldName." ".$tCnt." times (".$perc."%)");
		}

		//Show stats for each collection processed
		foreach($collArr as $collId => $collArr){
			if($collId){
				$this->outToReport("\nCollection: ".$collArr['name']." (#".$collId."), total labels: ".$collArr['cnt']);
				foreach($this->totalStats as $fieldName => $fieldArr){
					if(isset($fieldArr[$collId])){
						$perc = ($collArr['cnt']?round(100*$fieldArr[$collId]/$collArr['cnt']):0);
						$this->outToReport("\t".$fieldName.": ".$fieldArr[$collId].' times ('.$perc.'%)' );
					}
				}
			}
		}
	}

	private function outToReport($str){
		if($this->outFH){
			fwrite($this->outFH,$str."\n");
		}
		else{
			echo $str."\n";
		}
	}

	/*
	 * @param 	Array of parsed term/values.
	 * 			Key: DwC term; Value: Output text
	 * @return 	TRUE on success
	 */
	private function loadParsedData($inArr){
		if(!$inArr){
			$this->logMsg('ERROR: input empty');
			return false;
		}
		if(!is_array($inArr)){
			$this->logMsg('ERROR: input is not an array');
			return false;
		}
		$dwcArr = array_change_key_case($inArr);

		//Check to make sure occid and prlid variables are available (both required)
		if(!$this->prlid){
			$this->logMsg('ERROR: prlid is needed to load parsed data');
			return false;
		}
		if(!$this->occid){
			$this->logMsg('ERROR: occid is needed to load parsed data');
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
		if($rs = $this->conn->query('SELECT * FROM omoccurrences WHERE occid = '.$this->occid)){
			$curOccArr = array_change_key_case($rs->fetch_assoc());
		}
		else{
			$this->logMsg('ERROR: unable to populate $curOccArr');
			return false;
		}

		//Do some cleaning
		if(isset($dwcArr['month']) && !is_numeric($dwcArr['month'])){
			//Month should be numeric, yet is a sting. Check to see if it is the month name or abbreviation
			$mStr = strtolower(substr($dwcArr['month'],0,3));
			if(array_key_exists($mStr,$this->monthNames)){
				$dwcArr['month'] = $this->monthNames[$mStr];
			}
			else{
				if(!isset($dwcArr['verbatimeventdate']) || !$dwcArr['verbatimeventdate']){
					$vDate = '';
					if(isset($dwcArr['day'])) $vDate = $dwcArr['day'].' ';
					$vDate .= $dwcArr['month'].' ';
					if(isset($dwcArr['year'])) $vDate .= $dwcArr['year'];
					$dwcArr['verbatimeventdate'] = trim($vDate);
				}
				unset($dwcArr['month']);
			}
		}
		if(!isset($dwcArr['eventdate']) && isset($dwcArr['year']) && isset($dwcArr['month'])){
			//If not eventdate and year/month exists, build event date from year-month-day
			if(!isset($dwcArr['day'])) $dwcArr['day'] = "00";
			$dwcArr['eventdate'] = $dwcArr['year'].'-'.$dwcArr['month'].'-'.$dwcArr['day'];
		}
		if(!isset($dwcArr['eventdate']) && isset($dwcArr['verbatimeventdate'])){
			$dwcArr['eventdate'] = $this->formatDate($dwcArr['verbatimeventdate']);
		}

		//Load data
		$dataToLoad = array_intersect_key($dwcArr,$targetFields);
		$leftOverData = array_diff_key($dwcArr,$targetFields);
		$sqlFrag = '';
		$finalFields = array();
		//int, double, varchar, text, date
		foreach($dataToLoad as $fieldTerm => $value){
			$valueStr = $this->encodeString($value);
			$valueStr = $this->cleanInStr($valueStr);
			if($valueStr){
				if(!$curOccArr[$fieldTerm]){
					//A value does not already exist in existing record, thus OK to populate field
					$valueIn = '';
					if(strpos($targetFields[$fieldTerm],'int') === 0 || strpos($targetFields[$fieldTerm],'double') === 0 || strpos($targetFields[$fieldTerm],'decimal') === 0){
						//Target field is a numeric data type
						if(is_numeric($valueStr)){
							$valueIn = $valueStr;
						}
						else{
							$this->logMsg('WARNING: '.$fieldTerm.' skipped ("'.$valueStr.'" not numeric)');
						}
					}
					elseif(strpos($targetFields[$fieldTerm],'date') === 0){
						//Target field is a date data type
						$dateValue = $this->formatDate($valueStr);
						if($dateValue){
							$valueIn = '"'.$dateValue.'"';
						}
						else{
							$this->logMsg('WARNING: '.$fieldTerm.' skipped ("'.$valueStr.'" not a valid date)');
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
				else{
					$this->logMsg('WARNING: '.$fieldTerm.' skipped because value already existed in DB ("'.$valueStr.'")');
				}
			}
		}

		if($sqlFrag){
			//Code that modifies the processing status
			//processingStatus = unprocessed-NLP
			$sqlFrag .= ', processingstatus = "unprocessed-NLP"';

			//Load data into existing record
			$sql = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$this->occid;
			echo $sql.'<br/>';
			if($this->conn->query($sql)){
				//Version field that were added along with the time stamp
				$sql = 'INSERT INTO specprocnlpversion(prlid, archivestr) '.
					'VALUES('.$this->prlid.',"'.implode(',',$finalFields).'")';
				echo $sql.'<br/>';
				if(!$this->conn->query($sql)){
					$this->logMsg('WARNING: unable to version edit: ; '.$this->conn->error);
					$this->logMsg('Error details: ; '.$this->conn->error);
				}

				//Deal with Exsiccati data
				if(isset($dwcArr['exsiccatinumber']) && $dwcArr['exsiccatinumber'] && !isset($dwcArr['exsnumber'])){
					//exsiccatinumber variable submitted instead of exsnumber
					$dwcArr['exsnumber'] = $dwcArr['exsiccatinumber'];
				}
				//Exsiccati number is required, if not there no need to progress
				if((isset($dwcArr['exsnumber']) && $dwcArr['exsnumber']) || (isset($dwcArr['omenid']) && $dwcArr['omenid'])){
					if(isset($dwcArr['exsiccatititle']) && $dwcArr['exsiccatititle'] && (!isset($dwcArr['ometid']) || !$dwcArr['ometid'])){
						//Get ometid (title number) since only exsiccatiTitle exists
						$sql = 'SELECT ometid FROM omexsiccatititles '.
							'WHERE (title = "'.trim($dwcArr['exsiccatititle']).'") OR (abbreviation = "'.trim($dwcArr['exsiccatititle']).'")';
						$rs = $this->conn->query($sql);
						if($r = $rs->fetch_object()){
							$dwcArr['ometid'] = $r->ometid;
						}
						$rs->free();
					}
					if($dwcArr['ometid']){
						if(!isset($dwcArr['omenid']) && !$dwcArr['omenid'] && isset($dwcArr['exsnumber']) && $dwcArr['exsnumber']){
							//Get exsiccati number id (omenid), since exsnumber was only supplied
							$sql = 'SELECT omenid FROM omexsiccatinumbers '.
								'WHERE ometid = ('.$dwcArr['ometid'].') AND (exsnumber = "'.trim($dwcArr['exsnumber']).'")';
							$rs = $this->conn->query($sql);
							if($r = $rs->fetch_object()){
								$dwcArr['omenid'] = $r->omenid;
							}
							$rs->free();
							if(!isset($dwcArr['omenid'])){
								//Exsiccati number needs to be added
								$sql = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
									'VALUES('.$dwcArr['ometid'].',"'.trim($dwcArr['exsnumber']).'")';
								if($this->conn->query($sql)) $dwcArr['omenid'] = $this->conn->insert_id;
							}
						}
						if($dwcArr['omenid']){
							//ometid and omenid both exists, thus load Exsiccati
							$sqlExs ='INSERT INTO omexsiccatiocclink(omenid,occid) VALUES('.$dwcArr['omenid'].','.$this->occid.')';
							if($this->conn->query($sqlExs)){
								//Remove exsiccati fields from $leftOverData
								unset($leftOverData['ometid']);
								unset($leftOverData['omenid']);
								if(isset($leftOverData['exsnumber'])) unset($leftOverData['exsnumber']);
								if(isset($leftOverData['exsiccatinumber'])) unset($leftOverData['exsiccatinumber']);
								if(isset($leftOverData['exsiccatititle'])) unset($leftOverData['exsiccatititle']);
							}
							else{
								$this->logMsg("ERROR linking exsiccati record (".$dwcArr['omenid'].'-'.$this->occid."): ".$this->conn->error);
							}
						}
					}
				}
			}
			else{
				$this->logMsg('ERROR: unable to load data; '.$this->conn->error);
				return false;
			}
		}

		if(count($leftOverData)) $this->logMsg('WARNING: Unmatched data fields: '.implode(', ',array_keys($leftOverData)));

		return true;
	}

	private function logMsg($str){
		if($this->logErrors){
			if(!$this->logFH){
				$this->logFH = fopen($this->outFilePath.'.log', 'a');
				$this->logMsg('Starting batch processing ('.date('Y-m-d h:i:s A').')');
			}
			if($this->logFH){
				fwrite($this->logFH,$str."\n");
			}
			else{
				echo $str."\n";
			}
		}
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

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>