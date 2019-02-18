<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecLoans.php');
include_once($SERVER_ROOT.'/content/lang/collections/loans/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$collId = $_REQUEST['collid'];
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$displayAll = array_key_exists('displayall',$_POST)?$_POST['displayall']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$eMode = array_key_exists('emode',$_REQUEST)?$_REQUEST['emode']:0;

$isEditor = 0;
if($SYMB_UID && $collId){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))
		|| (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"]))){
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
		elseif($formSubmit == 'Add New Determinations'){
			include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
			$occManager = new OccurrenceEditorDeterminations();
			$occidArr = $_REQUEST['occid'];
			foreach($occidArr as $k){
				$occManager->setOccId($k);
				$occManager->addDetermination($_REQUEST,$isEditor);
			}
		}
	}
}

$loanOutList = $loanManager->getLoanOutList($searchTerm,$displayAll);
$loansOnWay = $loanManager->getLoanOnWayList();
$loanInList = $loanManager->getLoanInList($searchTerm,$displayAll);
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['LOAN_MANAGEMENT']; ?></title>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
    <link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" rel="stylesheet" type="text/css" />
	<link href="../../css/jquery-ui.css" rel="Stylesheet" type="text/css" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../../js/symb/collections.loans.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_loans_indexMenu)?$collections_loans_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($collections_loans_indexCrumbs) && $collections_loans_indexCrumbs){
		?>
		<div class='navpath'>
			<a href='../../index.php'><?php echo $LANG['HOME'];?></a> &gt;&gt; 
			<?php echo $collections_loans_indexCrumbs; ?>
			<a href='index.php?collid=<?php echo $collId; ?>'> <b><?php echo $LANG['LOAN'];?></b></a>
		</div>
		<?php 
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'><?php echo $LANG['HOME'];?></a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1"><?php echo $LANG['COLL'];?></a> &gt;&gt;
			<a href='index.php?collid=<?php echo $collId; ?>'> <b><?php echo $LANG['LOAN'];?></b></a>
		</div>
		<?php 
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($SYMB_UID && $isEditor && $collId){
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
						<li><a href="#loanoutdiv"><span><?php echo $LANG['OUT'];?></span></a></li>
						<li><a href="#loanindiv"><span><?php echo $LANG['INCO'];?></span></a></li>
						<li><a href="exchange.php?collid=<?php echo $collId; ?>"><span><?php echo $LANG['GIFT'];?></span></a></li>
					</ul>
					<div id="loanoutdiv" style="">
						<div style="float:right;">
							<form name='optionform' action='index.php' method='post'>
								<fieldset>
									<legend><b><?php echo $LANG['OPTION'];?></b></legend>
									<div>
										<b><?php echo $LANG['SEARCH'];?> </b>
										<input type="text" autocomplete="off" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
									</div>
									<div>
										<input type="radio" name="displayall" value="0"<?php echo ($displayAll==0?'checked':'');?> /> <?php echo $LANG['DISPLAY'];?>
									</div>
									<div>
										<input type="radio" name="displayall" value="1"<?php echo ($displayAll?'checked':'');?> /> <?php echo $LANG['DIS'];?>
									</div>
									<div style="float:right;">
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input type="hidden" name="formsubmit" value="Refresh List" />
										<input type="submit" value="<?php echo $LANG['REFRESH_LIST']; ?>" />
									</div>
								</fieldset>
							</form>	
						</div>
						<?php
						if($loanOutList){
							?>
							<div id="loanoutToggle" style="float:right;margin:10px;">
								<a href="#" onclick="displayNewLoanOut();">
									<img src="../../images/add.png" alt="<?php echo $LANG['CREATE_NEW_LOAN']; ?>" />
								</a>
							</div>
							<?php
						}
						?>
						<div id="newloanoutdiv" style="display:<?php echo ($loanOutList?'none':'block'); ?>;">
							<form name="newloanoutform" action="index.php" method="post" onsubmit="return verfifyLoanOutAddForm(this);">
								<fieldset>
									<legend><b><?php echo $LANG['NEW'];?></b></legend>
									<div style="padding-top:4px;float:left;">
										<span>
											<?php echo $LANG['ENTER'];?>
										</span><br />
										<span>
											<input type="text" autocomplete="off" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:15px;float:right;">
										<span>
											<b><?php echo $LANG['LOAN_IDEN'];?> </b><input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
										</span>
									</div>
									<div style="clear:both;padding-top:6px;float:left;">
										<span>
											<?php echo $LANG['SEND'];?>
										</span><br />
										<span>
											<select name="reqinstitution" style="width:400px;">
												<option value=""><?php echo $LANG['SELEC'];?></option>
												<option value="">------------------------------------------</option>
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'">'.$v.'</option>';
												}
												?>
											</select>
										</span>
										<span>
											<a href="../admin/institutioneditor.php?emode=1" target="_blank" title="<?php echo $LANG['CREATE_NEW_LOAN']; ?>">
												<img src="../../images/add.png" style="width:15px;" />
											</a>
										</span>
									</div>
									<div style="clear:both;padding-top:8px;float:right;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<button name="formsubmit" type="submit" value="Create Loan Out"><?php echo $LANG['CREA'];?></button>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
						if(!$loanOutList){
							echo '<script type="text/javascript">displayNewLoanOut();</script>';
						}
						?>
						<div>
							<?php 
							if($loanOutList){
								echo '<h3>'.$LANG['OUTGOING_LOAN_RECORDS'].'</h3>';
								echo '<ul>';
								foreach($loanOutList as $k => $loanArr){
									echo '<li>';
									echo '<a href="index.php?collid='.$collId.'&loanid='.$k.'&loantype=out">';
									echo $loanArr['loanidentifierown'];
									echo '</a>: '.$loanArr['institutioncode'].' ('.$loanArr['forwhom'].')';
									echo ' - '.($loanArr['dateclosed']?'Closed: '.$loanArr['dateclosed']:'<b>OPEN</b>');
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo '<div style="font-weight:bold;font-size:120%;margin-top:10px;">'.$LANG['THERE_ARE_NO_LOANS_OUT_REGISTERED_FOR_THIS_COLLECTION'].'</div>';
							}
							?>
						</div>
						<div style="clear:both;">&nbsp;</div>
					</div>
					<div id="loanindiv" style="">
						<div style="float:right;">
							<form name='optionform' action='index.php' method='post'>
								<fieldset>
									<legend><b><?php echo $LANG['OP'];?></b></legend>
									<div>
										<b><?php echo $LANG['SEAR'];?> </b><input type="text" autocomplete="off" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
									</div>
									<div>
										<input type="radio" name="displayall" value="0"<?php echo ($displayAll==0?'checked':'');?> /> <?php echo $LANG['DI'];?>
									</div>
									<div>
										<input type="radio" name="displayall" value="1"<?php echo ($displayAll?'checked':'');?> /> <?php echo $LANG['DIS_ALL'];?>
									</div>
									<div style="float:right;">
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input type="hidden" name="formsubmit" value="Refresh List" />
										<input type="submit" name="formsubmit" value="<?php echo $LANG['REFRESH_LIST']; ?>" />
									</div>
								</fieldset>
							</form>	
						</div>
						<?php
						if($loansOnWay || $loanInList){
							?>
							<div id="loaninToggle" style="float:right;margin:10px;">
								<a href="#" onclick="displayNewLoanIn();">
									<img src="../../images/add.png" alt="<?php echo $LANG['CREATE_NEW_LOAN']; ?>" />
								</a>
							</div>
							<?php
						}
						?>
						<div id="newloanindiv" style="display:<?php echo (($loansOnWay || $loanInList)?'none':'block'); ?>;">
							<form name="newloaninform" action="index.php" method="post" onsubmit="return verifyLoanInAddForm(this);">
								<fieldset>
									<legend><b><?php echo $LANG['NEW_INCOM'];?></b></legend>
									<div style="padding-top:4px;float:left;">
										<span>
											<?php echo $LANG['ENTER_BY'];?>
										</span><br />
										<span>
											<input type="text" autocomplete="off" name="createdbyborr" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
										</span>
									</div>
									<div style="padding-top:15px;float:right;">
										<span>
											<b><?php echo $LANG['LOAN_INDENTIFIER'];?> </b>
											<input type="text" autocomplete="off" id="loanidentifierborr" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" onchange="inIdentCheck(loanidentifierborr,<?php echo $collId; ?>);" />
										</span>
									</div>
									<div style="clear:both;padding-top:6px;float:left;">
										<span>
											<?php echo $LANG['SENT'];?> 
										</span><br />
										<span>
											<select name="iidowner" style="width:400px;">
												<option value="0"><?php echo $LANG['SELECT_INST'];?></option>
												<option value="0">------------------------------------------</option>
												<?php 
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="'.$k.'">'.$v.'</option>';
												}
												?>
											</select>
										</span>
										<span>
											<a href="../admin/institutioneditor.php?emode=1" target="_blank" title="<?php echo $LANG['ADD_A_NEW_INSTITUTION']; ?>">
												<img src="../../images/add.png" style="width:15px;" />
											</a>
										</span>
									</div>
									<div style="clear:both;padding-top:8px;float:right;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<button name="formsubmit" type="submit" value="Create Loan In"><?php echo $LANG['CREA']; ?></button>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
						if(!$loansOnWay && !$loanInList){
							echo '<script type="text/javascript">displayNewLoanIn();</script>';
						}
						?>
						<div>
							<?php 
							if($loansOnWay){
								echo '<h3>'.$LANG['LOANDS_ON_THEIR_WAY'].'</h3>';
								echo '<ul>';
								foreach($loansOnWay as $k => $loanArr){
									echo '<li>';
									echo '<a href="index.php?collid='.$collId.'&loanid='.$k.'&loantype=in">';
									echo $loanArr['loanidentifierown'];
									echo ' from '.$loanArr['collectionname'].'</a>';
									echo '</li>';
								}
								echo '</ul>';
							}
							?>
						</div>
						<div>
							<?php 
							echo '<h3>'.$LANG['INCO'].'</h3>';
							echo '<ul>';
							if($loanInList){
								foreach($loanInList as $k => $loanArr){
									echo '<li>';
									echo '<a href="index.php?collid='.$collId.'&loanid='.$k.'&loantype=in">';
									echo $loanArr['loanidentifierborr'];
									echo '</a>: '.$loanArr['institutioncode'].' ('.$loanArr['forwhom'].')';
									echo ' - '.($loanArr['dateclosed']?'Closed: '.$loanArr['dateclosed']:'<b>'.$LANG['OPEN'].'</b>');
									echo '</li>';
								}
							}
							else{
								echo '<li>'.$LANG['THERE_ARE_NO_LOANS_RECEIVED'].'</li>';
							}
							echo '</ul>';
							?>
						</div>
						<div style="clear:both;">&nbsp;</div>
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
				if(!$SYMB_UID){
					echo '<h2>'.$LANG['PLEASE'].' <a href="'.$CLIENT_ROOT.'/profile/index.php?collid='.$collId.'&refurl='.$CLIENT_ROOT.'/collections/loans/index.php?collid='.$collId.'">'.$LANG['LOGIN'].'</a></h2>';
				}
				elseif(!$collId){
					echo '<h2>'.$LANG['COLLECTION_NOT_DEFINED'].'</h2>';
				}
				elseif(!$isEditor){
					echo '<h2>'.$LANG['YOU_ARE_NOT_AUTHORIZED_TO_MANAGE_LOANS'].'</h2>';
				}
			}
		}
		else{
			if(!$SYMB_UID){
				echo 'Please <a href="../../profile/index.php?refurl=../collections/loans/index.php?collid='.$collId.'">login</a>';
			}
			elseif(!$isEditor){
				echo '<h2>'.$LANG['YOU_ARE_NOT_AUTHORIZED_TO_ADD_OCCURRENCE_RECORDS'].'</h2>';
			}
			else{
				echo '<h2>'.$LANG['ERROR_UNKNOWN_ERROR_PLEASE_CONTACT_SYSTEM_ADMINISTRATOR'].'</h2>';
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT."/footer.php");
	?>
</body>
</html>