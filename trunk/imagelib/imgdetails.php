<?php
/*
 * Created on Jun 11, 2006
 * By E.E. Gilbert
 */
 //error_reporting(0);
 include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
 Header("Content-Type: text/html; charset=".$charset);
 
 $imgId = $_REQUEST["imgid"]; 
 $imgManager = new ImgManager();
 
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<meta name="keywords" content="<?php echo $spDisplay; ?>" />
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<link rel="stylesheet" href="../css/speciesprofile.css" type="text/css"/>
	<title><?php echo $defaultTitle." Image Details: #".$imgId; ?></title>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_imgdetailsMenu)?$taxa_imgdetailsMenu:false);
include($serverRoot.'/header.php');
if(isset($taxa_imgdetailsCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_imgdetailsCrumbs;
	echo " <b>Image #$imgId</b>";
	echo "</div>";
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php $imgArr = $imgManager->getImageMetadata($imgId); ?>
		<table>
			<tr>
				<td style="width:55%;text-align:center;padding:20px;">
					<?php 
						$imgUrl = $imgArr["url"];
						$origUrl = $imgArr["originalurl"];
						if(array_key_exists("imageDomain",$GLOBALS)){
							if(substr($imgUrl,0,1)=="/"){
								$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
							}
							if($origUrl && substr($origUrl,0,1)=="/"){
								$origUrl = $GLOBALS["imageDomain"].$origUrl;
							}
						}
					?>
					<a href="<?php echo $imgUrl;?>">
						<img src="<?php echo $imgUrl;?>" style="width:90%;" />
					</a>
					<?php 
					if($origUrl){
						echo "<div><a href='".$origUrl."'>Click on Image to Enlarge</a></div>";
					}
					?>
				</td>
				<td style="align:left;padding:100px 10px 10px 10px;">
					<?php 
						if($imgArr["caption"]) echo "<div><b>Caption:</b> ".$imgArr["caption"]."</div>";
						if($imgArr["photographer"]) echo "<div><b>Photographer:</b> ".$imgArr["photographer"]."</div>";
						if($imgArr["owner"]) echo "<div><b>Manager:</b> ".$imgArr["owner"]."</div>";
						if($imgArr["locality"]) echo "<div><b>Locality:</b> ".$imgArr["locality"]."</div>";
						if($imgArr["notes"]) echo "<div><b>Notes:</b> ".$imgArr["notes"]."</div>";
						if($imgArr["copyright"]) echo "<div><b>Copyright:</b> ".$imgArr["copyright"]."</div>";
						if($imgArr["sourceurl"]) echo "<div><a href='".$imgArr["sourceurl"]."'>Source Webpage</a></div>";
						if($imgArr["occid"]) echo "<div><a href='../collections/individual/individual.php?occid=".$imgArr["occid"]."'>Display Specimen Details</a></div>";
						echo "<div><a href='".$imgUrl."'>Open Medium Sized Image</a></div>";
						if($origUrl) echo "<div><a href='".$origUrl."'>Open Large Image</a></div>";
					?>
				</td>
			</tr>
		</table>
	</div>

<?php 
include($serverRoot.'/footer.php');

?>
</body>
</html>

<?php 

class ImgManager{
	
	private $conn;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
 	
	public function getImageMetadata($imgId){
		$retArr = Array();
		$sql = "SELECT ti.imgid, ti.url, ti.thumbnailurl, ti.originalurl, ".
			"IFNULL(ti.photographer,CONCAT_WS(' ',u.firstname,u.lastname)) AS photographer, ".
			"ti.caption, ti.owner, ti.sourceurl, ti.copyright, ti.locality, ti.notes, ti.occid ".
			"FROM images ti LEFT JOIN users u ON ti.photographeruid = u.uid ".
			"WHERE ti.imgid = ".$imgId;
		//echo "<div>$sql</div>";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$retArr["url"] = $row->url;
			$retArr["originalurl"] = $row->originalurl;
			$retArr["photographer"] = $row->photographer;
			$retArr["caption"] = $row->caption;
			$retArr["owner"] = $row->owner;
			$retArr["sourceurl"] = $row->sourceurl;
			$retArr["copyright"] = $row->copyright;
			$retArr["locality"] = $row->locality;
			$retArr["notes"] = $row->notes;
			$retArr["occid"] = $row->occid;
		}
		return $retArr;
	}
}
?>