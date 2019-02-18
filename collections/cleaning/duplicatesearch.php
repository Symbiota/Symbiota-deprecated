<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');
include_once($SERVER_ROOT.'/content/lang/collections/cleaning/duplicatesearch.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:200;

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/duplicatesearch.php?'.$_SERVER['QUERY_STRING']);

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';
if(!is_numeric($start)) $start = 0;
if(!is_numeric($limit)) $limit = 0;

$cleanManager = new OccurrenceCleaner();
if($SOLR_MODE) $solrManager = new SOLRManager();
if($collid) $cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])) || ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations'){
	$cleanManager->setObsUid($SYMB_UID);
}

$dupArr = array();
if($action == 'listdupscatalog'){
	$limit = 1000;
	$dupArr = $cleanManager->getDuplicateCatalogNumber('cat',$start,$limit);
}
if($action == 'listdupsothercatalog'){
	$limit = 1000;
	$dupArr = $cleanManager->getDuplicateCatalogNumber('other',$start,$limit);
}
elseif($action == 'listdupsrecordedby'){
	$dupArr = $cleanManager->getDuplicateCollectorNumber($start);
}

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Occurrence Cleaner</title>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <style type="text/css">
		table.styledtable td { white-space: nowrap; }
    </style>
	<script type="text/javascript">
		function validateMergeForm(f){
			var dbElements = document.getElementsByName("dupid[]");
			for(i = 0; i < dbElements.length; i++){
				var dbElement = dbElements[i];
				if(dbElement.checked) return true;
			}
		   	alert("Please select occurrences to be merged!");
	      	return false;
		}

		function selectAllDuplicates(f){
			var boxesChecked = true;
			if(!f.selectalldupes.checked){
				boxesChecked = false;
			}
			var dbElements = document.getElementsByName("dupid[]");
			for(i = 0; i < dbElements.length; i++){
				dbElements[i].checked = boxesChecked;
			}

		}

		function batchSwitchTargetSpecimens(cbElem){
			cbElem.checked = false;
			var dbElements = document.getElementsByTagName("input");
			//var dbElements = $("input[type='radio']").val();
			var elemName = '';
			for(i = 0; i < dbElements.length; i++){
				if(dbElements[i].type == "radio"){
					if(dbElements[i].checked == false && elemName != dbElements[i].name){
						dbElements[i].checked = true;
						elemName = dbElements[i].name;
					}
				}
			}
		}
	</script>
</head>
<body style="background-color:white;margin-left:0px;margin-right:0px;padding:50px">
	<div class='navpath'>
		<a href="../../index.php"><?php echo $LANG['HOME'];?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1"><?php echo $LANG['COL_MAN'];?></a> &gt;&gt;
		<a href="index.php?collid=<?php echo $collid; ?>"><?php echo $LANG['CLEANING'];?></a> &gt;&gt;
		<b><?php echo $LANG['DUPLICATE'];?></b>
	</div>

	<!-- inner text -->
	<div id="innertext" style="background-color:white;">
		<?php
		echo '<h2>'.$collMap['collectionname'].' ('.$collMap['code'].')</h2>';
		if($isEditor){
			if($action == 'listdupscatalog' || $action == 'listdupsothercatalog' || $action == 'listdupsrecordedby'){
				//Look for duplicate catalognumbers
				if($dupArr){
					$recCnt = count($dupArr);
					//Build table
					?>
					<div style="margin-bottom:10px;">
						<b><?php echo $LANG['USE_BOXE'];?></b>
					</div>
					<form name="mergeform" action="duplicatesearch.php" method="post" onsubmit="return validateMergeForm(this);">
						<?php
						if($recCnt > $limit){
							$href = 'duplicatesearch.php?collid='.$collid.'&action='.$action.'&start='.($start+$limit);
							echo '<div style="float:right;"><a href="'.$href.'"><b>NEXT '.$limit.' RECORDS &gt;&gt;</b></a></div>';
						}
						echo '<div><b>'.($start+1).' to '.($start+$recCnt).' Duplicate Clusters </b></div>';
						?>
						<table class="styledtable" style="font-family:Arial;font-size:12px;">
							<tr>
								<th style="width:40px;">ID</th>
								<th style="width:20px;"><input name="selectalldupes" type="checkbox" title="Select/Deselect All" onclick="selectAllDuplicates(this.form)" /></th>
								<th><input type="checkbox" name="batchswitch" onclick="batchSwitchTargetSpecimens(this)" title="Batch switch target occurrences" /></th>
								<th style="width:40px;"><?php echo $LANG['CAT_NUM'];?></th>
								<th style="width:40px;"><?php echo $LANG['OTHER_CAT'];?></th>
								<th><?php echo $LANG['SCIENT'];?></th>
								<th><?php echo $LANG['COLLECTOR'];?></th>
								<th><?php echo $LANG['COL_NUMBER'];?></th>
								<th><?php echo $LANG['ASSOCIATED'];?></th>
								<th><?php echo $LANG['COLL_DATE'];?></th>
								<th><?php echo $LANG['VERB_DATE'];?></th>
								<th></th>
								<th><?php echo $LANG['COUNTRY'];?></th>
								<th><?php echo $LANG['STATE'];?></th>
								<th><?php echo $LANG['COUNTY'];?></th>
								<th><?php echo $LANG['LOCALITY'];?></th>
								<th><?php echo $LANG['DATE_LAST'];?></th>
							</tr>
							<?php
							$setCnt = 0;
							foreach($dupArr as $dupKey => $occArr){
								$setCnt++;
								$first = true;
								foreach($occArr as $occId => $occArr){
									echo '<tr '.(($setCnt % 2) == 1?'class="alt"':'').'>';
									echo '<td><a href="../editor/occurrenceeditor.php?occid='.$occId.'" target="_blank">'.$occId.'</a></td>'."\n";
									echo '<td><input name="dupid[]" type="checkbox" value="'.$dupKey.':'.$occId.'" /></td>'."\n";
									echo '<td><input name="dup'.$dupKey.'target" type="radio" value="'.$occId.'" '.($first?'checked':'').'/></td>'."\n";
									echo '<td>'.$occArr['catalognumber'].'</td>'."\n";
									echo '<td>'.$occArr['othercatalognumbers'].'</td>'."\n";
									echo '<td>'.$occArr['sciname'].'</td>'."\n";
									echo '<td>'.$occArr['recordedby'].'</td>'."\n";
									echo '<td>'.$occArr['recordnumber'].'</td>'."\n";
									echo '<td>'.$occArr['associatedcollectors'].'</td>'."\n";
									echo '<td>'.$occArr['eventdate'].'</td>'."\n";
									echo '<td>'.$occArr['verbatimeventdate'].'</td>'."\n";
									echo '<td>'.$occArr['country'].'</td>'."\n";
									echo '<td>'.$occArr['stateprovince'].'</td>'."\n";
									echo '<td>'.$occArr['county'].'</td>'."\n";
									echo '<td>'.$occArr['locality'].'</td>'."\n";
									echo '<td>'.$occArr['datelastmodified'].'</td>'."\n";
									echo '</tr>';
									$first = false;
								}
							}
							?>
						</table>
						<div style="margin:15px;">
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="action" type="submit" value="Merge Duplicate Records" />
						</div>
					</form>
					<?php
				}
				else{
					?>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<?php echo $LANG['THERE'];?>
					</div>
					<?php
				}
			}
			elseif($action == 'Merge Duplicate Records'){
				?>
				<ul>
					<li><?php echo $LANG['DUPLICATE_MERGIN'];?></li>
					<?php
					$dupArr = array();
					foreach($_POST['dupid'] as $v){
						$vArr = explode(':',$v);
						if(count($vArr) > 1){
							$target = $_POST['dup'.$vArr[0].'target'];
							if($target != $vArr[1]) $dupArr[$target][] = $vArr[1];
						}
					}
					$cleanManager->mergeDupeArr($dupArr);
                    if($SOLR_MODE) $solrManager->updateSOLR();
					?>
					<li><?php echo $LANG['DONE'];?></li>
				</ul>
				<?php
			}
			?>
			<div>
				<a href="index.php?collid=<?php echo $collid; ?>"><?php echo $LANG['RETURN'];?></a>
			</div>
			<?php
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
</body>
</html>