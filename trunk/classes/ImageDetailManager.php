<?php
/*
 * Created on Jun 11, 2006
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');

class ImageDetailManager{
	
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
			"ti.caption, ti.owner, ti.sourceurl, ti.copyright, ti.copyrighturl, ti.locality, ti.notes, ti.occid ".
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
			$retArr["copyrighturl"] = $row->copyrighturl;
			$retArr["locality"] = $row->locality;
			$retArr["notes"] = $row->notes;
			$retArr["occid"] = $row->occid;
		}
		return $retArr;
	}
}
?>