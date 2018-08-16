<?php
/*
 * This tool can be used to batch link vouchers to a checklist based on project specific query terms
 * Script will estabish voucher linkages of specimens and observations based on $numberOfVouchersToLoad variable
 * Vouchers are ranked based on certain criteria
 * Steps to use: 1) Modify checklist identifier (clid), 2) Customize criteria and code to match project specific needs, 3) Uncomment line 14, 4) Run from command line: php voucher_linker.php
 */

include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

$voucherLinker = new VoucherLinker();
$voucherLinker->linkVouchers();

class VoucherLinker {

	private $conn;
	private $clid = 4905;
	private $footprintWKT;
	private $numberOfVouchersToLoad = 5;
	private $linkSpecimens = true;
	private $linkObservations = true;
	private $excludeCultivated = true;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function linkVouchers(){
		//Uncomment following line to remove and reset existing voucher links
		$sql = 'DELETE FROM fmvouchers WHERE clid = '.$this->clid;
		if(!$this->conn->query($sql)) echo 'ERROR resetting voucher: '.$this->conn->error."\n";

		//Set footprintWKT
		$sql = 'SELECT footprintwkt FROM fmchecklists WHERE clid = '.$this->clid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->footprintwkt = $r->footprintwkt;
		}
		$rs->free();

		//Get taxa and synonyms
		$taxaArr = array();
		$sql = 'SELECT DISTINCT c.tid, ts2.tid as syntid '.
			'FROM fmchklsttaxalink c INNER JOIN taxstatus ts ON c.tid = ts.tid '.
			'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
			'WHERE ts.taxauthid = 1 AND ts2.taxauthid = 1 AND c.clid = '.$this->clid;
		//echo $sql."\n";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$taxaArr[$r->tid][] = $r->syntid;
		}
		$rs->free();

		$cnt = 1;
		foreach($taxaArr as $targetTid => $tidArr){
			if($this->linkSpecimens) $this->loadVouchers($targetTid,$tidArr,array('Preserved Specimens'));
			if($this->linkObservations) $this->loadVouchers($targetTid,$tidArr,array('General Observations','Observations'));
			if($cnt%200 == 0) echo $cnt." taxa processed \n";
			$cnt++;
		}
	}

	public function loadVouchers($targetTid,$tidArr,$collTypeArr){
		//Load vouchers
		$voucherArr = array();
		$oldest = '';
		$oldestOccid = 0;
		$newest = '';
		$newestOccid = 0;
		$sql = 'SELECT DISTINCT o.collid, o.occid, o.recordedby, o.recordnumber, o.eventdate, o.establishmentmeans, o.decimallatitude, c.colltype ';
		if($this->footprintWKT) $sql .= ',ST_Within(p.point,GeomFromText("POLYGON ((40.647273 -74.179168,40.644538 -74.187408,40.631642 -74.202514,40.606364 -74.204917,40.600108 -74.199768,40.588898 -74.206291,40.558126 -74.216247,40.558126 -74.229980,40.545083 -74.251609,40.526035 -74.246460,40.497584 -74.266372,40.488577 -74.241996,40.523426 -74.157196,40.533603 -74.111190,40.561256 -74.044585,40.532037 -73.927650,40.586030 -73.761267,40.590137 -73.750946,40.593330 -73.744916,40.593921 -73.737780,40.599690 -73.737602,40.603505 -73.738797,40.612765 -73.746497,40.610434 -73.754687,40.611264 -73.759659,40.613853 -73.764030,40.625250 -73.765205,40.637098 -73.737342,40.647762 -73.737921,40.652939 -73.725347,40.687768 -73.726291,40.721992 -73.730239,40.726936 -73.709297,40.739423 -73.701057,40.752429 -73.702430,40.796628 -73.769722,40.823912 -73.779334,40.852094 -73.751010,40.878448 -73.774872,40.890128 -73.821907,40.895497 -73.839803,40.904499 -73.840532,40.910564 -73.854351,40.900379 -73.862076,40.916856 -73.916664,40.828199 -73.964729,40.750868 -74.016914,40.693625 -74.034767,40.651962 -74.063606,40.646622 -74.110041,40.644147 -74.123859,40.641542 -74.145883,40.647533 -74.160183,40.647273 -74.179168,40.647273 -74.179168,40.647273 -74.179168))")) as inzone ';
		$sql .= 'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
		if($this->footprintWKT) $sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
		$sql .= 'WHERE (o.stateprovince = "New York") AND (o.county LIKE "Bronx%" OR o.county LIKE "Kings%" OR o.county LIKE "New York%" OR o.county LIKE "Queens%" OR o.county LIKE "Richmond%") '.
			'AND (o.tidinterpreted IN('.implode(',',$tidArr).')) AND (cultivationStatus IS NULL OR cultivationStatus = 0) AND (c.colltype IN("'.implode('","', $collTypeArr).'"))';
		//echo $sql."\n";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$ranking = 20;
			if($r->eventdate){
				$ranking++;
				if(!$oldest || $oldest < $r->eventdate){
					$oldest = $r->eventdate;
					$oldestOccid = $r->occid;
				}
				if(!$newest || $newest > $r->eventdate){
					$newest = $r->eventdate;
					$newestOccid = $r->occid;
				}
			}
			if($this->excludeCultivated && stripos($r->establishmentmeans,'cultivate') !== false){
				continue;
			}
			if($r->collid == 40) $ranking++;
			if(strpos($r->recordedby,'Atha') !== false) $ranking++;
			if($r->decimallatitude){
				$ranking++;
				if($this->footprintWKT && !$r->inzone) $ranking -= 15;
			}
			if($r->recordnumber){
				if(!preg_match('/^s\.{0,1}n\.{0,1}$/', $r->recordnumber)) $ranking++;
				if($r->recordnumber > 1000) $ranking++;
			}
			$voucherArr[$r->occid] = $ranking;
		}
		$rs->free();
		if($oldestOccid && isset($voucherArr[$oldestOccid])) $voucherArr[$oldestOccid] += 20;
		if($newestOccid && isset($voucherArr[$newestOccid])) $voucherArr[$newestOccid] += 20;

		//Sort and load vouchers
		asort($voucherArr);
		$vouchersToLoad = array_slice($voucherArr,-1*$this->numberOfVouchersToLoad,null,true);
		foreach($vouchersToLoad as $occid => $r){
			$sql2 = 'INSERT INTO fmvouchers(tid,clid,occid) VALUES('.$targetTid.','.$this->clid.','.$occid.')';
			if(!$this->conn->query($sql2)){
				echo 'ERROR loading voucher: '.$this->conn->error."\n";
				echo "\tSQL: ".$sql2."\n";
			}
		}
	}
}
?>