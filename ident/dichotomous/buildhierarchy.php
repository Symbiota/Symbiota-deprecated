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
		$sqlHier = "SELECT dk.stmtid FROM dichotomouskey dk WHERE dk.hierarchystr IS NULL";
		//echo $sqlHier;
		$resultHier = $conn->query($sqlHier);
		while($rowHier = $resultHier->fetch_object()){
			$id = $rowHier->stmtid;
			$parentArr = Array();
			$targetId = $id;
			$parCnt = 0;
			do{
				$sqlParents = "SELECT IFNULL(dk.parentstmtid,0) AS parentid FROM dichotomouskey dk WHERE dk.stmtid = ". $targetId;
				$targetId = 0;
				//echo "<div>".$sqlParents."</div>";
				$resultParent = $conn->query($sqlParents);
				if($rowParent = $resultParent->fetch_object()){
					$parentId = $rowParent->parentid;
					if($parentId) {
						$parentArr[$parentId] = $parentId;
					}
					$targetId = $parentId;
				}
				$resultParent->close();
				$parCnt++;
				if($parCnt > 15) break;
			}while($targetId);
			
			//Add hierarchy string to taxa table
			if($parentArr){
				$sqlInsert = "UPDATE dichotomouskey SET hierarchystr = '".implode(",",array_reverse($parentArr))."' WHERE stmtid = ".$id;
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