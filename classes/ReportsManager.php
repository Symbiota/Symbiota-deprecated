<?php
include_once($serverRoot.'/config/dbconnection.php');

class ReportsManager{

	private $conn;

	public function __construct(){
	 	$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}
 
	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	/* 
	 * Input: JSON array 
	 * Input criteria: taxa (INT: tid), country (string), state (string), tag (string), 
	 *     idNeeded (INT: 0,1), collid (INT), photographer (INT: photographerUid), 
	 *     cntPerCatagory (INT: 0-2), start (INT), limit (INT) 
	 *     e.g. {"state": {"Arizona", "New Mexico"},"taxa":{"Pinus"}}
	 * Output: Array of images 
	 */
    public function getNewIdentByDeterminerReport(){
        $retArr = array();
        $sql = 'select count(*) as numberOfDet, identifiedby from omoccurdeterminations where dateIdentified '.
         'like \'%2013%\' or dateIdentified like \'%2014%\' and sciname like \'% %\' group by identifiedby;';

        $rs = $this->conn->query($sql);
        if($rs){
            while($r = $rs->fetch_assoc()){
                $retArr[] = $r;
            }
            $rs->free();
        }

        return $retArr;
    }

	public function getNewIdentBySpecialistReport(){
		$retArr = array();
		$sql = 'SELECT CONCAT_WS(" ", firstname, lastname) as fullname, t.sciname AS family, c.numberOfDet FROM usertaxonomy ut ' .
            'INNER JOIN users u ON ut.uid = u.uid INNER JOIN userlogin l ON u.uid = l.uid INNER JOIN taxa t ' .
            'ON ut.tid = t.tid INNER JOIN taxstatus ts ON t.tid = ts.tid INNER JOIN (SELECT ts.family, count(*) '.
            'as numberOfDet FROM omoccurdeterminations d INNER JOIN taxa t ON d.sciname = t.sciname INNER JOIN '.
            'taxstatus ts ON t.tid = ts.tid WHERE (t.rankid >= 220) AND dateIdentified LIKE \'%2013%\' OR dateIdentified '.
            'LIKE \'%2014%\' GROUP BY ts.family) c ON c.family = t.sciname GROUP BY ut.idusertaxonomy ORDER BY '.
            'u.lastname, u.firstname, t.sciname;';

		$rs = $this->conn->query($sql);
		if($rs){
            while($r = $rs->fetch_assoc()){
                $retArr[] = $r;
            }
			$rs->free();
        }

        return $retArr;
	}

    public function getNewIdentByFamilyReport(){
        $retArr = array();
        $sql = 'SELECT ts.family, count(*) as numberOfDet FROM omoccurdeterminations d INNER JOIN taxa t '.
            'ON d.sciname = t.sciname INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE (t.rankid >= 220) '.
            'AND (dateIdentified LIKE \'%2013%\' OR dateIdentified LIKE \'%2014%\') AND family '.
            'IS NOT NULL GROUP BY ts.family;';

        $rs = $this->conn->query($sql);
        if($rs){
            while($r = $rs->fetch_assoc()){
                $retArr[] = $r;
            }
            $rs->free();
        }

        return $retArr;
    }
}
?>