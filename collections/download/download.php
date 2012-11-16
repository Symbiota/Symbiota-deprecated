<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);

$downloadType = array_key_exists("dltype",$_REQUEST)?$_REQUEST["dltype"]:"specimen"; 
$taxonFilterCode = array_key_exists("taxonFilterCode",$_REQUEST)?$_REQUEST["taxonFilterCode"]:0; 
?>

<html>
<head>
    <title>Collections Search Download</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_download_downloadMenu)?$collections_download_downloadMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_download_downloadCrumbs)){
		if($collections_download_downloadCrumbs){
			?>
			<div class='navpath'>
				<a href='../../index.php'>Home</a> &gt; 
				<?php echo $collections_download_downloadCrumbs; ?>
				<b>Specimen Download</b>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt; 
			<a href="../index.php">Collections</a> &gt; 
			<a href="../harvestparams.php">Search Criteria</a> &gt; 
			<a href="../list.php">Occurrence Listing</a> &gt; 
			<b>Specimen Download</b>
		</div>
		<?php 
	}
	?>

	<div id="innertext">
		<h2>Data Usage Guidelines</h2>
        <div style="margin:15px;">
        	By downloading data, the user confirms that he/she has read and agrees with the general 
        	<a href="../../misc/usagepolicy.php#images">data usage terms</a>. 
        	Note that additional terms of use specific to the individual collections 
        	may be distributed with the data download. When present, the terms 
        	supplied by the owning institution should take precedence over the 
        	general terms posted on the website.
        </div>
        <div style='margin:30px;'>
	        <h2>Download Results:</h2>
	        <ul>
				<?php  
				if($downloadType == "checklist"){
				    echo "<li style='font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=checklist&taxonfilter=".$taxonFilterCode."'>Checklist tab-delimited text file</a></li>";
				}
				else{
				    //echo "<li style='font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=darwincore_xml'>Darwin Core XML file</a></li>";
				    echo "<li style='font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=darwincore_text'>Darwin Core CSV text file</a></li>";
				    echo "<ul><li><a href='http://rs.tdwg.org/dwc/index.htm' class='bodylink' target='_blank'>What is Darwin Core?</a></li></ul>";
				    echo "<li style='margin-top:5px;font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=symbiota'>Symbiota CSV text file</a></li>";
				}
				   
				?>
	        </ul>
	       </div>
	</div>
<?php 
	include($serverRoot.'/footer.php');
?>
</body>

</html>
