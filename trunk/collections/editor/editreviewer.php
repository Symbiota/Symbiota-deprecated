<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecEditReviewManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$faStatus = array_key_exists('fastatus',$_REQUEST)?$_REQUEST['fastatus']:0;
$frStatus = array_key_exists('frstatus',$_REQUEST)?$_REQUEST['frstatus']:1;

$reviewManager = new SpecEditReviewManager($collId);

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$status = "";
if($editable){
	if($action == ''){
		//$reviewManager->;
	}
}

?>
<html>
	<head>
		<title>Specimen Edit Reviewer</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<style type="text/css">
			#edittab{
				width:100%;
				border-collapse:collapse;
			}
			#edittab td, #edittab th {
				font-size:1em;
				border:1px solid #98bf21;
				padding:3px 7px 2px 7px;
			}
			#edittab th {
				text-align:left;
				padding-top:5px;
				padding-bottom:4px;
				background-color:#A7C942;
				color:#ffffff;
			}
			#edittab tr.alt td {
				color:#000000;
				background-color:#EAF2D3;
			}
		</style>
		<script language="javascript">
			function toggle(divName){
				divObj = document.getElementById(divName);
				if(divObj != null){
					if(divObj.style.display == "block"){
						divObj.style.display = "none";
					}
					else{
						divObj.style.display = "block";
					}
				}
				else{
					divObjs = document.getElementsByTagName("div");
					divObjLen = divObjs.length;
					for(i = 0; i < divObjLen; i++) {
						var obj = divObjs[i];
						if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
							if(obj.style.display=="none"){
								obj.style.display="inline";
							}
							else {
								obj.style.display="none";
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
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php 
			if($symbUid){
				if($status){ 
					?>
					<div style='float:left;margin:20px 0px 20px 0px;'>
						<hr/>
						<?php echo $status; ?>
						<hr/>
					</div>
					<?php 
				}
				if($collId){
					?>
					<div style="float:right;">
						<form name="filter" action="editreviewer.php" method="post">
							<fieldset>
								<legend>Filter</legend>
								Status: 
								<select>
									<option value="0">All Records</option>
									<option value="1">Not Applied</option>
									<option value="2">Applied</option>
								</select><br/>
								<select>
									<option value="0">All Records</option>
									<option value="1">Not Applied</option>
									<option value="2">Applied</option>
								</select>
								<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
								<input name="action" type="submit" value="Filter Records" />
							</fieldset>
						</form>
					</div>
					<div style="clear:both;">
						<form name="editform" action="editreviewer.php" method="post" onsubmit="return validateEditForm(this);">
							<h2>Edits</h2>
							<table id="edittab">
								<tr>
									<th>ID</th>
									<th>Field Name</th>
									<th>New Value</th>
									<th>Old Value</th>
									<th>Review Status</th>
									<th>Applied Status</th>
									<th>Editor</th>
								</tr>
								<?php 
								$editArr = $reviewManager->getEditArr($faStatus, $frStatus);
								if($editArr){
									$recCnt = 0;
									foreach($editArr as $occid => $edits){
										foreach($edits as $ocedid => $edObj){
											?>
											<tr <?php echo ($recCnt%2?'class="alt"':'') ?>>
												<td>
													<input name="ocedid[]" type="checkbox" value="<?php echo $ocedid; ?>" />
												</td>
												<td>
													<div title="Field Name">
														<?php echo $edObj['fname']; ?>
													</div>
												</td>
												<td>
													<div title="New Status">
														<?php echo $edObj['fvaluenew']; ?>
													</div>
												</td>
												<td>
													<div title="Old Value">
														<?php echo $edObj['fvalueold']; ?>
													</div>
												</td>
												<td>
													<div title="Review Status">
														<?php
														$rStatus = $edObj['rstatus'];
														if($rStatus == 1){
															echo 'OPEN';
														}
														elseif($rStatus == 2){
															echo 'PENDING';
														}
														elseif($rStatus == 3){
															echo 'CLOSED';
														}
														else{
															echo 'UNKNOWN';
														}
														?>
													</div>
												</td>
												<td>
													<div title="Applied Status">
														<?php 
														$aStatus = $edObj['astatus'];
														if($rStatus == 1){
															echo 'Edits Applied';
														}
														else{
															echo 'Not Applied';
														}
														?>
													</div>
												</td>
												<td>
													<div title="Editor">
														<?php echo $edObj['uname']; ?>
													</div>
												</td>
											</tr>
											<?php 
										}
										$redCnt++;
									}
								?>
								<tr><td colspan="7">
									<div style="float:left;">
										<input name="action" type="radio" title="Apply Edits, if not already done" />Apply Edits<br/>
										<input name="action" type="radio" title="Revert Edits" />Revert Edits
									</div>
									<div style="float:left;">
										Change Status to:
										<select name="rstatus">
											<option value="0">LEAVE AS IS</option>
											<option value="1">OPEN</option>
											<option value="2">PENDING</option>
											<option value="3">CLOSED</option>
										</select>
									</div>
									<div style="float:left;">
										<input name="action" type="submit" value="Perform Action" />
									</div>
								</td></tr>
								<?php 
								}
								else{
									?>
									<tr>
										<td colspan="7">
											<div style="font-weight:bold;font-size:150%;margin:20px;">There are no Edits matching search criteria</div>
										</td>
									</tr>
									<?php 
								}
								?>
							</table>
						</form>
					</div>
					<?php 
				}
				else{
					if($collList = $reviewManager->getCollectionList()){
						?>
						<div style="clear:both;">
							<form name="collidform" action="editreviewer.php" method="post" onsubmit="return validateCollidForm(this);">
								<fieldset>
									<legend><b>Collection Projects</b></legend>
									<div style="margin:15px;">
										<?php 
										foreach($collList as $cId => $cName){
											echo '<input type="radio" name="collid" value="'.$cId.'" /> '.$cName.'<br/>';
										}
										?>
									</div>
									<div style="margin:15px;">
										<input type="submit" name="action" value="Select Collection for Review" />
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					}
					else{
						echo '<div>There are no Collection Project for which you have authority to review</div>';						
					} 
				}
			}
			else{
				?>
				<div style='font-weight:bold;'>
					Please <a href='../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/editor/editreviewer.php?collid=<?php echo $collId; ?>'>login</a>!
				</div>
				<?php 
			}
			?>
		</div>
		<?php include($serverRoot.'/footer.php');?>
	</body>
</html>
