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
			$loanId = $loanManager->getLoanId;
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
			echo "<a href='../index.php'>Home</a> &gt; ";
			echo $collections_loans_indexCrumbs;
			echo " <b>Loan Management</b>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
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
					<div id="loanoutdiv" style="height:500px;">
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
								<img src="../../images/add.png" />
							</a>
						</div>
						<div id="newloandiv" style="display:none;">
							<form name="newloanform" action="loans.php" method="post">
								<fieldset>
									<legend><b>New Loan</b></legend>
									<div>
										List input fields for creating a new loan
									</div>
									<div>
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="formsubmit" type="submit" value="Create Loan" />
									</div>
								</fieldset>
							</form>
						</div>
						<div>
							<ul>
								<?php 
								$loanList = $loanManager->getLoanList($searchTerm,$displayAll);
								if($loanList){
									foreach($loanList as $k => $loanArr){
										echo '<li>';
										echo '<a href="loans.php?collid='.$collId.'&loanid='.$k.'">';
										echo $loanArr['loanidentifier'];
										echo '</a> ('.($loanArr['dateclosed']?$loanArr['dateclosed']:'<b>OPEN</b>').')';
										echo '</li>';
									}
								}
								else{
									echo '<div style="font-weight:bold;font-size:120%;">There are no loans registered for this collection</div>';
								}
								?>
							</ul>
						</div>
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
						<li><a href="#editdiv">Edit Loan</a></li>
						<li><a href="#checkindiv">Check-in Loan</a></li>
					</ul>
					<div id="loandiv">
						<?php 
						//Show loan details
						$loanArr = getLoanDetails($loanId);
						
						?>
					</div>
					<div id="editdiv">
						<?php 
						//Allow user to edit loan
						//Can also put this in loan div and allow editor to toggle editing controls using javascript
						 
						?>
						<form name="editloanform" action="loans.php" method="post">
							<fieldset>
								<legend>Loan Editor</legend>
								
								<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
								<input name="formsubmit" type="submit" value="Submit Edits" />
							</fieldset>
						</form>
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