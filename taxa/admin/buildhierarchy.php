<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$taxAuthId = (array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:1);

$hierObj = new BuildHierarchy();
$hierObj->buildNulls($taxAuthId);

class BuildHierarchy{
	
	private function getCollection(){
		return MySQLiConnectionFactory::getCon("write");
	}
	
	public function buildNulls($taxAuthId){
		$conn = $this->getCollection();
		$sqlHier = 'SELECT ts.tid FROM taxstatus ts WHERE ts.hierarchystr IS NULL AND taxauthid = '.$taxAuthId;
		//echo $sqlHier;
		$resultHier = $conn->query($sqlHier);
		$cnt = 1;
		while($rowHier = $resultHier->fetch_object()){
			$tid = $rowHier->tid;
			$parentArr = Array();
			$targetTid = $tid;
			$parCnt = 0;
			do{
				$sqlParents = 'SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE  taxauthid = '.$taxAuthId.' AND ts.tid = '. $targetTid;
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
				$sqlInsert = 'UPDATE taxstatus ts SET ts.hierarchystr = "'.implode(",",array_reverse($parentArr)).'" WHERE taxauthid = '.$taxAuthId.' AND ts.tid = '.$tid;
				//echo "<div>".$sqlInsert."</div>";
				if($conn->query($sqlInsert)){
					echo '<div>#'.$cnt.': TID processed: <a href="taxonomyeditor.php?target='.$tid.'" target="_blank">'.$tid.'</a></div>';
					$cnt++;
				}
				else{
					echo '<div>ERROR adding hierarchy: '.$this->conn->error.'</div>';
				}
				ob_flush();
				flush();
			}
			unset($parentArr);
		}
		$resultHier->close();
		$conn->close();
	}
}
?>