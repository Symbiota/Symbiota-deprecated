<?php
//error_reporting(E_ALL);
include_once('config/symbini.php');
include_once($serverRoot.'/classes/SiteMapManager.php');

header("Content-Type: text/html; charset=".$charset);
$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:''; 

$smManager = new SiteMapManager();
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Site Map</title>
	<link rel="stylesheet" href="css/main.css" type="text/css" />
	<script type="text/javascript">
		function submitTaxaNoImgForm(f){
			if(f.clid.value != ""){
				f.submit();
			}
			return false;
		}
	</script>
	<script type="text/javascript" src="js/symb/shared.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($sitemapMenu)?$sitemapMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($sitemapCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='index.php'>Home</a> &gt; ";
		echo $sitemapCrumbs;
		echo " <b>Sitemap</b>";
		echo "</div>";
	}
		
	?> 
		<!-- This is inner text! --> 
		<div id="innertext">
			<h1>Site Map</h1>
			<div style="margin:10px;">
				<h2>Collections</h2>
				<ul>
					<li><a href="collections/index.php">Search Engine</a> - search Collections</li>
					<li><a href="collections/misc/collprofiles.php">Collections</a> - list of collection participating in project</li>
					<li><a href="collections/datasets/datapublisher.php">Darwin Core Archives</a> - published datasets of selected collections</li>
					<?php 
					if(file_exists('webservices/dwc/rss.xml')){
						echo '<li style="margin-left:15px;"><a href="webservices/dwc/rss.xml">RSS Feed</a></li>';
					}
					?>
					<li><a href="collections/misc/rarespecies.php">Rare Species</a> - list of taxa where locality information is hidden due to rare/threatened/endangered status</li>
					
				</ul>
					
				<h2>Image Library</h2>
				<ul>
					<li><a href="imagelib/index.php">Image Library</a></li>
					<li><a href="imagelib/photographers.php">Contributing Photographers</a></li>
					<li><a href="misc/usagepolicy.php">Usage Policy and Copyright Information</a></li>
				</ul>
	
				<?php 
				$clList = $smManager->getChecklistList($isAdmin,(array_key_exists('ClAdmin',$userRights)?$userRights['ClAdmin']:0));
				$projList = $smManager->getProjectList();
				if($projList){
					echo '<h2>Biotic Inventory Projects</h2><ul>';
					foreach($projList as $pid => $pArr){
						echo "<li><a href='projects/index.php?proj=".$pid."'>".$pArr["name"]."</a></li>\n";
						echo "<ul><li>Manager: ".$pArr["managers"]."</li></ul>\n";
					}
					echo '</ul>';
				}
				?>

				<h2>Dynamic Species Lists</h2>
				<ul>
					<li>
						<a href="checklists/dynamicmap.php?interface=checklist">
							Checklist
						</a> 
						- dynamically build a checklist using georeferenced specimen records
					</li>
					<li>
						<a href="checklists/dynamicmap.php?interface=key">
							Dynamic Key
						</a> 
						- dynamically build a key using georeferenced specimen records
					</li>
				</ul>

				<fieldset style="margin:30px 0px 10px 10px;padding:15px;">
					<legend><b>Data Management Tools</b></legend>
					<?php 
					if($symbUid){
						if($isAdmin){
							?>
							<h3>Administrative Functions (Super Admins only)</h3>
							<ul>
								<li><a href="profile/usermanagement.php">User Permissions</a></li>
<!-- 								<li><a href="profile/usertaxonomymanager.php">User Taxonomy Relationships</a></li>  -->
							</ul>
							<?php
						}

						if($keyModIsActive || array_key_exists("KeyAdmin",$userRights)){ 
							$clActive = array();
							if($clList && isset($userRights['ClAdmin'])){
								$clActive = array_intersect_key($clList,array_flip($userRights['ClAdmin']));
							}
							?>
							<h3>Identification Keys</h3>
							<?php 
							if(!$keyModIsActive && array_key_exists("KeyAdmin",$userRights)){
								?>
								<div style="color:red;margin-left:10px;">
									Note: The Identification Key module is deactivated within this portal. 
									However, as a Identification Key Adminitrator you have access to editing tools and 
									keys for development and testing.
								</div>
								<?php 
							}
							?>
							<ul>
								<?php 
								if($isAdmin || array_key_exists("KeyAdmin",$userRights)){
									?>
									<li>
										You are authorized to access the <a href="<?php echo $clientRoot; ?>/ident/admin/index.php">Characters and Character States Editor</a>
									</li>
									<?php 
									if($clActive){
										asort($clActive);
										?>
										<li id="testkeyhead" style="margin-left:15px;">
											<a href="#" onclick="toggle('testkey');toggle('testkeyhead');return false;">Display Test Keys</a>
										</li>
										<div id="testkey" style="display:none;margin-left:15px;">
											<?php 
											foreach($clActive as $clKey => $clValue){
												echo '<li>';
												echo '<a href="'.$clientRoot.'/ident/key.php?cl='.$clKey.'&taxon=All+Species">'.$clValue.'</a>';
												echo '</li>';
											}
											?>
										</div>
										<?php 
									}
								}
								if($isAdmin || array_key_exists("KeyEditor",$userRights) || array_key_exists("KeyAdmin",$userRights)){ 
									?>
									<li>
										You are authorized to edit Identification Keys. 
										To edit morphological characters, login and go to any key. Open the 
										morphological character editor by clicking on the 
										editing symbol to the right of Scientific Name that you wish to modify. 
									</li>
									<li>
										For coding characters in a table format, open the   
										<a href="<?php echo $clientRoot; ?>/ident/tools/massupdate.php">Mass-Update Editor</a><br/>
										<?php 
										if($projList){
											//Show Checklists that user has explicit editing rights
											if($clActive){
												echo '<div style="margin:5px 0px 0px 10px;"><b>Editor by Checklist</b></div>';
												echo '<ul>';
												foreach($clActive as $vClid => $name){
													echo "<li><a href='".$clientRoot."/ident/tools/massupdate.php?clf=".$vClid."'>".$name."</a></li>";
												}
												echo '</ul>';
											}
											//Show projects for access to a greater scope of checklist 
											echo '<div style="margin:5px 0px 0px 10px;"><b>Editor by Inventory Project</b></div>';
											foreach($projList as $pid => $pArr){
												echo '<ul>';
												echo "<li><a href='".$clientRoot."/ident/tools/massupdate.php?proj=".$pid."'>".$pArr["name"]."</a></li>";
												echo '</ul>';
											}
										}
										elseif($clList){
											echo '<div style="margin:5px 0px 0px 10px;"><b>Editor by Checklist</b></div>';
											echo '<ul>';
											foreach($clList as $clidKey => $clName){
												echo "<li><a href='".$clientRoot."/ident/tools/massupdate.php?clf=".$clidKey."'>".$clName."</a></li>";
											}
											echo '</ul>';
										}
										?>
									</li>
									<?php
								}
								else{
									?>
									<li>You are not authorized to edit Identification Keys</li>
									<?php 
								}
								?>
							</ul>
							<?php
						}
						?>
						<h3>Images</h3>
						<div style="margin:10px;">
							See the Symbiota documentation on 
							<a href="http://symbiota.org/tiki/tiki-index.php?page=Image+Submission">Image Submission</a> 
							for an overview of how images are managed within a Symbiota data portal. Field images without 
							detailed locality information can be uploaded using the Taxon Species Profile page.
							Specimen images are loaded through the Specimen Editing page or through a batch upload process 
							established by a portal manager. Image Observations (Image Vouchers) with detailed locality information can be 
							uploaded using the link below. Note that you will need the necessary permission assignments to use this 
							feature. 
						</div>
						<ul>
							<?php 
							if($isAdmin || array_key_exists('TaxonProfile',$userRights)){ 
								?>
								<li>
									<a href="taxa/admin/tpeditor.php?tabindex=1" target="_blank">
										Basic Field Image Submission 
									</a>
								</li>
								<?php
							}
							if($isAdmin || array_key_exists("CollAdmin",$userRights) || array_key_exists("CollEditor",$userRights)){
							?>
							<li>
								<a href="collections/editor/observationsubmit.php">
									Image Observation Submission Module
								</a>
							</li>
							<?php 
							}
							if($isAdmin || array_key_exists('TaxonProfile',$userRights)){ 
								?>
								<li>
									<?php if($submitAction == 'taxanoimages') echo '<a name="taxanoimages"><a/>'; ?>
									<b>Taxa without images:</b> 
									<form name="taxanoimg" action="sitemap.php#taxanoimages" method="post" style="display:inline;"> 
										<select name="clid" onchange="submitTaxaNoImgForm(this.form);">
											<option value="">Select a Checklist</option>
											<option value="">-------------------------------</option>
											<?php 
												$clArr = $smManager->getChecklistList($isAdmin,(array_key_exists('ClAdmin',$userRights)?$userRights['ClAdmin']:0));
												foreach($clArr as $clid => $clname){
													echo '<option value="'.$clid.'">'.$clname."</option>\n";
												}
											?>
										</select>
										<input type="hidden" name="submitaction" value="taxanoimages" />
									</form>
									<?php 
									if($submitAction == 'taxanoimages'){
										$tArr = $smManager->getTaxaWithoutImages($_REQUEST['clid']);
										echo '<fieldset style="margin:10px;width:400px;">';
										echo '<div style="margin:10px;"><b>'.$clArr[$_REQUEST['clid']].':</b> '.count($tArr).' taxa without images</div>';
										echo "<ul style='margin:10px'>\n";
										foreach($tArr as $tid => $sn){
											echo "<li><a href='taxa/admin/tpeditor.php?tid=".$tid."&category=imageadd&tabindex=3' target='_blank'>".$sn."</a></li>\n";
										}
										echo "</ul>\n";
										echo '</fieldset>';
									}
									?>
								</li>
								<li>
									<?php if($submitAction == 'taxanofieldimages') echo '<a name="taxanofieldimages"><a/>'; ?>
									<b>Taxa without field images:</b> 
									<form name="taxanofieldimg" action="sitemap.php#taxanofieldimages" method="post" style="display:inline;"> 
										<select name="clid" onchange="submitTaxaNoImgForm(this.form);">
											<option value="">Select a Checklist</option>
											<option value="">--------------------------------</option>
											<?php 
												foreach($clArr as $clid => $clname){
													echo '<option value="'.$clid.'">'.$clname."</option>\n";
												}
											?>
										</select>
										<input type="hidden" name="submitaction" value="taxanofieldimages" />
									</form>
									<?php 
									if($submitAction == 'taxanofieldimages'){
										$tArr = $smManager->getTaxaWithoutImages($_REQUEST['clid'],true);
										echo '<fieldset style="margin:10px;width:400px;">';
										echo '<div style="margin:10px;"><b>'.$clArr[$_REQUEST['clid']].':</b> '.count($tArr).' taxa without field images</div>';
										echo "<ul>";
										foreach($tArr as $tid => $sn){
											echo '<li>';
											echo '<a href="taxa/admin/tpeditor.php?tid='.$tid.'&category=imageadd&tabindex=3" target="_blank">'.$sn.'</a>';
											echo "</li>";
										}
										echo "</ul>";
										echo '</fieldset>';
									}
									?>
								</li>
							<?php }?>
						</ul>

						<h3>Biotic Inventory Projects</h3>
						<ul>
							<?php 
							if($isAdmin){
								echo '<li><a href="projects/index.php?newproj=1">Add a New Project</a></li>';
								if($projList){
									echo '<li><b>List of Current Projects</b> (click to edit)</li>';
									echo '<ul>';
									foreach($projList as $pid => $pArr){
										echo '<li><a href="'.$clientRoot.'/projects/index.php?proj='.$pid.'&emode=1">'.$pArr['name'].'</a></li>';
									}
									echo '</ul>';
								}
								else{
									echo '<li>There are no projects in the system</li>';	
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
								<li><a href="taxa/admin/tpeditor.php?taxon=&tabindex=4">Text Descriptions</a></li>
								<li><a href="taxa/admin/tpeditor.php?taxon=&tabindex=1">Edit Images</a></li>
								<li style="margin-left:15px;"><a href="taxa/admin/tpeditor.php?taxon=&category=imagequicksort&tabindex=2">Edit Image Sorting Order</a></li>
								<li style="margin-left:15px;"><a href="taxa/admin/tpeditor.php?taxon=&category=imageadd&tabindex=3">Add a New Image</a></li>
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
								<li><a href="taxa/admin/taxaloader.php">Batch
								 Upload a Taxonomic Data File</a></li>
								<?php 
							}
							else{
								echo '<li>You are not authorized to edit taxonomy</li>';
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
							if($clList){
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
							Tools for managing data specific to a particular collection are available through the collection's profile page. 
							Clicking on a collection name in the list below will take you to this page for that given collection. 
							An additional method to reach this page is by clicking on the collection name within the specimen search engine.
							The editing symbol located in the upper right of Collection Profile page will open 
							the editing pane and display a list of editing options.  
						</div>
						<?php 
						if($isAdmin){
							?>
							<ul>
								<li>
									<a href="<?php echo $clientRoot; ?>/collections/misc/collmetadata.php">
										Create a New Collection or Observation Profile
									</a>
								</li>
								<li>
									<a href="<?php echo $clientRoot; ?>/admin/guidmapper.php">
										Collection GUID Mapper
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
							$smManager->setCollectionList();
							if($collList = $smManager->getCollArr()){
								foreach($collList as $k => $cArr){
									echo '<li>';
									echo '<a href="'.$clientRoot.'/collections/misc/collprofiles.php?collid='.$k.'&emode=1">';
									echo $cArr['name'];
									echo '</a>';
									echo '</li>';
								}
							}
							else{
								echo "<li>You have no explicit editing permissions for a particular collections</li>";
							}
							?>
							</ul>
						</div>

						<h3>Observations</h3>
						<div style="margin:10px;">
							Data management for observation projects is handled in a similar manner to what is described in the Collections paragraph above.
							One difference is the General Observation project. This project serves two central purposes: 
							1) Allows registered users to submit a image voucherd field observation. 
							2) Allows collectors to enter their own collection data for label printing and to make it available for transfer 
							to collections obtaining the physical specimens through donations or exchange.
							Visit the <a href="http://symbiota.org/tiki/tiki-index.php?page=Specimen+Label+Printing" target="_blank">Symbiota Documentation</a> for more information on specimen processing capabilites.  
							Note that observation projects are not activated on all Symbiota data portals. 
						</div>
						<div style="margin:10px;">
							<?php 
							$obsList = $smManager->getObsArr();
							$genObsList = $smManager->getGenObsArr();
							$obsManagementStr = '';
							?>
							<div style="font-weight:bold;">
								Observation Image Voucher Submission
							</div>
							<ul>
								<?php 
								if($obsList){
									foreach($genObsList as $k => $oArr){
										?>
										<li>
											<a href="collections/editor/observationsubmit.php?collid=<?php echo $k; ?>">
												<?php echo $oArr['name']; ?>
											</a>
										</li>
										<?php
										if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid='.$k.'&emode=1">'.$oArr['name']."</a></li>\n";
									}
									foreach($obsList as $k => $oArr){
										?>
										<li>
											<a href="collections/editor/observationsubmit.php?collid=<?php echo $k; ?>">
												<?php echo $oArr['name']; ?>
											</a>
										</li>
										<?php
										if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid='.$k.'&emode=1">'.$oArr['name']."</a></li>\n";
									}
								}
								else{
									echo "<li>There are no Observation Projects to which you have permissions</li>";
								}
								?>
							</ul>
							<?php
							if($genObsList){ 
								?>
								<div style="font-weight:bold;">
									Personal Specimen Management and Label Printing Features
								</div>
								<ul>
									<?php 
									foreach($genObsList as $k => $oArr){
										?>
										<li>
											<a href="collections/misc/collprofiles.php?collid=<?php echo $k; ?>&emode=1">
												<?php echo $oArr['name']; ?>
											</a>
										</li>
										<?php 
									}
									?>
								</ul>
								<?php
							}
							if($obsManagementStr){
								?>
								<div style="font-weight:bold;">
									Observation project Management
								</div>
								<ul>
									<?php echo $obsManagementStr; ?>
								</ul>
							<?php 
							}
						?>
						</div>
						<?php 
					}
					else{
						echo 'Please <a href="'.$clientRoot.'/profile/index.php?refurl=../sitemap.php">login</a> to access editing tools.<br/>'.
						'Contact a portal administrator for obtaining editing permissions.';
					}
				?>
				</fieldset>

				<h2>About Symbiota</h2>
				<ul>
					<li>
						Schema Version <?php echo $smManager->getSchemaVersion(); ?>
					</li>
				</ul>
			</div>
		</div>
	<?php
		include($serverRoot.'/footer.php');
	?> 

</body>
</html>
