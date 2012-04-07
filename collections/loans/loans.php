<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$displayAll = array_key_exists('displayall',$_POST)?$_POST['displayall']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$isEditor = 0;
if($symbUid && $collId){
	if($isAdmin	|| (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))
		|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
}

$loanManager = new SpecLoans();
if($collId) $loanManager->setCollId($collId);

$statusStr = '';
if($isEditor){
	if($formSubmit){
		if($formSubmit == 'Create Loan'){
			$statusStr = $loanManager->createNewLoan($_POST);
			$loanId = $loanManager->getLoanId();
		}
		elseif($formSubmit == 'Submit Edits'){
			$statusStr = $loanManager->editLoan($_POST);
		}
		
	}
}

header("Content-Type: text/html; charset=".$charset);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title><?php echo $defaultTitle; ?> Loan Management</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script language="javascript" type="text/javascript">
		$(document).ready(function() {
			if(!navigator.cookieEnabled){
				alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
			}

			$('#tabs').tabs();

		});

		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
			  	var divs = document.getElementsByTagName("div");
			  	for (var h = 0; h < divs.length; h++) {
			  	var divObj = divs[h];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
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
	$displayLeftMenu = (isset($collections_loans_indexMenu)?$collections_loans_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($collections_loans_indexCrumbs)){
		if($collections_loans_indexCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt; ";
			echo $collections_loans_indexCrumbs;
			echo " <b>Loan Management</b>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../../index.php'>Home</a> &gt; ";
		echo "<b>Loan Management</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($symbUid && $isEditor && $collId){
			//Collection is defined and User is logged-in and have permissions
			if($statusStr){
				?>
				<hr/>
				<div style="margin:15px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php 
			}
			
			if(!$loanId){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="#loanoutdiv">Outgoing Loans</a></li>
						<li><a href="#reportdiv">Reports</a></li>
						<li><a href="#loanindiv">Incoming Loans</a></li>
						<li><a href="#requestdiv">Request a Loan</a></li>
					</ul>
					<div id="loanoutdiv" style="">
						<div style="float:right;">
							<form name='optionform' action='loans.php' method='post'>
								<fieldset>
								    <legend><b>Options</b></legend>
							    	<div>
							    		<b>Search:</b> 
										<input type="text" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
									</div>
									<div>
										<input type="radio" name="displayall" value="0"<?php echo ($displayAll==0?'checked':'');?> /> 
										Display outstanding loans only
									</div>
									<div>
										<input type="radio" name="displayall" value="1"<?php echo ($displayAll?'checked':'');?> /> 
										Display all loans
									</div>
									<div>
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input type="submit" name="formsubmit" value="Refresh List" />
									</div>
								</fieldset>
							</form>	
						</div>
						<div style="float:right;margin:10px;">
							<a href="#" onclick="toggle('newloandiv')">
								<img src="../../images/add.png" alt="Create New Loan" />
							</a>
						</div>
						<div id="newloandiv" style="display:none;">
							<form name="newloanform" action="loans.php" method="post">
								<fieldset>
									<legend><b>New Loan</b></legend>
									<div style="float:right;padding-bottom:2px;">
										<span>
											<b>Loan Number: </b> 
											<input type="text" name="loanidentifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
										</span>
									</div>
									<div style="padding-top:20px;">
										<span>
											Sent To:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="reqInstitution" style="width:400px;">
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'">'.$v.'</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Requested for:
										</span>
										<span style="margin-left:125px;">
											Entered By:
										</span>
										<span style="margin-left:65px;">
											Processed By:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value=" " onchange=" " />
										</span>
										<span style="margin-left:20px;">
											<input type="text" name="createdBy" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
										</span>
										<span style="margin-left:20px;">
											<input type="text" name="processedBy" tabindex="96" maxlength="32" style="width:100px;" value=" " onchange=" " />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Date Sent:
										</span>
										<span style="margin-left:40px;">
											Date Due:
										</span>
										<span style="margin-left:40px;">
											# of Boxes:
										</span>
										<span style="margin-left:5px;">
											Shipping Service:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="dateSent" tabindex="100" maxlength="32" style="width:80px;" value=" " onchange=" " />
										</span>
										<span style="margin-left:10px;">
											<input type="text" name="dateDue" tabindex="100" maxlength="32" style="width:80px;" value=" " onchange=" " />
										</span>
										<span style="margin-left:10px;">
											<input type="text" name="totalBoxes" tabindex="100" maxlength="32" style="width:50px;" value=" " onchange=" " />
										</span>
										<span style="margin-left:10px;">
											<input type="text" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value=" " onchange=" " />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Loan Description:
										</span>
										<span style="margin-left:150px;">
											Notes:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<textarea name="description" rows="10" style="width:200px;resize:vertical;" value=" " onchange=" ">
											</textarea>
										</span>
										<span style="margin-left:40px;">
											<textarea name="notes" rows="10" style="width:200px;resize:vertical;" value=" " onchange=" ">
											</textarea>
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="formsubmit" type="submit" value="Create Loan" />
									</div>
								</fieldset>
							</form>
						</div>
						<div>
							<h3>Loan Records</h3>
							<ul>
								<?php 
								$loanList = $loanManager->getLoanList($searchTerm,$displayAll);
								if($loanList){
									foreach($loanList as $k => $loanArr){
										echo '<li>';
										echo '<a href="loans.php?collid='.$collId.'&loanid='.$k.'">';
										echo $loanArr['loanidentifier'];
										echo '</a> ('.($loanArr['dateclosed']?'Closed: '.$loanArr['dateclosed']:'<b>OPEN</b>').')';
										echo '</li>';
									}
								}
								else{
									echo '<div style="font-weight:bold;font-size:120%;">There are no loans registered for this collection</div>';
								}
								?>
							</ul>
						</div>
						<div style="clear:both;">&nbsp;</div>
					</div>
					<div id="reportdiv" style="height:500px;">
						List loans outstanding, Invoices, mailing labels, etc
						<?php 
						
						?>
					</div>
					<div id="loanindiv" style="height:500px;">
						List all loans-in 
						<?php 
						
						?>
					</div>
					<div id="requestdiv" style="height:500px;">
						<?php 
						
						?>
					</div>
				</div>
				<?php 
			}
			else{
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="#loandiv">Loan Details</a></li>
						<li><a href="#addspecdiv">Add Specimens</a></li>
						<li><a href="#checkindiv">Check-in Loan</a></li>
					</ul>
					<div id="loandiv">
						<?php 
						//Show loan details
						$loanArr = $loanManager->getLoanDetails($loanId);
						//$loanDetails = $loanManager->getLoanDetails($loanId);
						//foreach($loanDetails as $k => $loanArr){
						?>
						<form name="editloanform" action="loans.php" method="post">
							<fieldset>
								<legend>Loan Details</legend>
								<div style="padding-top:4px;">
										<span style="margin-left:235px;">
											Entered By:
										</span>
										<span style="margin-left:70px;">
											Processed By:
										</span>
										<span style="margin-left:50px;">
											Date Sent:
										</span>
										<span style="margin-left:55px;">
											Date Due:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<b>Loan Number:</b> <input type="text" name="loanIdentifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifier']; ?>" disabled />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="createdBy" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['createdBy']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedBy" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedBy']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="dateSent" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateSent']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="dateDue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateDue']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Sent To:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="iidreceiver" style="width:400px;">
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$loanArr['iid']?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Requested for:
										</span>
										<span style="margin-left:340px;">
											# of Boxes:
										</span>
										<span style="margin-left:25px;">
											Shipping Service:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="forWhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forWhom']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<b>Specimen Total:</b> <input type="text" name="totalSpecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value=" " onchange=" " disabled />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="totalBoxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalBoxes']; ?>" onchange=" " />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="shippingMethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingMethod']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Loan Description:
										</span>
										<span style="margin-left:270px;">
											Notes:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['description']; ?></textarea>
										</span>
										<span style="margin-left:40px;">
											<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['notes']; ?></textarea>
										</span>
									</div>
									<hr />
									<div style="padding-top:4px;">
										<span>
											Date Returned:
										</span>
										<span style="margin-left:30px;">
											Date Closed:
										</span>
										<span style="margin-left:40px;">
											Ret. Processed By:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="dateReturned" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateReturned']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="dateClosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateClosed']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedByReturn" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedByReturn']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
										<input name="formsubmit" type="submit" value="Submit Edits" />
									</div>
							</fieldset>
						</form>
						<?php
						//}
						?>
					</div>
					<div id="addspecdiv">
						<?php 
						//Add specimens to loan
						 
						?>
					</div>
					<div id="checkindiv">
						<?php 
						//Form for check-in loan 
						//Form lets user scan or key-in barcodes, javascript verifies accession numbers as checked in 
						
						?>
					</div>
				</div>
				<?php 
			}
		}
		else{
			if(!$symbUid){
				echo '<h2>Please <a href="'.$clientRoot.'/profile/index.php?collid='.$collId.'&refurl='.$clientRoot.'/collections/loans/loans.php?collid='.$collId.'">login</a></h2>';
			}
			elseif(!$collId){
				echo '<h2>Collection not defined</h2>';
			}
			elseif(!$isEditor){
				echo '<h2>You are not authorized to manage loans</h2>';
			}
		}
		?>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>