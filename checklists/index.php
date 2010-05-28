<?php
//error_reporting(E_ALL);

 header("Content-Type: text/html; charset=ISO-8859-1");
 include_once("../util/dbconnection.php");
 include_once("../util/symbini.php");
 $projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
 $clManager = new ChecklistManager();
 $clManager->setProj($projValue);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Species Lists</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<meta name='keywords' content='checklists,species lists' />
	<script type="text/javascript">
		function toggle(target){
			var divObjs = document.getElementsByTagName("div");
		  	for (i = 0; i < divObjs.length; i++) {
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
		}
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($checklists_indexMenu)?$checklists_indexMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($checklists_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_indexCrumbs;
		echo " <b>".$defaultTitle." Species Lists</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Species Checklists</h1>
        <div style="margin:20px">
			Static and dynamic checklists are listed below. 
            Static checklists are pre-compiled by floristic researchers.
            This is a very controlled method of building a species list where specific specimens can be linked to serve 
            as a voucher. Vouchers are proof that the species actually occurs in the given area. If there is any doubt, one
            can inspect these specimens for verification or make changes (annotations), if necessary. 
            Dynamic checklists are generated directly from specimen data each time the checklist is accessed.
            Since these lists are built on-the-fly, they take a bit longer to display. 
            The addition or annotation of a specimen record collected within the research area will make an 
            immediate adjustment to dynamic checklists. 
		</div>

        <div style='margin:20px;'>
			<h2>Research Checklists</h2>
            <?php
                $staticList = $clManager->getStaticChecklists();
				foreach($staticList as $projStr => $clArr){
					$projId = str_replace(" ","",$projStr);
					?>
					<div style='margin:3px 0px 0px 15px;'>
						<a name="<?php echo $projStr; ?>"></a>
						<h3>
							<span style="cursor:pointer;color:#990000;" onclick="javascript:toggle('stcl-<?php echo $projId; ?>')">
								<span class="stcl-<?php echo $projId; ?>" style="display:none;">
									<img src='../images/plus.gif'/>
								</span>
								<span class="stcl-<?php echo $projId; ?>" style="display:inline;">
									<img src='../images/minus.gif'/>
								</span>&nbsp;&nbsp;
								<?php echo $projStr;?> Species Lists
							</span>&nbsp;&nbsp;
							<a href="<?php echo "clgmap.php?cltype=static&proj=".$projStr; ?>" title='Show checklists on map'>
								<img src='../images/world40.gif' style='width:10px;border:0' />
							</a>
						</h3>
						<div class="stcl-<?php echo $projId; ?>" style="display:block;">
							<ul>
								<?php 
								foreach($clArr as $clid => $clName){
									echo "<li><a href='checklist.php?cl=".$clid."'>".$clName."</a></li>\n";
								}
								?>
							</ul>
						</div>
					</div>
					<?php 
				}
			?>
		</div>
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
<?php
 
 class ChecklistManager {

	private $con;
	private $projectId;

 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function setProj($projValue){
		if(is_numeric($projValue)){
			$this->projectId = $projValue;
		}
		else{
			$sql = "SELECT p.pid FROM fmprojects p WHERE p.projname = '".$projValue."'";
			$result = $this->con->query($sql);
			if($row = $result->fetch_object()){
				$this->projectId = $row->pid;
			}
			$result->close();
		}
	}
	
	public function getProjectId(){
		return $this->projectId;
	}

	public function getStaticChecklists(){
		$returnArr = Array();
		$sql = "SELECT p.projname, c.CLID, c.Name ".
			"FROM (fmprojects p INNER JOIN fmchklstprojlink cpl ON p.pid = cpl.pid) ".
			"INNER JOIN fmchecklists c ON cpl.clid = c.CLID ".
			"WHERE (c.Type = 'static') ";
		if($this->projectId) $sql .= "AND p.pid = ".$this->projectId." ";
		$sql .= "ORDER BY p.SortSequence, p.projname, c.SortSequence, c.Name";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->projname][$row->CLID] = $row->Name;
		}
		return $returnArr;
	}
	
	public function getDynamicChecklists(){
		$returnArr = Array();
		$sql = "SELECT p.projname, c.CLID, c.Name ".
			"FROM (fmprojects p INNER JOIN fmchklstprojlink cpl ON p.pid = cpl.pid) ".
			"INNER JOIN fmchecklists c ON cpl.clid = c.CLID ".
			"WHERE (c.Type = 'dynamic') ";
		if($this->projectId) $sql .= "AND p.pid = ".$this->projectId." ";
		$sql .= "ORDER BY p.SortSequence, p.projname, c.SortSequence, c.Name";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->projname][$row->CLID] = $row->Name;
		}
		return $returnArr;
	}
 }

 ?>