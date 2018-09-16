<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyCleaner.php');

header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/taxonomycleaner.php?'.$_SERVER['QUERY_STRING']);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST["collid"]:0;
$autoClean = array_key_exists('autoclean',$_POST)?$_POST['autoclean']:0;
$targetKingdom = array_key_exists('targetkingdom',$_POST)?$_POST['targetkingdom']:0;
$taxResource = array_key_exists('taxresource',$_POST)?$_POST['taxresource']:array();
$startIndex = array_key_exists('startindex',$_POST)?$_POST['startindex']:'';
$limit = array_key_exists('limit',$_POST)?$_POST['limit']:20;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$cleanManager = new TaxonomyCleaner();
if(is_array($collid)) $collid = implode(',',$collid);
$activeCollArr = explode(',', $collid);

foreach($activeCollArr as $k => $id){
	if(!isset($USER_RIGHTS["CollAdmin"]) || !in_array($id,$USER_RIGHTS["CollAdmin"])) unset($activeCollArr[$k]);
}
if(!$activeCollArr && strpos($collid, ',')) $collid = 0;
$cleanManager->setCollId($IS_ADMIN?$collid:implode(',',$activeCollArr));

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}
elseif($activeCollArr){
	$isEditor = true;
}
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Occurrence Taxon Cleaner</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../js/jquery-ui-1.12.1/jquery-ui.min.css?ver=3" type="text/css" rel="Stylesheet" />
		<script src="../../js/jquery-3.2.1.min.js?ver=3" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js?ver=3" type="text/javascript"></script>
		<script>

			var cache = {};
			$( document ).ready(function() {
				$(".displayOnLoad").show();
				$(".hideOnLoad").hide();

				$(".taxon").each(function(){
					$( this ).autocomplete({
						minLength: 2,
						autoFocus: true,
						source: function( request, response ) {
							var term = request.term;
							if ( term in cache ) {
								response( cache[ term ] );
								return;
							}
							$.getJSON( "rpc/taxasuggest.php", request, function( data, status, xhr ) {
								cache[ term ] = data;
								response( data );
							});
						},
						change: function(event,ui) {
							if(ui.item == null && this.value.trim() != ""){
								alert("Scientific name not found in Thesaurus.");
								this.focus();
								this.form.tid.value = "";
							}
						},
						focus: function( event, ui ) {
							this.form.tid.value = ui.item.id;
						},
						select: function( event, ui ) {
							this.form.tid.value = ui.item.id;
						}
					});
				});
			});

			function remappTaxon(oldName,targetTid,idQualifier,msgCode){
				$.ajax({
					type: "POST",
					url: "rpc/remaptaxon.php",
					dataType: "json",
					data: { collid: "<?php echo $collid; ?>", oldsciname: oldName, tid: targetTid, idq: idQualifier }
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

			function batchUpdate(f, oldName, itemCnt){
				if(f.tid.value == ""){
					alert("Taxon not found within taxonomic thesaurus");
					return false;
				}
				else{
					remappTaxon(oldName, f.tid.value, '', itemCnt+"-c");
				}
			}

			function checkSelectCollidForm(f){
				var formVerified = false;
				for(var h=0;h<f.length;h++){
					if(f.elements[h].name == "collid[]" && f.elements[h].checked){
						formVerified = true;
						break;
					}
				}
				if(!formVerified){
					alert("Please choose at least one collection!");
					return false;
				}
				return true;
			}

			function selectAllCollections(cbObj){
				var cbStatus = cbObj.checked
				var f = cbObj.form;
				for(var i=0;i<f.length;i++){
					if(f.elements[i].name == "collid[]") f.elements[i].checked = cbStatus;
				}
			}

			function verifyCleanerForm(f){
				if(f.targetkingdom.value == ""){
					alert("Select target kingdom for collection");
					return false;
				}
				return true;
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
			<?php
			if($collid && is_numeric($collid)){
				?>
				<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management Menu</a> &gt;&gt;
				<a href="index.php?collid=<?php echo $collid; ?>&emode=1">Data Cleaning Menu</a> &gt;&gt;
				<?php
			}
			else{
				?>
				<a href="../../profile/viewprofile.php?tabindex=1">Specimen Management</a> &gt;&gt;
				<?php
			}
			?>
			<b>Taxonomic Name Cleaner</b>
		</div>
		<!-- inner text block -->
		<div id="innertext">
			<?php
			$collMap = $cleanManager->getCollMap();
			if($collid){
				if($isEditor){
					?>
					<div style="float:left;font-weight: bold; font-size: 130%; margin-bottom: 10px">
						<?php
						if(is_numeric($collid)){
							echo $collMap[$collid]['collectionname'].' ('.$collMap[$collid]['code'].')';
						}
						else{
							echo 'Multiple Collection Cleaning Tool (<a href="#" onclick="$(\'#collDiv\').show()" style="color:blue;text-decoration:underline">'.count($activeCollArr).' collections</a>)';
						}
						?>
					</div>
					<?php
					if(count($collMap) > 1 && $activeCollArr){
						?>
						<div style="float:left;margin-left:5px;"><a href="#" onclick="toggle('mult_coll_fs')"><img src="../../images/add.png" style="width:12px" /></a></div>
						<div style="clear:both">
							<fieldset id="mult_coll_fs" style="display:none;padding: 15px;margin:20px;">
								<legend><b>Multiple Collection Selector</b></legend>
								<form name="selectcollidform" action="taxonomycleaner.php" method="post" onsubmit="return checkSelectCollidForm(this)">
									<div><input name="selectall" type="checkbox" onclick="selectAllCollections(this);" /> Select / Unselect All</div>
									<?php
									foreach($collMap as $id => $collArr){
										if(in_array($id, $USER_RIGHTS["CollAdmin"])){
											echo '<div>';
											echo '<input name="collid[]" type="checkbox" value="'.$id.'" '.(in_array($id,$activeCollArr)?'CHECKED':'').' /> ';
											echo $collArr['collectionname'].' ('.$collArr['code'].')';
											echo '</div>';
										}
									}
									?>
									<div style="margin: 15px">
										<button name="submitaction" type="submit" value="EvaluateCollections">Evaluate Collections</button>
									</div>
								</form>
								<div>* Only collections with administrative access are shown</div>
							</fieldset>
						</div>
						<?php
					}
					if(count($activeCollArr) > 1){
						echo '<div id="collDiv" style="display:none;margin:0px 20px;clear:both;">';
						foreach($activeCollArr as $activeCollid){
							echo '<div>'.$collMap[$activeCollid]['collectionname'].' ('.$collMap[$activeCollid]['code'].')</div>';
						}
						echo '</div>';
					}
					?>
					<div style="margin:20px;clear:both;">
						<?php
						if($action){
							if($action == 'deepindex'){
								$cleanManager->deepIndexTaxa();
							}
							elseif($action == 'AnalyzingNames'){
								echo '<ul>';
								$cleanManager->setAutoClean($autoClean);
								$cleanManager->setTargetKingdom($targetKingdom);
								$startIndex = $cleanManager->analyzeTaxa($taxResource, $startIndex, $limit);
								echo '</ul>';
							}
						}
						$badTaxaCount = $cleanManager->getBadTaxaCount();
						$badSpecimenCount = $cleanManager->getBadSpecimenCount();
						?>
					</div>
					<div style="margin:20px;">
						<fieldset style="padding:20px;">
							<legend><b>Action Menu</b></legend>
							<form name="maincleanform" action="taxonomycleaner.php" method="post" onsubmit="return verifyCleanerForm(this)">
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
											<fieldset style="padding:15px;margin:10px 0px">
												<legend><b>Taxonomic Resource</b></legend>
												<?php
												$taxResourceList = $cleanManager->getTaxonomicResourceList();
												foreach($taxResourceList as $taKey => $taValue){
													echo '<input name="taxresource[]" type="checkbox" value="'.$taKey.'" '.(in_array($taKey,$taxResource)?'checked':'').' /> '.$taValue.'<br/>';
												}
												?>
											</fieldset>
										</div>
										<div style="margin-bottom:5px;">
											Target Kingdom:
											<select name="targetkingdom">
												<option value="">Select Target Kingdom</option>
												<option value="">--------------------------</option>
												<?php
												$kingdomArr = $cleanManager->getKingdomArr();
												foreach($kingdomArr as $kTid => $kSciname){
													echo '<option value="'.$kTid.':'.$kSciname.'" '.($targetKingdom==$kTid?'SELECTED':'').'>'.$kSciname.'</option>';
												}
												?>
											</select>
										</div>
										<div style="margin-bottom:5px;">
											Names Processed per Run: <input name="limit" type="text" value="<?php echo $limit; ?>" style="width:40px" />
										</div>
										<div style="margin-bottom:5px;">
											Start Index: <input name="startindex" type="text" value="<?php echo $startIndex; ?>" title="Enter a taxon name or letter of the alphabet to indicate where the processing should start" />
										</div>
										<div style="height:50px;">
											<div style="">Clean and Mapping Function:</div>
											<div style="float:left;margin-left:15px;"><input name="autoclean" type="radio" value="0" <?php echo (!$autoClean?'checked':''); ?> /> Semi-Manual</div>
											<div style="float:left;margin-left:10px;"><input name="autoclean" type="radio" value="1" <?php echo ($autoClean==1?'checked':''); ?> /> Fully Automatic</div>
										</div>
										<div style="clear:both;">
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<button name="submitaction" type="submit" value="AnalyzingNames" ><?php echo ($startIndex?'Continue Analyzing Names':'Analyze Taxonomic Names'); ?></button>
										</div>
									</div>
								</div>
							</form>
							<hr/>
							<form name="deepindexform" action="taxonomycleaner.php" method="post">
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
					echo '<div><b>ERROR: you do not have permission to edit this collection</b></div>';
				}
			}
			elseif($collMap){
				?>
				<div style="margin:0px 0px 20px 20xp;font-weight:bold;font-size:120%;">Batch Taxonomic Cleaning Tool</div>
				<fieldset style="padding: 15px;margin:20px;">
					<legend><b>Collection Selector</b></legend>
					<form name="selectcollidform" action="taxonomycleaner.php" method="post" onsubmit="return checkSelectCollidForm(this)">
						<div><input name="selectall" type="checkbox" onclick="selectAllCollections(this);" /> Select / Unselect All</div>
						<?php
						foreach($collMap as $id => $collArr){
							echo '<div>';
							echo '<input name="collid[]" type="checkbox" value="'.$id.'" /> ';
							echo $collArr['collectionname'].' ('.$collArr['code'].')';
							echo '</div>';
						}
						?>
						<div style="margin: 15px">
							<button name="submitaction" type="submit" value="EvaluateCollections">Evaluate Collections</button>
						</div>
					</form>
					<div>* Only collections with administrative access are shown</div>
				</fieldset>
				<?php
			}
			else{
				?>
				<div style='font-weight:bold;font-size:120%;'>
					ERROR: Collection identifier is null
				</div>
				<?php
			}
			?>
		</div>
		<?php include($SERVER_ROOT.'/footer.php');?>
	</body>
</html>