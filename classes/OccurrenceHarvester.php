<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceHarvester {

	private $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function exportCsvFile(){
		$sql = $this->getLabelSql();
		//echo 'SQL: '.$sql;
		if($sql){
	    	$fileName = 'labeloutput_'.time().".csv";
			header ('Content-Type: text/csv');
			header ("Content-Disposition: attachment; filename=\"$fileName\""); 
			
			$rs = $this->conn->query($sql);
			if($rs){
				echo "\"occid\",\"catalogNumber\",\"family\",\"scientificName\",\"genus\",\"specificEpithet\",".
				"\"taxonRank\",\"infraspecificEpithet\",\"scientificNameAuthorship\",\"taxonRemarks\",\"identifiedBy\",".
				"\"dateIdentified\",\"identificationReferences\",\"identificationRemarks\",\"identificationQualifier\",".
	 			"\"recordedBy\",\"recordNumber\",\"associatedCollectors\",\"eventDate\",\"year\",\"month\",\"monthName\",\"day\",".
		 		"\"verbatimEventDate\",\"habitat\",\"substrate\",\"verbatimAttributes\",\"occurrenceRemarks\",".
	 			"\"associatedTaxa\",\"reproductiveCondition\",\"establishmentMeans\",\"country\",".
	 			"\"stateProvince\",\"county\",\"municipality\",\"locality\",\"decimalLatitude\",\"decimalLongitude\",".
		 		"\"geodeticDatum\",\"coordinateUncertaintyInMeters\",\"verbatimCoordinates\",".
	 			"\"minimumElevationInMeters\",\"maximumElevationInMeters\",\"verbatimElevation\",\"disposition\"\n";
				
				while($row = $rs->fetch_assoc()){
					$dupCnt = $_POST['q-'.$row['occid']];
					for($i = 0;$i < $dupCnt;$i++){
						echo $row['occid'].",\"".$row["catalognumber"]."\",\"".
							$row["family"]."\","."\"".$row["sciname"]."\",\"".$row["genus"]."\",\"".$row["specificepithet"]."\",\"".
							$row["taxonrank"]."\",\"".$row["infraspecificepithet"]."\",\"".$row["scientificnameauthorship"]."\",\"".
							$row["taxonremarks"]."\",\"".$row["identifiedby"]."\",\"".$row["dateidentified"]."\",\"".$row["identificationreferences"]."\",\"".
							$row["identificationremarks"]."\",\"".$row["identificationqualifier"]."\",\"".$row["recordedby"]."\",\"".$row["recordnumber"]."\",\"".
							$row["associatedcollectors"]."\",\"".$row["eventdate"]."\",".$row["year"].",".$row["month"].",".$row["monthname"].",".$row["day"].",\"".
							$row["verbatimeventdate"]."\",\"".$row["habitat"]."\",\"".$row["substrate"]."\",\"".
							$row["verbatimattributes"]."\",\"".
							$row["occurrenceremarks"]."\",\"".$row["associatedtaxa"]."\",\"".$row["reproductivecondition"]."\",\"".
							$row["establishmentmeans"]."\",\"".$row["country"]."\",\"".$row["stateprovince"]."\",\"".
							$row["county"]."\",\"".$row["municipality"]."\",\"".$row["locality"]."\",".$row["decimallatitude"].",".
							$row["decimallongitude"].",\"".$row["geodeticdatum"]."\",".$row["coordinateuncertaintyinmeters"].",\"".
							$row["verbatimcoordinates"]."\",".$row["minimumelevationinmeters"].",".$row["maximumelevationinmeters"].",\"".
							$row["verbatimelevation"]."\",\"".$row["disposition"]."\"\n";
					}
				}
			}
			else{
				echo "Recordset is empty.\n";
			}
	        if($rs) $rs->close();
		}
	}

}
?>