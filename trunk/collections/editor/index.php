<?php
//error_reporting(E_ALL);
include_once("../../util/symbini.php");
header("Content-Type: text/html; charset=".$charset);
 
?>
<html>
	<head>
		<title>Page</title>
		<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($collections_editor_indexMenu)?$collections_editor_indexMenu:"true");
		include($serverRoot."/util/header.php");
		if(isset($collections_editor_indexCrumbs)){
			echo "<div class='navpath'>";
			echo "<a href='../index.php'>Home</a> &gt; ";
			echo $collections_editor_indexCrumbs;
			echo " <b>Specimen Editor</b>";
			echo "</div>";
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<form name="speceditform" >
			
			
			
			
			
			</form>
		
		
		</div>
		<?php
			include("../../util/footer.php");
		?>
	</body>
</html>

<?php 
class SpecEditorHandler{

	private $conn;
	private $numericCol = Array();
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function buildForm(){
		$sql = "SHOW COLUMNS FROM omoccurrences";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$col = $row->field;
			$type = $row->field;
			$size = 0;
			preg_match('/\(\d.\)/',$type,$matches);
			if($matches && strpos($type,"varchar") !== false){
				$size = $matches[0]; 
			}
			echo "<div style=\"clear:both;\">\n";
			echo "<div style=\"float:left;width:75px;\">".$col.": </div>\n";
			echo "\t<div style=\"float:left;\">\n";
			if($type == "text"){
				echo "\t\t<textarea id=\"".$col."_elem\" name=\"".$col."\" col='45' row='3'>".$col."</texarea>\n";
			}
			else{
				echo "\t\t<input id=\"".$col."_elem\" name=\"".$col."\" value=\"".$col."\" maxsize=\"".$size."\" />\n";
			}
			if(strpos($type, "int") === 0 || strpos($type, "decimal") === 0){
				$this->numericCol[] = $col;
			}
			echo "\t</div>\n";
			echo "</div>\n";
		}
		$this->conn->close();
	}
	
	public function getNumericCol(){
		return $this->numericCol;
	}
}



?>