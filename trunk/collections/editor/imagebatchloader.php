<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$uploadManager = new ImageUploadManager();

$collId = (array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:"");
$action = (array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"");

$isEditable = false;
if($isAdmin){
 	$isEditable = true;
}

if($isEditable){
	if($action == "Add Image"){
		
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>Observation Image Batch Loader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset="<?php echo $charset;?>>
	<script type="text/javascript">
	
	function validateForm(thisForm){
		var testStr = document.getElementById("uploadimg").value;
		if(testStr == ""){
			alert("Please select an image file to upload");
			return false;
		}
		testStr = testStr.toLowerCase();
		if((testStr.indexOf(".jpg") == -1) && (testStr.indexOf(".JPG") == -1)){
			alert("Document "+testStr+" must be a JPG file (with a .jpg extension)");
			return false;
		}
		return true;
	}
	
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_admin_observationuploaderMenu)?$collections_admin_observationuploaderMenu:false);
	include($serverRoot.'/header.php');
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
		if($isEditable){
			?>
			<form id="imgloader" name="imgloader" action="imagebatchloader.php" method="get">
				<fieldset>
					<legend><b>Query Criteria</b></legend>
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
						<div style="float:left;"><input type="text" name="qgui" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Upload Date:</div> 
						<div style="float:left;"><input type="text" name="qloaddate" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Observer:</div> 
						<div style="float:left;"><input type="text" name="qobserver" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Family:</div> 
						<div style="float:left;"><input type="text" name="qfamily" /></div>
					</div>
					<div style="clear:both;margin-bottom:20px;">
						<div style="float:left;width:130px;">Scientific Name:</div> 
						<div style="float:left;"><input type="text" name="qsciname" /></div>
					</div>
					<div style="clear:both;margin:5px">
						<fieldset style="margin:10px 2px 10px 2px;">
							<legend><b>Default Upload Parameters</b></legend>
							<div style='margin:3px;clear:both;'>
								<div style="float:left;width:150px;">Image Type:</div>
								<select name='dimagetype'>
									<option value='observation'>
										Observation Image
									</option>
									<option value='specimen'>
										Specimen Image
									</option>
									<option value='field'>
										Field Image
									</option>
								</select>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:150px;">Photographer:</div>
								<select name="dphotographeruid">
									<option value="">Select a photographer</option>
									<option value="">--------------------------------</option>
									<?php $uploadManager->echoPhotographerSelect(); ?>
								</select>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:150px;">Manager:</div>
								<div style="float:left;"><input name="downer" value="" /></div>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:150px;">Copyright URL:</div>
								<div style="float:left;"><input name="dcopyright" value="" /></div>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:150px;">Sort Sequence:</div>
								<div style="float:left;"><input name="dsortsequence" value="50" /></div>
							</div>
						</fieldset>
					</div>
					<div style="clear:both;">
						<div style="float:right;">
							<input type="submit" name="action" value="Query Records" />
						</div>
					</div>
				</fieldset>
			</form>
			<hr />
			<?php
				if($action == "Query Records" && $collId){
					$queryArr = Array("collid"=>$collId);
					$queryArr["gui"] = $_REQUEST["qgui"];
					$queryArr["loaddate"] = $_REQUEST["qloaddate"];
					$queryArr["observer"] = $_REQUEST["qobserver"];
					$queryArr["family"] = $_REQUEST["qfamily"];
					$queryArr["sciname"] = $_REQUEST["qsciname"];
					$recArr = $uploadManager->getOccurrenceRecords($queryArr);
					foreach($recArr as $occId => $v){
						?>
						<div>
							<form action="<?php echo $clientRoot;?>/taxa/admin/tpimageeditor.php" method="post" enctype='multipart/form-data' target="_blank">
								<fieldset>
									<legend><b><?php echo $v["occurrenceid"]; ?></b></legend>
									<div style="margin:3px;">
										<a href="<?php echo $clientRoot."/collections/individual/index.php?occid=".$occId;?>"><?php echo $occId;?></a>
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
									<?php 
									if(array_key_exists("images",$v)){
										$imgArr = $v["images"];
										foreach($imgArr as $imgId => $iArr){
											$tnUrl = (array_key_exists("tnurl",$iArr)?$iArr["tnurl"]:"");
											$url = (array_key_exists("url",$iArr)?$iArr["url"]:"");
											if(!$tnUrl) $tnUrl = $url;
											if(array_key_exists("imageDomain",$GLOBALS)){
												if(substr($url,0,1)=="/") $url = $GLOBALS["imageDomain"].$url;
												if(substr($tnUrl,0,1)=="/") $url = $GLOBALS["imageDomain"].$tnUrl;
											}
											?>
											<div style="margin:3px;float:left;">
												<a href="<?php echo $url;?>">
													<img src="<?php echo $tnUrl;?>" />
												</a>
											</div>
											<?php
										} 
									} 
									?>
									<div style="clear:both;font-weight:bold;margin:3px;">
										<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
										File: <input id="userfile" name="userfile" type="file" size="45" />
									</div>
									<div style="margin:3px;clear:both;">
										<div style="float:left;width:100px;font-weight:bold;">Caption:</div>
										<div style="float:left;">
											<input name="caption" value="" />
										</div>
									</div>
									<div style="margin:3px;clear:both;">
										<div style="float:left;width:100px;font-weight:bold;">Photographer:</div>
										<select name="photographeruid">
											<option value="">Select a photographer</option>
											<option value="">--------------------------------</option>
											<?php $uploadManager->echoPhotographerSelect($_REQUEST["dphotographeruid"]); ?>
										</select>
									</div>
									<div style='margin:3px;clear:both;'>
										<div style="float:left;width:100px;font-weight:bold;">Image Type:</div> 
										<div style="float:left;">
											<select name='imagetype'>
												<option value='observation'>
													Observation Image
												</option>
												<option value='specimen' <?php echo ($_REQUEST["dimagetype"]=="specimen"?"SELECTED":"");?>>
													Specimen Image
												</option>
												<option value='field' <?php echo ($_REQUEST["dimagetype"]=="field"?"SELECTED":"");?>>
													Field Image
												</option>
											</select>
										</div>
									</div>
									<div style="margin:3px;clear:both;">
										<div style="float:left;width:100px;font-weight:bold;">Manager:</div>
										<div style="float:left;">
											<input name="owner" value="<?php echo $_REQUEST["downer"]; ?>" />
										</div>
									</div>
									<div style="margin:3px;clear:both;">
										<div style="float:left;width:100px;font-weight:bold;">Copyright URL:</div>
										<div style="float:left;">
											<input name="copyright" value="<?php echo $_REQUEST["dcopyright"]; ?>" />
										</div>
									</div>
									<div style="margin:3px;float:right;">
										<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
										<input type="hidden" name="tid" value="<?php echo $v["tid"]; ?>" />
										<input type="hidden" name="catagory" value="imagequicksort" />
										<input type="submit" name="action" value="Upload Image" />
									</div>
									<div style="margin:3px;">
										<div style="float:left;width:100px;font-weight:bold;">Sort Sequence:</div>
										<div style="float:left;">
											<input name="sortsequence" value="<?php echo $_REQUEST["dsortsequence"]; ?>" />
										</div>
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
	include($serverRoot.'/footer.php');
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
		$sql = "SELECT o.occid, o.tidinterpreted, o.occurrenceid, o.recordedby, o.recordnumber, o.eventdate, ".
			"o.family, o.sciname, o.locality, o.datelastmodified ".
			"FROM omoccurrences o ".
			"WHERE o.collid = ".$queryArr["collid"]." AND o.tidinterpreted IS NOT NULL ";
		if($queryArr["gui"]) $sql .= "AND o.occurrenceId LIKE '".$queryArr["gui"]."%' "; 
		if($queryArr["loaddate"]) $sql .= "AND o.datelastmodified = '".$queryArr["loaddate"]."' "; 
		if($queryArr["observer"]) $sql .= "AND o.recordedby LIKE '%".$queryArr["observer"]."%' "; 
		if($queryArr["family"]) $sql .= "AND o.family = '".$queryArr["family"]."' "; 
		if($queryArr["sciname"]) $sql .= "AND o.sciname LIKE '".$queryArr["sciname"]."%' ";
		$sql .= "ORDER BY o.occurrenceid "; 
		//echo "SQL: ".$sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$returnArr[$occId]["tid"] = $row->tidinterpreted;
			$returnArr[$occId]["occurrenceid"] = $row->occurrenceid;
			$returnArr[$occId]["recordedby"] = $row->recordedby;
			$returnArr[$occId]["recordnumber"] = $row->recordnumber;
			$returnArr[$occId]["eventdate"] = $row->eventdate;
			$returnArr[$occId]["family"] = $row->family;
			$returnArr[$occId]["sciname"] = $row->sciname;
			$returnArr[$occId]["locality"] = $row->locality;
			$returnArr[$occId]["datelastmodified"] = $row->datelastmodified;
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
			echo "<option value='".$row->collid."' ".(strpos($row->collectionname,"Madrean")!==false?"SELECTED":"").">".$row->collectionname."</option>";
		}
		$rs->close();
	}

	public function echoPhotographerSelect($defaultUid = 0){
 		$sql = "SELECT u.uid, CONCAT_WS(' ',u.lastname,u.firstname) AS fullname ".
			"FROM users u ORDER BY u.lastname, u.firstname ";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<option value='".$row->uid."'".($defaultUid==$row->uid?" SELECTED":"").">".$row->fullname."</option>";
		}
		$result->close();
 	}
}

?>