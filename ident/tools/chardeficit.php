<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/KeyCharDeficitManager.php');
header("Content-Type: text/html; charset=".$charset);
 
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 
$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:""; 
$cfValue = array_key_exists("cf",$_REQUEST)?$_REQUEST["cf"]:""; 
$cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:"";
  
$cdManager = new KeyCharDeficitManager();
if($langValue) $cdManager->setLanguage($langValue);
if($projValue) $cdManager->setProject($projValue);
$editable = false;
if($isAdmin || array_key_exists("KeyEditor",$userRights) || array_key_exists("KeyAdmin",$userRights)){
	$editable = true;
}
?>

<html>
<head>
	<title><?php echo $defaultTitle; ?> Character Deficit Finder</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function openPopup(urlStr,windowName){
			var wWidth = 900;
			try{
				if(document.getElementById('maintable').offsetWidth){
					wWidth = document.getElementById('maintable').offsetWidth*1.05;
				}
				else if(document.body.offsetWidth){
					wWidth = document.body.offsetWidth*0.9;
				}
			}
			catch(e){
			}
			newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($ident_tools_chardeficitMenu)?$ident_tools_chardeficitMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($ident_tools_chardeficitCrumbs)){
		echo "<div class='navpath'>";
		echo $ident_tools_chardeficitCrumbs;
		echo "<b>Character Deficit Editor</b>";
		echo "</div>";
	}
?>
	<!-- This is inner text! -->
	<div id="innertext">
  		<form action="chardeficit.php" method="get">
<?php 
 	if($editable){
?>
		<table width="700" border="0">
    <tr>
      <td width="200" valign="top">
			<div style='margin-top:1em;font-weight:bold;'>Checklist:</div>
		  	<select name="cl"> 
		  		<?php 
		  			$selectList = Array();
		  			$selectList = $cdManager->getClQueryList();
		  			echo "<option>--Select a Checklist--</option>";
		  			foreach($selectList as $key => $value){
		  				$selectStr = $key==$clValue?"SELECTED":"";
		  				echo "<option value='".$key."' $selectStr>$value</option>";
		  			}
		  		?>
		  	</select>
		  	<br/>
			<div style='margin-top:1em;font-weight:bold;'>Filter Character List:</div>
				<select name="cf">
		  		<?php 
		  			$selectList = Array();
		  			$selectList = $cdManager->getTaxaQueryList();
		  			echo "<option>--Select a Taxon--</option>";
		  			foreach($selectList as $key => $value){
		  				$selectStr = $key==$cfValue?"SELECTED":"";
		  				echo "<option value='".$key."' $selectStr>$value</option>\n";
		  			}
		  		?>
		  	</select><br/>
				<div style='margin-top:1em;'><input type='submit' name='action' id='submit' value='Get Characters' /></div>
	
	  		<hr size="2"/>
			<input type='submit' name='action' id='submit' value='Get Species List'/>
			<div style="margin:10px 0px 10px 0px;height:250px; width:190; overflow : auto;border:black solid 1px;">
	  		<?php
				if($cfValue != "--Select a Taxon--"){
	  				if($action=="Get Characters" || $action=="Get Species List"){
			  			$cList = $cdManager->getCharList($cfValue, $cidValue);
			  			foreach($cList as $value){
			  				echo $value."\n";
			  			}
		  			}
		  			else{
		  				echo "<h2>Character List Empty</h2>";
		  			}
				}
				else{
	  				echo "<h2>Select as Taxon</h2>";
				}
	  		?>
			</div>
			<input type='submit' name='action' id='submit' value='Get Species List' />
      </td>
			<td width='20' background='../../images/brown_hor_strip.gif'></td>
      <td valign="top">
      	<?php
	      	if($action=="Get Species List" && $cfValue != "--Select a Taxon--"){
	      		$tList = $cdManager->getTaxaList($cidValue, $cfValue, $clValue);								//family => Array(tid => sciname)
	      		if($tList){
					echo "<h3>Species Count: ".$cdManager->getTaxaCount()."</h3>\n";
	      			foreach($tList as $f=>$sArr){
	      				echo "<div style='margin-top:1em;font-size:125%;'>$f</div>\n";
	      				foreach($sArr as $idValue => $spValue){
	      					echo "<div style=''>&nbsp;&nbsp;<a href='editor.php?tid=".$idValue."&lang=English&lang=English' target='_blank'>$spValue</a> ";
	      					echo "(<a href=\"#\" onclick=\"openPopup('editor.php?tid=".$idValue."&char=".$cidValue."','technical');\">@</a>)</div>\n";
	      				}
	      			}
	      		}
	      		else{
	      			echo "<h2>No taxa were returned.</h2>";
	      		}
	      	}
      		else{
      			echo "<h2>List Empty.</h2>";
      		}
				?>
	  	</td>
    </tr>
  </table>
  </form>
<?php
 }
 else{  //Not editable
	echo "<h1>You do not have authority to edit character data or there is a problem with the connection.</h1> <h3>You must first login to the system.</h3>";
 }
 ?>
</div>
<?php include($serverRoot.'/footer.php'); ?>
  </body>
</html>
