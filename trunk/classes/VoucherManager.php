<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class VoucherManager {

	private $conn;
	private $tid;
	private $taxonName;
	private $clid;
	private $clName;
	private $voucherData;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
 	}
	
 	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

 	public function setTid($t){
		if(is_numeric($t)){
			$this->tid = $this->conn->real_escape_string($t);
		}
 	}
	
	public function getTid(){
		return $this->tid;
	}
	
	public function getTaxonName(){
		return $this->taxonName;
	}
	
	public function setClid($id){
		if(is_numeric($id)){
			$this->clid = $this->conn->real_escape_string($id);
		}
	}
	
	public function getClid(){
		return $this->clid;
	}
	
	public function getClName(){
		return $this->clName;
	}
	
	public function getChecklistData(){
 		$checklistData = Array();
 		if(!$this->tid || !$this->clid) return $checklistData; 
		$sql = "SELECT t.SciName, cllink.Habitat, cllink.Abundance, cllink.Notes, cllink.internalnotes, cllink.source, cllink.familyoverride, cl.Name ".
			"FROM (fmchecklists cl INNER JOIN fmchklsttaxalink cllink ON cl.CLID = cllink.CLID) ".
			"INNER JOIN taxa t ON cllink.TID = t.TID ".
			"WHERE ((cllink.TID = ".$this->tid.") AND (cllink.CLID = ".$this->clid."))";
 		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$checklistData["habitat"] = $row->Habitat;
			$checklistData["abundance"] = $row->Abundance;
			$checklistData["notes"] = $row->Notes;
			$checklistData["internalnotes"] = $row->internalnotes;
			$checklistData["source"] = $row->source;
			$checklistData["familyoverride"] = $row->familyoverride;
			if(!$this->clName) $this->clName = $row->Name;
			if(!$this->taxonName) $this->taxonName = $row->SciName;
		}
		$result->close();
		return $checklistData;
	}

	public function editClData($eArr){
		$innerSql = "";
		foreach($eArr as $k => $v){
			$valStr = trim($v);
			$innerSql .= ",".$k."=".($valStr?"\"".$valStr."\" ":"NULL ");
		}
		$sqlClUpdate = 'UPDATE fmchklsttaxalink SET '.substr($innerSql,1).
			'WHERE (tid = '.$this->tid.') AND (clid = '.$this->clid.')';
		if(!$this->conn->query($sqlClUpdate)){
			return "ERROR: ".$conn->error."<br/>SQL: ".$sqlClUpdate.";<br/> ";
		}
		return "";
	}

	public function renameTaxon($newTaxon){
		$nTaxon = $this->conn->real_escape_string($newTaxon);
		$sql = 'UPDATE fmchklsttaxalink SET TID = '.$nTaxon.' '.
			"WHERE (TID = ".$this->tid.") AND (CLID = ".$this->clid.')';
		if($this->conn->query($sql)){
			$this->tid = $nTaxon;
			$this->taxonName = "";
		}
		else{
			$sqlTarget = "SELECT cllink.Habitat, cllink.Abundance, cllink.Notes, cllink.internalnotes, cllink.source, cllink.Nativity ".
				"FROM fmchklsttaxalink cllink WHERE (TID = ".$nTaxon.") AND (CLID = ".$this->clid.')';
			$rsTarget = $this->conn->query($sqlTarget);
			if($row = $rsTarget->fetch_object()){
				$habitatTarget = $this->cleanInStr($row->Habitat);
				$abundTarget = $this->cleanInStr($row->Abundance);
				$notesTarget = $this->cleanInStr($row->Notes);
				$internalNotesTarget = $this->cleanInStr($row->internalnotes);
				$sourceTarget = $this->cleanInStr($row->source);
				$nativeTarget = $this->cleanInStr($row->Nativity);
			
				//Move all vouchers to new name
				$sqlVouch = "UPDATE fmvouchers SET TID = ".$nTaxon." ".
					"WHERE (TID = ".$this->tid.") AND (CLID = ".$this->clid.')';
				$this->conn->query($sqlVouch);
				//Delete all Vouchers that didn't transfer because they were already linked to target name
				$sqlVouchDel = 'DELETE FROM fmvouchers v '.
					'WHERE (v.CLID = '.$this->clid.") AND (v.TID = ".$this->tid.')';
				$this->conn->query($sqlVouchDel);
				
				//Merge chklsttaxalink data
				//Harvest source (unwanted) chklsttaxalink data
				$sqlSourceCl = "SELECT ctl.Habitat, ctl.Abundance, ctl.Notes, ctl.internalnotes, ctl.source, ctl.Nativity ".
					"FROM fmchklsttaxalink ctl WHERE (ctl.TID = ".$this->tid.") AND (ctl.CLID = ".$this->clid.')';
				$rsSourceCl =  $this->conn->query($sqlSourceCl);
				if($row = $rsSourceCl->fetch_object()){
					$habitatSource = $this->cleanInStr($row->Habitat);
					$abundSource = $this->cleanInStr($row->Abundance);
					$notesSource = $this->cleanInStr($row->Notes);
					$internalNotesSource = $this->cleanInStr($row->internalnotes);
					$sourceSource = $this->cleanInStr($row->source);
					$nativeSource = $this->cleanInStr($row->Nativity);
				}
				$rsSourceCl->close();
				//Transfer source chklsttaxalink data to target record
				$habitatStr = $habitatTarget.(($habitatTarget && $habitatSource)?"; ":"").$habitatSource;
				$abundStr = $abundTarget.(($abundTarget && $abundSource)?"; ":"").$abundSource;
				$notesStr = $notesTarget.(($notesTarget && $notesSource)?"; ":"").$notesSource;
				$internalNotesStr = $internalNotesTarget.(($internalNotesTarget && $internalNotesSource)?"; ":"").$internalNotesSource;
				$sourceStr = $sourceTarget.(($sourceTarget && $sourceSource)?"; ":"").$sourceSource;
				$nativeStr = $nativeTarget.(($nativeTarget && $nativeSource)?"; ":"").$nativeSource;
				$sqlCl = 'UPDATE fmchklsttaxalink SET Habitat = "'.$this->cleanInStr($habitatStr).'", '. 
					'Abundance = "'.$this->cleanInStr($abundStr).'", Notes = "'.$this->cleanInStr($notesStr).
					'", internalnotes = "'.$this->cleanInStr($internalNotesStr).'", source = "'.
					$this->cleanInStr($sourceStr).'", Nativity = "'.$this->cleanInStr($nativeStr).'" '.
					'WHERE (TID = '.$nTaxon.') AND (CLID = '.$this->clid.')';
				$this->conn->query($sqlCl);
				//Delete unwanted taxon
				$sqlDel = 'DELETE FROM fmchklsttaxalink ctl WHERE (ctl.CLID = '.$this->clid.') AND (ctl.TID = '.$this->tid.')';
				if($this->conn->query($sqlDel)){
					$this->tid = $nTaxon;
					$this->taxonName = '';
				}
			}
			$rsTarget->close();
		}
	}
	
	public function deleteTaxon(){
		//Delete vouchers
		$vSql = "DELETE v.* FROM fmvouchers v WHERE (v.tid = ".$this->tid.") AND (v.clid = ".$this->clid.')';
		$this->conn->query($vSql);
		//Delete checklist record 
		$sql = 'DELETE ctl.* FROM fmchklsttaxalink ctl WHERE (ctl.tid = '.$this->tid.') AND (ctl.clid = '.$this->clid.')';
		if(!$this->conn->query($sql)){
			return "ERROR - Unable to delete taxon from checklist: ".$this->conn->error;
		}
	}

	public function getVoucherData(){
		$voucherData = Array();
 		if(!$this->tid || !$this->clid) return $voucherData;
		$sql = 'SELECT v.occid, CONCAT(o.recordedby," ",IFNULL(o.recordnumber,"s.n.")) AS collector, v.notes, v.editornotes '.
			'FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid '.
			'WHERE (v.TID = '.$this->tid.') AND (v.CLID = '.$this->clid.')';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$voucherData[$occId]["collector"] = $row->collector;
			$voucherData[$occId]["notes"] = $row->notes;
			$voucherData[$occId]["editornotes"] = $row->editornotes;
		}
		$result->close();
		return $voucherData;
	}
	
	public function editVoucher($editArr){
		if($this->tid && $this->clid){
			$occId = $editArr['occid'];
			unset($editArr['occid']);
			$setStr = '';
			foreach($editArr as $k => $v){
				$setStr .= ', '.$k.' = "'.$this->cleanInStr($v).'"';
			}
			$setStr = substr($setStr,2);
			$sqlVoucUpdate = 'UPDATE fmvouchers v '.
				'SET '.$setStr.' WHERE (v.occid = "'.
				$this->conn->real_escape_string($occId).'") AND (v.TID = '.$this->tid.
				') AND (v.CLID = '.$this->clid.')';
			//echo $sqlVoucUpdate;
			$this->conn->query($sqlVoucUpdate);
		}
	}
	
	public function addVoucher($vOccId, $vNotes, $vEditNotes){
		$vNotes = $this->cleanInStr($vNotes);
		$vEditNotes = $this->cleanInStr($vEditNotes);
		if(is_numeric($vOccId)){
			if($vOccId && $this->clid){
				$status = $this->addVoucherRecord($vOccId, $vNotes, $vEditNotes);
				if($status){
					$sqlInsertCl = 'INSERT INTO fmchklsttaxalink ( clid, TID ) '.
						'SELECT '.$this->clid.' AS clid, o.TidInterpreted '.
						'FROM omoccurrences o WHERE (o.occid = '.$vOccId.')';
					//echo "<div>sqlInsertCl: ".$sqlInsertCl."</div>";
					if($this->conn->query($sqlInsertCl)){
						return $this->addVoucherRecord($vOccId, $vNotes, $vEditNotes);
					}
				}
			}
		}
	}

	private function addVoucherRecord($vOccId, $vNotes, $vEditNotes){
		$insertArr = Array();
		//Checklist-taxon combination already exists
		$sql = 'SELECT DISTINCT o.occid, ctl.tid, ctl.clid, o.recordedby, o.recordnumber, '.
			'"'.$vNotes.'" AS Notes, "'.$vEditNotes.'" AS editnotes '.
			'FROM ((omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.tid) '.
			'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted) '.
			'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '.
			'WHERE (ctl.clid = '.$this->clid.') AND (o.occid = '.
			$vOccId.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
			'LIMIT 1';
		//echo "addVoucherSql: ".$sql."<br/>";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
			$recNum = $this->cleanInStr($row->recordnumber);
			$notes = $this->cleanInStr($row->Notes);
			$editNotes = $this->cleanInStr($row->editnotes);
			
			$sqlInsert = 'INSERT INTO fmvouchers ( occid, TID, CLID, Collector, Notes, editornotes ) '.
				'VALUES ('.$occId.','.$row->tid.','.$row->clid.',"","'.
				$notes.'","'.$editNotes.'") ';
			//echo "<div>".$sqlInsert."</div>";
			if(!$this->conn->query($sqlInsert)){
				$rs->close();
				return "ERROR - Voucher insert failed: ".$this->conn->error;
			}
			else{
				$this->tid = $row->tid;
			}
			$rs->close();
			return "";
		}
		return "ERROR: Neither the target taxon nor a sysnonym is present in this checklists. Taxon needs to be added.";
	}

	public function removeVoucher($delOid){
		if(is_numeric($delOid)){
			$sqlDel = 'DELETE FROM fmvouchers WHERE occid = '.$delOid.' AND (TID = '.$this->tid.') AND (CLID = '.$this->clid.')';
			$this->conn->query($sqlDel);
		}
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
 }
?>
 