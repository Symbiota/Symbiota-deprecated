<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceUtilities.php');

class OccurrenceSupport {

	private $conn;
	private $errorMessage;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getComments($collid, $start, $limit, $tsStart, $tsEnd, $uid, $rs){
		$retArr = array();
		if($collid){
			$sqlBase = 'FROM omoccurcomments c INNER JOIN omoccurrences o ON c.occid = o.occid '.
				'WHERE o.collid = '.$collid;
			if($uid){
				$sqlBase .= ' AND uid = '.$uid;
			}
			if(is_numeric($rs)){
				$sqlBase .= ' AND reviewstatus = '.$rs;
			}
			if($tsStart){
				$tsStartStr = OccurrenceUtilities::formatDate($tsStart);
				if($tsStartStr){
					$sqlBase .= ' AND initialtimestamp >= '.$tsStartStr;
				}
			}
			if($tsEnd){
				$tsEndStr = OccurrenceUtilities::formatDate($tsEnd);
				if($tsEndStr){
					$sqlBase .= ' AND initialtimestamp < '.$tsEndStr;
				}
			}
			//Get count
			$sqlCnt = 'SELECT count(c.comid) as cnt '.$sqlBase;
			$rsCnt = $this->conn->query($sqlCnt);
			while($rCnt = $rsCnt->fetch_object()){
				$retArr['cnt'] = $rCnt->cnt;
			}
			$rsCnt->free();
			
			//Get records
			$sql = 'SELECT c.comid, c.occid, c.comment, c.uid, c.reviewstatus, c.parentcomid, c.initialtimestamp '.$sqlBase.
				' ORDER BY initialtimestamp DESC LIMIT '.$start.','.$limit;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->comid]['str'] = $r->comment;
				$retArr[$r->comid]['occid'] = $r->occid;
				$retArr[$r->comid]['uid'] = $r->uid;
				$retArr[$r->comid]['rs'] = $r->reviewstatus;
				$retArr[$r->comid]['ts'] = $r->initialtimestamp;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function hideComment($repComid){
		$status = true;
		//Set Review status to supress
		if(is_numeric($repComid)){
			if(!$this->conn->query('UPDATE omoccurcomments SET reviewstatus = 0 WHERE comid = '.$repComid)){
				$this->errorMessage = 'ERROR hiding comment: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}
	
	public function makeCommentPublic($comid){
		$status = true;
		if(is_numeric($comid)){
			if(!$this->conn->query('UPDATE omoccurcomments SET reviewstatus = 1 WHERE comid = '.$comid)){
				$this->errorMessage = 'ERROR making comment public: '.$con->error;
				$status = false;
			}
		}
		return $status;
	}

	public function deleteComment($comid){
		$status = true;
		if(is_numeric($comid)){
			$sql = 'DELETE FROM omoccurcomments WHERE comid = '.$comid;
			if(!$this->conn->query($sql)){
				$status = false;
				$this->errorMessage = 'ERROR deleting comment: '.$this->conn->error;
			}
		}
		return $status;
	}
	
	public function getCommentUsers($collid){
		$retArr = array();
		if($collid){
			$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) as userstr  '.
				'FROM omoccurcomments c INNER JOIN omoccurrences o ON c.occid = o.occid '.
				'INNER JOIN users u ON c.uid = u.uid '.
				'WHERE o.collid = '.$collid;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->uid] = $r->userstr;
			}
			$rs->free();
			asort($retArr);
		}
		return $retArr;
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

	public function getErrorStr(){
		return $this->errorMessage;
	} 
}
?>