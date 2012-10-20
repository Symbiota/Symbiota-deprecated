<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

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
	include($serverRoot.'/header.php');
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
	include($serverRoot.'/footer.php');
	?>
	
</body>
</html>

<?php 
class BuildThumbnails{
	
	private $rootPathBase = "";
	private $rootPathTn = "";
	private $pathTnFrag = "";
	private $urlPath = "";
	private $conn;
	private $thumbnailArr = Array();

	private $tnPixWidth = 200;
	private $tnPixWidthMax = 250;
	
	private $verbose = 1;
	
	function __construct() {
		set_time_limit(2000);
		ini_set('memory_limit', '512M');
		$this->rootPathBase = $GLOBALS["imageRootPath"];
		if(substr($this->rootPathBase,-1) != "/") $this->rootPathBase .= "/";  
		$this->urlPath = $GLOBALS["imageRootUrl"];
		if(!$this->urlPath) exit('imageRootUrl is not set');
		if(substr($this->urlPath,-1) != "/") $this->urlPath .= "/";
		$this->conn = MySQLiConnectionFactory::getCon("write");
		
		if(array_key_exists('imgTnWidth',$GLOBALS)){
			$this->tnPixWidth = $GLOBALS['imgTnWidth'];
			$this->tnPixWidthMax = $this->tnPixWidth*1.25; 
		}
	}

	function __destruct(){
		$this->conn->close();
	}
	
	public function buildThumbnails(){
		echo '<div style="font-weight:bold;">Working on internal and external images</div>';
		echo '<ol>';
		//Hunt for images on the main image server yet for some reason lack thumbnails
		$sql = 'SELECT ti.imgid, ti.url FROM images ti '.
			'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "") ';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$url = trim($row->url);
			if($this->verbose) echo '<li>Building thumbnail for image: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">'.$imgId.'</a>... ';
			ob_flush();
			flush();
			//if there are spaces in the file name, fix it
			if(strpos($url," ") || strpos($url,"%20")){
				$url = $this->removeSpacesFromFileName($imgId,$url);
			}
			$statusStr = 'failed';
			if($this->createThumbnail($imgId,$url)) $statusStr = 'done!';
			if($this->verbose) echo $statusStr.'</li>';
		}
		echo '</ol>';
		echo '<div style="font-weight:bold;">Finished!</div>';
	}
	
	private function createThumbnail($imgId,$imgUrl){
		$status = false;
		if($imgUrl && $imgId){
			$filePath = "";
			$newThumbnailUrl = "";
			$newThumbnailPath = "";
			if(strpos($imgUrl,$this->urlPath) !== false){
				$filePath = str_replace($this->urlPath,$this->rootPathBase,$imgUrl);
				$newThumbnailUrl = str_ireplace(".jpg","_tn.jpg",$imgUrl);
				$newThumbnailPath = str_replace($this->urlPath,$this->rootPathBase,$newThumbnailUrl);
			}
			else{
				$filePath = $imgUrl;
				if(!$this->pathTnFrag){
					$this->pathTnFrag = 'thumbnails/';
					if(!is_dir($this->rootPathBase.$this->pathTnFrag)){
						if(!mkdir($this->rootPathBase.$this->pathTnFrag)) return "";
					}
					$this->pathTnFrag .= date("Ym").'/';
					if(!is_dir($this->rootPathBase.$this->pathTnFrag)){
						if(!mkdir($this->rootPathBase.$this->pathTnFrag)) return "";
					}
				}
				$fileName = str_ireplace(".jpg","_tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")+1));
				$newThumbnailUrl = $this->urlPath.$this->pathTnFrag.$fileName;
				$newThumbnailPath = $this->rootPathBase.$this->pathTnFrag.$fileName;
			}
			if(substr($filePath,0,1) == '/'){
				if(array_key_exists('imageDomain',$GLOBALS) && $GLOBALS['imageDomain']){
					$filePath = $GLOBALS['imageDomain'].$filePath;
				}
				else{
					if(file_exists(str_replace($GLOBALS['imageRootUrl'],$GLOBALS['imageRootPath'],$filePath))){
						$filePath = str_replace($GLOBALS['imageRootUrl'],$GLOBALS['imageRootPath'],$filePath);
					}
					else{
						$filePath = 'http://'.$_SERVER['HTTP_HOST'].$filePath;
					}
				}
			}
			if(file_exists($filePath) || $this->url_exists($filePath)){
				if(!file_exists($newThumbnailPath)){
					list($sourceWidth, $sourceHeight, $imageType) = getimagesize($filePath);
		        	$newWidth = $this->tnPixWidth;
		        	$newHeight = round($sourceHeight*($newWidth/$sourceWidth));
		        	if($newHeight > $this->tnPixWidthMax){
		        		$newHeight = $this->tnPixWidthMax;
		        		$newWidth = round($sourceWidth*($this->tnPixWidthMax/$sourceHeight));
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
				        		echo "<li style='margin-left:5px;color:red;'>Failed to write GIF thumbnail: $newThumbnailPath</li>";
				        	}
				        	break;
				        case 2: 
				        	if(!imagejpeg($tmpImg, $newThumbnailPath)){
				        		echo "<li style='margin-left:5px;color:red;'>Failed to write JPG thumbnail: $newThumbnailPath</li>";
				        	}
				        	break; // best quality
				        case 3: 
				        	if(!imagepng($tmpImg, $newThumbnailPath, 0)){
				        		echo "<li style='margin-left:5px;color:red;'>Failed to write PNG thumbnail: $newThumbnailPath</li>";
				        	}
				        	break; // no compression
				    }
				    imagedestroy($tmpImg);
				}
				else{
					if($this->verbose) echo '<div style="margin-left:10px;">Bad target path: '.$newThumbnailPath.'</div>';
				}
			    
			    if(file_exists($newThumbnailPath)){
				    //Insert thumbnail path into database
			    	$sql = "UPDATE images ti SET ti.thumbnailurl = '".$newThumbnailUrl."' WHERE ti.imgid = ".$imgId;
				    $this->conn->query($sql);
				    $status = true;
			    }
			}
			else{
				if($this->verbose) echo '<div style="margin-left:10px;">Bad source path: '.$filePath.'</div>';
			}
		}
	    return $status;
	}
	
	private function removeSpacesFromFileName($imgId, $url){
		$imgUrl = str_replace("%20"," ",$url);
		$filePath = str_replace($this->urlPath,$this->rootPathBase,$imgUrl);
		$newPath = str_replace(" ","_", $filePath);
		$newPath = str_replace(Array("(",")"),"",$newPath);
		$newPath = str_replace("JPG","jpg",$newPath);
		$newUrl = str_replace($this->rootPathBase,$this->urlPath,$newPath);
		if($filePath != $newPath){
			if(!file_exists($newPath)){
				if(rename($filePath, $newPath)){
			    	$sql = "UPDATE images ti SET ti.url = '".$newUrl."' WHERE ti.imgid = ".$imgId;
				    if($this->conn->query($sql)){
					    if($this->verbose) echo "<li style='margin-left:5px;'><b>Image file ($imgId) renamed</b> from $imgUrl to $newUrl</li>";
					    return $newUrl;
				    }
				    else{
				    	echo "<li style='margin-left:5px;color:red;'><b>ERROR:</b> Image file ($imgId) rename successful but database update failed. Please repair.</li>";
				    }
				}
			    else{
			    	echo "<li style='margin-left:5px;color:red;'><b>ERROR:</b> Unable to rename image file $filePath (imgid = $imgId)</li>";
			    }
			}
			else{
			    echo "<li style='margin-left:5px;color:red;'><b>ERROR:</b> Unable t rename file. New file already exists: $newPath</li>";
			}
		}
		return "";
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