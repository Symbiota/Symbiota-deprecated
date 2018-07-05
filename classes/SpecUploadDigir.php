<?php
include_once($SERVER_ROOT.'/classes/SpecUploadBase.php');
class SpecUploadDigir extends SpecUploadBase {

	//Search variables
	private $searchStart = 0;
	private $searchLimit = 1000;
	private $defaultSchema = "";	//http://digir.sourceforge.net/schema/conceptual/darwin/brief/2003/1.0/darwin2brief.xsd
	private $recCount = 0;
	
	//XML parser stuff
	private $withinRecordElement = false;
	private $activeFieldName = "";
	private $activeFieldValue = "";

	//MySQL database stuff
	private $fieldDataArr = Array();
	private $symbTargetFields = Array();
	private $dbpkSequence = 0;

 	public function __construct(){
 		parent::__construct();
 		$this->defaultSchema = $GLOBALS["clientRoot"]."/collections/admin/darwinsymbiota.xsd";
 		set_time_limit(10000);
 	}

	public function __destruct(){
 		parent::__destruct();
	}
 	
	public function uploadData($finalTransfer){
		$this->prepUploadData();
		
		if($this->schemaName){
			if(substr($this->schemaName,0,4) != "http"){
				$this->schemaName = "http://".$_SERVER["HTTP_HOST"].substr($_SERVER["PHP_SELF"],0,strrpos($_SERVER["PHP_SELF"],"/"))."/".$this->schemaName;
			}
		}
		else{
			$this->schemaName = $this->defaultSchema;
		}
 		//Delete all records in uploadspectemp table
		$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
		$this->conn->query($sqlDel);
 		
		echo "<li style='font-weight:bold;'>Starting record harvest</li>\n";
		$this->submitReq();
		$this->cleanUpload();
		if($finalTransfer){
			$this->transferOccurrences();
			$this->finalCleanup();
		}
 	}

	private function submitReq(){

		$digirEof = false;
		$matchCount = 0;
		$zeroReturnCnt = 0;
		$qStr = "<like><darwin:collectioncode>%%%</darwin:collectioncode></like>";
		if($this->queryStr){
			$qStr = trim($this->queryStr);
		}
		
		do{
			$url = (stripos($this->server,"http://")!==false?"":"http://").$this->server.$this->path."?doc=".urlencode("<request ".
				"xmlns='http://digir.net/schema/protocol/2003/1.0' ".
				"xmlns:xsd='http://www.w3.org/2001/XMLSchema' ".
				"xmlns:darwin='http://digir.net/schema/conceptual/darwin/2003/1.0' ".
				"xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ".
				"xsi:schemaLocation='http://digir.net/schema/protocol/2003/1.0 http://digir.sourceforge.net/schema/protocol/2003/1.0/digir.xsd http://digir.net/schema/conceptual/darwin/2003/1.0 http://digir.sourceforge.net/schema/conceptual/darwin/2003/1.0/darwin2.xsd'>".
				"<header>".
				"<version>1.0</version>".
				"<sendTime>".date(DATE_ISO8601)."</sendTime>".
				"<source>".$_SERVER['SERVER_ADDR']."</source>".
				"<destination resource='".$this->code."'>".$this->server."</destination>".
				"<type>search</type>".
				"</header><search><filter>");
			$url .= urlencode($qStr);
			$url .= urlencode("</filter>".
				"<records limit='".$this->searchLimit."' start='".$this->searchStart."'>".
				"<structure schemaLocation='".$this->schemaName."'/>".
				"</records>".
				"<count>true</count>".
				"</search></request>");
			//echo "\n".$url."\n";
			$fp = fopen($url, 'rb');
			if(!$fp){
				echo "<div style='margin-left:10px;font-weight:bold;color:red;'>ERROR: Unable to retrieve data</div>\n";
				echo "<div style='margin-left:10px;font-weight:bold;color:red;'>SQL: ".$url."</div>\n";
			} else {
			    $line = "";
			    $contentPassed = false;
				$diagnosticStr = "";
				$xml_parser = xml_parser_create();
				xml_set_element_handler($xml_parser, array(&$this,"startElement"), array(&$this,"endElement"));
				xml_set_character_data_handler($xml_parser, array(&$this,"characterData"));
				while(!feof($fp)){
					$line = fgets($fp);
					//echo "line: ".$line."\n";
					$line = $this->cleanXmlStr($line);
					if (!xml_parse($xml_parser, $line, feof($fp))){
						echo "<div style='font-weight:bold;color:red;'>";
						echo "XML error: %s at line %d".xml_error_string(xml_get_error_code($xml_parser)).xml_get_current_line_number($xml_parser);
						echo "</div>";
						echo "<div style='margin-left:10px;'>".$line."</div>";
						break;
					}
		        	if($contentPassed){
			        	$diagnosticStr .= $line;
			        }
					elseif(strpos($line,"<diagnostics>") !== false){
			        	$contentPassed = true;
			        	$diagnosticStr = substr($line,strpos($line,"<diagnostics>"));
					}
			    }
			    xml_parser_free($xml_parser);
			    
				//Process $diagnosticStr
				$diagnosticStr = substr($diagnosticStr,0,strpos($diagnosticStr,"</response>"));
				if($diagnosticStr){
					$xmlStr = $diagnosticStr;
					//echo $xmlStr;
					$xml = new SimpleXMLElement($xmlStr);
					foreach ($xml->diagnostic as $diag) {
						switch((string) $diag['code']) { 
							case 'MATCH_COUNT':
								$matchCount = (int)$diag;
								break;
							case 'END_OF_RECORDS':
								$digirEof = ($diag == "true"?true:false);
								break;
							case 'RECORD_COUNT':
								$currentReturn = (int)$diag;
								break;
						}
					}
				}
			    fclose($fp);
			}
			echo "<li style='font-weight:bold;'>Records Returned: ".$this->transferCount." of ".$matchCount." (".($this->recCount-$this->transferCount)." failed)</li>";
			$this->searchStart += $this->searchLimit;
			ob_flush();
			flush();
			if($currentReturn){
				$zeroReturnCnt = 0;
			}
			else{
				$zeroReturnCnt++;
			}
			if($zeroReturnCnt > 5){
				echo '<li>Download stopped prematurely due to stall in record return</li>';
				break;
			}
			//sleep(3);
		} while (!$digirEof);
	}
	
	private function startElement($parser, $name, $attrs){
		if($name == "RECORD"){
			$this->withinRecordElement = true;
			$this->recCount++;
		}
		if($this->withinRecordElement){
			if($p = strpos($name,":")){
				$name = substr($name,$p+1);
				//echo "<div>".$name."</div>";
			}
			$this->activeFieldName = $name;
		}
	}

	private function endElement($parser, $name) {
		if($name == "RECORD"){
			//End of record, load record into database
			//print_r($this->fieldDataArr);
			$this->withinRecordElement = false;
			$this->loadRecord(array_change_key_case($this->fieldDataArr));
			unset($this->fieldDataArr);
			$this->fieldDataArr = Array();
		}
		elseif($this->withinRecordElement && $this->activeFieldName && $this->activeFieldValue){
			if($this->activeFieldName == "GLOBALUNIQUEIDENTIFIER" && !array_key_exists("OCCURRENCEID",$this->fieldDataArr)){
				$this->fieldDataArr["OCCURRENCEID"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "DATELASTMODIFIED" && !array_key_exists("MODIFIED",$this->fieldDataArr)){
				$datetime = strtotime($this->activeFieldValue);
				$this->fieldDataArr["MODIFIED"] = date('Y-m-d H:i:s',$datetime);
			}
			elseif($this->activeFieldName == "COLLECTOR" && !array_key_exists("RECORDEDBY",$this->fieldDataArr)){
				$this->fieldDataArr["RECORDEDBY"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "COLLECTORNUMBER" && !array_key_exists("RECORDNUMBER",$this->fieldDataArr)){
				$this->fieldDataArr["RECORDNUMBER"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "YEARCOLLECTED" && !array_key_exists("YEAR",$this->fieldDataArr)){
				$this->fieldDataArr["YEAR"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "MONTHCOLLECTED" && !array_key_exists("MONTH",$this->fieldDataArr)){
				$this->fieldDataArr["MONTH"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "DAYCOLLECTED" && !array_key_exists("DAY",$this->fieldDataArr)){
				$this->fieldDataArr["DAY"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "EARLIESTDATECOLLECTED" && !array_key_exists("EVENTDATE",$this->fieldDataArr)){
				$datetime = strtotime($this->activeFieldValue);
				if($datetime){
					$this->fieldDataArr["EVENTDATE"] = date('Y-m-d',$datetime);
					$this->fieldDataArr["YEAR"] = date('Y',$datetime);
					$this->fieldDataArr["MONTH"] = date('m',$datetime);
					$this->fieldDataArr["DAY"] = date('d',$datetime);
					$this->fieldDataArr["STARTDAYOFYEAR"] = date('z',$datetime);
				}
			}
			elseif($this->activeFieldName == "LATESTDATECOLLECTED" && !array_key_exists("ENDDAYOFYEAR",$this->fieldDataArr)){
				$datetime = strtotime($this->activeFieldValue);
				$this->fieldDataArr["ENDDAYOFYEAR"] = date('z',$datetime);
			}
			elseif($this->activeFieldName == "VERBATIMCOLLECTINGDATE" || $this->activeFieldName == "VERBATIMEVENTDATE"){
				if(!array_key_exists("VERBATIMEVENTDATE",$this->fieldDataArr)){
					$this->fieldDataArr["VERBATIMEVENTDATE"] = $this->activeFieldValue;
				}
				if(!array_key_exists("EVENTDATE",$this->fieldDataArr)){
					$datetime = strtotime($this->activeFieldValue);
					if($datetime) $this->fieldDataArr["EVENTDATE"] = date('Y-m-d H:i:s',$datetime);
				}
			}
			elseif(($this->activeFieldName == "CATALOGNUMBERTEXT" || $this->activeFieldName == "CATALOGNUMBERNUMERIC") && !array_key_exists("CATALOGNUMBER",$this->fieldDataArr)){
				$this->fieldDataArr["CATALOGNUMBER"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "SPECIES" && !array_key_exists("SPECIFICEPITHET",$this->fieldDataArr)){
				$this->fieldDataArr["SPECIFICEPITHET"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "INFRASPECIFICRANK" && !array_key_exists("TAXONRANK",$this->fieldDataArr)){
				$this->fieldDataArr["TAXONRANK"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "SUBSPECIES" && !array_key_exists("INFRASPECIFICEPITHET",$this->fieldDataArr)){
				$this->fieldDataArr["INFRASPECIFICEPITHET"] = $this->activeFieldValue;
			}
			elseif(($this->activeFieldName == "SCIENTIFICNAMEAUTHOR" || $this->activeFieldName == "AUTHORYEAROFSCIENTIFICNAME") && !array_key_exists("SCIENTIFICNAMEAUTHORSHIP",$this->fieldDataArr)){
				$this->fieldDataArr["SCIENTIFICNAMEAUTHORSHIP"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "IDENTIFICATIONMODIFIER" && !array_key_exists("IDENTIFICATIONQUALIFIER",$this->fieldDataArr)){
				$this->fieldDataArr["IDENTIFICATIONQUALIFIER"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "LATITUDE" && !array_key_exists("DECIMALLATITUDE",$this->fieldDataArr)){
				$this->fieldDataArr["DECIMALLATITUDE"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "LONGITUDE" && !array_key_exists("DECIMALLONGITUDE",$this->fieldDataArr)){
				$this->fieldDataArr["DECIMALLONGITUDE"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "HORIZONTALDATUM" && !array_key_exists("GEODETICDATUM",$this->fieldDataArr)){
				$this->fieldDataArr["GEODETICDATUM"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "ORIGINALCOORDINATESYSTEM" && !array_key_exists("VERBATIMCOORDINATESYSTEM",$this->fieldDataArr)){
				$this->fieldDataArr["VERBATIMCOORDINATESYSTEM"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "GEOREFMETHOD" && !array_key_exists("GEOREFERENCEPROTOCOL",$this->fieldDataArr)){
				$this->fieldDataArr["GEOREFERENCEPROTOCOL"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "COORDINATEPRECISION" && !array_key_exists("COORDINATEUNCERTAINTYINMETERS",$this->fieldDataArr)){
				$this->fieldDataArr["COORDINATEUNCERTAINTYINMETERS"] = $this->activeFieldValue;
			}
			elseif(($this->activeFieldName == "MINIMUMELEVATION" || $this->activeFieldName == "MINIMUMELEVATIONINMETERS") && !array_key_exists("MINIMUMELEVATIONINMETERS",$this->fieldDataArr) && $this->activeFieldValue != ''){
				$this->fieldDataArr["MINIMUMELEVATIONINMETERS"] = (int) $this->activeFieldValue;
			}
			elseif(($this->activeFieldName == "MAXIMUMELEVATION" || $this->activeFieldName == "MAXIMUMELEVATIONINMETERS") && !array_key_exists("MAXIMUMELEVATIONINMETERS",$this->fieldDataArr) && $this->activeFieldValue != ''){
				$this->fieldDataArr["MAXIMUMELEVATIONINMETERS"] = (int) $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "NOTES" && !array_key_exists("OCCURRANCEREMARKS",$this->fieldDataArr)){
				$this->fieldDataArr["OCCURRANCEREMARKS"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "INSTITUTIONCODE" && strtolower($this->activeFieldValue) != strtolower($this->getCollInfo("institutioncode"))){
				$this->fieldDataArr["INSTITUTIONCODE"] = $this->activeFieldValue;
			}
			elseif($this->activeFieldName == "COLLECTIONCODE" && strtolower($this->activeFieldValue) != strtolower($this->getCollInfo("collectioncode"))){
				$this->fieldDataArr["COLLECTIONCODE"] = $this->activeFieldValue;
			}
			else{
				if(!array_key_exists($this->activeFieldName,$this->fieldDataArr)){
					$this->fieldDataArr[$this->activeFieldName] = $this->activeFieldValue;
				}
			}
		}
		$this->activeFieldValue = "";
		$this->activeFieldName = "";
	}

	private function characterData($parser, $data){
		$value = $this->cleanInStr($this->encodeString($data));
		if($this->withinRecordElement && $value != ""){
			$this->activeFieldValue .= $value;
		}
	}
	
	public function setSearchStart($start){
		$this->searchStart = $start;
	}

	public function getSearchStart(){
		return $this->searchStart;
	}

	public function setSearchLimit($limit){
		$this->searchLimit = $limit;
	}
	
	public function getSearchLimit(){
		return $this->searchLimit;
	}
	
	private function cleanXmlStr($inStr){
		$retStr = $inStr;
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		return $retStr;
	}
}
?>