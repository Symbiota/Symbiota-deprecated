<?php 
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$taxon = array_key_exists("taxon",$_REQUEST)?trim($_REQUEST["taxon"]):"";
$target = array_key_exists("target",$_REQUEST)?trim($_REQUEST["target"]):"";

$imgLibManager = new ImageLibraryManager();
?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Image Library</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($imagelib_indexMenu)?$imagelib_indexMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_indexCrumbs)){
		echo "<div class='navpath'>";
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
				<form name="searchform1" action="index.php" method="post">
					<fieldset style="background-color:#FFFFCC;padding:10px;">
						<legend style="font-weight:bold;">Scientific Name Search</legend>
						<input type="text" name="taxon" value="<?php echo $taxon; ?>" title="Enter family, genus, or scientific name" />
						<input name="submit" value="Search" type="submit">
					</fieldset>
				</form>
			</div>
			<div style="font-weight:bold;margin:15px 10px 0px 20px;">
				<div>
					<a href="../misc/usagepolicy.php#images">Image Copyright Policy</a>
				</div>
				<div>
					<a href="contributors.php">Image Contributors</a>
				</div>
				<div>
					<a href="search.php">Image Search</a>
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
				$taxaList = $imgLibManager->getSpeciesList();
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
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>