<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$dataLoader = new DataLoader();

$analyzeSubmit = (array_key_exists("analyzesubmit",$_REQUEST)?1:0);
$obserSubmit = (array_key_exists("obsersubmit",$_REQUEST)?1:0);
$useDefaults = (array_key_exists("defaults",$_REQUEST)?1:0);
$collId = (array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:"");
$replaceRecs = (array_key_exists("replacerecs",$_REQUEST)?1:0);

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}
?>
<html>
<head>
	<title>Observation data loader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<script type="text/javascript">
	
	function validateForm(thisForm){
		var testStr = document.getElementById("uploadfile").value;
		if(testStr == ""){
			alert("Please select a file to upload");
			return false;
		}
		testStr = testStr.toLowerCase();
		if(testStr.indexOf(".txt") == -1){
			alert("Document "+document.getElementById("uploadfile").value+" must be a text file (with a .txt extension)");
			return false;
		}
		return true;
	}
	
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_admin_observationuploaderMenu)?$collections_admin_observationuploaderMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($collections_admin_observationuploaderCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_admin_observationuploaderCrumbs;
		echo " <b>Observations Batch Loader</b>"; 
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h2>Observation Data File Upload</h2>
	    <?php
		if($editable){
	    	if($analyzeSubmit){
				$reqFieldStr = $dataLoader->analyzeFile();
				echo "<hr/>";
				echo "<div style='margin:20px;'>";
				echo "<div style='font-weight:bold;font-size:120%;text-decoration:underline'>Results of Analysis</div>";
				if($reqFieldStr){
					echo "<div style='font-weight:bold;margin:10px;'>";
					echo "<span style='color:red;'>Following required fields must included:</span> ".$reqFieldStr;
					echo "</div>";
				}
				else{
					echo "<div style='font-weight:bold;margin:10px;'>Passed Required Fields Test</div>";
					echo "<div style='margin:10px;'><div style='font-weight:bold;'>Fields that will be uploaded:</div>";
					echo "<ul>";
					echo "<li>".implode("</li><li>",$dataLoader->getFieldsToBeLoaded())."</li>";
					echo "</ul></div>";
					echo "<div style='margin:10px;'><div style='font-weight:bold;'>Fields that will not be uploaded:</div>";
					echo "<ul>";
					echo "<li>".implode("</li><li>",$dataLoader->getFieldsToBeSkipped())."</li>";
					echo "</ul></div>";
				}
				echo "</div>";
				echo "<hr/>";
			}
			elseif($obserSubmit){
				echo "<div>".$dataLoader->loadData($collId,$useDefaults,$replaceRecs)."</div>";
			}
			?>
			<div style="margin:20px;">
				<form id="obserform" name="obserform" enctype="multipart/form-data" action="observationuploader.php" method="post" onsubmit="return validateForm(this);">
					<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
					<div style="font-weight:bold;">
						File: 
						<input id="uploadfile" name="uploadfile" type="file" size="45" />
					</div>
					<div>
						<input type="checkbox" name="defaults" value="1" <?php if($useDefaults) echo "CHECKED"; ?>> 
						Use first line as default values 
					</div>
					<div style="">
						<input id="analyzesubmit" name="analyzesubmit" type="submit" value="Analyze File" />
					</div>
					<?php if($analyzeSubmit && !$reqFieldStr){ ?>
						<div>
							<select name="collid">
								<?php $dataLoader->echoCollectionIdSelect(); ?>
							</select>
						</div>
						<div>
							Replace Matching Records <input type="checkbox" name="replacerecs" value="1" title="Reload Existing Records" />
						</div>
						<div style="">
							<input id="obsersubmit" name="obsersubmit" type="submit" value="Upload File" />
						</div>
					<?php } ?>
				</form>
			</div>
		<?php 
		}
		else{
			echo "<div>You must be logged in and authorized to view this page. Please login.</div>";
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
	
</body>
</html>
<?php 
class DataLoader{

	private $conn;
	private $columnMax = 35;
	private $fieldsToBeLoaded = Array();
	private $fieldsToBeSkipped = Array();
	
	//SourceField => ObservationField
	private $fieldMapArr = Array(
		//"occurrenceid" => "gui",
		"identificationQualifier" => "taxonnotes",
		"dbpk" => "gui",
		"stateprovince" => "state",
		"county" => "municipiocounty",
		"decimallatitude" => "declat",
		"decimallongitude" => "declon",
		"minimumelevationinmeters" => "elevation_meters",
		"recordedby" => "observer",
		"recordnumber" => "collnumber",
		"associatedCollectors" => "associates",
		"eventdate" => "dateobserved",
		"verbatimeventdate" => "verbatimdate",
		"fieldnotes" => "vegetation",
		"occurrenceremarks" => "notes",
		"attributes" => "description",
		"disposition" => "vouchers"
	);
	private $requiredArr = Array(		//Enter target values 
		"sciname",
		"locality",
		"recordedby",
		"eventdate",
		"decimallatitude",
		"decimallongitude"
	);

	private function setConnection() {
 		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	private function setFieldMap() {
    	//Get obsertaion metadata
    	$fieldArr = Array();
		$sql = "SHOW COLUMNS FROM omoccurrences";
		$rs = $this->conn->query($sql);
    	while($row = $rs->fetch_object()){
    		$field = strtolower($row->Field);
    		if($field != "currenttimestamp"){
	    		$type = $row->Type;
	    		$fieldArr[$field]["source"] = $field; 
				if(strpos($type,"double") !== false || strpos($type,"int") !== false){
					$fieldArr[$field]["type"] = "numeric";
				}
				elseif(strpos($type,"date") !== false){
					$fieldArr[$field]["type"] = "date";
				}
				else{
					$fieldArr[$field]["type"] = "string";
					if(preg_match('/\(\d+\)$/', $type, $matches)){
						$fieldArr[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
					}
				}
    		}
    	}
    	
    	//Merge two arrays
    	foreach($this->fieldMapArr as $targetField => $sourceField){
    		$sf = strtolower($sourceField);
    		$tf = strtolower(trim($targetField));
    		if(array_key_exists($tf,$fieldArr)){
    			$fieldArr[$tf]["source"] = $sf;
    		}
    	}
    	unset($this->fieldMapArr);
    	$this->fieldMapArr = $fieldArr;
	}
	
	public function analyzeFile(){
		$this->setConnection();
		$this->setFieldMap();
		//Just read first line of file to report what fields while be loaded, ignored, and required fulfilled
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'r') or die("Can't open file");
		$headerData = fgets($fh);
		$headerArr = explode("\t",$headerData);
		$sourceArr = Array();
		$colCnt = 0;
		foreach($headerArr as $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr) $sourceArr[] = $fieldStr;
			$colCnt++;
			if($colCnt > $this->columnMax) break;
		}
		
		//Check to see if required fields are included
		$requiredFaultStr = "";
		foreach($this->requiredArr as $v){
			$reqField = strtolower($v);
			if(!in_array($this->fieldMapArr[$reqField]["source"],$sourceArr)){
				$requiredFaultStr .= ",".$reqField;
			}
		}
		if($requiredFaultStr) $requiredFaultStr = substr($requiredFaultStr,1);
		
		//Check to see which files will be loaded or not
		foreach($this->fieldMapArr as $specField => $detailArr){
			$sf = $detailArr["source"];
			if(in_array($sf,$sourceArr)){
				$this->fieldsToBeLoaded[] = $sf;
				unset($sourceArr[array_search($sf,$sourceArr)]);
			}
		}
		$this->fieldsToBeSkipped = $sourceArr;

		$this->conn->close();
		return $requiredFaultStr;
	}

	public function loadData($collId, $useDefaults, $replaceRecs){
		$this->setConnection();
		$this->setFieldMap();

		set_time_limit(500);
		ini_set("max_input_time",240);
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'r') or die("Can't open file");
		$headerData = fgets($fh);
		$headerArr = explode("\t",$headerData);
		foreach($headerArr as $k => $hv){
			$headerArr[$k] = strtolower(trim($hv));
		}
		
		$colCnt = 0;
		$sourceArr = Array();
		$targetArr = Array();
		$tempSourceArr = Array();
		//Set sourceArr (all header fields within fieldMapArr)
		foreach($this->fieldMapArr as $spf => $detailArr){
			$sf = $detailArr["source"];
			if(in_array($sf,$headerArr)){
				$sourceArr[$spf] = $sf;
			}
		}
		//Set $sqlBase values (all specimen field names that are mapped to headerArr) 
		foreach($headerArr as $k => $fieldStr){
			$kArr = array_keys($sourceArr,$fieldStr);
			foreach($kArr as $v){
				$targetArr[] = $v;
			}
			$colCnt++;
			if($colCnt > $this->columnMax) break;
		}
		$sqlBase = "";
		if($replaceRecs){
			$sqlBase .= "REPLACE ";
		}
		else{
			$sqlBase .= "INSERT ";
		}
		
		$sqlBase .= "INTO omoccurrences(collid,".implode(",",$targetArr).") ";
		
		$defaultArr = Array();
		$recordCnt = 1;
		$reqFieldsNullCnt = 0;
		while($record = fgets($fh)){
			$recordArr = explode("\t",$record);
			if(!$recordArr[array_search($this->fieldMapArr["sciname"]["source"],$headerArr)]){
				echo "<div>End of upload: Last record reached or a record didn't have a scientific name</div>";
				break;
			}
			
			//Following code for van Devender data: Convert latdeg,latmin,latsec => latdec; londeg,lonmin,lonsec => londec
			if(in_array("deglat",$headerArr) && $recordArr[array_search("deglat",$headerArr)] && in_array("declat",$headerArr) && !$recordArr[array_search("declat",$headerArr)]){
				$latDeg = $recordArr[array_search("deglat",$headerArr)];
				$latMin = $recordArr[array_search("minlat",$headerArr)];
				$latSec = $recordArr[array_search("seclat",$headerArr)];
				$lonDeg = $recordArr[array_search("deglon",$headerArr)];
				$lonMin = $recordArr[array_search("minlon",$headerArr)];
				$lonSec = $recordArr[array_search("seclon",$headerArr)];
				$latDec = round(($latDeg?$latDeg:0) + (($latMin?$latMin:0)/60) + (($latSec?$latSec:0)/3660),5);
				$lonDec = round(($lonDeg?$lonDeg:0) + (($lonMin?$lonMin:0)/60) + (($lonSec?$lonSec:0)/3660),5);
				if($lonDec > 0) $lonDec *= -1;
				if($latDec || $lonDec){
					$recordArr[array_search("declat",$headerArr)] = $latDec;
					$recordArr[array_search("declon",$headerArr)] = $lonDec;
				}
				if($useDefaults && $recordCnt == 1){
					$defaultArr[array_search("declat",$headerArr)] = $latDec;
					$defaultArr[array_search("declon",$headerArr)] = $lonDec;
				}
			}
			if($recordArr[array_search("declon",$headerArr)] > 0){
				$recordArr[array_search("declon",$headerArr)] = -1*$recordArr[array_search("declon",$headerArr)]; 
			}
			
			//If a required field is null, abort upload of record
			$loadRecord = true;
			foreach($this->requiredArr as $spField){
				$reqKey = array_search($this->fieldMapArr[$spField]["source"],$headerArr);
				$reqValue = trim($recordArr[$reqKey]);
				if(!$reqValue && $useDefaults && $recordCnt > 1 && $defaultArr){
					$reqValue = $defaultArr[$reqKey];
				}
				if(!$reqValue){
					//A required field has a null value
					echo "<div>Adding observation #".$recordCnt.": FAILED - Required field '".$reqKey."' has a null value</div>";
					$loadRecord = false;
					$reqFieldsNullCnt++;
					if($reqFieldsNullCnt > 4){
						echo "<div>Aborting upload: too many records with blank values for require fields </div>";
						break 2;
					}
					break;
				}
				//$reqFieldsNullCnt = 0;
			}
			
			if($loadRecord){
				$sqlValues = "";
				for($x=0;$x<count($headerArr);$x++){
					if($spKeys = array_keys($sourceArr,$headerArr[$x])){
						foreach($spKeys as $specName){
							$valueStr = trim($recordArr[$x]);
							if(substr($valueStr,0,1) == "\"" && substr($valueStr,-1) == "\"") $valueStr = substr($valueStr,1,strlen($valueStr)-2);
							$valueStr = str_replace("\"","''",$valueStr);
							if($useDefaults){
								if($recordCnt == 1){
									if(!array_key_exists($x,$defaultArr)) $defaultArr[$x] = $valueStr;
								}
								else{
									echo $specName;
									if(!$valueStr && $specName != "family"){
										$valueStr = $defaultArr[$x];
									}
								}
							}
							//Load data
							$type = $this->fieldMapArr[$specName]["type"];
							switch($type){
								case "numeric":
									if(!$valueStr) $valueStr = "\N";
									$sqlValues .= ",".$valueStr;
									break;
								case "date":
									if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $valueStr)){
										$sqlValues .= ',"'.$valueStr.'"';
										echo 'Date: '.$valueStr;
									}
									elseif(($dateStr = strtotime($valueStr))){
										$sqlValues .= ',"'.date('Y-m-d H:i:s', $dateStr).'"';
									}
									else{
										$sqlValues .= ',NULL';
									}
									break;
								default:	//string
									$sqlValues .= ",\"".$valueStr."\"";
									
							}
						}
					}
				}
				$sql = $sqlBase."VALUES(".$collId.",".substr($sqlValues,1).")";
				//echo "<div>".$recordCnt.": ".$sql."</div>";
				
				$status = $this->conn->query($sql);
				if($status){
					echo "<div style='margin-bottom:10px;'>";
					if($replaceRecs){
						echo "Replacing ";
					}
					else{
						echo "Adding ";
					}
					echo "observation #".$recordCnt.": SUCCESS";
					echo "</div>";
				}
				else{
					echo "<div>Adding observation #".$recordCnt.": FAILED</div>";
					echo "<div style='margin-left:10px;'>Error: ".$this->conn->error."</div>";
					echo "<div style='margin:0px 0px 10px 10px;'>SQL: $sql</div>";
				}
			}
			$recordCnt++;
		}
		fclose($fh);
		//Update taxa links and families values
		//$sql1 = 'UPDATE omoccurrences s INNER JOIN taxa t ON s.sciname = t.sciname SET s.TidInterpreted = t.tid '.
		//	'WHERE s.TidInterpreted IS NULL';
		//$this->conn->query($sql1);
		$sql2 = 'UPDATE omoccurrences u INNER JOIN taxstatus ts ON u.tidinterpreted = ts.tid '.
			'SET u.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "")';
		$this->conn->query($sql2);
		$sql3 = 'UPDATE omoccurrences u INNER JOIN taxa t ON u.genus = t.unitname1 '.
			'INNER JOIN taxstatus ts on t.tid = ts.tid '.
			'SET u.family = ts.family '.
			'WHERE t.rankid = 180 and ts.taxauthid = 1 AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "")';
		$this->conn->query($sql3);
		/*$sql4 = 'UPDATE taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'SET o.scientificnameauthorship = t.author '.
			'WHERE t.author is not null and (o.scientificnameauthorship IS NULL or o.scientificnameauthorship = "")';
		$this->conn->query($sql4); */
		
		$this->conn->query("Call UpdateCollectionStats(".$collId.");");
		$this->conn->close();
	}
	
	public function echoCollectionIdSelect(){
		$this->setConnection();
		$sql = "SELECT c.collid, c.CollectionName, c.institutioncode FROM omcollections c ".
			"WHERE c.colltype LIKE '%observations' ORDER BY c.CollectionName";
		$result = $this->conn->query($sql);
		$obserArr = Array();
		while($row = $result->fetch_object()){
			$collId = $row->collid;
			echo "<option value='".$collId."' ".($row->institutioncode=='MABA'?"SELECTED":"").">".$row->CollectionName."</option>";
		}
		$this->conn->close();
	}
	
	public function getFieldMapArr(){
		return $this->fieldMapArr;
	}
	
	public function getFieldsToBeLoaded(){
		return $this->fieldsToBeLoaded;
	}
	
	public function getFieldsToBeSkipped(){
		return $this->fieldsToBeSkipped;
	}
}

?>