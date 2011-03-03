<?php
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/OccurrenceChecklistManager.php');
 header("Content-Type: text/html; charset=".$charset);

 $checklistManager = new OccurrenceChecklistManager();
 $taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;

?>

<html>
    <head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
        <title><?php echo $defaultTitle; ?> Dynamic Checklist</title>
        <link rel="stylesheet" href="../css/main.css" type="text/css">
		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', '<?php echo $googleAnalyticsKey; ?>']);
			_gaq.push(['_trackPageview']);
		
			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>
    </head>
    <body>
<?php
	$displayLeftMenu = (isset($collections_checklistMenu)?$collections_checklistMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_checklistCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_checklistCrumbs;
		echo "<b>Dynamic Checklist</b>";
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

		<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Download Checklist Data'>
			<a href='download/downloadhandler.php?dltype=checklist&taxonFilterCode=<?php echo $taxonFilter; ?>'>
				<img width='15px' src='../images/dl.png'/>
			</a>
		</div>
		<?php 
		if($keyModIsActive){
		?>
			<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Open in Interactive Key Interface'>
				<a href="checklistsymbiota.php?taxonfilter=<?php echo $taxonFilter; ?>&interface=key">
					<img width='15px' src='../images/key.jpg'/>
				</a>
			</div>
		<?php 
		}
		if($floraModIsActive){
		?>
			<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Open in Checklist Explorer Interface'>
				<a href="checklistsymbiota.php?taxonfilter=<?php echo $taxonFilter; ?>&interface=checklist">
					<img width='15px' src='../images/list.png'/>
				</a>
			</div>
		<?php
		}
		?>
		<div style='margin:10px;float:right;'>
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
		include($serverRoot.'/footer.php');
	?>
</body>

</html>
