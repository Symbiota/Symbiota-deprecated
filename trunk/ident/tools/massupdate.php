<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/KeyMassUpdate.php');
header("Content-Type: text/html; charset=".$charset);

$isEditor = false;
if($isAdmin || array_key_exists("KeyEditor",$userRights)){
	$isEditor = true;
}

$muManager = new KeyMassUpdate();

$removeAttrs = Array();
$addAttrs = Array();

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$clFilter = array_key_exists("clf",$_REQUEST)?$_REQUEST["clf"]:'all'; 
$taxonFilter = array_key_exists("tf",$_REQUEST)?$_REQUEST["tf"]:'all';
$generaOnly = array_key_exists("generaonly",$_REQUEST)?$_REQUEST["generaonly"]:""; 
$cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:'';
$removeAttrs = array_key_exists("r",$_REQUEST)?$_REQUEST["r"]:""; 
$addAttrs = array_key_exists("a",$_REQUEST)?$_REQUEST["a"]:""; 
$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 

$muManager->setProj($projValue);
if($langValue) $muManager->setLang($langValue);
$muManager->setClFilter($clFilter);
$muManager->setTaxonFilter($taxonFilter);
if($generaOnly) $muManager->setGeneraOnly($generaOnly);
if($cidValue) $muManager->setCid($cidValue);

//Set username
 if(array_key_exists("un",$paramsArr)) $muManager->setUsername($paramsArr["un"]);

if($addAttrs || $removeAttrs){
	if($removeAttrs) $muManager->setRemoves($removeAttrs);
	if($addAttrs) $muManager->setAdds($addAttrs);
	$muManager->deleteInheritance();
	$muManager->processAttrs();
	$muManager->resetInheritance();
}

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Character Mass Updater</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script language="JavaScript">
	
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
include($serverRoot.'/header.php');
?>
<div class='navpath'>
	<a href='../../index.php'>Home</a> 
	<?php 
	if($cidValue){
		?>
		&gt;&gt;
		<a href='massupdate.php?clf=<?php echo $clFilter.'&tf='.$taxonFilter.'&lang='.$langValue; ?>'>
			<b>Return to Character List</b>
		</a>
		<?php 
	}
	?>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if($isEditor){
		if(!$cidValue){
			?>
			<table>
				<tr><td>
					<form id="setupform1" action="massupdate.php" method="post">
				  		<fieldset style="padding:15px;">
				  			<legend><b>Step 1: Limit Scope</b></legend>
							<div style='font-weight:bold;'>Checklist:</div>
							<select name="clf"> 
								<option value="">-- Select a Checklist --</option>
								<option value="all" <?php echo ($clFilter=="all"?"SELECTED":""); ?>>Checklist Filter Off (all taxa)</option>
								<option value="">---------------------------------</option>
						 		<?php 
						 		$selectList = $muManager->getClQueryList();
					 			foreach($selectList as $key => $value){
					 				echo "<option value='".$key."' ".($key==$clFilter?"SELECTED":"").">$value</option>\n";
					 			}
						 		?>
					  		</select>
					  		<div style='font-weight:bold;'>Taxon:</div>
							<select name="tf">
					 			<option value="">-- Select Taxon --</option>
					 			<option value="all" <?php if($taxonFilter == 'all') echo 'SELECTED'; ?>>All Taxa</option>
					 			<option value="">--------------------------</option>
						  		<?php 
						  		$selectList = $muManager->getTaxaQueryList();
					  			foreach($selectList as $value){
					  				echo "<option ".($value==$taxonFilter?"SELECTED":"").">$value</option>\n";
					  			}
						  		?>
							</select>
							<div>
								<input type="checkbox" name="generaonly" value="1" <?php if($generaOnly) echo "checked"; ?> /> 
								Exclude Species Rank
							</div>
							<div>
								<input type='submit' name='action' id='list' value='Display Character List' />
							</div>
						</fieldset>
					</form>
				</td>
				<td>
					<form id="setupform2" name="setupform2" action="massupdate.php" method="post">
						<fieldset style="padding:0px 15px 15px 15px;">
							<legend><b>Step 2: Select Character</b></legend>
					 		<?php 
			 				$cList = $muManager->getCharList();			//Array(Heading => Array(CID => CharName))
							foreach($cList as $h => $charData){
								echo "<div style='margin-top:1em;font-size:125%;'>$h</div>\n";
								ksort($charData);
								foreach($charData as $cidKey => $charValue){
									echo '<div> <input name="cid" type="radio" value="'.$cidKey.'" onclick="this.form.submit()">'.$charValue.'</div>'."\n";
								}
							}
					 		?>
							<input type="hidden" name="proj" value="<?php echo $projValue; ?>" />
							<input type="hidden" name="lang" value="<?php echo $langValue; ?>" />
					 		<input type="hidden" name="clf" value="<?php echo $clFilter; ?>" />
					 		<input type="hidden" name="tf" value="<?php echo $taxonFilter; ?>" />
					 	</fieldset>
					</form>
			 	</td></tr>
			</table>
		<?php
		}
		else{
			$inheritStr = "<span title='State Inherited from parent taxon'> (I)</span>";
			?>
	     	<table class="styledtable">
	     		<?php 
	     		$sList = $muManager->getStates();
				//List CharState columns and replace spaces with line breaks
				$headerStr = '<tr><th/>';
     		
	     		foreach($sList as $cs => $csName){
					$csNameNew = str_replace(" ","<br/>",$csName);
	     			$sList[$cs] = $csName;
	     			$headerStr .= '<th>'.$csNameNew.'</th>';
	     		}
	     		$headerStr .= '</tr>'."\n";
	     		echo $headerStr;
	     		?>
				<tr>
					<td align='right' colspan='<?php echo (count($sList)+1); ?>'>
						<input type='submit' name='action' value='Save Changes' onclick='submitAttrs();' />
					</td>
				</tr>
	     		<?php 
	     		$count = 0;
	     		//Array(familyName => Array(SciName => Array("TID" => TIDvalue,"csArray" => Array(csValues => Inheritance))))
	     		$tList = $muManager->getTaxaList();
				ksort($tList);
	     		foreach($tList as $fam => $sciNameArr){
					//Show Family first
	     			if(array_key_exists($fam,$sciNameArr)){
	      				$famArr = $sciNameArr[$fam];
	      				?>
						<tr>
							<td>
								<span style='font-weight:bold;'>
									<a href="massupdate.php?clf=<?php echo $clFilter.'&tf='.$fam.'&cid='.$cidValue.'&proj='.$projValue.'&lang='.$langValue; ?>">
										<?php echo $fam; ?>
									</a>
									<a href='editor.php?taxon=<?php echo $fam; ?>&action=Get+Character+Info' target='_blank'>
										<img src="../../images/edit.png" />
									</a>
								</span>
							</td>
							<?php 
							$t = $famArr["TID"];
							$csValues = $famArr["csArray"];
							foreach($sList as $cs => $csName){
								$isSelected = false;
								$isInherited = false;
								if(array_key_exists($cs,$csValues)){
									$isSelected = true;
									if($csValues[$cs]) $isInherited = true;
								}
								if($isSelected && !$isInherited){
									//State is true and not inherited for this taxon
									$jsStr = "removeAttr('".$t."-".$cs."');";
								}
								else{
									//State is false for this taxon or it is inherited
									$jsStr = "addAttr('".$t."-".$cs."');";	
								}
								echo "<td align='center' width='15'>";
								echo "<input type=\"checkbox\" name=\"csDisplay\" onclick=\"".$jsStr."\" ".($isSelected && !$isInherited?"CHECKED":"")." title=\"".$csName."\"/>".($isInherited?"(I)":"");
								echo "</td>\n";
							}
							?>
						</tr>
						<?php 
						unset($sciNameArr[$fam]);
	     			}

					//Go through taxa names and list
					ksort($sciNameArr);
					foreach($sciNameArr as $sciName => $sciArr){
						$display = $sciArr["display"];
						$t = $sciArr["TID"];
						?>
						<tr>
							<td>
								<?php echo $display; ?> 
								<a href='editor.php?taxon=<?php echo $sciName; ?>&action=Get+Character+Info' target='_blank'>
									<img src="../../images/edit.png" />
								</a>
							</td>
							<?php 
							$csValues = $sciArr["csArray"];
							foreach($sList as $cs => $csName){
								$isSelected = false;
								$isInherited = false;
								if(array_key_exists($cs,$csValues)){
									$isSelected = true;
									if($csValues[$cs]) $isInherited = true;
								}
								if($isSelected && !$isInherited){
									//State is true and not inherited for this taxon
									$jsStr = "removeAttr('".$t."-".$cs."');";
								}
								else{
									//State is false for this taxon or it is inherited
									$jsStr = "addAttr('".$t."-".$cs."');";
								}
								?>
								<td width='10' align='center'>
									<div <?php echo ($isSelected?"style='text-weight:bold;'":"")?>>
										<input type="checkbox" name="csDisplay" onclick="<?php echo $jsStr.'" '.($isSelected && !$isInherited?'CHECKED':''); ?> title="<?php echo $csName; ?>" />
										<?php echo ($isInherited?$inheritStr:""); ?>
									</div>
								</td>
								<?php 
							}
							?>
						</tr>
						<?php 
						//Occationally show column names and submit button
						$count++;
						if($count%13 == 0){
							?>
							<tr>
								<td align='right' colspan='<?php echo (count($sList)+1); ?>'>
									<input type='submit' name='action' value='Save Changes' onclick='submitAttrs();' />
								</td>
							</tr>
							<?php 
	     					echo $headerStr;
						}
					}
	     		}
				?>
				<tr>
					<td align='right' colspan='<?php echo (count($sList)+1); ?>'>
						<input type='submit' name='action' value='Save Changes' onclick='submitAttrs();' />
					</td>
				</tr>
			</table>
			<form name="submitform" action="massupdate.php" method="post">
				<input type='hidden' name='clf' value='<?php echo $clFilter; ?>' />
				<input type='hidden' name='tf' value='<?php echo $taxonFilter; ?>' />
				<input type='hidden' name='cid' value='<?php echo $cidValue; ?>' />
				<input type='hidden' name='proj' value='<?php echo $projValue; ?>' />
				<input type='hidden' name='lang' value='<?php echo $langValue; ?>' />
			</form>
			<?php
     	}
	}
	elseif(!$symbUid){
		?>
		<div style="font-weight:bold;font-size:120%;margin:30px;">
			Please 
			<a href="../../profile/index.php?refurl=<?php echo $clientRoot; ?>/ident/tools/massupdate.php">
				LOGIN
			</a> 
		</div>
		<?php 
	}
	else{  //Not editable or writable connection is not set
		echo "<h1>You appear not to have necessary premissions to edit character data.</h1>";
	}
	?>
</div>
<?php  
include($serverRoot.'/footer.php');
 
?>
</body>
</html>

	