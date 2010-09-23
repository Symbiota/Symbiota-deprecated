<?php

class SpecimenDigirUpload extends SpecimenUploadManager {
	
	//Search variables
	private $searchStart = 0;
	private $searchLimit = 1000;
	//private $defaultSchema = "http://digir.sourceforge.net/schema/conceptual/darwin/brief/2003/1.0/darwin2brief.xsd";
	private $defaultSchema = "";
	private $returnCount = true;
	
	//XML parser stuff
	private $withinRecordElement = false;
	private $activeFieldName = "";
	private $activeFieldValue = "";
	
	//MySQL database stuff
	private $fieldDataArr = Array();
	private $symbTargetFields = Array();
	private $dbpkSequence = 0;
	
	//
	private $nibbleGoodChars;
	private $byteMap = array();
	
 	public function __construct(){
 		parent::__construct();
 		$defaultSchema = $GLOBALS["clientRoot"]."/collections/admin/util/darwinsymbiota.xsd";
 		set_time_limit(10000);
 		//
		$this->initByteMap();
		$ascii_char='[\x00-\x7F]';
		$cont_byte='[\x80-\xBF]';
		$utf8_2='[\xC0-\xDF]'.$cont_byte;
		$utf8_3='[\xE0-\xEF]'.$cont_byte.'{2}';
		$utf8_4='[\xF0-\xF7]'.$cont_byte.'{3}';
		$utf8_5='[\xF8-\xFB]'.$cont_byte.'{4}';
		$this->nibbleGoodChars = "@^($ascii_char+|$utf8_2|$utf8_3|$utf8_4|$utf8_5)(.*)$@s";
 	}
	
 	public function uploadData($finalTransfer){
	 	$this->readUploadParameters();
 		if($this->schemaName){
			if(substr($this->schemaName,0,4) != "http"){
				$this->schemaName = "http://".$_SERVER["HTTP_HOST"].substr($_SERVER["PHP_SELF"],0,strrpos($_SERVER["PHP_SELF"],"/"))."/".$this->schemaName;
			}
		}
		else{
			$this->schemaName = $this->defaultSchema;
		}
 		//Delete all records in uploadspectemp table
		$sqlDel = "DELETE FROM uploadspectemp";
		//$this->conn->query($sqlDel);
 		
		$alphabet = array ("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
		//$alphabet = array ("M");
		foreach($alphabet as $alphaChar){
			echo "<li style='font-weight:bold;'>Starting ".$alphaChar."%%</li>\n";
			$this->searchStart = 0;
			$this->submitReq($alphaChar."%%");
		}
		$this->finalUploadSteps($finalTransfer);
 	}

	private function submitReq($searchStr){

		$digirEof = false;
		$recordCount = 0;
		$recordReturn = 0;
		//$outFile = "C:\\temp\\outFile.xml";
		//$fh = fopen($outFile, 'w') or die("can't open file");
		
		do{
			$fp = fsockopen($this->server, $this->port, $errno, $errstr, 30);
			if(!$fp){
				echo "<div style='margin-left:10px;font-weight:bold;color:red;'>ERROR: $errstr ($errno)</div>\n";
			} else {
				$poststring = "GET ".$this->digirPath."?doc=".urlencode("<request ".
					"xmlns='http://digir.net/schema/protocol/2003/1.0' ".
					"xmlns:xsd='http://www.w3.org/2001/XMLSchema' ".
					"xmlns:darwin='http://digir.net/schema/conceptual/darwin/2003/1.0' ".
					"xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ".
					"xsi:schemaLocation='http://digir.net/schema/protocol/2003/1.0 http://digir.sourceforge.net/schema/protocol/2003/1.0/digir.xsd http://digir.net/schema/conceptual/darwin/2003/1.0 http://digir.sourceforge.net/schema/conceptual/darwin/2003/1.0/darwin2.xsd'>".
					"<header>".
					"<version>1.0</version>".
					"<sendTime>".date(DATE_ISO8601)."</sendTime>".
					"<source>".$_SERVER['SERVER_ADDR']."</source>".
					"<destination resource='".$this->digirCode."'>".$this->server."</destination>".
					"<type>search</type>".
					"</header><search><filter>");
				$poststring .= urlencode(trim(str_replace("--TAXON--",$searchStr,$this->queryStr)));
				$poststring .= urlencode("</filter>".
					"<records limit='".$this->searchLimit."' start='".$this->searchStart."'>".
					"<structure schemaLocation='".$this->schemaName."'/>".
					"</records>".
					"<count>".($this->returnCount?"true":"false")."</count>".
					"</search></request>");
				//echo urldecode($poststring)."\n";
			    $poststring .= " HTTP/1.0\r\nHost: ".$this->server."\r\n";
			    $poststring .= "Connection: Close\r\n\r\n";
				//fputs($fp,"Content-type: application/x-www-form- urlencoded\r\n");
				//fputs($fp, "Content-length: " . strlen($data) . "\r\n");
			    fwrite($fp, $poststring);
			    //$doc = "";
			    $line = "";
			    $contentPassed = false;
			    $headerPassed = false;
				$diagnosticStr = "";
				$xml_parser = xml_parser_create();
				xml_set_element_handler($xml_parser, array(&$this,"startElement"), array(&$this,"endElement"));
				xml_set_character_data_handler($xml_parser, array(&$this,"characterData"));
				while(!feof($fp)){
					$line = fgets($fp);
					//echo "line: ".$line;
					if($headerPassed){
						$line = $this->cleanString($line);
				        //fwrite($fh, $line);
						//$doc .= $line;
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
			        else{
			        	if($line == "\r\n"){
							$headerPassed = true;
			        	}
			        }
			    }
			    xml_parser_free($xml_parser);
				//$this->processXmlDom($doc);
			    
				//Process $diagnosticStr
				$diagnosticStr = substr($diagnosticStr,0,strpos($diagnosticStr,"</response>"));
				if($diagnosticStr){
					$xmlStr = $diagnosticStr;
					//echo $xmlStr;
					$xml = new SimpleXMLElement($xmlStr);
					foreach ($xml->diagnostic as $diag) {
						switch((string) $diag['code']) { 
							case 'MATCH_COUNT':
								$recordCount = (int)$diag;
								break;
							case 'END_OF_RECORDS':
								$digirEof = ($diag == "true"?true:false);
								break;
							case 'RECORD_COUNT':
								$recordReturn += (int)$diag;
								break;
						}
					}
				}
			    fclose($fp);
			}
			echo "<li style='font-weight:bold;'>Records Returned: ".$recordReturn." of ".$recordCount."</li>";
			$this->searchStart += $this->searchLimit;
			flush();
			//sleep(3);
		} while ($recordCount > $recordReturn && !$digirEof);
		//fclose($fh);
	}
	
	private function startElement($parser, $name, $attribs){
		if($name == "RECORD") $this->withinRecordElement = true;
		if($this->withinRecordElement){
			if(substr($name,0,7) == "DARWIN:") $name = substr($name,7);
			$this->activeFieldName = trim($name);
		}
	}

	private function endElement($parser, $name) {
		if($name == "RECORD"){
			//End of record, load record into database
			//print_r($this->fieldDataArr);
			$this->withinRecordElement = false;
			$this->databaseRecord();
			unset($this->fieldDataArr);
		}
		if($this->withinRecordElement && $this->activeFieldName && $this->activeFieldValue){
			switch($this->activeFieldName){
				case "DATELASTMODIFIED":
					$datetime = strtotime($this->activeFieldValue);
					$this->fieldDataArr["MODIFIED"] = date('Y-m-d H:i:s',$datetime);
					break;
				case "EARLIESTDATECOLLECTED":
					$datetime = strtotime($this->activeFieldValue);
					$this->fieldDataArr["EVENTDATE"] = date('Y-m-d',$datetime);
					$this->fieldDataArr["YEAR"] = date('Y',$datetime);
					$this->fieldDataArr["MONTH"] = date('m',$datetime);
					$this->fieldDataArr["DAY"] = date('d',$datetime);
					$this->fieldDataArr["STARTDAYOFYEAR"] = date('z',$datetime);
					break;
				case "LATESTDATECOLLECTED":
					$datetime = strtotime($this->activeFieldValue);
					$this->fieldDataArr["ENDDAYOFYEAR"] = date('z',$datetime);
					break;
				case "VERBATIMCOLLECTINGDATE":
					$this->fieldDataArr["VERBATIMEVENTDATE"] = $this->activeFieldValue;
					$datetime = strtotime($this->activeFieldValue);
					if($datetime) $this->fieldDataArr["EVENTDATE"] = date('Y-m-d H:i:s',$datetime);
					break;
				case "CATALOGNUMBERTEXT":
					$this->fieldDataArr["CATALOGNUMBER"] = $this->activeFieldValue; 
					break;
				case "SPECIES":
					$this->fieldDataArr["SPECIFICEPITHET"] = $this->activeFieldValue;
					break;
				case "SUBSPECIES":
					$this->fieldDataArr["INFRASPECIFICEPITHET"] = $this->activeFieldValue;
					break;
				case "SCIENTIFICNAMEAUTHOR":
					$this->fieldDataArr["SCIENTIFICNAMEAUTHORSHIP"] = $this->activeFieldValue;
					break;
				case "IDENTIFICATIONMODIFIER":
					$this->fieldDataArr["IDENTIFICATIONQUALIFIER"] = $this->activeFieldValue;
					break;
				case "LONGITUDE":
					$this->fieldDataArr["DECIMALLONGITUDE"] = $this->activeFieldValue;
					break;
				case "LATITUDE":
					$this->fieldDataArr["DECIMALLATITUDE"] = $this->activeFieldValue;
					break;
				case "HORIZONTALDATUM":
					$this->fieldDataArr["GEODETICDATUM"] = $this->activeFieldValue;
					break;
				case "ORIGINALCOORDINATESYSTEM":
					$this->fieldDataArr["VERBATIMCOORDINATESYSTEM"] = $this->activeFieldValue;
					break;
				case "GEOREFMETHOD":
					$this->fieldDataArr["GEOREFERENCEPROTOCOL"] = $this->activeFieldValue;
					break;
				case "COORDINATEPRECISION":
					$this->fieldDataArr["COORDINATEUNCERTAINTYINMETERS"] = $this->activeFieldValue;
					break;
				case "MINIMUMELEVATION":
					$this->fieldDataArr["MINIMUMELEVATIONINMETERS"] = (int) $this->activeFieldValue;
					break;
				case "MAXIMUMELEVATION":
					$this->fieldDataArr["MAXIMUMELEVATIONINMETERS"] = (int) $this->activeFieldValue;
					break;
				case "MINIMUMELEVATIONINMETERS":
					$this->fieldDataArr["MINIMUMELEVATIONINMETERS"] = (int) $this->activeFieldValue;
					break;
				case "MAXIMUMELEVATIONINMETERS":
					$this->fieldDataArr["MAXIMUMELEVATIONINMETERS"] = (int) $this->activeFieldValue;
					break;
				case "NOTES":
					$this->fieldDataArr["OCCURRANCEREMARKS"] = $this->activeFieldValue;
					break;
				default:
					$this->fieldDataArr[$this->activeFieldName] = $this->activeFieldValue;
			}
		}
		$this->activeFieldValue = "";
		$this->activeFieldName = "";
	}

	private function characterData($parser, $data){
		if($this->withinRecordElement) $this->activeFieldValue .= utf8_decode(trim($data));
	}

	private function databaseRecord(){
		if(array_key_exists("SCIENTIFICNAME",$this->fieldDataArr) || array_key_exists("SCINAME",$this->fieldDataArr)){
			if(array_key_exists("SCIENTIFICNAME",$this->fieldDataArr) || !array_key_exists("SCINAME",$this->fieldDataArr)){
				$this->fieldDataArr["SCINAME"] = $this->fieldDataArr["SCIENTIFICNAME"];
			}
			if(!array_key_exists("SCIENTIFICNAME",$this->fieldDataArr) || array_key_exists("SCINAME",$this->fieldDataArr)){
				$this->fieldDataArr["SCIENTIFICNAME"] = $this->fieldDataArr["SCINAME"]." ".(array_key_exists("SCIENTIFICNAMEAUTHORSHIP",$this->fieldDataArr)?$this->fieldDataArr["SCIENTIFICNAMEAUTHORSHIP"]:"");
			}
			if(array_key_exists("YEAR",$this->fieldDataArr) && array_key_exists("MONTH",$this->fieldDataArr) && array_key_exists("DAY",$this->fieldDataArr) && !array_key_exists("EVENTDATE",$this->fieldDataArr)){
				$datetime = strtotime($this->fieldDataArr["YEAR"]."-".$this->fieldDataArr["MONTH"]."-".$this->fieldDataArr["DAY"]);
				if($datetime) $this->fieldDataArr["EVENTDATE"] = date('Y-m-d',$datetime);
			}
			if($this->digirPKField){
				$this->fieldDataArr["DBPK"] = $this->fieldDataArr[strtoupper($this->digirPKField)];
			}
			else{
				$this->fieldDataArr["DBPK"] = ++$this->dbpkSequence;
			}
			$this->fieldDataArr["COLLID"] = $this->collId;
			//$this->fieldDataArr["ULID"] = $this->collId;
			$sqlInsertFrag = "";
			$sqlValuesFrag = "";
			foreach($this->fieldDataArr as $fieldName => $fieldValue){
				if(array_key_exists(strtolower($fieldName),$this->fieldMap)){
					$sqlInsertFrag .= ",".$fieldName;
					$sqlValuesFrag .= "\",\"".str_replace(chr(34),"'",$fieldValue);
				}
			}
			$sql = "INSERT INTO uploadspectemp (".substr($sqlInsertFrag,1).") VALUES (\"".substr($sqlValuesFrag,3)."\")";
			//echo "<div>SQL: ".$sql."</div>";
			if(!$this->conn->query($sql)){
				echo "<div style='margin-left:10px;font-weight:bold;color:red;'>ERROR LOADING RECORD: ".$this->conn->error."</div>";
				echo "<div style='margin-left:10px;'>SQL: ".$sql."</div>";
				//$textFile = "C:\\temp\\TransferErrors.txt";
				//$fh = fopen($textFile, 'a') or die("can't open file");
				//fwrite($fh, "<div>ERROR LOADING RECORD: ".$this->con->error."</div>");
				//fwrite($fh, "<div>SQL: ".$sql."</div>");
				//fclose($fh);
			}
		}
	}
	
	private function processXmlDom($doc){
		$dom = new DOMDocument();
		$dom->loadXML( $doc );
		$recordList = $dom->getElementsByTagName("record");
		//$recCnt = 0;
		foreach($recordList as $recordNode){
			$childNodes = $recordNode->childNodes;
			//++$recCnt;
			//echo "<h1>Record ".$recCnt."</h1>";
			foreach($childNodes as $fieldNode){
				$fieldType = $fieldNode->nodeType;
				if($fieldType == 1){
					$fieldName = $fieldNode->nodeName;
					$fieldValue = $fieldNode->nodeValue;
					$this->fieldDataArr[$fieldName] = $fieldValue;
					//if($fieldValue) echo "<div>".$fieldName.": ".$fieldValue."</div>";
				}
			}
		}
	}
	
	private function initByteMap(){
		for($x=128;$x<256;++$x){
			$this->byteMap[chr($x)]=utf8_encode(chr($x));
		}
		$cp1252Map=array(
			"\x80"=>"\xE2\x82\xAC",    // EURO SIGN
			"\x82" => "\xE2\x80\x9A",  // SINGLE LOW-9 QUOTATION MARK
			"\x83" => "\xC6\x92",      // LATIN SMALL LETTER F WITH HOOK
			"\x84" => "\xE2\x80\x9E",  // DOUBLE LOW-9 QUOTATION MARK
			"\x85" => "\xE2\x80\xA6",  // HORIZONTAL ELLIPSIS
			"\x86" => "\xE2\x80\xA0",  // DAGGER
			"\x87" => "\xE2\x80\xA1",  // DOUBLE DAGGER
			"\x88" => "\xCB\x86",      // MODIFIER LETTER CIRCUMFLEX ACCENT
			"\x89" => "\xE2\x80\xB0",  // PER MILLE SIGN
			"\x8A" => "\xC5\xA0",      // LATIN CAPITAL LETTER S WITH CARON
			"\x8B" => "\xE2\x80\xB9",  // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
			"\x8C" => "\xC5\x92",      // LATIN CAPITAL LIGATURE OE
			"\x8E" => "\xC5\xBD",      // LATIN CAPITAL LETTER Z WITH CARON
			"\x91" => "\xE2\x80\x98",  // LEFT SINGLE QUOTATION MARK
			"\x92" => "\xE2\x80\x99",  // RIGHT SINGLE QUOTATION MARK
			"\x93" => "\xE2\x80\x9C",  // LEFT DOUBLE QUOTATION MARK
			"\x94" => "\xE2\x80\x9D",  // RIGHT DOUBLE QUOTATION MARK
			"\x95" => "\xE2\x80\xA2",  // BULLET
			"\x96" => "\xE2\x80\x93",  // EN DASH
			"\x97" => "\xE2\x80\x94",  // EM DASH
			"\x98" => "\xCB\x9C",      // SMALL TILDE
			"\x99" => "\xE2\x84\xA2",  // TRADE MARK SIGN
			"\x9A" => "\xC5\xA1",      // LATIN SMALL LETTER S WITH CARON
			"\x9B" => "\xE2\x80\xBA",  // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
			"\x9C" => "\xC5\x93",      // LATIN SMALL LIGATURE OE
			"\x9E" => "\xC5\xBE",      // LATIN SMALL LETTER Z WITH CARON
			"\x9F" => "\xC5\xB8"       // LATIN CAPITAL LETTER Y WITH DIAERESIS
		);
		foreach($cp1252Map as $k=>$v){
			$this->byteMap[$k]=$v;
		}
	}

	private function fixLatin($instr){
		if(mb_check_encoding($instr,'UTF-8'))return $instr; // no need for the rest if it's all valid UTF-8 already
  		$outstr='';
		$char='';
		$rest='';
		while((strlen($instr))>0){
			if(1==preg_match($this->nibbleGoodChars,$instr,$match)){
				$char=$match[1];
				$rest=$match[2];
				$outstr.=$char;
			}elseif(1==preg_match('@^(.)(.*)$@s',$instr,$match)){
				$char=$match[1];
				$rest=$match[2];
				$outstr.=$this->byteMap[$char];
			}
			$instr=$rest;
		}
		return $outstr;
	}
}
?>