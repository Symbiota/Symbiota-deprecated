<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";
$photographer = array_key_exists("photographer",$_REQUEST)?$_REQUEST["photographer"]:"";
$owner = array_key_exists("owner",$_REQUEST)?$_REQUEST["owner"]:"";
$latdecimal = array_key_exists("latdecimal",$_REQUEST)?$_REQUEST["latdecimal"]:"";
$longdecimal = array_key_exists("longdecimal",$_REQUEST)?$_REQUEST["longdecimal"]:"";
$notes = array_key_exists("notes",$_REQUEST)?$_REQUEST["notes"]:"";

$submitManager = new UnknownSubmitManager();
if($tid){
	$unkArr = Array("tid"=>$tid,"photographer"=>$photographer,"owner"=>$owner,"latdecimal"=>$latdecimal,"longdecimal"=>$longdecimal,"notes"=>$notes);
	$submitManager->loadUnknown($unkArr);
}
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> - Submit to Community Identification</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<!--inicio favicon -->
		<link rel="shortcut icon" href="../images/favicon.png" type="image/x-icon">
	<meta name='keywords' content='' />
</head>

<body>
	<?php
	$displayLeftMenu = (isset($imagelib_unknownsubmitMenu)?$imagelib_unknownsubmitMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_unknownsubmitCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_unknownsubmitCrumbs;
		echo " <b>Submit Unknown</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div style="margin:15px;">
		<h1>Submit an Image for Identification</h1>
		<?php
		if(array_key_exists("un",$paramsArr)){
		?>
		<div style="margin:15px;">
			Use form below to submit one to several images of an unknown. Enter the family or genus if you know it, or just leave as unknown.
			Make sure to include a locality and elevation of the unknown.
		</div>
		<form id="unknownsubmit" name="unknownsubmit" action="unknownsubmit.php">
			<fieldset>
				<legend>Image Information</legend>
				<div>
					Family or Genus (if known):
					<select name="tid" id="tid">
						<option value="unknown">unknown</option>
						<?php
							$submitManager->showTaxaList();
						?>
					</select>
				</div>
				<div>
					Photographer: <input name="photographer" id="photographer" type="text" size="50">
				</div>
				<div>
					Owner: <input name="owner" id="owner" type="text">
				</div>
				<div>
					Decimal Latitude:
					<input name="latdecimal" id="latdecimal" type="text" onchange="javascript:checkLat();">
					<select id="lat_ns" name="lat_ns" onchange="javascript:checkLat();">
						<option id="N" value="N">N</option>
						<option id="S" value="S">S</option>
					</select>
				</div>
				<div>
					Decimal Longitude:
					<input name="longdecimal" id="longdecimal" type="text" onchange="javascript:checkLong();">
					<select id="long_ew" name="long_ew" onchange="javascript:checkLong();">
						<option id="W" value="W">W</option>
						<option id="E" value="E">E</option>
					</select>
				</div>
				<div>
					Notes: <input name="notes" id="notes" type="text" size="50">
				</div>
				<div>
					<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
					<div style="font-weight:bold;">Select up to 4 files to upload:</div>
					<div style="margin-left:15px;">File 1: <input name="uploadedfile[]" type="file" /></div>
					<div style="margin-left:15px;">File 2: <input name="uploadedfile[]" type="file" /></div>
					<div style="margin-left:15px;">File 3: <input name="uploadedfile[]" type="file" /></div>
					<div style="margin-left:15px;">File 4: <input name="uploadedfile[]" type="file" /></div>
				</div>
				<div>
					<input id="submitunknown" name="submitunknown" type="submit" value="Upload Data" />
				</div>
			</fieldset>
		</form>
	</div>
	<?php
		}
		else{
			echo "<div style='margin:15px;'>You must <a href='../profile/index.php?refurl=".$_SERVER['PHP_SELF']."'>login</a> before you can submit or comment on an image.</div>";
			echo "<div style='margin:15px;'>Don't have an account? Create a <a href='../profile/newprofile.php'>new account</a>.</div>";
		}
		include($serverRoot.'/footer.php');
	?>

</body>
<script language="javascript">

	function checkLat(){
		if(document.unknownsubmit.latdecimal.value != ""){
			if(document.unknownsubmit.lat_ns.value=='N'){
				document.unknownsubmit.latdecimal.value = Math.abs(parseFloat(document.unknownsubmit.latdecimal.value));
			}
			else{
				document.unknownsubmit.latdecimal.value = -1*Math.abs(parseFloat(document.unknownsubmit.latdecimal.value));
			}
		}
	}

	function checkLong(){
		if(document.unknownsubmit.longdecimal.value != ""){
			if(document.unknownsubmit.long_ew.value=='E'){
				document.unknownsubmit.longdecimal.value = Math.abs(parseFloat(document.unknownsubmit.longdecimal.value));
			}
			else{
				document.unknownsubmit.longdecimal.value = -1*Math.abs(parseFloat(document.unknownsubmit.longdecimal.value));
			}
		}
	}
</script>
</html>

<?php

class UnknownSubmitManager{

	private function getConnection() {
 		return MySQLiConnectionFactory::getCon("write");
	}

	public function showTaxaList(){
		$conn = $this->getConnection();
		$sql = "SELECT t.tid, t.sciname ".
			"FROM taxa t WHERE t.rankid = 140 OR t.rankid = 180 ".
			"ORDER BY t.rankid, t.sciname";
		$result = $conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<option value='".$row->tid."'>".$row->sciname."</option>";
		}
		$result->close();
		$conn->close();
	}

	public function loadUnknown($unkData){
		$conn = $this->getConnection();
		$sql = "INSERT INTO unknowns (tid, photographer, owner, latdecimal, longdecimal, notes, username) ".
			"VALUES (".$unkData["tid"].",".$unkData["photographer"].",".$unkData["owner"].",".$unkData["latdecimal"].",".
			$unkData["notes"].",".$paramsArr["un"].")";
		$conn->query($sql);
		$conn->close();
	}
}
?>
