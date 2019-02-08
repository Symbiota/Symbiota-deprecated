<?php
/*
 * Script that navigates through submitted image ids (imgid) and removes image records from database and moves physical to an archive directory
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

$collid = (array_key_exists('collid', $_POST)?$_POST['collid']:'');
$imgidStart = (array_key_exists('imgidstart', $_POST)?$_POST['imgidstart']:0);
$limit = (array_key_exists('limit', $_POST)?$_POST['limit']:10000);
$archiveImages = (array_key_exists('archiveimg', $_POST)?$_POST['archiveimg']:0);
$imgidStr = (array_key_exists('imgidstr', $_POST)?$_POST['imgidstr']:'');
$submit = (array_key_exists('submitbutton', $_POST)?$_POST['submitbutton']:'');

$toolManager = new MediaTools();
$imgidEnd = 0;
if($IS_ADMIN){
	if($submit == 'Process Images'){
		if($archiveImages) $toolManager->setArchiveImages($archiveImages);
		$toolManager->setImgidArr($imgidStr);
		$imgidEnd = $toolManager->archiveImageFiles($imgidStart, $limit);
	}
	?>
	<form action="media_scripts.php" method="post">
		<div style="margin:15px">
			<div style="margin:3px">
				<b>Collection ID (collid):</b> <input type="text" name="collid" value="<?php echo $collid; ?>" /><br />
			</div>
			<div style="margin:3px">
				<b>Starting Image ID:</b> <input type="text" name="imgidstart" value="<?php echo $imgidEnd; ?>" /><br />
			</div>
			<div style="margin:3px">
				<b>Batch limit: </b><input type="text" name="limit" value="<?php echo $limit; ?>" /><br />
			</div>
			<div style="margin:3px">
				<input type="radio" name="archiveimg" value="0" <?php echo ($archiveImages?'':'CHECKED'); ?> /> Delete Images<br />
				<input type="radio" name="archiveimg" value="1" <?php echo ($archiveImages?'CHECKED':''); ?> /> Archive Images<br />
			</div>
			<div style="margin:3px">
				<b>imgids (enter multiple values delimited by commas)</b><br/>
				<textarea name="imgidstr" rows="8" cols="100"></textarea>
			</div>
		</div>
		<div style="margin:15px">
			<input type="submit" name="submitbutton" value="Process Images" />
		</div>
	</form>
	<?php
}
else{
	echo '<div>Permissions issue; are you logged in?</div>';
}


class MediaTools {

	private $conn;
	private $collid;
	private $imgidArr;
	private $archiveImages = false;
	private $archiveDir;
	private $reportFH;
	private $deleteThumbnail = false;
	private $deleteWeb = false;
	private $deleteOriginal = false;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function archiveImageFiles($imgidStart, $limit){
		//Set stage
		if(!$imgidStart) $imgidStart = 0;
		if(!$this->imgidArr){
			echo '<li>ABORTED: Image ids (imgid) not supplied</li>';
			return false;
		}
		$this->archiveDir = $GLOBALS['IMAGE_ROOT_PATH'].'/archive_'.date('Y-m-d');
		if(!file_exists($this->archiveDir)){
			if(!mkdir($this->archiveDir)) {
				echo '<li>ABORTED: unalbe to create archive directory ('.$this->archiveDir.')</li>';
				return false;
			}
		}
		$createHeader = true;
		if(file_exists($this->archiveDir.'/mediaArchiveReport.csv')) $createHeader = false;
		$this->reportFH = fopen($this->archiveDir.'/mediaArchiveReport.csv', 'a');
		if(!$this->reportFH){
			echo '<li>ABORTED: unalbe to create archive file ('.$this->archiveDir.')</li>';
			return false;
		}
		if($createHeader) fputcsv($this->reportFH, array('imgid','status','path','url'));
		//Remove images
		$imgidFinal = $imgidStart;
		$cnt = 0;
		$sql = 'SELECT i.* FROM images i ';
		if($this->collid) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE (i.imgid IN('.trim(implode(',',$this->imgidArr),', ').')) AND (i.imgid > '.$imgidStart.') ';
		if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.') ';
		$sql .= 'ORDER BY i.imgid LIMIT '.$limit;
		//echo $sql;
		$rs = $this->conn->query($sql);
		echo '<ol>';
		while($r = $rs->fetch_assoc()){
			$imgId = $r['imgid'];
			//Transfer images to archive folder
			$this->archiveImage($r['url'], $imgId);
			$this->archiveImage($r['thumbnailurl'], $imgId);
			$this->archiveImage($r['originalurl'], $imgId);
			//Place INSERT sql into file in case records need to be reintalled
			$insertArr = $r;
			unset($insertArr['imgid']);
			unset($insertArr['initialtimestamp']);
			$insertStr = '';
			foreach($insertArr as $v){
				if($v){
					$insertStr .= ',"'.$v.'"';
				}
				else{
					$insertStr .= ',NULL';
				}
			}
			$insSql = 'INSERT INTO images('.implode(',', array_keys($insertArr)).') VALUES('.substr($insertStr,1).');';
			fputcsv($this->reportFH,array($imgId,'record deleted',$insSql));
			//Delete image from database
			$this->conn->query('DELETE FROM images WHERE imgid = '.$imgId);
			if($cnt && $cnt%100 == 0){
				echo '<li>'.$cnt.' image checked (imgid: '.$imgId.')</li>';
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $imgId;
		}
		echo '</ol>';
		$rs->free();
		fclose($this->reportFH);
		echo '<div>Done! '.$cnt.' images '.($this->archiveImages?'archived':'deleted').'</div>';
		return $imgidFinal;
	}

	private function archiveImage($imgFilePath, $imgid){
		if($imgFilePath){
			if(substr($imgFilePath,0,4) == 'http') {
				$imgFilePath = substr($imgFilePath,strpos($imgFilePath,"/",9));
			}
			$path = str_replace($GLOBALS['IMAGE_ROOT_URL'], $GLOBALS['IMAGE_ROOT_PATH'], $imgFilePath);
			if(is_writable($path)){
				if($this->archiveImages){
					$fileName = substr($path, strrpos($path, '/'));
					rename($path,$this->archiveDir.'/'.$fileName);
				}
				else{
					unlink($path);
				}
			}
			else{
				fputcsv($this->reportFH,array($imgid,'unwritable',$imgFilePath,$path));
			}
		}
	}

	//Setters and getters
	public function setCollid($id){
		if(is_numeric($id)) $this->collid = $id;
	}

	public function setImgidArr($imgidStr){
		$imgidStr = str_replace(';', ' ', $imgidStr);
		$imgidStr = str_replace(',', ' ', $imgidStr);
		$imgidStr = trim(preg_replace('/\s\s+/',' ',$imgidStr),',');
		if($imgidStr){
			if(preg_match('/^[\d\s]+$/',$imgidStr)){
				$this->imgidArr = explode(' ',$imgidStr);
			}
		}
	}

	public function setArchiveImages($b){
		if($b) $this->archiveImages = true;
	}

	public function setDeleteThumbnail($delTn){
		if($delTn) $this->deleteThumbnail = true;
		else $this->deleteThumbnail = false;
	}

	public function setDeleteWebImage($delWeb){
		if($delWeb) $this->deleteWeb = true;
		else $this->deleteWeb = false;
	}

	public function setDeleteOriginal($delOrig){
		if($delOrig) $this->deleteOriginal = true;
		else $this->deleteOriginal = false;
	}
}
?>