<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

	$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
	$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
	$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0; 
	$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:1; 
	$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
	$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$defaultLang; 

	$fcManager = new FlashcardManager();
	$fcManager->setClid($clid);
	$fcManager->setDynClid($dynClid);
	$fcManager->setTaxonFilter($taxonFilter);
	$fcManager->setThesFilter($thesFilter);
	$fcManager->setShowCommon($showCommon);
	$fcManager->setLang($lang);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Flash Cards</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/js/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		var imageArr = new Array();
		<?php 
			$urlArr = $fcManager->getImages();
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
	include($serverRoot.'/header.php');
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
									$fcManager->echoTaxonFilterList();
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
		include($serverRoot.'/footer.php');
	?>
</body>
</html>
<?php
 
 class FlashcardManager {
 	
	private $conn;
	private $clid;
	private $dynClid;
	private $taxonFilter;
	private $thesFilter = 1;
	private $showCommon = 0;
	private $lang;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function getImages(){
		//Get species list
		$sql1 = "SELECT DISTINCT t.sciname, ti.url ";
		$sql2 = "FROM ((((".($this->clid?"fmchklsttaxalink":"fmdyncltaxalink")." ctl INNER JOIN taxstatus ts ON ctl.tid = ts.tid) ".
			"INNER JOIN taxa t ON ts.".($this->thesFilter?"tidaccepted":"tid")." = t.tid) ".
			"INNER JOIN taxstatus ts1 ON ts.tidaccepted = ts1.tidaccepted) ".
			"INNER JOIN images ti ON ts1.tid = ti.tid) ";
		if($this->showCommon){
			$sql1 .= ",v.vernacularname "; 
			$sql2 .= "LEFT JOIN (SELECT vern.tid, vern.VernacularName FROM taxavernaculars vern WHERE vern.Language = '".$this->lang."' AND vern.SortSequence = 1) v ON ts.TidAccepted = v.tid ";
		}
		$sql = $sql1.$sql2."WHERE ".($this->clid?"ctl.clid = ".$this->clid:"ctl.dynclid = ".$this->dynClid)." AND ts.taxauthid = ".($this->thesFilter?$this->thesFilter:"1")." AND ts1.taxauthid = 1 AND ti.SortSequence < 90 ";
		if($this->taxonFilter) $sql .= "AND (ts.UpperTaxonomy = '".$this->taxonFilter."' OR ts.Family = '".$this->taxonFilter."' OR t.sciname Like '".$this->taxonFilter."%') ";
		$sql .= "ORDER BY t.sciname,ti.sortsequence";
		//echo $sql;
		$result = $this->conn->query($sql);
		$returnArr = Array();
		while ($row = $result->fetch_object()){
			$url = $row->url;
			if(array_key_exists("imageDomain",$GLOBALS) && substr($url,0,1)=="/"){
				$url = $GLOBALS["imageDomain"].$url;
			}
			$sciName = $row->sciname;
			if($this->showCommon && $row->vernacularname) $sciName .= " (".$row->vernacularname.")";
			if(!array_key_exists($sciName,$returnArr) || count($returnArr[$sciName]) < 10){
				$returnArr[$sciName][] = $url;
			}
		}
		$result->close();
		return $returnArr;
	}

	public function echoTaxonFilterList(){
		$returnArr = Array();
		$upperList = Array();
		$sqlFamily = "SELECT DISTINCT ts.uppertaxonomy, ".($this->clid?"IFNULL(ctl.familyoverride,ts.Family)":"ts.Family")." AS family ".
			"FROM (taxa t INNER JOIN taxstatus ts ON t.TID = ts.TID) ".
			"INNER JOIN ".($this->clid?"fmchklsttaxalink":"fmdyncltaxalink")." ctl ON t.TID = ctl.TID ".
			"WHERE (ts.taxauthid = ".($this->thesFilter?$this->thesFilter:1)." AND ctl.".
			($this->clid?"clid = ".$this->clid:"dynclid = ".$this->dynClid).") ";
		//echo $sqlFamily."<br>";
		$rsFamily = $this->conn->query($sqlFamily);
		while ($row = $rsFamily->fetch_object()){
			$returnArr[] = $row->family;
			$upperList[$row->uppertaxonomy] = "";
		}
		$rsFamily->close();
		$sqlGenus = "SELECT DISTINCT t.unitname1 ".
			"FROM taxa t INNER JOIN ".($this->clid?"fmchklsttaxalink":"fmdyncltaxalink")." ctl ON t.tid = ctl.tid ".
			"WHERE (ctl.clid = ".$this->clid.") ";
		//echo $sqlGenus."<br>";
 		$rsGenus = $this->conn->query($sqlGenus);
		while ($row = $rsGenus->fetch_object()){
			$returnArr[] = $row->unitname1;
		}
		$rsGenus->close();
		natcasesort($returnArr);
		ksort($upperList);
		$upperList["-----------------------------------------------"] = "";
		$returnArr["-----------------------------------------------"] = "";
		$returnArr = array_merge(array_keys($upperList),$returnArr);
		foreach($returnArr as $value){
			echo "<option ";
			if($this->taxonFilter && $this->taxonFilter == $value){
				echo " SELECTED";
			}
			echo ">".$value."</option>\n";
		}
	}

	public function setClid($id){
		$this->clid = $id;
	}

	public function setDynClid($id){
		$this->dynClid = $id;
	}

	public function setTaxonFilter($tValue){
		$this->taxonFilter = $tValue;
	}

	public function setThesFilter($tValue){
		$this->thesFilter = $tValue;
	}

	public function setShowCommon($sc){
		$this->showCommon = $sc;
	}

	public function setLang($l){
		$this->lang = $l;
	}
}

 ?>