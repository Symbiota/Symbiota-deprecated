<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ReferenceManager.php');

$refId = array_key_exists('refid',$_REQUEST)?$_REQUEST['refid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$refManager = new ReferenceManager();
$refArr = '';
$refExist = false;

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'search references'){
		$refArr = $refManager->getRefList($_POST['searchtitlekeyword'],$_POST['searchauthor']);
		foreach($refArr as $refName => $valueArr){
			if($valueArr["title"]){
				$refExist = true;
			}
		}
	}
	if($formSubmit == 'Delete Reference'){
		$statusStr = $refManager->deleteReference($refId);
	}
}

header("Content-Type: text/html; charset=".$charset);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title><?php echo $defaultTitle; ?> Reference Management</title>
    <link href="../css/base.css" rel="stylesheet" type="text/css" />
    <link href="../css/main.css" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/references.index.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($reference_indexMenu)?$reference_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($reference_indexCrumbs)){
		if($reference_indexCrumbs){
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt;&gt; 
				<?php echo $reference_indexCrumbs; ?>
				<a href='index.php'> <b>Reference Management</b></a>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt; 
			<a href='index.php'> <b>Reference Management</b></a>
		</div>
		<?php 
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($symbUid){
			if($statusStr){
				?>
				<hr/>
				<div style="margin:15px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<?php 
			}
			?>
			<div style="margin:0px;">
				<div id="findrefdiv" style="min-height:200px;">
					<div style="float:right;margin:10px;">
						<a href="#" onclick="toggle('newreferencediv');">
							<img src="../images/add.png" alt="Create New Reference" />
						</a>
					</div>
					<div id="newreferencediv" style="display:none;">
						<form name="newreferenceform" action="refdetails.php" method="post" onsubmit="return verifyNewRefForm(this.form);">
							<fieldset>
								<legend><b>New Reference</b></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="">
										<b>Title: </b>
									</div>
									<div style="margin-left:35px;margin-top:-14px;">
										<textarea name="newreftitle" id="newreftitle" rows="10" style="width:380px;height:40px;resize:vertical;" ></textarea>
									</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<b>Reference Type: </b><select name="newreftype" id="newreftype" style="width:400px;">
											<option value="">Select Reference Type</option>
											<option value="">------------------------------------------</option>
											<?php 
											$typeArr = $refManager->getRefTypeArr();
											foreach($typeArr as $k => $v){
												echo '<option value="'.$k.'">'.$v.'</option>';
											}
											?>
										</select>
									</span>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<button name="formsubmit" type="submit" value="Create Reference">Create Reference</button>
								</div>
							</fieldset>
						</form>
					</div>
					<div id="searchreferencediv" style="">
						<form name="searchrefform" action="index.php" method="post" onsubmit="return verifySearchRefForm(this.form);">
							<fieldset>
								<legend><b>Search References</b></legend>
								<div style="padding-top:4px;float:left;">
									<span>
										<b>Title Keyword: </b><input type="text" autocomplete="off" name="searchtitlekeyword" id="searchtitlekeyword" style="width:250px;" value="<?php echo ($formSubmit == 'search references'?$_POST['searchtitlekeyword']:''); ?>" />
									</span>
								</div>
								<div style="clear:both;padding-top:15px;float:left;">
									<span>
										<b>Author's Last Name: </b><input type="text" name="searchauthor" id="searchauthor" style="width:250px;" value="<?php echo ($formSubmit == 'search references'?$_POST['searchauthor']:''); ?>" />
									</span>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<button name="formsubmit" type="submit" value="search references">Search References</button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if($_POST){
						if($refExist){
							echo '<div style="margin-top:10px;"><hr />';
							echo '<ul>';
							foreach($refArr as $refId => $recArr){
								echo '<li>';
								echo '<a href="refdetails.php?refid='.$refId.'"><b>'.$recArr["title"].'</b></a>';
								echo ($recArr["secondarytitle"]?', '.$recArr["secondarytitle"].'.':'.');
								echo ($recArr["pubdate"]?$recArr["pubdate"].'.':'');
								echo ($recArr["authline"]?$recArr["authline"].'.':'');
								echo '</li>';
							}
							echo '</ul></div>';
						}
						else{
							echo '<div style="margin-top:10px;"><hr /><div style="font-weight:bold;font-size:120%;">There were no references matching your criteria.</div></div>';
						}
					}
					?>
				</div>
			</div>
			<?php 
		}
		else{
			if(!$symbUid){
				echo 'Please <a href="../profile/index.php?refurl=../references/index.php">login</a>';
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