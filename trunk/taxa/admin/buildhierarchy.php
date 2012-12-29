<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$hierObj = new BuildHierarchy();
$hierObj->buildNulls();

class BuildHierarchy{
	
	private function getCollection(){
		return MySQLiConnectionFactory::getCon("write");
	}
	
	public function buildNulls(){
		$conn = $this->getCollection();
		$sqlHier = "SELECT ts.tid FROM taxstatus ts WHERE ts.hierarchystr IS NULL LIMIT 10000";
		//echo $sqlHier;
		$resultHier = $conn->query($sqlHier);
		while($rowHier = $resultHier->fetch_object()){
			$tid = $rowHier->tid;
			$parentArr = Array();
			$targetTid = $tid;
			$parCnt = 0;
			do{
				$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE ts.tid = ". $targetTid;
				$targetTid = 0;
				//echo "<div>".$sqlParents."</div>";
				$resultParent = $conn->query($sqlParents);
				if($rowParent = $resultParent->fetch_object()){
					$parentTid = $rowParent->parenttid;
					if($parentTid) {
						$parentArr[$parentTid] = $parentTid;
					}
					$targetTid = $parentTid;
				}
				$resultParent->close();
				$parCnt++;
				if($parCnt > 35) break;
			}while($targetTid);
			
			//Add hierarchy string to taxa table
			if($parentArr){
				$sqlInsert = "UPDATE taxstatus ts SET ts.hierarchystr = '".implode(",",array_reverse($parentArr))."' WHERE ts.tid = ".$tid;
				echo "<div>".$sqlInsert."</div>";
				$conn->query($sqlInsert);
			}
			unset($parentArr);
		}
		$resultHier->close();
		$conn->close();
	}
}
?>