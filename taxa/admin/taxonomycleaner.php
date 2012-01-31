<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collId = $_REQUEST["collid"];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$displayIndex = array_key_exists('displayindex',$_REQUEST)?$_REQUEST['displayindex']:0;
$analyzeIndex = array_key_exists('analyzeindex',$_REQUEST)?$_REQUEST['analyzeindex']:0;

$cleanManager = new TaxonomyCleaner();
$cleanManager->setCollId($collId);
$collName = $cleanManager->getCollectionName();

$isEditor = false;
if($symbUid && ($isAdmin || ($collId && (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))))){
 	$isEditor = true;
}

$status = "";

?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> Specimen Taxonomic Name Cleaner</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<script language="javascript">
			function toggle(divName){
				divObj = document.getElementById(divName);
				if(divObj != null){
					if(divObj.style.display == "block"){
						divObj.style.display = "none";
					}
					else{
						divObj.style.display = "block";
					}
				}
				else{
					divObjs = document.getElementsByTagName("div");
					divObjLen = divObjs.length;
					for(i = 0; i < divObjLen; i++) {
						var obj = divObjs[i];
						if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
							if(obj.style.display=="none"){
								obj.style.display="inline";
							}
							else {
								obj.style.display="none";
							}
						}
					}
				}
			}
			
		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($collections_admin_spectaxcleanerMenu)?$collections_admin_spectaxcleanerMenu:'true');
		include($serverRoot.'/header.php');
		if(isset($collections_admin_spectaxcleanerCrumbs)){
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt; 
				<?php echo $collections_admin_spectaxcleanerCrumbs; ?>
				<b>Specimen Taxonomic Name Cleaner</b>
			</div>
			<?php 
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php 
			if($symbUid){
				if($status){ 
					?>
					<div style='float:left;margin:20px 0px 20px 0px;'>
						<hr/>
						<?php echo $status; ?>
						<hr/>
					</div>
					<?php 
				}
				if($collId){
					if($isEditor){
						?>
						<h1><?php echo $collName; ?></h1>
						<div>
							This module is designed to aid in cleaning scientific names that are not mapping  
							to the taxonomic thesaurus. Unmapped names are likely due to misspelllings, illegidimate names, 
							or simply because they just have not been added to the thesaurus.   
						</div>
						<div>
							Number of mismapped names: <?php echo $cleanManager->getTaxaCount(); ?>
						</div>
						<?php 
						if(!$action){
							?>
							<form name="mainmenu" action="spectaxcleaner.php" method="get">
								<fieldset>
									<legend><b>Main Menu</b></legend>
									<div>
										<input type="radio" name="submitaction" value="displaynames" /> 
										Display unverified names 
										<div style="margin-left:15px;">Start index: 
											<input name="displayindex" type="text" value="0" style="width:25px;" />
											(500 names at a time)
										</div> 
									</div>
									<div>
										<input type="radio" name="submitaction" value="analyzenames" /> 
										analyze names 
										<div style="margin-left:15px;">Start index: 
											<input name="analyzeindex" type="text" value="0" style="width:25px;" />
											(10 names at a time)
										</div> 
									</div>
									<div>
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input type="submit" name="submitbut" value="Perform Action" />
									</div>								
								</fieldset>
							</form>
							<?php
						}
						elseif($action == 'displaynames'){
							$nameArr = $cleanManager->getTaxaList($displayIndex);
							echo '<ul>';
							foreach($nameArr as $k => $sciName){
								echo '<li>';
								echo '<a href="spectaxcleaner.php?submitaction=analyzenames&analyzeindex='.$k.'">';
								echo '<b><i>'.$sciName.'</i></b>';
								echo '</a>';
								echo '</li>';
							}
							echo '</ul>';
						}
						elseif($action == 'analyzenames'){
							$nameArr = $cleanManager->analyzeTaxa($analyzeIndex);
							echo '<ul>';
							foreach($nameArr as $sn => $snArr){
								echo '<li>'.$sn.'</li>';
								if(array_key_exists('col',$snArr)){
									
								}
								else{
									echo '<div style="margin-left:15px;font-weight:bold;">';
									echo '<form name="taxaremapform" method="get" action="" >';
									echo 'Remap to: ';
									echo '<input type="input" name="remaptaxon" value="'.$sn.'" />';
									echo '<input type="submit" name="submitaction" value="Remap" />';
									echo '</form>';
									echo '</div>';
									if(array_key_exists('soundex',$snArr)){
										foreach($snArr['soundex'] as $t => $s){
											echo '<div style="margin-left:15px;font-weight:bold;">';
											echo $s;
											echo ' <a href="" title="Remap to this name...">==>></a>';
											echo '</div>';
										}
									}
								}
							}
							echo '</ul>';
						}
					}
					else{
						?>
						<div style="margin:20px;font-weight:bold;font-size:120%;">
							ERROR: You don't have the necessary permissions to access the editing tools for this collection.
						</div>
						<?php 
					}
				}
				else{
					?>
					<div style="margin:20px;font-weight:bold;font-size:120%;">
						ERROR: No collection selected.
					</div>
					<?php 
				}
			}
			else{
				?>
				<div style="font-weight:bold;">
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/admin/spectaxcleaner.php?collid=<?php echo $collId; ?>'>login</a>!
				</div>
				<?php 
			}
			?>
		</div>
		<?php include($serverRoot.'/footer.php');?>
	</body>
</html>
