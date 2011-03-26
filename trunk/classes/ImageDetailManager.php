<?php
/*
 * Modified on 19 March 2011
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');

class ImageDetailManager{
	
	private $conn;
	private $imgId;

	public function __construct($id,$conType){
 		$this->conn = MySQLiConnectionFactory::getCon($conType);
 		$this->imgId = $id;
	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
 	
	public function getImageMetadata(){
		$retArr = Array();
		$sql = "SELECT i.imgid, i.tid, i.url, i.thumbnailurl, i.originalurl, i.photographeruid, ".
			"IFNULL(i.photographer,CONCAT_WS(' ',u.firstname,u.lastname)) AS photographer, ".
			"i.caption, i.owner, i.sourceurl, i.copyright, i.locality, i.notes, i.occid, i.sortsequence, ".
			"t.sciname, t.author, t.rankid ".
			"FROM images i INNER JOIN taxa t ON i.tid = t.tid ".
			"LEFT JOIN users u ON i.photographeruid = u.uid ".
			"WHERE i.imgid = ".$this->imgId;
		//echo "<div>$sql</div>";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$retArr["tid"] = $row->tid;
			$retArr["sciname"] = $row->sciname;
			$retArr["author"] = $row->author;
			$retArr["rankid"] = $row->rankid;
			$retArr["url"] = $row->url;
			$retArr["thumbnailurl"] = $row->thumbnailurl;
			$retArr["originalurl"] = $row->originalurl;
			$retArr["photographer"] = $row->photographer;
			$retArr["photographeruid"] = $row->photographeruid;
			$retArr["caption"] = $row->caption;
			$retArr["owner"] = $row->owner;
			$retArr["sourceurl"] = $row->sourceurl;
			$retArr["copyright"] = $row->copyright;
			$retArr["locality"] = $row->locality;
			$retArr["notes"] = $row->notes;
			$retArr["sortsequence"] = $row->sortsequence;
			$retArr["occid"] = $row->occid;
		}
		return $retArr;
	}

	public function editImage(){
		$status = "";
		$searchStr = $GLOBALS["imageRootUrl"];
		if(substr($searchStr,-1) != "/") $searchStr .= "/";
		$replaceStr = $GLOBALS["imageRootPath"];
		if(substr($replaceStr,-1) != "/") $replaceStr .= "/";
	 	$url = $_REQUEST["url"];
	 	$tnUrl = $_REQUEST["thumbnailurl"];
	 	$origUrl = $_REQUEST["originalurl"];
	 	if(array_key_exists("renameweburl",$_REQUEST)){
	 		$oldUrl = $_REQUEST["oldurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$url);
	 		if($url != $oldUrl){
	 			if(!rename($oldName,$newName)){
	 				$url = $oldUrl;
		 			$status .= "Web URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
		if(array_key_exists("renametnurl",$_REQUEST)){
	 		$oldTnUrl = $_REQUEST["oldthumbnailurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldTnUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$tnUrl);
	 		if($tnUrl != $oldTnUrl){
	 			if(!rename($oldName,$newName)){
	 				$tnUrl = $oldTnUrl;
		 			$status .= "Thumbnail URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
		if(array_key_exists("renameorigurl",$_REQUEST)){
	 		$oldOrigUrl = $_REQUEST["oldoriginalurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldOrigUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$origUrl);
	 		if($origUrl != $oldOrigUrl){
	 			if(!rename($oldName,$newName)){
	 				$origUrl = $oldOrigUrl;
		 			$status .= "Large image URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
	 	$caption = $this->cleanStr($_REQUEST["caption"]);
		$photographer = $this->cleanStr($_REQUEST["photographer"]);
		$photographerUid = $_REQUEST["photographeruid"];
		$owner = $this->cleanStr($_REQUEST["owner"]);
		$locality = $this->cleanStr($_REQUEST["locality"]);
		$occId = $_REQUEST["occid"];
		$notes = $this->cleanStr($_REQUEST["notes"]);
		$sourceUrl = $this->cleanStr($_REQUEST["sourceurl"]);
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$sortSequence = (array_key_exists("sortsequence",$_REQUEST)?$_REQUEST["sortsequence"]:0);
		$addToTid = (array_key_exists("addtoparent",$_REQUEST)?$this->parentTid:0);
		if(array_key_exists("addtotid",$_REQUEST)){
			$addToTid = $_REQUEST["addtotid"];
		}
		
		$sql = "UPDATE images SET caption = \"".$caption."\", url = \"".$url."\", thumbnailurl = \"".$tnUrl."\", ".
			"originalurl = \"".$origUrl."\", photographer = ".($photographer?"\"".$photographer."\"":"NULL").", ".
			"photographeruid = ".($photographerUid?$photographerUid:"NULL").", owner = \"".$owner."\", sourceurl = \"".$sourceUrl."\", ".
			"copyright = \"".$copyRight."\", locality = \"".$locality."\", occid = ".($occId?$occId:"NULL").", ".
			"notes = \"".$notes."\", sortsequence = ".$sortSequence." ".
			" WHERE imgid = ".$this->imgId;
		//echo $sql;
		if($this->conn->query($sql)){
			if($addToTid){
				$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, ".
					"owner, sourceurl, copyright, locality, occid, notes) ".
					"VALUES (".$addToTid.",\"".$url."\",\"".$tnUrl."\",\"".$origUrl."\",".
					($photographer?"\"".$photographer."\"":"NULL").",".$photographerUid.",\"".$caption."\",\"".
					$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",\"".$locality."\",".($occId?$occId:"NULL").",\"".$notes."\")";
				//echo $sql;
				if($this->conn->query($sql)){
					$this->setPrimaryImageSort($addToTid);
				}
				else{
					$status = "unable to upload image for related taxon";
					//$status = "Error:editImage:loading the parent data: ".$this->conn->error."<br/>SQL: ".$sql;
				}
			}
		}
		else{
			$status = "Error:editImage: ".$this->conn->error."\nSQL: ".$sql;
		}
		return $status;
	}
	
	public function changeTaxon($targetTid,$sourceTid){
		$status = '';
		$sql = 'UPDATE images SET tid = '.$targetTid.', sortsequence = 50 WHERE imgid = '.$this->imgId.' AND tid = '.$sourceTid;
		if(!$this->conn->query($sql)){
			//Transfer is not happening because image is probably already mapped to that taxon  
			$sql2 = 'DELETE FROM images WHERE imgid = '.$this->imgId.' AND tid = '.$sourceTid;
			$this->conn->query($sql2);
		}
		$this->setPrimaryImageSort($targetTid);
		$this->setPrimaryImageSort($sourceTid);
		return $status;
	}

	public function deleteImage($imgIdDel, $removeImg){
		$status = '';
		$imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($imageRootPath,-1) != "/") $imageRootPath .= "/";
		$imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($imageRootUrl,-1) != "/") $imageRootUrl .= "/";
		
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = ""; $tid = 0;
		$sqlQuery = "SELECT ti.url, ti.thumbnailurl, ti.originalurl, ti.tid FROM images ti WHERE ti.imgid = ".$imgIdDel;
		$result = $this->conn->Query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgThumbnailUrl = $row->thumbnailurl;
			$imgOriginalUrl = $row->originalurl;
			$tid = $row->tid;
		}
		$result->close();
				
		$sql = "DELETE FROM images WHERE imgid = ".$imgIdDel;
		//echo $sql;
		if($this->conn->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT imgid FROM images WHERE url = '".$imgUrl."'";
				$rs = $this->conn->query($sql);
				if(!$rs->num_rows){
					//Delete image from server
					$imgDelPath = str_replace($imageRootUrl,$imageRootPath,$imgUrl);
					if(file_exists($imgDelPath)){
						if(!unlink($imgDelPath)){
							$status = 'ERROR: Deleted records from database successfully but FAILED to delete image from server. The Image will have to be deleted manually. ';
							$status .= '<br/>PATH: '.$imgDelPath;
							$status .= '<br/>Return to <a href="../taxa/admin/tpimageeditor.php?tid='.$tid.'&category=images">Taxon Editor</a>';
						}
					}
					$imgTnDelPath = str_replace($imageRootUrl,$imageRootPath,$imgThumbnailUrl);
					if(file_exists($imgTnDelPath)){
						unlink($imgTnDelPath);
					}
					$imgOriginalDelPath = str_replace($imageRootUrl,$imageRootPath,$imgOriginalUrl);
					if(file_exists($imgOriginalDelPath)){
						unlink($imgOriginalDelPath);
					}
				}
			}
			$this->setPrimaryImageSort($tid);
		}
		else{
			$status = 'ERROR: Unable to delete image record: '.$this->conn->error;
			//echo 'SQL: '.$sql;
		}
		if(!$status) $status = $tid;
		
		return $status;
	}

	public function parentImageEmpty($url,$tid){
		$sql = 'SELECT i.imgid '.
			'FROM taxa t INNER JOIN images i ON t.parenttid = i.tid '.
			'WHERE t.tid = '.$tid.' AND i.url = "'.$url.'"';
		$result = $this->conn->query($sql);
		if($result && $result->num_rows > 0) return false;
		return true;
	}

	private function setPrimaryImageSort($subjectTid){
		$sql = "UPDATE images ti INNER JOIN ".
			"(SELECT ti.imgid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted ".
			"INNER JOIN images ti ON ts2.tid = ti.tid WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ".
			"AND (ts1.tidaccepted=".$subjectTid.") ORDER BY ti.SortSequence LIMIT 1) innertab ON ti.imgid = innertab.imgid ".
			"SET ti.SortSequence = 1";
		//echo $sql2;
		$this->conn->query($sql);
	}
	
	public function getChildrenArr($tid){
		$childrenArr = Array();
		$sql = "SELECT t.Tid, t.SciName, t.Author ".
			"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = 1 AND ts.ParentTid = ".$tid." ORDER BY t.SciName";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$childrenArr[$row->Tid]["sciname"] = $row->SciName;
			$childrenArr[$row->Tid]["author"] = $row->Author;
		}
		$result->close();
		return $childrenArr;
	}

	public function echoPhotographerSelect($userId = 0){
		$sql = "SELECT u.uid, CONCAT_WS(', ',u.lastname,u.firstname) AS fullname ".
			"FROM users u ORDER BY u.lastname, u.firstname ";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<option value='".$row->uid."' ".($row->uid == $userId?"SELECTED":"").">".$row->fullname."</option>\n";
		}
		$result->close();
	}

 	protected function cleanStr($str){
 		$newStr = trim($str);
 		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace("\"","&quot;",$newStr);
 		return $newStr;
 	}
}
?>