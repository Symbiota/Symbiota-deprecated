<?php
//error_reporting(E_ALL);
include_once('config/symbini.php');
include_once($serverRoot.'/classes/SiteMapManager.php');

header("Content-Type: text/html; charset=".$charset);

$smManager = new SiteMapManager();
?>
<html>
<head>
    <title><?php echo $defaultTitle; ?> Site Map</title>
    <link rel="stylesheet" href="css/main.css" type="text/css" />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($sitemapMenu)?$sitemapMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($sitemapCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $sitemapCrumbs;
		echo " <b>Sitemap</b>";
		echo "</div>";
	}
	    
	?> 
        <!-- This is inner text! --> 
        <div class="innertext">
            <h1>Site Map</h1>
            <div style="margin:10px;">
	            <h2>Collections</h2>
	            	<ul>
	            		<li><a href="collections/misc/collprofiles.php">Collections</a> - list of collection participating in project</li>
	            		<li><a href="collections/index.php">Search Engine</a> - search Collections</li>
	            		<li><a href="collections/misc/rarespecies.php">Rare Species</a> - list of taxa where locality information is hidden due to rare/threatened/endangered status</li>
	            	</ul>
	            	
	            <h2>Image Library</h2>
	            	<ul>
	            		<li><a href="imagelib/index.php">Image Library</a> - list of species images</li>
	            		<li><a href="imagelib/photographers.php">Photographers</a> - list of individuals you have supplies images</li>
	            		<li><a href="javascript:var popupReference=window.open('util/imageusagepolicy.php','crwindow','toolbar=1,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=1,width=700,height=550,left=20,top=20');">Usage Policy</a> - copyright information</li>
	            	</ul>
	
	            <h2>Projects</h2>
	            	<ul>
	            		<li><a href="projects/index.php?proj=1">Arizona Flora</a></li>
	            			<ul>
	            				<li>Manager: <a href="http://nhc.asu.edu/vpherbarium/">Arizona State University Herbarium</a></li>
	            			</ul>
						<li><a href="projects/index.php?proj=2">New Mexico Flora Project</a></li>
							<ul>
								<li>No Managers Yet Defined</li>
							</ul>
	            		<li><a href="projects/index.php?proj=3">Sonoran Flora</a></li>
	            			<ul>
	            				<li>Manager: <a href="http://www.desertmuseum.org/">Arizona-Sonora Desert Museum</a></li>
	            				<li>Manager: <a href="http://www.conabio.gob.mx/remib_ingles/doctos/uson.html">Herbario de la Universidad de Sonora (DICTUS)</a></li>
	            			</ul>
						<li><a href="projects/index.php?proj=4">Teaching Checklists</a></li>
							<ul>
								<li>No Managers Yet Defined</li>
							</ul>
	            	</ul>
	            	
	            <h2>Misc Features</h2>
	            	<ul>
	            		<li><a href="ident/index.php">Identification Keys</a> - all keys registered within the system</li>
	            		<li><a href="checklists/index.php">Species Lists</a> - all species checklists registered within the system</li>
	            	</ul>
	            	
            	<?php if($symbUid){ ?>
		        <div class="fieldset" style="margin:30px 0px 10px 10px;">
		            <div class="legend">Data Editing Tools</div>
            		<h3>Identification Keys</h3>
						<ul>
		            		<?php if($isAdmin || in_array("IdentKey",$userRights)){ ?>
								<li>You are authorized to edit Identification Keys</li>
			            		<li>To add or remove species, edit checklist that is aligned with the key. 
			            		Note that you must have editing rights for that checklist.</li>
			            		<li>To edit morphological characters, login, go to key, and click on the 
			            		editing symbol to the right of Scientific Name that you wish to edit. 
			            		that you must have morphological character editing rights to edit keys. </li>
			            		<?php
		            		}
            				else{?>
								<li>You are not authorized to edit Identification Keys</li>
            				<?php }?>
						</ul>

            		<h3>Projects</h3>
	            		<?php 
	            			$projList = $smManager->getProjectList();
	            			if($projList){
		            			echo "<ul>";
	            				foreach($projList as $k => $v){
		            				echo "<li><a href='".$clientRoot."/projects/index.php?proj=".$k."&emode=1'>$v</a></li>";
		            			}
		            			echo "</ul>";
	            			}
	            			else{
	            				echo "<div style='margin:15px;'>You are not authorized to edit any of the Projects</div>";
	            			}
	            		?>
            		
            		<h3>Taxon Profile Page</h3>
					<?php if($isAdmin || in_array("TaxonProfile",$userRights)){?>
            			<ul>
            				<li><a href="taxa/admin/tpeditor.php?taxon=">Synonyms / Common Names</a></li>
							<li><a href="taxa/admin/tpdesceditor.php?taxon=&category=textdescr">Text Descriptions</a></li>
							<li><a href="taxa/admin/tpimageeditor.php?taxon=&category=images">Edit Images</a></li>
							<ul>
								<li><a href="taxa/admin/tpimageeditor.php?taxon=&category=imagequicksort">Edit Image Sorting Order</a></li>
								<li><a href="taxa/admin/tpimageeditor.php?taxon=&category=imageadd">Add a New Image</a></li>
							</ul>
            			</ul>
	            	<?php 
						}
						else{
							echo "<div>You are not yet authorized to edit the Taxon Profile</div>";
						}
	            	?>

            		<h3>Taxonomy</h3>
					<?php if($isAdmin || in_array("Taxonomy",$userRights)){?>
            			<ul>
							<li><a href="taxa/admin/taxonomydisplay.php">Taxonomic Tree Viewer</a></li>
							<li>Edit Taxonomic Placement (use <a href="taxa/admin/taxonomydisplay.php">Taxonomic Tree Viewer)</a></li>
							<li><a href="taxa/admin/taxonomyloader.php">Add New Taxonomic Name</a></li>
            			</ul>
	            	<?php 
						}
						else{
							echo "<div>You are not yet authorized to edit taxonomy</div>";
						}
	            	?>
	            	
	            	<h3>Misc</h3>
	            		<ul>
	            			<li><a href="collections/admin/observationuploader.php">Observation Batch Loader</a></li>
	            			<li><a href="profile/usermanagement.php">User Permissions</a></li>
            			</ul>
            		<h3>Collections</h3>
	            		<?php 
	            			$collList = $smManager->getCollectionList();
	            			if($collList){
	            				echo "<ul>";
		            			foreach($collList as $k => $v){
		            				echo "<li>$v</li>";
		            				echo "<ul>";
		            				echo "<li><a href='".$clientRoot."/collections/misc/collprofiles.php?collid=".$k."&emode=1'>View/Edit Metadata</a></li>";
		            				echo "<li><a href='".$clientRoot."/collections/admin/datauploader.php?collid=".$k."'>Upload Records</a></li>";
		            				echo "</ul>";
		            			}
		            			echo "</ul>";
	            			}
	            			else{
	            				echo "<div style='margin:15px;'>You are not authorized to edit any of the Collections</div>";
	            			}
	            		?>

            		<h3>Checklists</h3>
	            		<?php 
	            			$clList = $smManager->getChecklistList();
	            			if($clList){
	            				echo "<ul>";
	            				foreach($clList as $k => $v){
		            				echo "<li><a href='".$clientRoot."/checklists/checklist.php?cl=".$k."&emode=1'>$v</a></li>";
		            			}
		            			echo "</ul>";
	            			}
	            			else{
	            				echo "<div style='margin:15px;'>You are not authorized to edit any of the Checklists</div>";
	            			}
	            		?>
            		
	            </div>
            	<?php 
            	}
            	?>
			</div>
		</div>
	<?php
		include($serverRoot.'/footer.php');
	?> 

</body>
</html>
