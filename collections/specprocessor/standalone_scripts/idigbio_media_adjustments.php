<?php
/*
 * Script that navigates through iDigBio media links and fixes bad full derivative links that were the result of a disk crash
 */
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

$collid = (array_key_exists('collid', $_POST)?$_POST['collid']:'');
$imgidStart = (array_key_exists('imgidstart', $_POST)?$_POST['imgidstart']:0);
$limit = (array_key_exists('limit', $_POST)?$_POST['limit']:10000);
$submit = (array_key_exists('submitbutton', $_POST)?$_POST['submitbutton']:10000);

$toolManager = new iDigBioMediaTools();
$imgidEnd = 0;
if($IS_ADMIN && $submit == 'Process Images'){
	echo '<ol>';
	$imgidEnd = $toolManager->checkImageLinks($imgidStart, $limit, $collid);
	echo '</ol>';
}

?>
<form action="idigbio_media_adjustments.php" method="post">
	<div style="margin:15px">
		<b>Collection ID (collid):</b> <input type="text" name="collid" value="<?php echo $collid; ?>" /><br />
		<b>Starting Image ID:</b> <input type="text" name="imgidstart" value="<?php echo $imgidEnd; ?>" /><br />
		<b>Batch limit: </b><input type="text" name="limit" value="<?php echo $limit; ?>" />
	</div>
	<div style="margin:15px">
		<input type="submit" name="submitbutton" value="Process Images" />
	</div>
</form>

<?php
class iDigBioMediaTools {

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function checkImageLinks($imgidStart, $limit, $collid){
		$imgidFinal = $imgidStart;
		$cnt = 1;
		$sql = 'SELECT i.imgid, i.originalurl FROM images i ';
		if($collid) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE (i.originalurl LIKE "https://api.idigbio.org/v2/media/%size=fullsize") AND (i.imgid > '.$imgidStart.') ';
		if($collid) $sql .= 'AND (o.collid = '.$collid.') ';
		$sql .= 'ORDER BY i.imgid LIMIT '.$limit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$url = $r->originalurl;
			if($this->isBrokenUrl($url)){
				if($newUrl = substr($url,0,-14)){
					if(!$this->isBrokenUrl($newUrl)){
						$sql2 = 'UPDATE images SET originalurl = "'.$newUrl.'" WHERE imgid = '.$r->imgid;
						$this->conn->query($sql2);
						echo '<li>'.$cnt.': Remapping image #'.$r->imgid.' to: '.$newUrl.'</li>';
						ob_flush();
						flush();
					}
				}
			}
			//echo '<li>Image is good (imgid: '.$r->imgid.'): '.$url.'</li>';
			if($cnt%500 == 0){
				echo '<li>'.$cnt.' image checked (imgid: '.$r->imgid.')</li>';
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $r->imgid;
		}
		$rs->free();
		return $imgidFinal;
	}

	private function isBrokenUrl($url){
		$status = false;
		$handle = curl_init($url);
		if(false === $handle){
			$status = true;
		}
		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLOPT_NOBODY, true);
		curl_setopt($handle, CURLOPT_FAILONERROR, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_exec($handle);
		$retCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		//print_r(curl_getinfo($handle));
		if($retCode == 403) $status = true;
		curl_close($handle);
		return $status;
	}
}
?>