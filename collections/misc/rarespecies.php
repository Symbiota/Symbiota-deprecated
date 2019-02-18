<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/RareSpeciesManager.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/rarespecies.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

$submitAction = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:'';
$searchTaxon = array_key_exists("searchtaxon",$_POST)?$_POST["searchtaxon"]:'';

$isEditor = 0;
if($IS_ADMIN || array_key_exists("RareSppAdmin",$USER_RIGHTS)){
	$isEditor = 1;
}

$rsManager = new RareSpeciesManager($isEditor?'write':'readonly');

if($isEditor){
	if($submitAction == "addspecies"){
		$rsManager->addSpecies($_POST["tidtoadd"]);
	}
	elseif($submitAction == "deletespecies"){
		$rsManager->deleteSpecies($_REQUEST["tidtodel"]);
	}
}
if($searchTaxon) $rsManager->setSearchTaxon($searchTaxon);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $LANG['ESPECIES']; ?></title>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />

	<!--inicio favicon -->
	<link rel="shortcut icon" href="../../images/favicon.png" type="image/x-icon">

	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script>
		$(document).ready(function() {
			$("#speciestoadd").autocomplete({
				source: "rpc/speciessuggest.php" },{ minLength: 3, autoFocus: true
			});

			$("#searchtaxon").autocomplete({
				source: "rpc/speciessuggest.php" },{ minLength: 3, autoFocus: true
			});
		});

		function toggle(target){
		  	var divs = document.getElementsByTagName("div");
		  	for (var i = 0; i < divs.length; i++) {
		  	var divObj = divs[i];
				if(divObj.className == target){
					if(divObj.style.display=="none"){
						divObj.style.display="block";
					}
				 	else {
				 		divObj.style.display="none";
				 	}
				}
			}

		  	var spans = document.getElementsByTagName("span");
		  	for (var h = 0; h < spans.length; h++) {
		  	var spanObj = spans[h];
				if(spanObj.className == target){
					if(spanObj.style.display=="none"){
						spanObj.style.display="inline";
					}
				 	else {
				 		spanObj.style.display="none";
				 	}
				}
			}
		}

		function submitAddSpecies(f){
			var sciName = f.speciestoadd.value;
			if(sciName == ""){
				alert("<?php echo $LANG['ENTER']; ?>");
				return false;
			}

			$.ajax({
				type: "POST",
				url: "rpc/gettid.php",
				dataType: "json",
				data: { sciname: sciName }
			}).done(function( data ) {
				f.tidtoadd.value = data;
				f.submit();
			}).fail(function(jqXHR){
				alert("<?php echo $LANG['ERROR_SCIENTIFIC_NAME']; ?>");
			});
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($collections_misc_rarespeciesMenu)?$collections_misc_rarespeciesMenu:true);
include($SERVER_ROOT.'/header.php');
if(isset($collections_misc_rarespeciesCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>".$LANG['HOME']."</a> &gt;&gt; ";
	echo $collections_misc_rarespeciesCrumbs." &gt;&gt;";
	echo " <b>".$LANG['SENSITIVE_SPECIES_FOR_MASKING']."</b>";
	echo "</div>";
}
?>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if($isEditor){
		?>
		<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editobj');" title="<?php  echo $LANG['TOGGLE_EDITING_FUNCTIONS']; ?>">
			<img style="border:0px;" src="../../images/edit.png" />
		</div>
		<?php
	}
	?>
	<h1><?php echo $LANG['ESPECIES'];?></h1>
	<div style="float:right;">
		<fieldset style="margin:0px 15px;padding:10px">
			<legend><b><?php echo $LANG['TAX_SEARCH'];?></b></legend>
			<form name="searchform" action="rarespecies.php" method="post">
				<input id="searchtaxon" name="searchtaxon" type="text" value="<?php echo $searchTaxon; ?>" />
				<input type="hidden" name="submitaction" value="Search" />
				<input type="submit" value="<?php echo $LANG['SEARCH']; ?>" />
			</form>
		</fieldset>
	</div>
	<div style='margin:15px;'>
		<?php echo $LANG['PAR_2'];?>
	</div>
	<div style="clear:both">
		<fieldset style="padding:15px;margin:15px">
			<legend><b><?php echo $LANG['GLOBAL'];?></b></legend>
			<?php
			if($isEditor){
				?>
				<div class="editobj" style="display:none;width:400px;">
					<form name="addspeciesform" action='rarespecies.php' method='post'>
						<fieldset style='margin:5px;background-color:#FFFFCC;'>
							<legend><b><?php echo $LANG['ADD_SPECIES'];?></b></legend>
							<div style="margin:3px;">
								<?php echo $LANG['SCIENTIFIC_NAME'];?>
								<input type="text" id="speciestoadd" name="speciestoadd" style="width:300px" />
								<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
							</div>
							<div style="margin:3px;">
								<input type="hidden" name="submitaction" value="addspecies" />
								<input type="button" value="<?php echo $LANG['ADD_SPECIES_NEW']; ?>" onclick="submitAddSpecies(this.form)" />
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			$rsArr = $rsManager->getRareSpeciesList();
			if($rsArr){
				foreach($rsArr as $family => $speciesArr){
					?>
					<h3><?php echo $family; ?></h3>
					<div style='margin-left:20px;'>
					<?php
					foreach($speciesArr as $tid => $sciName){
						echo '<div id="tid-'.$tid.'"><a href="../../taxa/index.php?taxon='.$tid.'" target="_blank">'.$sciName.'</a>';
						if($isEditor){
							?>
							<span class="editobj" style="display:none;">
								<a href="rarespecies.php?submitaction=deletespecies&tidtodel=<?php echo $tid;?>">
									<img src="../../images/del.png" style="width:13px;border:0px;" title="<?php echo $LANG['REMOVE_SPECIES_FROM_LIST']; ?>" />
								</a>
							</span>
							<?php
						}
						echo "</div>";
					}
					?>
					</div>
					<?php
				}
			}
			else{
				?>
				<div style="margin:30px;font-weight:bold;font-size:120%;">
					<?php echo $LANG['NO_SPECIES'];?>
				</div>
				<?php
			}
			?>
		</fieldset>
		<fieldset style="padding:25px;margin:15px">
			<legend><b><?php echo $LANG['STATE'];?> </b></legend>
			<?php
			$stateList = $rsManager->getStateList();
			$emptyList = true;
			foreach($stateList as $clid => $stateArr){
				if($isEditor || $stateArr['access'] == 'public'){
					echo '<div>';
					echo '<a href="../../checklists/checklist.php?cl='.$clid.'">';
					echo $stateArr['locality'].': '.$stateArr['name'];
					echo '</a>';
					if($stateArr['access'] == 'private') echo ' (private)';
					echo '</div>';
					$emptyList = false;
				}
			}
			if($emptyList){
				?>
				<div style="margin:30px;font-weight:bold;font-size:120%;">
					 <?php echo $LANG['NO_CHECKLIST'];?>
				</div>
				<?php
			}
			?>
		</fieldset>
	</div>
</div>
<?php
include($SERVER_ROOT.'/footer.php')
?>
</body>
</html>
