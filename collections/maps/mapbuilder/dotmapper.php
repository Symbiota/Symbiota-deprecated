<?php
//error_reporting(E_ALL);
include_once("../../../util/symbini.php");
include_once("../../../util/dbconnection.php");
include_once("MapperManager.php");
header("Content-Type: text/html; charset=".$charset);

$mapId = array_key_exists("mapid",$_REQUEST)?$_REQUEST["mapid"]:"";
$taxon = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";
$mapperObj = new TaxaMapper($serverRoot);
if($mapId){
	if($tid || $taxon){
		if($tid){
			$mapperObj->createMap($tid,$mapId);
		}
		else{
			if($taxon == "all"){
				$mapperObj->createAllMaps($mapId);
			}
			else{
				$mapperObj->createSelectedMaps($taxon,$mapId);
			}
		}			
	}
}
?>
<html>
<head>
    <title><?php echo $defaultTitle; ?> - Map Builder</title>
    <link rel="stylesheet" href="../../../css/main.css" type="text/css" />
</head>

<body>

	<?php
		$displayLeftMenu = (isset($collections_maps_mapbuilder_dotmapperMenu)?$collections_maps_mapbuilder_dotmapperMenu:"true");
		include($serverRoot."/util/header.php");
		if(isset($collections_maps_mapbuilder_dotmapperCrumbs)) echo "<div class='navpath'>".$collections_maps_mapbuilder_dotmapperCrumbs."</div>";
	?> 
    <!-- This is inner text! -->
    <div id="innertext">
		<h2>Map Builder</h2>

        <div style="margin:20px;">
        	<form name="mapperform" id="mapperform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
        		<fieldset>
	        		<legend>1: Taxon Limits</legend>
	        		<div style='margin:5px;'>
	        			Taxon Name:<br/><select id="tid" name="tid">
		        			<option value="0">Select a Taxon</option>
			        		<?php 
			        			$taxaList = $mapperObj->getSpeciesList();
			        			foreach($taxaList as $tid => $taxonName){
									echo "<option value='".$tid."'>".$taxonName."</option>\n";
			        			}
			        		?>
		        		</select>
	        		</div>
	        		<div style="margin-left:50px;">-- OR --</div>
	        		<div style='margin:5px;'>
	        			Family or Genus:<br/><select id="taxon" name="taxon">
			        		<option value="0">Select a Taxonomic Group</option>
			        		<option value="all">All Accepted Taxa</option>
			        		<?php 
			        			$taxaList = $mapperObj->getTaxaList();
			        			foreach($taxaList as $tid => $taxonName){
									echo "<option>".$taxonName."</option>\n";
			        			}
			        		?>
		        		</select>
		        	</div>
	        	</fieldset>
        		<fieldset>
	        		<legend>2: Map Project</legend>
					<div style='margin:5px;'>
		        		<select id="mapid" name="mapid">
						<?php 
							$mapList = $mapperObj->getTaxaMapList();
							foreach($mapList as $dmid => $valueStr){
								echo "<option value='".$dmid."'>".$valueStr."</option>"; 
							}
						?>
						</select>
					</div>
	        	</fieldset>
				<input type="submit" name="submit" id="submit"/>
        	</form>
        </div>
	</div>
	<?php
	    include($serverRoot."/util/footer.php");
	?> 

</body>
</html>	
	

