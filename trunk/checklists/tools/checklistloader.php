<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:""; 
$hasHeader = array_key_exists("hasheader",$_REQUEST)?$_REQUEST["hasheader"]:"";
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 

$clLoaderManager = new ClLoaderManager();
 
$editable = false;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clManager->getClid(),$userRights["ClAdmin"]))){
	$editable = true;
}
 
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Species Checklist Loader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script type="text/javascript">
	
	function validateUploadForm(thisForm){
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
	$displayLeftMenu = (isset($checklists_checklistloaderMenu)?$checklists_checklistloaderMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($checklists_checklistloaderCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_checklistloaderCrumbs;
		echo " <b>".$defaultTitle." Checklists Loader</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
			if($editable){ 
				if($action == "Upload Checklist"){
					echo "<div style='margin:10px;'>";
					$clLoaderManager->uploadChecklist($clid,$hasHeader);
					echo "</div>";
				}
				?>
				<form enctype="multipart/form-data" action="checklistloader.php" method="post" onsubmit="return validateUploadForm(this);">
					<fieldset>
						<legend>Checklist Upload Form</legend>
						<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
						<div style="font-weight:bold;">
							Checklist File: 
							<input id="uploadfile" name="uploadfile" type="file" size="45" />
						</div>
						<div>
							<input type="checkbox" name="hasheader" value="1" <?php echo ($hasHeader?"CHECKED":""); ?> />
							First line contains header
						</div>
						<div style="margin:10px;">
							<div>Must be a tab-delimited text file that follows one of the following criteria:</div>
							<ul>
								<li>First column consisting of the scientific name, with or without authors</li>
								<li>First row contains following column names (in any order):</li>
								<ul>
									<li>sciname (required)</li>
									<li>family (optional)</li>
									<li>habitat (optional)</li>
									<li>abundance (optional)</li>
									<li>notes (optional)</li>
								</ul>
							</ul>
						</div>
						<div style="margin-top:10px;">
							<input id="clloadsubmit" name="action" type="submit" value="Upload Checklist" />
							<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
						</div>
					</fieldset>
				</form>
			<?php 
			}
			elseif(!$symbUid){ 
				echo "<h2>You must login to the system before you can upload a species list</h2>";	
			}
			else{
				echo "<h2>You appear not to have rights to edit this checklist. If you think this is in error, contact an administrator</h2>";
			}
		?>

	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>
</body>
</html>
<?php
 
 class ClLoaderManager {

	private $conn;

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function uploadChecklist($clid, $hasHeader){
		set_time_limit(120);
		ini_set("max_input_time",120);
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'r') or die("Can't open file");
		
		$headerArr = Array();
		if($hasHeader){
			$headerStr = fgets($fh);
			$headerData = explode("\t",$headerStr);
			foreach($headerData as $k => $v){
				$vStr = strtolower($v);
				$vStr = str_replace(Array(" ",".","_"),"",$vStr);
				$vStr = str_replace(Array("scientificname","taxa","species","taxon"),"sciname",$vStr);
				$headerArr[$vStr] = $k;
			}
		}
		else{
			$headerArr["sciname"] = 0;
		}
		
		if(array_key_exists("sciname",$headerArr)){
			echo "<ul>";
			echo "<li>Beginning process to load checklist</li>";
			echo "<li>File uploaded and now reading file...</li>";
			echo "<ol>";
			$successCnt = 0;
			$failCnt = 0;
			while($line = fgets($fh)){
				$valueArr = explode("\t",$line);
				$statusStr = "";
				$tid = 0;
				$rankId = 0;
				$sciName = ""; $family = "";
				$sciNameStr = $valueArr[$headerArr["sciname"]];
				if($sciNameStr){
					$sql = "SELECT t.tid, ts.family, t.rankid ".
						"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
						"WHERE ts.taxauthid = 1 AND t.sciname = '".$sciNameStr."'";
					//echo $sql;
					$rs = $this->conn->query($sql);
					if($row = $rs->fetch_object()){
						$tid = $row->tid;
						$family = $row->family;
						$sciName = $sciNameStr;
						$rankId = $row->rankid;
					}
					$rs->close();
		
					if(!$tid){
						//$sciNameStr not in database, thus try parsing out author  
						$unitInd1 = ""; $unitName1 = "";
						$unitInd2 = ""; $unitName2 = "";
						$unitInd3 = ""; $unitName3 = "";
						$sciNameArr = explode(" ",$sciNameStr);
						if(count($sciNameArr)){
							if(strtolower($sciNameArr[0]) == "x"){
								$unitInd1 = array_shift($sciNameArr);
							}
							$unitName1 = array_shift($sciNameArr);
							if(count($sciNameArr)){
								if(strtolower($sciNameArr[0]) == "x"){
									$unitInd2 = array_shift($sciNameArr);
								}
								$unitName1 = array_shift($sciNameArr);
							}				
						}
						while($sciStr = array_shift($sciNameArr)){
							if($sciStr == "ssp." || $sciStr == "ssp" || $sciStr == "subsp." || $sciStr == "subsp" || $sciStr == "var." || $sciStr == "var" || $sciStr == "f." || $sciStr == "forma"){
								$unitInd3 = $sciStr;
								$unitName3 = array_shift($sciNameArr);
							}
						}
						$sciName = $unitInd1." ".$unitName1." ".$unitInd2." ".$unitName2." ".$unitInd3." ".$unitName3;
						$sql = "SELECT t.tid, ts.family ".
							"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
							"WHERE ts.taxauthid = 1 AND t.sciname = '".$sciName."'";
						$rs = $this->conn->query($sql);
						if($row = $rs->fetch_object()){
							$tid = $row->tid;
							$family = $row->family;
							$sciName = $sciNameStr;
							$rankId = $row->rankid;
						}
						$rs->close();
					}
					
					//Load taxon into checklist
					if($tid && $rankId > 140){
						$sqlInsert = "";
						$sqlValues = "";
						if(array_key_exists("family",$headerArr) && strtolower($family) != strtolower($valueArr[$headerArr["family"]])){
							$sqlInsert .= ",familyoverride";
							$sqlValues .= ",'".$valueArr[$headerArr["family"]]."'";
						}
						if(array_key_exists("habitat",$headerArr) && $valueArr[$headerArr["habitat"]]){
							$sqlInsert .= ",habitat";
							$sqlValues .= ",'".$valueArr[$headerArr["habitat"]]."'";
						}
						if(array_key_exists("abundance",$headerArr) && $valueArr[$headerArr["abundance"]]){
							$sqlInsert .= ",abundance";
							$sqlValues .= ",'".$valueArr[$headerArr["abundance"]]."'";
						}
						if(array_key_exists("notes",$headerArr) && $valueArr[$headerArr["notes"]]){
							$sqlInsert .= ",notes";
							$sqlValues .= ",'".$valueArr[$headerArr["notes"]]."'";
						}
						$sql = "INSERT INTO fmchklsttaxalink (tid,clid".$sqlInsert.") VALUES (".$tid.", ".$clid.$sqlValues.")";
						//echo $sql;
						if($this->conn->query($sql)){
							$successCnt++;
						}
						else{
							$failCnt++;
							$statusStr = $sciNameStr." (TID = $tid) failed to load<br />Error msg: ".$this->conn->error;
						}
					}
					else{
						$statusStr = $sciNameStr." failed to load";
						$failCnt++;
					}
				}
				if($statusStr) echo "<li><span style='color:red;'>ERROR:</span> ".$statusStr."</li>";
			}
			echo "</ol>";
			fclose($fh);
			echo "<li>Finished loading checklist</li>";
			echo "<li>".$successCnt." names loaded successfully</li>";
			echo "<li>".$failCnt." failed to load</li>";
			echo "</ul>";
		}
		else{
			echo "<div style='color:red;'>ERROR: unable to located sciname field</div>";
		}
	}
 }

 ?>