<?php 
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
$unkid = array_key_exists("unkid",$_REQUEST)?$_REQUEST["unkid"]:"";

$unkDisplayManager = new UnknownDisplayManager();
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> - Unknown Display</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link rel="stylesheet" href="../css/speciesprofile.css" type="text/css"/>
	<meta name='keywords' content='' />
</head>

<body>
	<?php
	$displayLeftMenu = (isset($imagelib_unknowndisplayMenu)?$imagelib_unknowndisplayMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_unknowndisplayCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_unknowndisplayCrumbs;
		echo " <b>Unknown</b>"; 
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div style="margin:15px;">
		<h1>Unknown #<?php echo $unkid; ?></h1>
		<div style="margin:0px 0px 5px 20px;">
			Use form below to submit one to several images of an unknown. Enter the family or genus if you know it, or just leave as unknown.
			Make sure to include a locality and elevation of the unknown.  
		</div>
		<?php 
			$unkArray = $unkDisplayManager->getUnknown($unkid);
			echo "<div><b>Taxonomy:</b> ".$unkArray["sciname"].($unkArray["sciname"] != $unkArray["family"]?" (".$unkArray["family"].")":"")."</div>";
			echo "<div><b>Photographer:</b> ".$unkArray["photographer"]."</div>";
			echo "<div><b>Owner:</b> ".$unkArray["owner"]."</div>";
			echo "<div><b>Latitude:</b> ".$unkArray["latdecimal"]."</div>";
			echo "<div><b>Longitude:</b> ".$unkArray["longdecimal"]."</div>";
			echo "<div><b>Notes:</b> ".$unkArray["notes"]."</div>";
			echo "<div><b>Submitted by:</b> ".$unkArray["username"]."</div>";
			echo "<div><hr/>";
			$urlArr = $unkArray["url"];
			foreach($urlArr as $url){
				echo "<div class='imgthumb'>";
				echo "<a href='".$url."'><img src='".$url."' style='width:150px;' /></a>";
				echo "</div>"; 
			}
			echo "<hr/></div>";
		?>

		<div id="unknowneditdiv" style="display:block;">
			<form id="unknowneditform" name="unknowneditform" action="unknowndisplay.php">
				<fieldset>
					<legend>Image Information</legend>
					<div>
						Family or Genus (if known): 
						<select name="tid" id="tid">
							<option value="unknown">unknown</option>
							<?php 
								$submitManager->showTaxaList($unkArray["sciname"]);
							?>
						</select>
					</div>
					<div>
						Photographer: <input name="photographer" id="photographer" type="text" value="<?php echo $unkArray["photographer"]; ?>" size="50">
					</div>
					<div>
						Owner: <input name="owner" id="owner" type="text" value="<?php echo $unkArray["owner"]; ?>">
					</div>
					<div>
						Decimal Latitude: 
						<input name="latdecimal" id="latdecimal" type="text" value="<?php echo $unkArray["latdecimal"]; ?>" onchange="javascript:checkLat();">
						<select id="lat_ns" name="lat_ns" onchange="javascript:checkLat();">
							<option id="N" value="N">N</option>
							<option id="S" value="S">S</option>
						</select>
					</div>
					<div>
						Decimal Longitude:
						<input name="longdecimal" id="longdecimal" type="text" value="<?php echo $unkArray["longdecimal"]; ?>" onchange="javascript:checkLong();">
						<select id="long_ew" name="long_ew" onchange="javascript:checkLong();">
							<option id="W" value="W">W</option>
							<option id="E" value="E">E</option>
						</select>
					</div>
					<div>
						Notes: <input name="notes" id="notes" type="text" value="<?php echo $unkArray["notes"]; ?>" size="50">
					</div>
				</fieldset>
			</form>
		</div>
		<div style="margin:20px;">
			<h1>Public Comments</h1>
			<?php 
				$unkDisplayManager->getComments($unkid);
			?>
		</div>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
	
</body>
</html>

<?php

class UnknownDisplayManager{

	private $conn; 
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
 	}
	
 	public function __destruct() {
 		$this->conn->close();
	}

	public function getUnknown($unkid){
		$unkArr = Array();
		$sql = "SELECT t.family, t.sciname, t.rankid, u.photographer, u.owner, u.latdecimal, u.longdecimal, ".
			"u.notes, u.username, ui.url ".
			"FROM unknowns u INNER JOIN unknownimages ui ON u.unkid = ui.unkid ".
			"LEFT JOIN taxa t ON u.tid = t.tid WHERE u.unkid = ".$unkid;
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$unkArr["sciname"] = $row->sciname;
			$unkArr["family"] = $row->family;
			$unkArr["photographer"] = $row->photographer;
			$unkArr["owner"] = $row->owner;
			$unkArr["latdecimal"] = $row->latdecimal;
			$unkArr["longdecimal"] = $row->longdecimal;
			$unkArr["notes"] = $row->notes;
			$unkArr["username"] = $row->username;
			$unkArr["urls"][] = $row->url;
		}
		while($row = $result->fetch_object()){
			$unkArr["urls"][] = $row->url;
		}
		$result->close();
		return $unkArr;
	}
	
	public function getComments($unkid){
		$sql = "SELECT c.comid, c.comment, c.username, c.initialtimestamp ".
			"FROM unknowncomments c ".
			"WHERE c.unkcomid = ".$unkid." ORDER BY c.initialtimestamp desc";
		$result = $conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<div>".$row->comment."</div>";
			echo "<div>Comment by: ".$row->username." - ".$row->initialtimestamp."</div>";
		}
		$result->close();
	}
	
	public function showTaxaList($sciname){
		$sql = "SELECT t.tid, t.sciname ".
			"FROM taxa t WHERE t.rankid = 140 OR t.rankid = 180 ".
			"ORDER BY t.rankid, t.sciname";
		$result = $conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<option value='".$row->tid."'>".$row->sciname.($row->sciname==$sciname?" SELECTED":"")."</option>";
		}
		$result->close();
	}
	
	public function loadUnknown($unkData){
		$sql = "INSERT INTO unknowns (tid, photographer, owner, latdecimal, longdecimal, notes, username) ".
			"VALUES (".$unkData["tid"].",".$unkData["photographer"].",".$unkData["owner"].",".$unkData["latdecimal"].",".
			$unkData["notes"].",".$unkData["username"].")";
		$conn->query($sql);
		$conn->close();
	}
}
?>
