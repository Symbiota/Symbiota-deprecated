<?php
include_once('../../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$obsUid = array_key_exists('obsuid',$_REQUEST)?$_REQUEST['obsuid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$function = array_key_exists('fn',$_REQUEST)?$_REQUEST['fn']:'';
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:200;

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/occurrencecleaner.php?'.$_SERVER['QUERY_STRING']);

//Sanitation
if(!is_numeric($collId)) $collId = 0;
if(!is_numeric($obsUid)) $obsUid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';
if($function && !preg_match('/^[a-zA-Z0-9\s_]+$/',$function)) $function = '';
if(!is_numeric($start)) $start = 0;
if(!is_numeric($limit)) $limit = 0;

$cleanManager = new OccurrenceCleaner();
if($collId) $cleanManager->setCollId($collId);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"])) || ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations' && $obsUid !== 0){
	$obsUid = $SYMB_UID;
	$cleanManager->setObsUid($obsUid);
}

if($action == 'listdupscatalog') $limit = 500;

$dupArr = array();
if($action == 'listdupscatalog'){
	$dupArr = $cleanManager->getDuplicateCatalogNumber('cat',$start,$limit);
	$function = $action;
}
if($action == 'listdupsothercatalog'){
	$dupArr = $cleanManager->getDuplicateCatalogNumber('other',$start,$limit);
	$function = $action;
}
elseif($action == 'listdupsrecordedby'){
	$dupArr = $cleanManager->getDuplicateCollectorNumber($start);
	$function = $action;
}

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Occurrence Cleaner</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
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
		   	alert("Please select specimens to be merged!");
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

		function verifyEditForm(f){
			if(f.title == ""){
				alert("Title field must have a value");
				return false;
			}
			return true;
		}

		function openOccurPopup(occid) {
			occWindow=open("../individual/index.php?occid="+occid,"occwin"+occid,"resizable=1,scrollbars=1,toolbar=1,width=750,height=600,left=20,top=20");
			if(occWindow.opener == null) occWindow.opener = self;
		}

		function toggle(target){
			var ele = document.getElementById(target);
			if(ele){
				if(ele.style.display=="block"){
					ele.style.display="none";
		  		}
			 	else {
			 		ele.style.display="block";
			 	}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var divObj = divObjs[i];
			  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
						if(divObj.style.display=="none"){
							divObj.style.display="inline";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
		}
	</script>
</head>
<body <?php echo (($dupArr)?'style="margin-left: 0px; margin-right: 0px;"':'');?>>
	<?php 	
	$displayLeftMenu = false;
	if(!$dupArr) include($SERVER_ROOT.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
		<?php 
		if($action){
			echo '<a href="occurrencecleaner.php?collid='.$collId.'">';
			echo 'Data Cleaning Module';
			echo '</a> &gt;&gt; ';
			if($action == 'listdupscatalog' || $action == 'listdupsothercatalog' || $action == 'listdupsrecordedby') echo '<b>Duplicate Occurrences</b>';
		}
		else{
			echo '<b>Data Cleaning Module</b>';
		}
		?>
	</div>

	<!-- inner text -->
	<div id="innertext" style="background-color:white;">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		} 
		echo '<h2>'.$collMap['collectionname'].' ('.$collMap['code'].')</h2>';
		if($isEditor){
			if(!$action){
				?>
				<fieldset style="padding:20px;">
					<legend><b>Duplicate Occurrences</b></legend>
					<div>
						This function will query the collection for records with duplicate records within a collection. 
						Duplicates can be searched based on catalog numbers or collector/observer name and number. 
						Results will be listed in a table grouped by the catlog number or collector. Clicking on the number in the 
						left most column will open the editor for that record. Selecting the checkboxes for two or more 
						records within the groups and submitting the form will merge selected records. Select link below to 
						query database for possible duplicate records. Note that a maximun of 500 records will 
						be returned at a time.
					</div>
					<?php 
					if($collMap['colltype'] != 'General Observations'){
						?>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=listdupscatalog">
								Search for duplicates based on <b>Catalog Numbers</b>
							</a>
						</div>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=listdupsothercatalog">
								Search for duplicates based on <b>Other Catalog Numbers</b>
							</a>
						</div>
						<?php
					}
					?>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=listdupsrecordedby">
							Search for duplicates based on <b>Collector/Observer and numbers</b>
						</a>
					</div>
				</fieldset>
<!-- 
				<fieldset style="padding:20px;">
					<legend><b>Taxonomic Names</b></legend>
						<div>
							This function will query the collection for specimen records with unmapped, possibly problematic taxonomic names.      
						</div>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							In development
						</div>
				</fieldset>

				<fieldset style="padding:20px;">
					<legend><b>Primary Collector</b></legend>
						<div>
							These tools are designed to aid collection manager in indexing primary collectors 
							to the collector/observer tables.      
						</div>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							In development
						</div>
				</fieldset>
 -->
				<?php
			}
			else{
				if($action == 'listdupscatalog' || $action == 'listdupsothercatalog' || $action == 'listdupsrecordedby'){
					//Look for duplicate catalognumbers 
					if($dupArr){
						$recCnt = count($dupArr);
						//Build table
						?>
						<div style="margin-bottom:10px;">
							<b>Use the checkboxes to select the records you would like to merge, and the radio buttons to select which target record to merge into.</b>
						</div>
						<form name="mergeform" action="occurrencecleaner.php" method="post" onsubmit="return validateMergeForm(this);">
							<?php 
							if($recCnt > $limit){
								$href = 'occurrencecleaner.php?collid='.$collId.'&obsuid='.$obsUid.'&action='.$action.'&start='.($start+$limit); 
								echo '<div style="float:right;"><a href="'.$href.'"><b>NEXT '.$limit.' RECORDS &gt;&gt;</b></a></div>';
							}
							echo '<div><b>'.($start+1).' to '.($start+$recCnt).' Duplicate Clusters </b></div>';
							?>
							<table class="styledtable" style="font-family:Arial;font-size:12px;">
								<tr>
									<th style="width:40px;">ID</th>
									<th style="width:20px;"><input name="selectalldupes" type="checkbox" title="Select/Deselect All" onclick="selectAllDuplicates(this.form)" /></th>
									<th></th>
									<th style="width:40px;">Catalog Number</th>
									<th style="width:40px;">Other Catalog Numbers</th>
									<th>Scientific Name</th>
									<th>Collector</th>
									<th>Collection Number</th>
									<th>Associated Collectors</th>
									<th>Collection Date</th>
									<th>Verbatim Date</th>
									<th>Country</th>
									<th>State</th>
									<th>County</th>
									<th>Locality</th>
									<th>Date Last Modified</th>
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
								<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
								<input name="obsuid" type="hidden" value="<?php echo $obsUid; ?>" />
								<input name="fn" type="hidden" value="<?php echo $function; ?>" />
								<input name="action" type="submit" value="Merge Duplicate Records" />
							</div>
						</form>
						<?php 
					}
					else{
						?>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							There are no duplicate catalog numbers!
						</div>
						<?php 
					}
				}
				elseif($action == 'Merge Duplicate Records'){
					?>
					<ul>
						<li>Duplicate merging process started</li>
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
						?>
						<li>Done!</li>
					</ul>
					<div>
						<a href="occurrencecleaner.php?action=<?php echo $function.'&collid='.$collId.'&obsuid='.$obsUid; ?>">Return to duplicate list</a><br/>
					</div> 
					<?php 
				}
				?>
				<div>
					<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>">Return to main menu</a>
				</div> 
				<?php 
			}
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
<?php 	
if(!$dupArr) include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>