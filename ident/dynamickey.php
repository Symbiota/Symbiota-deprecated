<?php
//error_reporting(E_ALL);
include_once("../util/dbconnection.php");
include_once("../util/symbini.php");
header("Content-Type: text/html; charset=".$charset);
 
 $lat = array_key_exists("lat",$_REQUEST)?$_REQUEST["lat"]:0;
 $lng = array_key_exists("lng",$_REQUEST)?$_REQUEST["lng"]:0;
 $radius = (isset($dynKeyRadius)?$dynKeyRadius:5);
 $dynKeyManager = new DynKeyManager();
 $dynPk = $dynKeyManager->createKey($lat, $lng, $radius);
 header("Location: key.php?crumburl=dynamickeymap.php&crumbtitle=Dynamic%20Key&symclid=".$dynPk."&taxon=All Species");
 
 class DynKeyManager {

	private $con;

 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}

	public function createKey($lat, $lng, $radius){
		//set_time_limit(120);
		echo "Call DynamicKey(".$lat.",".$lng.",".$radius.")";
		$result = $this->con->query("Call DynamicKey(".$lat.",".$lng.",".$radius.")");
		if($row = $result->fetch_row()){
			$dynPk = $row[0];
		}
		$result->close();
		return $dynPk;
	}
 }

 ?>