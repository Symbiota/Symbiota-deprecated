<?php
	include_once('../../config/symbini.php');
	include_once($serverRoot.'/classes/InventoryDynSqlManager.php');
	header("Content-Type: text/html; charset=".$charset);
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
	$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
	$sqlFrag = array_key_exists("sqlfrag",$_REQUEST)?$_REQUEST["sqlfrag"]:"";
	
	$dynSqlManager = new InventoryDynSqlManager($clid);
	$isEditable = false;
	$statusStr = "";
	if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid(),$userRights["ClAdmin"]))){
		$isEditable = true;
		
		//Submit checklist MetaData edits
		if($action == "Save SQL Fragment"){
	 		$statusStr = $dynSqlManager->saveSql();
	 	}
	 	elseif($action == "Test SQL Fragment"){
	 		if($dynSqlManager->testSql($sqlFrag)){
	 			$statusStr = "SQL fragment valid";
	 		}
	 		else{
	 			$statusStr = "ERROR: SQL fragment failed";
	 		}
	 	}
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<title><?php echo $defaultTitle; ?> Flora Linkage Builder </title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script language=javascript>
		function updateSql(){
			country = document.getElementById("countryinput").value;
			state = document.getElementById("stateinput").value;
			county = document.getElementById("countyinput").value;
			locality = document.getElementById("localityinput").value;
			latNorth = document.getElementById("latnorthinput").value;
			lngWest = document.getElementById("lngwestinput").value;
			lngEast = document.getElementById("lngeastinput").value;
			latSouth = document.getElementById("latsouthinput").value;
			sqlFragStr = "";
			if(country){
				sqlFragStr = "AND (o.country = \"" + country + "\") ";
			}
			if(state){
				sqlFragStr = sqlFragStr + "AND (o.stateprovince = \"" + state + "\") ";
			}
			if(county){
				sqlFragStr = sqlFragStr + "AND (o.county LIKE \"%" + county + "%\") ";
			}
			if(locality){
				sqlFragStr = sqlFragStr + "AND (o.locality LIKE \"%" + locality + "%\"') ";
			}
			if(latNorth && latSouth){
				sqlFragStr = sqlFragStr + "AND (o.decimallatitude BETWEEN " + latSouth + " AND " + latNorth + ") ";
			}
			if(lngWest && lngEast){
				sqlFragStr = sqlFragStr + "AND (o.decimallongitude BETWEEN " + lngWest + " AND " + lngEast + ") ";
			}
			document.getElementById("sqlfrag").value = sqlFragStr.substring(4);
		}

		function buildSql(){
			updateSql();
			return false;
		}
	</script>
</head>

<body>
<?php
	$displayLeftMenu = (isset($checklists_tools_floradynsqlMenu)?$checklists_tools_floradynsqlMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($checklists_tools_floradynsqlCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_tools_floradynsqlCrumbs;
		echo " <b>".$dynSqlManager->getClName()."</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php
		if($clid  && $isEditable){ ?>
			<h1><?php echo $dynSqlManager->getClName(); ?></h1>
			<?php if($statusStr){ ?>
			<div style="margin:20px;font-weight:bold;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<?php } ?>
		<div>
			
		</div>
			<div style="margin:10px 0px 15px 0px;">
				This editing module will aid you in building an SQL fragment that will be used to help link vouchers to species names within the checklist. 
				When a dynamic SQL fragment exists, the checklist editors will have access to 
				editing tools that will dynamically query occurrence records matching the criteria within the SQL statement. 
				Editors can then go through the list and select the records that are to serve as specimen vouchers for that checklist.
				See the Flora Voucher Mapping Tutorial for more details. 
			</div>
			<div style="margin-top:10px;">
				<fieldset>
					<legend><b>Current Dynamic SQL Fragment</b></legend>
					<?php echo $dynSqlManager->getDynamicSql()?$dynSqlManager->getDynamicSql():"SQL not yet set"?>
				</fieldset>
			</div>
			<form name="sqlbuilder" action="" onsubmit="return buildSql();" style="margin-bottom:15px;">
				<fieldset style="padding:15px;">
					<legend><b>SQL Fragment Builder</b></legend>
					<div>
						Use this form to aid in building the SQL fragment. 
						Clicking the 'Build SQL' button will build the SQL using the terms 
						supplied and place it in the form near the bottom of the page. 
					</div>
					<div>
						<b>Country:</b>
						<input id="countryinput" type="text" name="country" onchange="" />
					</div>
					<div>
						<b>State:</b>
						<input id="stateinput" type="text" name="state" onchange="" />
					</div>
					<div>
						<b>County:</b>
						<input id="countyinput" type="text" name="county" onchange="" />
					</div>
					<div>
						<b>Locality:</b>
						<input id="localityinput" type="text" name="locality" onchange="" />
					</div>
					<div>
						<b>Latitude/Longitude:</b>
					</div>
					<div style="margin-left:75px;">
						<input id="latnorthinput" type="text" name="latnorth" style="width:70px;" onchange="" title="Latitude North" />
					</div>
					<div>
						<span style="">
							<input id="lngwestinput" type="text" name="lngwest" style="width:70px;" onchange="" title="Longitude West" />
						</span>
						<span style="margin-left:70px;">
							<input id="lngeastinput" type="text" name="lngeast" style="width:70px;" onchange="" title="Longitude East" />
						</span>
					</div>
					<div style="margin-left:75px;">
						<input id="latsouthinput" type="text" name="latsouth" style="width:70px;" onchange="" title="Latitude South" />
					</div>
					<div>
						<input type="submit" name="buildsql" value="Build SQL" />
					</div>
				</fieldset>
			</form>
			<form name="sqlform" action="floradynsql.php" method="post" style="margin-bottom:15px;">
				<div>
					Once SQL fragment meets your requirements, click the 'Save SQL Fragment' button to transfer to the database. 
					The 'Test SQL Fragment' button will test and verify your SQL syntax. 
					Note that you can fine tune the SQL by hand before saving.
				</div>
				<fieldset>
					<legend><b>New SQL Fragment</b></legend>
					<input type="hidden" name="clid" value="<?php echo $clid; ?>"/>
					<textarea id="sqlfrag" rows="5" cols="70"><?php echo $sqlFrag?$sqlFrag:$dynSqlManager->getDynamicSql();?></textarea>
					<input type="submit" name="action" value="Test SQL Fragment" />
					<input type="submit" name="action" value="Save SQL Fragment" />
				</fieldset>
			</form>
		<?php } ?>
	</div>
	<?php
 	include($serverRoot.'/footer.php');
	?>

</body>
</html> 