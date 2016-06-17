<?php 
include_once($SERVER_ROOT.'/config/dbconnection.php');

class PhotographerManager{

	private $conn;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}

 	public function __destruct() {
 		$this->conn->close();
	}
	
	public function getCollectionImageList(){
		$retArr = array();
 		$sql = 'SELECT o.collid, CONCAT(c.collectionname, " (", CONCAT_WS("-",c.institutioncode,c.collectioncode),")") as collname, COUNT(i.imgid) AS imgcnt '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
 			'INNER JOIN omcollections c ON o.collid = c.collid '.
 			'WHERE c.colltype = "Preserved Specimens" '.
 			'GROUP BY c.collid '.
			'ORDER BY c.collectionname';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->collid]['name'] = $row->collname; 
			$retArr[$row->collid]['imgcnt'] = $row->imgcnt; 
		}
    	$result->free();
    	return $retArr;
	}
	
	public function getCollectionName($collId){
		$retStr = '';
		if($collId && is_numeric($collId)){
	 		$sql = 'SELECT CONCAT(collectionname, CONCAT_WS("-",institutioncode,collectioncode)) as collname '.
				'FROM omcollections '.
	 			'WHERE collid = '.$collId;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$retStr = $row->collname; 
			}
	    	$result->free();
		}
    	return $retStr;
	}
	
	public function getPhotographerList(){
		$retArr = array();
 		$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as pname, u.email, Count(ti.imgid) AS imgcnt '.
			'FROM users u INNER JOIN images ti ON u.uid = ti.photographeruid '.
			'GROUP BY u.uid '.
			'ORDER BY u.lastname, u.firstname';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->uid]['name'] = $row->pname; 
			$retArr[$row->uid]['imgcnt'] = $row->imgcnt; 
		}
    	$result->free();
    	return $retArr;
	}

	public function getPhotographerInfo($uid){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(" ",u.firstname, u.lastname) as pname, u.title, u.institution, u.department, u.address, '.
			'u.city, u.state, u.zip, u.country, u.email, u.url, u.biography, u.notes, IFNULL(u.ispublic,0) AS ispublic '.
			'FROM users u WHERE (u.uid = '.$uid.')';
		//echo "SQL: ".$sql;
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$retArr['name'] = $this->cleanOutStr($row->pname);
			$retArr['ispublic'] = $row->ispublic;
			$retArr['title'] = $this->cleanOutStr($row->title);
			$retArr['institution'] = $this->cleanOutStr($row->institution);
			$retArr['department'] = $this->cleanOutStr($row->department);
			$retArr['city'] = $row->city;
			$retArr['state'] = $row->state;
			$retArr['zip'] = $row->zip;
			$retArr['country'] = $row->country;
			$retArr['email'] = $this->cleanOutStr($row->email);
			$retArr['notes'] = $this->cleanOutStr($row->notes);
			$retArr['biography'] = $this->cleanOutStr($row->biography);
			$retArr['url'] = $row->url;
		}
    	$result->free();
    	return $retArr;
	}

	public function getPhotographerImages($uid, $collId, $lStart, $lNum){
		$retArr = array();
		if(!is_numeric($uid)) return $retArr;
		if(!is_numeric($collId)) return $retArr;
		$limitStart = 0;
		$limitNum = 100;
		$imgCnt = 0;
		if($lStart && is_numeric($lStart)){
			$limitStart = $this->conn->real_escape_string($lStart);
		}
		if($lNum && is_numeric($lNum)){
			$limitNum = $this->conn->real_escape_string($lNum);
		}
		if($uid){
			$sql = 'SELECT i.imgid, i.thumbnailurl, i.url, IFNULL(t.sciname,"undefined") AS sciname, t.tid '.
				'FROM images i LEFT JOIN taxa t ON i.tid = t.tid '.
				'WHERE (i.photographeruid = '.$uid.') '.
				'ORDER BY sciname '.
				'LIMIT '.$limitStart.', '.$lNum;
		}
		else{
			$sql = 'SELECT i.imgid, i.thumbnailurl, i.url, IFNULL(o.sciname,"Not Identified") AS sciname, t.tid '. 
				'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
				'WHERE o.collid = '.$collId.' '.
				'ORDER BY t.sciname '.
				'LIMIT '.$limitStart.', '.$lNum;
		}
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$retArr[$imgId]['url'] = $row->url;
			$retArr[$imgId]['tnurl'] = $row->thumbnailurl;
			$retArr[$imgId]['tid'] = $row->tid;
			$retArr[$imgId]['sciname'] = $this->cleanOutStr($row->sciname);
		}
    	$result->free();
    	return $retArr;
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>