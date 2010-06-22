<?php
error_reporting(E_ALL);
header("Content-Type: text/html; charset=ISO-8859-1");
include_once("../../util/dbconnection.php");
include_once("../../util/symbini.php");

$buildThumbnailsObj = new BuildThumbnails();
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Thumbnail Builder</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($imagelib_misc_buildthumbnailsMenu)?$imagelib_misc_buildthumbnailsMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($imagelib_misc_buildthumbnailsCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_misc_buildthumbnailsCrumbs;
		echo " <b>Build Thumbnails</b>";
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Thumbnail Builder</h1>
		<div style="margin:10px;">
			Clicking the button below will build thumbnail images for all image records that have NULL values for the thumbnail field.
		</div>

		<?php 
			if($action){
				$buildThumbnailsObj->buildThumbnails();
			}
		?>
		<div style="margin:10px;">
			<form name="startprocessform" action="buildthumbnails.php" method="post">
				<input type="submit" name="action" value="Start Thumbnail Creation">
			</form>
		</div>
	</div>
	<?php 
	include($serverRoot."/util/footer.php");
	?>
	
</body>
</html>

<?php 
class BuildThumbnails{
	
	private $rootPath = "";
	private $urlPath = "";
	private $conn;
	private $thumbnailArr = Array();

	function __construct() {
		set_time_limit(200);
		$this->rootPath = $GLOBALS["imageRootPath"];
		if(substr($this->rootPath,-1) != "/") $this->rootPath .= "/";  
		$this->urlPath = $GLOBALS["imageRootUrl"];
		if(substr($this->urlPath,-1) != "/") $this->urlPath .= "/";  
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		$this->conn->close();
	}
	
	public function buildThumbnails(){
		$sql = "SELECT ti.imgid, trim(ti.url) AS url FROM images ti ".
			"WHERE ti.thumbnailurl IS NULL AND ti.url LIKE '".$this->urlPath."%' ";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$url = $row->url;
			//if there are spaces in the file name, fix it
			if(strpos($url," ") || strpos($url,"%20")){
				$url = $this->removeSpacesFromFileName($imgId, $url);
			}
			$this->createThumbnail($url, $imgId);
		}
	}
	
	private function removeSpacesFromFileName($imgId, $url){
		$imgUrl = str_replace("%20"," ",$url);
		$filePath = str_replace($this->urlPath,$this->rootPath,$imgUrl);
		$newPath = str_replace(" ","_", $filePath);
		$newPath = str_replace(Array("(",")"),"",$newPath);
		$newPath = str_replace("JPG","jpg",$newPath);
		$newUrl = str_replace($this->rootPath,$this->urlPath,$newPath);
		if($filePath != $newPath){
			if(!file_exists($newPath)){
				if(rename($filePath, $newPath)){
			    	$sql = "UPDATE images ti SET ti.url = '".$newUrl."' WHERE ti.imgid = ".$imgId;
				    if($this->conn->query($sql)){
					    echo "<div style='margin:5px;'><b>Image file ($imgId) renamed</b> from $imgUrl to $newUrl</div>";
					    return $newUrl;
				    }
				    else{
				    	echo "<div style='margin:5px;'><b>ERROR:</b> Image file ($imgId) rename successful but database update failed. Please repair.</div>";
				    }
				}
			    else{
			    	echo "<div style='margin:5px;'><b>ERROR:</b> Unable to rename image file $filePath (imgid = $imgId)</div>";
			    }
			}
			else{
			    echo "<div style='margin:5px;'><b>ERROR:</b> Unable t rename file. New file already exists: $newPath</div>";
			}
		}
		return "";
	}
	
	private function createThumbnail($imgUrl, $imgId){
		if($imgUrl && $imgId){
			$filePath = "";
			$newThumbnailUrl = "";
			$newThumbnailPath = "";
			if(substr($imgUrl,0,7) == "http://"){
				$filePath = $imgUrl;
				if(!is_dir($this->rootPath."misc_thumbnails/")){
					if(!mkdir($this->rootPath."misc_thumbnails/")) return "";
				}
				$fileName = str_ireplace(".jpg","_tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				$newThumbnailPath = $this->rootPath."misc_thumbnails/".$fileName;
				$newThumbnailUrl = $this->urlPath."misc_thumbnails/".$fileName;
			}
			elseif(substr($imgUrl,0,strlen($this->urlPath)) == $this->urlPath){
				$filePath = str_replace($this->urlPath,$this->rootPath,$imgUrl);
				$newThumbnailUrl = str_ireplace(".jpg","_tn.jpg",$imgUrl);
				$newThumbnailPath = str_replace($this->urlPath,$this->rootPath,$newThumbnailUrl);
			}

			$idealWidth = 200;
			$maxHeight = 250;
			if(file_exists($filePath) || $this->url_exists($filePath)){
				if(!file_exists($newThumbnailPath)){
		        	list($sourceWidth, $sourceHeight, $imageType) = getimagesize($filePath);
		        	$newWidth = $idealWidth;
		        	$newHeight = round($sourceHeight*($idealWidth/$sourceWidth));
		        	if($newHeight > $maxHeight){
		        		$newHeight = $maxHeight;
		        		$newWidth = round($sourceWidth*($maxHeight/$sourceHeight));
		        	}
		        	
				    switch ($imageType){
				        case 1: 
				        	$sourceImg = imagecreatefromgif($filePath); 
				        	break;
				        case 2: 
				        	$sourceImg = imagecreatefromjpeg($filePath);  
				        	break;
				        case 3: 
				        	$sourceImg = imagecreatefrompng($filePath); 
				        	break;
				        default: return '';  break;
				    }
		        	
		    		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		
				    /* Check if this image is PNG or GIF to preserve its transparency */
				    if(($imageType == 1) || ($imageType==3)){
				        imagealphablending($tmpImg, false);
				        imagesavealpha($tmpImg,true);
				        $transparent = imagecolorallocatealpha($tmpImg, 255, 255, 255, 127);
				        imagefilledrectangle($tmpImg, 0, 0, $newWidth, $newHeight, $transparent);
				    }
					imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);
		
					switch ($imageType){
				        case 1: 
				        	if(!imagegif($tmpImg,$newThumbnailPath)){
				        		echo "<div style='margin:5px;'>Failed to write GIF thumbnail: $newThumbnailPath</div>";
				        	}
				        	break;
				        case 2: 
				        	if(!imagejpeg($tmpImg, $newThumbnailPath, 50)){
				        		echo "<div style='margin:5px;'>Failed to write JPG thumbnail: $newThumbnailPath</div>";
				        	}
				        	break; // best quality
				        case 3: 
				        	if(!imagepng($tmpImg, $newThumbnailPath, 0)){
				        		echo "<div style='margin:5px;'>Failed to write PNG thumbnail: $newThumbnailPath</div>";
				        	}
				        	break; // no compression
				    }
				    imagedestroy($tmpImg);
				}
			    
			    if(file_exists($newThumbnailPath)){
				    //Insert thumbnail path into database
			    	$sql = "UPDATE images ti SET ti.thumbnailurl = '".$newThumbnailUrl."' WHERE ti.imgid = ".$imgId;
				    $this->conn->query($sql);
				    echo "<div style='margin:5px;'><b>Thumbnail Created:</b> $imgId - $newThumbnailUrl</div>";
			    }
			}
		}
	}
	
	private function url_exists($url) {
	    // Version 4.x supported
	    $handle   = curl_init($url);
	    if (false === $handle)
	    {
	        return false;
	    }
	    curl_setopt($handle, CURLOPT_HEADER, false);
	    curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
	    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox   
	    curl_setopt($handle, CURLOPT_NOBODY, true);
	    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
	    $connectable = curl_exec($handle);
	    curl_close($handle);  
	    return $connectable;
	}	
}
?>