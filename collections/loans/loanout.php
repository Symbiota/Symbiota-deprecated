<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$loanOutId = array_key_exists('loanoutid',$_REQUEST)?$_REQUEST['loanoutid']:0;
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
			$loanOutId = $loanManager->getLoanOutId;
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
			
			if(!$loanOutId){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="#listdiv">List of Loans</a></li>
						<li><a href="#outstandingdiv">Loans Out Standing</a></li>
						<li><a href="#reportdiv">Reports</a></li>
						<li><a href="#creatediv">Create New Loan</a></li>
					</ul>
				</div>
				<div id="listdiv">
					<ul>
						<?php 
						$loanList = $loanManager->getLoanList();
						foreach($loanList as $k => $loanArr){
							echo '<li><a href="loanout.php?collid='.$collId.'&loanoutid='.$k.'">'.$loanArr['title'].'</a> ('.($loanArr['dateclosed']?$loanArr['dateclosed']:'<b>OPEN</b>').')</li>';
						}
						?>
					</ul>
				</div>
				<div id="outstandingdiv">
					<?php 
					//List outstanding loans, or can be included in general loan listing with javascript checklist that hides closed loans
					//Maybe include printable form for mailing to institution notifying them that they are infraction
					
					?>
				</div>
				<div id="reportdiv">
					<?php 
					//Invoices, mailing labels, etc
					
					?>
				</div>
				<div id="creatediv">
					<form name="newloanform" action="loanout.php" method="post">
						<fieldset>
							<legend>New Loan</legend>
							
							<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
							<input name="formsubmit" type="submit" value="Create Loan" />
						</fieldset>
					</form>
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
						$loanArr = getLoanDetails($loanOutId);
						
						?>
					</div>
					<div id="editdiv">
						<?php 
						//Allow user to edit loan
						//Can also put this in loan div and allow editor to toggle editing controls using javascript
						 
						?>
						<form name="editloanform" action="loanout.php" method="post">
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
				echo '<h2>Please <a href="'.$clientRoot.'/profile/index.php?collid='.$collId.'&refurl='.$clientRoot.'/collections/loans/loanout.php?collid='.$collId.'">login</a></h2>';
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