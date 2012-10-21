<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$imgManager = new ThumbnailBuilder();
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
		<div style="margin:10px;">
			<fieldset>
				<legend><b>Thumbnail Builder</b></legend>
				<div style="margin:10px;">
					<b>Images w/o thumbnails:</b> <?php echo $imgManager->getMissingTnCount(); ?>
				</div>
				<div style="margin:10px;">
					This function will build thumbnail images for all image records that have NULL values for the thumbnail field.
				</div>
				<div style="margin:15px;">
					<?php 
					if($action == "Build Thumbnails"){
						echo '<div style="font-weight:bold;">Working on internal and external thumbnail images</div>';
						echo '<ol>';
						$imgManager->buildThumbnailImages(); 
						echo '</ol>';
						echo '<div style="font-weight:bold;">Finished!</div>';
					}
					?>
				</div>
				<div style="margin:10px;">
					<form name="tnbuilderform" action="thumbnailbuilder.php" method="post">
						<input type="submit" name="action" value="Build Thumbnails">
					</form>
				</div>
			</fieldset>
		</div>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
	
</body>
</html>

<?php 
class ThumbnailBuilder{
	
	private $rootPathBase = "";
	private $urlPath = "";
	private $conn;

	private $tnPixWidth = 200;
	private $webPixWidth = 1600;
	private $imgFileSizeLimit = 500000;

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
		}
		if(array_key_exists('imgWebWidth',$GLOBALS)){
			$this->webPixWidth = $GLOBALS['imgWebWidth'];
		}
		if(array_key_exists('imgFileSizeLimit',$GLOBALS)){
			$this->imgFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
		}
	}

	function __destruct(){
		$this->conn->close();
	}

	public function getMissingTnCount(){
		$tnCnt = 0;
		$sql = 'SELECT count(ti.imgid) AS tnCnt FROM images ti '.
			'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "") AND ti.url != "empty" ';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$tnCnt = $row->tnCnt;
		}
		$result->free();
		return $tnCnt;
	}

	public function buildThumbnailImages(){
		$sql = 'SELECT ti.imgid, ti.url, ti.originalurl FROM images ti '.
			'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "") AND imgid = 191668'; 
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$statusStr = 'ERROR';
			$webIsEmpty = false;
			$imgId = $row->imgid;
			if($row->url == 'empty' && $row->originalurl){
				$imgUrl = trim($row->originalurl);
				$webIsEmpty = true;
			}
			else{
				$imgUrl = trim($row->url);
			}
			if($this->verbose) echo '<li>Building thumbnail: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">#'.$imgId.'</a>... ';
			ob_flush();
			flush();
			//if there are spaces in the file name, fix it
			if(strpos($imgUrl," ") || strpos($imgUrl,"%20")){
				$imgUrl = $this->removeSpacesFromThumbnail($imgId,$imgUrl);
			}
			//Get source path
			$sourcePath = $imgUrl;
			if(substr($imgUrl,0,1) == '/'){
				if(array_key_exists('imageDomain',$GLOBALS) && $GLOBALS['imageDomain']){
					$sourcePath = $GLOBALS['imageDomain'].$imgUrl;
				}
				else{
					if(file_exists(str_replace($this->urlPath,$this->rootPathBase,$imgUrl))){
						$sourcePath = str_replace($this->urlPath,$this->rootPathBase,$imgUrl);
					}
					else{
						$sourcePath = 'http://'.$_SERVER['HTTP_HOST'].$imgUrl;
					}
				}
			}
			//Create target path
			$targetPath = '';
			$targetUrl = '';
			if(substr($sourcePath,0,1) == '/'){
				$targetPath = substr($sourcePath,0,strrpos($sourcePath,'/'));
				$targetUrl = str_replace($this->rootPathBase,$this->urlPath,$targetPath);
			}
			else{
				$targetPath = $this->rootPathBase.'misc/';
				if(!is_dir($targetPath)){
					if(!mkdir($targetPath)){
						echo '<li>FATAL ERROR => unable to create target folder: '.$targetPath.'</li>';
						exit("FATAL ERROR => unable to create target folder: ".$targetPath);
					}
				}
				$targetPath .= date("Ym").'/';
				if(!is_dir($targetPath)){
					if(!mkdir($targetPath)){
						echo '<li>FATAL ERROR => unable to create target folder: '.$targetPath.'</li>';
						exit("FATAL ERROR => unable to create target folder: ".$targetPath);
					}
				}
				$targetUrl = $this->urlPath.'misc/'.date("Ym").'/';
			}
			
			//Create file names
			$fileName = substr($sourcePath,strrpos($sourcePath,'/')+1);
			
			if(file_exists($sourcePath) || $this->url_exists($sourcePath)){
				if(is_dir($targetPath)){
					//Get image statistics
					list($sourceWidth, $sourceHeight) = getimagesize($sourcePath);

					$sourceImg = imagecreatefromjpeg($sourcePath);  

					//Create thumbnail
					$tnFileName = str_ireplace(".jpg","_tn.jpg",$fileName);
					$newTnHeight = round($sourceHeight*($this->tnPixWidth/$sourceWidth));
		        	
		    		$tmpTnImg = imagecreatetruecolor($this->tnPixWidth,$newTnHeight);
					imagecopyresampled($tmpTnImg,$sourceImg,0,0,0,0,$this->tnPixWidth, $newTnHeight,$sourceWidth,$sourceHeight);
		        	if(!imagejpeg($tmpTnImg, $targetPath.$tnFileName)){
		        		echo "<li style='margin-left:5px;color:red;'>Failed to write JPG: $targetPath.$tnFileName</li>";
		        	}
				    imagedestroy($tmpTnImg);
				    
				    //Create large image is too large
				    $lgFileName = '';
				    $webFileName = '';
				    $fileSize = 0;
				    if(!$webIsEmpty){
					    if(strtolower(substr($sourcePath,0,7)) == 'http://'){
					    	$fileSize = $this->getRemoteSize($sourcePath);
					    }
					    else{
					    	$fileSize = filesize($sourcePath);
					    }
				    }
				    if($webIsEmpty || $fileSize > $this->imgFileSizeLimit){
			    		$lgFileName = $imgUrl;
			    		$webFileName = str_ireplace(".jpg","_web.jpg",$fileName);

			    		$newWebHeight = round($sourceHeight*($this->webPixWidth/$sourceWidth));
			        	
			    		$tmpWebImg = imagecreatetruecolor($this->webPixWidth,$newWebHeight);
						imagecopyresampled($tmpWebImg,$sourceImg,0,0,0,0,$this->webPixWidth, $newWebHeight,$sourceWidth,$sourceHeight);
			        	if(!imagejpeg($tmpWebImg, $targetPath.$webFileName)){
			        		if($webIsEmpty){
			        			$webFileName = $imgUrl;
			        		}
			        		else{
								$webFileName = '';
			        		}
			        		$lgFileName = '';
			        		echo "<div style='margin-left:10px;color:red;'>Failed to write JPG: $targetPath.$webFileName</div>";
			        	}
					    imagedestroy($tmpWebImg);
				    }

				    //Final cleanup
				    imagedestroy($sourceImg);
				
				    if(file_exists($targetPath.$tnFileName)){
					    //Insert urls into database
					    $webFullUrl = '';
				    	if($webFileName && $webFileName != $fileName){
				    		if(strtolower(substr($webFileName,0,4)) != "http") $webFullUrl = $targetUrl;
				    		$webFullUrl .= $webFileName;
				    	}
					    $lgFullUrl = '';
					    if($lgFileName){
				    		if(strtolower(substr($lgFileName,0,4)) != "http") $lgFullUrl = $targetUrl;
					    	$lgFullUrl .= $lgFileName;
					    }

				    	$sql = 'UPDATE images ti SET ti.thumbnailurl = "'.$targetUrl.$tnFileName.'" ';
				    	if($webFullUrl){
				    		$sql .= ',url = "'.$webFullUrl.'" ';
				    	}
				    	if($lgFullUrl){
				    		$sql .= ',originalurl = "'.$lgFullUrl.'" ';
				    	}
				    	
				    	$sql .= "WHERE ti.imgid = ".$imgId;
				    	//echo $sql;
					    $this->conn->query($sql);
					    $statusStr = 'Done!';
				    }
				}
				else{
					if($this->verbose) echo '<div style="margin-left:10px;">Bad target path: '.$targetPath.'</div>';
				}
			}
			else{
				if($this->verbose) echo '<div style="margin-left:10px;">Bad source path: '.$sourcePath.'</div>';
			}

			if($this->verbose) echo $statusStr.'</li>';
		}
	}

	private function removeSpacesFromThumbnail($imgId, $url){
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
	
	private function getRemoteSize($remoteFile){
		$ch = curl_init($remoteFile);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		if($data === false) {
			return 0;
		}
		
		$contentLength = 0;
		if(preg_match('/Content-Length: (\d+)/', $data, $matches)) {
		  $contentLength = (int)$matches[1];
		}
		return $contentLength;
	}
}
?>