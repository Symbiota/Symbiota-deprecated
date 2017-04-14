<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyMassUpdate.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/tools/massupdate.php?'.$_SERVER['QUERY_STRING']);

$clid = $_REQUEST['clid'];
$taxonFilter = array_key_exists("tf",$_REQUEST)?$_REQUEST["tf"]:'';
$generaOnly = array_key_exists("generaonly",$_POST)?$_POST["generaonly"]:0; 
$cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:'';
$removeAttrs = array_key_exists("r",$_REQUEST)?$_REQUEST["r"]:""; 
$addAttrs = array_key_exists("a",$_REQUEST)?$_REQUEST["a"]:""; 
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 

$muManager = new KeyMassUpdate();
$muManager->setClid($clid);
if($langValue) $muManager->setLang($langValue);
if($cidValue) $muManager->setCid($cidValue);

$isEditor = false;
if($isAdmin || array_key_exists("KeyEditor",$userRights) || array_key_exists("KeyAdmin",$userRights)){
	$isEditor = true;
}

if($isEditor){
	if($removeAttrs || $addAttrs){
		$muManager->processAttributes($removeAttrs,$addAttrs);
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Character Mass Updater</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script>
		var addStr = ";";
		var removeStr = ";";
		var dataChanged = false;
		
		window.onbeforeunload = verifyClose();

		function verifyClose() { 
			if(dataChanged == true) { 
				return "You will lose any unsaved data if you don't first save your changes!"; 
			}
		}

		function addAttr(target){
			var indexOfAdd = addStr.indexOf(";"+target+";");
			if(indexOfAdd == -1){
				addStr += target + ";";
			}
			else{
				removeAttr(target);
			}
		}
		
		function removeAttr(target){
			var indexOfRemove = removeStr.indexOf(";"+target+";");
			if(indexOfRemove == -1){
				removeStr += target + ";";
			}
			else{
				addAttr(target);
			}
		}
	
		function submitAttrs(){
			var sform = document.submitform;
			var a;
			var r;
			var submitForm = false;
			
			if(addStr.length > 1){
				var addAttrs = addStr.split(";");
				for(a in addAttrs){
					var addValue = addAttrs[a];
					if(addValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","a[]");
						newInput.setAttribute("value",addValue);
						sform.appendChild(newInput);
					}
				}
				submitForm = true;
			}
	
			if(removeStr.length > 1){
				var removeAttrs = removeStr.split(";");
				for(r in removeAttrs){
					var removeValue = removeAttrs[r];
					if(removeValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","r[]");
						newInput.setAttribute("value",removeValue);
						sform.appendChild(newInput);
					}
				}
				submitForm = true;
			}
			if(submitForm){
				sform.submit();
			}
			else{
				alert("It doesn't appear that any edits have been made");
			}
		}
	</script>
</head>
<body>
<?php 
$displayLeftMenu = false;
include($SERVER_ROOT.'/header.php');
?>
<div class='navpath'>
	<a href="../../index.php">Home</a> &gt;&gt;
	<a href="../../checklists/checklist.php?cl=<?php echo $clid; ?>">
		<b>Open Checklist</b>
	</a> &gt;&gt;
	<a href="../key.php?cl=<?php echo $clid; ?>&taxon=All+Species">
		<b>Open Key</b>
	</a>
	<?php 
	if($cidValue){
		?>
		&gt;&gt;
		<a href='massupdate.php?clid=<?php echo $clid.'&tf='.$taxonFilter.'&lang='.$langValue; ?>'>
			<b>Return to Character List</b>
		</a>
		<?php 
	}
	?>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if($clid && $isEditor){
		if(!$cidValue){
			?>
			<form id="filterform" action="massupdate.php" method="post" onsubmit="return verifyFilterForm(this)">
		  			<div style="margin: 10px 0px;">Select character to edit</div>
		  			<div>
						<select name="tf">
				 			<option value="">All Taxa</option>
				 			<option value="">--------------------------</option>
					  		<?php 
					  		$selectList = $muManager->getTaxaQueryList();
				  			foreach($selectList as $tid => $scinameValue){
				  				echo '<option value="'.$tid.'" '.($tid==$taxonFilter?"SELECTED":"").'>'.$scinameValue."</option>";
				  			}
					  		?>
						</select>
						<?php 
						count($selectList);
						?>
					</div>
					<div style="margin: 10px 0px;">
						<input type="checkbox" name="generaonly" value="1" <?php if($generaOnly) echo "checked"; ?> /> 
						Exclude Species Rank
					</div>
			 		<?php 
	 				$cList = $muManager->getCharList($taxonFilter);			//Array(Heading => Array(CID => CharName))
					foreach($cList as $h => $charData){
						echo "<div style='margin-top:1em;font-size:125%;font-weight:bold;'>$h</div>\n";
						ksort($charData);
						foreach($charData as $cidKey => $charValue){
							echo '<div> <input name="cid" type="radio" value="'.$cidKey.'" onclick="this.form.submit()">'.$charValue.'</div>'."\n";
						}
					}
			 		?>
					<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
					<input type="hidden" name="lang" value="<?php echo $langValue; ?>" />
			 	</fieldset>
			</form>
			<?php
		}
		else{
			$inheritStr = "&nbsp;<span title='State has been inherited from parent taxon'><b>(i)</b></span>";
			?>
			<div><?php echo $inheritStr; ?> = character state is inherited as true from a parent taxon (genus, family, etc)</div>
		 	<table class="styledtable" style="font-family:Arial;font-size:12px;">
				<?php 
				$muManager->echoTaxaList($taxonFilter,$generaOnly);
				?>
			</table>
			<form name="submitform" action="massupdate.php" method="post">
				<input type='hidden' name='tf' value='<?php echo $taxonFilter; ?>' />
				<input type='hidden' name='cid' value='<?php echo $cidValue; ?>' />
				<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
				<input type='hidden' name='lang' value='<?php echo $langValue; ?>' />
			</form>
			<?php
	 	}
	}
	else{  
		echo "<h1>You appear not to have necessary premissions to edit character data.</h1>";
	}
	?>
</div>
<?php  
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>

	