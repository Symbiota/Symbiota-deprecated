<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceSkeletalSubmit.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/skeletalsubmit.php?'.$_SERVER['QUERY_STRING']);

$collid  = $_REQUEST["collid"];
$action = array_key_exists("formaction",$_REQUEST)?$_REQUEST["formaction"]:"";

$skeletalManager = new OccurrenceSkeletalSubmit();
if($collid){
	$skeletalManager->setCollid($collid);
	$collMap = $skeletalManager->getCollectionMap();
}

$statusStr = '';
$isEditor = 0;
if($collid){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor'])){
		$isEditor = 1;
	}
}
if($isEditor){
	if($action == ''){

	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Skeletal Record Submission</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />	
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.occurskeletalsubmit.js?ver=150324" type="text/javascript"></script>
	<script src="../../js/symb/shared.js?ver=150324" type="text/javascript"></script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
		<b>Occurrence Skeletal Record Submission</b>
	</div>
	<!-- inner text -->
	<div id="innertext">
		<h1><?php echo $collMap['collectionname']; ?></h1>
		<?php 
		if($statusStr){
			echo '<div style="margin:15px;color:red;">'.$statusStr.'</div>';
		}
		if($isEditor){
			?>
			<fieldset style="padding:0px 15px 15px 15px;position:relative;">
				<legend>
					<b>Skeletal Data</b> 
					<img src="../../images/list.png" style="width:12px;" onclick="toggle('optiondiv')" title="Display Options" />
				</legend>
				<div id="optiondiv" style="display:none;position:absolute;background-color:white;">
					<fieldset>
						<legend><b>Display Options</b></legend>
						<input type="checkbox" onclick="toggle('authordiv')" CHECKED /> Author<br/> 
						<input type="checkbox" onclick="toggle('familydiv')" CHECKED /> Family<br/> 
						<input type="checkbox" onclick="toggle('localitysecuritydiv')" CHECKED /> Locality Security<br/> 
						<input type="checkbox" onclick="toggle('countrydiv')" /> Country<br/>
						<input type="checkbox" onclick="toggle('statediv')" CHECKED /> State / Province<br/>
						<input type="checkbox" onclick="toggle('countydiv')" CHECKED /> County / Parish<br/>
						<input type="checkbox" onclick="toggle('processingstatusdiv')" /> Processing Status<br/>
						<input type="checkbox" onclick="toggle('recordedbydiv')" /> Collector<br/>
						<input type="checkbox" onclick="toggle('recordnumberdiv')" /> Collector Number<br/>
						<input type="checkbox" onclick="toggle('eventdatediv')" /> Collection Date<br/>
						<input type="checkbox" onclick="toggle('languagediv')" /> Language<br/>
					</fieldset> 
				</div>
				<div style="position:absolute;background-color:white;top:10px;right:10px;">
					Session: <label id="minutes">00</label>:<label id="seconds">00</label><br/>
					Count: <label id="count">0</label><br/>
					Rate: <label id="rate">0</label> per hour
				</div>
				<div>
					<form id="defaultform" name="defaultform" action="skeletalsubmit.php" method="post" autocomplete="off" onsubmit="return submitDefaultForm(this)">
						<div style="">
							<div id="scinamediv" style="float:left"> 
								<b>Scientific Name:</b> 
								<input id="fsciname" name="sciname" type="text" value="" style="width:300px"/>
								<input id="ftidinterpreted" name="tidinterpreted" type="hidden" value="" />
							</div>
							<div id="authordiv" style="float:left"> 
								<input id="fscientificnameauthorship" name="scientificnameauthorship" type="text" value="" />
							</div>
							<div style="clear:both;">
								<div id="familydiv" style="float:left">
									<b>Family:</b> <input id="ffamily" name="family" type="text" tabindex="0" value="" />
								</div>
								<div id="localitysecuritydiv" style="float:left">
									<input id="flocalitysecurity" name="localitysecurity" type="checkbox" tabindex="0" value="1" />
									Protect locality details from general public
								</div>
							</div>
						</div>
						<div style="clear:both;padding-top:5px"> 
							<div id="countrydiv" style="display:none;float:left;margin:3px 3px 3px 0px;">
								<b>Country:</b><br/> 
								<input id="fcountry" name="country" type="text" value="" autocomplete="off" />
							</div> 
							<div id="statediv" style="float:left;margin:3px 3px 3px 0px;">
								<b>State/Province:</b><br/>
								<input id="fstateprovince" name="stateprovince" type="text" value="" autocomplete="off" onchange="localitySecurityCheck(this.form)" />
							</div> 
							<div id="countydiv" style="float:left;margin:3px 3px 3px 0px;">
								<b>County/Parish:</b><br/>
								<input id="fcounty" name="county" type="text" autocomplete="off" value="" />
							</div> 
							<div id="processingstatusdiv" style="display:none;float:left;margin:3px 3px 3px 0px">
								<b>Processing Status:</b><br/>
								<select id="fprocessingstatus" name="processingstatus">
									<option>unprocessed</option>
									<option>stage 1</option>
									<option>stage 2</option>
									<option>stage 3</option>
									<option>expert required</option>
									<option>pending review</option>
									<option>reviewed</option>
									<option>closed</option>
								</select>
							</div> 
						</div>
						<div style="clear:both;padding-top:5px">
							<div id="recordedbydiv" style="display:none;float:left;margin:3px 3px 3px 0px;">
								<b>Collector:</b><br/> 
								<input id="frecordedby" name="recordedby" type="text" value="" />
							</div> 
							<div id="recordnumberdiv" style="display:none;float:left;margin:3px 3px 3px 0px;">
								<b>Collector Number:</b><br/> 
								<input id="frecordnumber" name="recordnumber" type="text" value="" />
							</div> 
							<div id="eventdatediv" style="display:none;float:left;margin:3px 3px 3px 0px;">
								<b>Date:</b><br/> 
								<input id="feventdate" name="eventdate" type="text" value="" onchange="eventDateChanged(this)" />
							</div> 
							<div id="languagediv" style="display:none;float:left;margin:3px 3px 3px 0px;">
								<b>Language:</b><br/> 
								<select id="flanguage" name="language">
									<?php 
									$langArr = $skeletalManager->getLanguageArr();
									foreach($langArr as $code => $langStr){
										echo '<option value="'.$code.'" '.($code == 'en'?'selected':'').'>'.$langStr.'</option>';
									}
									?>
								</select>
							</div> 
						</div> 
						<div style="clear:both;padding:15px 0px 0px 20px;">
							<div style="float:right;">
								<input name="clearform" type="reset" value="Clear Form" style="margin-right:40px" />
							</div>
							<b>Catalog Number:</b>
							<input id="fcatalognumber" name="catalognumber" type="text" style="border-color:green;" />
							<input id="fcollid" name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="recordsubmit" type="submit" value="Add Record" />
						</div> 
					</form>
				</div>
			</fieldset>
			<fieldset style="padding:15px;">
				<legend><b>Records</b></legend>
				<div id="occurlistdiv"></div>
			</fieldset>
			<?php 
		}
		else{
			if($collid){
				echo 'You are not authorized to acces this page.<br/>';
				echo 'Contact an administrator to obtain the necessary permissions.</b> ';
			}
			else{
				echo 'ERROR: collection identifier not set';
			}
		}
		?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>
</body>
</html>