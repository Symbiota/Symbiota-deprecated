<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class ChecklistFGExportManager {

	private $conn;
	private $clid;
	private $dynClid;
	private $childClidArr = array();
	private $pid = '';
    private $linkTable = '';
    private $sqlWhereVar = '';
    private $sqlTaxaStr = '';
	private $taxaList = Array();
    private $dataArr = Array();
	private $language = "English";
    private $index = 0;
    private $recLimit = 0;
	private $thesFilter = 1;
	private $imageLimit = 100;
	private $taxaLimit = 500;
    private $photogNameArr = array();
    private $photogIdArr = array();
    private $maxPhoto = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setClValue($clValue){
		$retStr = '';
		$clValue = $this->conn->real_escape_string($clValue);
		if(is_numeric($clValue)){
			$this->clid = $clValue;
		}
		else{
			$sql = 'SELECT c.clid FROM fmchecklists AS c WHERE (c.Name = "'.$clValue.'")';
			$rs = $this->conn->query($sql);
			if($rs){
				if($row = $rs->fetch_object()){
					$this->clid = $row->clid;
				}
				else{
					$retStr = '<h1>ERROR: invalid checklist identifier supplied ('.$clValue.')</h1>';
				}
				$rs->free();
			}
			else{
				trigger_error('ERROR setting checklist ID, SQL: '.$sql, E_USER_ERROR);
			}
		}
		//Get children checklists
		$sqlChildBase = 'SELECT clidchild FROM fmchklstchildren WHERE clid IN(';
		$sqlChild = $sqlChildBase.$this->clid.')';
		do{
			$childStr = "";
			$rsChild = $this->conn->query($sqlChild);
			while($rChild = $rsChild->fetch_object()){
				$this->childClidArr[] = $rChild->clidchild;
				$childStr .= ','.$rChild->clidchild;
			}
			$sqlChild = $sqlChildBase.substr($childStr,1).')';
		}while($childStr);
		return $retStr;
	}

	public function setDynClid($did){
		if(is_numeric($did)){
			$this->dynClid = $did;
		}
	}

    public function setSqlVars(){
        if($this->clid){
            $clidStr = $this->clid;
            if($this->childClidArr){
                $clidStr .= ','.implode(',',$this->childClidArr);
            }
            $this->linkTable = 'fmchklsttaxalink';
            $this->sqlWhereVar = '(ctl.clid IN('.$clidStr.'))';
        }
        else{
            $this->linkTable = 'fmdyncltaxalink';
            $this->sqlWhereVar = '(ctl.dynclid = '.$this->dynClid.')';
        }
    }

    public function primeDataArr(){
        $taxaArr = array();
        $sql = 'SELECT DISTINCT t.tid, ts.family, t.sciname, t.author '.
            'FROM '.$this->linkTable.' AS ctl LEFT JOIN taxstatus AS ts ON ctl.tid = ts.tid '.
            'LEFT JOIN taxa AS t ON ts.tidaccepted = t.TID '.
            'WHERE (ts.taxauthid = '.$this->thesFilter.') AND '.$this->sqlWhereVar.' ';
        if($this->index || $this->recLimit) $sql .= "LIMIT ".$this->index.",".$this->recLimit;
        //echo $sql; exit;
        $rs = $this->conn->query($sql);
        while($row = $rs->fetch_object()){
            $this->dataArr[$row->tid]["sciname"] = $row->sciname;
            $this->dataArr[$row->tid]["family"] = $row->family;
            $this->dataArr[$row->tid]["author"] = $row->author;
            $taxaArr[] = $row->tid;
        }
        $rs->free();
        $this->sqlTaxaStr = implode(',',$taxaArr);
    }

    public function primeOrderData(){
        $sql = 'SELECT te.tid, t.SciName AS taxonOrder '.
            'FROM taxaenumtree AS te LEFT JOIN taxa AS t ON te.parenttid = t.TID '.
            'WHERE te.taxauthid = '.$this->thesFilter.' AND t.RankId = 100 AND te.tid IN('.$this->sqlTaxaStr.') ';
        //echo $sql; exit;
        $rs = $this->conn->query($sql);
        while($row = $rs->fetch_object()){
            $this->dataArr[$row->tid]["order"] = $row->taxonOrder;
        }
        $rs->free();
    }

    public function primeDescData(){
        $sql = 'SELECT tdb.tid, tdb.caption, tdb.source, tds.tdsid, tds.heading, tds.statement, tds.displayheader '.
            'FROM taxadescrblock AS tdb LEFT JOIN taxadescrstmts AS tds ON tdb.tdbid = tds.tdbid '.
            'WHERE tdb.tid IN('.$this->sqlTaxaStr.') '.
            'ORDER BY tdb.tid,tdb.displaylevel,tds.sortsequence ';
        //echo $sql; exit;
        $rs = $this->conn->query($sql);
        while($row = $rs->fetch_object()){
            $heading = ($row->displayheader?strip_tags($row->heading):'');
            $statement = strip_tags($row->statement);
            $source = strip_tags($row->source);
            $this->dataArr[$row->tid]["desc"][$row->caption]['source'] = $source;
            $this->dataArr[$row->tid]["desc"][$row->caption][$row->tdsid]['heading'] = $heading;
            $this->dataArr[$row->tid]["desc"][$row->caption][$row->tdsid]['statement'] = $statement;
        }
        $rs->free();
    }

    public function primeVernaculars(){
        $sql = 'SELECT v.tid, v.VernacularName '.
            'FROM taxavernaculars AS v '.
            'WHERE v.tid IN('.$this->sqlTaxaStr.') AND (v.SortSequence < 90) AND v.`language` = "'.$this->language.'" '.
            'ORDER BY v.tid,v.SortSequence';
        //echo $sql; exit;
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $this->dataArr[$row->tid]["vern"][] = $row->VernacularName;
        }
        $result->free();
    }

    public function primeImages(){
        if($this->maxPhoto > 0){
            $photogNameStr = '';
            $photogIdStr = '';
            if($this->photogNameArr){
                $photogNameStr .= '"';
                $photogNameStr .= implode('","',$this->photogIdArr);
                $photogNameStr .= '"';
            }
            if($this->photogIdArr){
                $photogIdStr .= implode(",",$this->photogIdArr);
            }
            $sql = 'SELECT ti.tid, ti.imgid, ti.thumbnailurl, ti.url, ti.`owner`, '.
                'IFNULL(ti.photographer,IFNULL(CONCAT_WS(" ",u.firstname,u.lastname),CONCAT_WS(" ",u2.firstname,u2.lastname))) AS photographer '.
                'FROM images AS ti LEFT JOIN users AS u ON ti.photographeruid = u.uid '.
                'LEFT JOIN userlogin AS ul ON ti.username = ul.username '.
                'LEFT JOIN users AS u2 ON ul.uid = u2.uid '.
                'LEFT JOIN taxstatus AS ts ON ti.tid = ts.tid '.
                'WHERE ts.taxauthid = '.$this->thesFilter.' AND ti.tid IN('.$this->sqlTaxaStr.') AND ti.SortSequence < 500 ';
            if($photogNameStr || $photogIdStr){
                $tempSql = 'AND (';
                if($photogNameStr) $tempSql .= '(ti.photographer IN('.$photogNameStr.'))';
                if($photogNameStr && $photogIdStr) $tempSql .= ' OR ';
                if($photogIdStr) $tempSql .= '(ti.photographeruid IN('.$photogIdStr.'))';
                $tempSql .= ') ';
                $sql .= $tempSql;
            }
            $sql .= 'ORDER BY ti.tid, ti.sortsequence ';
            //echo $sql; exit;
            $i = 0;
            $currTid = 0;
            $result = $this->conn->query($sql);
            while($row = $result->fetch_object()){
                if($currTid != $row->tid){
                    $currTid = $row->tid;
                    $i = 0;
                }
                if($i < $this->maxPhoto){
                    $imgUrl = $row->thumbnailurl;
                    if((!$imgUrl || $imgUrl == 'empty') && $row->url) $imgUrl = $row->url;
                    $this->dataArr[$row->tid]["img"][$row->imgid]['id'] = $row->imgid;
                    $this->dataArr[$row->tid]["img"][$row->imgid]['owner'] = $row->owner;
                    $this->dataArr[$row->tid]["img"][$row->imgid]['photographer'] = $row->photographer;
                }
                $i++;
            }
            $result->free();
        }
	}

    public function getImageUrl($imgID){
        $imgUrl = '';
	    $sql = 'SELECT thumbnailurl, url FROM images WHERE imgid = '.$imgID.' ';
        //echo $sql; exit;
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $imgUrl = $row->thumbnailurl;
            if((!$imgUrl || $imgUrl == 'empty') && $row->url) $imgUrl = $row->url;
        }
        $result->free();
        return $imgUrl;
    }

    public function getImageDataUrl($url){
        $type = pathinfo($url, PATHINFO_EXTENSION);
        $data = file_get_contents($url);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return $base64;
    }

    public function getDescSourceList(){
        $descSourceList = Array();
        $sql = 'SELECT DISTINCT tdb.caption '.
            'FROM taxadescrblock AS tdb '.
            'WHERE tdb.tid IN('.$this->sqlTaxaStr.') '.
            'ORDER BY tdb.caption ';
        //echo $sql;
        $rs = $this->conn->query($sql);
        while ($row = $rs->fetch_object()){
            $descSourceList[] = $row->caption;
        }
        $rs->free();
        return $descSourceList;
    }

    public function getPhotogList(){
        $photogList = Array();
        $sql = 'SELECT DISTINCT ti.photographeruid, ti.photographer, u.firstname, u.lastname '.
            'FROM images AS ti LEFT JOIN users AS u ON ti.photographeruid = u.uid '.
            'LEFT JOIN taxstatus AS ts ON ti.tid = ts.tid '.
            'WHERE ts.taxauthid = '.$this->thesFilter.' AND ti.tid IN('.$this->sqlTaxaStr.') AND ti.SortSequence < 500 ';
        //echo $sql;
        $rs = $this->conn->query($sql);
        while ($row = $rs->fetch_object()){
            $uId = $row->photographeruid;
            $givenName = $row->photographer;
            $lastName = $row->lastname;
            $firstName = $row->firstname;
            if($uId){
                $nameStr = $lastName.', '.$firstName;
                $photogList[$nameStr] = $uId;
            }
            else{
                $photogList[$givenName] = 0;
            }
        }
        $rs->free();
        return $photogList;
    }

    //Setters and getters
    public function setThesFilter($filt){
		$this->thesFilter = $filt;
	}

	public function getClid(){
		return $this->clid;
	}

	public function getChildClidArr(){
		return $this->childClidArr;
	}

	public function setProj($pValue){
		$sql = 'SELECT pid, projname FROM fmprojects ';
		if(is_numeric($pValue)){
			$sql .= 'WHERE (pid = '.$pValue.')';
		}
		else{
			$sql .= 'WHERE (projname = "'.$this->cleanInStr($pValue).'")';
		}
		$rs = $this->conn->query($sql);
		if($rs){
			if($r = $rs->fetch_object()){
				$this->pid = $r->pid;
				$this->projName = $this->cleanOutStr($r->projname);
			}
			$rs->free();
		}
		else{
			trigger_error('ERROR: Unable to project => SQL: '.$sql, E_USER_WARNING);
		}
		return $this->pid;
	}

    public function getPid(){
		return $this->pid;
	}

	public function setLanguage($l){
		$l = strtolower($l);
		if($l == "en"){
			$this->language = 'English';
		}
		elseif($l == "es"){
			$this->language = 'Spanish';
		}
		else{
			$this->language = $l;
		}
	}

    public function setPhotogJson($json){
        $photogArr = json_decode($json,true);
        if(is_array($photogArr)){
            foreach($photogArr as $str){
                $parts = explode("---",$str);
                $id = $parts[0];
                $name = $parts[1];
                if($id) $this->photogIdArr[] = $id;
                elseif($name) $this->photogNameArr[] = $name;
            }
        }
        elseif($photogArr != 'all'){
            $this->maxPhoto = 0;
        }
    }

    public function setMaxPhoto($cnt){
        $this->maxPhoto = $cnt;
    }

	public function setImageLimit($cnt){
		$this->imageLimit = $cnt;
	}

    public function setRecIndex($val){
        $this->index = $val;
    }

    public function setRecLimit($val){
        $this->recLimit = $val;
    }

    public function getImageLimit(){
		return $this->imageLimit;
	}

	public function setTaxaLimit($cnt){
		$this->taxaLimit = $cnt;
	}

	public function getTaxaLimit(){
		return $this->taxaLimit;
	}

    public function getDataArr(){
        return $this->dataArr;
    }

	private function cleanOutStr($str){
		$str = str_replace('"',"&quot;",$str);
		$str = str_replace("'","&apos;",$str);
		return $str;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>