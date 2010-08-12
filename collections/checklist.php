<?php
 include_once("../util/symbini.php");
 include_once("util/ChecklistManager.php");
 header("Content-Type: text/html; charset=".$charset);

 $checklistManager = new ChecklistManager();
 $taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;

?>

<html>
    <head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
        <title><?php echo $defaultTitle; ?> Dynamic Checklist</title>
        <link rel="stylesheet" href="../css/main.css" type="text/css">
    </head>
    <body>
<?php
	$displayLeftMenu = (isset($collections_checklistMenu)?$collections_checklistMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($collections_checklistCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_checklistCrumbs;
		echo " &gt; <b>Dynamic Checklist</b>";
		echo "</div>";
	}
?>

	<!-- This is inner text! -->
	<div id='innertext'>
		<div id="tabdiv">
			<div class='backendleft' style='border-bottom:0px;height:100%;'>&nbsp;</div>
			<div class='fronttab'>Species List</div>
			<div class="midright" style='border-bottom:0px;height:100%;'>&nbsp;</div>
			<div class='backtab'><a href='list.php'>Specimen List</a></div>
			<div class="midright">&nbsp;</div>
			<div class='backtab'><a href='maps/index.php'>Maps</a></div>
			<div class='backendright'>&nbsp;</div>
		</div>

		<div style='margin:10px;float:left;'>
			<form name="changetaxonomy" id="changetaxonomy" action="checklist.php" method="get">
				Taxonomic Filter:
					<select id="taxonfilter" name="taxonfilter" onchange="document.changetaxonomy.submit();">
						<option value="0">Raw Data</option>
						<?php 
							$taxonAuthList = $checklistManager->getTaxonAuthorityList();
							foreach($taxonAuthList as $taCode => $taValue){
								echo "<option value='".$taCode."' ".($taCode == $taxonFilter?"SELECTED":"").">".$taValue."</option>";
							}
	                        ?>
					</select>
			</form>
		</div>
		<?php if($keyModIsActive){ ?>
		<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Open Checklist in Interactive Key Interface'>
			<a href="checklistsymbiota.php?taxonfilter=<?php echo $taxonFilter; ?>"><img width='15px' src='../images/key.jpg'/></a>
		</div>
		<?php } ?>
		<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Download Checklist Data'>
			<a href='download/downloadhandler.php?dltype=checklist&taxonFilterCode=<?php echo $taxonFilter; ?>'><img src='../images/dl.png'/></a>
		</div>
		<div style="clear:both;"><hr/></div>
		<?php
			$checklistArr = $checklistManager->getChecklist($taxonFilter);
			echo "<div style='font-weight:bold;font-size:125%;'>Taxa Count: ".$checklistManager->getChecklistTaxaCnt()."</div>";
			$undFamilyArray = Array();
			if(array_key_exists("undefined",$checklistArr)) $undFamilyArray = $checklistArr["undefined"]; 
			foreach($checklistArr as $family => $sciNameArr){
				echo "<div style='margin-left:5;margin-top:5;'><h3>".$family."</h3></div>";
				foreach($sciNameArr as $sciName){
					echo "<div style='margin-left:20;font-style:italic;'><a target='_blank' href='../taxa/index.php?taxon=".$sciName."'>".$sciName."</a></div>";
				}
			}
			if($undFamilyArray){
				echo "<div style='margin-left:5;margin-top:5;'><h3>Family Not Defined</h3></div>";
				foreach($undFamilyArray as $sciName){
					echo "<div style='margin-left:20;font-style:italic;'><a target='_blank' href='../taxa/index.php?taxon=".$sciName."'>".$sciName."</a></div>";
				}
			}
		?>
	</div>
	<?php 
		include($serverRoot."/util/footer.php");
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
