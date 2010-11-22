<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
include_once($serverRoot."/config/dbconnection.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Glossary</title>
	<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
	<script type="text/javascript">
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($glossary_indexMenu)?$glossary_indexMenu:"true");
	include($serverRoot."/header.php");
	if(isset($glossary_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $glossary_indexCrumbs;
		echo " <b>Glossary</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Glossary</h1>
	</div>
	
	<?php
		include($serverRoot."/footer.php");
	?>

</body>
</html>
<?php
 
 class GlossaryManager {

	private $con;

 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
 }

 ?>