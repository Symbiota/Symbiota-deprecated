<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceSupport.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID){
	header('Location: '.$serverRoot.'/profile/index.php?refurl=../../collections/misc/commentlist.php?'.$_SERVER['QUERY_STRING']);
}

$collid = $_REQUEST['collid'];
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:100;
$tsStart = array_key_exists('tsstart',$_POST)?$_POST['tsstart']:'';
$tsEnd = array_key_exists('tsend',$_POST)?$_POST['tsend']:'';
$uid = array_key_exists('uid',$_POST)?$_POST['uid']:0;
$rs = array_key_exists('rs',$_POST)?$_POST['rs']:'';

$commentManager = new OccurrenceSupport();

$isEditor = 0; 
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
	}
}

$statusStr = '';
$commentArr = null;
if($isEditor){
	if(array_key_exists('hidecomid',$_GET)){
		if(!$commentManager->hideComment($_GET['hidecomid'])){
			$statusStr = $commentManager->getErrorStr();
		}
	}
	elseif(array_key_exists('publiccomid',$_GET)){
		if(!$commentManager->makeCommentPublic($_GET['publiccomid'])){
			$statusStr = $commentManager->getErrorStr();
		}
	}
	elseif(array_key_exists('delcomid',$_POST)){
		if(!$commentManager->deleteComment($_POST['delcomid'])){
			$statusStr = $commentManager->getErrorStr();
		}
	}
	$commentArr = $commentManager->getComments($collid, $start, $limit, $tsStart, $tsEnd, $uid, $rs);
}
?>
<html>
	<head>
		<title>Comments Listing</title>
		<link href="<?php echo $clientRoot; ?>/css/base.css" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" rel="stylesheet" />
		<script type="text/javascript">

		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $clientRoot; ?>/index.php">Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
			<b>Occurrence Comment Listing</b>
		</div>
		<?php 
		if($statusStr){
			echo '<div style="margin:20px;color:red;">';
			echo $statusStr;
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php
			if($collid){
				$pageBar = '<b>';
				if($commentArr){
					$recCnt = 0;
					if(isset($commentArr['cnt'])){
						$recCnt = $commentArr['cnt'];
						unset($commentArr['cnt']);
					}
					$urlVars = 'collid='.$collid.'&limit='.$limit.'&tsstart='.$tsStart.'&tsend='.$tsEnd.'&uid='.$uid.'&rs='.$rs;
					$pageNumber = ceil($recCnt / ($start + 1));
					$lastPage = ceil($recCnt / $limit) + 1;
					$startPage = ($pageNumber > 4?ceil($pageNumber - 4):1);
					$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
					$hrefPrefix = 'commentlist.php?'.$urlVars."&start=";
					$pageBar .= "<span style='margin:5px;'>\n";
					if($startPage > 1){
					    $pageBar .= "<span style='margin-right:5px;'><a href='".$hrefPrefix."1'>First</a></span>";
					    $pageBar .= "<span style='margin-right:5px;'><a href='".$hrefPrefix.(($pageNumber - 10) < 1 ?1:$pageNumber - 10)."'>&lt;&lt;</a></span>";
						for($x = $startPage; $x <= $endPage; $x++){
						    if($pageNumber != $x){
						        $pageBar .= "<span style='margin-right:3px;margin-right:3px;'><a href='".$hrefPrefix.($x*$limit)."'>".$x."</a></span>";
						    }
						    else{
						        $pageBar .= "<span style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
						    }
						}
					}
					if($lastPage < $endPage){
					    $pageBar .= "<span style='margin-left:5px;'><a href='".$hrefPrefix.(($pageNumber + 10) > $lastPage?($lastPage*$limit):(($pageNumber + 10)*$limit))."'>&gt;&gt;</a></span>";
					    $pageBar .= "<span style='margin-left:5px;'><a href='".$hrefPrefix.($lastPage*$limit)."'>Last</a></span>";
					}
					$pageBar .= "</span>";
					$pageBar .= "<span style='margin:5px;'>";
					$beginNum = ($pageNumber - 1)*$limit + 1;
					$endNum = $start + $limit;
					if($endNum > $recCnt) $endNum = $recCnt;
					$pageBar .= ($start+1)."-".$endNum." of ".$recCnt.' records';
					$pageBar .= "</span></b>";
					echo "<div style='clear:both;'><hr/></div>\n";
					echo '<div style="float:left;"><b>'.count($commentArr).' Comments</b></div>';
					echo '<div style="float:right;">'.$pageBar.'</div>';
					echo "<div style='clear:both;'><hr/></div>";
				}
				?>
				<!-- Option box -->
				<fieldset style="float:right;width:250px;margin:10px;">
					<legend><b>Options</b></legend>
					<form name="optionform" action="commentlist.php" method="post">
						<div>
							Date range: 
							<input name="tsstart" type="text" value="<?php echo $tsStart; ?>" style="width:60px;" /> - 
							<input name="tsend" type="text" value="<?php echo $tsEnd; ?>" style="width:60px;" />
						</div>
						<div>
							User:
							<select name="uid">
								<option value="0">All Users</option> 
								<option value="0">------------------------</option> 
								<?php 
									$userArr = $commentManager->getCommentUsers($collid);
									foreach($userArr as $uid => $userStr){
										echo '<option value="'.$uid.'">'.$userStr.'</option>';
									}
								?>
							</select>
						</div>
						<div>
							<input name="rs" type="radio" value="1" /> Active 
							<input name="rs" type="radio" value="0" /> Inactivated
						</div>
						<div>
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="formsubmit" type="submit" value="Display Edits" />
						</div>
					</form>
				</fieldset>
				<?php 
				if($commentArr){
					foreach($commentArr as $comid => $cArr){
						echo '<div style="margin:15px;">';
						echo '<div><b>'.$userArr[$cArr['uid']].'</b> <span style="color:gray;">posted '.$cArr['ts'].'</span></div>';
						if($cArr['rs'] == 0) echo '<div style="color:red;">Comment not public, may be due to abuse report (viewable to administrators only)</div>';
						echo '<div style="margin:10px;">'.$cArr['str'].'</div>';
						if($cArr['rs']){
							echo '<div><a href="commentlist.php?hidecomid='.$comid.'&start='.$start.'&'.$urlVars.'">';
							echo 'Hide comment from public';
							echo '</a></div>';
						}
						else{
							echo '<div><a href="commentlist.php?publiccomid='.$comid.'&start='.$start.'&'.$urlVars.'">';
							echo 'Make comment public';
							echo '</a></div>';
						}
						?>
						<div style="margin:20px;">
							<form name="delcommentform" action="commentlist.php" method="post" onsubmit="return confirm('Are you sure you want to delete comment?')">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="start" type="hidden" value="<?php echo $start; ?>" />
								<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
								<input name="tsstart" type="hidden" value="<?php echo $tsStart; ?>" />
								<input name="tsend" type="hidden" value="<?php echo $tsEnd; ?>" />
								<input name="uid" type="hidden" value="<?php echo $uid; ?>" />
								<input name="rs" type="hidden" value="<?php echo $rs; ?>" />
								<input name="delcomid" type="hidden" value="<?php echo $comid; ?>" />
								<input name="formsubmit" type="submit" value="Delete Comment" />
							</form>
						</div>
						<?php 
						echo '</div>';
						echo '<hr style="color:gray;"/>';
					}
					echo '<div style="float:right;">'.$pageBar.'</div>';
					echo "<div style='clear:both;'><hr/></div></div>";
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;margin:20px;">No comments have been submitted</div>';
				}
			}
			else{
				echo '<div>ERROR: collid is null</div>';
			}
			?>
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
