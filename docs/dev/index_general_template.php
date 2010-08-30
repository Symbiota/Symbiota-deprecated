<?php
//error_reporting(E_ALL);
 include_once("../config/symbini.php");
 include_once($serverRoot."/config/dbconnection.php");
 header("Content-Type: text/html; charset=".$charset);

 $inValue = array_key_exists("invalue",$_REQUEST)?$_REQUEST["invalue"]:""; 
 $pageManager = new PageManager();

?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> template page</title>
	<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
	<meta name="keywords" content="" />
	<script type="text/javascript">
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($basefolder_indexMenu)?$basefolder_indexMenu:"true");
	include($serverRoot."/header.php");
	if(isset($thispage_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $thispage_indexCrumbs;
		echo " <b>".$defaultTitle." Template Page</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1></h1>
        <div style="margin:20px">

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
 
 class PageManager {

	private $conn;

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	//Rest of the methods
 }

 ?>