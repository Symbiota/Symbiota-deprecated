<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyCleaner.php');

header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/taxonomycleaner.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST["collid"];
$start = array_key_exists('start',$_POST)?$_POST['start']:0;
$limit = array_key_exists('limit',$_POST)?$_POST['limit']:20;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';
$cleanManager = new TaxonomyCleaner();
$cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$isEditor = false;
if($isAdmin){
	$isEditor = true;
}
else{
	if($collid){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = true;
		}
	}
}

$badTaxaCount = $cleanManager->getBadTaxaCount();
$badSpecimenCount = $cleanManager->getBadSpecimenCount();
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Occurrence Taxon Cleaner</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script>
			$( document ).ready(function() {
				$(".displayOnLoad").show();
				$(".hideOnLoad").hide();
			});

			function remappTaxon(oldName,targetTid,newName,msgCode){
				$.ajax({
					type: "POST",
					url: "rpc/remaptaxon.php",
					dataType: "json",
					data: { collid: <?php echo $collid; ?>, oldsciname: oldName, tid: targetTid, newsciname: newName }
				}).done(function( res ) {
					if(res == "1"){
						$("#remapSpan-"+msgCode).text(" >>> Taxon remapped successfully!");
						$("#remapSpan-"+msgCode).css('color', 'green');
					}
					else{
						$("#remapSpan-"+msgCode).text(" >>> Taxon remapping failed!");
						$("#remapSpan-"+msgCode).css('color', 'orange');
					}
				});
				return false;
			}
			
		</script>
		<script src="../../js/symb/shared.js?ver=1" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($taxa_admin_taxonomycleanerMenu)?$taxa_admin_taxonomycleanerMenu:'true');
		include($SERVER_ROOT.'/header.php');
		?>
		<div class='navpath'>
			<a href="../../index.php">Home</a> &gt;&gt;
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management Menu</a> &gt;&gt;
			<a href="index.php?collid=<?php echo $collid; ?>&emode=1">Data Cleaning Menu</a> &gt;&gt;
			<b>Taxonomic Name Cleaner</b>
		</div>
		<!-- inner text block -->
		<div id="innertext">
			<?php 
			if($isEditor){
				if($collid){
					?>
					<h1><?php echo $collMap['collectionname'].' ('.$collMap['code'].')'; ?></h1>
					<div style="margin:20px;">
						<?php
						$startAdjustment = 0;
						if($action == 'deepindex'){
							$cleanManager->deepIndexTaxa();
						}
						elseif($action){
							echo '<ul>';
							$startAdjustment = $cleanManager->analyzeTaxa($start, $limit);
							echo '</ul>';
						}
						?>
					</div>
					<div style="margin:20px;">
						<fieldset style="padding:20px;">
							<legend><b>Action Menu</b></legend>
							<form name="occurmainmenu" action="taxonomycleaner.php" method="post">
								<div style="margin-bottom:15px;">
									<b>Specimen records not indexed to central taxonomic thesaurus</b>
									<div style="margin-left:10px;">
										<u>Specimens</u>: <?php echo $badSpecimenCount; ?><br/>
										<u>Scientific names</u>: <?php echo $badTaxaCount; ?>
									</div>
								</div>
								<hr/>
								<div style="margin:20px 10px">
									<div style="margin:10px 0px">
										Following tool will crawl through unindexed names and attempt to resolve name discrepancies
									</div>
									<div style="margin:10px;">
										<div style="margin-bottom:5px;">
											Processing limit: <input name="limit" type="text" value="<?php echo $limit; ?>" style="width:30px" />
										</div>
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="start" type="hidden" value="<?php echo $start+$limit-$startAdjustment; ?>" />
										<button name="submitaction" type="submit" value="submitaction" ><?php echo ($start?'Continue Analying Name':'Analyze Taxonomic Names'); ?></button>
										<div style="margin-top:10px;">
											<button name="submitaction" type="submit" value="submitaction" onclick="this.form.start.value = 0" >Restart from Beginning</button>
										</div>
									</div>
								</div>
							</form>
							<hr/>
							<form name="occurmainmenu" action="taxonomycleaner.php" method="post">
								<div style="margin:20px 10px">
									<div style="margin:10px 0px">
										Following tool will run a set of algorithms that will run names through several filters to improve linkages to taxonomic thesaurus 
									</div>
									<div style="margin:10px">
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<button name="submitaction" type="submit" value="deepindex">Deep Index Specimen Taxa</button>
									</div>
								</div>								
							</form>
						</fieldset>
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
		<?php include($SERVER_ROOT.'/footer.php');?>
	</body>
</html>