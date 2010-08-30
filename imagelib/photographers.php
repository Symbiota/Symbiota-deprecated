<?php 
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$phUid = array_key_exists("phuid",$_REQUEST)?$_REQUEST["phuid"]:0;
$limitStart = array_key_exists("lstart",$_REQUEST)?$_REQUEST["lstart"]:0;
$limitNum = array_key_exists("lnum",$_REQUEST)?$_REQUEST["lnum"]:50;
$imgCnt = array_key_exists("imgcnt",$_REQUEST)?$_REQUEST["imgcnt"]:0;

$pManager = new PhotographerManager();
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Photographer List</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<link rel="stylesheet" href="../css/speciesprofile.css" type="text/css"/>
	<meta name='keywords' content='' />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($imagelib_photographersMenu)?$imagelib_photographersMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_photographersCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_photographersCrumbs;
		echo " <b>Photographer List</b>"; 
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
		<h1><?php echo $defaultTitle; ?> Photographers</h1>
		<?php
			if($phUid){
				echo "<div style='margin:0px 0px 5px 20px;'>"; 
				$pManager->echoPhotographerInfo($phUid);
				echo "</div>";
				echo "<div style='float:right;'><a href='photographers.php'>Return to Photographer List</a></div>";
				if($imgCnt < 51){
					echo "<div>Total Image: $imgCnt</div>";
				}
				else{
					echo "<div style='font-weight:bold;'>Images: $limitStart - ".($limitStart+$limitNum)." of $imgCnt</div>";
				}
				echo "<hr />";
				$pManager->echoPhotographerImages($phUid,$limitStart,$limitNum,$imgCnt);
			}
			else{
				$pManager->echoPhotographerList(); 
			}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
	
</body>
</html>

<?php

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
