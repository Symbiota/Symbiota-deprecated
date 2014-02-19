<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class SalixUtilities {

	private $conn;
	private $verbose = 1;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function buildWordStats($reset = 1){
		$collArr = array();
		$sqlColl = 'SELECT collid, CONCAT(collectionname,CONCAT_WS(" - ",institutioncode, collectioncode)) as collname '.
			'FROM omcollections '.
			'WHERE colltype = "Preserved Specimens" and collid = 1 ';
		$rsColl = $this->conn->query($sqlColl);
		while($rColl = $rsColl->fetch_object()){
			$collArr[$rColl->collid] = $rColl->collname;
		}
		$rsColl->free();
		
		//Build word stats
		foreach($collArr as $collid => $collName){
			$this->echoStr('Starting to build word stats for: '.$collName);
			ob_flush();
			flush();
			
			if($reset){
				if($this->conn->query('DELETE FROM salixwordstats WHERE collid = '.$collid)){
					$this->echoStr('Deleting old word stats');
				}
				else{
					$this->echoStr('ERROR deleting old word stats: '.$this->conn->error);
				}
			}
			$this->echoStr('Starting to collect Words');
			ob_flush();
			flush();
			$statsArr = array();
			$recCnt = 0;
			$sql = 'SELECT locality, habitat, substrate, verbatimAttributes, occurrenceRemarks '.
				'FROM omoccurrences '.
				'WHERE locality IS NOT NULL AND collid = '.$collid.' LIMIT 10000';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->countWords($statsArr, 'loc', $r->locality);
				$this->countWords($statsArr, 'hab', $r->habitat);
				$this->countWords($statsArr, 'sub', $r->substrate);
				$this->countWords($statsArr, 'att', $r->verbatimAttributes);
				$this->countWords($statsArr, 'rem', $r->occurrenceRemarks);
				if($recCnt%1000 == 0){
					$this->echoStr('Record cnt: '.$recCnt);
					ob_flush();
					flush();
				}
				$recCnt++;
			}
			$rs->free();
			$this->echoStr('Finished collecting words');
			$this->echoStr('Total record cnt: '.$recCnt);
			$this->echoStr('Total word cnt: '.count($statsArr));
			ob_flush();
			flush();
			//Load stats into table
			$this->loadStats($collid,$statsArr);
		}
	}

	private function countWords(&$statsArr, $tag, $inStr){
		$inStr = str_replace(';',',',$inStr);
		$fragmentArr = explode(',',$inStr);
		foreach($fragmentArr as $str){
			$wordArr = explode(' ',$str);
			$prevWord = '';
			foreach($wordArr as $word){
				$cleanWord = $this->cleanWord($word);
				if($cleanWord){
					if(strlen($cleanWord) > 2){
						$firstCnt = 0;
						if(isset($statsArr[$cleanWord][$tag.'cnt'])) $firstCnt = $statsArr[$cleanWord][$tag.'cnt'];
						$statsArr[$cleanWord][$tag.'cnt'] = ++$firstCnt;
					}
					if($prevWord){
						$secondCnt = 0;
						if(isset($statsArr[$prevWord][$cleanWord][$tag.'cnt'])) $secondCnt = $statsArr[$prevWord][$cleanWord][$tag.'cnt'];
						$statsArr[$prevWord][$cleanWord][$tag.'cnt'] = ++$secondCnt;
					}
				}
				$prevWord = $cleanWord;
			}
		}
	}
	
	private function loadStats($collid,$inArr,$firstWordIn = ''){
		$firstWord = ''; $secondWord = '';
		if($firstWordIn) $firstWord = $firstWordIn;
		foreach($inArr as $word => $subArr){
			if($firstWordIn){
				$secondWord = $word;
			}
			else{
				$firstWord = $word;
			}
			$locCnt = (isset($subArr['loccnt'])?$subArr['loccnt']:0);
			$habCnt = (isset($subArr['habcnt'])?$subArr['habcnt']:0);
			$subCnt = (isset($subArr['subcnt'])?$subArr['subcnt']:0);
			$attCnt = (isset($subArr['attcnt'])?$subArr['attcnt']:0);
			$remCnt = (isset($subArr['remcnt'])?$subArr['remcnt']:0);
			$cnt = $locCnt + $habCnt + $subCnt + $attCnt + $remCnt;
			if($cnt){
				$locPer = round($locCnt/$cnt,2);
				$habPer = round($habCnt/$cnt,2);
				$subPer = round($subCnt/$cnt,2);
				$attPer = round($attCnt/$cnt,2);
				$remPer = round($remCnt/$cnt,2);
				$sql = 'INSERT IGNORE INTO salixwordstats(collid,firstword,secondword,locality,localityFreq,habitat,habitatFreq,substrate,substrateFreq,verbatimAttributes,verbatimAttributesFreq,occurrenceRemarks,occurrenceRemarksFreq,totalcount) '.
					'VALUES('.$collid.',"'.$this->cleanInStr($firstWord).'",'.($secondWord?'"'.$this->cleanInStr($secondWord).'"':'NULL').','.
					$locCnt.','.$locPer.','.$habCnt.','.$habPer.','.$subCnt.','.$subPer.','.$attCnt.','.$attPer.','.$remCnt.','.$remPer.','.$cnt.')';
				if(!$this->conn->query($sql)){
					echo 'ERROR loading word: '.$this->conn->error;
					echo $sql;
					exit;
				}
			}
			unset($subArr['loccnt']);
			unset($subArr['habcnt']);
			unset($subArr['subcnt']);
			unset($subArr['attcnt']);
			unset($subArr['remcnt']);
			if($subArr) $this->loadStats($collid,$subArr,$firstWord);
		}
	}
	
	public function cleanWord($w){
		if(preg_match('/\d+/',$w)) return '';
		$w = trim($w,' ,;().');
		if(strlen($w) < 2) return '';
		return $w;
	}
	
	//Setters and getters
	public function setVerbose($v){
		$this->verbose = $v;
	}
	
	//misc fucntions 
	private function echoStr($str){
		if($this->verbose){
			echo '<li>'.$str."</li>\n";
		}
	}
	
	private function cleanOutStr($str){
		$str = str_replace('"',"&quot;",$str);
		$str = str_replace("'","&apos;",$str);
		return $str;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>