<?php
/*
 * Script that navigates through submitted image ids (imgid) and removes image records from database and moves physical to an archive directory
 */
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

$collid = (array_key_exists('collid', $_POST)?$_POST['collid']:'');
$imgidStart = (array_key_exists('imgidstart', $_POST)?$_POST['imgidstart']:0);
$limit = (array_key_exists('limit', $_POST)?$_POST['limit']:10000);
$imgidStr = (array_key_exists('imgidstr', $_POST)?$_POST['imgidstr']:10000);
$submit = (array_key_exists('submitbutton', $_POST)?$_POST['submitbutton']:10000);

$toolManager = new MediaTools();
$imgidEnd = 0;
if($IS_ADMIN && $submit == 'Process Images'){
	$toolManager->setImgidArr($imgidStr);
	echo '<ol>';
	$imgidEnd = $voucherLinker->archiveImageFiles($imgidStart, $limit);
	echo '</ol>';
}

?>
<form action="idigbio_media_adjustments.php" method="post">
	<div style="margin:15px">
		<b>Collection ID (collid):</b> <input type="text" name="collid" value="<?php echo $collid; ?>" /><br />
		<b>Starting Image ID:</b> <input type="text" name="imgidstart" value="<?php echo $imgidEnd; ?>" /><br />
		<b>Batch limit: </b><input type="text" name="limit" value="<?php echo $limit; ?>" /><br />
		<textarea name="imgidstr" rows="8" cols="400"></textarea>
	</div>
	<div style="margin:15px">
		<input type="submit" name="submitbutton" value="Process Images" />
	</div>
</form>

<?php
class MediaTools {

	private $conn;
	private $collid;
	private $imgidArr;
	private $archiveDir;
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
		if(!$this->imgidArr){
			echo '<li>ABORTED: Image ids (imgid) not supplied</li>';
			return false;
		}
		$this->archiveDir = $GLOBALS['IMAGE_ROOT_PATH'].'/archive_'.date('Y-m-d');
		if(!mkdir($this->archiveDir)) {
			echo '<li>ABORTED: unalbe to create archive directory ('.$this->archiveDir.')</li>';
			return false;
		}
		$fh = fopen($this->archiveDir.'/mediafilearchive.csv', 'a')
		//Remove images
		$imgidFinal = $imgidStart;
		$cnt = 1;
		$sql = 'SELECT * FROM images i ';
		if($this->collid) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE (i.imgid IN('.implode(',',$this->imgidArr).')) AND (i.imgid > '.$imgidStart.') ';
		if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.') ';
		$sql .= 'ORDER BY i.imgid LIMIT '.$limit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$imgId = $r['imgid'];
			//Transfer images to archive folder
			$this->archiveImage($r['url']);
			$this->archiveImage($r['thumbnailurl']);
			$this->archiveImage($r['originalurl']);
			//Place INSERT sql into file in case records need to be reintalled
			$insertArr = $r;
			unset($insertArr['imgid']);
			unset($insertArr['initialtimestamp']);
			fwrite($fh, 'INSERT INTO images('.implode(',', array_keys($insertArr)).') VALUES("'.implode('","', $insertArr).'");');
			//Delete image from database
			$this->conn->query('DELETE FROM images WHERE imgid = '.$imgId);
			if($cnt%500 == 0){
				echo '<li>'.$cnt.' image checked (imgid: '.$imgId.')</li>';
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $imgId;
		}
		$rs->free();
		fclose($fh);
		return $imgidFinal;
	}

	private function archiveImage($imgFilePath){
		if(substr($imgFilePath,0,4) == 'http') {
			$imgFilePath = substr($imgFilePath,strpos($imgFilePath,"/",9);
		}
		$path = str_replace($GLOBALS['IMAGE_ROOT_URL'], $GLOBALS['IMAGE_ROOT_PATH'], $imgFilePath);
		rename($path,$this->archiveDir);
	}

	//Setters and getters
	public function setCollid($id){
		if(is_numeric($id)) $this->collid = $id;
	}

	public function setImgidArr($imgidStr){
		if($imgidStr){
			if(preg_match('/^[\d,]$/',$imgidStr)){
				$this->imgidArr = explode(',',$imgidStr);
			}
			elseif(preg_match('/^[\d;]$/',$imgidStr)){
				$this->imgidArr = explode(';',$imgidStr);
			}
		}
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