<?php 
/**
 *  Base class for managers.  Supplies $conn for connection, $id for primary key, and 
 *  $errorMessage/getErrorMessage(), along with supporting clean methods cleanOutStr()
 *  cleanInStr() and cleanInArray();
 */

include_once($serverRoot.'/config/dbconnection.php');

class Manager  {
	protected $conn = null;
	protected $id = null;
    protected $errorMessage = '';
    protected $warningArr = array();

	protected $logFH;
	protected $verboseMode = 0;
	
    public function __construct($id=null,$conType='readonly'){
 		$this->conn = MySQLiConnectionFactory::getCon($conType);
 		if($id != null || is_numeric($id)){
	 		$this->id = $id;
 		}
	}

 	public function __destruct(){
 		if(!($this->conn === null)) $this->conn->close();
		if($this->logFH){
			fclose($this->logFH);
		}
	}

	protected function setLogFH($logPath){
		$this->logFH = fopen($logPath, 'a');
	}

	protected function logOrEcho($str, $indexLevel=0){
		//verboseMode: 0 = silent, 1 = log, 2 = out to screen, 3 = both
		if($this->verboseMode){
			if($this->verboseMode == 3 || $this->verboseMode == 1){
				if($this->logFH){
					fwrite($this->logFH,$str);
				} 
			}
			if($this->verboseMode == 3 || $this->verboseMode == 2){
				echo '<li style="'.($indexLevel?'margin-left:'.($indexLevel*15).'px':'').'">'.$str.'</li>';
				ob_flush();
				flush();
			}
		}
	}

	public function setVerboseMode($c){
		$this->verboseMode = $c;
	}

	public function getVerboseMode(){
		return $this->verboseMode;
	}

	public function getErrorMessage() { 
		return $this->errorMessage;
	}

   public function getWarningArr(){
		return $this->warningArr;
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

	protected function cleanInArray($arr){
		$newArray = Array();
		foreach($arr as $key => $value){
			$newArray[$this->cleanInStr($key)] = $this->cleanInStr($value);
		}
		return $newArray;
	}

	protected function encodeString($inStr){
		global $charset;
		$retStr = $inStr;
		//Get rid of curly (smart) quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);
		//Get rid of UTF-8 curly smart quotes and dashes 
		$badwordchars=array("\xe2\x80\x98", // left single quote
							"\xe2\x80\x99", // right single quote
							"\xe2\x80\x9c", // left double quote
							"\xe2\x80\x9d", // right double quote
							"\xe2\x80\x94", // em dash
							"\xe2\x80\xa6" // elipses
		);
		$fixedwordchars=array("'", "'", '"', '"', '-', '...');
		$inStr = str_replace($badwordchars, $fixedwordchars, $inStr);
		
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
			//$line = iconv('macintosh', 'UTF-8', $line);
			//mb_detect_encoding($buffer, 'windows-1251, macroman, UTF-8');
 		}
		return $retStr;
	}
	
   /** To enable mysqli_stmt->bind_param using call_user_func_array($array) 
     * allow $array to be converted to array of by references 
     * if php version requires it. 
     */
   public static function correctReferences($array) { 
    if (strnatcmp(phpversion(),'5.3') >= 0) {
       $byrefs = array();
       foreach($array as $key => $value)
          $byrefs[$key] = &$array[$key];
       return $byrefs;
    }
    return $byrefs;
   }
}
?>