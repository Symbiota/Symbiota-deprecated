<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<?php
include_once("../../util/dbconnection.php");
include_once("../../util/symbini.php");

 ?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>Rare, Threatened, Sensitive Species</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_misc_rarespeciesMenu)?$collections_misc_rarespeciesMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($collections_misc_rarespeciesCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_misc_rarespeciesCrumbs;
		echo " <b>Sensitive Species for Masking Locality Details</b>";
		echo "</div>";
	}
	?>
<!-- This is inner text! -->
<div class="innertext">

	<h1>Rare, Threatened, Sensitive Species</h1>
	<div style='margin-left:10px;'>The following species have a protective status within SEINet.  
	Sensitive population numbers and a threatened status are the typical cause for this though some 
	species that are cherished by collectors (Orchids and Cacti) or wild harvesters will also occur 
	on this list. In some cases, whole families have a blanket protection. Specific locality 
	information is withheld from lists and maps within the search engine for the following species.</div>
		
<?php
	$rsObj = new RareSpecies();
	$rsArr = $rsObj->getRareSpeciesList();
	foreach($rsArr as $family => $speciesArr){
		echo "<h3>".$family."</h3>";
		//echo "<div style='margin:10px 0px 0px 5px;font-weight:bold;'>".$family."</div>";
		foreach($speciesArr as $sciName){
			echo "<div style='margin-left:20px;'>".$sciName."</div>";
		}
	}
?>
</div>
<?php 		
	include($serverRoot."/util/footer.php")
?>
</body>
</html>

<?php
 
 class RareSpecies {
    
 	private $con;
    
    function __construct(){
		$this->con = MySQLiConnectionFactory::getCon("readonly");
    }
    
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
    
	public function getRareSpeciesList(){
 		$returnArr = Array();
		$sql = "SELECT ts.Family, t.SciName, t.Author ".
			"FROM taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid ".
			"WHERE ((t.SecurityStatus = 2) AND (ts.taxauthid = 1)) ".
			"ORDER BY ts.Family, t.SciName";
		//echo $sql;
 		$result = $this->con->query($sql);
		if($result) {
			while($row = $result->fetch_object()){
				$family = $row->Family;
				$sciName = "<i>".$row->SciName."</i>&nbsp;&nbsp;".$row->Author;
				$returnArr[$family][] = $sciName;
			}
		}
		$result->free();
		return $returnArr;
 	}
 }
?>