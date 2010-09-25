<?php 
include_once($serverRoot.'/config/dbconnection.php');

class PhotographerManager{

	private $conn;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}

 	public function __destruct() {
 		$this->conn->close();
	}

 	public function echoPhotographerList(){
		$sql = "SELECT u.uid, u.firstname, u.lastname, u.email, Count(ti.imgid) AS imgcnt ".
			"FROM users u INNER JOIN images ti ON u.uid = ti.photographeruid ".
			"GROUP BY u.firstname, u.lastname, u.email ".
			"ORDER BY u.lastname, u.firstname";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<div><a href='photographers.php?phuid=".$row->uid."&imgcnt=".$row->imgcnt."'>".$row->lastname.($row->firstname?", ".$row->firstname:"")."</a> ($row->imgcnt)</div>";
		}
    	$result->close();
	}
	
	public function echoPhotographerInfo($uid){
		$sql = "SELECT u.uid, u.firstname, u.lastname, u.title, u.institution, u.department, u.address, ".
			"u.city, u.state, u.zip, u.country, u.email, u.url, u.biography, u.notes, u.ispublic ".
			"FROM users u WHERE u.uid = ".$uid;
		//echo "SQL: ".$sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<div style='margin:20px;font-size:14px;'>";
			echo "<div style='font-weight:bold;'>$row->firstname $row->lastname</div>";
			$isPublic = $row->ispublic;
			if($isPublic){
				if($row->title) echo "<div>$row->title</div>";
				if($row->institution) echo "<div>$row->institution</div>";
				if($row->department) echo "<div>$row->department</div>";
				if($row->city || $row->state){
					echo "<div>".$row->city.($row->city?", ":"").$row->state."&nbsp;&nbsp;$row->zip</div>";
				}
				if($row->country) echo "<div>$row->country</div>";
				if($row->email) echo "<div>$row->email</div>";
				if($row->notes) echo "<div>$row->biography</div>";
				if($row->url) echo "<div><a href='".$row->url."'>$row->url</a></div>";
			}
			else{
				echo "<div style='margin:10px;font-size:12px;'>Photographers details not public</div>";
			}
			echo "</div>";
		}
    	$result->close();
	}
	
	public function echoPhotographerImages($uid,$limitStart = 0, $limitNum = 50, $imgCnt = 0){
		$sql = "SELECT i.thumbnailurl, i.url, i.originalurl, ts.family, t.sciname ".
			"FROM (images i INNER JOIN taxa t ON i.tid = t.tid) ".
			"INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = 1 AND i.photographeruid = $uid ".
			"ORDER BY t.sciname, ts.family LIMIT $limitStart, ".($limitNum+1);
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		$rowCnt = $result->num_rows;
		echo "<div>";
		if($limitStart){
			echo "<div style='float:left;'>";
			echo "<a href='photographers.php?phuid=$uid&imgcnt=$imgCnt&lstart=".($limitStart - $limitNum)."&lnum=$limitNum'>&lt;&lt; Previous Images</a>";
			echo "</div>";
		}
		if($rowCnt >= $limitNum){
			echo "<div style='float:right;'>";
			echo "<a href='photographers.php?phuid=$uid&imgcnt=$imgCnt&lstart=".($limitStart + $limitNum)."&lnum=$limitNum'>Next Images &gt;&gt;</a>";
			echo "</div>";
		}
		echo "</div><div style='clear:both;'>";
		while($row = $result->fetch_object()){
			echo "<div style='float:left;height:160px;' class='imgthumb'>";
			$imgUrl = $row->url;
			$imgTn = $row->thumbnailurl;
			if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
				$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
			}
			echo "<a href='".$imgUrl."'>";
			if($imgTn){
				$imgUrl = $imgTn;
				if(array_key_exists("imageDomain",$GLOBALS) && substr($imgTn,0,1)=="/"){
					$imgUrl = $GLOBALS["imageDomain"].$imgTn;
				}
			}
			echo "<img src='".$imgUrl."' style='height:130px;' />";
			echo "</a><br />";
			echo "<a href='../taxa/index.php?taxon=".$row->sciname."'><i>".$row->sciname."</i></a>";
			echo "</div>";
		}
    	$result->close();
		echo "</div><div style='clear:both;'>";
		if($limitStart){
			echo "<div style='float:left;'>";
			echo "<a href='photographers.php?phuid=$uid&imgcnt=$imgCnt&lstart=".($limitStart - $limitNum)."&lnum=$limitNum'>&lt;&lt; Previous Images</a>";
			echo "</div>";
		}
		if($rowCnt >= $limitNum){
			echo "<div style='float:right;'>";
			echo "<a href='photographers.php?phuid=$uid&imgcnt=$imgCnt&lstart=".($limitStart + $limitNum)."&lnum=$limitNum'>Next Images &gt;&gt;</a>";
			echo "</div>";
		}
	}
}
?>
