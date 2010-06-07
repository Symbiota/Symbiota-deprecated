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
			<form id="imgloader" name="imgloader" action="imagebatchloader.php" method="get">
				<fieldset>
					<legend>Query Criteria</legend>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Collection/Observation:</div> 
						<div style="float:left;">
							<select name="collid">
								<option value=''>Select Collection/Observation Project</option>
								<option value=''>---------------------------------------------------------</option>
								<?php echo $uploadManager->echoOccurrenceHoldings(); ?>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Occurrence ID (GUID):</div>
						<div style="float:left;"><input type="text" name="gui" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Upload Date:</div> 
						<div style="float:left;"><input type="text" name="loaddate" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Observer:</div> 
						<div style="float:left;"><input type="text" name="observer" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Family:</div> 
						<div style="float:left;"><input type="text" name="family" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Scientific Name:</div> 
						<div style="float:left;"><input type="text" name="sciname" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:right;"><input type="submit" name="action" value="Query Records" /></div>
					</div>
				</fieldset>
			</form>
			<hr />
			<?php
				if($action == "Query Records" && $collId){
					$queryArr = Array("collid"=>$collId);
					$queryArr["gui"] = $_REQUEST["gui"];
					$queryArr["loaddate"] = $_REQUEST["loaddate"];
					$queryArr["observer"] = $_REQUEST["observer"];
					$queryArr["family"] = $_REQUEST["family"];
					$queryArr["sciname"] = $_REQUEST["sciname"];
					$recArr = $uploadManager->getOccurrenceRecords($queryArr);
					foreach($recArr as $occId => $v){
						?>
						<div>
							<form action="imagebatchloader.php" method="get">
								<fieldset>
									<legend><b><?php echo $v["occurrenceid"]; ?></b></legend>
									<div style="margin:3px;">
										<a href="<?php echo $clientRoot."/collections/individual/individual.php?occid=".$occId;?>"><?php echo $occId;?></a>
										<?php 
										echo $v["recordedby"];
										if($v["recordnumber"]) echo " [".$v["recordnumber"]."] ";
										echo ", ".$v["eventdate"]."; ".$v["sciname"];
										if($v["family"]) {
											echo " [".$v["family"]."]";
										}	
										echo "; ".$v["locality"];
										?>
									</div>
									<?php if(array_key_exists("images",$v)){ ?>
									<div style="margin:3px;">
										
									</div>
									<?php } ?>
									<div style="clear:both;font-weight:bold;margin:3px;">
										<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
										File: <input id="uploadfile" name="uploadfile" type="file" size="45" />
									</div>
									<div style="margin:3px;">
										<input type="hidden" name="gui" value="<?php echo $v["occurrenceid"]; ?>" />
										<input type="submit" name="action" value="Add Image" />
									</div>
								</fieldset>
							</form>
						</div>
						<?php 
					}
				}

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
		$this->setConnection();
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
		
	private function setConnection() {
 		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	public function getOccurrenceRecords($queryArr){
		$returnArr = Array();
		$sql = "SELECT o.occid, o.occurrenceid, o.recordedby, o.recordnumber, o.eventdate, ".
			"o.family, o.sciname, o.locality, o.initialtimestamp ".
			"FROM omoccurrences o ".
			"WHERE o.collid = ".$queryArr["collid"]." ";
		if($queryArr["gui"]) $sql .= "AND o.occurrenceId LIKE '".$queryArr["gui"]."%' "; 
		if($queryArr["loaddate"]) $sql .= "AND o.initialtimestamp = '".$queryArr["loaddate"]."' "; 
		if($queryArr["observer"]) $sql .= "AND o.recordedby LIKE '%".$queryArr["observer"]."%' "; 
		if($queryArr["family"]) $sql .= "AND o.family = '".$queryArr["family"]."' "; 
		if($queryArr["sciname"]) $sql .= "AND o.sciname LIKE '".$queryArr["sciname"]."%' ";
		$sql .= "ORDER BY o.occurrenceid "; 
		//echo "SQL: ".$sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$returnArr[$occId]["occurrenceid"] = $row->occurrenceid;
			$returnArr[$occId]["recordedby"] = $row->recordedby;
			$returnArr[$occId]["recordnumber"] = $row->recordnumber;
			$returnArr[$occId]["eventdate"] = $row->eventdate;
			$returnArr[$occId]["family"] = $row->family;
			$returnArr[$occId]["sciname"] = $row->sciname;
			$returnArr[$occId]["locality"] = $row->locality;
			$returnArr[$occId]["initialtimestamp"] = $row->initialtimestamp;
		}
		$result->close();
		//Grab images
		$sql = "SELECT i.imgid, i.occid, i.url, i.thumbnailurl ".
			"FROM images i ".
			"WHERE i.occid IN (".implode(",",array_keys($returnArr)).")";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$occId = $row->occid;
			if($row->url) $returnArr[$occId]["images"][$row->imgid]["url"] = $row->url;
			if($row->thumbnailurl) $returnArr[$occId]["images"][$row->imgid]["tnurl"] = $row->thumbnailurl;
		}
		$rs->close();
		return $returnArr;	
	}

	public function echoOccurrenceHoldings(){
		$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
			"WHERE colltype = 'observations' ORDER BY c.collectionname";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->collid."'>".$row->collectionname."</option>";
		}
		$rs->close();
	}
}

?>