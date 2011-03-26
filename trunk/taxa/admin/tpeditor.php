<?php
/*
* Rebuilt on Sept 2010
* Author: E.E. Gilbert
*/

//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TPEditorManager.php');

$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:0;
$taxon = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
$category = array_key_exists("category",$_REQUEST)?$_REQUEST["category"]:"common";
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$tEditor = new TPEditorManager();
if($tid){
	$tEditor->setTid($tid);
}
elseif($taxon){
	$tEditor->setTid($taxon);
}
if($tEditor->getTid()){
	if(strpos($category,"image") !== false){
		header('Location: tpimageeditor.php?tid='.$tEditor->getTid().'&category='.$category.'&lang='.$lang);
	}
	elseif(strpos($category,"desc") !== false){
		header('Location: tpdesceditor.php?tid='.$tEditor->getTid().'&category='.$category.'&lang='.$lang);
	}
	$tEditor->setLanguage($lang);
	 
	$editable = false;
	if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
		$editable = true;
	}

	$status = "";
	if($editable){
		if($action == "Edit Synonym Sort Order"){
			$synSortArr = Array();
			foreach($_REQUEST as $sortKey => $sortValue){
				if($sortValue && (substr($sortKey,0,4) == "syn-")){
					$synSortArr[substr($sortKey,4)] = $sortValue;
				}
			}
			$status = $tEditor->editSynonymSort($synSortArr);
		}
	 	elseif($action == "Submit Common Name Edits"){
	 		$editVernArr = Array();
			$editVernArr["vid"] = $_REQUEST["vid"];
	 		if($_REQUEST["vernacularname"]) $editVernArr["vernacularname"] = str_replace("\"","-",$_REQUEST["vernacularname"]);
			if($_REQUEST["language"]) $editVernArr["language"] = $_REQUEST["language"];
			$editVernArr["notes"] = str_replace("\"","-",$_REQUEST["notes"]);
			$editVernArr["source"] = $_REQUEST["source"];
			if($_REQUEST["sortsequence"]) $editVernArr["sortsequence"] = $_REQUEST["sortsequence"];
			$editVernArr["username"] = $paramsArr["un"];
			$status = $tEditor->editVernacular($editVernArr);
		}
		elseif($action == "Add Common Name"){
			$addVernArr = Array();
			$addVernArr["vernacularname"] = str_replace("\"","-",$_REQUEST["vern"]);
			if($_REQUEST["language"]) $addVernArr["language"] = $_REQUEST["language"];
			if($_REQUEST["notes"]) $addVernArr["notes"] = str_replace("\"","-",$_REQUEST["notes"]);
			if($_REQUEST["source"]) $addVernArr["source"] = $_REQUEST["source"];
			if($_REQUEST["sortsequence"]) $addVernArr["sortsequence"] = $_REQUEST["sortsequence"];
			$addVernArr["username"] = $paramsArr["un"];
			$status = $tEditor->addVernacular($addVernArr);
		}
		elseif($action == "Delete Common Name"){
			$delVern = $_REQUEST["delvern"];
			$status = $tEditor->deleteVernacular($delVern);
		}
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." Taxon Editor: ".$tEditor->getSciName(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<link rel="stylesheet" href="../../css/speciesprofile.css" type="text/css"/>
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery-1.4.4.min.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui-1.8.11.custom.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#sninput").autocomplete({
				source: function( request, response ) {
					$.getJSON( "rpc/gettaxalist.php", { "term": request.term, "taid": "1" }, response );
				}
			},{ minLength: 3, autoFocus: true }
			);
		});

		function toggle(target){
			var spanObjs = document.getElementsByTagName("span");
			for (i = 0; i < spanObjs.length; i++) {
				var obj = spanObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="inline";
					}
					else {
						obj.style.display="none";
					}
				}
			}
	
			var divObjs = document.getElementsByTagName("div");
			for (var i = 0; i < divObjs.length; i++) {
				var obj = divObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="block";
					}
					else {
						obj.style.display="none";
					}
				}
			}
		}

		function checkGetTidForm(f){
			if(f.taxon.value == ""){
				alert("Please enter a scientific name.");
				return false;
			}
			return true;
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_tpeditorMenu)?$taxa_admin_tpeditorMenu:false);
include($serverRoot.'/header.php');
if(isset($taxa_admin_tpeditorCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_tpeditorCrumbs;
	echo " <b>Taxon Profile Editor</b>"; 
	echo "</div>";
}

if($tEditor->getTid()){
	if($editable){
		?>
		<table style="width:100%;">
			<tr><td>
				<div style='float:right;'>
					<ul style="margin-top:15px;width:200px;border:dotted 1px;">
						<li><a href="../index.php?taxon=<?php echo $tEditor->getTid(); ?>">Taxon Profile Page</a></li>
						<li><a href="tpimageeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=images">Edit Images</a></li>
						<ul>
							<li><a href="tpimageeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=imagequicksort">Edit Image Sorting Order</a></li>
							<li><a href="tpimageeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=imageadd">Add a New Image</a></li>
						</ul>
						<li><a href="tpdesceditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=textdescr">Text Descriptions</a></li>
						<?php if($isAdmin || array_key_exists("Taxonomy",$userRights)){ ?>
						<li><a href="taxonomydisplay.php?target=<?php echo $tEditor->getSciName(); ?>">View Taxonomic Tree</a></li>
						<ul>
							<li><a href="taxonomyeditor.php?target=<?php echo $tEditor->getTid(); ?>">Edit Taxonomic Placement</a></li>
							<li><a href="taxonomyloader.php">Add New Taxonomic Name</a></li>
						</ul>
						<?php } ?>
					</ul>
				</div>
			<?php 
	 	//If submitted tid does not equal accepted tid, state that user will be redirected to accepted
	 	if($tEditor->getSubmittedTid()){
	 		echo "<div style='font-size:16px;margin-top:5px;margin-left:10px;font-weight:bold;'>Redirected from: <i>".$tEditor->getSubmittedSciName()."</i></div>";
	 	}
		//Display Scientific Name and Family
		echo "<div style='font-size:16px;margin-top:15px;margin-left:10px;'><a href='../index.php?taxon=".$tEditor->getTid()."' style='color:#990000;text-decoration:none;'><b><i>".$tEditor->getSciName()."</i></b></a> ".$tEditor->getAuthor();
		//Display Parent link
		if($tEditor->getRankId() > 140) echo "&nbsp;<a href='tpeditor.php?tid=".$tEditor->getParentTid()."'><img border='0' height='10px' src='../../images/toparent.jpg' title='Go to Parent' /></a>";
		echo "</div>\n";
		//Display Family
		echo "<div id='family' style='margin-left:20px;margin-top:0.25em;'><b>Family:</b> ".$tEditor->getFamily()."</div>\n";
		
		if($status){
			echo "<h3 style='color:red;'>Error: $status<h3>";
		}
	
		//Display Synonyms
		$synonymArr = $tEditor->getSynonym();
		if($synonymArr){
			$synStr = "";
			foreach($synonymArr as $tidKey => $valueArr){
				 $synStr .= ", ".$valueArr["sciname"];
			}
			echo "<div style='margin:10px 0px 10px 0px;width:450px;'><b>Synonyms:</b> ".substr($synStr,2)."&nbsp;&nbsp;&nbsp;";
			echo "<span onclick='javascript:toggle(\"synsort\");' title='Edit Synonym Sort Order'><img style='border:0px;width:12px;' src='../../images/edit.png'/></span>";
			echo "</div>\n";
			echo "<div class='synsort' style='display:none;'>";
			echo "<form action='".$_SERVER["PHP_SELF"]."' method='post'>\n";
			echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
			echo "<fieldset style='margin:5px 0px 5px 5px;margin-left:20px;width:350px;'>";
	    	echo "<legend>Synonym Sort Order</legend>";
			foreach($synonymArr as $tidKey => $valueArr){
				echo "<div><b>".$valueArr["sortsequence"]."</b> - ".$valueArr["sciname"]."</div>\n";
				echo "<div style='margin:0px 0px 5px 10px;'>new sort value: <input type='text' name='syn-".$tidKey."' style='width:35px;border:inset;' /></div>\n";
			}
			echo "<div><input type='submit' name='action' value='Edit Synonym Sort Order' /></div>\n";
			echo"</fieldset></form></div>\n";
		}
	
		//Display Common Names (vernaculars)
		$vernList = $tEditor->getVernaculars();
		echo "<div>";
		echo "<div><b>Common Names</b>&nbsp;&nbsp;&nbsp;<span onclick='javascript:toggle(\"addvern\");' title='Add a New Common Name'><img style='border:0px;width:15px;' src='../../images/add.png'/></span></div>\n";
		//Add new image section
		echo "<div id='addvern' class='addvern' style='display:none;'>";
		echo "<form id='addvernform' name='addvernform'>";
		echo "<fieldset style='width:250px;margin:5px 0px 0px 20px;'>";
	    echo "<legend>New Common Name</legend>";
		echo "<div style=''>Common Name: <input id='vern' name='vern' style='margin-top:5px;border:inset;' type='text' /></div>\n";
	    echo "<div style=''>Language: <input id='language' name='language' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Notes: <input id='notes' name='notes' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Source: <input id='source' name='source' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Sort Sequence: <input id='sortsequence' name='sortsequence' style='margin-top:5px;border:inset;width:40px;' type='text' /></div>\n";
		echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
		echo "<div><input id='vernsadd' name='action' style='margin-top:5px;' type='submit' value='Add Common Name' /></div>\n";
		echo "</fieldset>";
		echo "</form>\n";
		echo "</div>";
		if($vernList){
			foreach($vernList as $lang => $vernsList){
				echo "<div style='width:250px;margin:5px 0px 0px 15px;'><fieldset>";
		    	echo "<legend>".$lang."</legend>";
				foreach($vernsList as $vernArr){
					echo "<div style='margin-left:10px;'><b>".$vernArr["vernacularname"]."</b>&nbsp;&nbsp;&nbsp;\n";
					echo "<span onclick='javascript:toggle(\"vid-".$vernArr["vid"]."\");' title='Edit Common Name'><img style='border:0px;width:12px;' src='../../images/edit.png'/></span>&nbsp;&nbsp;&nbsp;\n";
					echo "</div>\n";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><div style='background-color:yellow;'>\n";
					echo "<form id='delvern' name='delvern' action='".$_SERVER["PHP_SELF"]."' method='post' onsubmit=\"javascript: return window.confirm('Are you sure you want to delete this Common Name?');\">\n";
					echo "<input type='hidden' name='delvern' value='".$vernArr["vid"]."'>\n";
					echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
					echo "<input id='vernsubmitimg' name='action' type='image' value='Delete Common Name' style='margin:10px 0px 0px 20px;height:12px;' src='../../images/del.gif'/> Delete Common Name ";
					echo "</form></div></div>\n";
					echo "<form id='updatevern' name='updatevern' style='margin-left:20px;'>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='vernacularname' name='vernacularname' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["vernacularname"]."' /></div>\n";
					echo "<div style='display:none;'>Language: ".$vernArr["language"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='language' name='language' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["language"]."' /></div>\n";
					echo "<div style=''>Notes: ".$vernArr["notes"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='notes' name='notes' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["notes"]."' /></div>\n";
					echo "<div style=''>Source: ".$vernArr["source"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='source' name='source' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["source"]."' /></div>\n";
					echo "<div style=''>Sort Sequence: ".$vernArr["sortsequence"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='sortsequence' name='sortsequence' style='margin:2px 0px 5px 15px;border:inset;width:40px;' type='text' value='".$vernArr["sortsequence"]."' /></div>\n";
					echo "<input type='hidden' name='vid' value='".$vernArr["vid"]."'>\n";
					echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='vernssubmit' name='action' style='margin:2px 0px 20px 15px;' type='submit' value='Submit Common Name Edits' /></div>\n";
					echo "</form>\n";
					
				}
				echo "</fieldset></div>";
			}
			echo "</div>";
		}
		?>
			</td></tr>
		</table>
		<?php  
	}
	else{
		?>
		<div style="margin:30px;">
			<h2>You must be logged in and authorized to taxon data.</h2>
			<h3>
				<?php 
					echo "Click <a href='".$clientRoot."/profile/index.php?tid=".$tEditor->getTid()."&refurl=".$clientRoot."/taxa/admin/tpeditor.php'>here</a> to login";
				?>
			</h3>
		</div>
		<?php 
	}
}
else{
	?>
	<div style="margin:20px;">
		<div style="font-weight:bold;">
		<?php 
		if($taxon){
			echo "<i>".ucfirst($taxon)."</i> not found in system. Check to see if spelled correctly and if so, add to system.";
		}
		else{
			echo "Enter scientific name you wish to edit:";
		}
		?>
		</div>
		<form name="gettidform" action="tpeditor.php" method="post" onsubmit="return checkGetTidForm(this);">
			<input id="sninput" name="taxon" value="<?php echo $taxon; ?>" size="40" />
			<input type="hidden" name="category" value="<?php echo $category; ?>" />
			<input type="hidden" name="lang" value="<?php echo $lang; ?>" />
			<input type="submit" name="action" value="Edit Taxon" />
		</form>
	</div>
	<?php 
}
include($serverRoot.'/footer.php');
?>

</body>
</html>
