<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');

class TaxonomyAPIManager{

	private $conn;
	private $taxAuthId = 0;
    private $rankLimit = 0;
    private $rankLow = 0;
    private $rankHigh = 0;
    private $limit = 0;
    private $hideAuth = false;
    private $hideProtected = false;
	
	function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

    public function generateSciNameList($queryString){
        $retArr = Array();
        $sql = '';

 	    $sql = 'SELECT DISTINCT t.SciName, t.Author, t.TID '.
            'FROM taxa AS t ';
 	    if($this->taxAuthId){
            $sql .= 'INNER JOIN taxstatus AS ts ON t.tid = ts.tid ';
        }
        $sql .= 'WHERE t.SciName LIKE "'.$this->cleanInStr($queryString).'%" ';
        if($this->rankLimit){
            $sql .= 'AND t.RankId = '.$this->rankLimit.' ';
        }
        else{
            if($this->rankLow){
                $sql .= 'AND t.RankId >= '.$this->rankLow.' ';
            }
            if($this->rankHigh){
                $sql .= 'AND t.RankId <= '.$this->rankHigh.' ';
            }
        }
        if($this->taxAuthId){
            $sql .= 'AND ts.taxauthid = '.$this->taxAuthId.' ';
        }
        if($this->hideProtected){
            $sql .= 'AND t.SecurityStatus <> 2 ';
        }
        if($this->limit){
            $sql .= 'LIMIT '.$this->limit.' ';
        }
        $rs = $this->conn->query($sql);
        while ($r = $rs->fetch_object()){
            $sciName = $r->SciName.($this->hideAuth?'':' '.$r->Author);
            $retArr[$sciName]['id'] = $r->TID;
            $retArr[$sciName]['value'] = $sciName;
            $retArr[$sciName]['author'] = $r->Author;
        }

 	    return $retArr;
    }

    public function generateVernacularList($queryString){
        $retArr = Array();
        $sql = '';

        $sql = 'SELECT DISTINCT v.VernacularName '.
            'FROM taxavernaculars AS v ';
        $sql .= 'WHERE v.VernacularName LIKE "'.$this->cleanInStr($queryString).'%" ';
        if($this->limit){
            $sql .= 'LIMIT '.$this->limit.' ';
        }
        $rs = $this->conn->query($sql);
        while ($r = $rs->fetch_object()){
            $retArr[] = $r->VernacularName;
        }

        return $retArr;
    }
    
    public function generateAnynameList($queryString){
        $retArr = Array();
        $queryString = $this->cleanInStr($queryString);

        // TODO: Once map UI has LANG support, get LANG values from language file.
        $LANG = array();
        $LANG['TSTYPE_1-2'] = 'Family';
        $LANG['TSTYPE_1-3'] = 'Scientific Name';
        $LANG['TSTYPE_1-4'] = 'Higher Taxonomy';
        $LANG['TSTYPE_1-5'] = 'Common Name';
        $sql      = "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-5'].": ',v.vernacularname) AS sciname, ".
                    "                CONCAT('A:'                       ,v.vernacularname) AS snorder, ".
                    "                ''                                                   AS author, ".
                    "                'v.TID'                                              AS tid ".
                    "FROM taxavernaculars v ".
                    "WHERE v.vernacularname LIKE '%".$queryString."%' ";
        
        $sql     .= "UNION ".
                    "SELECT          CONCAT('".$LANG['TSTYPE_1-4'].": ',t.sciname       ) AS sciname, ".
                    "                CONCAT('E:'                       ,t.sciname       ) AS snorder, ".
                    "                t.Author                                             AS author, ".
                    "                t.TID                                                AS tid ".
                    "FROM taxa t ";
        if($this->taxAuthId){
            $sql .= 'INNER JOIN taxstatus AS ts ON t.tid = ts.tid ';
        }
        $sql     .= "WHERE t.sciname LIKE '%".$queryString."%' AND t.rankId > 20 AND t.rankId < 140 ";
        if($this->hideProtected){
            $sql .= 'AND t.SecurityStatus <> 2 ';
        }
        
        $sql     .= "UNION ".
                    "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-3'].": ',t.sciname       ) AS sciname, ".
                    "                CONCAT('B:'                       ,t.sciname       ) AS snorder, ".
                    "                t.Author                                             AS author, ".
                    "                t.TID                                                AS tid ".
                    "FROM taxa t ";
        if($this->taxAuthId){
            $sql .= 'INNER JOIN taxstatus AS ts ON t.tid = ts.tid ';
        }
        $sql     .= "WHERE t.sciname LIKE '%".$queryString."%' AND t.rankid > 140 ";
        if($this->hideProtected){
            $sql .= 'AND t.SecurityStatus <> 2 ';
        }
        
        $sql     .= "UNION ".
                    "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-2'].": ',ts.family       ) AS sciname, ".
                    "                CONCAT('C:'                       ,ts.family       ) AS snorder, ".
                    "                ''                                                   AS author, ".
                    "                'ts.tid'                                             AS tid ".
                    "FROM taxstatus ts ".
                    "WHERE ts.family LIKE '%".$queryString."%' ";
        
        $sql     .= "UNION ".
                    "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-2'].": ',t.sciname       ) AS sciname, ".
                    "                CONCAT('C:'                       ,t.sciname       ) AS snorder, ".
                    "                t.Author                                             AS author, ".
                    "                t.TID                                                AS tid ".
                    "FROM taxa t ";
        if($this->taxAuthId){
            $sql .= 'INNER JOIN taxstatus AS ts ON t.tid = ts.tid ';
        }
        $sql     .= "WHERE t.sciname LIKE '%".$queryString."%' AND rankid = 140 ";
        if($this->hideProtected){
            $sql .= 'AND t.SecurityStatus <> 2 ';
        }
        
        $sql     .= "ORDER BY snorder ";

        if($this->limit){
            $sql .= "LIMIT ".$this->limit." ";
        } else {
            $sql .= "LIMIT 30 ";
        }
        
        error_log($sql);

        $rs = $this->conn->query($sql);
        if ($rs) {
            while ($r = $rs->fetch_object()){
                $sciName = $r->sciname.($this->hideAuth?'':' '.$r->author);
                $retArr[$sciName]['id'    ] = $r->tid;
                $retArr[$sciName]['value' ] = $sciName;
                $retArr[$sciName]['author'] = $r->author;
            }
        }
        
        return $retArr;
    }
 	
	public function setTaxAuthId($val){
        $this->taxAuthId = $this->cleanInStr($val);
    }

    public function setRankLimit($val){
        $this->rankLimit = $this->cleanInStr($val);
    }

    public function setRankLow($val){
        $this->rankLow = $this->cleanInStr($val);
    }

    public function setRankHigh($val){
        $this->rankHigh = $this->cleanInStr($val);
    }

    public function setLimit($val){
        $this->limit = $this->cleanInStr($val);
    }

    public function setHideAuth($val){
        $this->hideAuth = $this->cleanInStr($val);
    }

    public function setHideProtected($val){
        $this->hideProtected = $this->cleanInStr($val);
    }
	
	protected function cleanInStr($str){
        $newStr = trim($str);
        $newStr = preg_replace('/\s\s+/', ' ',$newStr);
        $newStr = $this->conn->real_escape_string($newStr);
        return $newStr;
    }
}
?>