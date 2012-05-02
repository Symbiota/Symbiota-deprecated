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
			$loanType = 'Out';
		}
		elseif($formSubmit == 'Create Loan In'){
			$statusStr = $loanManager->createNewLoanIn($_POST);
			$loanId = $loanManager->getLoanId();
			$loanType = 'In';
		}
		elseif($formSubmit == 'Create Exchange'){
			$statusStr = $loanManager->createNewExchange($_POST);
			$exchangeId = $loanManager->getExchangeId();
			$loanType = 'Exchange';
		}
		elseif($formSubmit == 'Save Exchange'){
			$statusStr = $loanManager->editExchange($_POST);
			$loanType = 'Exchange';
		}
		elseif($formSubmit == 'Save Outgoing'){
			$statusStr = $loanManager->editLoanOut($_POST);
			$loanType = 'Out';
		}
		elseif($formSubmit == 'Save Incoming'){
			$statusStr = $loanManager->editLoanIn($_POST);
			$loanType = 'In';
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
		
		function selectAll(cb){
			boxesChecked = true;
			if(!cb.checked){
				boxesChecked = false;
			}
			var dbElements = document.getElementsByName("occid[]");
			for(i = 0; i < dbElements.length; i++){
				var dbElement = dbElements[i];
				dbElement.checked = boxesChecked;
			}
		}

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
		
		function GetXmlHttpObject(){
			var xmlHttp=null;
			try{
				// Firefox, Opera 8.0+, Safari, IE 7.x
				xmlHttp=new XMLHttpRequest();
			}
			catch (e){
				// Internet Explorer
				try{
					xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
				}
				catch(e){
					xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
				}
			}
			return xmlHttp;
		}
		
		function addSpecimen(f){ 
			var catalogNumber = f.catalognumber.value;
			var loanid = f.loanid.value;
			var collid = f.collid.value;
			if(!catalogNumber){
				alert("There are no specimens linked to that catalog number!");
				return false;
			}
			else{
				xmlHttp=GetXmlHttpObject();
				if (xmlHttp==null){
					alert ("Your browser does not support AJAX!");
					return false;
				}
				var url="rpc/insertloanspecimens.php";
				url=url+"?loanid="+loanid;
				url=url+"&catalognumber="+catalogNumber;
				url=url+"&collid="+collid;
				xmlHttp.onreadystatechange=function(){
					if(xmlHttp.readyState==4 && xmlHttp.status==200){
						responseCode = xmlHttp.responseText;
						if(responseCode == '0'){
							alert("ERROR: Specimen record not found in database.");
						}
						else if(responseCode == '2'){
							alert("ERROR: More than one specimen with that catalog number.");
						}
						else{
							alert("SUCCESS: Specimen added to loan.");
						}
					}
				};
				xmlHttp.open("POST",url,true);
				xmlHttp.send(null);
				return false;
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
			echo "<a href='loans.php?collid=1'> <b>Loan Management</b></a>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../../index.php'>Home</a> &gt; ";
		echo "<a href='loans.php?collid=1'> <b>Loan Management</b></a>";
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
			
			if(!$loanId && !$exchangeId){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="outgoing.php?collid=<?php echo $collId; ?>"><span>Outgoing Loans</span></a></li>
						<!-- <li><a href="#loanoutdiv">Outgoing Loans</a></li> -->
						<li><a href="incoming.php?collid=<?php echo $collId; ?>"><span>Incoming Loans</span></a></li>
						<!-- <li><a href="#loanindiv">Incoming Loans</a></li> -->
						<li><a href="exchange.php?collid=<?php echo $collId; ?>"><span>Gifts/Exchanges</span></a></li>
						<!-- <li><a href="#newexchangediv">Gifts/Exchanges</a></li> -->
						<li><a href="#reportdiv">Reports</a></li>
					</ul>
					<div id="reportdiv" style="height:50px;">
						List loans outstanding, Invoices, mailing labels, etc
						<?php 
						
						?>
					</div>
				<?php 
			}
			elseif($loanType == 'Out'){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="outgoingdetails.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>"><span>Loan Details</span></a></li>
						<li><a href="addspecimen.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>"><span>Add/Edit Specimens</span></a></li>
					</ul>
				</div>
				<?php 
			}
			elseif($loanType == 'In'){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="incomingdetails.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>"><span>Loan Details</span></a></li>
					</ul>
				</div>
			<?php 
			}
			elseif($loanType == 'Exchange'){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="exchangedetails.php"<span>Exchange Details</span></a></li>
					</ul>
				</div>
				<?php 
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
		}	
		?>
		</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>