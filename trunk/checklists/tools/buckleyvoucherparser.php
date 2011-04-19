<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:""; 
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 

$vManager = new VoucherParserManager();
$vManager->setClid($clid);
 
$editable = false;
if($isAdmin){
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
		if(testStr.indexOf(".csv") == -1 && testStr.indexOf(".CSV") == -1){
			alert("Document "+document.getElementById("uploadfile").value+" must be a CSV file (with a .csv extension)");
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
		<h1>
		</h1>
		<?php 
			if($editable){ 
				if($action == "Upload Checklist"){
					echo "<div style='margin:10px;'>";
					$vManager->parseVouchers();
				}
				?>
				<form enctype="multipart/form-data" action="buckleyvoucherparser.php" method="post" onsubmit="return validateUploadForm(this);">
					<fieldset>
						<legend>Voucher Upload Form</legend>
						<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
						<div style="font-weight:bold;">
							Checklist File: 
							<input id="uploadfile" name="uploadfile" type="file" size="45" />
						</div>
						<div style="font-weight:bold;">
							<input name="clid" value="<?php echo $clid; ?>" />
						</div>
						<div style="margin-top:10px;">
							<input id="clloadsubmit" name="action" type="submit" value="Upload Checklist" />
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
 
class VoucherParserManager {

	private $conn;
	private $clid;
	private $clName;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function setClid($c){
		$this->clid = $c;
	}

	public function getClName(){
		return $this->clName;
	}

	public function parseVouchers(){
		set_time_limit(240);
		ini_set("max_input_time",240);
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'r') or die("Can't open file. File may be too large. Try uploading file in sections.");

		$headerData = fgetcsv($fh);
		$headerArr = Array();
		foreach($headerData as $k => $v){
			$vStr = strtolower($v);
			$vStr = str_replace(Array(" ",".","_"),"",$vStr);
			$headerArr[$vStr] = $k;
		}

		echo "<ul>";
		echo "<li>File uploaded and now reading file...</li>";
		echo "<ol>";
		$successCnt = 0;
		$failCnt = 0;
		while($valueArr = fgetcsv($fh)){
			if(array_key_exists("herbariumrecords",$headerArr) && array_key_exists("sciname",$headerArr)){
				$sciName = $valueArr[$headerArr["sciname"]];
				$vStr = $valueArr[$headerArr["herbariumrecords"]];
				if($vStr){
					$regExp = "([A-Z]+ \d+)";
					$fnaDesc = preg_match_all("/".$regExp."/s", $vStr, $matches);
					$matchArr = $matches[0];
					foreach($matchArr as $mStr){
						echo "<li>processing: ".$mStr;
						$targetArr = explode(" ",$mStr);
						$sql = "INSERT INTO fmvouchers(tid,clid,occid,collector) ".
							"SELECT t.tid,".$this->conn->real_escape_string($this->clid).",o.occid,concat(o.recordedby,' (',o.recordnumber,')') as collector ".
							"FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted ".
							"INNER JOIN omcollections c ON o.collid = c.collid ".
							"WHERE t.sciname = '".$this->conn->real_escape_string($sciName)."' AND c.collectioncode = '".$this->conn->real_escape_string($targetArr[0])."' AND o.catalognumber = '".$this->conn->real_escape_string($targetArr[1])."'";
						//echo $sql;
						if($this->conn->query($sql)){
							$successCnt++;
						}
						else{
							$failCnt++;
							echo "<li><span style='color:red;'>ERROR:</span> ";
							echo $sciName." failed to load voucher<br />Error msg: ".$this->conn->error;
							echo $sql."<br />";
							echo "</li>";
						}
					}
				}
			}
		}
		echo "</ol>";
		fclose($fh);
		echo "<li>Finished loading checklist</li>";
		echo "<li>".$successCnt." names loaded successfully</li>";
		echo "<li>".$failCnt." failed to load</li>";
		echo "</ul>";
	}
}
?>