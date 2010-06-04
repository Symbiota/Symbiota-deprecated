<?php
 header("Content-Type: text/html; charset=ISO-8859-1");
 include_once("../../util/symbini.php");

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
	include($serverRoot."/util/header.php");
	if(isset($collections_download_downloadCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_download_downloadCrumbs;
		echo " <b>Specimen Download</b>";
		echo "</div>";
	}
	?>

<div id="tabdiv">
	<div class='backendleft'>&nbsp;</div>
	<div class='backtab'>
		<a href='../checklist.php'>Checklist</a>
	</div>
	<div class="midleft" style='border-bottom:0px;height:100%;'>&nbsp;</div>
	<div class='fronttab'>
		<a style="color:black;" href="../list.php">List</a>
	</div>
	<div class="midright" style='border-bottom:0px;height:100%;'>&nbsp;</div>
	<div class='backtab'>
		<a href='../maps/index.php'>Maps</a>
	</div>
	<div class='backendright'>&nbsp;</div>
</div>


<table width="580" >

    <tr><td>
        
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

    </td></tr>

    <tr><td align="center"><hr></td></tr>
    <tr><td>
        <div style="margin:5px 0px 10px 10px;font-weight:bold;">By downloading data, the user confirms that he/she has read and agrees with the above terms.</div>
        <div style="margin-left:25px;font-weight:bold;font-size:130%;">Download Results:</div>
        <ul style='margin:5px 0px 0px 35px;'>
<?php  
if($downloadType == "checklist"){
    echo "<li style='font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=checklist&taxonfilter=".$taxonFilterCode."'>Checklist tab-delimited text file</a></li>";
}
else{
    //echo "<li style='font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=darwincore_xml'>Darwin Core XML file</a></li>";
    echo "<li style='font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=darwincore_text'>Darwin Core tab-delimited text file</a></li>";
    echo "<ul><li><a href='http://wiki.tdwg.org/twiki/bin/view/DarwinCore/WebHome' class='bodylink' target='_blank'>What is Darwin Core?</a></li></ul>";
    echo "<li style='margin-top:5px;font-weight:bold;'><a class='bodylink' target='_blank' href='downloadhandler.php?dltype=symbiota'>Symbiota tab-delimited text file</a></li>";
}
   
?>
        </ul>
    </td></tr>
    <tr><td align="center"><hr></td></tr>

</table>

<?php 
	include_once("../../util/footer.php");
?>
</body>

</html>
