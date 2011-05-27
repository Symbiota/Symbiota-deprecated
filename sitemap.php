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
    <script type="text/javascript">
	    function openPopup(urlStr,windowName){
	    	var wWidth = 900;
	    	try{
		    	if(document.getElementById('maintable').offsetWidth){
		    		wWidth = document.getElementById('maintable').offsetWidth*1.05;
		    	}
		    	else if(document.body.offsetWidth){
		    		wWidth = document.body.offsetWidth*0.9;
		    	}
	    	}
	    	catch(e){
	    	}
	    	newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	    	if (newWindow.opener == null) newWindow.opener = self;
	    }
    </script>
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
	            		<li>
	            			<a href="#" onclick="openPopup('imagelib/imageusagepolicy.php','crwindow'">
	            				Usage Policy and Copyright Information
	            			</a>
	            		</li>
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
	            	
		        <fieldset style="margin:30px 0px 10px 10px;padding:15px;">
		            <legend><b>Data Editing Tools</b></legend>
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
						
						<h3>Images</h3>
						<div style="margin:10px;">
							See the Symbiota documentation on 
							<a href="http://symbiota.org/tiki/tiki-index.php?page=Image+Submission">Image Submission</a> 
							for an overview of how images are managed within a Symbiota data portal. Field images without 
							detailed locality information can be uploaded using the Taxon Species Profile page (details below).
							Specimen images are loaded through the Specimen Editing page or through a batch upload process 
							established by a portal manager. Image Observations (Image Vouchers) with detailed locality information can be 
							uploaded using the link below. Note that you will need the necessary permission assignments to use this 
							feature. 
						</div>
						<ul>
							<li><a href="collections/editor/observationsubmit.php">Image Observation Submission Module</a></li>
						</ul>

						<h3>Floristic Projects</h3>
						<div style="margin:10px;">
							Click on any project below to edit the metadata for that project. 
						</div>
						<ul>
							<?php 
	            			if($isAdmin){
								foreach($projList as $pid => $pArr){
									echo '<li><a href="'.$clientRoot.'/projects/index.php?proj='.$pid.'&emode=1">'.$pArr['name'].'</a></li>';
		            			}
							}
							else{
								echo '<li>You are not authorized to edit any of the Projects</li>';
	            			}
							?>
						</ul>
	
	            		<h3>Taxon Profile Page</h3>
						<?php 
						if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
							?>
							<div style="margin:10px;">
								The following Species Profile page editing features are also available to editors via an
								editing link located in the upper right of each Species Profile page. 
							</div>
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
							?>
							<ul>
								<li>You are not yet authorized to edit the Taxon Profile</li>
							</ul>
							<?php 
						}
		            	?>
						<h3>Taxonomy</h3>
						<ul>
							<?php 
							if($isAdmin || array_key_exists("Taxonomy",$userRights)){
								?>
								<li><a href="taxa/admin/taxonomydisplay.php">Taxonomic Tree Viewer</a></li>
								<li>Edit Taxonomic Placement (use <a href="taxa/admin/taxonomydisplay.php">Taxonomic Tree Viewer)</a></li>
								<li><a href="taxa/admin/taxonomyloader.php">Add New Taxonomic Name</a></li>
								<li><a href="taxa/admin/taxaloader.php">Bathc Upload a Taxonomic Data File</a></li>
								<?php 
							}
							else{
								echo '<li>You are not authorized to edit taxonomy</li>';
							}
							?>
						</ul>
		            	
						<h3>Misc</h3>
						<ul>
							<?php 
							if($isAdmin){
								?>
		            			<li><a href="profile/usermanagement.php">User Permissions</a></li>
		            			<?php
							}
							else{
								?>
		            			<li>You are not authorized to manage permissions</li>
		            			<?php
							}
	            			?>
						</ul>

						<h3>Checklists</h3>
						<div style="margin:10px;">
							Tools for managing Checklists are available from each checklist display page.
							Editing symbols located in the upper right of the page will display 
							editing options for that checklist.  
							Below is a list of the checklists you are authorized to edit. 
						</div>
						<ul>
		            		<?php 
	            			if($isAdmin || array_key_exists("ClAdmin",$userRights)){
	            				$clList = $smManager->getChecklistList($isAdmin,(array_key_exists('ClAdmin',$userRights)?$userRights['ClAdmin']:0));
	            				foreach($clList as $k => $v){
		            				echo "<li><a href='".$clientRoot."/checklists/checklist.php?cl=".$k."&emode=1'>$v</a></li>";
		            			}
	            			}
	            			else{
								echo "<li>You are not authorized to edit any of the Checklists</li>";
	            			}
	            			?>
	            		</ul>

						<h3>Collections</h3>
						<div style="margin:10px;">
							Tools for managing collection data are available through each Collection Profile page, 
							which is generally accessed by clicking on the collection name within the specimen search engine.
							Clicking on the editing symbol located in the upper right of Collection Profile page will open 
							the editing pane and display a list of editing options.  
							Click on a collecion in the list below to go directly to this page. 
						</div>
						<?php 
						if($isAdmin){
							?>
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
						}
						?>
						<div style="margin:10px;">
							<div style="font-weight:bold;">
								List of collections you have permissions to edit
							</div>
	            			<ul>
	            			<?php 
							if($isAdmin || array_key_exists("CollAdmin",$userRights) || array_key_exists("CollEditor",$userRights)){
	            				$collList = $smManager->getCollectionList($userRights);
		            			foreach($collList as $k => $v){
		            				echo '<li>';
		            				echo '<a href="'.$clientRoot.'/collections/misc/collprofiles.php?collid='.$k.'&emode=1">';
		            				echo $v;
		            				echo '</a>';
		            				echo '</li>';
		            			}
	            			}
	            			else{
	            				echo "<li>You are not authorized to edit any of the Collections</li>";
	            			}
	            			?>
							</ul>
						</div>

	            		<?php 
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
