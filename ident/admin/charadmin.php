<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');
include_once($serverRoot.'/classes/KeyCharAdmin.php');

$keyManager = new KeyAdmin();
$keyManager->setCollId($collId);

$loanManager = new SpecLoans();

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$hidiid = array_key_exists('hidiid',$_REQUEST)?$_REQUEST['hidiid']:0;

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Save'){
		$statusStr = $instManager->saveInstitution($_POST);
	}
	elseif($formSubmit == 'Delete'){
		$statusStr = $instManager->deleteInstitution($hidiid);
	}
}
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title>Add New Institution</title>
    <link type="text/css" href="<?php echo $serverRoot;?>/css/main.css" rel="stylesheet" />
	<link type="text/css" href="<?php echo $serverRoot;?>/css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="<?php echo $serverRoot;?>/js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo $serverRoot;?>/js/jquery-ui.js"></script>
	<script type="text/javascript" src="<?php echo $serverRoot;?>/js/symb/ariz_collections.institutions.js"></script>
</head>
<body>
<?php
$displayLeftMenu = (isset($collections_loans_indexMenu)?$collections_loans_indexMenu:true);
include($serverRoot."/header.php");
?>
	<!-- This is inner text! -->
	<?php
	if($statusStr){
		?>
		<hr/>
		<div style="margin:15px;color:red;">
			<?php echo $statusStr; ?>
		</div>
		<hr/>
		<?php 
	}
	?>
	<div id="tabs" style="margin:0px;">
		<ul>
			<li><a href="#addeditinst"><span>Add/Edit Institution</span></a></li>
			<li><a href="#instdeldiv" onclick="instTransCheck(visibleiid);"><span>Admin</span></a></li>
		</ul>
		<div style="float:right;margin:10px;">
			<a href="#" onclick="displayNewLoanOut();">
				<img src="../../images/add.png" alt="Create New Loan" />
			</a>
		</div>
		<div id="newloanoutdiv" style="display:none;">
			<form name="newloanoutform" action="index.php" method="post" onsubmit="return verfifyLoanOutAddForm(this)">
				<fieldset>
					<legend><b>New Loan</b></legend>
					<div style="padding-top:4px;">
						<span>
							Entered By:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<input type="text" autocomplete="off" name="createdbyown" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $paramsArr['un']; ?>" onchange=" " />
						</span>
						<span style="float:right;">
							<b>Loan Identifier: </b><input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
						</span>
					</div>
					<div style="padding-top:4px;">
						<span>
							Send to Institution:
						</span>
					</div>
					<div style="padding-bottom:2px;">
						<span>
							<select name="reqinstitution" style="width:400px;">
								<option value="">Select Institution</option>
								<option value="">------------------------------------------</option>
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
						<button name="formsubmit" type="submit" value="Create Loan Out">Create Loan</button>
					</div>
				</fieldset>
			</form>
		</div>
		<div>
		<div>
			<?php 
			$charList = $keyManager->getCharList();
			if($charList){
				echo '<h3>Characters</h3>';
				echo '<ul>';
				foreach($charList as $k => $charArr){
					echo '<li>';
					echo '<a href="csadmin.php?cid='.$k.'">';
					echo $charArr['charname'];
					echo '</a>';
					echo '</li>';
				}
				echo '</ul>';
			}
			else{
				echo '<div style="font-weight:bold;font-size:120%;">There are no existing characters</div>';
			}
			?>
		</div>
		<div id="instdeldiv">
			<form name="delinstform" action="ariz_addinstitution.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this institution?')">
				<fieldset style="width:350px;margin:20px;padding:20px;">
					<legend><b>Delete Institution</b></legend>
					<input id="hiddeniid" name="hidiid" type="hidden" value="" />
					<input name="formsubmit" type="submit" value="Delete" />
				</fieldset>
			</form>
		</div>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>

