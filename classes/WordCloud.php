<?php
include_once($serverRoot.'/config/dbconnection.php');

class WordCloud{
	
	private $conn;
	private $frequencyArr = array();
	private $commonWordArr = array(); 
	
	//custom parameters
	private $displayedWordCount;
	private $tagUrl; 
	private $backgroundImage;
	private $backgroundColor;
	private $cloudWidth;
	private $wordColors;
	private $supportUtf8 = true;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		
		$this->displayedWordCount = 100;
		if($GLOBALS['charset'] == 'ISO-8859-1') $this->supportUtf8 = false;
		$this->tagUrl = "http://www.google.com/search?hl=en&q=";
		
		$this->backgroundColor = "#000";
		$this->wordColors[0] = "#5122CC";
		$this->wordColors[1] = "#229926";
		$this->wordColors[2] = "#330099";
		$this->wordColors[3] = "#819922";
		$this->wordColors[4] = "#22CCC3";
		$this->wordColors[5] = "#99008D";
		$this->wordColors[6] = "#943131";
		$this->wordColors[7] = "#B23B3B";
		$this->wordColors[8] = "#229938";
		$this->wordColors[9] = "#419922";
		
		$commonWordStr = "a,able,about,across,after,all,almost,also,am,among,an,and,any,are,arent," .
			"as,at,be,because,been,but,by,can,cant,cannot,could,couldve,couldnt,dear,did,didnt,do,does,doesnt," .
			"dont,either,else,ever,every,for,from,get,got,had,has,hasnt,have,he,her,him,his,how,however," .
			"i,if,in,into,is,isnt,it,its,just,least,let,like,likely,may,me,might,most,must,my,neither,no,nor,not,of,off," .
			"often,on,only,or,other,our,own,rather,said,say,says,she,should,since,so,some,than,that," .
			"the,their,them,then,there,theres,these,they,this,to,too,us,wants,was,wasnt,we,were,werent,what," .
			"when,when,where,which,while,who,whom,why,will,with,wont,would,wouldve,wouldnt,yet,you,your";
		//$commonWordStr = strtolower($commonWordStr);
		$this->commonWordArr = explode(",", $commonWordStr);
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function buildWordFile($collectionId = 0,$csMode = 0){
		$collArr = array();
		$sqlFrag = 'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'INNER JOIN specprocessorrawlabels r ON i.imgid = r.imgid ';
		if($csMode){
			$sqlFrag .= 'INNER JOIN omcrowdsourcequeue q ON o.occid = q.occid '; 
		}
		$sqlColl = 'SELECT DISTINCT c.collid, c.collectionname '.$sqlFrag.
			'INNER JOIN omcollections c ON c.collid = o.collid ';
		if($collectionId){
			$sqlColl .= 'WHERE c.collid = '.$collectionId;
		}
		//echo 'sql: '.$sqlColl;
		$rsColl = $this->conn->query($sqlColl);
		while($rColl = $rsColl->fetch_object()){
			$collArr[$rColl->collid] = $rColl->collectionname;
		}
		$rsColl->free();

		$sql = 'SELECT DISTINCT r.rawstr '.$sqlFrag.
			'WHERE o.processingstatus = "unprocessed" AND o.locality IS NULL ';
		foreach($collArr as $collid => $collName){
			//Reset frequency array
			unset($this->frequencyArr);
			$this->frequencyArr = array();
			//Process all raw OCR strings for collection
			$sql .= 'AND o.collid = '.$collid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->addTagsFromText($r->rawstr);
			}
			$rs->free();
			//Get Word cloud
			$cloudStr = $this->getWordCloud();
			echo $cloudStr.'<br/><br/>';
			//Write word out to text file
			$wcPath = $GLOBALS['serverRoot'];
			if(substr($wcPath,-1) != '/' && substr($wcPath,-1) != "\\") $wcPath .= '/'; 
			$wcPath .= 'temp/wordclouds/ocrcloud'.$collid.'.html';
			if(file_exists($wcPath)){
				$wcFH = fopen($wcPath, 'a');
			    if(!$wcFH = fopen($wcPath, 'a')) {
			         echo "Cannot open file ($wcPath)";
			         exit;
			    }
			    if(fwrite($wcFH, $cloudStr) === FALSE) {
			        echo "Cannot write to file ($wcPath)";
			        exit;
			    }
			    fclose($handle);
			}
			else{
				echo 'ERROR trying to write word cloud to temp folder: '.$wcPath;
				echo '<br/>Is the symbiota temp folder writable to Apache?';
			}
		}
	}

	public function addTagsFromText($seedText){
		//$text = strtolower($seedText);
		//$text = strip_tags($text);

		/* remove punctuation and newlines */
		if ($this->supportUtf8){
			$seedText = preg_replace('/[^\p{L}0-9\s]|\n|\r/u',' ',$seedText);
		}
		else{
			$seedText = preg_replace('/[^a-zA-Z0-9\s]|\n|\r/',' ',$seedText);
		}

		/* remove extra spaces created */
		$seedText = preg_replace('/\s+/',' ',$seedText);
		$seedText = trim($seedText);

		//Remove common words 
		$wordArr = array_diff(explode(" ", $seedText),$this->commonWordArr);
		
		foreach ($wordArr as $key => $value){
			$this->addTag($value);
		}
	}

	public function addTag($tag, $useCount = 1){
		//$tag = strtolower($tag);
		if (array_key_exists($tag, $this->frequencyArr)){
			$this->frequencyArr[$tag] += $useCount;
		}
		else{
			$this->frequencyArr[$tag] = $useCount;
		}
	}

	public function getWordCloud(){
		$retStr = '<div id="id_tag_cloud" style="' . (isset($this->cloudWidth) ? ("width:". $this->cloudWidth. ";") : "") .
			'line-height:normal"><div style="border-style:solid;border-width:1px;' .
			(isset($this->backgroundImage) ? ("background:url('". $this->backgroundImage ."');") : "") .
			'border-color:#888;margin-top:20px;margin-bottom:10px;padding:5px 5px 20px 5px;background-color:'.$this->backgroundColor.';">';
		if($this->frequencyArr){
			arsort($this->frequencyArr);
			$topTags = array_slice($this->frequencyArr, 0, $this->displayedWordCount);
			
			/* randomize the order of elements */
			uasort($topTags, 'randomSort');
			
			$maxCount = max($this->frequencyArr);
			foreach ($topTags as $tag => $useCount){
				$grade = $this->gradeFrequency(($useCount * 100) / $maxCount);
				$retStr .= ('<a href="'. $this->tagUrl.urlencode($tag).'" title="More info on '.
					$tag.'" style="color:'.$this->wordColors[$grade].';">'.
					'<span style="color:'.$this->wordColors[$grade].'; letter-spacing:3px; '.
					'padding:4px; font-family:Tahoma; font-weight:900; font-size:'. 
					(0.6 + 0.1 * $grade).'em">'.$tag.'</span></a> ');
			}
			$retStr .= '</div></div><br />';
		}
		return $retStr;
	}
	
	private function gradeFrequency($frequency){
		$grade = 0;
		if ($frequency >= 90)
			$grade = 9;
		else if ($frequency >= 70)
			$grade = 8;
		else if ($frequency >= 60)
			$grade = 7;
		else if ($frequency >= 50)
			$grade = 6;
		else if ($frequency >= 40)
			$grade = 5;
		else if ($frequency >= 30)
			$grade = 4;
		else if ($frequency >= 20)
			$grade = 3;
		else if ($frequency >= 10)
			$grade = 2;
		else if ($frequency >= 5)
			$grade = 1;
		 
		return $grade;
	}
	
	//Setters and getters
	public function setDisplayedWordCount($cnt){
		$this->displayedWordCount = $cnt;
	}
	
	public function setSearchURL($searchURL){
		$this->tagUrl = $searchURL;
	}

	public function setUTF8($bUTF8){
		$this->supportUtf8 = $bUTF8;
	}

	public function setWidth($width){
		$this->cloudWidth = $width;
	}

	public function setBackgroundImage($backgroundImage){
		$this->backgroundImage = $backgroundImage;
	}

	public function setBackgroundColor($backgroundColor){
		$this->backgroundColor = $backgroundColor;
	}

	public function setTextColors($colors){
		if(is_array($colors)){
			$this->wordColors = $colors;
		}
	}
}

/* array sort helper function */
function randomSort($a, $b)
{
	return rand(-1, 1);
}
?>
