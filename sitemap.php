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
	            		<li><a href="imagelib/index.php">Image Library</a></li>
	            		<li><a href="imagelib/photographers.php">Contributing Photographers</a></li>
	            		<li><a href="javascript:var popupReference=window.open('imagelib/imageusagepolicy.php','crwindow','toolbar=1,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=1,width=700,height=550,left=20,top=20');">Usage Policy and Copyright Information</a></li>
	            	</ul>
	
	            <h2>Projects</h2>
	            	<ul>
	            		<?php 
	            		$projList = $smManager->getProjectList();
	            		foreach($projList as $pid => $pArr){
	            			echo "<li><a href='projects/index.php?proj=".$pid."'>".$pArr["name"]."</a></li>\n";
	            			echo "<ul><li>Manager: ".$pArr["managers"]."</li></ul>\n";
	            		}
	            		?>
	            	</ul>
	            	
	            <h2>Misc Features</h2>
	            	<ul>
	            		<li><a href="ident/index.php">Identification Keys</a> - all keys registered within the system</li>
	            		<li><a href="checklists/index.php">Species Lists</a> - all species checklists registered within the system</li>
	            	</ul>
	            	
		        <fieldset style="margin:30px 0px 10px 10px;">
		            <legend>Data Editing Tools</legend>
	            	<?php if($symbUid){ ?>
	            		<h3>Identification Keys</h3>
							<ul>
			            		<?php if($isAdmin || array_key_exists("KeyEditor",$userRights)){ ?>
									<li>
										You are authorized to edit Identification Keys
									</li>
				            		<li>
				            			To add or remove species names with a keys checklist, access the checklist editor that is aligned with the key. 
				            			Note that you must have editing rights for that checklist.
				            		</li>
				            		<li>
				            			To edit morphological characters, login and go any key. Open the 
				            			morphological character editor by clicking on the 
				            			editing symbol to the right of Scientific Name that you wish to modify. 
				            		</li>
									<li>
										Click on check project name below to open the  
										<a href="<?php echo $clientRoot; ?>/ident/tools/massupdate.php">Mass-Update Editor</a>
										for that project  
										<ul>
											<?php 
											foreach($projList as $pid => $pArr){
												echo "<li><a href='".$clientRoot."/ident/tools/massupdate.php?proj=".$pid."'>".$pArr["name"]."</a></li>";
					            			}
					            			?>
			            				</ul>
									</li>
				            		<?php
			            		}
	            				else{?>
									<li>You are not authorized to edit Identification Keys</li>
	            				<?php }?>
							</ul>
	
	            		<h3>Projects</h3>
		            		<?php 
		            			if($isAdmin){
			            			echo "<ul>";
		            				foreach($projList as $pid => $pArr){
			            				echo "<li><a href='".$clientRoot."/projects/index.php?proj=".$pid."&emode=1'>".$pArr["name"]."</a></li>";
			            			}
			            			echo "</ul>";
		            			}
		            			else{
		            				echo "<div style='margin:15px;'>You are not authorized to edit any of the Projects</div>";
		            			}
		            		?>
	            		
	            		<h3>Taxon Profile Page</h3>
						<?php if($isAdmin || array_key_exists("TaxonProfile",$userRights)){?>
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
						<?php if($isAdmin || array_key_exists("Taxonomy",$userRights)){?>
	            			<ul>
								<li><a href="taxa/admin/taxonomydisplay.php">Taxonomic Tree Viewer</a></li>
								<li>Edit Taxonomic Placement (use <a href="taxa/admin/taxonomydisplay.php">Taxonomic Tree Viewer)</a></li>
								<li><a href="taxa/admin/taxonomyloader.php">Add New Taxonomic Name</a></li>
								<li><a href="taxa/admin/taxaloader.php">Bathc Upload a Taxonomic Data File</a></li>
	            			</ul>
		            	<?php 
							}
							else{
								echo "<div>You are not yet authorized to edit taxonomy</div>";
							}
		            	?>
		            	
		            	<h3>Misc</h3>
		            		<ul>
		            			<li><a href="profile/usermanagement.php">User Permissions</a></li>
	            			</ul>
	            		<h3>Collections</h3>
							<ul>
								<li>
									<a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php?newcoll=1">
										Create a New Collection Profile
									</a>
								</li>
								<li>
									<a href="<?php echo $clientRoot; ?>/collections/admin/specimenupload.php">
										Specimen Upload Management
									</a>
								</li>
	            			</ul>
		            		<?php 
		            			if($isAdmin || array_key_exists("CollAdmin",$userRights)){
		            				$collList = $smManager->getCollectionList((array_key_exists("CollAdmin",$userRights)?$userRights["CollAdmin"]:""));
		            				echo "<ul>";
			            			foreach($collList as $k => $v){
			            				echo "<li>$v</li>";
			            				echo "<ul>";
			            				echo "<li><a href='".$clientRoot."/collections/misc/collprofiles.php?collid=".$k."&emode=1'>View/Edit Metadata</a></li>";
			            				echo "<li><a href='".$clientRoot."/collections/admin/specimenupload.php?collid=".$k."'>Upload Records</a></li>";
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
	            			if($isAdmin || array_key_exists("ClAdmin",$userRights)){
	            				$clList = $smManager->getChecklistList((array_key_exists("ClAdmin",$userRights)?$userRights["ClAdmin"]:""));
	            				echo "<ul>";
	            				foreach($clList as $k => $v){
		            				echo "<li><a href='".$clientRoot."/checklists/checklist.php?cl=".$k."&emode=1'>$v</a></li>";
		            			}
		            			echo "</ul>";
	            			}
	            			else{
	            				echo "<div style='margin:15px;'>You are not authorized to edit any of the Checklists</div>";
	            			}
					}
					else{
						echo '<a href="'.$clientRoot.'/profile/index.php">Login</a> to view editing tools that you have permission to access. Please see you data administrator for editing permissions assignment procedures.';
					}
	            ?>
	            </fieldset>
			</div>
		</div>
	<?php
		include($serverRoot.'/footer.php');
	?> 

</body>
</html>
