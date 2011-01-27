<?php
error_reporting(E_ALL);
header("Content-Type: text/html; charset=ISO-8859-1");
include_once("dbconnection.php");

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$mapTn = array_key_exists("maptn",$_REQUEST)?$_REQUEST["maptn"]:0;
$mapLarge = array_key_exists("maplarge",$_REQUEST)?$_REQUEST["maplarge"]:0;

$imageMapManagers = new ImageMapManagers();

?>
<html>
<head>
<title>Image Mapper</title>
	<link rel="stylesheet" href="css/main.css" type="text/css" />
</head>

<body>

	<!-- This is inner text! -->
	<div id="innertext">
		<h2>ASU Image Mapper</h2>
		<div>
			Clicking the button below will go through all the images within &#8220;source folder&#8221;, 
			move to &#8220;target folder&#8221;, and map each to the ASU Herbarium Database. Note that the 
			specimen records need to be inserted into the ASU Herbarium database before transferring the images. 
			Each night a script will run that will transfer the images from the ASU database to SEINet.
		</div>
		<?php 
			if($action){
				echo "<fieldset style='margin:20px;'>\n";
				echo "<legend><b>Status</b></legend>\n";
				echo "<ul>\n";
				if($action == "Map Images to Database"){
					$imageMapManagers->mapImages($mapTn,$mapLarge);
					$imageMapManagers->transferToSeinet();
				}
				elseif($action == "Map Images to Database"){
					//$imageMapManagers->transferToSeinet();
				}
				echo "</ul>\n";
				echo "</fieldset>\n";
			}
		?>

		<div style="margin:20px;">
			<form name="imagemappingform" action="asuimagelinker.php" method="get">
				<fieldset>
					<legend><b>Image Mapper Form</b></legend>
					<div>
						<input type="submit" name="action" value="Map Images to Database" />
					</div>
					<div style="margin-left:10px;">
						<input type="checkbox" name="maptn" value="1" CHECKED /> 
						Map Thumbnail Image
					</div>
					<div style="margin-left:10px;">
						<input type="checkbox" name="maplarge" value="1" CHECKED /> 
						Map Large Image
					</div>
				</fieldset>
			</form>
		</div>
		<fieldset style="margin:20px;">
			<legend><b>Parameters</b></legend>
			<div>
				<b>Source Folder:</b> 
				<?php echo $imageMapManagers->getSourcePath();?>
			</div>
			<div>
				<b>Target Folder:</b> 
				<?php echo $imageMapManagers->getTargetBase();?>
			</div>
			<div>
				<a href="logs/">Log Files</a>
			</div>
		</fieldset>
		<!-- 
		<div style="margin:20px;">
			<form name="imagemappingform" action="asuimagelinker.php" method="get">
				<fieldset>
					<legend><b>ASU Herbarium to SEINet Transfer</b></legend>
					<div style="margin-left:10px;">
						Transfer new data from ASU herbarium database to SEINet after image upload 
					</div>
					<div>
						<input type="submit" name="action" value="Transfer to SEINet" />
					</div>
				</fieldset>
			</form>
		</div>
		 -->
	</div>
</body>
</html>

<?php 
class ImageMapManagers{
	
	private $sourcePath = "source/";
	private $targetBase = "target/";
	private $targetUrl = "http://localhost/salix/asuimagelinker/target/";
	private $logBasePath = "logs/";
	private $mapTn = 1;
	private $mapLarge = 1;
	private $tnPixWidth = 200;
	private $webPixWidth = 1300;
	private $largePixWidth = 3168;
	
	private $transferToSeinet = 1;

	private $logFH;
	private $logErrFH;
	private $logPath;
	private $logErrPath;
	private $conn;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		include("asuini.php");
		$this->sourcePath = $sourcePath;
		$this->targetBase = $targetBase;
		$this->targetUrl = $targetUrl;
		$this->logBasePath = $logBasePath;
		$this->mapTn = $mapTn;
		$this->mapLarge = $mapLarge;
		$this->tnPixWidth = $tnPixWidth;
		$this->webPixWidth = $webPixWidth;
		$this->largePixWidth = $largePixWidth;
		
		$this->logPath = $this->logBasePath."log_".date('Ymd').".log";
		$this->logErrPath = $this->logBasePath."logError_".date('Ymd').".log";
	}
	
	function __destruct(){
		$this->conn->close();
	}
	
	public function transferToSeinet(){
		//transfer new data from ASU Herbarium database to SEINet
		set_time_limit(1600);
		echo "<li>Transferring of new records from ASU Herbarium DB to SEINet DB. Note that it might be 5-10 minutes before they refresh in SEINet.</li>";
		$seinetConn = MySQLiConnectionFactory::getCon("write","symbiotaseinet");
		$seinetConn->query("Call ASUDataTransferModified()");
		$seinetConn->query("CALL TransferUploads(1,0)");
		$seinetConn->close();
		echo "<li>Transfer Complete</li>";
	}
	
	public function mapImages($mTn,$mLarge){
		$this->mapTn = $mTn;
		$this->mapLarge = $mLarge;
		//Create log Files
		$this->logFH = fopen($this->logPath, 'a') 
			or die("Can't open file: ".$this->logPath);
		$this->logErrFH = fopen($this->logErrPath, 'a') 
			or die("Can't open file: ".$this->logErrPath);
		fwrite($this->logFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
		fwrite($this->logErrFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
		echo "<li>Starting Upload Process</li>";
		
		set_time_limit(800);
		$this->evaluateFolder($this->sourcePath);

		echo "<li>Image upload complete</li>";
		fwrite($this->logFH, "Image upload complete\n");
		fwrite($this->logFH, "----------------------------\n\n");
		fwrite($this->logErrFH, "----------------------------\n\n");
		fclose($this->logFH);
		fclose($this->logErrFH);
	}
	
	private function evaluateFolder($dirPath){
		if($handle = opendir($dirPath)){
			$cnt = 0;
			while(false !== ($file = readdir($handle)) && $cnt < 150){
        		if($file != "." && $file != ".."){
        			if(is_file($dirPath.$file)){
						if(stripos($file,".jpg")){
							$targetPath = $this->targetBase;
							if(substr($targetPath,-1) != "/" && substr($targetPath,-1) != "\\"){
								$targetPath .= "/";
							}
							$folderName = "";
							if(preg_match('/^\D*(\d+)/',$file,$matchArr)){
								$num = $matchArr[1];
								if($num > 1000){
									$folderName = "spec".substr($num,0,strlen($num)-3)."/";
								}
								else{
									$folderName = "spec0000/";
								}
							}
							else{
								$folderName = "spec0000/";
							}
							if(!file_exists($targetPath.$folderName)){
								mkdir($targetPath.$folderName);
							}
							$targetPath .= $folderName;
							if(file_exists($targetPath.$file)){
	        					unlink($targetPath.$file);
	        					unlink($targetPath.substr($file,0,strlen($file)-4)."tn.jpg");
	        					unlink($targetPath.substr($file,0,strlen($file)-4)."lg.jpg");
							}
							list($width, $height) = getimagesize($dirPath.$file);
							echo "<li>Start loading: ".$file."</li>";
							fwrite($this->logFH, "Start loading: ".$file."\n");
							//Create web image
							$webImgCreated = false;
							if($width > ($this->webPixWidth*1.2)){
								$webImgCreated = $this->resizeImage($file,$targetPath.$file,$this->webPixWidth,round($this->webPixWidth*$height/$width),$width,$height);
							}
							else{
								$webImgCreated = copy($dirPath.$file,$targetPath.$file);
							}
							if($webImgCreated){
	        					//echo "<li style='margin-left:10px;'>Web image copied to target folder</li>";
								fwrite($this->logFH, "\tWeb image copied to target folder\n");
								$tnUrl = "";$oUrl = "";
								//Create Large Image
								if($this->mapLarge && $width > ($this->webPixWidth*1.2)){
									if($width < ($this->largePixWidth*1.2)){
										if(copy($dirPath.$file,$targetPath.substr($file,0,strlen($file)-4)."lg.jpg")){
											$oUrl = substr($file,0,strlen($file)-4)."lg.jpg";
										}
									}
									else{
										if($this->resizeImage($file,$targetPath.substr($file,0,strlen($file)-4)."lg.jpg",$this->largePixWidth,round($this->largePixWidth*$height/$width),$width,$height)){
											$oUrl = substr($file,0,strlen($file)-4)."lg.jpg";
										}
									}
								}
								//Create Thumbnail Image
								if($this->mapTn){
									if($this->resizeImage($file,$targetPath.substr($file,0,strlen($file)-4)."tn.jpg",$this->tnPixWidth,round($this->tnPixWidth*$height/$width),$width,$height)){
										$tnUrl = substr($file,0,strlen($file)-4)."tn.jpg";
									}
								}
								if($this->insertImageInDB($folderName.$file,$tnUrl,$oUrl)){
									if(file_exists($this->sourcePath.$file)) unlink($this->sourcePath.$file);
									echo "<li>Success!</li>";
									fwrite($this->logFH, "\tSuccess!\n");
								}
							}
						}
						else{
        					echo "<li style='margin-left:10px;'><b>Error:</b> File is not a jpg file: $targetName</li>";
							fwrite($this->logErrFH, "Error: File skipped: ".$file." is not a jpg file\n");
							fwrite($this->logFH, "Error: File skipped, ".$file." is not a jpg file\n");
						}
					}
					elseif(is_dir($dirPath.$file)){
						$this->evaluateFolder($dirPath.$file."/");
					}
        		}
        		$cnt++;
			}
		}
   		closedir($handle);
	}
	
	private function resizeImage($sourceName, $targetPath, $newWidth, $newHeight, $oldWidth, $oldHeight){
		$status = false;
       	$sourceImg = imagecreatefromjpeg($this->sourcePath.$sourceName);
   		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$oldWidth,$oldHeight);
        if(imagejpeg($tmpImg, $targetPath)){
        	$status = true;
        }
        else{
			fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetPath</li>";
        }
		imagedestroy($tmpImg);
		return $status;
	}

	private function insertImageInDB($webFileName,$tnUrl,$oUrl){
		$status = false;
        //echo "<li style='margin-left:20px;'>About to load record into database</li>";
		fwrite($this->logFH, "\tAbout to load record into database\n");
		if(preg_match('/\/(ASU\d{7})\.(jpg|JPG)/',$webFileName,$matchArr)){
			$barcode = $matchArr[1];
			//Get dbsn for target record
			$dbsn = 0;
			$imgUrls = Array();
			$sqlStr = "SELECT s.dbsn, p.hyperlink FROM tbl_specimens s LEFT JOIN tbl_photos p ON s.dbsn = p.dbsn ".
				"WHERE s.barcode = '".$barcode."'";
			$rs = $this->conn->query($sqlStr);
			while($row = $rs->fetch_object()){
				$dbsn = $row->dbsn;
				$imgUrls[] = $row->hyperlink;
			}
			if($dbsn){
				if(!in_array($this->targetUrl.$webFileName,$imgUrls)){
					$sql = "INSERT tbl_photos(dbsn,hyperlink";
					if($tnUrl) $sql .= ",thumbnailurl";
					if($oUrl) $sql .= ",originalurl"; 
					$sql .= ") VALUES (".$dbsn.", '".$this->targetUrl.$webFileName."' ";
					if($tnUrl) $sql .= ", '".$this->targetUrl.$tnUrl."' ";
					if($oUrl) $sql .= ", '".$this->targetUrl.$oUrl."' ";
					$sql .= ")";
					$status = $this->conn->query($sql);
					if($status){
				        //echo "<li style='margin-left:20px;'>Record successfully loaded into database</li>";
						fwrite($this->logFH, "\tRecord successfully loaded into database\n");
					}
					else{
						fwrite($this->logFH, "\tError: unable to load record into database\n");
						fwrite($this->logErrFH, "\tError: Unable to load image record into database. ".$this->conn->error." SQL: ".$sql."\n");
			        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to load image record into database. ".$this->conn->error."</li>";
					}
				}
				else{
					fwrite($this->logFH, '\Notice: '.$webFileName.' image record already mapped in database\n');
					fwrite($this->logErrFH, '\Notice: '.$webFileName.' image record already mapped in database\n');
			        echo "<li style='margin-left:20px;'><b>Notice:</b> ".$webFileName." image record already mapped in database ".$this->conn->error."</li>";
			        $status = true;
				}
			}
			else{
				fwrite($this->logFH, "\tError: unable to load record. Specimen ".$barcode." not in ASU herbarium database. \n");
				fwrite($this->logErrFH, "\tError: Unable to load image record into database. Specimen ".$barcode." not in ASU herbarium database.\n");
	        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to load image record into database. Specimen ".$barcode." not in ASU herbarium database. </li>";
			}
		}
		else{
			fwrite($this->logFH, "\tERROR: unable to extract barcode from file name (".$webFileName."). \n");
			fwrite($this->logErrFH, "\tERROR: unable to extract barcode from file name (".$webFileName."). \n");
        	echo "<li style='margin-left:20px;'><b>ERROR:</b> unable to extract barcode from file name (".$webFileName."). </li>";
		}
		return $status;
	}
	
	public function getSourcePath(){
		return $this->sourcePath;
	}
	
	public function getTargetBase(){
		return $this->targetBase;
	}
	
	public function getLogPath(){
		return $this->logPath;
	}

	public function getErrLogPath(){
		return $this->logErrPath;
	}
}
?>