<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php 
include_once("../../util/symbini.php");
include_once("../../util/dbconnection.php");

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
<html>
<head>
	<title>Observation Image Batch Loader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<script type="text/javascript">
	
	function validateForm(thisForm){
		var testStr = document.getElementById("uploadimg").value;
		if(testStr == ""){
			alert("Please select an image file to upload");
			return false;
		}
		testStr = testStr.toLowerCase();
		if(testStr.indexOf(".jpg") == -1){
			alert("Document "+testStr+" must be a JPG file (with a .jpg extension)");
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
		if($isEditable){
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
					<div style="clear:both;">
						<div style="float:left;width:130px;">Scientific Name:</div> 
						<div style="float:left;"><input type="text" name="qsciname" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:right;"><input type="submit" name="action" value="Query Records" /></div>
					</div>
				</fieldset>
				<fieldset>
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
					<div style="clear:both;">
						<input type="checkbox" name="maptn" value="1" CHECKED /> 
						Map Thumbnail Image
					</div>
					<div style="clear:both;">
						<input type="checkbox" name="maplg" value="1" CHECKED /> 
						Map Large Image
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
												<a href="<?php echo $tnUrl;?>">
													<img src="<?php echo $url;?>" />
												</a>
											</div>
											<?php
										} 
									} 
									?>
									<div style="clear:both;font-weight:bold;margin:3px;">
										<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
										File: <input id="uploadimg" name="uploadimg" type="file" size="45" />
									</div>
									<div style="margin:3px;float:right;">
										<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
										<input type="submit" name="action" value="Add Image" />
									</div>
								</fieldset>
								<fieldset>
									<legend><b>Upload Parameters</b></legend>
									<div style='margin:3px;clear:both;'>
										<b>Image Type:</b> 
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
									<div style="clear:both;">
										<div style="float:left;width:150px;">Caption:</div>
										<div style="float:left;">
											<input name="caption" value="" />
										</div>
									</div>
									<div style="clear:both;">
										<div style="float:left;width:150px;">Photographer:</div>
										<select name="photographeruid">
											<option value="">Select a photographer</option>
											<option value="">--------------------------------</option>
											<?php $uploadManager->echoPhotographerSelect($_REQUEST["dphotographeruid"]); ?>
										</select>
									</div>
									<div style="clear:both;">
										<div style="float:left;width:150px;">Manager:</div>
										<div style="float:left;">
											<input name="owner" value="<?php echo $_REQUEST["downer"]; ?>" />
										</div>
									</div>
									<div style="clear:both;">
										<div style="float:left;width:150px;">Copyright URL:</div>
										<div style="float:left;">
											<input name="copyright" value="<?php echo $_REQUEST["dcopyright"]; ?>" />
										</div>
									</div>
									<div style="clear:both;">
										<div style="float:left;width:150px;">Sort Sequence:</div>
										<div style="float:left;">
											<input name="sortsequence" value="<?php echo $_REQUEST["dsortsequence"]; ?>" />
										</div>
									</div>
									<input type="hidden" name="dimagetype" value="<?php echo $_REQUEST["dimagetype"];?>" />
									<input type="hidden" name="dphotographeruid" value="<?php echo $_REQUEST["dphotographeruid"];?>" />
									<input type="hidden" name="downer" value="<?php echo $_REQUEST["downer"];?>" />
									<input type="hidden" name="dcopyright" value="<?php echo $_REQUEST["dcopyright"];?>" />
									<input type="hidden" name="dsortsequence" value="<?php echo $_REQUEST["dsortsequence"];?>" />
									<input type="hidden" name="collid" value="<?php echo $collId;?>" />
									<input type="hidden" name="qgui" value="<?php echo $_REQUEST["qgui"];?>" />
									<input type="hidden" name="qloaddate" value="<?php echo $_REQUEST["qloaddate"];?>" />
									<input type="hidden" name="qobserver" value="<?php echo $_REQUEST["qobserver"];?>" />
									<input type="hidden" name="qfamily" value="<?php echo $_REQUEST["qfamily"];?>" />
									<input type="hidden" name="qsciname" value="<?php echo $_REQUEST["qsciname"];?>" />
									<input type="hidden" name="maptn" value="<?php echo $_REQUEST["maptn"];?>" />
									<input type="hidden" name="maplg" value="<?php echo $_REQUEST["maplg"];?>" />
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
	private $urlPath = "";
	private $uploadPath = "";

	private $tnPixWidth = 130;
	private $webPixWidth = 1300;
	private $largePixWidth = 3168;
	
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
			echo "<option value='".$row->collid."'>".$row->collectionname."</option>";
		}
		$rs->close();
	}
	public function loadImageData(){
		$status = "";
		$this->setUploadPath(); 
	 	$this->setUrlPath();
		$imgFileName = $this->cleanFileName(basename($_FILES['userfile']['name']),$this->uploadPath);
		$tnFileName = "";$lgFileName = "";
		
		if(move_uploaded_file($_FILES['userfile']['tmp_name'], $this->uploadPath.$imgFileName)){
			list($width, $height) = getimagesize($this->uploadPath.$imgFileName);
			//Create Large Image
			if($_REQUEST["maplg"] && $width > ($this->webPixWidth*1.2)){
				$targetName = substr($imgFileName,0,strrpos($imgFileName,"."))."lg".substr($imgFileName,strrpos($imgFileName,"."));;
				if($width < ($this->largePixWidth*1.2)){
					if(copy($this->uploadPath.$imgFileName,$this->uploadPath.$targetName)){
						$lgFileName = $targetName;
					}
					else{
						$status = "ERROR: Unable to copy source image to create large version";
					}
				}
				else{
					if($this->resizeImage($imgFileName,$targetName,$this->largePixWidth,round($this->largePixWidth*$height/$width),$width,$height)){
						$lgFileName = $targetName;
					}
					else{
						$status = "ERROR: Unable to resize source image to create large version";
					}
				}
			}
			//Create Thumbnail Image
			if($_REQUEST["maptn"]){
				$targetName = substr($imgFileName,0,strrpos($imgFileName,"."))."tn".substr($imgFileName,strrpos($imgFileName,"."));;
				if($this->resizeImage($imgFileName,$targetName,$this->tnPixWidth,round($this->tnPixWidth*$height/$width),$width,$height)){
					$tnFileName = $targetName;
				}
				else{
					$status = "ERROR: Unable to resize source image to create thumbnail version";
				}
			}
			//If upload image to too large, create web version
			if($width > ($this->webPixWidth*1.2)){
				if(!$this->resizeImage($imgFileName,$imgFileName,$this->webPixWidth,round($this->webPixWidth*$height/$width),$width,$height)){
					$status = "ERROR: Unable to resize source image to create web version";
				}
			}

			if(!$status){
				//Load record into database 
				$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographeruid, imagetype, caption, ".
					"owner, copyright, occid, notes, sortsequence) ".
					"SELECT o.tidinterpreted, \"".$this->urlPath.$imgFileName."\, \"".$this->urlPath.$tnFileName."\, \"".$this->urlPath.$lgFileName."\", ".
					$_REQUEST["photographeruid"].", \"".$_REQUEST["imagetype"]."\", \"".$_REQUEST["caption"]."\", \"".$_REQUEST["owner"]."\", \"".
					$_REQUEST["copyright"]."\", ".$_REQUEST["occid"].", \"".$_REQUEST["notes"]."\", ".$_REQUEST["sortsequence"]." ".
					"FROM omoccurrences o WHERE o.occid = ".$_REQUEST["occid"];
				//echo $sql;
				if(!$con->query($sql)){
					$status = "ERROR: unable to load image record into database: ".$con->error."<br/>SQL: ".$sql;
				}
			}
		} 
		else {
			$status = "ERROR: problem uploading image file";
		}
	 	
		return $status;
	}
	
 	private function cleanFileName($fName, $dlPath){
		$fName = str_replace("'","",$fName);
		$fName = str_replace("\"","",$fName);
		$fName = str_replace(" ","_",$fName);
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,25).substr($fName,strrpos($fName,"."));
		}
 		//Check and see if file already exists, if so, rename filename until it has a unique name
 		$tempFileName = $fName;
 		$cnt = 0;
 		while(file_exists($dlPath.$tempFileName)){
 			$tempFileName = substr($fName,0,strrpos($fName,"."))."_".$cnt.substr($fName,strrpos($fName,"."));
 			$cnt++;
 		}
 		$fName = $tempFileName;
		return $fName;
 	}
 	
	private function getUploadPath(){
		$this->uploadPath = $GLOBALS["imageRootPath"];
		if(substr($this->uploadPath,-1,1) != "/") $this->uploadPath .= "/";
		if(!file_exists($this->uploadPath.$_REQUEST["imagetype"])){
 			mkdir($this->uploadPath.$_REQUEST["imagetype"], 0775);
 		}
		$this->uploadPath .= $_REQUEST["imagetype"]."/";
 	}

 	private function setUrlPath(){
		$this->urlPath = $GLOBALS["imageRootUrl"];
		if(substr($this->urlPath,-1,1) != "/") $this->urlPath .= "/";
		$this->urlPath .= $_REQUEST["imagetype"]."/";
 	}
 	
	private function resizeImage($sourceName, $targetName, $newWidth, $newHeight, $oldWidth, $oldHeight){
		$status = false;
       	$sourceImg = imagecreatefromjpeg($this->uploadPath.$sourceName);
   		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$oldWidth,$oldHeight);
        if(imagejpeg($tmpImg, $this->uploadPath.$targetName, 50)){
        	$status = "";
        }
        else{
        	$status = "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetName</li>";
        }
		imagedestroy($tmpImg);
		return $status;
	}
	
	private function echoPhotographerSelect($defaultUid = 0){
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