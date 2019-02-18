<?php
if(isset($fpEnabled) && $fpEnabled){
	include_once('fp/FPNetworkFactory.php');
	include_once('fp/includes/symbiotahelper.php');
}

class OccurrenceEditorDeterminations extends OccurrenceEditorManager{

	public function __construct(){
 		parent::__construct();
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getDetMap($identBy, $dateIdent, $sciName){
		$retArr = array();
		$hasCurrent = 0;
		$sql = "SELECT detid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, ".
			"identificationQualifier, iscurrent, appliedstatus, identificationReferences, identificationRemarks, sortsequence ".
			"FROM omoccurdeterminations ".
			"WHERE (occid = ".$this->occid.") ORDER BY iscurrent DESC, sortsequence";
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$detId = $row->detid;
			$retArr[$detId]["identifiedby"] = $this->cleanOutStr($row->identifiedBy);
			$retArr[$detId]["dateidentified"] = $this->cleanOutStr($row->dateIdentified);
			$retArr[$detId]["sciname"] = $this->cleanOutStr($row->sciname);
			$retArr[$detId]["scientificnameauthorship"] = $this->cleanOutStr($row->scientificNameAuthorship);
			$retArr[$detId]["identificationqualifier"] = $this->cleanOutStr($row->identificationQualifier);
			if($row->iscurrent == 1) $hasCurrent = 1;
			$retArr[$detId]["iscurrent"] = $row->iscurrent;
			$retArr[$detId]["appliedstatus"] = $row->appliedstatus;
			$retArr[$detId]["identificationreferences"] = $this->cleanOutStr($row->identificationReferences);
			$retArr[$detId]["identificationremarks"] = $this->cleanOutStr($row->identificationRemarks);
			$retArr[$detId]["sortsequence"] = $row->sortsequence;
		}
		$result->free();
		if(!$hasCurrent){
			//Try to guess which is current
			foreach($retArr as $detId => $detArr){
				if($detArr['identifiedby'] == $identBy && $detArr['dateidentified'] == $dateIdent && $detArr['sciname'] == $sciName){
					$retArr[$detId]["iscurrent"] = "1";
					break;
				}
			}
		}
		
		return $retArr;
	}
	
	public function addDetermination($detArr,$isEditor){
		$status = "Determination submitted successfully!";
		if(!$this->occid) return 'ERROR: occid is null';
		$isCurrent = 0;
		if(!array_key_exists('makecurrent',$detArr)) $detArr['makecurrent'] = 0;
		if(!array_key_exists('printqueue',$detArr)) $detArr['printqueue'] = 0;
		if($detArr['makecurrent'] == 1 && $isEditor < 3){
			$isCurrent = 1;
		}
		if($isEditor == 3){
			$status = "Determination has been added successfully, but is pending approval before being activated";
		}
		$sortSeq = 1;
		if(preg_match('/([1,2]{1}\d{3})/',$detArr['dateidentified'],$matches)){
			$sortSeq = 2100-$matches[1];
		}
		if($isCurrent){
			//Set all dets for this specimen to not current
			$sqlSetCur1 = 'UPDATE omoccurdeterminations SET iscurrent = 0 WHERE appliedstatus = 1 AND occid = '.$this->occid;
			if(!$this->conn->query($sqlSetCur1)){
				$status = 'ERROR resetting dets to not current: '.$this->conn->error;
				//$status .= '; '.$sqlSetCur1;
			}
		}
		//Load new determination into omoccurdeterminations
		$sciname = $this->cleanInStr($detArr['sciname']);
		$notes = $this->cleanInStr($detArr['identificationremarks']);
		if($isEditor==3 && is_numeric($detArr['confidenceranking'])) {
			$notes .= ($notes?'; ':'').'ConfidenceRanking: '.$detArr['confidenceranking'];
		}
		$sql = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
			'identificationQualifier, iscurrent, printqueue, appliedStatus, identificationReferences, identificationRemarks, sortsequence) '.
			'VALUES ('.$this->occid.',"'.$this->cleanInStr($detArr['identifiedby']).'","'.$this->cleanInStr($detArr['dateidentified']).'","'.
			$sciname.'",'.($detArr['scientificnameauthorship']?'"'.$this->cleanInStr($detArr['scientificnameauthorship']).'"':'NULL').','.
			($detArr['identificationqualifier']?'"'.$this->cleanInStr($detArr['identificationqualifier']).'"':'NULL').','.
			$detArr['makecurrent'].','.$detArr['printqueue'].','.($isEditor==3?0:1).','.
			($detArr['identificationreferences']?'"'.$this->cleanInStr($detArr['identificationreferences']).'"':'NULL').','.
			($notes?'"'.$notes.'"':'NULL').','.
			$sortSeq.')';
		//echo "<div>".$sql."</div>";
		if($this->conn->query($sql)){
			//Create and insert Symbiota GUID for determination(UUID)
			$guid = UuidFactory::getUuidV4();
			$detId = $this->conn->insert_id;
			if(!$this->conn->query('INSERT INTO guidoccurdeterminations(guid,detid) VALUES("'.$guid.'",'.$detId.')')){
				$status .= ' (Warning: Symbiota GUID mapping #1 failed)';
			}
			//If is current, move old determination from omoccurrences to omoccurdeterminations and then load new record into omoccurrences  
			if($isCurrent){
				//If determination is already in omoccurdeterminations, INSERT will fail move omoccurrences determination to  table
				$sqlInsert = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
					'identificationQualifier, identificationReferences, identificationRemarks, sortsequence) '.
					'SELECT occid, IFNULL(identifiedby,"unknown") AS idby, IFNULL(dateidentified,"unknown") AS di, '.
					'sciname, scientificnameauthorship, identificationqualifier, identificationreferences, identificationremarks, 10 AS sortseq '.
					'FROM omoccurrences WHERE (occid = '.$this->occid.')';
				//echo "<div>".$sqlInsert."</div>";
				if($this->conn->query($sqlInsert)){
					//Create and insert Symbiota GUID for determination(UUID)
					$guid = UuidFactory::getUuidV4();
					$detId = $this->conn->insert_id;
					if(!$this->conn->query('INSERT INTO guidoccurdeterminations(guid,detid) VALUES("'.$guid.'",'.$detId.')')){
						$status .= ' (Warning: Symbiota GUID mapping #2 failed)';
					}
				}

				$tidToAdd = $detArr['tidtoadd'];
				if($tidToAdd && !is_numeric($tidToAdd)) $tidToAdd = 0;
				
				//Check to see if taxon has a locality security protection (rare, threatened, or sensitive species)
				$sStatus = 0;
				if($tidToAdd){
					$sqlSs = 'SELECT securitystatus FROM taxa WHERE (tid = '.$tidToAdd.')';
					$rsSs = $this->conn->query($sqlSs);
					if($rSs = $rsSs->fetch_object()){
						if($rSs->securitystatus == 1) $sStatus = 1;
					}
					$rsSs->free();
					if(!$sStatus){
						$sql2 = 'SELECT c.clid '.
							'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
							'INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid '.
							'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
							'INNER JOIN omoccurrences o ON c.locality = o.stateprovince '.
							'WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
							'AND (ts2.tid = '.$tidToAdd.') AND (o.occid = '.$this->occid.')';
						//echo $sql; exit;
						$rsSs2 = $this->conn->query($sql2);
						if($rsSs2->num_rows){
							$sStatus = 1;
						}
						$rsSs2->free();
					}
				}
				
				//Load new determination into omoccurrences table
				$sqlNewDet = 'UPDATE omoccurrences '.
					'SET identifiedBy = "'.$this->cleanInStr($detArr['identifiedby']).'", dateIdentified = "'.$this->cleanInStr($detArr['dateidentified']).'",'.
					'family = '.($detArr['family']?'"'.$this->cleanInStr($detArr['family']).'"':'NULL').','.
					'sciname = "'.$sciname.'",genus = NULL, specificEpithet = NULL, taxonRank = NULL, infraspecificepithet = NULL,'.
					'scientificNameAuthorship = '.($detArr['scientificnameauthorship']?'"'.$this->cleanInStr($detArr['scientificnameauthorship']).'"':'NULL').','.
					'identificationQualifier = '.($detArr['identificationqualifier']?'"'.$this->cleanInStr($detArr['identificationqualifier']).'"':'NULL').','.
					'identificationReferences = '.($detArr['identificationreferences']?'"'.$this->cleanInStr($detArr['identificationreferences']).'"':'NULL').','.
					'identificationRemarks = '.($detArr['identificationremarks']?'"'.$this->cleanInStr($detArr['identificationremarks']).'"':'NULL').', '.
					'tidinterpreted = '.($tidToAdd?$tidToAdd:'NULL').', localitysecurity = '.$sStatus.
					' WHERE (occid = '.$this->occid.')';
				//echo "<div>".$sqlNewDet."</div>";
				$this->conn->query($sqlNewDet);
				//Add identification confidence
				if(isset($detArr['confidenceranking'])){
					$idStatus = $this->editIdentificationRanking($detArr['confidenceranking'],'');
					if($idStatus) $status .= '; '.$idStatus;
				}
				//Remap images
				$sql = 'UPDATE images SET tid = '.($tidToAdd?$tidToAdd:'NULL').' WHERE (occid = '.$this->occid.')';
				//echo $sql;
				if(!$this->conn->query($sql)){
					$status = 'ERROR: Annotation added but failed to remap images to new name';
					$status .= ': '.$this->conn->error;
				}
			}
			//FP code
			global $fpEnabled;
			if(isset($fpEnabled) && $fpEnabled && isset($detArr['fpsubmit']) && $detArr['fpsubmit']) {
				$status = "Determination added successfully and submitted to Filtered Push!";
				try {
					// create an array that the annotation generator can understand from $detArr
					$annotation = fpNewDetArr($detArr);
			
					// generate rdf/xml
					$generator = FPNetworkFactory::getAnnotationGenerator();
					$rdf = $generator->generateRdfXml($annotation);
			
					// inject annotation into fp
					$network = FPNetworkFactory::getNetworkFacade();
					$response = $network->injectIntoFP($rdf);
				} 
				catch (Exception $e) {
					error_log($e->getFile() . '(' . $e->getLine() . '): ' . $e->getMessage());
					$status = "Determination added successfully but there was an error during submission to Filtered Push!";
				}
			}
		}
		else{
			$status = 'ERROR - failed to add determination: '.$this->conn->error;
		}
		return $status;
	}

	public function editDetermination($detArr){
		if(!array_key_exists('printqueue',$detArr)) $detArr['printqueue'] = 0;
		$status = "Determination editted successfully!";
		//Update determination table
		$sql = 'UPDATE omoccurdeterminations '.
			'SET identifiedBy = "'.$this->cleanInStr($detArr['identifiedby']).'", '.
			'dateIdentified = "'.$this->cleanInStr($detArr['dateidentified']).'", '.
			'sciname = "'.$this->cleanInStr($detArr['sciname']).'", '.
			'scientificNameAuthorship = '.($detArr['scientificnameauthorship']?'"'.$this->cleanInStr($detArr['scientificnameauthorship']).'"':'NULL').','.
			'identificationQualifier = '.($detArr['identificationqualifier']?'"'.$this->cleanInStr($detArr['identificationqualifier']).'"':'NULL').','.
			'identificationReferences = '.($detArr['identificationreferences']?'"'.$this->cleanInStr($detArr['identificationreferences']).'"':'NULL').','.
			'identificationRemarks = '.($detArr['identificationremarks']?'"'.$this->cleanInStr($detArr['identificationremarks']).'"':'NULL').','.
			'sortsequence = '.($detArr['sortsequence']?$detArr['sortsequence']:'10').','.
			'printqueue = '.($detArr['printqueue']?$detArr['printqueue']:'NULL').' '.
			'WHERE (detid = '.$detArr['detid'].')';
		if(!$this->conn->query($sql)){
			$status = "ERROR - failed to edit determination: ".$this->conn->error;
		}
		return $status;
	}

	public function deleteDetermination($detId){
		$status = 'Determination deleted successfully!';
		$isCurrent = 0;
		$occid = 0;

		$sql = 'SELECT * FROM omoccurdeterminations WHERE detid = '.$detId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$detArr = array();
			$isCurrent = $r['iscurrent'];
			$occid = $r['occid'];
			foreach($r as $k => $v){
				if($v) $detArr[$k] = $this->encodeStr($v);
			}
			//Archive determinations
			$detObj = json_encode($detArr);
			$sqlArchive = 'UPDATE guidoccurdeterminations '.
			'SET archivestatus = 1, archiveobj = "'.$this->cleanInStr($detObj).'" '.
			'WHERE (detid = '.$detId.')';
			$this->conn->query($sqlArchive);
		}
		
		if($isCurrent){
			$prevDetId = 0;
			$sql2 = 'SELECT detid FROM omoccurdeterminations WHERE occid = '.$occid.' AND detid <> '.$detId.' '.
			'ORDER BY detid DESC LIMIT 1 ';
			$rs = $this->conn->query($sql2);
			if($r = $rs->fetch_object()){
				$prevDetId = $r->detid;
			}
			if($prevDetId){
				$this->applyDetermination($prevDetId, 1);
			}
		}
		
		$sql = 'DELETE FROM omoccurdeterminations WHERE (detid = '.$detId.')';
		if(!$this->conn->query($sql)){
			$status = "ERROR - failed to delete determination: ".$this->conn->error;
		}

		return $status;
	}
	
	public function applyDetermination($detId, $makeCurrent){
		$statusStr = 'Determiantion has been applied';
		//Get ConfidenceRanking value
		$iqStr = '';
		$sqlcr = 'SELECT identificationremarks FROM omoccurdeterminations WHERE detid = '.$detId;
		$rscr = $this->conn->query($sqlcr);
		if($rcr = $rscr->fetch_object()){
			$iqStr = $rcr->identificationremarks;
			if(preg_match('/ConfidenceRanking: (\d{1,2})/',$iqStr,$m)){
				if($makeCurrent) $this->editIdentificationRanking($m[1],'');
				$iqStr = trim(str_replace('ConfidenceRanking: '.$m[1],'',$iqStr),' ;');				
			}
		}
		$rscr->free();
		
		//Update applied status of det
		$sql = 'UPDATE omoccurdeterminations '.
			'SET appliedstatus = 1, iscurrent = '.$makeCurrent.', '.
			'identificationremarks = '.($iqStr?'"'.$this->cleanInStr($iqStr).'"':'NULL').' WHERE detid = '.$detId;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR attempting to apply dertermiantion: '.$this->conn->error;
		}
		if($makeCurrent){
			$this->makeDeterminationCurrent($detId);
		}
		return $statusStr;
	}

	public function makeDeterminationCurrent($detId){
		$status = 'Determination is now current!';
		//Make sure determination data within omoccurrences is in omoccurdeterminations. If already there, INSERT will fail and nothing lost
		$sqlInsert = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
			'identificationQualifier, identificationReferences, identificationRemarks, sortsequence) '.
			'SELECT occid, IFNULL(identifiedby,"unknown") AS idby, '.
			'IFNULL(dateidentified,"unknown") AS iddate, sciname, scientificnameauthorship, '.
			'identificationqualifier, identificationreferences, identificationremarks, 10 AS sortseq '.
			'FROM omoccurrences WHERE (occid = '.$this->occid.')';
		if($this->conn->query($sqlInsert)){
			//Create and insert Symbiota GUID for determination(UUID)
			$guid = UuidFactory::getUuidV4();
			if(!$this->conn->query('INSERT INTO guidoccurdeterminations(guid,detid) VALUES("'.$guid.'",'.$this->conn->insert_id.')')){
				$status .= ' (Warning: Symbiota GUID mapping #1 failed)';
			}
		}
		//echo "<div>".$sqlInsert."</div>";
		//Update omoccurrences to reflect this determination
		$tid = 0;
		$sStatus = 0;
		$family = '';
		$sqlTid = 'SELECT t.tid, t.securitystatus, ts.family '.
			'FROM omoccurdeterminations d INNER JOIN taxa t ON d.sciname = t.sciname '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (d.detid = '.$detId.') AND (taxauthid = 1)';
		$rs = $this->conn->query($sqlTid);
		if($r = $rs->fetch_object()){
			$tid = $r->tid;
			$family = $r->family;
			if($r->securitystatus == 1) $sStatus = 1;
		}
		$rs->free();
		if(!$sStatus && $tid){
			$sql2 = 'SELECT c.clid '.
				'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
				'INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN omoccurrences o ON c.locality = o.stateprovince '.
				'WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
				'AND (ts2.tid = '.$tid.') AND (o.occid = '.$this->occid.')';
			//echo $sql; exit;
			$rsSs2 = $this->conn->query($sql2);
			if($rsSs2->num_rows){
				$sStatus = 1;
			}
			$rsSs2->free();
		}
		
		$sqlNewDet = 'UPDATE omoccurrences o INNER JOIN omoccurdeterminations d ON o.occid = d.occid '.
			'SET o.identifiedBy = d.identifiedBy, o.dateIdentified = d.dateIdentified,o.family = '.($family?'"'.$family.'"':'NULL').','.
			'o.sciname = d.sciname,o.genus = NULL,o.specificEpithet = NULL,o.taxonRank = NULL,o.infraspecificepithet = NULL,o.scientificname = NULL,'.
			'o.scientificNameAuthorship = d.scientificnameauthorship,o.identificationQualifier = d.identificationqualifier,'.
			'o.identificationReferences = d.identificationreferences,o.identificationRemarks = d.identificationremarks,'.
			'o.tidinterpreted = '.($tid?$tid:'NULL').', o.localitysecurity = '.$sStatus.
			' WHERE (detid = '.$detId.')';
		//echo "<div>".$sqlNewDet."</div>";
		$this->conn->query($sqlNewDet);
		//Set all dets for this specimen to not current
		$sqlSetCur1 = 'UPDATE omoccurdeterminations SET iscurrent = 0 WHERE occid = '.$this->occid;
		if(!$this->conn->query($sqlSetCur1)){
			$status = 'ERROR resetting dets to not current: '.$this->conn->error;
			//$status .= '; '.$sqlSetCur1;
		}
		//Set targetted det to current
		$sqlSetCur2 = 'UPDATE omoccurdeterminations SET iscurrent = 1 WHERE detid = '.$detId;
		if(!$this->conn->query($sqlSetCur2)){
			$status = 'ERROR setting target det to current: '.$this->conn->error;
			//$status .= '; '.$sqlSetCur2;
		}

		if($tid){
			$sql = 'UPDATE images SET tid = '.$tid.' WHERE (occid = '.$this->occid.')';
			//echo $sql;
			$this->conn->query($sql);
		}
		else{
			$status = 'ERROR: Annotation made current but failed to remap image because taxon name not linked to taxonomic thesaurus.';
		}
	}

	public function addNomAdjustment($detArr,$isEditor){
		$sql = 'SELECT identificationQualifier '.
			'FROM omoccurrences '.
			'WHERE occid = '.$this->occid;
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$detArr['identificationqualifier'] = $r->identificationQualifier;
		}
		$rs->free();
		$detArr['identifiedby'] = 'Nomenclatural Adjustment';
		$detArr['dateidentified'] = date('F').' '.date('j').', '.date('Y');
		$this->addDetermination($detArr,$isEditor);
	}
	
	public function getBulkDetRows($collid,$catNum,$sciName,$occStr){
		$retHtml = '';
		$sql = 'SELECT occid, catalogNumber, sciname, CONCAT_WS(" ",recordedby,IFNULL(recordnumber,eventdate)) AS collector, '.
			'CONCAT_WS(", ",country,stateprovince,county,locality) AS locality '.
			'FROM omoccurrences '.
			'WHERE collid = '.$collid.' ';
		if($catNum){
			$sql .= 'AND catalogNumber = "'.$catNum.'" ';
		}
		elseif($sciName){
			$sql .= 'AND sciname = "'.$sciName.'" ';
		}
		elseif($occStr){
			$sql .= 'AND occid IN('.$occStr.') ';
		}
		$sql .= 'LIMIT 400 ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$loc = $r->locality;
			if(strlen($loc) > 500) $loc = substr($loc,400);
			$retHtml .= '<tr>';
			$retHtml .= '<td><input type="checkbox" name="occid[]" value="'.$r->occid.'" checked /></td>';
			$retHtml .= '<td>';
			$retHtml .= '<a href="#" onclick="openIndPopup('.$r->occid.'); return false;">'.($r->catalogNumber?$r->catalogNumber:'[no catalog number]').'</a>';
			$retHtml .= '<a href="#" onclick="openEditorPopup('.$r->occid.'); return false;"><img src="../../images/edit.png" /></a>';
			$retHtml .= '</td>';
			$retHtml .= '<td>'.$r->sciname.'</td>';
			$retHtml .= '<td>'.$r->collector.'; '.$loc.'</td>';
			$retHtml .= '</tr>';
		}
		$rs->free();
		return $retHtml;
	}
	
	public function getCatNumArr($occStr){
		$retArr = array();
		$sql = 'SELECT catalogNumber '.
			'FROM omoccurrences '.
			'WHERE occid IN('.$occStr.') ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->catalogNumber;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getCollName(){
		return $this->collMap['collectionname'].' ('.$this->collMap['institutioncode'].($this->collMap['collectioncode']?':'.$this->collMap['collectioncode']:'').')';
	}

}
?>