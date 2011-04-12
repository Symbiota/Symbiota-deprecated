<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/TaxaLoaderItisManager.php');

class TaxaLoaderManager{
	
	protected $conn;
	protected $sourceArr = Array();
	protected $targetArr = Array();
	protected $fieldMap = Array();	//target field => source field
	private $uploadFileName;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
 		set_time_limit(600);
		ini_set("max_input_time",120);
		ini_set("upload_max_filesize",10);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setUploadFile($ulFileName = ""){
		//Just read first line of file in order to map fields to uploadtaxa table
		if(!$ulFileName){
	 		$targetPath = $this->getUploadTargetPath();
			$ulFileName = $_FILES['uploadfile']['name'];
	        move_uploaded_file($_FILES['uploadfile']['tmp_name'], $targetPath.$ulFileName);
	        if(substr($ulFileName,-4) == ".zip"){
	        	$zipFileName = $ulFileName;
				$zip = new ZipArchive;
				$zip->open($targetPath.$ulFileName);
				$ulFileName = $zip->getNameIndex(0);
				$zip->extractTo($targetPath);
				$zip->close();
	        }
		}
		$this->uploadFileName = $ulFileName;
	}
	
	public function getUploadFileName(){
		return $this->uploadFileName;
	}

	public function uploadFile(){
		$statusStr = "<li>Starting Upload</li>";
		$this->conn->query("DELETE FROM uploadtaxa");
		$fh = fopen($this->getUploadTargetPath().$this->uploadFileName,'rb') or die("Can't open file");
		$headerArr = fgetcsv($fh);
		//convert all values to lowercase
		foreach($headerArr as $k => $v){
			$headerArr[$k] = strtolower($v);
		}
		$recordCnt = 0;
		if(in_array("scinameinput",$this->fieldMap)){
			$keys = array_keys($this->fieldMap);
			while($recordArr = fgetcsv($fh)){
				//Load into uploadtaxa
				$sql = "INSERT INTO uploadtaxa(".implode(",",$this->fieldMap).") ";
				$valueSql = "";
				foreach($keys as $sourceName){
					$valueSql .= '","'.trim($recordArr[array_search($sourceName,$headerArr)]);
				}
				$valueSql = str_replace('""','NULL',$valueSql);
				$sql .= "VALUES (".substr($valueSql,2)."\")";
				//echo "<div>".$sql."</div>";
				$this->conn->query($sql);
				$recordCnt++;
			}
			$statusStr .= '<li>'.$recordCnt.' taxon records uploaded</li>';
		}
		else{
			$statusStr .= '<li>ERROR: Scientific name is not mapped to &quot;scinameinput&quot;</li>';
		}
		fclose($fh);
		$this->cleanUpload();
		if(file_exists($this->getUploadTargetPath().$this->uploadFileName)) unlink($this->getUploadTargetPath().$this->uploadFileName);
		return $statusStr;
	}

	protected function cleanUpload(){
		set_time_limit(600);
		
		$sspStr = 'ssp.';$inSspStr = 'subsp.';
		$sql = 'SELECT unitind3 FROM taxa WHERE rankid = 230 AND unitind3 LIKE "s%" LIMIT 1';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$sspStr = $r->unitind3;
		}
		$rs->close();
		if($sspStr == 'subsp.') $inSspStr = 'ssp.';
		
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," '.$inSspStr.' "," '.$sspStr.' ") WHERE AcceptedStr LIKE "% '.$inSspStr.' %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," var "," var. ") WHERE AcceptedStr LIKE "% var %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") WHERE AcceptedStr LIKE "% '.substr($sspStr,0,strlen($sspStr)-1).' %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," sp.") WHERE AcceptedStr LIKE "% sp."';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = trim(AcceptedStr) WHERE AcceptedStr LIKE "% " OR AcceptedStr LIKE " %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr,"  "," ") WHERE AcceptedStr LIKE "%  %"';
		$this->conn->query($sql);
		
		//Value if only a Source Id was supplied
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceAcceptedId = u2.sourceId '.
			'SET u.AcceptedStr = u2.scinameinput '.
			'WHERE u.AcceptedStr IS NULL';
		$this->conn->query($sql);
		
		//Insert into uploadtaxa table all accepted taxa not already present in scinameinput. If they turn out to be in taxa table, they will be deleted later 
		$sql = 'INSERT INTO uploadtaxa(scinameinput) '.
			'SELECT DISTINCT u.AcceptedStr '.
			'FROM uploadtaxa u LEFT JOIN uploadtaxa ul2 ON u.AcceptedStr = ul2.scinameinput '.
			'WHERE u.AcceptedStr IS NOT NULL AND ul2.scinameinput IS NULL';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," '.$inSspStr.' "," '.$sspStr.' ") WHERE sciname like "% '.$inSspStr.' %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," var "," var. ") WHERE sciname like "% var %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") WHERE sciname like "% '.substr($sspStr,0,strlen($sspStr)-1).' %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," cf. "," ") WHERE sciname like "% cf. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," cf "," ") WHERE sciname like "% cf %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff. "," ") WHERE sciname like "% aff. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff "," ") WHERE sciname like "% aff %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," sp.","") WHERE sciname like "% sp."';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname," sp","") WHERE sciname like "% sp"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = trim(sciname) WHERE sciname like "% " OR sciname like " %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET sciname = replace(sciname,"  "," ") WHERE sciname like "%  %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," '.$inSspStr.' "," '.$sspStr.' ") '.
			'WHERE scinameinput like "% '.$inSspStr.' %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," var "," var. ") WHERE scinameinput like "% var %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," '.substr($sspStr,0,strlen($sspStr)-1).' "," '.$sspStr.' ") '.
			'WHERE scinameinput like "% '.substr($sspStr,0,strlen($sspStr)-1).' %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," cf. "," ") WHERE scinameinput like "% cf. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," cf "," ") WHERE scinameinput like "% cf %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff. "," ") WHERE scinameinput like "% aff. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff "," ") WHERE scinameinput like "% aff %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," sp.","") WHERE scinameinput like "% sp."';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput," sp","") WHERE scinameinput like "% sp"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = trim(scinameinput) WHERE scinameinput like "% " OR scinameinput like " %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET scinameinput = replace(scinameinput,"  "," ") WHERE scinameinput like "%  %"';
		$this->conn->query($sql);

		//Parse scinameinput into unitind and unitname fields 
		$sql = 'UPDATE uploadtaxa SET unitind1 = "x" WHERE unitind1 IS NULL AND scinameinput LIKE "x %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind2 = "x" WHERE unitind2 IS NULL AND scinameinput LIKE "% x %" AND scinameinput NOT LIKE "% % x %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitname1 = TRIM(substring(scinameinput,3,LOCATE(" ",scinameinput,3)-3)) '.
			'WHERE unitname1 IS NULL and scinameinput LIKE "x %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname1 = TRIM(substring(scinameinput,1,LOCATE(" ",CONCAT(scinameinput," ")))) '.
			'WHERE unitname1 IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname2 = TRIM(substring(scinameinput,LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2,LOCATE(" ",CONCAT(scinameinput," "),LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2)-(LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2))) '.
			'WHERE unitname2 IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitname2 = NULL WHERE unitname2 = ""';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind3 = "f.", rankid = 260 '.
			'WHERE unitind3 IS NULL AND (scinameinput LIKE "% f. %" OR scinameinput LIKE "% forma %") AND (rankid IS NULL OR rankid = 260)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind3 = "var.", rankid = 240 '.
			'WHERE unitind3 IS NULL AND scinameinput LIKE "% var. %" AND (rankid IS NULL OR rankid = 240)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET unitind3 = "'.$sspStr.'", rankid = 230 '.
			'WHERE unitind3 IS NULL AND scinameinput LIKE "% '.$sspStr.' %" AND (rankid IS NULL OR rankid = 230)';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET unitname3 = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(unitind3)+1, '.
			'LOCATE(" ",CONCAT(scinameinput," "),LOCATE(unitind3,scinameinput)+LENGTH(unitind3)+1)-LOCATE(unitind3,scinameinput)-LENGTH(unitind3))) '.
			'WHERE unitname3 IS NULL AND rankid > 220 ';
		$this->conn->query($sql);
		$sql = 'UPDATE IGNORE uploadtaxa '.
			'SET sciname = CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3, unitname3) '.
			'WHERE sciname IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u INNER JOIN taxa t ON u.sciname = t.sciname '.
			'SET u.tid = t.tid WHERE u.tid IS NULL';
		$this->conn->query($sql);
		
		$sql = 'UPDATE (uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname) '.
			'INNER JOIN uploadtaxa u3 ON u2.sourceParentId = u3.sourceId '.
			'SET u1.family = u3.sciname '.
			'WHERE u1.family is null AND u2.rankid = 180 AND u3.rankid = 140';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.sourceAcceptedId = u1.sourceid '.
			'SET u0.family = u1.family '.
			'WHERE u0.family IS NULL AND u1.family IS NOT NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.sciname = u1.acceptedstr '.
			'SET u0.family = u1.family '.
			'WHERE u0.family IS NULL AND u1.family IS NOT NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceParentId = u2.sourceId '.
			'SET u.parentstr = u2.sciname '.
			'WHERE u.parentstr IS NULL';
		$this->conn->query($sql);

		$sql = 'INSERT IGNORE INTO taxavernaculars (tid, VernacularName, Language, Source) '.
			'SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL';
		$this->conn->query($sql);
		$sql = 'DELETE * FROM uploadtaxa WHERE tid IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadtaxa SET rankid = 140 WHERE rankid IS NULL AND (sciname like "%aceae" || sciname like "%idae")';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET rankid = 220 WHERE rankid IS NULL AND unitname1 is not null AND unitname2 is not null';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET rankid = 180 WHERE rankid IS NULL AND unitname1 is not null AND unitname2 is null';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LENGTH(sciname)+1)) '.
			'WHERE (author IS NULL) AND rankid <= 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(CONCAT_WS(" ",unitind3,unitname3)))) '.
			'WHERE (author IS NULL) AND rankid > 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET author = NULL WHERE author = ""';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadtaxa SET family = NULL WHERE family = ""';
		$this->conn->query($sql);
		$sql = 'UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND t.rankid = 180 AND ts.family is not null AND (ut.family IS NULL OR ts.family <> ut.family)';
		$this->conn->query($sql);
		$sql = 'UPDATE ((uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
			'INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid '.
			'SET ut.family = t2.sciname '.
			'WHERE ts.taxauthid = 1 AND t.rankid = 140 AND ts.tid <> ts.tidaccepted';
		$this->conn->query($sql);

		$sql = 'UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.uppertaxonomy = ts.uppertaxonomy '.
			'WHERE ts.taxauthid = 1 AND t.rankid = 180 AND ts.uppertaxonomy IS NOT NULL AND ut.uppertaxonomy IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname) '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.uppertaxonomy = ts.uppertaxonomy '.
			'WHERE ts.taxauthid = 1 AND t.rankid = 140 AND ts.uppertaxonomy is not null AND ut.uppertaxonomy IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname '.
			'SET ut.kingdomid = t.kingdomid '.
			'WHERE t.rankid = 180 AND t.kingdomid IS NOT NULL AND ut.kingdomid IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname '.
			'SET ut.kingdomid = t.kingdomid '.
			'WHERE t.rankid = 140 AND t.kingdomid is not null AND ut.kingdomid IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa ut SET ut.kingdomid = 3 WHERE ut.kingdomid IS NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadtaxa SET parentstr = CONCAT_WS(" ", unitname1, unitname2) WHERE (parentstr IS NULL OR parentstr = "") AND rankid > 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET parentstr = unitname1 WHERE (parentstr IS NULL OR parentstr = "") AND rankid = 220';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa SET parentstr = family WHERE (parentstr IS NULL OR parentstr = "") AND rankid = 180';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET parenttid = t.tid WHERE parenttid IS NULL';
		$this->conn->query($sql);
		
		//Load into uploadtaxa parents of infrasp not yet in taxa table 
		$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, UnitName2, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 220 as r, ut.unitname1, ut.unitname2, ut.unitname1, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr <> "" AND ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid > 220 AND ut2.sciname IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE up.parenttid IS NULL';
		$this->conn->query($sql);
		
		//Load into uploadtaxa parents of species not yet in taxa table 
		$sql = 'INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 180 as r, ut.unitname1, ut.family, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr <> "" AND ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.family IS NOT NULL AND ut.rankid = 220 AND ut2.sciname IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET parenttid = t.tid '.
			'WHERE parenttid IS NULL';
		$this->conn->query($sql);

		//Load into uploadtaxa parents of genera not yet in taxa table 
		$defaultParent = 0;
		if(!$defaultParent){
			$sql = 'SELECT tid FROM taxa WHERE rankid = 10 LIMIT 1';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$defaultParent = $r->tid;
			}
			$rs->close();
		}
		$sql = 'INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, parenttid, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 140 as r, ut.parentstr, '.$defaultParent.', ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid = 180 AND ut2.sciname IS NULL';
		$this->conn->query($sql);
	}

	public function transferUpload(){
		set_time_limit(600);
		$startLoadCnt = -1;
		$endLoadCnt = 0;
		$loopCnt = 0;

		WHILE(($endLoadCnt > 0 || $startLoadCnt <> $endLoadCnt) && $loopCnt < 30){
			$sql = 'SELECT COUNT(*) AS cnt FROM uploadtaxa';
			$rs = $this->conn->query($sql);
			$r = $rs->fetch_object();
			$startLoadCnt = $r->cnt;

			$sql = 'INSERT INTO taxa ( SciName, KingdomID, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes ) '.
				'SELECT DISTINCT ut.SciName, ut.KingdomID, ut.RankId, ut.UnitInd1, ut.UnitName1, ut.UnitInd2, ut.UnitName2, ut.UnitInd3, '.
				'ut.UnitName3, ut.Author, ut.Source, ut.Notes '.
				'FROM uploadtaxa AS ut '.
				'WHERE (ut.TID Is Null AND ut.parenttid IS NOT NULL )';
			$this->conn->query($sql);
			
			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.sciname = t.sciname '.
				'SET ut.tid = t.tid WHERE ut.tid IS NULL';
			$this->conn->query($sql);
			
			$sql = 'UPDATE uploadtaxa SET tidaccepted = tid '.
				'WHERE (acceptance = 1 OR acceptance IS NULL) AND tid IS NOT NULL';
			$this->conn->query($sql);

			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.acceptedstr = t.sciname SET ut.tidaccepted = t.tid '.
				'WHERE ut.acceptance = 0 AND ut.tidaccepted IS NULL AND ut.acceptedstr IS NOT NULL';
			$this->conn->query($sql);

			$sql = 'INSERT IGNORE INTO taxstatus ( TID, TidAccepted, taxauthid, ParentTid, Family, UpperTaxonomy, UnacceptabilityReason ) '.
				'SELECT DISTINCT ut.TID, ut.TidAccepted, 1 AS taxauthid, ut.ParentTid, ut.Family, ut.UpperTaxonomy, ut.UnacceptabilityReason '.
				'FROM uploadtaxa AS ut '.
				'WHERE (ut.TID IS NOT NULL AND ut.TidAccepted IS NOT NULL AND ut.parenttid IS NOT NULL)';
			$this->conn->query($sql);

			$sql = 'INSERT IGNORE INTO taxavernaculars (tid, VernacularName, Language, Source) '.
				'SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL';
			$this->conn->query($sql);

			$sql = 'DELETE FROM uploadtaxa WHERE tid IS NOT NULL AND tidaccepted IS NOT NULL';
			$this->conn->query($sql);

			$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
				'SET up.parenttid = t.tid '.
				'WHERE up.parenttid IS NULL';
			$this->conn->query($sql);

			$sql = 'SELECT COUNT(*) as cnt FROM uploadtaxa';
			$rs = $this->conn->query($sql);
			$r = $rs->fetch_object();
			$endLoadCnt = $r->cnt;
			
			$loopCnt++;
		}

		$this->buildHierarchy();
		
		//Do some house cleaning
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL';
		$this->conn->query($sql);

		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '. 
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '. 
			'FROM omoccurrences o LEFT JOIN omoccurgeoindex g ON o.tidinterpreted = g.tid '.
			'WHERE g.tid IS NULL AND o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL';
		$this->conn->query($sql);
		
	}

	protected function buildHierarchy($taxAuthId = 1){
		do{
			unset($hArr);
			$hArr = Array();
			$tempArr = Array();
			$sql = "SELECT ts.tid FROM taxstatus ts WHERE taxauthid = $taxAuthId AND ts.hierarchystr IS NULL LIMIT 100";
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($rs->num_rows){
				while($row = $rs->fetch_object()){
					$hArr[$row->tid] = $row->tid;
				}
				do{
					unset($tempArr);
					$tempArr = Array();
					$sql2 = "SELECT IFNULL(ts.parenttid,0) AS parenttid, ts.tid ".
						"FROM taxstatus ts WHERE taxauthid = ".$taxAuthId." AND ts.tid IN(".implode(",",array_keys($hArr)).")";
					//echo $sql2."<br />";
					$rs2 = $this->conn->query($sql2);
					while($row2 = $rs2->fetch_object()){
						$tid = $row2->tid;
						$pTid = $row2->parenttid;
						if($pTid && $tid != $pTid){
							if(array_key_exists($tid,$hArr)){
								$tempArr[$pTid][$tid] = $hArr[$tid];
								unset($hArr[$tid]);
							}
						}
					}
					foreach($tempArr as $p => $c){
						$hArr[$p] = $c;
					}
					$rs2->close();
				}while($tempArr);
				//Process hierarchy strings
				$finalArr = Array();
				$finalArr = $this->getLeaves($hArr);
				foreach($finalArr as $hStr => $taxaArr){
					$sqlInsert = "UPDATE taxstatus ts ".
						"SET ts.hierarchystr = '".$hStr."' ".
						"WHERE ts.taxauthid = ".$taxAuthId." AND ts.tid IN(".implode(",",$taxaArr).") AND (ts.hierarchystr IS NULL)";
					//echo "<div>".$sqlInsert."</div>";
					$this->conn->query($sqlInsert);
				}
			}
		}while($rs->num_rows);
	}
	
	private function getLeaves($inArr,$seed=""){
		$retArr = Array();
		foreach($inArr as $p => $t){
			if(is_array($t)){
				$newSeed = $seed.",".$p;
				$retArr = array_merge($retArr,$this->getLeaves($t,$newSeed));
			}
			else{
				if(!$seed) $seed = $p;
				$retArr[substr($seed,1)][] = $t;
			}
		}
		return $retArr;
	}

	protected function getUploadTargetPath(){
		$tPath = $GLOBALS["tempDirRoot"];
		if(!$tPath){
			$tPath = $GLOBALS["serverRoot"]."/temp";
		}
		if(!file_exists($tPath."/downloads")){
			mkdir($tPath."/downloads");
		}
		if(file_exists($tPath."/downloads")){
			$tPath .= "/downloads";
		}
    	return $tPath."/";
    }

	public function setFieldMap($fm){
		$this->fieldMap = $fm;
	}
	
	public function getFieldMap(){
		return $this->fieldMap;
	}

	private function setTargetArr(){
		//Get metadata
		$sql = "SHOW COLUMNS FROM uploadtaxa";
		$rs = $this->conn->query($sql);
    	while($row = $rs->fetch_object()){
    		$field = strtolower($row->Field);
    		if(stripos($field,"tid")===false && stripos($field,"tidaccepted")===false && stripos($field,"parenttid")===false){
				$this->targetArr[] = $field;
    		}
    	}
    	$rs->close();
	}
	
	private function setSourceArr(){
		$fh = fopen($this->getUploadTargetPath().$this->uploadFileName,'rb') or die("Can't open file");
		$headerArr = fgetcsv($fh);
		$sourceArr = Array();
		foreach($headerArr as $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$sourceArr[] = $fieldStr;
			}
			else{
				break;
			}
		}
		$this->sourceArr = $sourceArr;
	}
    
	public function getTargetArr(){
		if(!$this->targetArr){
			$this->setTargetArr();
		}
		return $this->targetArr;
	}

	public function getSourceArr(){
		if(!$this->sourceArr){
			$this->setSourceArr();
		}
		return $this->sourceArr;
	}
	
 	protected function cleanField($field){
		$rStr = str_replace("\"","'",$rStr);
		return $rStr;
	}
}
?>
