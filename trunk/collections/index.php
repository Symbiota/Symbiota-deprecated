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

$otherCatArr = $collManager->getOccurVoucherProjects();
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
			$('html').hide();
			$(document).ready(function() {
				$('html').show();
			});

			
			$(document).ready(function() {
				if(!navigator.cookieEnabled){
					alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
				}

				$("#tabs").tabs();

				//document.collections.onkeydown = checkKey;
			});
		
			function toggle(target){
				var ele = document.getElementById(target);
				if(ele){
					if(ele.style.display=="none"){
						if(ele.id.substring(0,5) == "minus" || ele.id.substring(0,4) == "plus"){
							ele.style.display = "inline";
				  		}
						else{
							ele.style.display = "block";
						}
			  		}
				 	else {
				 		ele.style.display="none";
				 	}
				}
			}

			function toggleCat(catid){
				toggle("minus-"+catid);
				toggle("plus-"+catid);
				toggle("cat-"+catid);
			}

			function togglePid(pid){
				toggle("minus-pid-"+pid);
				toggle("plus-pid-"+pid);
				toggle("pid-"+pid);
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

			function selectAllPid(cb){
				var boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var target = "pid-"+cb.value;
				var inputObjs = document.getElementsByTagName("input");
			  	for (i = 0; i < inputObjs.length; i++) {
			  		var inputObj = inputObjs[i];
			  		if(inputObj.getAttribute("class") == target || inputObj.getAttribute("className") == target){
			  			inputObj.checked = boxesChecked;
			  		}
			  	}
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
				var pidElems = document.getElementsByName("pid[]");
				for(i = 0; i < pidElems.length; i++){
					var pidElem = pidElems[i];
					if(pidElem.checked) return true;
				}
				var clidElems = document.getElementsByName("clid[]");
				for(i = 0; i < clidElems.length; i++){
					var clidElem = clidElems[i];
					if(clidElem.checked) return true;
				}
			   	alert("Please choose at least one search region!");
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
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" checked />
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
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballspeccb" name="db[]" class="spec" value='allspec' type="checkbox" onclick="selectAll(this);" checked />
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
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballobscb" name="db[]" class="obs" value='allobs' type="checkbox" onclick="selectAll(this);" checked />
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
				$titleArr = $otherCatArr['titles'];
				unset($otherCatArr['titles']);
				?>
				<div id="otherdiv">
					<form id="othercatform" action="harvestparams.php" method="get" onsubmit="return verifyOtherCatForm(this)">
						<?php 
						foreach($otherCatArr as $catPid => $catArr){
							?>
							<fieldset style="margin:10px;padding:10px;">
								<legend style="font-weight:bold;"><?php echo $titleArr[$catPid]; ?></legend>
								<?php 
								foreach($catArr as $pid => $clidArr){
									?>
									<div>
										<a href="#" onclick="togglePid('<?php echo $pid; ?>');return false;"><img id="plus-pid-<?php echo $pid; ?>" src="../images/plus.gif" /><img id="minus-pid-<?php echo $pid; ?>" src="../images/minus.gif" style="display:none;" /></a>
										<input name="pid[]" type="checkbox" value="<?php echo $pid; ?>" onchange="selectAllPid(this);" />
										<b><?php echo $titleArr[$pid]; ?></b>
									</div>
									<div id="pid-<?php echo $pid; ?>" style="margin:10px 15px;display:none;">
										<div style="margin:20px 15px;float:right;">
											<input type="image" src='../images/next.jpg'
												onmouseover="javascript:this.src = '../images/next_rollover.jpg';" 
												onmouseout="javascript:this.src = '../images/next.jpg';"
												title="Click button to advance to the next step" />
										</div>
										<?php 
										foreach($clidArr as $clid => $clidName){
											?>
											<div>
												<input name="clid[]" class="pid-<?php echo $pid; ?>" type="checkbox" value="<?php echo $clid; ?>" />
												<?php echo $clidName; ?>
											</div>
											<?php
										} 
										?>
									</div>
									<?php
								} 
								?>
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