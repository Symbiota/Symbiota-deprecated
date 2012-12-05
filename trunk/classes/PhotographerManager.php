<?php 
include_once($serverRoot.'/config/dbconnection.php');

class PhotographerManager{

	private $conn;
	private $uid;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}

 	public function __destruct() {
 		$this->conn->close();
	}
	
	public function setUid($u){
		if(is_numeric($u)){
			$this->uid = $this->conn->real_escape_string($u);
		}
	}

 	public function getPhotographerList(){
		$retArr = array();
 		$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as pname, u.email, Count(ti.imgid) AS imgcnt '.
			'FROM users u INNER JOIN images ti ON u.uid = ti.photographeruid '.
			'GROUP BY u.firstname, u.lastname, u.email '.
			'ORDER BY u.lastname, u.firstname';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->uid]['name'] = $row->pname; 
			$retArr[$row->uid]['imgcnt'] = $row->imgcnt; 
		}
    	$result->close();
    	return $retArr;
	}

	public function getPhotographerInfo(){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(" ",u.firstname, u.lastname) as pname, u.title, u.institution, u.department, u.address, '.
			'u.city, u.state, u.zip, u.country, u.email, u.url, u.biography, u.notes, IFNULL(u.ispublic,0) AS ispublic '.
			'FROM users u WHERE (u.uid = '.$this->uid.')';
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
    	$result->close();
    	return $retArr;
	}

	public function getPhotographerImages($lStart, $lNum){
		$retArr = array();
		$limitStart = 0;
		$limitNum = 100;
		$imgCnt = 0;
		if($lStart && is_numeric($lStart)){
			$limitStart = $this->conn->real_escape_string($lStart);
		}
		if($lNum && is_numeric($lNum)){
			$limitNum = $this->conn->real_escape_string($lNum);
		}
		$sql = 'SELECT i.imgid, i.thumbnailurl, i.url, i.originalurl, ts.family, t.sciname, t.tid '.
			'FROM (images i INNER JOIN taxa t ON i.tid = t.tid) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = 1) AND (i.photographeruid = '.$this->uid.') '.
			'ORDER BY t.sciname, ts.family '.
			'LIMIT '.$limitStart.', '.$lNum;
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$retArr[$imgId]['url'] = $row->url;
			$retArr[$imgId]['tnurl'] = $row->thumbnailurl;
			$retArr[$imgId]['tid'] = $row->tid;
			$retArr[$imgId]['sciname'] = $this->cleanOutStr($row->sciname);
		}
    	$result->close();
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
