<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class SalixUtilities {

	private $conn;
	private $verbose = 1;
	private $recCnt = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function buildWordStats($reset = 1){
		//Reset wordstats table for that collection
		if($this->verbose) echo '<ul>';
		if($reset){
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
		//Build word stats
		$limit = 50000;
		$statsArr = array();
		$fieldArr = array('l' => 'locality', 'h' => 'habitat', 's' => 'substrate', 'v' => 'verbatimAttributes', 'o' => 'occurrenceRemarks');
		$totalCnt = 0;
		$previousWordCnt = 0;
		foreach($fieldArr as $k => $field){
			$this->echoStr('Starting to collect Words for <b>'.$field.'</b>');
			ob_flush();
			flush();
			$recCnt = 0;
			$sql = 'SELECT distinct '.$field.' AS f '.
				'FROM omoccurrences '.
				'WHERE '.$field.' IS NOT NULL '.
				'LIMIT '.$limit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->countWords($statsArr, $k, $r->f);
				if($recCnt%($limit/10) == 0){
					$this->echoStr('Count: '.$recCnt,1);
					ob_flush();
					flush();
				}
				$recCnt++;
			}
			$rs->free();
			$this->echoStr('End record cnt: '.$recCnt,1);
			$this->echoStr('Word cnt: '.(count($statsArr) - $previousWordCnt),1);
			$previousWordCnt = count($statsArr);
			ob_flush();
			flush();
		}
		//Load stats into table
		$this->echoStr('Final word cnt: '.count($statsArr));
		$this->echoStr('Loading data');
		$this->loadStats($statsArr);
		$this->echoStr('Done!');
		if($this->verbose) echo '</ul>';
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
						if(isset($statsArr[$cleanWord][$tag])) $firstCnt = $statsArr[$cleanWord][$tag];
						$statsArr[$cleanWord][$tag] = ++$firstCnt;
					}
					if($prevWord){
						$secondCnt = 0;
						if(isset($statsArr[$prevWord][$cleanWord][$tag])) $secondCnt = $statsArr[$prevWord][$cleanWord][$tag];
						$statsArr[$prevWord][$cleanWord][$tag] = ++$secondCnt;
					}
				}
				$prevWord = $cleanWord;
			}
		}
	}

	private function loadStats($inArr,$firstWordIn = ''){
		$firstWord = ''; $secondWord = '';
		if($firstWordIn) $firstWord = $this->cleanInStr($firstWordIn);
		foreach($inArr as $word => $subArr){
			if($firstWordIn){
				$secondWord = $this->cleanInStr($word);
			}
			else{
				$firstWord = $this->cleanInStr($word);
			}
			if(strlen($firstWord) < 46 && strlen($secondWord) < 46){
				$locCnt = (isset($subArr['l'])?$subArr['l']:0);
				$habCnt = (isset($subArr['h'])?$subArr['h']:0);
				$subCnt = (isset($subArr['s'])?$subArr['s']:0);
				$attCnt = (isset($subArr['v'])?$subArr['v']:0);
				$remCnt = (isset($subArr['o'])?$subArr['o']:0);
				$cnt = $locCnt + $habCnt + $subCnt + $attCnt + $remCnt;
				if($cnt){
					$locPer = round($locCnt/$cnt,2)*100;
					$habPer = round($habCnt/$cnt,2)*100;
					$subPer = round($subCnt/$cnt,2)*100;
					$attPer = round($attCnt/$cnt,2)*100;
					$remPer = round($remCnt/$cnt,2)*100;
					$sql = 'INSERT INTO salixwordstats(firstword,secondword,locality,localityFreq,habitat,habitatFreq,substrate,substrateFreq,verbatimAttributes,verbatimAttributesFreq,occurrenceRemarks,occurrenceRemarksFreq,totalcount) '.
						'VALUES("'.$firstWord.'",'.($secondWord?'"'.$secondWord.'"':'NULL').','.
						$locCnt.','.$locPer.','.$habCnt.','.$habPer.','.$subCnt.','.$subPer.','.$attCnt.','.$attPer.','.$remCnt.','.$remPer.','.$cnt.')';
					if($this->conn->query($sql)){
						if($this->recCnt%10000 == 0){
							$this->echoStr('Count: '.$this->recCnt,1);
							ob_flush();
							flush();
						}
						$this->recCnt++;
					}
					else{
						//$this->echoStr('ERROR loading word ('.$firstWord.' - '.$secondWord.'): '.$this->conn->error);
						//echo $sql;
					}
				}
				unset($subArr['l']);
				unset($subArr['h']);
				unset($subArr['s']);
				unset($subArr['v']);
				unset($subArr['o']);
				if(!$secondWord) $this->loadStats($subArr,$firstWord);
			}
		}
	}
	
	public function cleanWord($w){
		if(preg_match('/\d+/',$w)) return '';
		$w = trim($w,' ,;().');
		if(strlen($w) < 2) return '';
		return strtolower($w);
	}
	
	//Setters and getters
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