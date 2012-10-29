<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);

$startIndex = array_key_exists('sindex',$_REQUEST)?$_REQUEST['sindex']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:500;
$returnAll = array_key_exists('retall',$_REQUEST)?$_REQUEST['retall']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();

$statusStr = '';
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Crowdsourcing Review Status</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script type="text/javascript">

	</script>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<b>Crowdsourcing Review Status</b>
	</div>
	<!-- inner text -->
	<div id="innertext">
		<h1>Review Status</h1>
		<?php 
		$recArr = $csManager->getReviewArr($symbUid,$startIndex,$limit+1,$returnAll);
		if($recArr){
			$totalCnt = $recArr['totalcnt'];
			unset($recArr['totalcnt']);
			$navStr = '';
			if($startIndex > 0 || count($retArr) > $limit){
				$navStr = '<b>';
				if($startIndex > 0) $navStr .= '<a href="crowdsourcestatus.php?sindex='.$startIndex.'&limit='.$limit.'&retall='.$returnAll.'">';
				$navStr .= 'First Page';
				if($startIndex > 0) $navStr .= '</a>';
				$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
				if($startIndex > 0) $navStr .= '<a href="crowdsourcestatus.php?sindex='.($startIndex-$limit).'&limit='.$limit.'&retall='.$returnAll.'">';
				$navStr .= 'Previous Page';
				if($startIndex > 0) $navStr .= '</a>';
				$navStr .= '&nbsp;&nbsp;| '.($startIndex + 1).' to '.$limit.' of '.$totalCnt.' |&nbsp;&nbsp;';
				if(count($retArr) > $limit) $navStr .= '<a href="crowdsourcestatus.php?sindex='.($startIndex+$limit).'&limit='.$limit.'&retall='.$returnAll.'">';
				$navStr .= 'Next Page';
				if(count($retArr) > $limit) $navStr .= '</a>';
				$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
				if($startIndex+(count($retArr)) < $totalCnt) $navStr .= '<a href="crowdsourcestatus.php?sindex='.($totalCnt-$limit).'&limit='.($limit+2).'&retall='.$returnAll.'">';
				$navStr .= 'Last Page';
				if($startIndex+(count($retArr)) < $totalCnt) $navStr .= '</a> ';
				$navStr .= '</b>';
			}
			if($navStr) echo '<div style="float:right;">'.$navStr.'</div>'; 
			foreach($recArr as $collCode => $cArr){
				echo '<h2>'.$cArr['name'].'</h2>';
				unset($cArr['name']);
				echo '<div style="margin:20px;"><table>'."\n";
				echo '<tr><td>Record</td><td>Status</td><td>Points</td><td>Notes</td><td>Edit Date</td></tr>'."\n";
				foreach($cArr as $occid => $c2Arr){
					echo '<tr><td><a href="../individual.php?occid='.$occid.'">'.$occid.'</a></td>';
					echo '<td>'.($c2Arr['rs']==5?'pending':'closed').'</td>';
					echo '<td>'.$c2Arr['pts'].' </td><td>'.$c2Arr['n'].' </td><td>'.$c2Arr['ts'].' </td></tr>';
				}
				echo '</table>';
			}
			if($navStr) echo '<div style="float:right;">'.$navStr.'</div>'; 
		}		
		
		?>
	</div>
	<?php 	
	include($serverRoot.'/footer.php');
	?>
</body>
</html>