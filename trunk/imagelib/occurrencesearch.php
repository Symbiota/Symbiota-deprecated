<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
 
 $targetId = $_REQUEST["targetid"];
 $collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0; 
 $identifier = array_key_exists("identifier",$_REQUEST)?$_REQUEST["identifier"]:""; 
 $collector = array_key_exists("collector",$_REQUEST)?$_REQUEST["collector"]:""; 
 $collNumber = array_key_exists("collnum",$_REQUEST)?$_REQUEST["collnum"]:""; 
 
 $occManager = new OccurrenceSearch();
 
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Search Page</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<script type="text/javascript">
	    function updateParentForm(occId) {
	        opener.document.getElementById("<?php echo $targetId;?>").value = occId;
	        self.close();
	        return false;
	    }

	</script>
</head>

<body>
	<!-- This is inner text! -->
	<div id="innertext">
		<form name="occform" action="occurrencesearch.php" method="get" >
			<fieldset style="width:450px;">
				<legend><b>Voucher Search Pane</b></legend>
				<div style="clear:both;padding:2px;">
					<div style="float:left;width:130px;">Collection Id:</div>
					<div style="float:left;">
						<select name="collid">
							<option value="">Select Collection</option>
							<option value="">--------------------------------</option>
							<?php $occManager->echoCollections($collId); ?>
						</select>
					</div>
				</div>
				<div style="clear:both;padding:2px;">
					<div style="float:left;width:130px;">Identifier (GUID, Catalog #):</div>
					<div style="float:left;"><input name="identifier" type="text" /></div>
				</div>
				<div style="clear:both;padding:2px;">
					<div style="float:left;width:130px;">Collector Last Name:</div>
					<div style="float:left;"><input name="collector" type="text" /></div>
				</div>
				<div style="clear:both;padding:2px;">
					<div style="float:left;width:130px;">Collector Number:</div>
					<div style="float:left;"><input name="collnum" type="text" /></div>
				</div>
				<div style="clear:both;padding:2px;">
					<input name="action" type="submit" value="Search Occurrences" />
					<input type="hidden" name="targetid" value="<?php echo $targetId;?>" />
				</div>
			</fieldset>
		</form>
		<?php 
			$occArr = $occManager->getOccurrenceList($collId, $identifier, $collector, $collNumber);
			foreach($occArr as $occId => $vArr){
				?>
				<div style="margin:10px;">
					<?php echo "<b>OccId ".$occId.":</b> ".$vArr["occurrenceid"]."; ".$vArr["recordedby"]." [".($vArr["recordnumber"]?$vArr["recordnumber"]:"s.n.")."]; ".$vArr["locality"];?>
					<div style="margin-left:10px;cursor:pointer;color:blue;" onclick="updateParentForm('<?php echo $occId;?>')">
						Select Occurrence Record
					</div>
				</div>
				<hr />
				<?php 
			}
			if(!$occArr){
				?>
				<div style="margin:10px;">
					No records were returned. Please modify your search and try again. 
				</div>
				<?php 
			}
		?>
	</div>
</body>
</html> 

<?php
 
 class OccurrenceSearch {
    
    private $occId;
	private $conn;
    
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
    
 	public function getOccurrenceList($collId, $identifier, $collector, $collNumber){
 		$returnArr = Array();
 		if(!$identifier && !$collector && !$collNumber) return $returnArr;
 		$sql = "";
 		if($collId){
 			$sql .= "AND o.collid = ".$collId." ";
 		}
 		if($identifier){
 			$sql .= "AND (o.occurrenceId LIKE \"%".$identifier."%\" OR o.catalognumber LIKE \"%".$identifier."%\")";
 		}
 		if($collector){
 			$sql .= "AND o.recordedby LIKE '%".$collector."%' ";
 		}
 		if($collNumber){
 			$sql .= "AND o.recordnumber LIKE '%".$collNumber."%' ";
 		}
 		$sql = "SELECT o.occid, o.occurrenceid, o.recordedby, o.recordnumber, CONCAT_WS('; ',o.stateprovince, o.county, o.locality) AS locality ".
 			"FROM omoccurrences o WHERE ".substr($sql,4);
 		//echo $sql;
 		$rs = $this->conn->query($sql);
 		while($row = $rs->fetch_object()){
 			$occId = $row->occid;
 			$returnArr[$occId]["occurrenceid"] = $row->occurrenceid;
 			$returnArr[$occId]["recordedby"] = $row->recordedby;
 			$returnArr[$occId]["recordnumber"] = $row->recordnumber;
 			$returnArr[$occId]["locality"] = $row->locality;
 		}
 		$rs->close();
 		return $returnArr;
 	}
 	
 	public function echoCollections($defaultCollId){
 		$sql = "SELECT c.collid, c.collectionname FROM omcollections c ORDER BY c.collectionname";
 		$rs = $this->conn->query($sql);
 		while($row = $rs->fetch_object()){
 			echo "<option value='".$row->collid."' ".($row->collid==$defaultCollId?"SELECTED":"").">";
 			echo $row->collectionname;
 			echo "</option>";
 		}
 		$rs->close();
 	}
 }

?>

