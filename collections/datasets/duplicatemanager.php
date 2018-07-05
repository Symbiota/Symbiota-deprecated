<?php
include_once('../../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$dupeDepth = array_key_exists('dupedepth',$_REQUEST)?$_REQUEST['dupedepth']:0;
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:1000;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

if(!$SYMB_UID){
	header('Location: ../../profile/index.php?refurl=../collections/datasets/duplicatemanager.php?'.$_SERVER['QUERY_STRING']);
}

$dupManager = new OccurrenceDuplicate();
$collMap = $dupManager->getCollMap($collId);

$statusStr = '';
$isEditor = 0; 
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))
	|| ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations'){
	$dupManager->setObsUid($SYMB_UID);
}

if($isEditor && $formSubmit){
	if($formSubmit == 'clusteredit'){
		$statusStr = $dupManager->editCluster($_POST['dupid'],$_POST['title'],$_POST['description'],$_POST['notes']);
	}
	elseif($formSubmit == 'clusterdelete'){
		$statusStr = $dupManager->deleteCluster($_POST['deldupid']);
	}
	elseif($formSubmit == 'occdelete'){
		$statusStr = $dupManager->deleteOccurFromCluster($_POST['dupid'],$_POST['occid']);
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Occurrence Cleaner</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <style type="text/css">
		table.styledtable td { white-space: nowrap; }
    </style>
	<script type="text/javascript">
		function verifyEditForm(f){
			if(f.title == ""){
				alert("Title field must have a value");
				return false;
			}
			return true;
		}

		function openOccurPopup(occid) {
			occWindow=open("../individual/index.php?occid="+occid,"occwin"+occid,"resizable=1,scrollbars=1,toolbar=0,width=750,height=600,left=20,top=20");
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
<body>
	<?php 	
	$displayLeftMenu = true;
	include($SERVER_ROOT.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
		<?php 
		if($action){
			echo '<a href="duplicatemanager.php?collid='.$collId.'">';
			echo 'Duplicate Management';
			echo '</a> &gt;&gt; ';
			echo '<b>Duplicate Clusters</b>';
		}
		else{
			echo '<b>Duplicate Management</b>';
		}
		?>
	</div>

	<!-- inner text -->
	<div id="innertext">
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
		if($isEditor){
			if(!$action){
				?>
				<fieldset style="padding:20px;">
					<legend><b>Duplicate Linkages</b></legend>
						<div>
						It is common within some collection domains to collect specimens in duplicate. 
						Links below list duplicate cluster and aid collection managers in batch linking 
						their specimen records to duplicate specimens housed at other institutions. 
						The main method of batch clustering duplicates is by matching 
						the collector, collector number, and collection date.
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="duplicatemanager.php?collid=<?php echo $collId; ?>&action=listdupes">
							List linked duplicate clusters 
						</a>
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="duplicatemanager.php?collid=<?php echo $collId; ?>&dupedepth=2&action=listdupeconflicts">
							List linked duplicate clusters with conflicted identifications 
						</a>
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="duplicatemanager.php?collid=<?php echo $collId; ?>&action=batchlinkdupes">
							Start batch linking duplicates
						</a>
					</div>
				</fieldset>
				<?php
			}
			else{
				if($action == 'batchlinkdupes'){
					?>
					<ul>
						<?php 
						$dupManager->batchLinkDuplicates($collId,true);
						?>
					</ul>
					<?php 
				}
				elseif($action == 'listdupes' || $action == 'listdupeconflicts'){
					$clusterArr = $dupManager->getDuplicateClusterList($collId, $dupeDepth, $start, $limit);
					$totalCnt = $clusterArr['cnt'];
					unset($clusterArr['cnt']);
					if($clusterArr){
						$paginationStr = '<span>';
						if($start) $paginationStr .= '<a href="duplicatemanager.php?collid='.$collId.'&action='.$action.'&start='.($start - $limit).'&limit='.$limit.'">';
						$paginationStr .= '&lt;&lt; Previous';
						if($start) $paginationStr .= '</a>';
						$paginationStr .= '</span>';
						$paginationStr .= ' || '.($start+1).' - '.(count($clusterArr)<$limit?$totalCnt:($start + $limit)).' || ';
						$paginationStr .= '<span>';
						if($totalCnt >= ($start+$limit)) $paginationStr .= '<a href="duplicatemanager.php?collid='.$collId.'&action='.$action.'&start='.($start + $limit).'&limit='.$limit.'">';
						$paginationStr .= 'Next &gt;&gt;';
						if($totalCnt >= ($start+$limit)) $paginationStr .= '</a>';
						$paginationStr .= '</span>';
						?>
						<div style="clear:both;font-weight:bold;font-size:140%;">
							<?php echo $collMap['collectionname']; ?>
						</div>
						<div style="float:right;">
							<?php echo $paginationStr; ?>
						</div>
						<div style="font-weight:bold;margin-left:15px;">
							<?php echo $totalCnt.' Duplicate Clusters '.($action == 'listdupeconflicts'?'with Identification Differences':''); ?>
						</div>
						<div style="margin:20px 0px;clear:both;">
							<?php 
							foreach($clusterArr as $dupId => $dupArr){
								?>
								<div style="clear:both;margin:10px 0px;">
									<div style="font-weight:bold;font-size:120%;">
										<?php echo $dupArr['title']; ?> 
										<span onclick="toggle('editdiv-<?php echo $dupId; ?>')" title="Display Editing Controls"><img src="../../images/edit.png" style="width:13px;" /></span> 
									</div>
									<?php 
									if(isset($dupArr['desc'])) echo '<div style="margin-left:10px;">'.$dupArr['desc'].'</div>';
									if(isset($dupArr['notes'])) echo '<div style="margin-left:10px;">'.$dupArr['notes'].'</div>';
									?>
									<div class="editdiv-<?php echo $dupId; ?>" style="display:none;">
										<fieldset style="margin:20px;padding:15px;">
											<legend><b>Edit Cluster</b></legend>
											<form name="dupeditform-<?php echo $dupId; ?>" method="post" action="duplicatemanager.php" onsubmit="return verifyEditForm(this);">
												<b>Title:</b> <input name="title" type="text" value="<?php echo $dupArr['title']; ?>" style="width:300px;" /><br/>
												<b>Description:</b> <input name="description" type="text" value="<?php echo $dupArr['desc']; ?>" style="width:400px;" /><br/>
												<b>Notes:</b> <input name="notes" type="text" value="<?php echo $dupArr['notes']; ?>" style="width:400px;" /><br/>
												<input name="dupid" type="hidden" value="<?php echo $dupId; ?>" />
												<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
												<input name="start" type="hidden" value="<?php echo $start; ?>" />
												<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
												<input name="action" type="hidden" value="<?php echo $action; ?>" />
												<input name="formsubmit" type="hidden" value="clusteredit" />
												<input name="submit" type="submit" value="Save Edits" />
											</form>
											<form name="dupdelform-<?php echo $dupId; ?>" method="post" action="duplicatemanager.php" onsubmit="return confirm('Are you sure you want to delete this duplicate cluster?');">
												<input name="deldupid" type="hidden" value="<?php echo $dupId; ?>" />
												<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
												<input name="start" type="hidden" value="<?php echo $start; ?>" />
												<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
												<input name="action" type="hidden" value="<?php echo $action; ?>" />
												<input name="formsubmit" type="hidden" value="clusterdelete" />
												<input name="submit" type="submit" value="Delete Cluster" />
											</form>
										</fieldset>
									</div>
									<div style="margin:7px 10px;">
										<?php 
										unset($dupArr['title']);
										unset($dupArr['desc']);
										unset($dupArr['notes']);
										foreach($dupArr as $occid => $oArr){
											?>
											<div style="margin:10px">
												<div style="float:left;">
													<a href="#" onclick="openOccurPopup(<?php echo $occid; ?>); return false;"><b><?php echo $oArr['id']; ?></b></a> =&gt; 
													<?php echo $oArr['recby']; ?>
												</div> 
												<div class="editdiv-<?php echo $dupId; ?>" style="display:none;float:left;" title="Delete Specimen from Cluster">
													<form name="dupdelform-<?php echo $dupId.'-'.$occid; ?>" method="post" action="duplicatemanager.php" onsubmit="return confirm('Are you sure you want to remove this occurrence record from this cluster?');" style="display:inline;">
														<input name="dupid" type="hidden" value="<?php echo $dupId; ?>" />
														<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
														<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
														<input name="start" type="hidden" value="<?php echo $start; ?>" />
														<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
														<input name="action" type="hidden" value="<?php echo $action; ?>" />
														<input name="formsubmit" type="hidden" value="occdelete" />
														<input name="submit" type="image" src="../../images/del.png" style="width:15px;" />
													</form>
												</div>
												<div style="margin-left:15px;clear:both;">
													<?php
													echo '<b>'.$oArr['sciname'].'</b><br/>';
													if($oArr['idby']) echo 'Determined by: '.$oArr['idby'].' '.$oArr['dateid'];
													?>
												</div>
											</div>
											<?php 
										}
										?>
									</div>
								</div>
								<?php 
							}
							?>
						</div>
						<?php
						echo $paginationStr;
					}
					else{
						 echo '<div><b>No Duplicate Clusters match the request. We suggest returning to Duplicate cluster main menu and batch building duplicate clusters</b></div>';
					}
				}
				?>
				<div>
					<a href="duplicatemanager.php?collid=<?php echo $collId; ?>">Return to main menu</a>
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
include($SERVER_ROOT.'/footer.php');
?>

</body>
</html>