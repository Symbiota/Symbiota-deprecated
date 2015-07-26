<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$collManager = new CollectionProfileManager();

if($collId){
	$dateArr = $collManager->getYearStatsHeaderArr($collId);
	$statArr = $collManager->getYearStatsDataArr($collId);
}
?>
<html>
	<head>
		<meta name="keywords" content="Natural history collections yearly statistics" />
		<title><?php echo $defaultTitle; ?> Year Statistics</title>
		<link rel="stylesheet" href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" />
		<link rel="stylesheet" href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
		<script type="text/javascript" src="../../js/jquery.js"></script>
		<script type="text/javascript" src="../../js/jquery-ui.js"></script>
		<script type="text/javascript" src="../../js/symb/collections.index.js"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($collections_misc_collstatsMenu)?$collections_misc_collstatsMenu:false);
		include($serverRoot.'/header.php');
		?>
		<div id="innertext">
			<fieldset id="yearstatsbox" style="clear:both;margin-top:15px;width:97%;">
				<legend><b>Past Year Totals</b></legend>
				<table class="styledtable" style="width:98%;font-size:12px;">
					<tr>
						<th style="text-align:center;">Institution</th>
						<th style="text-align:center;">Object</th>
						<?php
						foreach($dateArr as $i => $month){
							echo '<th style="text-align:center;">'.$month.'</th>';
						}
						?>
					</tr>
					<?php
					$recCnt = 0;
					foreach($statArr as $code => $data){
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td>'.wordwrap($data['collectionname'],52,"<br />\n",true).'</td>';
						echo '<td>Specimens</td>';
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								echo '<td>'.$data['stats'][$month]['speccnt'].'</td>';
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Stage 1</td>';
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								echo '<td>'.$data['stats'][$month]['stage1Count'].'</td>';
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Stage 2</td>';
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								echo '<td>'.$data['stats'][$month]['stage2Count'].'</td>';
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Stage 3</td>';
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								echo '<td>'.$data['stats'][$month]['stage3Count'].'</td>';
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Images</td>';
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								echo '<td>'.$data['stats'][$month]['imgcnt'].'</td>';
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '</tr>';
						$recCnt++;
					}
					?>
				</table>
				<div style='float:right;margin:15px;' title="Save CSV">
					<form name="yearstatscsv" id="yearstatscsv" style="margin-bottom:0px" action="collstatscsv.php" method="post" onsubmit="">
						<input type="hidden" name="collids" id="collids" value='<?php echo $collId; ?>' />
						<input type="submit" name="action" value="Download CSV" />
					</form>
				</div>
			</fieldset>
		</div>
		<!-- end inner text -->
		<?php
			include($serverRoot.'/footer.php');		
		?>
	</body>
</html>