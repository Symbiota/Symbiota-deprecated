<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collId = $_REQUEST["collid"];
$displayIndex = array_key_exists('displayindex',$_REQUEST)?$_REQUEST['displayindex']:0;
$analyzeIndex = array_key_exists('analyzeindex',$_REQUEST)?$_REQUEST['analyzeindex']:0;
$taxAuthId = array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:0;

$cleanManager;
$collName = '';

if($collId){
	$cleanManager = new TaxonomyCleaner();
	$cleanManager->setCollId($collId);
	$collName = $cleanManager->getCollectionName();
}
else{
	$cleanManager = new TaxonomyCleaner();
}
if($taxAuthId){
	$cleanManager->setTaxAuthId($taxAuthId);
}

$isEditor = false;
if($isAdmin){
	$isEditor = true;
}
else{
	if($collId){
		if(array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
			$isEditor = true;
		}
	}
	else{
		if(array_key_exists("Taxonomy",$userRights)) $isEditor = true;
	}
}

$status = "";

?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> Taxonomic Name Cleaner</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
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
		$displayLeftMenu = (isset($taxa_admin_taxonomycleanerMenu)?$taxa_admin_taxonomycleanerMenu:'true');
		include($serverRoot.'/header.php');
		if(isset($taxa_admin_taxonomycleanerCrumbs)){
			?>
			<div class='navpath'>
				<?php echo $taxa_admin_taxonomycleanerCrumbs; ?>
				<b>Taxonomic Name Cleaner</b>
			</div>
			<?php 
		}
		?>
		<!-- inner text block -->
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
				if($isEditor){
					if($collId){
						?>
						<h1><?php echo $collName; ?></h1>
						<div>
							This module is designed to aid in cleaning scientific names that are not mapping  
							to the taxonomic thesaurus. Unmapped names are likely due to misspelllings, illegidimate names, 
							or simply because they just have not yet been added to the thesaurus.   
						</div>
						<div>
							Number of mismapped names: <?php echo $cleanManager->getTaxaCount(); ?>
						</div>
						<?php 
						$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
						if(!$action){
							?>
							<form name="occurmainmenu" action="taxonomycleaner.php" method="post">
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
						<h1>Taxonomic Thesaurus Validator</h1>
						<div style="margin:15px;">
							This module is designed to aid in validating scientific names within the taxonomic thesauri. 
						</div>
						<?php
						$taxonomyAction = array_key_exists('taxonomysubmit',$_POST)?$_POST['taxonomysubmit']:'';
						if($taxonomyAction == 'Validate Names'){
							?>
							<div style="margin:15px;">
								<b>Validation Status:</b>
								<ul>
									<?php //$cleanManager->verifyTaxa($_POST['versource']); ?>
								</ul>
							</div>
							<?php
						}
						?>
						<div style="margin:15px;">
							<fieldset>
								<legend><b>Verification Status</b></legend>
								<?php 
								$vetArr = $cleanManager->getVerificationCounts();
								?>
								Full Verification: <?php $vetArr[1]; ?><br/>
								Suspect Status: <?php $vetArr[2]; ?><br/>
								Name Validated Only: <?php $vetArr[3]; ?><br/>
								Untested: <?php $vetArr[0]; ?>
							</fieldset>
						</div>
						<div style="margin:15px;">
							<form name="taxonomymainmenu" action="taxonomycleaner.php" method="post">
								<fieldset>
									<legend><b>Main Menu</b></legend>
									<div>
										<b>Testing Resource:</b><br/> 
										<input type="radio" name="versource" value="col" CHECKED /> 
										Catalogue of Life<br/>
									</div>
									<div>
										<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId; ?>" />
										<input type="submit" name="taxonomysubmit" value="Validate Names" />
									</div>								
								</fieldset>
							</form>
						
						</div>
						<?php 
					}
				}
				else{
					?>
					<div style="margin:20px;font-weight:bold;font-size:120%;">
						ERROR: You don't have the necessary permissions to access this data cleaning module.
					</div>
					<?php 
				}
			}
			else{
				?>
				<div style="font-weight:bold;">
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/taxa/admin/taxonomycleaner.php?collid=<?php echo $collId; ?>'>login</a>!
				</div>
				<?php 
			}
			?>
		</div>
		<?php include($serverRoot.'/footer.php');?>
	</body>
</html>
