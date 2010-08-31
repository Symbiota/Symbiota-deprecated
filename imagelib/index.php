<?php 
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$taxon = array_key_exists("taxon",$_REQUEST)?trim($_REQUEST["taxon"]):"";
$target = array_key_exists("target",$_REQUEST)?trim($_REQUEST["target"]):"";

$imgLibManager = new ImageLibraryManager();
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Image Library</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<meta name='keywords' content='' />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($imagelib_indexMenu)?$imagelib_indexMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_indexCrumbs;
		echo " <b>Image Library</b>";
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Species with Images</h1>
		<div style="margin:0px 0px 5px 20px;">This page provides a complete list to taxa that have images. 
		Use the controls below to browse and search for images by family, genus, or species. 
		</div>
		<div style="float:left;margin:10px 0px 10px 30px;">
			<div style=''>
				<a href='index.php?target=family'>Browse by Family</a>
			</div>
			<div style='margin-top:10px;'>
				<a href='index.php?target=genus'>Browse by Genus</a>
			</div>
			<div style='margin-top:10px;'>
				<a href='index.php?target=species'>Browse by Species</a>
			</div>
			<div style='margin:2px 0px 0px 10px;'>
				<div><a href='index.php?taxon=A'>A</a>|<a href='index.php?taxon=B'>B</a>|<a href='index.php?taxon=C'>C</a>|<a href='index.php?taxon=D'>D</a>|<a href='index.php?taxon=E'>E</a>|<a href='index.php?taxon=F'>F</a>|<a href='index.php?taxon=G'>G</a>|<a href='index.php?taxon=H'>H</a></div>
				<div><a href='index.php?taxon=I'>I</a>|<a href='index.php?taxon=J'>J</a>|<a href='index.php?taxon=K'>K</a>|<a href='index.php?taxon=L'>L</a>|<a href='index.php?taxon=M'>M</a>|<a href='index.php?taxon=N'>N</a>|<a href='index.php?taxon=O'>O</a>|<a href='index.php?taxon=P'>P</a>|<a href='index.php?taxon=Q'>Q</a></div>
				<div><a href='index.php?taxon=R'>R</a>|<a href='index.php?taxon=S'>S</a>|<a href='index.php?taxon=T'>T</a>|<a href='index.php?taxon=U'>U</a>|<a href='index.php?taxon=V'>V</a>|<a href='index.php?taxon=W'>W</a>|<a href='index.php?taxon=X'>X</a>|<a href='index.php?taxon=Y'>Y</a>|<a href='index.php?taxon=Z'>Z</a></div>
			</div>
		</div>
		<div style="float:right;width:250px;">
			<div style="margin:10px 0px 0px 0px;">
				<form name='searchform1' action='index.php' method='post'>
					<fieldset style="background-color:#FFFFCC;padding:0px 10px 10px 10px;">
						<legend style="font-weight:bold;">Scientific Name Search</legend>
						<input type='text' name='taxon' title='Enter family, genus, or scientific name'>
						<input name='submit' value='Search' type='submit'>
					</fieldset>
				</form>
			</div>
			<div style='font-weight:bold;margin:15px 10px 0px 20px;'>
				<div>
					<a href="javascript:var popupReference=window.open('imageusagepolicy.php','crwindow','toolbar=1,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=1,width=700,height=550,left=20,top=20');">Image Copyright Policy</a>
				</div>
				<div>
					<a href="photographers.php">Contributing Photographers</a>
				</div>
			</div>
		</div>
		<div style='clear:both;'><hr/></div>
		<?php
			$taxaList = Array();
			if($target == "genus"){
				echo "<div style='margin-left:20px;margin-top:20px;margin-bottom:20px;font-weight:bold;'>Select a Genus to see species list.</div>";
				$taxaList = $imgLibManager->getGenusList();
				foreach($taxaList as $value){
					echo "<div style='margin-left:30px;'><a href='index.php?taxon=".$value."'>".$value."</a></div>";
				}
			}
			elseif($target == "species"){
				echo "<div style='margin-left:20px;margin-top:20px;margin-bottom:20px;font-weight:bold;'>Select a species to access available images.</div>";
				$taxaList = $imgLibManager->getSpeciesList("");
				foreach($taxaList as $key => $value){
					echo "<div style='margin-left:30px;font-style:italic;'>";
					echo "<a href='../taxa/index.php?taxon=".$key."' target='_blank'>".$value."</a>";
					echo "</div>";
				}
			}
			elseif($taxon){
				echo "<div style='margin-left:20px;margin-top:20px;margin-bottom:20px;font-weight:bold;'>Select a species to access available images.</div>";
				$taxaList = $imgLibManager->getSpeciesList($taxon);
				foreach($taxaList as $key => $value){
					echo "<div style='margin-left:30px;font-style:italic;'>";
					echo "<a href='../taxa/index.php?taxon=".$key."' target='_blank'>".$value."</a>";
					echo "</div>";
				}
			}
			else{ //Family display
				echo "<div style='margin-left:20px;margin-top:20px;margin-bottom:20px;font-weight:bold;'>Select a family to see species list.</div>";
				$taxaList = $imgLibManager->getFamilyList();
				foreach($taxaList as $value){
					echo "<div style='margin-left:30px;'><a href='index.php?taxon=".$value."'>".strtoupper($value)."</a></div>";
				}
			}
	?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
	
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
			var pageTracker = _gat._getTracker("<?php echo $googleAnalyticsKey; ?>");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
</body>
</html>

<?php

class ImageLibraryManager{

	function getConnection() {
 		return MySQLiConnectionFactory::getCon("readonly");
	}

 	public function getFamilyList(){
		$con = $this->getConnection();
 		$returnArray = Array();
		$sql = "SELECT DISTINCT ts.Family 
			FROM (images ti INNER JOIN taxstatus ts ON ti.TID = ts.TID) 
			INNER JOIN taxa t ON ts.tidaccepted = t.tid 
			WHERE (ts.taxauthid = 1) AND (t.RankId > 180) AND (ts.Family Is Not Null) 
			ORDER BY ts.Family";
		$result = $con->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->Family;
    	}
    	$result->free();
    	$con->close();
		return $returnArray;
	}
	
	public function getGenusList(){
		$con = $this->getConnection();
 		$returnArray = Array();
		$sql = "SELECT DISTINCT t.UnitName1 
			FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID 
			WHERE (ts.taxauthid = 1) AND (t.RankId > 180) ORDER BY t.UnitName1";
		$result = $con->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->UnitName1;
    	}
    	$result->free();
    	$con->close();
		return $returnArray;
	}
	
	public function getSpeciesList($taxon){
		$con = $this->getConnection();
		$returnArray = Array();
		$sql = "SELECT DISTINCT t.tid, t.SciName 
			FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID 
			WHERE (ts.taxauthid = 1) AND (t.RankId > 180) ";
		if($taxon) $sql .= "AND ((t.SciName LIKE '".$taxon."%') OR (ts.Family = '".$taxon."')) ";
		$sql .= "ORDER BY t.SciName ";
		//echo $sql;
		$result = $con->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[$row->tid] = $row->SciName;
	    }
	    $result->free();
    	$con->close();
	    return $returnArray;
	}
}
?>
