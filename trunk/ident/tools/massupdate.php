<?php
error_reporting(E_ALL);
//set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT']."" );
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/KeyMassUpdateManager.php');
header("Content-Type: text/html; charset=".$charset);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">
 <head>
  <title><?php echo $defaultTitle; ?> Character Mass Updater</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script language="JavaScript">
		
		var addStr = ";";
		var removeStr = ";";
		
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
			var sform = document.getElementById("submitform");
			var a;
			var r;
			
			if(addStr.length > 1){
				var addAttrs = addStr.split(";");
				for(a in addAttrs)
				{
					var addValue = addAttrs[a];
					if(addValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","a[]");
						newInput.setAttribute("value",addValue);
						sform.appendChild(newInput);
					}
				}
			}
	
			if(removeStr.length > 1){
				var removeAttrs = removeStr.split(";");
				for(r in removeAttrs)
				{
					var removeValue = removeAttrs[r];
					if(removeValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","r[]");
						newInput.setAttribute("value",removeValue);
						sform.appendChild(newInput);
					}
				}
			}
			sform.submit();
		}
			
	</script>
</head>
<body>

<?php
/*
 * Created on Jul 9, 2006
 *
 * By E.E. Gilbert
 */

 	
 	$editable = false;
 	if($isAdmin || array_key_exists("KeyEditor",$userRights)){
 		$editable = true;
 	}
 	 	
 	$muManager = new KeyMassUpdateManager();

	$removeAttrs = Array();
	$addAttrs = Array();

	 $action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
	 $clFilter = array_key_exists("clf",$_REQUEST)?$_REQUEST["clf"]:""; 
	 $taxonFilter = array_key_exists("tf",$_REQUEST)?$_REQUEST["tf"]:""; 
	 $generaOnly = array_key_exists("generaonly",$_REQUEST)?$_REQUEST["generaonly"]:""; 
	 $cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:""; 
	 $removeAttrs = array_key_exists("r",$_REQUEST)?$_REQUEST["r"]:""; 
	 $addAttrs = array_key_exists("a",$_REQUEST)?$_REQUEST["a"]:""; 
	 $projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
	 $langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 
	 
	if($projValue) $muManager->setProj($projValue);
	if($langValue) $muManager->setLang($langValue);
	if($clFilter) $muManager->setClFilter($clFilter);
	if($taxonFilter) $muManager->setTaxonFilter($taxonFilter);
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

	$displayLeftMenu = (isset($ident_tools_massupdateMenu)?$ident_tools_massupdateMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($ident_tools_massupdateCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $ident_tools_massupdateCrumbs;
		echo "<b>Character Mass Update Editor</b>";
		echo "</div>";
	}
	
?>
	<!-- This is inner text! -->
	<div id="innertext">
<?php 	
	if($editable){
		?>
		<table height='500' border='0'>
			<tr>
				<td width='200' valign='top'>
		  			<form id="setupform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
						<div style='font-weight:bold;'>Checklist:</div>
	  					<select name="clf"> 
							<option value='all'>Select a Checklist</option>
					 		<?php 
							echo "<option value='all' ".($clFilter=="all"?"SELECTED":"").">Checklist Filter Off (all taxa)</option>\n";
					 		$selectList = $muManager->getClQueryList();
					 			foreach($selectList as $key => $value){
					 				echo "<option value='".$key."' ".($key==$clFilter?"SELECTED":"").">$value</option>\n";
					 			}
					 		?>
	  					</select>
	  					<div style='font-weight:bold;'>Taxon:</div>
						<select name="tf">
					  		<?php 
				 				echo "<option value='0'>-- Select a Family or Genus --</option>\n";
				 				echo "<option value='0'>--------------------------</option>\n";
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
							<input type='submit' name='action' id='list' value='Submit Criteria' />
						</div>
	 					<hr size="2" />
				 		<?php 
				 			if($clFilter && $taxonFilter){
				 				$cList = $muManager->getCharList();			//Array(Heading => Array(CID => CharName))
								foreach($cList as $h => $charData){
									echo "<div style='margin-top:1em;font-size:125%;'>$h</div>\n";
									ksort($charData);
									foreach($charData as $cidKey => $charValue){
										echo "<div> <input name='cid' type='radio' value='".$cidKey."' ".($cidKey == $cidValue?"checked":"").">$charValue</div>\n";
									}
								}
				 				echo "<input type='submit' name='action' id='list' value='Submit Criteria'>\n";
				 			}
							if($projValue) echo "<input type='hidden' name='proj' value='".$projValue."' />\n";
							if($langValue) echo "<input type='hidden' name='lang' value='".$langValue."' />\n";
				 			
				 		?>
					</form>
		     	</td>
			  	<td width="20" background="../../images/brown_hor_strip.gif">
			  	</td>
		     	<td valign="top">
		     	<?php
		     	$inheritStr = "<span title='State Inherited from parent taxon'> (I)</span>";
		     	if($clFilter && $taxonFilter && $cidValue){
		     		?>
		     		<table border='1'>
		     		<?php 
		     		$sList = $muManager->getStates();
		     		$tList = $muManager->getTaxaList();				//Array(familyName => Array(SciName => Array("TID" => TIDvalue,"csArray" => Array(csValues => Inheritance))))
						//List CharState columns and replace spaces with line breaks
		     		echo "<tr><td/>";
		     		foreach ($sList as $cs => $csName){
							$csNameNew = str_replace(" ","<br/>",$csName);
		     			$sList[$cs] = $csName;
		     			echo "<td align='center' width='50px'>$csNameNew</td>\n";
		     		}
						echo "</tr>\n";
						$count = 0;
						ksort($tList);
		     		foreach($tList as $fam => $sciNameArr){
							//Show Family first
		     			if(array_key_exists($fam,$sciNameArr)){
		      			$famArr = $sciNameArr[$fam];
								echo "<tr><td><span style='margin-left:1px'><a href='editor.php?taxon=".$fam."&action=Get+Character+Info' target='_blank'>$fam</a></span></td>\n";
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
										$jsStr = "javascript: removeAttr('".$t."-".$cs."');";
									}
									else{
										//State is false for this taxon or it is inherited
										$jsStr = "javascript: addAttr('".$t."-".$cs."');";	
									}
									echo "<td align='center' width='15'><input type=\"checkbox\" name=\"csDisplay\" onclick=\"".$jsStr."\" ".($isSelected && !$isInherited?"CHECKED":"")." title=\"".$csName."\"/>".($isInherited?"(I)":"")."</td>\n";
								}
								echo "</tr>\n";
								unset($sciNameArr[$fam]);
		     			}
		
							//Go through taxa names and list
							ksort($sciNameArr);
							foreach($sciNameArr as $sciName => $sciArr){
								$display = $sciArr["display"];
								echo "<tr><td><a href='editor.php?taxon=".$sciName."&action=Get+Character+Info' target='_blank'>$display</a></td>\n";
								$t = $sciArr["TID"];
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
										$jsStr = "javascript: removeAttr('".$t."-".$cs."');";
									}
									else{
										//State is false for this taxon or it is inherited
										$jsStr = "javascript: addAttr('".$t."-".$cs."');";	
									}
									echo "<td width='10' align='center'><div ".($isSelected?"style='text-weight:bold;'":"")."><input type=\"checkbox\" name=\"csDisplay\" onclick=\"".$jsStr."\" ".($isSelected && !$isInherited?"CHECKED":"")." title=\"".$csName."\"/>".($isInherited?$inheritStr:"")."</div></td>\n";
								}
								echo "</tr>\n";
		
								//Occationally show column names and submit button
								$count++;
								if($count%13 == 0){
				      		echo "<tr><td align='right' colspan='".(count($sList)+1)."'><input type='submit' name='action' value='Save Changes' onclick='javascript: submitAttrs();'></td></tr>\n";
								}
							}
							echo "<tr><td align='right' colspan='".(count($sList)+1)."'><input type='submit' name='action' value='Save Changes' onclick='javascript: submitAttrs();'></td></tr>\n";
		     		}
					?>
		  		</table>
		  	</td>
		   </tr>
			<tr>
				<td colspan="3">
				<?php
		     	}
		     	else if($clFilter && $taxonFilter){
		     		echo "<h3>Select a morphological character and click 'Set Species List' button</h3>";
		     	}
		     	else{
		     		echo "<h3 sytle='margin-left:20px;'>Select a checklist, family or genus, and click 'Display Characters' button</h3>";
		     	}
				?>
				</td>
			</tr>
		 </table>
		 <form id="submitform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<?php
				if($clFilter) echo "<input type='hidden' name='clf' value='".$clFilter."' />\n";
				if($taxonFilter) echo "<input type='hidden' name='tf' value='".$taxonFilter."' />\n";
				if($cidValue) echo "<input type='hidden' name='cid' value='".$cidValue."' />\n";
				if($projValue) echo "<input type='hidden' name='proj' value='".$projValue."' />\n";
				if($langValue) echo "<input type='hidden' name='lang' value='".$langValue."' />\n";
			?>
		</form>
		<?php 
 }
 else{  //Not editable or writable connection is not set
	echo "<h1>You do not have authority to edit character data.</h1> <h3>You must first login to the system.</h3>";
 }
?>
	</div>
<?php  
 include($serverRoot.'/footer.php');
 
?>
</body>
</html>

	