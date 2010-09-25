<?php
/*
 * 15 Jan 2010
 * E.E. Gilbert
 */

include_once($serverRoot.'/config/dbconnection.php');
 
 class VoucherManager {

	private $vCon;
	private $tid;
	private $taxonName;
	private $clid;
	private $clName;
	private $voucherData;
	
	function __construct() {
		$this->vCon = MySQLiConnectionFactory::getCon("write");
 	}
	
 	function __destruct(){
 		if(!($this->vCon === false)) $this->vCon->close();
	}

 	public function setTid($t){
		$this->tid = $t;
 	}
	
	public function getTid(){
		return $this->tid;
	}
	
	public function getTaxonName(){
		return $this->taxonName;
	}
	
	public function setClid($id){
		$this->clid = $id;
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
 		$result = $this->vCon->query($sql);
		if($row = $result->fetch_object()){
			if($row->Habitat) $checklistData["habitat"] = $row->Habitat;
			if($row->Abundance) $checklistData["abundance"] = $row->Abundance;
			if($row->Notes) $checklistData["notes"] = $row->Notes;
			if($row->internalnotes) $checklistData["internalnotes"] = $row->internalnotes;
			if($row->source) $checklistData["source"] = $row->source;
			if($row->familyoverride) $checklistData["familyoverride"] = $row->familyoverride;
			if(!$this->clName) $this->clName = $row->Name;
			if(!$this->taxonName) $this->taxonName = $row->SciName;
		}
		$result->close();
		return $checklistData;
	}

	public function editClData($eArr){
		$innerSql = "";
		foreach($eArr as $k => $v){
			$innerSql .= ",".$k."=".($v?"\"".$v."\" ":"NULL ");
		}
		$sqlClUpdate = "UPDATE fmchklsttaxalink SET ".substr($innerSql,1).
			"WHERE (tid = $this->tid) AND (clid = $this->clid)";
		if(!$this->vCon->query($sqlClUpdate)){
			return "ERROR: ".$vCon->error."<br/>SQL: ".$sqlClUpdate.";<br/> ";
		}
		return "";
	}

	public function renameTaxon($newTaxon){
		$sql = "UPDATE fmchklsttaxalink SET TID = ".$newTaxon." ".
			"WHERE TID = ".$this->tid." AND CLID = ".$this->clid;
		if($this->vCon->query($sql)){
			$this->tid = $newTaxon;
			$this->taxonName = "";
		}
		else{
			$sqlTarget = "SELECT cllink.Habitat, cllink.Abundance, cllink.Notes, cllink.internalnotes, cllink.source, cllink.Nativity ".
				"FROM fmchklsttaxalink cllink WHERE TID = ".$newTaxon." AND CLID = ".$this->clid;
			$rsTarget = $this->vCon->query($sqlTarget);
			if($row = $rsTarget->fetch_object()){
				$habitatTarget = $row->Habitat; 
				$abundTarget = $row->Abundance;
				$notesTarget = $row->Notes;
				$internalNotesTarget = $row->internalnotes;
				$sourceTarget = $row->source;
				$nativeTarget = $row->Nativity;
			
				//Move all vouchers to new name
				$sqlVouch = "UPDATE fmvouchers SET TID = ".$newTaxon." ".
					"WHERE TID = ".$this->tid." AND CLID = ".$this->clid;
				$this->vCon->query($sqlVouch);
				//Delete all Vouchers that didn't transfer because they were already linked to target name
				$sqlVouchDel = "DELETE FROM fmvouchers v WHERE v.CLID = $this->clid AND v.TID = $this->tid";
				$this->vCon->query($sqlVouchDel);
				
				//Merge chklsttaxalink data
				//Harvest source (unwanted) chklsttaxalink data
				$sqlSourceCl = "SELECT ctl.Habitat, ctl.Abundance, ctl.Notes, ctl.internalnotes, ctl.source, ctl.Nativity ".
					"FROM fmchklsttaxalink ctl WHERE ctl.TID = ".$this->tid." AND ctl.CLID = ".$this->clid;
				$rsSourceCl =  $this->vCon->query($sqlSourceCl);
				if($row = $rsSourceCl->fetch_object()){
					$habitatSource = $row->Habitat;
					$abundSource = $row->Abundance;
					$notesSource = $row->Notes;
					$internalNotesSource = $row->internalnotes;
					$sourceSource = $row->source;
					$nativeSource = $row->Nativity;
				}
				$rsSourceCl->close();
				//Transfer source chklsttaxalink data to target record
				$habitatStr = $habitatTarget.(($habitatTarget && $habitatSource)?"; ":"").$habitatSource;
				$abundStr = $abundTarget.(($abundTarget && $abundSource)?"; ":"").$abundSource;
				$notesStr = $notesTarget.(($notesTarget && $notesSource)?"; ":"").$notesSource;
				$internalNotesStr = $internalNotesTarget.(($internalNotesTarget && $internalNotesSource)?"; ":"").$internalNotesSource;
				$sourceStr = $sourceTarget.(($sourceTarget && $sourceSource)?"; ":"").$sourceSource;
				$nativeStr = $nativeTarget.(($nativeTarget && $nativeSource)?"; ":"").$nativeSource;
				$sqlCl = "UPDATE fmchklsttaxalink SET Habitat = \"".$habitatStr."\", ". 
					"Abundance = \"".$abundStr."\", Notes = \"".$notesStr."\", internalnotes = \"".$internalNotesStr."\", source = \"".$sourceStr."\", Nativity = \"".$nativeStr."\" ".
					"WHERE TID = ".$newTaxon." AND CLID = ".$this->clid;
				$this->vCon->query($sqlCl);
				//Delete unwanted taxon
				$sqlDel = "DELETE FROM fmchklsttaxalink ctl WHERE ctl.CLID = $this->clid AND ctl.TID = $this->tid";
				if($this->vCon->query($sqlDel)){
					$this->tid = $newTaxon;
					$this->taxonName = "";
				}
			}
			$rsTarget->close();
		}
	}
	
	public function deleteTaxon(){
		//Delete vouchers
		$vSql = "DELETE v.* FROM fmvouchers v WHERE v.tid = ".$this->tid." AND v.clid = ".$this->clid;
		$this->vCon->query($vSql);
		//Delete checklist record 
		$sql = "DELETE ctl.* FROM fmchklsttaxalink ctl WHERE ctl.tid = $this->tid AND ctl.clid = $this->clid ";
		if(!$this->vCon->query($sql)){
			return "ERROR - Unable to delete taxon from checklist: ".$this->vCon->error;
		}
	}

	public function getVoucherData(){
		$voucherData = Array();
 		if(!$this->tid || !$this->clid) return $voucherData;
		$sql = "SELECT v.occid, v.Collector, v.Notes, v.editornotes ".
			"FROM fmvouchers v ".
			"WHERE (v.TID = ".$this->tid.") AND (v.CLID = ".$this->clid.")";
		$result = $this->vCon->query($sql);
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$voucherData[$occId]["collector"] = $row->Collector;
			$voucherData[$occId]["notes"] = $row->Notes;
			$voucherData[$occId]["editornotes"] = $row->editornotes;
		}
		$result->close();
		return $voucherData;
	}
	
	public function editVoucher($editArr){
		if($this->tid && $this->clid){
			$occId = $editArr["occid"];
			unset($editArr["occid"]);
			$setStr = "";
			foreach($editArr as $k => $v){
				$setStr .= ", ".$k." = '".$v."'";
			}
			$setStr = substr($setStr,2);
			$sqlVoucUpdate = "UPDATE fmvouchers v ".
				"SET $setStr WHERE v.occid = \"".$occId."\" AND v.TID = ".$this->tid." AND v.CLID = ".$this->clid;
			$this->vCon->query($sqlVoucUpdate);
		}
	}
	
	public function addVoucher($vOccId, $vNotes, $vEditNotes){
		if($vOccId && $this->clid){
			$status = $this->addVoucherRecord($vOccId, $vNotes, $vEditNotes);
			if($status){
				$sqlInsertCl = "INSERT INTO fmchklsttaxalink ( clid, TID ) ".
					"SELECT ".$this->clid." AS clid, o.TidInterpreted ".
					"FROM omoccurrences o WHERE o.occid = ".$vOccId;
				//echo "<div>sqlInsertCl: ".$sqlInsertCl."</div>";
				if($this->vCon->query($sqlInsertCl)){
					return $this->addVoucherRecord($vOccId, $vNotes, $vEditNotes);
				}
			}
		}
	}

	private function addVoucherRecord($vOccId, $vNotes, $vEditNotes){
		$insertArr = Array();
		//Checklist-taxon combination already exists
		$sql = "SELECT DISTINCT o.occid, o.occurrenceID, ctl.tid, ctl.clid, ".
			"CONCAT_WS('',o.recordedby, CONCAT(' (',IFNULL(o.recordnumber,o.occurrenceid),')')) AS Collector, ".
			"'".$vNotes."' AS Notes, '".$vEditNotes."' AS editnotes ".
			"FROM ((omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted) ".
			"INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid ".
			"WHERE ctl.clid = ".$this->clid." AND o.occid = ".$vOccId." AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 ".
			"LIMIT 1";
		//echo "addVoucherSql: ".$sql."<br/>";
		$rs = $this->vCon->query($sql);
		if($row = $rs->fetch_object()){
			$occId = str_replace("\"","''",$row->occid);
			$collector = str_replace("\"","''",$row->Collector);
			$notes = str_replace("\"","''",$row->Notes);
			$editNotes = str_replace("\"","''",$row->editnotes);
			
			$sqlInsert = "INSERT INTO fmvouchers ( occid, TID, CLID, Collector, Notes, editornotes ) ".
				"VALUES (\"".$occId."\",".$row->tid.",".$row->clid.",\"".$collector."\",\"".
				$notes."\",\"".$editNotes."\") ";
			//echo "<div>".$sqlInsert."</div>";
			if(!$this->vCon->query($sqlInsert)){
				$rs->close();
				return "ERROR - Voucher insert failed: ".$this->vCon->error;
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
		$sqlDel = "DELETE FROM fmvouchers WHERE occid = ".$delOid." AND TID = ".$this->tid." AND CLID = ".$this->clid;
		$this->vCon->query($sqlDel);
	}
 }
?>
 