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
						<li><a href="#loanoutdiv">Outgoing Loans</a></li>
						<li><a href="#loanindiv">Incoming Loans</a></li>
						<li><a href="#newexchangediv">Gifts/Exchanges</a></li>
						<li><a href="#reportdiv">Reports</a></li>
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
							<a href="#" onclick="toggle('newloanoutdiv')">
								<img src="../../images/add.png" alt="Create New Loan" />
							</a>
						</div>
						<div id="newloanoutdiv" style="display:none;">
							<form name="newloanoutform" action="loans.php" method="post">
								<fieldset>
									<legend><b>New Loan</b></legend>
									<div style="padding-top:4px;">
										<span>
											Entered By:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
										</span>
										<span style="float:right;">
											<b>Loan Identifier: </b> 
											<input type="text" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
										</span>
									</div>
									<div style="padding-top:6;">
										<span>
											Sent To:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="reqinstitution" style="width:400px;">
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'">'.$v.'</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<button name="formsubmit" type="submit" value="Create Loan Out" />Create Loan</button>
									</div>
								</fieldset>
							</form>
						</div>
						<div>
							<?php 
							$loanOutList = $loanManager->getLoanOutList($searchTerm,$displayAll);
							if($loanOutList){
								echo '<h3>Outgoing Loan Records</h3>';
								echo '<ul>';
								foreach($loanOutList as $k => $loanArr){
									echo '<li>';
									echo '<a href="loans.php?collid='.$collId.'&loanid='.$k.'&loantype=Out">';
									echo $loanArr['loanidentifierown'];
									echo '</a> ('.($loanArr['dateclosed']?'Closed: '.$loanArr['dateclosed']:'<b>OPEN</b>').')';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo '<div style="font-weight:bold;font-size:120%;">There are no loans out registered for this collection</div>';
							}
							?>
						</div>
						<div style="clear:both;">&nbsp;</div>
					</div>
					<div id="loanindiv" style="">
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
							<a href="#" onclick="toggle('newloanindiv')">
								<img src="../../images/add.png" alt="Create New Loan" />
							</a>
						</div>
						<div id="newloanindiv" style="display:none;">
							<form name="newloaninform" action="loans.php" method="post">
								<fieldset>
									<legend><b>New Loan</b></legend>
									<div style="padding-top:4px;">
										<span>
											Entered By:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="createdbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
										</span>
										<span style="float:right;">
											<b>Loan Identifier: </b> 
											<input type="text" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
										</span>
									</div>
									<div style="padding-top:6;">
										<span>
											Sent From:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="iidowner" style="width:400px;">
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'">'.$v.'</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<button name="formsubmit" type="submit" value="Create Loan In" />Create Loan</button>
									</div>
								</fieldset>
							</form>
						</div>
						<div>
							<?php 
							$loansOnWay = $loanManager->getLoanOnWayList();
							if($loansOnWay){
								echo '<h3>Loans on Their Way</h3>';
								echo '<ul>';
								foreach($loansOnWay as $k => $loanArr){
									echo '<li>';
									echo '<a href="loans.php?collid='.$collId.'&loanid='.$k.'&loantype=In">';
									echo $loanArr['loanidentifierown'];
									echo ' from '.$loanArr['collectionname'].'</a>';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo '<div style="font-weight:bold;font-size:120%;">There are no loans on their way to this collection.</div>';
							}
							?>
						</div>
						<div>
							<?php 
							$loanInList = $loanManager->getLoanInList($searchTerm,$displayAll);
							if($loanInList){
								echo '<h3>Incoming Loan Records</h3>';
								echo '<ul>';
								foreach($loanInList as $k => $loanArr){
									echo '<li>';
									echo '<a href="loans.php?collid='.$collId.'&loanid='.$k.'&loantype=In">';
									echo $loanArr['loanidentifierborr'];
									echo '</a> ('.($loanArr['dateclosed']?'Closed: '.$loanArr['dateclosed']:'<b>OPEN</b>').')';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo '<div style="font-weight:bold;font-size:120%;">There are no loans in registered for this collection</div>';
							}
							?>
						</div>
						<div style="clear:both;">&nbsp;</div>
					</div>
					<div id="newexchangediv" style="">
					<!-- BEGIN EXCHANGE -->	
					
						<form name="newexchangegiftform" action="loans.php" method="post">
								<fieldset>
									<legend>New Gift/Exchange</legend>
									<div style="padding-top:4px;">
										<span style="margin-left:290px;">
											Entered By:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<b>Transaction Number:</b> <input type="text" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
										</span>
										<span style="margin-left:40px;">
											<input type="text" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Institution:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="iid" style="width:400px;" >
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'">'.$v.'</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<button name="formsubmit" type="submit" value="Create Exchange" />Create</button>
									</div>
								</fieldset>
							</form>
					
					<!-- END OF EXCHANGE -->
					</div>
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
						<li><a href="#loandiv">Loan Details</a></li>
						<li><a href="#addspecdiv">Add/Edit Specimens</a></li>
					</ul>
					<div id="loandiv">
						<?php 
						//Show loan details
						$loanArr = $loanManager->getLoanOutDetails($loanId);
						$specTotal = $loanManager->getSpecTotal($loanId);
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
											<b>Loan Number:</b> <input type="text" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" disabled />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['createdbyown']; ?>" onchange=" " disabled />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyown']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="datesent" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datesent']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="datedue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datedue']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Sent To:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="iidborrower" style="width:400px;" disabled >
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$loanArr['iidborrower']?'SELECTED':'').'>'.$v.'</option>';
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
											<input type="text" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<b>Specimen Total:</b> <input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($specTotal?$specTotal['speccount']:0);?>" onchange=" " disabled />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="totalboxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxes']; ?>" onchange=" " />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethod']; ?>" onchange=" " />
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
											Date Received:
										</span>
										<span style="margin-left:30px;">
											Ret. Processed By:
										</span>
										<span style="margin-left:25px;">
											Date Closed:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="datereceivedown" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datereceivedown']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedbyreturnown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnown']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="dateclosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Additional Invoice Message:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<textarea name="invoicemessageown" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $loanArr['invoicemessageown']; ?></textarea>
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
										<button name="formsubmit" type="submit" value="Save Outgoing" />Save</button>
									</div>
							</fieldset>
						</form>
						<?php
						//}
						?>
					</div>
					<div id="addspecdiv">
						<div style="float:right;margin:10px;">
							<a href="#" onclick="toggle('newspecdiv')">
								<img src="../../images/add.png" alt="Create New Loan" />
							</a>
						</div>
						<div id="newspecdiv" style="display:none;">
							<form name="addspecform" action="loans.php" method="post">
								<fieldset>
									<legend><b>Add Specimen</b></legend>
									<div style="padding-bottom:2px;">
										<span>
											<b>Catalog Number: </b> 
											<input type="text" name="catalognumber" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
										</span>
									</div>
									<div style="padding-top:8px;">
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
							<h3>Specimens on Loan</h3>
							<div style="margin-top:15px;">
								<span style="float:left;margin-left:15px;">
									<input name="" value="" type="checkbox" onclick="selectAll(this);" />
									Select/Deselect All
								</span>
								<span style="float:right;margin-right:15px;">
									<button name="formsubmit" type="submit" value="Refresh" />Refresh List</button>
								</span>
				        	</div>
							<table class="styledtable">
							<th style="width:25px;text-align:center;"> </th>
							<th style="width:150px;text-align:center;">Catalog Number</th>
							<th style="width:400px;text-align:center;">Scientific Name</th>
						<?php
							foreach($specList as $k => $specArr){
								echo '<tr>';
								echo '<td>';
								echo '<input name="occid[]" type="checkbox" value=" " />';
								echo '</td>';
								echo '<td>'.$specArr['catalognumber'].'</td>';
								echo '<td>'.$specArr['sciname'].'</td>';
								echo '</tr>';
							}
							echo '</table>';
						}
						else{
							echo '<div style="font-weight:bold;font-size:120%;">There are no specimens registered for this loan.</div>';
						}
						?>
						
						<table>
						<tr><td colspan="10" valign="bottom">
													<div style="margin:10px;">
														<div style="float:left;">
															<input name="applytask" type="radio" value="apply" CHECKED title="Apply Edits, if not already done" />Apply Edits<br/>
															<input name="applytask" type="radio" value="revert" title="Revert Edits" />Revert Edits
														</div>
														<div style="margin-left:30px;float:left;">
															Review Status:
															<select name="rstatus">
																<option value="0">LEAVE AS IS</option>
																<option value="1">OPEN</option>
																<option value="2">PENDING</option>
																<option value="3">CLOSED</option>
															</select>
														</span>
														<span style="margin-left:25px;">
															<input name="submitstr" type="submit" value="Perform Action" />
															<input name="collid" type="hidden" value="<?php/* echo $collId; */?>" />
															<input name="fastatus" type="hidden" value="<?php/* echo $faStatus; */?>" />
															<input name="frstatus" type="hidden" value="<?php/* echo $frStatus; */?>" />
															<input name="download" type="hidden" value="" />
														</span>
													</div>
													<hr/>
													<div>
														<b>Additional Actions:</b>
													</div>
													<div style="margin:5px 0px 10px 15px;">
														<a href="editreviewer.php?collid=<?php/* echo $collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=export'; */?>">
															Download Records
														</a>
													</div>
													<div style="margin:10px 0px 5px 15px;">
														<a href="editreviewer.php?collid=<?php/* echo $collId.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&mode=printmode'; */?>">
															Display as Printable Form
														</a>
													</div>
												</td></tr>
												</table
					</div>
				</div>
				<?php 
			}
			elseif($loanType == 'In'){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="#loandiv">Loan Details</a></li>
					</ul>
					<div id="loandiv">
						<?php 
						//Show loan details
						$loanArr = $loanManager->getLoanInDetails($loanId);
						$specTotal = $loanManager->getSpecTotal($loanId);
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
											Date Received:
										</span>
										<span style="margin-left:30px;">
											Date Due:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<b>Loan Number:</b> <input type="text" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($loanArr['loanidentifierborr']?$loanArr['loanidentifierborr']:$loanArr['loanidentifierown']); ?>" />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="createdbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo ($loanArr['createdbyborr']?$loanArr['createdbyborr']:$paramsArr['un']); ?>" onchange=" " disabled />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyborr']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="datereceivedborr" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datereceivedborr']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="datedue" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datedue']; ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Sent From:
										</span>
										<span style="margin-left:430px;">
											Sender's Loan Number:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<select name="iidowner" style="width:400px;" disabled >
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$loanArr['iidowner']?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
										</span>
										<span style="margin-left:90px;">
											<input type="text" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" <?php echo ($loanArr['collidown']?'disabled':''); ?> />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Requested for:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="forwhom" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<b>Specimen Total:</b> <input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php if($loanArr['collidown']){echo ($specTotal?$specTotal['speccount']:0) ;}else{echo $loanArr['numspecimens'] ;} ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
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
											<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" " <?php echo ($loanArr['collidown']?'disabled="disabled"':''); ?> ><?php echo $loanArr['description']; ?></textarea>
										</span>
										<span style="margin-left:40px;">
											<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" " <?php echo ($loanArr['collidown']?'disabled="disabled"':''); ?> ><?php echo $loanArr['notes']; ?></textarea>
										</span>
									</div>
									<hr />
									<div style="padding-top:4px;">
										<span>
											Date Returned:
										</span>
										<span style="margin-left:30px;">
											Ret. Processed By:
										</span>
										<span style="margin-left:30px;">
											# of Boxes:
										</span>
										<span style="margin-left:25px;">
											Shipping Service:
										</span>
										<span style="margin-left:115px;">
											Date Closed:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<input type="text" name="datesentreturn" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['datesentreturn']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="processedbyreturnborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnborr']; ?>" onchange=" " />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="totalboxesreturned" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxesreturned']; ?>" onchange=" " />
										</span>
										<span style="margin-left:30px;">
											<input type="text" name="shippingmethodreturn" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethodreturn']; ?>" onchange=" " />
										</span>
										<span style="margin-left:25px;">
											<input type="text" name="dateclosed" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $loanArr['dateclosed']; ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
										</span>
									</div>
									<div style="padding-top:4px;">
										<span>
											Additional Invoice Message:
										</span>
									</div>
									<div style="padding-bottom:2px;">
										<span>
											<textarea name="invoicemessageborr" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $loanArr['invoicemessageborr']; ?></textarea>
										</span>
									</div>
									<div style="padding-top:8px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="collidborr" type="hidden" value="<?php echo $collId; ?>" />
										<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
										<button name="formsubmit" type="submit" value="Save Incoming" />Save</button>
									</div>
							</fieldset>
						</form>
						<?php
						//}
						?>
					</div>
				</div>
			<?php 
			}
			elseif($loanType == 'Exchange'){
				?>
				<div id="tabs" style="margin:0px;">
				    <ul>
						<li><a href="#exchangedetaildiv">Exchange Details</a></li>
					</ul>
					<div id="exchangedetaildiv" style="">
						<?php 
							//Show loan details
							$exchangeArr = $loanManager->getExchangeDetails($exchangeId);
							//$specTotal = $loanManager->getSpecTotal($loanId);
						?>
						<form name="editexchangegiftform" action="loans.php" method="post">
								<fieldset>
									<legend>Edit Gift/Exchange</legend>
									<div style="padding-top:4px;">
											<span style="margin-left:290px;">
												Entered By:
											</span>
											<span style="margin-left:80px;">
												Date Shipped:
											</span>
											<span style="margin-left:50px;">
												Date Received:
											</span>
										</div>
										<div style="padding-bottom:2px;">
											<span>
												<b>Transaction Number:</b> <input type="text" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['identifier']; ?>" disabled />
											</span>
											<span style="margin-left:40px;">
												<input type="text" name="createdby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['createdby']; ?>" onchange=" " disabled />
											</span>
											<span style="margin-left:40px;">
												<input type="text" name="datesent" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['datesent']; ?>" onchange=" " />
											</span>
											<span style="margin-left:40px;">
												<input type="text" name="datereceived" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['datereceived']; ?>" onchange=" " />
											</span>
										</div>
										<div style="padding-top:4px;">
											<span>
												Institution:
											</span>
											<span style="margin-left:385px;">
												Transaction Type:
											</span>
											<span style="margin-left:45px;">
												In/Out:
											</span>
										</div>
										<div style="padding-bottom:2px;">
											<span>
												<select name="iid" style="width:400px;" >
													<?php 
													$instArr = $loanManager->getInstitutionArr();
													foreach($instArr as $k => $v){
														echo '<option value="'.$k.'" '.($k==$exchangeArr['iid']?'SELECTED':'').'>'.$v.'</option>';
													}
													?>
												</select>
											</span>
											<span style="margin-left:40px;">
												<select name="transactiontype" style="width:100px;" >
													<option value="Shipment" <?php echo ('Shipment'==$exchangeArr['transactiontype']?'SELECTED':'');?>>Shipment</option>
													<option value="Adjustment" <?php echo ('Adjustment'==$exchangeArr['transactiontype']?'SELECTED':'');?>>Adjustment</option>
												</select>
											</span>
											<span style="margin-left:40px;">
												<select name="in_out" style="width:100px;" >
													<option value="" <?php echo (!$exchangeArr['in_out']?'SELECTED':'');?>>   </option>
													<option value="In" <?php echo ('In'==$exchangeArr['in_out']?'SELECTED':'');?>>In</option>
													<option value="Out" <?php echo ('Out'==$exchangeArr['in_out']?'SELECTED':'');?>>Out</option>
												</select>
											</span>
										</div>
										<div style="padding-top:8px;padding-bottom:8px;">
											<table class="styledtable">
												<th style="width:220px;text-align:center;">Balance Adjustment</th>
												<th style="width:220px;text-align:center;">Gift Specimens</th>
												<th style="width:220px;text-align:center;">Exchange Specimens</th>
												<tr style="text-align:right;">
													<td><b>Adjustment Amount:</b>&nbsp;&nbsp;<input type="text" name="adjustment" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['adjustment']; ?>" onchange=" " /></td>
													<td><b>Total Gifts:</b>&nbsp;&nbsp;<input type="text" name="totalgift" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalgift']; ?>" onchange=" " /></td>
													<td><b>Total Unmounted:</b>&nbsp;&nbsp;<input type="text" name="totalexunmounted" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalexunmounted']; ?>" onchange=" " /></td>
												</tr>
												<tr style="text-align:right;">
													<td> </td>
													<td><b>Total Gifts For Det:</b>&nbsp;&nbsp;<input type="text" name="totalgiftdet" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalgiftdet']; ?>" onchange=" " /></td>
													<td><b>Total Mounted:</b>&nbsp;&nbsp;<input type="text" name="totalexmounted" tabindex="100" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalexmounted']; ?>" onchange=" " /></td>
												</tr>
												<tr style="text-align:right;">
													<td> </td>
													<td> </td>
													<td><b>Exchange Value:</b>&nbsp;&nbsp;<input type="text" name="exchangevalue" tabindex="100" maxlength="32" style="width:80px;" value="" onchange=" " disabled="disabled" /></td>
												</tr>
												<tr style="text-align:right;">
													<td colspan="3"><b>Total Specimens (gifts + exchanges):</b>&nbsp;&nbsp;<input type="text" name="totalspecimens" tabindex="100" maxlength="32" style="width:80px;" value="" onchange=" " disabled="disabled" /></td>
												</tr>
											</table>	
										</div>
										<div style="padding-top:4px;">
											<span style="margin-left:350px;">
												# of Boxes:
											</span>
											<span style="margin-left:55px;">
												Shipping Service:
											</span>
										</div>
										<div style="padding-bottom:2px;">
											<span style="margin-left:25px;">
												<b>Current Balance:</b> <input type="text" name="invoicebalance" tabindex="100" maxlength="32" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['invoicebalance']; ?>" onchange=" " disabled />
											</span>
											<span style="margin-left:100px;">
												<input type="text" name="totalboxes" tabindex="100" maxlength="32" style="width:50px;" value="<?php echo $exchangeArr['totalboxes']; ?>" onchange=" " />
											</span>
											<span style="margin-left:60px;">
												<input type="text" name="shippingmethod" tabindex="100" maxlength="32" style="width:180px;" value="<?php echo $exchangeArr['shippingmethod']; ?>" onchange=" " />
											</span>
										</div>
										<div style="padding-top:4px;">
											<span>
												Description:
											</span>
											<span style="margin-left:300px;">
												Notes:
											</span>
										</div>
										<div style="padding-bottom:2px;">
											<span>
												<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $exchangeArr['description']; ?></textarea>
											</span>
											<span style="margin-left:40px;">
												<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $exchangeArr['notes']; ?></textarea>
											</span>
										</div>
										<hr />
										<div style="padding-top:4px;">
											<span>
												Additional Message:
											</span>
										</div>
										<div style="padding-bottom:2px;">
											<span>
												<textarea name="invoicemessage" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $exchangeArr['invoicemessage']; ?></textarea>
											</span>
										</div>
										<div style="padding-top:8px;">
											<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
											<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
											<button name="formsubmit" type="submit" value="Save Exchange" />Save</button>
										</div>
								</fieldset>
							</form>
					
					<!-- END OF EXCHANGE -->
					</div>
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