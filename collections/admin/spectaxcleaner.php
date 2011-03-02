<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecTaxCleanerManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$cleanManager = new SpecTaxCleanerManager();
$collArr = $cleanManager->getCollectionList($collId, $userRights);

$isEditor = false;
if($symbUid && ($isAdmin || ($collId && (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))))){
 	$isEditor = true;
}

$status = "";
if($isEditor){
	if($action == 'verifyscinames'){
		$cleanManager->verifySciNames($collId);
	}
	elseif($action == 'showscinames'){
		
	}
}

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
					$cName = array_shift($collArr);
					echo '<h1>'.$cName.'</h1>';
					?>
					<form name="mainmenu" action="spectaxcleaner.php" method="get">
						<fieldset>
							<legend>Main Menu</legend>
							<div>
								<input type="radio" name="action" value="verifyscinames" /> 
								Verify New Specimen Scientific Names
							</div>
							<div>
								<input type="radio" name="action" value="showscinames" /> 
								Display specimen scientific names not in taxonomic thesaurus 
							</div>
							<div>
								<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
								<input type="submit" name="submitbut" value="Perform Action" />
								
							</div>								
						</fieldset>
					</form>
					<?php 
				}
				else{
					$collList = $cleanManager;
					?>
					<h1><?php echo $defaultTitle; ?> Collections </h1>
					<div style='margin:10px;'>Select a collection to see full details. </div>
					<ul>
					<?php 
					foreach($collList as $collId => $collName){
						?>
						<li>
							<a href="spectaxcleaner.php?collid=<?php echo $collId; ?>">
								<?php echo $collName; ?>
							</a>
						</li>
						<?php 
					}
				}
			}
			else{
				?>
				<div style='font-weight:bold;'>
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/editor/editreviewer.php?collid=<?php echo $collId; ?>'>login</a>!
				</div>
				<?php 
			}
			?>
		</div>
		<?php include($serverRoot.'/footer.php');?>
	</body>
</html>
