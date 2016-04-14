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
	public function getComments($collid, $start, $limit, $tsStart, $tsEnd, $uid, $reviewStatus){
		$retArr = array();
		if(is_numeric($collid)){
			if(!is_numeric($start)) $start = 0;
			if(!is_numeric($limit)) $limit = 100;
			$sqlBase = 'FROM omoccurcomments c INNER JOIN omoccurrences o ON c.occid = o.occid '.
				'WHERE o.collid = '.$collid;
			if(is_numeric($uid) && $uid){
				$sqlBase .= ' AND c.uid = '.$uid;
			}
			if(is_numeric($reviewStatus)){
				if($reviewStatus == 1){
					$sqlBase .= ' AND c.reviewstatus = 1 ';
				}
				elseif($reviewStatus == 2){
					$sqlBase .= ' AND c.reviewstatus = 0 ';
				}
			}
			if(preg_match('/^\d{4}-\d{2}-\d{2}/', $tsStart)){
				$sqlBase .= ' AND initialtimestamp >= "'.$tsStart.'"';
			}
			if(preg_match('/^\d{4}-\d{2}-\d{2}/', $tsEnd)){
				$sqlBase .= ' AND initialtimestamp < "'.$tsEnd.'"';
			}
			//Get count
			$sqlCnt = 'SELECT count(c.comid) as cnt '.$sqlBase;
			$rsCnt = $this->conn->query($sqlCnt);
			while($rCnt = $rsCnt->fetch_object()){
				$retArr['cnt'] = $rCnt->cnt;
			}
			$rsCnt->free();
			
			//Get records
			$sql = 'SELECT c.comid, c.occid, c.comment, c.uid, c.reviewstatus, c.parentcomid, c.initialtimestamp, '.
				'IFNULL(o.catalognumber, o.othercatalognumbers) AS catnum, o.recordedby, o.recordnumber, o.eventdate '.$sqlBase.
				' ORDER BY initialtimestamp DESC LIMIT '.$start.','.$limit;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->comid]['str'] = $r->comment;
				$retArr[$r->comid]['uid'] = $r->uid;
				$retArr[$r->comid]['rs'] = $r->reviewstatus;
				$retArr[$r->comid]['ts'] = $r->initialtimestamp;
				$retArr[$r->comid]['occid'] = $r->occid;
				$retArr[$r->comid]['occurstr'] = '<b>'.$r->catnum.'</b> <span style="margin:20px">'.$r->recordedby.' '.($r->recordnumber?' #'.$r->recordnumber:'').'</span> '.$r->eventdate;
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

	//OccurrenceSearch tool used to search for and link images to existing occurrence
 	public function getOccurrenceList($collid, $catalogNumber, $otherCatalogNumbers, $recordedBy, $recordNumber){
 		$retArr = Array();
 		if(!$catalogNumber && !$otherCatalogNumbers && !$recordedBy && !$recordNumber) return $retArr;
 		$sqlWhere = "";
 		if($collid){
 			$sqlWhere .= "AND (o.collid = ".$collid.") ";
 		}
 		if($catalogNumber){
 			$sqlWhere .= 'AND (o.catalognumber = "'.$catalogNumber.'") ';
 		}
 		if($otherCatalogNumbers){
 			$sqlWhere .= 'AND (o.othercatalognumbers = "'.$otherCatalogNumbers.'") ';
 		}
 		if($recordedBy){
 			$sqlWhere .= 'AND (o.recordedby LIKE "%'.$recordedBy.'%") ';
 		}
 		if($recordNumber){
 			$sqlWhere .= 'AND (o.recordnumber = "'.$recordNumber.'") ';
 		}
 		$sql = "SELECT occid, recordedby, recordnumber, eventdate, CONCAT_WS('; ',stateprovince, county, locality) AS locality ".
 			"FROM omoccurrences o WHERE ".substr($sqlWhere,4);
 		//echo $sql;
 		$rs = $this->conn->query($sql);
 		while($row = $rs->fetch_object()){
 			$occId = $row->occid;
 			$retArr[$occId]["recordedby"] = $row->recordedby;
 			$retArr[$occId]["recordnumber"] = $row->recordnumber;
 			$retArr[$occId]["eventdate"] = $row->eventdate;
 			$retArr[$occId]["locality"] = $row->locality;
 		}
 		$rs->free();
 		return $retArr;
 	}
 	
 	//Used by /collections/misc/occurrencesearch.php 
 	public function getCollectionArr($filter){
 		$retArr = array();
 		if(!$filter) return $retArr;
 		$sql = "SELECT collid, collectionname FROM omcollections ";
 		if($filter != 'all' && is_array($filter)) $sql .= 'WHERE collid IN('.implode(',',$filter).')';
 		$rs = $this->conn->query($sql);
 		while($row = $rs->fetch_object()){
 			$retArr[$row->collid] = $row->collectionname;
 		}
 		$rs->free();
 		asort($retArr);
 		return $retArr;
 	}

	//Occurrence harvester function (occurharvester.php)
	public function exportCsvFile($postArr){
		$fieldArr = array('occid','occurrenceID','catalogNumber','otherCatalogNumbers','family','sciname','genus','specificEpithet','taxonRank',
		'infraspecificEpithet','scientificNameAuthorship','taxonRemarks','identifiedBy','dateIdentified','identificationReferences',
		'identificationRemarks','identificationQualifier','typeStatus','recordedBy','recordNumber','associatedCollectors','eventDate',
		'year','month','day','verbatimEventDate','habitat','substrate','fieldnumber','occurrenceRemarks','informationWithheld',
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