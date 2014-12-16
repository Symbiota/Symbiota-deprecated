<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GlossaryManager.php');
header("Content-Type: text/html; charset=".$charset);

$glossId = array_key_exists('glossid',$_REQUEST)?$_REQUEST['glossid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$glosManager = new GlossaryManager();
$termList = '';

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Search Terms'){
		$termList = $glosManager->getTermList($_POST['searchtermkeyword'],$_POST['searchdefkeyword'],$_POST['searchlanguage']);
	}
	if($formSubmit == 'Delete Term'){
		$statusStr = $glosManager->deleteTerm($glossId);
		$glossId = 0;
	}
}
if(!$formSubmit || $formSubmit != 'Search Terms'){
	$termList = $glosManager->getTermList('','',$defaultLang);
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title><?php echo $defaultTitle; ?> Glossary</title>
    <link href="../css/base.css" rel="stylesheet" type="text/css" />
    <link href="../css/main.css" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($glossary_indexMenu)?$glossary_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($glossary_indexCrumbs)){
		if($glossary_indexCrumbs){
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt;&gt; 
				<?php echo $glossary_indexCrumbs; ?>
				<a href='index.php'> <b>Glossary Management</b></a>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt; 
			<a href='index.php'> <b>Glossary Management</b></a>
		</div>
		<?php 
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($statusStr){
			?>
			<hr/>
			<div style="margin:15px;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<?php 
		}
		?>
		<div id="" style="float:right;width:240px;">
			<form name="filtertermform" action="index.php" method="post">
				<fieldset style="background-color:#FFD700;">
					<legend><b>Filter List</b></legend>
					<div>
						<div>
							<b>Term Keyword:</b> 
							<input type="text" autocomplete="off" name="searchtermkeyword" id="searchtermkeyword" size="25" value="<?php echo ($formSubmit == 'Search Terms'?$_POST['searchtermkeyword']:''); ?>" />
						</div>
						<div style="margin-top:8px;">
							<b>Definition Keyword:</b> 
							<input type="text" autocomplete="off" name="searchdefkeyword" id="searchdefkeyword" size="25" value="<?php echo ($formSubmit == 'Search Terms'?$_POST['searchdefkeyword']:''); ?>" />
						</div>
						<div style="margin-top:8px;">
							<b>Language:</b><br />
							<select name="searchlanguage" id="searchlanguage" style="margin-top:2px;" onchange="">
								<option value="">Select Language</option>
								<option value="">----------------</option>
								<?php 
								$langArr = $glosManager->getLanguageArr();
								foreach($langArr as $k => $v){
									if($formSubmit == 'Search Terms'){
										echo '<option value="'.$k.'" '.($k==$_POST['searchlanguage']?'SELECTED':'').'>'.$k.'</option>';
									}
									else{
										echo '<option value="'.$k.'" '.($k==$defaultLang?'SELECTED':'').'>'.$k.'</option>';
									}
								}
								?>
							</select>
						</div>
						<div style="padding-top:8px;float:right;">
							<button name="formsubmit" type="submit" value="Search Terms">Filter List</button>
						</div>
					</div>
				</fieldset>
			</form>
		</div>
		<div id="termlistdiv" style="min-height:200px;">
			<?php
			if($symbUid){
				?>
				<div style="float:right;margin:10px;">
					<a href="#" onclick="toggle('newtermdiv');">
						<img src="../images/add.png" alt="Create New Term" />
					</a>
				</div>
				<div id="newtermdiv" style="display:none;margin-bottom:10px;">
					<form name="termeditform" action="termdetails.php" method="post" onsubmit="return verifyNewTermForm(this.form);">
						<fieldset>
							<legend><b>Add New Term</b></legend>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Term: </b>
								</div>
								<div style="margin-left:40px;margin-top:-14px;">
									<input type="text" name="term" id="term" maxlength="45" style="width:200px;" value="" onchange="verifyNewTerm(this.form);" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Definition: </b>
								</div>
								<div style="margin-left:65px;margin-top:-14px;">
									<textarea name="definition" id="definition" rows="10" style="width:380px;height:70px;resize:vertical;" ></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Language: </b>
								</div>
								<div style="margin-left:65px;margin-top:-14px;">
									<input type="text" name="language" id="language" maxlength="45" style="width:200px;" value="" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:8px;float:right;">
								<button name="formsubmit" type="submit" value="Create Term">Create Term</button>
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			if($termList){
				echo '<div style="font-weight:bold;font-size:120%;">Terms</div>';
				echo '<div><ul>';
				foreach($termList as $termId => $terArr){
					echo '<li>';
					echo '<a href="#" onclick="openTermPopup('.$termId.'); return false;"><b>'.$terArr["term"].'</b></a>';
					echo '</li>';
				}
				echo '</ul></div>';
			}
			elseif($formSubmit == 'Search Terms'){
				echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There were no terms matching your criteria.</div></div>';
			}
			else{
				echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are currently no terms in the database.</div></div>';
			}
			?>
		</div>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>