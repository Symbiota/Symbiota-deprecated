<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;
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
		if($formSubmit == 'Create Loan Out'){
			$statusStr = $loanManager->createNewLoanOut($_POST);
			$loanId = $loanManager->getLoanId();
			$loanType = 'out';
		}
		elseif($formSubmit == 'Create Loan In'){
			$statusStr = $loanManager->createNewLoanIn($_POST);
			$loanId = $loanManager->getLoanId();
			$loanType = 'in';
		}
		elseif($formSubmit == 'Create Exchange'){
			$statusStr = $loanManager->createNewExchange($_POST);
			$exchangeId = $loanManager->getExchangeId();
			$loanType = 'exchange';
		}
		elseif($formSubmit == 'Save Exchange'){
			$statusStr = $loanManager->editExchange($_POST);
			$loanType = 'exchange';
		}
		elseif($formSubmit == 'Save Outgoing'){
			$statusStr = $loanManager->editLoanOut($_POST);
			$loanType = 'out';
		}
		elseif($formSubmit == 'Delete Loan'){
			$status = $loanManager->deleteLoan($loanId);
			if($status) $loanId = 0;
		}
		elseif($formSubmit == 'Delete Exchange'){
			$status = $loanManager->deleteExchange($exchangeId);
			if($status) $exchangeId = 0;
		}
		elseif($formSubmit == 'Save Incoming'){
			$statusStr = $loanManager->editLoanIn($_POST);
			$loanType = 'in';
		}
		elseif($formSubmit == 'Perform Action'){
			$statusStr = $loanManager->editSpecimen($_REQUEST);
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
	<script type="text/javascript" src="../../js/symb/collections.loans.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_loans_indexMenu)?$collections_loans_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($collections_loans_indexCrumbs)){
		if($collections_loans_indexCrumbs){
			?>
			<div class='navpath'>
				<a href='../../index.php'>Home</a> &gt;&gt; 
				<?php echo $collections_loans_indexCrumbs; ?>
				<a href='index.php?collid=1'> <b>Loan Management</b></a>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
			<a href='index.php?collid=1'> <b>Loan Management</b></a>
		</div>
		<?php 
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
			
			if(!$loanId && !$exchangeId){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="outgoing.php?collid=<?php echo $collId.'&searchterm='.$searchTerm.'&displayall='.$displayAll; ?>"><span>Outgoing Loans</span></a></li>
						<li><a href="incoming.php?collid=<?php echo $collId.'&searchterm='.$searchTerm.'&displayall='.$displayAll; ?>"><span>Incoming Loans</span></a></li>
						<li><a href="exchange.php?collid=<?php echo $collId; ?>"><span>Gifts/Exchanges</span></a></li>
						<!-- <li><a href="#reportdiv">Reports</a></li> -->
					</ul>
					<div id="reportdiv" style="height:50px;">
						-- IN DEVELOPMENT --
						List loans outstanding, Invoices, mailing labels, etc?
						<?php 
						
						?>
					</div>
				</div>
				<?php 
			}
			elseif($loanType == 'out'){
				include_once('outgoingdetails.php');
			}
			elseif($loanType == 'in'){
				include_once('incomingdetails.php');
			}
			elseif($loanType == 'exchange'){
				include_once('exchangedetails.php');
			}
			else{
				if(!$symbUid){
					echo '<h2>Please <a href="'.$clientRoot.'/profile/index.php?collid='.$collId.'&refurl='.$clientRoot.'/collections/loans/index.php?collid='.$collId.'">login</a></h2>';
				}
				elseif(!$collId){
					echo '<h2>Collection not defined</h2>';
				}
				elseif(!$isEditor){
					echo '<h2>You are not authorized to manage loans</h2>';
				}
			}
		}
		else{
			if(!$symbUid){
				echo 'Please <a href="../../profile/index.php?refurl=../collections/loans/index.php?collid='.$collId.'">login</a>';
			}
			elseif(!$isEditor){
				echo '<h2>You are not authorized to add occurrence records</h2>';
			}
			else{
				echo '<h2>ERROR: unknown error, please contact system administrator</h2>';
			}
		}
		?>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>