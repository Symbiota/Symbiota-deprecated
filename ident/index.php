<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/content/lang/ident/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
<title><?php echo $defaultTitle; ?><?php echo $LANG['IDKEY'];?></title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='Symbiota, interactive key, identification' />
</head>

<body>

	<?php
	$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
	$klManager = new KeyListManager();

	$displayLeftMenu = (isset($ident_indexMenu)?$ident_indexMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($ident_indexCrumbs)){
		echo "<div class='navpath'>";
		echo $ident_indexCrumbs;
		echo "<b>".$LANG['IDKEYLIST']."</b>";
		echo "</div>";
	}
	
	?> 
	
	<!-- This is inner text! -->
	<div id="innertext">
		<h2>Identification Keys</h2>
	    <div style="margin:20px"><?php echo $LANG['IDKEYPARA'];?>
		</div>
	                    
	    <div style='margin:20px;'>
	        <?php
	        $staticList = $klManager->getStaticChecklists();
			foreach($staticList as $projStr => $clArr){
				$pidPos = strpos($projStr,':');
				$pid = substr($projStr,0,$pidPos); 
				$projName = substr($projStr,$pidPos+1);
				echo "<div style='margin:3px 0px 0px 15px;'><a name='".$projStr."'></a>";
				echo "<h3><span style='cursor:pointer;color:#990000;' onclick='javascript:toggle(\"stcl-".$pid."\")'>";
				echo "<span class='stcl-".$pid."' style='display:none;'><img src='../images/plus_sm.png'/></span>";
				echo "<span class='stcl-".$pid."' style='display:inline;'><img src='../images/minus_sm.png'/></span>";
				echo "&nbsp;&nbsp;".$projName."</span>&nbsp;&nbsp;";
				echo "<a href='../checklists/clgmap.php?proj=".$pid."&target=keys' title='".$LANG['SHOWCHECK']."'><img src='../images/world.png' style='width:10px;border:0' /></a>";
				echo "</h3>";
				echo "<div class='stcl-".$pid."' style='display:block;'><ul>";
				foreach($clArr as $clid => $clName){
					echo "<li><a href='key.php?cl=$clid&proj=$pid&taxon=All+Species'>".$clName."</a></li>";
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
		$sql = "SELECT p.pid, p.projname, c.CLID, c.Name ".
			"FROM (fmprojects p INNER JOIN fmchklstprojlink cpl ON p.pid = cpl.pid) ".
			"INNER JOIN fmchecklists c ON cpl.clid = c.CLID ".
			"WHERE (c.Type = 'static') ".
			"ORDER BY p.projname, c.Name";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->pid.':'.$row->projname][$row->CLID] = $row->Name;
		}
		return $returnArr;
	}
	
	public function getDynamicChecklists(){
		$returnArr = Array();
		$sql = "SELECT p.pid, p.projname, c.CLID, c.Name ".
			"FROM (fmprojects p INNER JOIN fmchklstprojlink cpl ON p.pid = cpl.pid) ".
			"INNER JOIN fmchecklists c ON cpl.clid = c.CLID ".
			"WHERE (c.Type = 'dynamic') ".
			"ORDER BY p.projname, c.Name";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->pid.':'.$row->projname][$row->CLID] = $row->Name;
		}
		return $returnArr;
	}
 }

 ?>
