<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php 
include_once("../../util/symbini.php");
include_once("../../util/dbconnection.php");

$uploadManager = new ImageUploadManager();

$collId = (array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:"");
$action = (array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"");

$editable = false;
if($isAdmin){
 	$editable = true;
}


?>
<html>
<head>
	<title>Observation Image Batch Loader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
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
	include($serverRoot."/util/header.php");
	if(isset($collections_admin_observationuploaderCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_admin_observationuploaderCrumbs;
		echo " <b>Observation Image Loader</b>"; 
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h2>Observation Image Loader</h2>
	    <?php
		if($editable){
			?>
			<form id="imgloader" name="imgloader" action="imagebatchloader.php" method="post">
				<fieldset>
					<legend>Query Criteria</legend>
					<div>
						Collection/Observation: 
						<select>
							<option value=''>Select Collection/Observation Project</option>
							<?php ?>
						</select>
					</div>
					<div>
						GUID: <input type="text" name="gui" />
					</div>
					<div>
						Upload Date: <input type="text" name="loaddate" />
					</div>
					<div>
						Observer: <input type="text" name="observer" />
					</div>
					<div>
						Family: <input type="text" name="family" />
					</div>
					<div>
						Taxon: <input type="text" name="sciname" />
					</div>
					<div>
						<input type="submit" name="action" value="Query Records" />
					</div>
				</fieldset>
			</form>
			<hr />
			<?php
				if($editable){
					if($action == "Query Records" && $collId){
						$queryArr = Array("collid"=>$collId);
						if($_REQUEST["gui"]) $queryArr["gui"] = $_REQUEST["gui"];
						if($_REQUEST["loaddate"]) $queryArr["loaddate"] = $_REQUEST["loaddate"];
						if($_REQUEST["observer"]) $queryArr["observer"] = $_REQUEST["observer"];
						if($_REQUEST["family"]) $queryArr["family"] = $_REQUEST["family"];
						if($_REQUEST["sciname"]) $queryArr["sciname"] = $_REQUEST["sciname"];
						$recArr = $uploadManager->getOccurrenceRecords($queryArr);
						foreach($recArr as $k => $v){
							echo "<div>".$v["occurrenceid"]."; ".$v["recordedby"]." [".$v["recordnumber"]."] ".
								$v["eventdate"]."; ".$v["sciname"]."[".$v["family"]."]; ".$v["locality"]."</div>";
						}
					}
				}
			?>

			
			<form id="imgloader" name="imgloader" enctype="multipart/form-data" action="observationuploader.php" method="post" onsubmit="return validateForm(this);">
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
		<?php 
		}
		else{
			echo "<div>You must be logged in and authorized to view this page. Please login.</div>";
		}
		?>
	</div>
	<?php 
	include($serverRoot."/util/footer.php");
	?>
	
</body>
</html>
<?php 
class ImageUploadManager{

	private $conn;

	function __construct() {
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
		
	private function setConnection() {
 		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	public function getOccurrenceRecords($queryArr){
		$this->setConnection();
		$returnArr = Array();
		$sql = "SELECT o.occurrenceid, o.recordedby, o.recordnumber, o.eventdate, ".
			"o.family, o.sciname, o.locality, o.initialtimestamp ".
			"FROM omoccurrences o WHERE o.collid = ".$queryArr["collid"]." ";
		if(array_key_exists("gui",$queryArr)) "AND o.occurrenceId LIKE '%".$queryArr["gui"]."%' "; 
		if(array_key_exists("loaddate",$queryArr)) "AND o.initialtimestamp = '".$queryArr["loaddate"]."' "; 
		if(array_key_exists("observer",$queryArr)) "AND o.recordedby LIKE '%".$queryArr["observer"]."%' "; 
		if(array_key_exists("family",$queryArr)) "AND o.family = '".$queryArr["family"]."' "; 
		if(array_key_exists("sciname",$queryArr)) "AND o.sciname LIKE '".$queryArr["sciname"]."%' ";
		$result = $this->conn->query($sql);
		$recCnt = 1;
		while($row = $result->fetch_object()){
			$returnArr[$recCnt]["occurrenceid"] = $row->occurrenceid;
			$returnArr[$recCnt]["recordedby"] = $row->recordedby;
			$returnArr[$recCnt]["recordnumber"] = $row->recordnumber;
			$returnArr[$recCnt]["eventdate"] = $row->eventdate;
			$returnArr[$recCnt]["family"] = $row->family;
			$returnArr[$recCnt]["sciname"] = $row->sciname;
			$returnArr[$recCnt]["locality"] = $row->locality;
			$returnArr[$recCnt]["initialtimestamp"] = $row->initialtimestamp;
			$recCnt++;
		}
		$result->close();
		return $returnArr;	
	}

}

?>