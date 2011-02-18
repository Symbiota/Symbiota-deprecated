<?php
/*
 * Created on Jun 11, 2006
 * By E.E. Gilbert
 */
 //error_reporting(0);
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/ImageDetailManager.php');
 Header("Content-Type: text/html; charset=".$charset);
 
 $imgId = $_REQUEST["imgid"]; 
 $imgManager = new ImageDetailManager();
 
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
						echo "<div>";
						if($imgArr["copyright"]){
							if(stripos($imgArr["copyright"],"http") === 0){
								echo "<a href='".$imgArr["copyright"]."'>Copyright Details</a>";
							}
							else{
								echo $imgArr["copyright"];
							}
						}
						else{
							echo "<a href='imageusagepolicy.php'>Copyright Details</a>";
						}
						echo "</div>";
						if($imgArr["sourceurl"]) echo "<div><a href='".$imgArr["sourceurl"]."'>Source Webpage</a></div>";
						if($imgArr["occid"]) echo "<div><a href='../collections/individual/index.php?occid=".$imgArr["occid"]."'>Display Specimen Details</a></div>";
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

