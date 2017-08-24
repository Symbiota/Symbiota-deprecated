<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class SalixUtilities {

	private $conn;
	private	$fieldArr = array('locality', 'habitat', 'substrate', 'verbatimAttributes', 'occurrenceRemarks');
	private $verbose = 1;
	private $lastBuildTimestamp;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function buildWordStats($collid,$actionType,$limit){
		//Reset wordstats table for that collection
		if($this->verbose) echo '<ul>';
		$lts = '';
		if($actionType == 1 || $actionType == 2){
			if($this->conn->query('DELETE FROM salixwordstats')){
				$this->conn->query('OPTIMIZE TABLE salixwordstats');
				$this->echoStr('Deleted old word stats');
			}
			else{
				$this->echoStr('ERROR deleting old word stats: '.$this->conn->error);
			}
			ob_flush();
			flush();
		}
		elseif($actionType == 3){
			$lts = $this->getLastBuildTimestamp();
		}
		//Build word stats
		$statsArr = array();
		$totalCnt = 0;
		foreach($this->fieldArr as $field){
			$this->echoStr('Starting to collect Words for <b>'.$field.'</b>');
			ob_flush();
			flush();
			$recCnt = 0;
			$sql = 'SELECT DISTINCT '.$field.' AS f '.
				'FROM omoccurrences '.
				'WHERE '.$field.' IS NOT NULL ';
			if($actionType == 1){
				$sql .= 'ORDER BY rand() ';
			}
			elseif($actionType == 2){
				$sql .= 'ORDER BY occid DESC ';
			}
			elseif($actionType == 3){
				if($lts){
					$sql .= 'AND dateentered > "'.$lts.'" ';
				}
			}
			if($limit){
				$sql .= 'LIMIT '.$limit;
			}
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->countWords($statsArr, $r->f);
				if($recCnt && $recCnt%($limit/10) == 0){
					$this->echoStr('Running count: '.$recCnt,1);
					ob_flush();
					flush();
				}
				if($recCnt && $recCnt%(20000) == 0){
					//$this->echoStr('Loading...',2);
					//ob_flush();
					//flush();
					$this->loadStats($statsArr,$field);
					unset($statsArr);
					$statsArr = array();
					//$this->echoStr(' Loaded, continuing to harvest',3);
					//ob_flush();
					//flush();
				}
				$recCnt++;
			}
			$rs->free();
			$this->loadStats($statsArr,$field);
			$this->echoStr('End record cnt: '.$recCnt,1);
			$this->echoStr('Loading final data for '.$field,1);
			ob_flush();
			flush();
			unset($statsArr);
			$statsArr = array();
		}
		$this->computeFrequencies();
		$this->echoStr('Harvesting word stats complete');
		if($this->verbose) echo '</ul>';
	}

	private function countWords(&$statsArr, $inStr){
		$inStr = str_replace(array(';',','),' ',$inStr);
		$inStr = preg_replace('/\s\s+/', ' ',$inStr);
		$wordArr = explode(' ',$inStr);
		$prevWord = '';
		foreach($wordArr as $word){
			$cleanWord = $this->cleanWord($word);
			if($cleanWord){
				if(strlen($cleanWord) > 2){
					$firstCnt = 0;
					if(isset($statsArr[$cleanWord])) $firstCnt = $statsArr[$cleanWord][''];
					$statsArr[$cleanWord][''] = ++$firstCnt;
				}
				if($prevWord){
					$secondCnt = 0;
					if(isset($statsArr[$prevWord][$cleanWord])) $secondCnt = $statsArr[$prevWord][$cleanWord];
					$statsArr[$prevWord][$cleanWord] = ++$secondCnt;
				}
			}
			$prevWord = $cleanWord;
		}
	}

	private function loadStats($inArr,$fieldName){
		$this->conn->query('SET autocommit=0');
		$this->conn->query('SET unique_checks=0');
		$this->conn->query('SET foreign_key_checks=0');
		foreach($inArr as $fWord => $subArr){
			$firstWord = $this->cleanInStr($fWord);
			foreach($subArr as $sWord => $cnt){
				$secondWord = $this->cleanInStr($sWord);
				$sql = 'SELECT '.$fieldName.' AS cnt FROM salixwordstats '.
					'WHERE firstword = "'.$firstWord.'" AND secondword '.($secondWord?'= "'.$secondWord.'"':'IS NULL');
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$newCnt = $r->cnt + $cnt;
					$sql1 = 'UPDATE salixwordstats '.
						'SET '.$fieldName.' = '.$newCnt.' '.
						'WHERE firstword = "'.$firstWord.'" AND secondword '.($secondWord?'= "'.$secondWord.'"':'IS NULL');
					if(!$this->conn->query($sql1)){
						$this->echoStr('ERROR updating record: '.$this->conn->error,1);
						echo $sql1.'<br/>';
						ob_flush();
						flush();
					}
				}
				else{
					$sql2 = 'INSERT INTO salixwordstats(firstword,secondword,'.$fieldName.') '.
						'VALUES("'.$firstWord.'",'.($secondWord?'"'.$secondWord.'"':'NULL').','.$cnt.')';
					if(!$this->conn->query($sql2)){
						$this->echoStr('ERROR inserting record: '.$this->conn->error,1);
						echo $sql2.'<br/>';
						ob_flush();
						flush();
					}
				}
				$rs->free();
			}
		}
		$this->conn->query('COMMIT');
		$this->conn->query('SET autocommit=1');
		$this->conn->query('SET unique_checks=1');
		$this->conn->query('SET foreign_key_checks=1');
	}

	private function computeFrequencies(){
		$this->echoStr('Updating stats... '.$this->conn->error);
		$sql = 'UPDATE salixwordstats '.
			'SET totalcount = (locality + habitat + substrate + verbatimAttributes + occurrenceRemarks)';
		if(!$this->conn->query($sql)){
			$this->echoStr('ERROR updating totalcount value: '.$this->conn->error,1);
			echo $sql.'<br/>';
			ob_flush();
			flush();
		}
		//Update field frequencies
		foreach($this->fieldArr as $field){
			$sql1 = 'UPDATE salixwordstats '.
				'SET '.$field.'freq = ('.$field.' * 100 / totalcount)';
			if(!$this->conn->query($sql1)){
				$this->echoStr('ERROR updating '.$field.' frequency: '.$this->conn->error,1);
				echo $sql1.'<br/>';
				ob_flush();
				flush();
			}
		}
		$this->echoStr('Done!'.$this->conn->error,1);
	}
	
	public function cleanWord($w){
		$w = strtolower(trim($w));
		$w = trim($w,'-');
		if(preg_match('/[^\-a-z]+/',$w)) return '';
		if(strlen($w) > 45) return '';
		return $w;
	}
	
	//Setters and getters
	public function getLastBuildTimestamp(){
		if(!$this->lastBuildTimestamp){
			$this->setLastBuildTimestamp();
		}
		return $this->lastBuildTimestamp;
	}

	private function setLastBuildTimestamp(){
		$sql = 'SELECT max(initialtimestamp) as maxts '.
			'FROM salixwordstats';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->lastBuildTimestamp = $r->maxts;
		}
		$rs->free();
	}

	public function setVerbose($v){
		$this->verbose = $v;
	}

	private function echoStr($str, $indent = 0){
		if($this->verbose){
			echo '<li style="margin-left:'.($indent*15).'px">'.$str."</li>\n";
			ob_flush();
			flush();
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