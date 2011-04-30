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
	$displayLeftMenu = (isset($collections_download_downloadMenu)?$collections_download_downloadMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($collections_download_downloadCrumbs)){
		if($collections_download_downloadCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt; ";
			echo $collections_download_downloadCrumbs;
			echo " <b>Specimen Download</b>";
			echo "</div>";
		}
	}
	else{
		echo '<div class="navpath">';
		echo '<a href="../../index.php">Home</a> &gt; ';
		echo '<a href="../index.php">Collections</a> &gt; ';
		echo '<a href="../harvestparams.php">Search Criteria</a> &gt; ';
		echo '<a href="../list.php">Occurrence Listing</a> &gt; ';
		echo ' <b>Specimen Download</b>';
		echo '</div>';
	}
	?>

	<div id="innertext">
	    <div style="margin:25px;">
	        <h3 style="margin-top:10px;">Guidelines for Acceptable Use of Data: </H3>
	        <ul>
		        <li>While <?php echo $defaultTitle; ?> will make every effort possible to control and document the quality of the data it publishes, the data are made available "as is". Any report of errors in the data should be directed to the appropriate curators and/or collections managers. </li>
		        <li><?php echo $defaultTitle; ?> cannot assume responsibility for damages resulting from mis-use or mis-interpretation of datasets or from errors or omissions that may exist in the data. </li>
		        <li>It is considered a matter of professional ethics to acknowledge the work of other scientists that has resulted in data used in subsequent research. </li>
		        <li><?php echo $defaultTitle; ?> expects that any use of data from this server will be accompanied with the appropriate citations and acknowledgments. </li>
		        <li><?php echo $defaultTitle; ?> encourages users to contact the original investigator responsible for the data that they are accessing. Where appropriate, researchers whose projects 
		            are integrally dependent on particular group of specimen data are encouraged to consider collaboration and/or co-authorship with original investigators. </li>
		        <li><?php echo $defaultTitle; ?> asks that users not redistribute data obtained from this site. However, links or references to this site may be freely posted.</li>
	        </ul>
	    </div>
	    <div>
	    	<hr>
	    </div>
	    <div style="margin:25px;">
	        <div style="margin:5px 0px 10px 10px;font-weight:bold;">By downloading data, the user confirms that he/she has read and agrees with the above terms.</div>
	        <div style="margin-left:25px;font-weight:bold;font-size:130%;">Download Results:</div>
	        <ul style='margin:5px 0px 0px 35px;'>
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
