<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/KeyMassUpdate.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/tools/massupdate.php?'.$_SERVER['QUERY_STRING']);

$clFilter = array_key_exists("clf",$_REQUEST)?$_REQUEST["clf"]:''; 
$taxonFilter = array_key_exists("tf",$_REQUEST)?$_REQUEST["tf"]:'';
$generaOnly = array_key_exists("generaonly",$_POST)?$_POST["generaonly"]:0; 
$cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:'';
$removeAttrs = array_key_exists("r",$_REQUEST)?$_REQUEST["r"]:""; 
$addAttrs = array_key_exists("a",$_REQUEST)?$_REQUEST["a"]:""; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:""; 
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 

$muManager = new KeyMassUpdate();
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
	<title><?php echo $defaultTitle; ?> Character Mass Updater</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
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

		function submitFilterForm(inputObj){
			var f = inputObj.form;
			if((f.clf.value == "") && (f.tf.value == "")){
				alert("Taxon OR checklist needs to have a defined scope ");
				inputObj.checked = false;
				return false;
			}
			else{
				f.submit();
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
			<form id="filterform" action="massupdate.php" method="post" onsubmit="return verifyFilterForm(this)">
		  		<fieldset style="padding:15px;">
		  			<legend><b>Define Group of Taxa to Edit</b></legend>
		  			<div style="margin: 10px 0px;">
		  				Define checklist and/or taxon scope and then select character to be edited. The action of selecting character will submit form.   
		  			</div>
		  			<div>
						<b>Checklist:</b> 
						<select name="clf"> 
							<option value="">Checklist Filter Off (all taxa)</option>
							<option value="">---------------------------------</option>
					 		<?php 
					 		$selectList = $muManager->getClQueryList();
				 			foreach($selectList as $key => $value){
				 				echo "<option value='".$key."' ".($key==$clFilter?"SELECTED":"").">$value</option>\n";
				 			}
					 		?>
				  		</select><br/>
				  		<b>Taxon:</b>
						<select name="tf">
				 			<option value="">All Taxa</option>
				 			<option value="">--------------------------</option>
					  		<?php 
					  		$selectList = $muManager->getTaxaQueryList();
				  			foreach($selectList as $tid => $scinameValue){
				  				echo '<option value="'.$tid.'" '.($tid==$taxonFilter?"SELECTED":"").'">'.$scinameValue."</option>\n";
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
							echo '<div> <input name="cid" type="radio" value="'.$cidKey.'" onclick="submitFilterForm(this)">'.$charValue.'</div>'."\n";
						}
					}
			 		?>
					<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
					<input type="hidden" name="lang" value="<?php echo $langValue; ?>" />
			 	</fieldset>
			</form>
			<?php
		}
		else{
			$inheritStr = "&nbsp;<span title='State has been inherited from parent taxon'><b>(i)</b></span>";
			?>
			<div><?php echo $inheritStr; ?> = character state is inherited as true from a parent taxon (genus, family, etc)</div>
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
		 		$tList = $muManager->getTaxaList($clFilter,$taxonFilter,$generaOnly);
		 		$csArr = array();
				if(isset($tList['cs'])){
					$csArr = $tList['cs'];
					unset($tList['cs']);
				}
				ksort($tList);
				foreach($tList as $fam => $sciNameArr){
					//Show Family first
					if(isset($sciNameArr[$fam])){
						$famTid = $sciNameArr[$fam];
						unset($sciNameArr[$fam]);
						?>
						<tr>
							<td>
								<span style='font-weight:bold;'>
									<a href="massupdate.php?clf=<?php echo $clFilter.'&tf='.$famTid.'&cid='.$cidValue.'&pid='.$pid.'&lang='.$langValue; ?>">
										<b><?php echo $fam; ?></b>
									</a>
									<a href='editor.php?tid=<?php echo $famTid; ?>' target='_blank'>
										<img src="../../images/edit.png" />
									</a>
								</span>
							</td>
							<?php 
							foreach($sList as $cs => $csName){
								$isSelected = false;
								$isInherited = false;
								if(isset($csArr[$famTid][$cs])){
									$isSelected = true;
									if($csArr[$famTid][$cs]) $isInherited = true;
								}
								if($isSelected && !$isInherited){
									//State is true and not inherited for this taxon
									$jsStr = "removeAttr('".$famTid."-".$cs."');";
								}
								else{
									//State is false for this taxon or it is inherited
									$jsStr = "addAttr('".$famTid."-".$cs."');";	
								}
								echo "<td align='center' width='15'>";
								echo '<input type="checkbox" name="csDisplay" onclick="'.$jsStr.'" '.($isSelected && !$isInherited?'CHECKED':'').' title="'.$csName.'"/>'.($isInherited?'(I)':'');
								echo "</td>\n";
							}
							?>
						</tr>
						<?php 
	
						//Go through taxa names and list
						ksort($sciNameArr);
						//$cnt = 1;
						foreach($sciNameArr as $sciName => $tid){
							$trClassStr = '';
							/*
							if(strpos($display,'10px')){
								if($cnt % 2) $trClassStr = 'class="alt"';
								$cnt++;
							}
							else{
								$cnt = 1;
							}
							*/
							?>
							<tr <?php echo $trClassStr; ?>>
								<td>
									<span style="margin-left:<?php echo (strpos($sciName,' ')?'20px':'10px;font-weight:bold;'); ?>;"><i><?php echo $sciName; ?></i></span> 
									<a href='editor.php?tid=<?php echo $tid; ?>' target='_blank'>
										<img src="../../images/edit.png" />
									</a>
								</td>
								<?php 
								foreach($sList as $cs => $csName){
									$isSelected = false;
									$isInherited = false;
									if(isset($csArr[$tid][$cs])){
										$isSelected = true;
										if($csArr[$tid][$cs]) $isInherited = true;
									}
									if($isSelected && !$isInherited){
										//State is true and not inherited for this taxon
										$jsStr = "removeAttr('".$tid."-".$cs."');";
									}
									else{
										//State is false for this taxon or it is inherited
										$jsStr = "addAttr('".$tid."-".$cs."');";
									}
									?>
									<td width='10' align='center' style="white-space:nowrap;">
										<?php 
										echo '<input type="checkbox" name="csDisplay" onclick="'.$jsStr.'" '.($isSelected && !$isInherited?'CHECKED':'').' title="'.$csName.'" />';
										if($isInherited) echo $inheritStr;
										?>
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
				<input type='hidden' name='pid' value='<?php echo $pid; ?>' />
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

	