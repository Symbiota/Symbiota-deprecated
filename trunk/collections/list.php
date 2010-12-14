<?php
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/OccurrenceListManager.php');
 header("Content-Type: text/html; charset=".$charset);

 $pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
 $collManager = new OccurrenceListManager();
 
 $specimenArray = $collManager->getSpecimenMap($pageNumber);			//Array(IID,Array(fieldName,value))
?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
    <title><?php echo $defaultTitle; ?> Collections Search Results</title>
    <link rel="stylesheet" href="../css/main.css" type="text/css">
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?php echo $googleAnalyticsKey; ?>']);
		_gaq.push(['_trackPageview']);
	
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_listMenu)?$collections_listMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($collections_listCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_listCrumbs;
		echo " &gt; <b>Specimen Records</b>";
		echo "</div>";
	}
	?>

<!-- This is inner text! -->
<div id="innertext">
	<div id="tabdiv">
		<div class='backendleft'>&nbsp;</div>
		<div class='backtab'><a href='checklist.php'>Species List</a></div>
		<div class="midleft" style='border-bottom:0px;height:100%;'>&nbsp;</div>
		<div class='fronttab'>Specimen List</div>
		<div class="midright" style='border-bottom:0px;height:100%;'>&nbsp;</div>
		<div class='backtab'><a href='maps/index.php<?php echo (array_key_exists("clid",$_REQUEST)?"?clid=".$_REQUEST["clid"]:"");?>'>Maps</a></div>
		<div class='backendright'>&nbsp;</div>
	</div>
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
	        $collectionData = $collectionArr[$collId];
	    	$collCode = $collectionData["collectioncode"];
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
	        	?>
				<tr>
					<td rowspan="4" width='60' valign='top' align='center'>
						<a target="_blank" href="misc/collprofiles.php?collid=<?php echo $collId; ?>">
	                    	<img align='absbottom' height='25' width='25' src='../<?php echo $icon; ?>' title='<?php echo $collCode; ?>' Collection Statistics' />
	                    </a>
	                    <div style='font-weight:bold;font-size:75%;'>
	                    	<?php echo $collCode; ?>
	                    </div>
					</td>
					<td colspan='3'>
						<div style="float:right;" title="Edit Occurrence Record">
							<a href="editor/occurrenceeditor.php?occid=<?php echo $fieldArr["occid"]; ?>" target="_blank">
								<img src="../images/edit.png" style="border:0px;height:12px;" />
							</a>
						</div>
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
			            	<a href="javascript:var puRef=window.open('individual/individual.php?occid=<?php echo $fieldArr["occid"]."&clid=".$collManager->getSearchTerm("clid")."','indspec".$fieldArr["occid"]?>','toolbar=1,scrollbars=1,width=650,height=600,left=20,top=20');">
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
<?php 
	include($serverRoot."/footer.php");
?>
</body>
</html>
