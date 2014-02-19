<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceListManager.php');
header("Content-Type: text/html; charset=".$charset);

$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:1; 
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;
$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;

$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
$collManager = new OccurrenceListManager();

$specimenArray = $collManager->getSpecimenMap($pageNumber, $cntPerPage);			//Array(IID,Array(fieldName,value))

$occFieldArr = array('occurrenceid','family', 'scientificname', 'sciname', 
	'tidinterpreted', 'scientificnameauthorship', 'taxonremarks', 'identifiedby', 'dateidentified', 'identificationreferences',
	'identificationremarks', 'identificationqualifier', 'typestatus', 'recordedby', 'recordnumber',
	'associatedcollectors', 'eventdate', 'year', 'month', 'day', 'startdayofyear', 'enddayofyear',
	'verbatimeventdate', 'habitat', 'substrate', 'fieldnumber','occurrenceremarks', 'associatedtaxa', 'verbatimattributes',
	'dynamicproperties', 'reproductivecondition', 'cultivationstatus', 'establishmentmeans', 
	'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
	'country', 'stateprovince', 'county', 'municipality', 'locality',
	'decimallatitude', 'decimallongitude','geodeticdatum', 'coordinateuncertaintyinmeters','coordinateprecision', 
	'locationremarks', 'verbatimcoordinates', 'georeferencedby', 'georeferenceprotocol', 'georeferencesources', 
	'georeferenceverificationstatus', 'georeferenceremarks', 'minimumelevationinmeters', 'maximumelevationinmeters',
	'verbatimelevation','language',
	'labelproject','basisofrecord');
?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
    <title><?php echo $defaultTitle; ?> Collections Search Results</title>
    <link rel="stylesheet" href="../css/main.css" type="text/css">
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<style type="text/css">
		.ui-tabs .ui-tabs-nav li { width:32%; }
		.ui-tabs .ui-tabs-nav li a { margin-left:10px;}
	</style>
	
	<script type="text/javascript" src="../js/jquery.js?ver=20130917"></script>
	<script type="text/javascript" src="../js/jquery-ui.js?ver=20130917"></script>
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		$('html').hide();
		$(document).ready(function() {
			$('html').show();
		});

		$(document).ready(function() {
			$('#tabs').tabs({
				active: <?php echo $tabIndex; ?>,
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<p>Loading...</p>");
				}
			});
		});

		function addVoucherToCl(occid,clid,tid){
			var vXmlHttp = GetXmlHttpObject();
			if(vXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url = "rpc/addvoucher.php" + "?occid=" + occid + "&clid=" + clid + "&tid=" + tid;
			vXmlHttp.onreadystatechange=function(){
				if(vXmlHttp.readyState==4 && vXmlHttp.status==200){
					var rStr = vXmlHttp.responseText;
					if(rStr == "1"){
						alert("Success! Voucher added to checklist.");
					}
					else{
						alert(rStr);
					}
				}
			};
			vXmlHttp.open("POST",url,true);
			vXmlHttp.send(null);
		}
		
		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
				var divs = document.getElementsByTagName("div");
				for (var h = 0; h < divs.length; h++) {
				var divObj = divs[h];
					if(divObj.className == target){
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

		function GetXmlHttpObject(){
			var xmlHttp=null;
			try{
				// Firefox, Opera 8.0+, Safari, IE 7.x
		  		xmlHttp=new XMLHttpRequest();
		  	}
			catch (e){
		  		// Internet Explorer
		  		try{
		    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		    	}
		  		catch(e){
		    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		    	}
		  	}
			return xmlHttp;
		}

		function openIndPU(occId,clid){
			var wWidth = 900;
			if(document.getElementById('maintable').offsetWidth){
				wWidth = document.getElementById('maintable').offsetWidth*1.05;
			}
			else if(document.body.offsetWidth){
				wWidth = document.body.offsetWidth*0.9;
			}
			if(wWidth > 1000) wWidth = 1000;
			newWindow = window.open('individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			return false;
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_listMenu)?$collections_listMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_listCrumbs)){
		if($collections_listCrumbs){
			echo "<div class='navpath'>";
			echo $collections_listCrumbs.' &gt;&gt; ';
			echo " <b>Specimen Records</b>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt;&gt; ";
		echo "<a href='index.php'>Collections</a> &gt;&gt; ";
		echo "<a href='harvestparams.php'>Search Criteria</a> &gt;&gt; ";
		echo "<b>Specimen Records</b>";
		echo "</div>";
	}
	?>

<!-- This is inner text! -->
<div id="innertext">
	<div id="tabs" style="width:95%;">
	    <ul>
	        <li>
	        	<a href="checklist.php?taxonfilter=<?php echo $taxonFilter; ?>">
	        		<span>Species List</span>
	        	</a>
	        </li>
	        <li>
	        	<a href="#speclist">
	        		<span>Specimens</span>
	        	</a>
	        </li>
	        <li>
	        	<a href="#maps">
	        		<span>Maps</span>
	        	</a>
	        </li>
	    </ul>
		<div id="speclist">

			<div class='button' style='margin:15px 15px 0px 0px;float:right;width:13px;height:13px;' title='Download Specimen Data'>
				<a href='download/download.php'>
					<img src='../images/dl.png'/>
				</a>
			</div>
			<div style='margin:10px;'>
				<div><b>Dataset:</b> <?php echo $collManager->getDatasetSearchStr(); ?></div>
				<?php 
				if($collManager->getTaxaSearchStr()){
					echo "<div><b>Taxa:</b> ".$collManager->getTaxaSearchStr()."</div>";
				}
				if($collManager->getLocalSearchStr()){
				    echo "<div><b>Search Criteria:</b> ".$collManager->getLocalSearchStr()."</div>";
				}
				?>
			</div>
			<?php 
			$paginationStr = "<div><div style='clear:both;'><hr/></div><div style='float:left;margin:5px;'>\n";
			$lastPage = (int) ($collManager->getRecordCnt() / $cntPerPage) + 1;
			$startPage = ($pageNumber > 4?$pageNumber - 4:1);
			$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
			$hrefPrefix = 'list.php?'.(array_key_exists('targettid',$_REQUEST)?'&targettid='.$_REQUEST["targettid"]:'').'&page=';
			$pageBar = '';
			if($startPage > 1){
			    $pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='".$hrefPrefix."1'>First</a></span>";
			    $pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='".$hrefPrefix.(($pageNumber - 10) < 1 ?1:$pageNumber - 10)."'>&lt;&lt;</a></span>";
			}
			for($x = $startPage; $x <= $endPage; $x++){
			    if($pageNumber != $x){
			        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;'><a href='".$hrefPrefix.$x."'>".$x."</a></span>";
			    }
			    else{
			        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
			    }
			}
			if(($lastPage - $startPage) >= 10){
			    $pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='".$hrefPrefix.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10))."'>&gt;&gt;</a></span>";
			    $pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='".$hrefPrefix.$lastPage."'>Last</a></span>";
			}
			$pageBar .= "</div><div style='float:right;margin:5px;'>";
			$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
			$endNum = $beginNum + $cntPerPage - 1;
			if($endNum > $collManager->getRecordCnt()) $endNum = $collManager->getRecordCnt();
			$pageBar .= "Page ".$pageNumber.", records ".$beginNum."-".$endNum." of ".$collManager->getRecordCnt();
			$paginationStr .= $pageBar;
			$paginationStr .= "</div><div style='clear:both;'><hr/></div></div>";
			echo $paginationStr;
	
			//Display specimen records
			if(array_key_exists("error",$specimenArray)){
				echo "<h3>".$specimenArray["error"]."</h3>";
				$collManager->reset();
			}
			elseif($specimenArray){
			    $collectionArr = $collManager->getCollectionList(array_keys($specimenArray));
			    ?>
				<table id="omlisttable" cellspacing="4">
				<?php 
			    foreach($specimenArray as $collId => $specData){
			    	$isEditor = false;
			    	if($symbUid && (array_key_exists("SuperAdmin",$userRights)
					|| (array_key_exists('CollAdmin',$userRights) && in_array($collId,$userRights['CollAdmin']))
					|| (array_key_exists('CollEditor',$userRights) && in_array($collId,$userRights['CollEditor'])))){
						$isEditor = true;
					}
					$instCode1 = $collectionArr[$collId]['instcode'];
					if($collectionArr[$collId]['collcode']) $instCode1 .= ":".$collectionArr[$collId]['collcode'];
		
					$icon = (substr($collectionArr[$collId]['icon'],0,6)=='images'?'../':'').$collectionArr[$collId]['icon']; 
			        ?>
					<tr>
						<td colspan='4'>
							<h2>
								<a href="misc/collprofiles.php?collid=<?php echo $collId; ?>">
									<?php echo $collectionArr[$collId]['name'];?>
					        	</a>
				        	</h2>
							<hr />
						</td>
					</tr>
					<?php 
			        foreach($specData as $occId => $fieldArr){
						$instCode2 = "";
						if($fieldArr["institutioncode"] && $fieldArr["institutioncode"] != $collectionArr[$collId]['instcode']){
							$instCode2 = $fieldArr["institutioncode"];
							if($fieldArr["collectioncode"]) $instCode2 .= ":".$fieldArr["collectioncode"];
						}
						?>
						<tr>
							<td rowspan="4" width='60' valign='top' align='center'>
								<a href="misc/collprofiles.php?collid=<?php echo $collId."&acronym=".$fieldArr["institutioncode"]; ?>">
			                    	<img align='bottom' width='30' src='<?php echo $icon; ?>' style="border:0px;" title='<?php echo ($instCode2?$instCode2:$instCode1); ?> Collection Statistics' />
			                    </a>
			                    <div style='font-weight:bold;font-size:75%;'>
			                    	<?php 
			                    	echo $instCode1;
									if($instCode2) echo "<br/>".$instCode2;
			                    	?>
			                    </div>
							</td>
							<td colspan='3'>
								<?php 
								if($isEditor || ($symbUid && $symbUid == $fieldArr['observeruid'])){ 
									?>
									<div style="float:right;" title="Edit Occurrence Record">
										<a href="editor/occurrenceeditor.php?occid=<?php echo $occId; ?>" target="_blank">
											<img src="../images/edit.png" style="border:solid 1px gray;height:13px;" />
										</a>
									</div>
									<?php 
								} 
								if($collManager->getClName() && array_key_exists('targettid',$_REQUEST)){ 
									?>
									<div style="float:right;cursor:pointer;" onclick="addVoucherToCl(<?php echo $occId.",".$collManager->getSearchTerm("clid").",".$_REQUEST["targettid"];?>)" title="Add as <?php echo $collManager->getClName(); ?> Voucher">
										<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" />
									</div>
									<?php 
								}
								?>
								<div style="float:left;">
									<a target='_blank' href='../taxa/index.php?taxon=<?php echo $fieldArr["sciname"];?>'>
										<span style='font-style:italic;' title='General Species Information'>
											<?php echo $fieldArr["sciname"];?>
										</span>
									</a> 
									<?php echo $fieldArr["author"]; ?>
								</div>
							</td>
						</tr>
						<tr>
							<td width='20%'>
								<?php echo $fieldArr["accession"];?>
							</td>
							<td>
								<?php echo $fieldArr["collector"]."&nbsp;&nbsp;&nbsp;".$fieldArr["collnumber"]; ?>
							</td>
							<td width='20%'>
								<?php echo $fieldArr["date1"].($fieldArr["date2"]?" to ".$fieldArr["date2"]:""); ?>
							</td>
						</tr>
						<tr>
							<?php 
				            $localStr = "";
				            if($fieldArr["country"]) $localStr .= $fieldArr["country"].", ";
				            if($fieldArr["state"]) $localStr .= $fieldArr["state"].", ";
				            if($fieldArr["county"]) $localStr .= $fieldArr["county"].", ";
				            if($fieldArr["locality"]) $localStr .= $fieldArr["locality"].", ";
				            if($fieldArr["date1"] || $fieldArr["date1"]) $localStr .= $fieldArr["date1"]."-" . $fieldArr["date2"] . "m, ";
				            if(strlen($localStr) > 2) $localStr = substr($localStr,0, strlen($localStr) - 2);
				            ?>
				            <td colspan='3'>
				            	<?php echo $localStr; ?>
				            </td>
			            </tr>
			            <tr>
			            	<td colspan='3'>
					            <b>
					            	<a href="#" onclick="return openIndPU(<?php echo $occId.",".($collManager->getSearchTerm("clid")?$collManager->getSearchTerm("clid"):"0"); ?>);">
				            			Full Record Details
				            		</a>
				            	</b>
			            	</td>
			            </tr>
			            <tr>
			            	<td colspan='4'>
			            		<hr/>
			            	</td>
			            </tr>
			            <?php 
			        }
			    }
			    ?>
				</table>
				<?php 
				echo $paginationStr;
				echo "<hr/>";
			}
			else{
				?>
				<div>
					<h3>Your query did not return any results. Please modify your query parameters.</h3>
					<?php
					$tn = $collManager->getTaxaSearchStr();
					if($p = strpos($tn,';')){
						$tn = substr($tn,0,$p);
					}
					if($p = strpos($tn,'=>')){
						$tn = substr($tn,$p+2);
					}
					if($p = strpos($tn,'(')){
						$tn = substr($tn,0,$p);
					}
					if($closeArr = $collManager->getCloseTaxaMatch(trim($tn))){
						?>
						<div style="margin: 40px 0px 200px 20px;font-weight:bold;font-size:140%;">
							Perhaps you were looking for:
							<?php
							$delimiter = '';
							foreach($closeArr as $v){
								echo $delimiter.'<a href="harvestparams.php?taxa='.$v.'">'.$v.'</a>';
								$delimiter = ', ';
							}
							?>
						</div>
						<?php 
					}
					?>
				</div>
				<?php 
			}
			?>
		</div>
	    <div id="maps" style="min-height:400px;margin-bottom:10px;">
	
		    <div class="button" style="margin-top:20px;float:right;width:13px;height:13px;" title="Download Coordinate Data">
				<a href="download/downloadhandler.php?dltype=georef"><img src="../images/dl.png"/></a>
	        </div>
	        <div style='margin-top:10px;'>
	        	<h2>Google Map</h2>
	        </div>
			<div style='margin:10 0 0 20;'>
			    <a href='javascript:var popupReference=window.open("../map/googlemap.php?maptype=occquery","gmap","toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=950,height=700,left=20,top=20");'>
			        Display coordinates in Google Map
			    </a>
			</div>
			<div style='margin:10 0 0 20;'>Google Maps is a web mapping service provided by Google that features a 
			    map that users can pan (by dragging the mouse) and zoom (by using the mouse wheel). Collection points are 
			    displayed as colored markers that when clicked on, displays the full information for that collection. When 
			    multiple species are queried (separated by semi-colons), 
			    different colored markers denote each individual species.
			</div>
	
			<div style='margin-top:10px;'>
			    <h2>Google Earth (KML)</h2>
			</div>
			<form name="kmlform" action="../map/googlekml.php" method="post" onsubmit="">
				<div style='margin:10 0 0 20;'>
					This creates an KML file that can be opened in the Google Earth mapping application.
					Note that you must have <a href='http://earth.google.com/' target="_blank">
					Google Earth</a> installed on your computer to make use of this option.
				</div>
				<div style='margin:10 0 0 20;'>
					<a href="#" onclick="toggle('fieldBox');">
						Add Extra Fields
					</a>
				</div>
				<div id="fieldBox" style="display:none;">
					<fieldset>
						<div style="width:600px;">
							<?php 
							foreach($occFieldArr as $k => $v){
								echo '<div style="float:left;margin-right:5px;">';
								echo '<input type="checkbox" name="kmlFields[]" value="'.$v.'" />'.$v.'</div>';
							}
							?>
						</div>
					</fieldset>
				</div>
				<div style="margin-top:8px;float:right;">
					<button name="formsubmit" type="submit" value="Create KML">Create KML</button>
				</div>
			</form>
	    </div>
	</div>
</div>
<?php 
	include($serverRoot."/footer.php");
?>
</body>
</html>
