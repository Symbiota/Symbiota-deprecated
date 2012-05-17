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
		
		function ProcessReport(){
		  if(document.pressed == 'invoice')
		  {
		   document.reportsform.action ="reports/defaultinvoice.php";
		  }
		  else
		  if(document.pressed == 'spec')
		  {
			document.reportsform.action ="reports/defaultspecimenlist.php";
		  }
		  else
		  if(document.pressed == 'label')
		  {
			document.reportsform.action ="reports/defaultmailinglabel.php";
		  }
		  else
		  if(document.pressed == 'envelope')
		  {
			document.reportsform.action ="reports/defaultenvelope.php";
		  }
		  return true;
		}
		
		function verifyspeceditform(f){
			//Make sure at least on specimen checkbox is checked
			var cbChecked = false;
			var dbElements = document.getElementsByName("occid[]");
			for(i = 0; i < dbElements.length; i++){
				var dbElement = dbElements[i];
				if(dbElement.checked){
					cbChecked = true;
					break;
				}
			}
			if(!cbChecked){
				alert("Please select specimens to which you wish to apply the action");
				return false;
			}

			//If task equals delete, confirm action
			var applyTaskObj = f.applytask;
			var l = applyTaskObj.length;
			var applyTaskValue = "";
			for(var i = 0; i < l; i++) {
				if(applyTaskObj[i].checked) {
					applyTaskValue = applyTaskObj[i].value;
				}
			}
			if(applyTaskValue == "delete"){
				return confirm("Are you sure you want to remove selected specimens from this loan?");
			}

			return true;
		}
		
		function addSpecimen(f){ 
			var catalogNumber = f.catalognumber.value;
			var loanid = f.loanid.value;
			var collid = f.collid.value;
			if(!catalogNumber){
				alert("Please enter a catalog number!");
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
						if(responseCode == "0"){
							document.getElementById("addspecsuccess").style.display = "none";
							document.getElementById("addspecerr1").style.display = "block";
							document.getElementById("addspecerr2").style.display = "none";
							document.getElementById("addspecerr3").style.display = "none";
							setTimeout(function () { 
								document.getElementById("addspecerr1").style.display = "none";
								}, 3000);
							//alert("ERROR: Specimen record not found in database.");
						}
						else if(responseCode == "2"){
							document.getElementById("addspecsuccess").style.display = "none";
							document.getElementById("addspecerr1").style.display = "none";
							document.getElementById("addspecerr2").style.display = "block";
							document.getElementById("addspecerr3").style.display = "none";
							setTimeout(function () { 
								document.getElementById("addspecerr2").style.display = "none";
								}, 3000);
							//alert("ERROR: More than one specimen with that catalog number.");
						}
						else if(responseCode == "3"){
							document.getElementById("addspecsuccess").style.display = "none";
							document.getElementById("addspecerr1").style.display = "none";
							document.getElementById("addspecerr2").style.display = "none";
							document.getElementById("addspecerr3").style.display = "block";
							setTimeout(function () { 
								document.getElementById("addspecerr3").style.display = "none";
								}, 3000);
							//alert("ERROR: More than one specimen with that catalog number.");
						}
						else{
							f.catalognumber.value = "";
							document.getElementById("addspecsuccess").style.display = "block";
							document.getElementById("addspecerr1").style.display = "none";
							document.getElementById("addspecerr2").style.display = "none";
							document.getElementById("addspecerr3").style.display = "none";
							setTimeout(function () { 
								document.getElementById("addspecsuccess").style.display = "none";
								}, 3000);
							//alert("SUCCESS: Specimen added to loan.");
						}
					}
				};
				xmlHttp.open("POST",url,true);
				xmlHttp.send(null);
				return false;
			}
		}

		function openOccurrenceDetails(occid){
			occWindow=open("../individual/index.php?occid="+occid,"occdetails","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
			if(occWindow.opener == null) occWindow.opener = self;
			return false;
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
		
		function eventDateModified(eventDateInput){
			var dateStr = eventDateInput.value;
			if(dateStr == "") return true;

			var dateArr = parseDate(dateStr);
			if(dateArr['y'] == 0){
				alert("Unable to interpret Date. Please use the following formats: yyyy-mm-dd, mm/dd/yyyy, or dd mmm yyyy");
				return false;
			}
			else{
				//Check to see if date is in the future 
				try{
					var testDate = new Date(dateArr['y'],dateArr['m']-1,dateArr['d']);
					var today = new Date();
					if(testDate > today){
						alert("Was this plant really collected in the future? The date you entered has not happened yet. Please revise.");
						return false;
					}
				}
				catch(e){
				}

				//Check to see if day is valid
				if(dateArr['d'] > 28){
					if(dateArr['d'] > 31 
						|| (dateArr['d'] == 30 && dateArr['m'] == 2) 
						|| (dateArr['d'] == 31 && (dateArr['m'] == 4 || dateArr['m'] == 6 || dateArr['m'] == 9 || dateArr['m'] == 11))){
						alert("The Day (" + dateArr['d'] + ") is invalid for that month");
						return false;
					}
				}

				//Enter date into date fields
				var mStr = dateArr['m'];
				if(mStr.length == 1){
					mStr = "0" + mStr;
				}
				var dStr = dateArr['d'];
				if(dStr.length == 1){
					dStr = "0" + dStr;
				}
				eventDateInput.value = dateArr['y'] + "-" + mStr + "-" + dStr;
				if(dateArr['y'] > 0) distributeEventDate(dateArr['y'],dateArr['m'],dateArr['d']);
			}
			//fieldChanged('eventdate');
			return true;
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
			echo "<a href='index.php?collid=1'> <b>Loan Management</b></a>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../../index.php'>Home</a> &gt; ";
		echo "<a href='index.php?collid=1'> <b>Loan Management</b></a>";
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
						<li><a href="incoming.php?collid=<?php echo $collId; ?>"><span>Incoming Loans</span></a></li>
						<li><a href="exchange.php?collid=<?php echo $collId; ?>"><span>Gifts/Exchanges</span></a></li>
						<li><a href="#reportdiv">Reports</a></li>
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
			elseif($loanType == 'Out'){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="outgoingdetails.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>"><span>Loan Details</span></a></li>
						<li><a href="#addspecdiv"><span>Specimens</span></a></li>
					</ul>
					<div id="addspecdiv">
						<div style="float:right;margin:10px;">
							<a href="#" onclick="toggle('newspecdiv');toggle('refreshbut');">
								<img src="../../images/add.png" alt="Create New Loan" />
							</a>
						</div>
						<div id="newspecdiv" style="display:none;">
							<form name="addspecform" action="index.php" method="post" onsubmit="return false">
								<fieldset>
									<legend><b>Add Specimen</b></legend>
									<div style="float:left;padding-bottom:2px;">
										<b>Catalog Number: </b><input type="text" autocomplete="off" name="catalognumber" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
									</div>
									<div id="addspecsuccess" style="float:left;margin-left:30px;padding-bottom:2px;color:green;display:none;">
										SUCCESS: Specimen record added to loan.
									</div>
									<div id="addspecerr1" style="float:left;margin-left:30px;padding-bottom:2px;color:red;display:none;">
										ERROR: No specimens found with that catalog number.
									</div>
									<div id="addspecerr2" style="float:left;margin-left:30px;padding-bottom:2px;color:red;display:none;">
										ERROR: More than one specimen located with same catalog number.
									</div>
									<div id="addspecerr3" style="float:left;margin-left:30px;padding-bottom:2px;color:orange;display:none;">
										Warning: Specimen already linked to loan.
									</div>
									<div style="padding-top:8px;clear:both;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
										<input name="formsubmit" type="button" value="Add Specimen" onclick="addSpecimen(this.form)" />
									</div>
								</fieldset>
							</form>
						</div>
						<?php 
						$specList = $loanManager->getSpecList($loanId);
						if($specList){
						?>
							<div style="height:25px;margin-top:15px;">
								<span style="float:left;margin-left:15px;">
									<input name="" value="" type="checkbox" onclick="selectAll(this);" />
									Select/Deselect All
								</span>
								<span id="refreshbut" style="display:none;float:right;margin-right:15px;">
									<form name="refreshspeclist" action="index.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>#addspecdiv" method="post">
										<button name="formsubmit" type="submit" value="Refresh">Refresh List</button>
									</form>
								</span>
							</div>
							<form name="speceditform" action="index.php?collid=<?php echo $collId; ?>&loanid=<?php echo $loanId; ?>&loantype=<?php echo $loanType; ?>#addspecdiv" method="post" onsubmit="return verifyspeceditform(this)" >
								<table class="styledtable">
									<tr>
										<th style="width:25px;text-align:center;">&nbsp;</th>
										<th style="width:100px;text-align:center;">Catalog Number</th>
										<th style="width:375px;text-align:center;">Details</th>
										<th style="width:75px;text-align:center;">Date Returned</th>
									</tr>
									<?php
									foreach($specList as $k => $specArr){
										?>
										<tr>
											<td>
												<input name="occid[]" type="checkbox" value="<?php echo $specArr['occid']; ?>" />
											</td>
											<td>
												<a href="#" onclick="openOccurrenceDetails(<?php echo $k; ?>);">
													<?php echo $specArr['catalognumber']; ?>
												</a>
											</td>
											<td>
												<?php 
												$loc = $specArr['locality'];
												if(strlen($loc) > 500) $loc = substr($loc,400);
												echo '<i>'.$specArr['sciname'].'</i>; ';
												echo  $specArr['collector'].'; '.$loc;
												?> 
												
											</td>
											<td><?php echo $specArr['returndate']; ?></td>
										</tr>
										<?php 
									}
								?>
								</table>
								<table>
									<tr>
										<td colspan="10" valign="bottom">
											<div style="margin:10px;">
												<div style="float:left;">
													<input name="applytask" type="radio" value="check" CHECKED title="Check-in Specimens" />Check-in Specimens<br/>
													<input name="applytask" type="radio" value="delete" title="Delete Specimens" />Delete Specimens from Loan
												</div>
												<span style="margin-left:25px;">
													<input name="formsubmit" type="submit" value="Perform Action" />
													<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
													<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
												</span>
											</div>
										</td>
									</tr>
								</table>
							</form>
						<?php
						}
						else{
							echo '<div style="font-weight:bold;font-size:120%;">There are no specimens registered for this loan.</div>';
						}
						?>
					</div>
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
						<li><a href="exchangedetails.php?collid=<?php echo $collId; ?>&exchangeid=<?php echo $exchangeId; ?>&loantype=<?php echo $loanType; ?>"><span>Exchange Details</span></a></li>
					</ul>
				</div>
				<?php 
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