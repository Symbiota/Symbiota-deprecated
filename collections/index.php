<?php
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/OccurrenceManager.php');
 header("Content-Type: text/html; charset=".$charset);

 $catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
 
 $collManager = new OccurrenceManager();
 $collManager->reset();
 
 $specArr = Array();
 $obsArr = Array();
 $collList = $collManager->getCollectionArr($catId);
 foreach($collList as $collId => $collObj){
	$collType = $collObj["colltype"];
	if(stripos($collType, "specimen") !== false){
	 	$specArr[$collId]["institutioncode"] = $collObj["institutioncode"];
	 	$specArr[$collId]["collectionname"] = $collObj["collectionname"];
	 	$specArr[$collId]["icon"] = $collObj["icon"];
	}
	elseif(stripos($collType, "observation") !== false){
	 	$obsArr[$collId]["institutioncode"] = $collObj["institutioncode"];
		$obsArr[$collId]["collectionname"] = $collObj["collectionname"];
	 	$obsArr[$collId]["icon"] = $collObj["icon"];
	}
 } 
//$otherCatArr = $collManager->getSurveys();
$otherCatArr = $collManager->getOccurVoucherProjects();
//$ownerInstArr = $collManager->getOwnerInstitutions();
//$specProjArr = $collManager->getSpecProjects();
 ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
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

				$('#tabs').tabs();

				c = document.cookie;
				if(c.indexOf("colldbs=all") > -1){
					document.getElementById("dballcb").checked = true;
				}

		        document.collections.onkeydown = checkKey;
			});
		
			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				cName = cb.className;
				var dbElements = document.getElementsByName("db[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.className == cName){
						dbElement.checked = boxesChecked;
					}
					else{
						dbElement.checked = false;
					}
				}
			}

		    function verifyCollForm(f){
				var dbElements = document.getElementsByName("db[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please choose at least one collection!");
		      	return false;
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
			echo "<a href='../index.php'>Home</a> &gt; ";
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
		<h1>Select Collections to be Searched</h1>
		<div id="tabs" style="margin:0px;">
		    <ul>
		        <?php if($specArr && $obsArr){?>
		        <li><a href="#specobsdiv">Specimens &amp; Observations</a></li>
		        <?php }if($specArr){?>
		        <li><a href="#specimendiv">Specimens</a></li>
		        <?php }if($obsArr){?>
		        <li><a href="#observationdiv">Observations</a></li>
		        <?php }if($otherCatArr){?>
		        <li><a href="#otherdiv">Federal Units</a></li>
		        <?php } ?>
		    </ul>
			<form name="collections" id="collform" action="harvestparams.php" method="get" onsubmit="return verifyCollForm(this)">
	        <?php 
	        if($specArr && $obsArr){
				?>
				<div id="specobsdiv">
		        	<div style="margin:0px 0px 10px 30px;">
		         		<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this,'specobs');" />
		         		Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
		        	</div>
					<?php
					$collCnt = 1;
					foreach($collList as $collId => $collArr){
						?>
						<div style="clear:both;padding:5px;height:30px;">
							<div style="float:left;width:50px;">
								<?php 
								if($collArr["icon"]){
									$collIcon = (substr($collArr["icon"],0,6)=='images'?'../':'').$collArr["icon"]; 
									?>
									<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>'>
										<img border="1" height="30" width="30" src="<?php echo $collIcon; ?>" />
									</a>
							    	<?php
								}
							    ?>
							    &nbsp;
							</div>
							<div style="float:left;width:30px;padding-top:5px;">
					    		<input name="db[]" class="specobs" value='<?php echo $collId; ?>' type='checkbox' <?php echo (array_key_exists("isselected",$collArr)?"CHECKED":""); ?> /> 
							</div>
							<div style="float:left;padding-top:6px;">
					    		<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>' style='text-decoration:none;color:black;font-size:120%;'>
					    			<?php echo $collArr["collectionname"]." (".$collArr["institutioncode"].")"; ?>
					    		</a>
					    		<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>' style='font-size:75%;'>
					    			more info
					    		</a>
						    </div>
					    	<?php 
					    	if($collCnt%8 == 4 || (count($collList) < 4 && $collCnt == 1)){ 
					    		?>
							    <div style="float:right;width:60px;height:35px;margin-right:20px;">
						        	<input type="image" src='../images/next.jpg'
						                onmouseover="javascript:this.src = '../images/next_rollover.jpg';" 
						                onmouseout="javascript:this.src = '../images/next.jpg';"
						                title="Click button to advance to the next step" />
						    	</div>
					    		<?php 
					    	} 
					    	?>
					    </div>
					    <?php
					    $collCnt++; 
					}
					?>
				</div>
	        <?php 
	        }
	        if($specArr){
	        	?>
				<div id="specimendiv">
		        	<div style="margin:0px 0px 10px 30px;">
		         		<input name="db[]" class="spec" value='' type="checkbox" onclick="selectAll(this,'spec');" />
		         		Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
		        	</div>
					<?php
					$collCnt = 1;
					foreach($specArr as $collId => $collArr){
						$collIcon = (substr($collArr["icon"],0,6)=='images'?'../':'').$collArr["icon"];
						?>
						<div style="clear:both;padding:5px;height:30px;">
							<div style="float:left;width:50px;">
								<?php 
								if($collArr["icon"]){
									?>
							    	<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>'>
							    		<img border="1" height="30" width="30" src="<?php echo $collIcon; ?>" />
							    	</a>
							    	<?php
								} 
							    ?>
							    &nbsp;
							</div>
							<div style="float:left;width:30px;padding-top:5px;">
					    		<input name="db[]" class="spec" value='<?php echo $collId; ?>' type='checkbox' <?php echo (array_key_exists("isselected",$collArr)?"CHECKED":""); ?> /> 
							</div>
							<div style="float:left;padding-top:6px;">
					    		<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>' style='text-decoration:none;color:black;font-size:120%;'>
					    			<?php echo $collArr["collectionname"]." (".$collArr["institutioncode"].")"; ?>
					    		</a>
					    		<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>' style='font-size:75%;'>
					    			more info
					    		</a>
							</div>
							<?php 
							if($collCnt%8 == 4 || (count($specArr) < 4 && $collCnt == 1)){ 
								?>
							    <div style="float:right;width:60px;height:35px;margin-right:20px;">
									<input type="image" src='../images/next.jpg'
										onmouseover="javascript:this.src = '../images/next_rollover.jpg';" 
										onmouseout="javascript:this.src = '../images/next.jpg';"
										title="Click button to advance to the next step" />
								</div>
								<?php 
							} 
							?>
				    	</div>
						<?php
						$collCnt++; 
					}
					?>
				</div>
	        	<?php 
	        }
	        if($obsArr){
	        	?>
				<div id="observationdiv">
		        	<div style="margin:0px 0px 10px 30px;">
						<input name="db[]" class="obs" value='' type="checkbox" onclick="selectAll(this,'obs');" />
						Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php">Collections</a>
					</div>
					<?php
					$collCnt = 1;
					foreach($obsArr as $collId => $collArr){
						$collIcon = (substr($collArr["icon"],0,6)=='images'?'../':'').$collArr["icon"];
						?>
						<div style="clear:both;padding:5px;height:30px;">
							<div style="float:left;width:50px;">
								<?php 
								if($collArr["icon"]){
									?>
							    	<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>'>
							    		<img border="1" height="30" width="30" src="<?php echo $collIcon; ?>" />
							    	</a>
							    	<?php
								} 
							    ?>
							    &nbsp;
							</div>
							<div style="float:left;width:30px;padding-top:5px;">
					    		<input name="db[]" class="obs" value='<?php echo $collId; ?>' type='checkbox' <?php echo (array_key_exists("isselected",$collArr)?"CHECKED":""); ?> /> 
							</div>
							<div style="float:left;padding-top:6px;">
					    		<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>' style='text-decoration:none;color:black;font-size:120%;'>
					    			<?php echo $collArr["collectionname"]." (".$collArr["institutioncode"].")"; ?>
					    		</a>
					    		<a href = 'misc/collprofiles.php?collid=<?php echo $collId; ?>' style='font-size:75%;'>
					    			more info
					    		</a>
							</div>
					    	<?php 
					    	if($collCnt%8 == 4 || (count($obsArr) < 4 && $collCnt == 1)){ 
					    		?>
							    <div style="float:right;width:60px;height:35px;margin-right:20px;">
						        	<input type="image" src='../images/next.jpg'
						                onmouseover="javascript:this.src = '../images/next_rollover.jpg';" 
						                onmouseout="javascript:this.src = '../images/next.jpg';"
						                title="Click button to advance to the next step" />
								</div>
								<?php 
					    	} 
					    	?>
							<input type="hidden" name="catid" value="<?php echo $catId; ?>" /> 
					    </div>
					    <?php
					    $collCnt++; 
					}
					?>
				</div>
				<?php 
	        } 
	        ?>
			</form>
			<?php 
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