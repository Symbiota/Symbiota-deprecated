<?php
 //error_reporting(E_ALL);
 include_once('../config/symbini.php');
 include_once($serverRoot.'/config/dbconnection.php');
 header("Content-Type: text/html; charset=".$charset);
 ?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Identification Keys</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<meta name='keywords' content='Symbiota,interactive key,plants identification' />
</head>

<body>

	<?php
	$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
	$klManager = new KeyListManager();

	$displayLeftMenu = (isset($ident_indexMenu)?$ident_indexMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($ident_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $ident_indexCrumbs;
		echo "</div>";
	}
	
	?> 
	
	<!-- This is inner text! -->
	<div id="innertext">
		<h2>Identification Keys</h2>
	    <div style="margin:20px">Symbiota interactive identification keys have the ability to function with any 
	    taxonomically complex species list. This enables a keying interface that can even be used with species 
	    lists dynamically generated from geo-referenced specimen data. Below is a collection of species lists 
	    that can be used with the key interface. 
		</div>
	                    
	    <div style='margin:20px;'>
	        <?php
	        $staticList = $klManager->getStaticChecklists();
			foreach($staticList as $projStr => $clArr){
				$projId = str_replace(" ","",$projStr);
				echo "<div style='margin:3px 0px 0px 15px;'><a name='".$projStr."'></a>";
				echo "<h3><span style='cursor:pointer;color:#990000;' onclick='javascript:toggle(\"stcl-".$projId."\")'>";
				echo "<span class='stcl-".$projId."' style='display:none;'><img src='../images/plus.gif'/></span>";
				echo "<span class='stcl-".$projId."' style='display:inline;'><img src='../images/minus.gif'/></span>";
				echo "&nbsp;&nbsp;".$projStr."</span>&nbsp;&nbsp;";
				echo "<a href='clgmap.php?proj=".$projStr."' title='Show checklists on map'><img src='../images/world40.gif' style='width:10px;border:0' /></a>";
				echo "</h3>";
				echo "<div class='stcl-".$projId."' style='display:block;'><ul>";
				foreach($clArr as $clid => $clName){
					echo "<li><a href='key.php?cl=$clid&proj=$projStr&taxon=All+Species'>".$clName."</a></li>";
				}
				echo "</ul></div>";
				echo "</div>";
			}
			?>
		</div>
	</div>
	<?php 
		include($serverRoot.'/footer.php');
	?>
	
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
 
 class KeyListManager {

	private $con;

 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}

	public function getStaticChecklists(){
		$returnArr = Array();
		$sql = "SELECT p.projname, c.CLID, c.Name ".
			"FROM (fmprojects p INNER JOIN fmchklstprojlink cpl ON p.pid = cpl.pid) ".
			"INNER JOIN fmchecklists c ON cpl.clid = c.CLID ".
			"WHERE (c.Type = 'static') ".
			"ORDER BY p.projname, c.Name";
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
			"WHERE (c.Type = 'dynamic') ".
			"ORDER BY p.projname, c.Name";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->projname][$row->CLID] = $row->Name;
		}
		return $returnArr;
	}
 }

 ?>
