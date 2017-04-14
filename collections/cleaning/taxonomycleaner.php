<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceCleaner.php');

header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/taxonomycleaner.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST["collid"];
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:5;

$cleanManager = new OccurrenceCleaner();
$cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$isEditor = false;
if($isAdmin){
	$isEditor = true;
}
else{
	if($collid){
		if(array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])){
			$isEditor = true;
		}
	}
}

?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> Occurrence Taxon Cleaner</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script language="javascript">
			function remappTaxon(oldName,targetTid,newName){
				$.ajax({
					type: "POST",
					url: "rpc/remaptaxon.php",
					dataType: "json",
					data: { collid: <?php echo $collid; ?>, oldsciname: oldName, tid: targetTid, newsciname: newName }
				}).done(function( res ) {
					if(res == "1"){
						alert("Taxon remapped successfully");
					}
					else{
						alert("Taxon failed to remap");
					}
				});
			}
			
		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($taxa_admin_taxonomycleanerMenu)?$taxa_admin_taxonomycleanerMenu:'true');
		include($serverRoot.'/header.php');
		if(isset($taxa_admin_taxonomycleanerCrumbs)){
			if($taxa_admin_taxonomycleanerCrumbs){
				?>
				<div class='navpath'>
					<?php echo $taxa_admin_taxonomycleanerCrumbs; ?>
					<b>Taxonomic Name Cleaner</b>
				</div>
				<?php 
			}
		}
		else{
			?>
			<div class='navpath'>
				<a href="../../index.php">Home</a> &gt;&gt;
				<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
				<b>Taxonomic Name Cleaner</b>
			</div>
			<?php 
		}
		?>
		<!-- inner text block -->
		<div id="innertext">
			<?php 
			if($isEditor){
				if($collid){
					?>
					<h1><?php echo $collMap['collectionname'].' ('.$collMap['code'].')'; ?></h1>
					<div style="margin:20px;">
						This module is designed to aid in cleaning scientific names within a collection. 
						Web services will be used to attempt to resolve names that are not mapped to the taxonomic thesaurus.   
					</div>
					<div style="margin:20px;">
						Number of mismapped names: <?php echo $cleanManager->getBadTaxaCount(); ?>
					</div>
					<div style="margin:20px;">
						<?php 
						$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
						if(!$action){
							?>
							<form name="occurmainmenu" action="taxonomycleaner.php" method="post">
								<fieldset>
									<legend><b>Main Menu</b></legend>
									<div style="margin-left:15px;">Start index: 
										<input name="index" type="text" value="0" style="width:25px;" />
										(50 names at a time)
									</div> 
									<div style="margin:20px">
										<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
										<input type="submit" name="submitaction" value="Analyze Names" />
									</div>								
								</fieldset>
							</form>
							<?php
						}
						elseif($action == 'Analyze Names'){
							echo '<ul>';
							$cleanManager->analyzeTaxa($start, $limit);
							echo '</ul>';
							echo '<div>';
							echo '<div style="margin:10px;"><a href="taxonomycleaner.php?collid='.$collid.'">Return to Main Menu</a></div>';
							echo '<div style="margin:10px;"><a href="taxonomycleaner.php?collid='.$collid.'&submitaction=Analyze%20Names">Continue Analyzing</a></div>';
						}
						?>
					</div>
					<?php 
				}
				else{
					?>
					<div style="margin:20px;font-weight:bold;font-size:120%;">
						ERROR: Collection identifier is NULL
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
			?>
		</div>
		<?php include($serverRoot.'/footer.php');?>
	</body>
</html>