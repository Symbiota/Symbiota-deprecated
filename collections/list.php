<?php
 include_once("../util/symbini.php");
 include_once("util/ListManager.php");
 header("Content-Type: text/html; charset=".$charset);

 $pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
 $collManager = new ListManager(); 
 
 $specimenArray = $collManager->getSpecimenMap($pageNumber);			//Array(IID,Array(fieldName,value))
?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
    <title><?php echo $defaultTitle; ?> Collections Search Results</title>
    <link rel="stylesheet" href="../css/main.css" type="text/css">
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_listMenu)?$collections_listMenu:"true");
	include($serverRoot."/util/header.php");
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
				echo "<tr>";
	            echo "<td rowspan='4' width='30' valign='top' align='center'>".
	                    "<a target='_blank' href='misc/collprofiles.php?collid=".$collId."'>".
	                    "<img align='absbottom' height='25' width='25' src='../".$icon."'/></a>".
	                    "<div style='font-weight:bold;font-size:75%;'>".$collCode."</div>".
	                    "</td>";
				echo "<td width='420' colspan='3'><a target='_blank' href='../taxa/index.php?taxon=".$fieldArr["sciname"]."'><span style='font-style:italic;font-weight:bold;'>".$fieldArr["sciname"]."</span></a> ".$fieldArr["author"]."</td>";
				echo "</tr><tr>";
				echo "<td width='75'>".$fieldArr["accession"]."</td>";
				echo "<td>".$fieldArr["collector"]."&nbsp;&nbsp;&nbsp;".$fieldArr["collnumber"]."</td>";
				echo "<td>".$fieldArr["date1"].($fieldArr["date2"]?" to ".$fieldArr["date2"]:"")."</td>";
				echo "</tr><tr>";
	            $localStr = "";
	            if($fieldArr["country"]) $localStr .= $fieldArr["country"].", ";
	            if($fieldArr["state"]) $localStr .= $fieldArr["state"].", ";
	            if($fieldArr["county"]) $localStr .= $fieldArr["county"].", ";
	            if($fieldArr["locality"]) $localStr .= $fieldArr["locality"].", ";
	            if(strlen($localStr) > 2) $localStr = substr($localStr,0, strlen($localStr) - 2);
	            echo "<td colspan='3'>".$localStr."</td>";
	            echo "</tr><tr><td colspan='3'>";
	            echo "<a href=\"javascript:var puRef=window.open('individual/individual.php?pk=".
	            $fieldArr["dbpk"]."&collid=".$collId."&clid=".$collManager->getSearchTerm("clid")."','indspec".$fieldArr["occid"]."','toolbar=1,scrollbars=1,width=650,height=600,left=20,top=20');\"> More Information </a>\n";
	            echo "</td></tr><tr><td colspan='4'><hr/></td></tr>";
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
	include($serverRoot."/util/footer.php");
?>

	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
			var pageTracker = _gat._getTracker("<?php echo $googleAnalyticsKey; ?>");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
</body>
</html>
