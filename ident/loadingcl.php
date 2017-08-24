<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
Header("Cache-Control: must-revalidate");
$offset = 60 * 60 * 24 * 7;
$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
Header($ExpStr);

 $symClid = 0;
 $attrsValues = Array();
 
 $clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:""; 
 $symClid = array_key_exists("symclid",$_REQUEST)?$_REQUEST["symclid"]:0; 
 $taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
 $action = array_key_exists("submit",$_REQUEST)?$_REQUEST["submit"]:""; 
 $rf = array_key_exists("rf",$_REQUEST)?$_REQUEST["rf"]:""; 
 $projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
 $defaultLang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 
 $displayMode = array_key_exists("displaymode",$_REQUEST)?$_REQUEST["displaymode"]:""; 
 $attrsValues = array_key_exists("attr",$_REQUEST)?$_REQUEST["attr"]:"";	//Array of: cid + "-" + cs (ie: 2-3) 
 
 $url = "key.php?cl=".$clValue;
 $url .= $taxonValue?"&taxon=".$taxonValue:"";
 $url .= $action?"&submit=".$action:"";
 $url .= $rf?"&rf=".$rf:"";
 $url .= $projValue?"&proj=".$projValue:"";
 $url .= $defaultLang?"&lang=".$defaultLang:"";
 if($attrsValues){
 	foreach($attrsValues as $v){
 		$url .= "&attr=".$v;
 	}
 }

 ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Symbiota: loading key</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<meta http-equiv="Refresh" content="0; url=<?php echo $url; ?>" />
</head>
<body>
	<?php
	$displayLeftMenu = (isset($ident_loadingclMenu)?$ident_loadingclMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($ident_loadingclCrumbs)) echo "<div class='navpath'>".$ident_loadingclCrumbs."</div>";
	
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<table cellspacing="0" width="853">
		    <tr>
		        <td rowspan="2" width="20" background="../images/brown_hor_strip.gif"></td>
		        <td rowspan="1" height="30" width="805">
					<?php if($symClid){ ?>
			            <a href='../index.php' class='navpath'>Home</a> &gt;
			            <a href='../collections/index.jsp' class='navpath'>Select Databases</a> &gt;
			            <a href='../collections/harvestparams.jsp' class='navpath'>Search Parameters</a> &gt;
			            <a href='../collections/checklist.jsp' class='navpath'>Checklist</a> 
					<?php }else{ ?>
			        	<a href='../index.php' class='navpath'>Home</a> &gt; 
			        	<a href='../ident/index.php' class='navpath'>Symbiota Intro</a> &gt; 
			        	<span class='navpath'>Symbiota Key</span>
					<?php } ?>
		        </td>
		        
		        <td rowspan="2" width="20" align="center" valign="top">
		            <!-- this is the line to the far right -->
		            <img src="../images/vert_strip_right.gif" />
		        </td>
		    </tr>
		    <tr>
		        <td rowspan="1" width="615">
					<h2>KEY IS BEING PRIMED WITH THE SPECIES LIST.</h2>
					<b>Please be patient. Large species lists may take up to a minute to load.</b>
				</td>
			</tr>
		</table>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>
  
  </body>
</html>
