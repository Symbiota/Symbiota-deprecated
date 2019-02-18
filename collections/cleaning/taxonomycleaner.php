<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyCleaner.php');
include_once($SERVER_ROOT.'/content/lang/collections/cleaning/taxonomycleaner.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/taxonomycleaner.php?'.$_SERVER['QUERY_STRING']);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST["collid"]:0;
$autoClean = array_key_exists('autoclean',$_POST)?$_POST['autoclean']:0;
$taxResource = array_key_exists('taxresource',$_POST)?$_POST['taxresource']:array();
$startIndex = array_key_exists('startindex',$_POST)?$_POST['startindex']:'';
$limit = array_key_exists('limit',$_POST)?$_POST['limit']:20;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$cleanManager = new TaxonomyCleaner();
if(is_array($collid)) $collid = implode(',',$collid);
$cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}
else{
	if($collid){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = true;
		}
	}
}
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Occurrence Taxon Cleaner</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />

			<link href="../../css/bootstrap.min.css" rel="stylesheet" type="text/css">

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
					data: { collid: <?php echo $collid; ?>, oldsciname: oldName, tid: targetTid, idq: idQualifier }
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
					alert("<?php echo $LANG['PLEASE'];?>");
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
		</script>
		<script src="../../js/symb/shared.js?ver=1" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($taxa_admin_taxonomycleanerMenu)?$taxa_admin_taxonomycleanerMenu:'true');
		include($SERVER_ROOT.'/header.php');
		?>
		 <div class='navpath'>

			<?php
			if($collid && is_numeric($collid)){
				?>
				<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1"><?php echo $LANG['COLL_MANA'];?></a> &gt;&gt;
				<a href="index.php?collid=<?php echo $collid; ?>&emode=1"><?php echo $LANG['DATA_CLEAN'];?></a> &gt;&gt;
				<?php
			}
			else{
				?>
				<a href="../../sitemap.php">Site Map</a> &gt;&gt;
				<?php
			}
			?>
			<b>Taxonomic Name Cleaner</b>
		</div>
		<!-- inner text block -->
		<div id="innertext">
			<?php
			if($isEditor){
				if($collid){
					?>
					<div style="font-weight: bold; font-size: 130%; margin-bottom: 10px">
						<?php
						if(is_numeric($collid)){
							echo $collMap[$collid]['collectionname'].' ('.$collMap[$collid]['code'].')';
						}
						else{
							echo 'Multiple Collection Cleaning Tool (<a href="#" onclick="$(\'#collDiv\').show()" style="color:blue;text-decoration:underline">'.count($collMap).' collections</a>)';
						}
						?>
					</div>
					<?php
					if(count($collMap) > 1){
						echo '<div id="collDiv" style="display:none;margin:0px 20px">';
						foreach($collMap as $k => $vArr){
							echo '<div>'.$vArr['collectionname'].' ('.$vArr['code'].')</div>';
						}
						echo '</div>';
					}
					?>
					<div style="margin:20px;">
						<?php
						if($action){
							if($action == 'deepindex'){
								$cleanManager->deepIndexTaxa();
							}
							elseif($action == 'AnalyzingNames'){
								echo '<ul>';
								$cleanManager->setAutoClean($autoClean);
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
							<legend><b><?php echo $LANG['ACTION'];?> </b></legend>
							<form name="maincleanform" action="taxonomycleaner.php" method="post">
								<div style="margin-bottom:15px;">
									<b><?php echo $LANG['SPECIMEN_RECORD'];?></b>
									<div style="margin-left:10px;">
										<u><?php echo $LANG['SPECIMEN'];?></u>: <?php echo $badSpecimenCount; ?><br/>
										<u><?php echo $LANG['SCIENT_NAME'];?></u>: <?php echo $badTaxaCount; ?>
									</div>
								</div>
								<hr/>
								<div style="margin:20px 10px">
									<div style="margin:10px 0px">
										<?php echo $LANG['FOLLOWING'];?>
									</div>
									<div style="margin:10px;">
										<div style="margin-bottom:5px;">
											<fieldset style="padding:15px;margin:10px 0px">
												<legend><b><?php echo $LANG['TAX_RES'];?></b></legend>
												<?php
												$taxResourceList = $cleanManager->getTaxonomicResourceList();
												foreach($taxResourceList as $taKey => $taValue){
													echo '<input name="taxresource[]" type="checkbox" value="'.$taKey.'" '.(in_array($taKey,$taxResource)?'checked':'').' /> '.$taValue.'<br/>';
												}
												?>
											</fieldset>
										</div>
										<div style="margin-bottom:5px;">
											<?php echo $LANG['NAME_PROCESS'];?> <input name="limit" type="text" value="<?php echo $limit; ?>" style="width:40px" />
										</div>
										<div style="margin-bottom:5px;">
											<?php echo $LANG['INDEX'];?> <input name="startindex" type="text" value="<?php echo $startIndex; ?>" title="Enter a taxon name or letter of the alphabet to indicate where the processing should start" />
										</div>
										<div style="height:50px;">
											<div style=""><?php echo $LANG['CLEAN_AND'];?></div>
											<div style="float:left;margin-left:15px;"><input name="autoclean" type="radio" value="0" <?php echo (!$autoClean?'checked':''); ?> /> Semi-Manual</div>
											<div style="float:left;margin-left:10px;"><input name="autoclean" type="radio" value="1" <?php echo ($autoClean==1?'checked':''); ?> /> <?php echo $LANG['FULL'];?></div>
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
										<?php echo $LANG['FOLL'];?>
									</div>
									<div style="margin:10px">
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<button name="submitaction" type="submit" value="deepindex"><?php echo $LANG['DEEP'];?></button>
									</div>
								</div>
							</form>
						</fieldset>
					</div>
					<?php
				}
				elseif($IS_ADMIN){
					?>
					<div style="margin:0px 0px 20px 20xp;font-weight:bold;font-size:120%;"><?php echo $LANG['BATCH'];?></div>
					<fieldset style="padding: 15px;margin:20px;">
						<legend><b><?php echo $LANG['COLLEC'];?></b></legend>
						<form name="selectcollidform" action="taxonomycleaner.php" method="post" onsubmit="return checkSelectCollidForm(this)">
							<div><input name="selectall" type="checkbox" onclick="selectAllCollections(this);" /> <?php echo $LANG['SEL_UNSEL'];?></div>
							<?php
							foreach($collMap as $id => $collArr){
								echo '<div>';
								echo '<input name="collid[]" type="checkbox" value="'.$id.'" /> ';
								echo $collArr['collectionname'].' ('.$collArr['code'].')';
								echo '</div>';
							}
							?>
							<div style="margin: 15px">
								<button name="submitaction" type="submit" value="EvaluateCollections"><?php echo $LANG['EVA'];?></button>
							</div>
						</form>
					</fieldset>
					<?php
				}
			}
			else{
				?>
				<div style="margin:20px;font-weight:bold;font-size:120%;">
					<?php echo $LANG['ERROR'];?>
				</div>
				<?php
			}
			?>
		</div>
		<?php include($SERVER_ROOT.'/footer.php');?>
	</body>
</html>
