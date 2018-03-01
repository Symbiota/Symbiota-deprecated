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
	private $thesFilter = 0;
	private $imageLimit = 100;
	private $taxaLimit = 500;
	private $basicSql;

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
        $sql .= "LIMIT ".$this->index.",".$this->recLimit;
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
        $sql = 'SELECT tdb.tid, tdb.caption, tds.tdsid, tds.heading, tds.statement, tds.displayheader '.
            'FROM taxadescrblock AS tdb LEFT JOIN taxadescrstmts AS tds ON tdb.tdbid = tds.tdbid '.
            'WHERE tdb.tid IN('.$this->sqlTaxaStr.') '.
            'ORDER BY tdb.tid,tdb.displaylevel,tds.sortsequence ';
        //echo $sql; exit;
        $rs = $this->conn->query($sql);
        while($row = $rs->fetch_object()){
            $heading = ($row->displayheader?strip_tags($row->heading):'');
            $statement = strip_tags($row->statement);
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
        $sql = 'SELECT ti.tid, ti.imgid, ti.thumbnailurl, ti.url, IFNULL(ti.photographer,CONCAT_WS(" ",u.firstname,u.lastname)) AS photographer '.
            'FROM images AS ti LEFT JOIN users AS u ON ti.photographeruid = u.uid '.
            'LEFT JOIN taxstatus AS ts ON ti.tid = ts.tid '.
            'WHERE ts.taxauthid = '.$this->thesFilter.' AND ti.tid IN('.$this->sqlTaxaStr.') AND ti.SortSequence < 500 ';
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
            if($i < 3){
                $imgUrl = $row->thumbnailurl;
                if((!$imgUrl || $imgUrl == 'empty') && $row->url) $imgUrl = $row->url;
                /*$type = pathinfo($imgUrl, PATHINFO_EXTENSION);
                if($data = $this->curl_get_contents($imgUrl)){
                    $dataUri = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    $this->dataArr[$row->tid]["img"][$row->imgid]['url'] = $dataUri;
                    $this->dataArr[$row->tid]["img"][$row->imgid]['photographer'] = $row->photographer;
                }*/
                //$data = file_get_contents($imgUrl);
                //$dataUri = 'data:image/' . $type . ';base64,' . base64_encode($data);
                //$this->dataArr[$row->tid]["img"][$row->imgid]['url'] = $dataUri;
                //$this->dataArr[$row->tid]["img"][$row->imgid]['photographer'] = $row->photographer;

                $this->dataArr[$row->tid]["img"][$row->imgid]['id'] = $row->imgid;
                $this->dataArr[$row->tid]["img"][$row->imgid]['photographer'] = $row->photographer;
            }
            $i++;
        }
        $result->free();
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
        /*$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $data = curl_exec($ch);
        curl_close($ch);*/

        //$path = 'myfolder/myimage.png';
        $type = pathinfo($url, PATHINFO_EXTENSION);
        $data = file_get_contents($url);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return $base64;
    }

    public function getCoordinates($tid = 0,$abbreviated=false){
		$retArr = array();
		if(!$this->basicSql) $this->setClSql();
		if($this->clid){
			//Add children checklists to query
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}

			$retCnt = 0;
			//Grab general points
			try{
				$sql1 = '';
				if($tid){
					$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '.
						'FROM fmchklstcoordinates cc INNER JOIN taxa t ON cc.tid = t.tid '.
						'WHERE cc.tid = '.$tid.' AND cc.clid IN ('.$clidStr.') AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				else{
					$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '.
						'FROM fmchklstcoordinates cc INNER JOIN ('.$this->basicSql.') t ON cc.tid = t.tid '.
						'WHERE cc.clid IN ('.$clidStr.') AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				if($abbreviated){
					$sql1 .= 'ORDER BY RAND() LIMIT 50';
				}
				//echo $sql1;
				$rs1 = $this->conn->query($sql1);
				if($rs1){
					while($r1 = $rs1->fetch_object()){
						if($abbreviated){
							$retArr[] = $r1->decimallatitude.','.$r1->decimallongitude;
						}
						else{
							$retArr[$r1->tid][] = array('ll'=>$r1->decimallatitude.','.$r1->decimallongitude,'sciname'=>$this->cleanOutStr($r1->sciname),'notes'=>$this->cleanOutStr($r1->notes));
						}
						$retCnt++;
					}
					$rs1->free();
				}
			}
			catch(Exception $e){
				echo 'Caught exception getting general coordinates: ',  $e->getMessage(), "\n";
			}

			if(!$abbreviated || $retCnt < 50){
				try{
					//Grab voucher points
					$sql2 = '';
					if($tid){
						$sql2 = 'SELECT DISTINCT v.tid, o.occid, o.decimallatitude, o.decimallongitude, '.
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,o.eventdate),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid '.
							'WHERE v.tid = '.$tid.' AND v.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND (o.localitysecurity = 0 OR o.localitysecurity IS NULL) ';
					}
					else{
						$sql2 = 'SELECT DISTINCT v.tid, o.occid, o.decimallatitude, o.decimallongitude, '.
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,o.eventdate),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid '.
							'INNER JOIN ('.$this->basicSql.') t ON v.tid = t.tid '.
							'WHERE v.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND (o.localitysecurity = 0 OR o.localitysecurity IS NULL) ';
					}
					if($abbreviated){
						$sql2 .= 'ORDER BY RAND() LIMIT 50';
					}
					//echo $sql2;
					$rs2 = $this->conn->query($sql2);
					if($rs2){
						while($r2 = $rs2->fetch_object()){
							if($abbreviated){
								$retArr[] = $r2->decimallatitude.','.$r2->decimallongitude;
							}
							else{
								$retArr[$r2->tid][] = array('ll'=>$r2->decimallatitude.','.$r2->decimallongitude,'notes'=>$this->cleanOutStr($r2->notes),'occid'=>$r2->occid);
							}
						}
						$rs2->free();
					}
				}
				catch(Exception $e){
					//echo 'Caught exception getting voucher coordinates: ',  $e->getMessage(), "\n";
				}
			}
		}
		return $retArr;
	}

	//Checklist index page fucntions
	public function getChecklists(){
		$retArr = Array();
		$sql = 'SELECT p.pid, p.projname, p.ispublic, c.clid, c.name, c.access '.
			'FROM fmchecklists c LEFT JOIN fmchklstprojlink cpl ON c.clid = cpl.clid '.
			'LEFT JOIN fmprojects p ON cpl.pid = p.pid '.
			'WHERE ((c.access LIKE "public%") ';
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin']) && $GLOBALS['USER_RIGHTS']['ClAdmin']) $sql .= 'OR (c.clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).'))';
		$sql .= ') AND ((p.pid IS NULL) OR (p.ispublic = 1) ';
		if(isset($GLOBALS['USER_RIGHTS']['ProjAdmin']) && $GLOBALS['USER_RIGHTS']['ProjAdmin']) $sql .= 'OR (p.pid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ProjAdmin']).'))';
		$sql .= ') ';
		if($this->pid) $sql .= 'AND (p.pid = '.$this->pid.') ';
		$sql .= 'ORDER BY p.projname, c.Name';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			if($row->pid){
				$pid = $row->pid;
				$projName = $row->projname.(!$row->ispublic?' (Private)':'');
			}
			else{
				$pid = 0;
				$projName = 'Undefinded Inventory Project';
			}
			$retArr[$pid]['name'] = $this->cleanOutStr($projName);
			$retArr[$pid]['clid'][$row->clid] = $this->cleanOutStr($row->name).($row->access=='private'?' (Private)':'');
		}
		$rs->free();
		if(isset($retArr[0])){
			$tempArr = $retArr[0];
			unset($retArr[0]);
			$retArr[0] = $tempArr;
		}
		return $retArr;
	}

	public function echoResearchPoints($target){
		$clCluster = '';
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin'])) {
			$clCluster = $GLOBALS['USER_RIGHTS']['ClAdmin'];
		}
		$sql = 'SELECT c.clid, c.name, c.longcentroid, c.latcentroid '.
			'FROM fmchecklists c INNER JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid '.
			'INNER JOIN fmprojects p ON cpl.pid = p.pid '.
			'WHERE (c.access = "public"'.($clCluster?' OR c.clid IN('.implode(',',$clCluster).')':'').') AND (c.LongCentroid IS NOT NULL) AND (p.pid = '.$this->pid.')';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$idStr = $row->clid;
			$nameStr = $this->cleanOutStr($row->name);
			echo "var point".$idStr." = new google.maps.LatLng(".$row->latcentroid.", ".$row->longcentroid.");\n";
			echo "points.push( point".$idStr." );\n";
			echo 'var marker'.$idStr.' = new google.maps.Marker({ position: point'.$idStr.', map: map, title: "'.$nameStr.'" });'."\n";
			//Single click event
			echo 'var infoWin'.$idStr.' = new google.maps.InfoWindow({ content: "<div style=\'width:300px;\'><b>'.$nameStr.'</b><br/>Double Click to open</div>" });'."\n";
			echo "infoWins.push( infoWin".$idStr." );\n";
			echo "google.maps.event.addListener(marker".$idStr.", 'click', function(){ closeAllInfoWins(); infoWin".$idStr.".open(map,marker".$idStr."); });\n";
			//Double click event
			if($target == 'keys'){
				echo "var lStr".$idStr." = '../ident/key.php?cl=".$idStr."&proj=".$this->pid."&taxon=All+Species';\n";
			}
			else{
				echo "var lStr".$idStr." = 'checklist.php?cl=".$idStr."&proj=".$this->pid."';\n";
			}
			echo "google.maps.event.addListener(marker".$idStr.", 'dblclick', function(){ closeAllInfoWins(); marker".$idStr.".setAnimation(google.maps.Animation.BOUNCE); window.location.href = lStr".$idStr."; });\n";
		}
		$result->free();
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