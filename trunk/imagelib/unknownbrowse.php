<?php 
include_once("../util/dbconnection.php");
include_once("../util/symbini.php");
header("Content-Type: text/html; charset=".$charset);
$showClosedIds = array_key_exists("showclosed",$_REQUEST)?$_REQUEST["showclosed"]:""; 

?>
<html>
<head>
<title><?php echo $defaultTitle; ?> - Community Identifications</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<script type="text/javascript" src="../js/googleanalytics.js"></script>
	<meta name='keywords' content='' />
</head>

<body>
	<?php
	$displayLeftMenu = (isset($imagelib_unknownbrowseMenu)?$imagelib_unknownbrowseMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($imagelib_unknownbrowseCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_unknownbrowseCrumbs;
		echo " <b>Unknown</b>"; 
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div style="margin:15px;">
		<h1>Community Identification</h1>
		<div style="margin:0px 0px 5px 20px;">
			<a href="unknownsubmit.php">Submit</a> an image of an unknown for identification by the botanical community or browse 
			submitted images and offer your options on their identification. Note that you must 
			<a href="../profile/index.php?refurl=<?php echo $_SERVER['PHP_SELF']; ?>">login</a> to either submit an image 
			or comment on a submitted image.   
		</div>
		<div style="margin:0px 0px 5px 20px;">
			<h1>Pending Identications</h1>
			<?php 
				$uknManager = new UnknownManager();
				$uknManager->showPending();
			?>   
		</div>
		<div style="margin:0px 0px 5px 20px;">
			<h1>Closed Identifications</h1>
   			<?php 
   				if($showClosedIds){
					$uknManager->showClosed();
   				}
   				else{
   					echo "<a href='unknownbrowse.php?showclosed=1'>Show Closed Identifications</a>";
   				}
   			
   			?>
		</div>
	</div>
	<?php
	include($serverRoot."/util/footer.php");
	?>
	
</body>
</html>

<?php

class UnknownManager{

	private function getConnection() {
 		return MySQLiConnectionFactory::getCon("readonly");
	}

	public function showPending(){
		$conn = $this->getConnection();
		$sql = "SELECT u.unkid, t.family, t.sciname, t.rankid, u.username ".
			"FROM unknowns u LEFT JOIN taxa t ON u.tid = t.tid WHERE u.idstatus = 'ID pending' ".
			"ORDER BY t.family, t.sciname, u.username";
		$result = $conn->query($sql);
		while($row = $result->fetch_object()){
			$unkid = $row->unkid;
			$family = $row->family;
			$sciName = $row->sciname;
			if($row->rankid && $row->rankid < 180) $sciname = "unknown (".$sciName.")";
			if(!$sciName) $sciName = "unknown";
			$username = $row->username;
			echo "<div><a href='unknowndisplay.php?unkid=".$unkid."'>".$unkid.": ".$sciName."</a> - ".$username."</div>";
		}
		if(!$result) echo "There are no identifications with a status of: Pending";
		$result->close();
		$conn->close();
	}

	public function showClosed(){
		$conn = $this->getConnection();
		$sql = "SELECT u.unkid, t.family, t.sciname, t.rankid, u.username ".
			"FROM unknowns u LEFT JOIN taxa t ON u.tid = t.tid WHERE u.idstatus = 'ID closed' ".
			"ORDER BY t.family, t.sciname, u.username";
		$result = $conn->query($sql);
		while($row = $result->fetch_object()){
			$unkid = $row->unkid;
			$family = $row->family;
			$sciName = $row->sciname;
			if($row->rankid && $row->rankid < 180) $sciname = "unknown (".$sciName.")";
			if(!$sciName) $sciName = "unknown";
			$username = $row->username;
			echo "<div><a href='unknowndisplay.php?unkid=".$unkid."'>".$unkid.": ".$sciName."</a> - ".$username."</div>";
		}
		if(!$result) echo "There are no identifications with a status of: Closed";
		$result->close();
		$conn->close();
	}
}
?>
