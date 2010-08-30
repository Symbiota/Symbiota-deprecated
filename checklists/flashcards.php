<?php
//error_reporting(E_ALL);
 include_once('../config/symbini.php');
 include_once($serverRoot.'/config/dbconnection.php');
 header("Content-Type: text/html; charset=".$charset);

	$clid = $_REQUEST["clid"]; 
	$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0; 
	$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:1; 
	$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
	$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$defaultLang; 
	
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Flash Cards</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<script type="text/javascript">
		var imageArr = new Array();
		<?php 
			$fcManager = new FlashcardManager();
			$urlArr = $fcManager->getImages($clid,$taxonFilter,$thesFilter,$showCommon,$lang);
			if($urlArr){
	 			$sciNameStr = "\"".implode("\",\"",array_keys($urlArr))."\""; 
				echo "var sciNameArr = Array(".$sciNameStr.");\n";
				$arrCnt = 0;
				foreach($urlArr as $imgUrls){
					echo "imageArr[".$arrCnt."] = new Array('".implode("','",$imgUrls)."');\n";
					$arrCnt++;
				}
			}
			else{
				echo "var sciNameArr = Array();\n";
			}
		?>
		var toBeIdentified = new Array();
		var randomIndex = 0;
		var activeIndex = 0;
		var activeImageArr = new Array();
		var activeImageIndex = 0;
		var totalCorrect = 0;
		var totalTried = 0;
		var firstTry = true;

		function reset(){
			toBeIdentified = new Array();
			if(sciNameArr.length == 0){
				alert("Sorry, there are no images for the species list you have defined");
			}
			else{
				for(x=0;x<sciNameArr.length;x++){
					toBeIdentified[x] = x;
				}
				document.getElementById("numtotal").innerHTML = sciNameArr.length;
				insertNewImage();
			}
		}

		function insertNewImage(){
			randomIndex = Math.floor(Math.random()*toBeIdentified.length);
			activeIndex = toBeIdentified[randomIndex];
			activeImageArr = imageArr[activeIndex];
			document.getElementById("activeimage").src = activeImageArr[0];
			document.getElementById("imageanchor").href = activeImageArr[0];
			activeImageIndex = 0;
			document.getElementById("imageindex").innerHTML = 1;
			document.getElementById("imagecount").innerHTML = activeImageArr.length;
		}

		function nextImage(){
			activeImageIndex++;
			if(activeImageIndex >= activeImageArr.length){
				activeImageIndex = 0;
			}
			document.getElementById("activeimage").src = activeImageArr[activeImageIndex];
			document.getElementById("imageanchor").href = activeImageArr[activeImageIndex];
			document.getElementById("imageindex").innerHTML = activeImageIndex + 1;
			document.getElementById("imagecount").innerHTML = activeImageArr.length;
		}

		function checkId($idSelect){
			var idIndexSelected = $idSelect.value;
			totalTried++;
			if(idIndexSelected == activeIndex){
				alert("Correct! Try another");
				toBeIdentified.splice(randomIndex,1);
				document.getElementById("numcomplete").innerHTML = sciNameArr.length - toBeIdentified.length;
				if(firstTry){
					totalCorrect++;
					document.getElementById("numcorrect").innerHTML = totalCorrect;
				}
				firstTry = true;
				if(toBeIdentified.length > 0){
					insertNewImage();
					document.getElementById("scinameselect").value = "-1";
				}
				else{
					alert("Nothing left to identify. Hit reset to start again.");
				}
			}
			else{
				alert("Sorry, incorrect. Try Again.");
				firstTry = false;
			}
		}

		function tellMe(){
			window.open("../taxa/index.php?taxon="+sciNameArr[activeIndex],"activetaxon",'width=850,height=600');
			firstTry = false;
		}
		
	</script>
</head>

<body onload="reset()">
<?php
	$displayLeftMenu = (isset($checklists_flashcardsMenu)?$checklists_flashcardsMenu:"true");
	include($serverRoot."/header.php");
	if(isset($checklists_flashcardsCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_flashcardsCrumbs;
		echo " <b>".$defaultTitle." Flashcard</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<div style="width:420px;height:420px;text-align:center;">
			<div>
				<a id="imageanchor" href="">
					<img id="activeimage" src=""/ style="height:97%;max-width:450px">
				</a>
			</div>
		</div>
		<div style="width:420px;text-align:center;">
			<div style="width:100%;">
				<div style="float:left;cursor:pointer;text-align:center;" onclick="insertNewImage()">
					<img src="../images/skipthisone.jpg" title="Skip to Next Species" />
				</div>
				<div id="rightarrow" style="float:right;cursor:pointer;text-align:center;" onclick="nextImage()">
					<img src="../images/rightarrow.jpg" title="Show Next Image" /><br/>
					Image <span id="imageindex">1</span> of <span id="imagecount">?</span>
				</div>
			</div>
			<div style="clear:both;">
				<select id="scinameselect" onchange="checkId(this)">
					<option value="-1">Name of Above Organism</option>
					<option value="-2">-------------------------</option>
					<?php 
					$cnt = 0;
					foreach($urlArr as $sciName => $url){
						echo "<option value='".$cnt."'>".$sciName."</option>";
						$cnt++;
					}
				
					?>
				</select>
			</div>
			<div><span id="numcomplete">0</span> out of <span id="numtotal">0</span> Species Identified</div>
			<div><span id="numcorrect">0</span> Identified Correctly on First Try</div>
			<div style="cursor:pointer;" onclick="tellMe()">Tell Me What It Is!</div>
			<div style="margin:5px 0px 0px 60px;width:300px;">
				<form id="taxonfilterform" name="taxonfilterform" action="flashcards.php" method="GET">
					<fieldset>
					    <legend>Options</legend>
						<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
						<input type="hidden" name="thesfilter" value="<?php echo $thesFilter; ?>" />
						<input type="hidden" name="lang" value="<?php echo $lang; ?>" />
						<div>
							<select name="taxonfilter" onchange="document.getElementById('taxonfilterform').submit();">
								<option value="0">Filter Quiz by Taxonomic Group</option>
								<?php 
									$fcManager->echoTaxonFilterList($clid,$thesFilter,$taxonFilter);
								?>
							</select>
						</div>
						<div style='margin-top:3px;'>
							<?php 
								//Display Common Names: 0 = false, 1 = true 
							    if($displayCommonNames){
							    	echo "<input id=\"showcommon\" name=\"showcommon\" type=\"checkbox\" value=\"1\" ".($showCommon?"checked":"")." onchange=\"document.getElementById('taxonfilterform').submit();\"/> Display Common Names\n";
							    }
							?>
						</div>
					</fieldset>
				</form>
			</div>
			<div style="cursor:pointer;" onclick="reset()">Reset Game</div>
		</div>
	</div>
	<?php
		include($serverRoot."/footer.php");
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
<?php
 
 class FlashcardManager {

 	private function getConnection(){
 		return MySQLiConnectionFactory::getCon("readonly");
 	}
 	
	public function getImages($clid, $taxonFilter, $thesFilter = 1, $showCommon = 0, $lang){
		//Get species list
		$sql1 = "SELECT DISTINCT ts.uppertaxonomy, ts.family, t.sciname, ti.url ";
		$sql2 = "FROM ((((fmchklsttaxalink ctl INNER JOIN taxstatus ts ON ctl.tid = ts.tid) ".
			"INNER JOIN taxa t ON ts.".($thesFilter?"tidaccepted":"tid")." = t.tid) ".
			"INNER JOIN taxstatus ts1 ON ts.tidaccepted = ts1.tidaccepted) ".
			"INNER JOIN images ti ON ts1.tid = ti.tid) ";
		if($showCommon){
			$sql1 .= ",v.vernacularname "; 
			$sql2 .= "LEFT JOIN (SELECT vern.tid, vern.VernacularName FROM taxavernaculars vern WHERE vern.Language = '".$lang."' AND vern.SortSequence = 1) v ON ts.TidAccepted = v.tid ";
		}
		$sql = $sql1.$sql2."WHERE ctl.clid = $clid AND ts.taxauthid = ".($thesFilter?$thesFilter:"1")." AND ts1.taxauthid = 1 AND ti.SortSequence < 90 ";
		if($taxonFilter) $sql .= "AND (ts.UpperTaxonomy = '".$taxonFilter."' OR ts.Family = '".$taxonFilter."' OR t.sciname Like '".$taxonFilter."%') ";
		$sql .= "ORDER BY t.sciname";
		//echo $sql;
		$conn = $this->getConnection();
		$result = $conn->query($sql);
		$returnArr = Array();
		while ($row = $result->fetch_object()){
			$url = $row->url;
			if(array_key_exists("imageDomain",$GLOBALS) && substr($url,0,1)=="/"){
				$url = $GLOBALS["imageDomain"].$url;
			}
			$sciName = $row->sciname;
			if($showCommon && $row->vernacularname) $sciName .= " (".$row->vernacularname.")"; 
			$returnArr[$sciName][] = $url;
		}
		$result->close();
		$conn->close();
		return $returnArr;
	}
	
	public function echoTaxonFilterList($clid, $thesFilter = 1, $taxonFilter = 0){
		$returnArr = Array();
		$upperList = Array();
		$sqlFamily = "SELECT DISTINCT ts.UpperTaxonomy, IFNULL(ctl.familyoverride,ts.Family) AS family ".
			"FROM (taxa t INNER JOIN taxstatus ts ON t.TID = ts.TID) ".
			"INNER JOIN fmchklsttaxalink ctl ON t.TID = ctl.TID ".
			"WHERE (ts.taxauthid = ".($thesFilter?$thesFilter:1)." AND ctl.CLID = ".$clid.") ";
		//echo $sqlFamily."<br>";
		$conn = $this->getConnection();
		$rsFamily = $conn->query($sqlFamily);
		while ($row = $rsFamily->fetch_object()){
			$returnArr[] = $row->family;
			$upperList[$row->UpperTaxonomy] = "";
		}
		$rsFamily->close();
		$sqlGenus = "SELECT DISTINCT taxa.UnitName1 ".
			"FROM taxa INNER JOIN fmchklsttaxalink ON taxa.TID = chklsttaxalink.TID ".
			"WHERE (chklsttaxalink.CLID = ".$clid.") ";
		//echo $sqlGenus."<br>";
 		$rsGenus = $conn->query($sqlGenus);
		while ($row = $rsGenus->fetch_object()){
			$returnArr[] = $row->UnitName1;
		}
		$rsGenus->close();
		natcasesort($returnArr);
		ksort($upperList);
		$upperList["-----------------------------------------------"] = "";
		$returnArr["-----------------------------------------------"] = "";
		$returnArr = array_merge(array_keys($upperList),$returnArr);
		$conn->close();
		foreach($returnArr as $value){
			echo "<option ";
			if($taxonFilter && $taxonFilter == $value){
				echo " SELECTED";
			}
			echo ">".$value."</option>\n";
		}
	}
 }

 ?>