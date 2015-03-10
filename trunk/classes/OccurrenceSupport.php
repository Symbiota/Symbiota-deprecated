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

	//Comment functions
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

	//Occurrence harvester function (occurharvester.php)
	public function exportCsvFile($postArr){
		$fieldArr = array('occid','occurrenceID','catalogNumber','otherCatalogNumbers','family','sciname','genus','specificEpithet','taxonRank',
		'infraspecificEpithet','scientificNameAuthorship','taxonRemarks','identifiedBy','dateIdentified','identificationReferences',
		'identificationRemarks','identificationQualifier','typeStatus','recordedBy','recordNumber','associatedCollectors','eventDate',
		'year','month','day','verbatimEventDate','habitat','substrate','fieldNotes','fieldnumber','occurrenceRemarks','informationWithheld',
		'associatedOccurrences','associatedTaxa','dynamicProperties','verbatimAttributes','behavior','reproductiveCondition','cultivationStatus',
		'establishmentMeans','lifeStage','sex','individualCount','samplingProtocol','samplingEffort','preparations','country','stateProvince',
		'county','municipality','locality','decimalLatitude','decimalLongitude','geodeticDatum','coordinateUncertaintyInMeters','locationRemarks',
		'verbatimCoordinates','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation','minimumDepthInMeters',
		'maximumDepthInMeters','verbatimDepth','dateEntered','dateLastModified');
		$fileName = 'specimenOutput_'.time().'.csv';
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$sql = 'SELECT '.implode(',',$fieldArr).' FROM omoccurrences WHERE occid IN() ';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$out = fopen('php://output', 'w');
			echo implode(',',$fieldArr)."\n";
			while($r = $rs->fetch_assoc()){
				fputcsv($out, $r);
			}
			fclose($out);
		}
		else{
			echo "Recordset is empty.\n";
		}
		$rs->free();
	}
	
	public function getErrorStr(){
		return $this->errorMessage;
	} 
}
?>