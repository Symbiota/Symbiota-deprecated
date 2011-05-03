<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceListManager.php');
header("Content-Type: text/html; charset=".$charset);

$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:1; 
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;
$clid = (array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:"");

$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
$collManager = new OccurrenceListManager();

$specimenArray = $collManager->getSpecimenMap($pageNumber);			//Array(IID,Array(fieldName,value))
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
	
	<script type="text/javascript" src="../js/jquery-1.4.4.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.8.11.custom.min.js"></script>
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">

		$(document).ready(function() {
			$('#tabs').tabs(
				{ selected: <?php echo $tabIndex; ?> }
			);
		});

		function addVoucherToCl(occid,clid,tid){
			var vXmlHttp = GetXmlHttpObject();
			if(vXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url = "rpc/addvoucher.php";
			url=url + "?occid=" + occid + "&clid=" + clid + "&tid=" + tid; 
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

	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_listMenu)?$collections_listMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_listCrumbs)){
		if($collections_listCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../index.php'>Home</a> &gt; ";
			echo $collections_listCrumbs;
			echo " <b>Specimen Records</b>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo "<a href='index.php'>Collections</a> &gt; ";
		echo "<a href='harvestparams.php'>Search Criteria</a> &gt; ";
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
	        		<span>Specimen List</span>
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
			<div style='clear:both;'><hr/></div>
			<?php 
			$paginationStr = "<table width='100%'>\n";
			$lastPage = (int) ($collManager->getRecordCnt() / $collManager->getCntPerPage()) + 1;
			$startPage = ($pageNumber > 4?$pageNumber - 4:1);
			$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
			$pageBar = "<tr><td>";
			if($startPage > 1){
			    $pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='list.php?page=1'>First</a></span>";
			    $pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='list.php?page=".(($pageNumber - 10) < 1 ?1:$pageNumber - 10)."'>&lt;&lt;</a></span>";
			}
			for($x = $startPage; $x <= $endPage; $x++){
			    if($pageNumber != $x){
			        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;'><a href='list.php?page=".$x."'>".$x."</a></span>";
			    }
			    else{
			        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
			    }
			}
			if(($lastPage - $startPage) >= 10){
			    $pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='list.php?page=".(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10))."'>&gt;&gt;</a></span>";
			    $pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='list.php?page=".$lastPage."'>Last</a></span>";
			}
			$pageBar .= "</td><td align='right'>";
			$beginNum = ($pageNumber - 1)*$collManager->getCntPerPage() + 1;
			$endNum = $beginNum + $collManager->getCntPerPage() - 1;
			if($endNum > $collManager->getRecordCnt()) $endNum = $collManager->getRecordCnt();
			$pageBar .= "Page ".$pageNumber.", records ".$beginNum."-".$endNum." of ".$collManager->getRecordCnt();
			$pageBar .= "</td></tr>";
			$paginationStr .= $pageBar;
			$paginationStr .= "</table>";
			echo $paginationStr;
	
			//Display specimen records
			if(array_key_exists("error",$specimenArray)){
				echo "<h3>".$specimenArray["error"]."</h3>";
				$collManager->reset();
			}
			elseif($specimenArray){
			    $collectionArr = $collManager->getCollectionArr();
			    ?>
				<hr/>
				<table id="omlisttable" cellspacing="4">
				<?php 
			    foreach($specimenArray as $collId => $specData){
			    	$isEditor = false;
			    	if($symbUid && (array_key_exists("SuperAdmin",$userRights)
					|| (array_key_exists('CollAdmin',$userRights) && in_array($collId,$userRights['CollAdmin']))
					|| (array_key_exists('CollEditor',$userRights) && in_array($collId,$userRights['CollEditor'])))){
						$isEditor = true;
					}
					$collectionData = $collectionArr[$collId];
					$instCode1 = $collectionData["institutioncode"];
					if($collectionData["collectioncode"]) $instCode1 .= ":".$collectionData["collectioncode"];
		
			    	$dispName = $collectionData["collectionname"];
			        $icon = $collectionData["icon"];
			        ?>
					<tr>
						<td colspan='4'>
							<h2>
								<a target="_blank" href="misc/collprofiles.php?collid=<?php echo $collId; ?>">
									<?php echo $dispName;?>
					        	</a>
				        	</h2>
							<hr />
						</td>
					</tr>
					<?php 
			        foreach($specData as $dbpk => $fieldArr){
						$instCode2 = "";
						if($fieldArr["institutioncode"] && $fieldArr["institutioncode"] != $collectionData["institutioncode"]){
							$instCode2 = $fieldArr["institutioncode"];
							if($fieldArr["collectioncode"]) $instCode2 .= ":".$fieldArr["collectioncode"];
						}
						?>
						<tr>
							<td rowspan="4" width='60' valign='top' align='center'>
								<a target="_blank" href="misc/collprofiles.php?collid=<?php echo $collId."&acronym=".$fieldArr["institutioncode"]; ?>">
			                    	<img align='bottom' height='25' width='25' src='../<?php echo $icon; ?>' title='<?php echo ($instCode2?$instCode2:$instCode1); ?>' Collection Statistics' />
			                    </a>
			                    <div style='font-weight:bold;font-size:75%;'>
			                    	<?php 
			                    	echo $instCode1;
									if($instCode2) echo "<br/>".$instCode2;
			                    	?>
			                    </div>
							</td>
							<td colspan='3'>
								<?php if($isEditor || ($symbUid && $symbUid == $fieldArr['observeruid'])){ ?>
								<div style="float:right;" title="Edit Occurrence Record">
									<a href="editor/occurrenceeditor.php?occid=<?php echo $fieldArr["occid"]; ?>" target="_blank">
										<img src="../images/edit.png" style="border:solid 1px gray;height:13px;" />
									</a>
								</div>
								<?php if($collManager->getClName() && $_REQUEST["targettid"]){ ?>
								<div style="float:right;cursor:pointer;" onclick="addVoucherToCl(<?php echo $fieldArr["occid"].",".$collManager->getSearchTerm("clid").",".$_REQUEST["targettid"];?>)" title="Add as <?php echo $collManager->getClName(); ?> Voucher">
									<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" />
								</div>
								<?php } ?>
								<?php } ?>
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
				            if(strlen($localStr) > 2) $localStr = substr($localStr,0, strlen($localStr) - 2);
				            ?>
				            <td colspan='3'>
				            	<?php echo $localStr; ?>
				            </td>
			            </tr>
			            <tr>
			            	<td colspan='3'>
					            <b>
					            	<a href="javascript:var puRef=window.open('individual/index.php?occid=<?php echo $fieldArr["occid"]."&clid=".$collManager->getSearchTerm("clid")."','indspec".$fieldArr["occid"]?>','toolbar=1,scrollbars=1,width=870,height=600,left=20,top=20');">
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
				echo "<div><h3>Your query produced no results. Please modify your query parameters.</h3></div>";
			}
			?>
		</div>
	    <div id="maps" style="height:400px;">
	
		    <div class="button" style="margin-top:20px;float:right;width:13px;height:13px;" title="Download Coordinate Data">
				<a href="download/downloadhandler.php?dltype=georef"><img src="../images/dl.png"/></a>
	        </div>
	        <div style='margin-top:10px;'>
	        	<h2>Google Map</h2>
	        </div>
			<div style='margin:10 0 0 20;'>
			    <a href='javascript:var popupReference=window.open("googlemap.php?clid=<?php echo $clid; ?>","gmap","toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=950,height=700,left=20,top=20");'>
			        Display coordinates in Google Map
			    </a>
			</div>
			<div style='margin:10 0 0 20;'>Google Maps is a free web mapping service application and technology provided by Google that features a 
			    map that users can pan (by dragging the mouse) and zoom (by using the mouse wheel). Collection points are 
			    displayed as colored markers that when clicked on, displays the full imformation for that collection. When 
			    multiple species are queried (separated by semi-colons in the Taxon Criteria search box), 
			    different colored markers denote each individual species. Note that the Google Map has a limit to the first 1000 georeferenced specimens for each taxon.
			</div>
	
			<div style='margin-top:10px;'>
			    <h2>Google Earth (KML)</h2>
			</div>
			<div style='margin:10 0 0 20;'>
			    <a href="googlekml.php" target="_blank">
			        Display coordinates in Google Earth 
			    </a>
			</div>
			<div style='margin:10 0 0 20;'>
			    This link creates an KML file that can be opened in the Google Earth mapping application.
			    Note that you must have <a href='http://earth.google.com/' target="_blank">
			    Google Earth</a> installed on your computer to make use of this option.
			</div>
	
	    </div>
	</div>
</div>
<?php 
	include($serverRoot."/footer.php");
?>
</body>
</html>
