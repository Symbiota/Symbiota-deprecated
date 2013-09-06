<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');
header("Content-Type: text/html; charset=".$charset);

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

$collManager = new OccurrenceManager();
$collManager->reset();

$collList = $collManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);

//$otherCatArr = $collManager->getSurveys();
$otherCatArr = $collManager->getOccurVoucherProjects();
//$ownerInstArr = $collManager->getOwnerInstitutions();
//$specProjArr = $collManager->getSpecProjects();
?>

<!doctype html>
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> Collections Search</title>
	    <link type="text/css" href="../css/main.css" rel="stylesheet" />
		<script type="text/javascript">
			<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
		</script>
		<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />	
		<script type="text/javascript" src="../js/jquery.js"></script>
		<script type="text/javascript" src="../js/jquery-ui.js"></script>
		<script language="javascript" type="text/javascript">
			$(document).ready(function() {
				if(!navigator.cookieEnabled){
					alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
				}

				$("#tabs").tabs();

				c = document.cookie;
				if(c.indexOf("colldbs=all") > -1){
					document.getElementById("dballcb").checked = true;
				}

				//document.collections.onkeydown = checkKey;
			});
		
			function toggle(target){
				var ele = document.getElementById(target);
				if(ele){
					if(ele.style.display=="none"){
						ele.style.display="block";
			  		}
				 	else {
				 		ele.style.display="none";
				 	}
				}
				else{
					var divObjs = document.getElementsByTagName("div");
				  	for (i = 0; i < divObjs.length; i++) {
				  		var divObj = divObjs[i];
				  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
							if(divObj.style.display=="none"){
								divObj.style.display="block";
							}
						 	else {
						 		divObj.style.display="none";
						 	}
						}
					}
				}
			}

			function toggleCat(catid){
				toggle("minus-"+catid);
				toggle("plus-"+catid);
				toggle("cat-"+catid);
			}

			function selectAll(cb){
				var boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var f = cb.form;
				for(var i=0;i<f.length;i++){
					if(f.elements[i].name == "db[]" || f.elements[i].name == "cat[]") f.elements[i].checked = boxesChecked;
				}
			}

			function uncheckAll(f){
				document.getElementById('dballcb').checked = false;
				document.getElementById('dballspeccb').checked = false;
				document.getElementById('dballobscb').checked = false;
			}

			function selectAllCat(cb,target){
				var boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var inputObjs = document.getElementsByTagName("input");
			  	for (i = 0; i < inputObjs.length; i++) {
			  		var inputObj = inputObjs[i];
			  		if(inputObj.getAttribute("class") == target || inputObj.getAttribute("className") == target){
			  			inputObj.checked = boxesChecked;
			  		}
			  	}
			}

			function unselectCat(catTarget){
				var catObj = document.getElementById(catTarget);
				catObj.checked = false;
				uncheckAll();
			}

			function verifyCollForm(f){
				var formVerified = false;
				for(var h=0;h<f.length;h++){
					if(f.elements[h].name == "db[]" && f.elements[h].checked){
						formVerified = true;
						break;
					}
					if(f.elements[h].name == "cat[]" && f.elements[h].checked){
						formVerified = true;
						break;
					}
				}
				if(!formVerified){
					alert("Please choose at least one collection!");
					return false;
				}
				else{
					for(var i=0;i<f.length;i++){
						if(f.elements[i].name == "cat[]" && f.elements[i].checked){
							//Uncheck all db input elements within cat div 
							var childrenEle = document.getElementById('cat-'+f.elements[i].value).children;
							for(var j=0;j<childrenEle.length;j++){
								if(childrenEle[j].tagName == "DIV"){
									var divChildren = childrenEle[j].children;
									for(var k=0;k<divChildren.length;k++){
										var divChildren2 = divChildren[k].children;
										for(var l=0;l<divChildren2.length;l++){
											if(divChildren2[l].tagName == "INPUT"){
												divChildren2[l].checked = false;
											}
										}
									}
								}
							}
						}
					}
				}
		      	return formVerified;
		    }

		    function verifyOtherCatForm(f){
				var dbElements = document.getElementsByName("surveyid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please choose at least one checkbox!");
				return false;
		    }

			function checkKey(e){
		        var key;
		        if(window.event){
		            key = window.event.keyCode;
		        }else{
		            key = e.keyCode;
		        }
		        if(key == 13){
		            document.collections.submit();
		        }
		    }
		</script>
	</head>
	<body>
	
	<?php
	$displayLeftMenu = (isset($collections_indexMenu)?$collections_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($collections_indexCrumbs)){
		if($collections_indexCrumbs){
			echo "<div class='navpath'>";
			echo $collections_indexCrumbs;
			echo " <b>Collections</b>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt;&gt; ";
		echo "<b>Collections</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Collections to be Searched</h1>
		<div id="tabs" style="margin:0px;">
		    <ul>
		        <?php 
				if($specArr && $obsArr) echo '<li><a href="#specobsdiv">Specimens &amp; Observations</a></li>';
				if($specArr) echo '<li><a href="#specimendiv">Specimens</a></li>';
				if($obsArr) echo '<li><a href="#observationdiv">Observations</a></li>';
				if($otherCatArr) echo '<li><a href="#otherdiv">Federal Units</a></li>';
				?>
		    </ul>
	        <?php 
	        if($specArr && $obsArr){
				?>
				<div id="specobsdiv">
					<form name="collform1" action="harvestparams.php" method="get" onsubmit="return verifyCollForm(this)">
			        	<div style="margin:0px 0px 10px 38px;">
			         		<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" />
			         		Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
			        	</div>
						<?php 
						$collManager->outputFullCollArr($specArr); 
						if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>'; 
						$collManager->outputFullCollArr($obsArr);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
	        <?php 
	        }
	        if($specArr){
	        	?>
				<div id="specimendiv">
					<form name="collform2" action="harvestparams.php" method="get" onsubmit="return verifyCollForm(this)">
			        	<div style="margin:0px 0px 10px 38px;">
			         		<input id="dballspeccb" name="db[]" class="spec" value='allspec' type="checkbox" onclick="selectAll(this);" />
			         		Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
			        	</div>
						<?php
						$collManager->outputFullCollArr($specArr);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
	        	<?php 
	        }
	        if($obsArr){
	        	?>
				<div id="observationdiv">
					<form name="collform3" action="harvestparams.php" method="get" onsubmit="return verifyCollForm(this)">
			        	<div style="margin:0px 0px 10px 38px;">
							<input id="dballobscb" name="db[]" class="obs" value='allobs' type="checkbox" onclick="selectAll(this);" />
							Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
						</div>
						<?php
						$collManager->outputFullCollArr($obsArr);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
				<?php 
	        } 
			if($otherCatArr){ 
				?>
				<div id="otherdiv">
					<form id="othercatform" action="harvestparams.php" method="get" onsubmit="return verifyOtherCatForm(this)">
						<?php 
						foreach($otherCatArr as $projTitle => $surveyArr){
							?>
							<fieldset style="margin:10px;">
								<legend style="font-weight:bold;"><?php echo $projTitle; ?></legend>
								<div style="margin:40px 15px;float:right;">
						        	<input type="image" src='../images/next.jpg'
						                onmouseover="javascript:this.src = '../images/next_rollover.jpg';" 
						                onmouseout="javascript:this.src = '../images/next.jpg';"
						                title="Click button to advance to the next step" />
								</div>
								<div style="margin:10px;">
									<?php 
									foreach($surveyArr as $surveyId => $surveyName){
										?>
										<div style="margin:5px;">
											<input name="surveyid[]" value='<?php echo $surveyId; ?>' type='checkbox' />
											<?php echo $surveyName; ?>
										</div>
										<?php 
									}
									?>
								</div>
							</fieldset>
							<?php 
						}
						?>
					</form>
				</div>
				<?php 
			} 
			?>
		</div>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
	</body>
</html>