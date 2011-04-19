<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$sciname = $conn->real_escape_string($_REQUEST["sciname"]); 
$responseStr = "";
$vTaxon = new VerifyTaxon();
$vTaxon->verifyTaxonTropicos($sciname);

//output the response
echo $responseStr;

class VerifyTaxon{

	private $conn;
	
	public function __construct() {
		//$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	public function __destruct(){
 		//if(!($this->conn === false)) $this->conn->close();
	}

	public function verifyTaxonTropicos($sciname){
		
		$urlTemplate = "http://www.tropicos.org/NameSearch.aspx?name=--TAXON--";
		$url = str_replace("--TAXON--",str_replace(" ","+",$sciname),$urlTemplate);
		if($fp = fopen($url, 'r')){
			$content = "";
			while ($line = fread($fp, 1024)){
				$content .= str_replace("\n","",$line);
			}
		}
		//Check to see if taxon exists
		if(strpos($content,"No result were found")){
			//return "ABSENT TAXON";
			echo "ABSENT TAXON";
		}
		//Taxon does exists, now get author and family
		$regExp = "\<td\>([A-Z]{1}[a-z]*aceae)\<\/td\>".
			"\<td\>\s*\<span id=\"ctl00.*\">[!]?\<\/span\>\s*\<\/td\>".
			"\<td\>\s*\<a.* href=\"\/Name\/(\d*)\"\>".ucfirst($sciname)."\<\/a\>\s*\<\/td\>".
			"\<td\>(.*)<\/td\>";
		if(preg_match("/".$regExp."/U", $content, $matches)){
			//echo "<div>whole pattern: ".$matches[0]."</div>";
			echo "<div>family: ".$matches[1]."</div>";
			echo "<div>id: ".$matches[2]."</div>";
			echo "<div>author: ".$matches[3]."</div>";
		}
	}
}
?>