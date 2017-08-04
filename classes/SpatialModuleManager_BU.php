<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpatialModuleManager{
	
	protected $conn;
	protected $recordCount = 0;
	private $collArrIndex = 0;

    public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
    }

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

    public function getFullCollectionList($catId = ""){
        $retArr = array();
        //Set collection array
        $collIdArr = array();
        $catIdArr = array();
        //Set collections
        $sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, c.colltype, ccl.ccpk, cat.category '.
            'FROM omcollections c LEFT JOIN omcollcatlink ccl ON c.collid = ccl.collid '.
            'LEFT JOIN omcollcategories cat ON ccl.ccpk = cat.ccpk '.
            'ORDER BY ccl.sortsequence, cat.category, c.sortseq, c.CollectionName ';
        //echo "<div>SQL: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($r = $result->fetch_object()){
            $collType = (stripos($r->colltype, "observation") !== false?'obs':'spec');
            if($r->ccpk){
                if(!isset($retArr[$collType]['cat'][$r->ccpk]['name'])){
                    $retArr[$collType]['cat'][$r->ccpk]['name'] = $r->category;
                }
                $retArr[$collType]['cat'][$r->ccpk][$r->collid]["instcode"] = $r->institutioncode;
                $retArr[$collType]['cat'][$r->ccpk][$r->collid]["collcode"] = $r->collectioncode;
                $retArr[$collType]['cat'][$r->ccpk][$r->collid]["collname"] = $r->collectionname;
                $retArr[$collType]['cat'][$r->ccpk][$r->collid]["icon"] = $r->icon;
            }
            else{
                $retArr[$collType]['coll'][$r->collid]["instcode"] = $r->institutioncode;
                $retArr[$collType]['coll'][$r->collid]["collcode"] = $r->collectioncode;
                $retArr[$collType]['coll'][$r->collid]["collname"] = $r->collectionname;
                $retArr[$collType]['coll'][$r->collid]["icon"] = $r->icon;
            }
        }
        $result->close();
        //Modify sort so that default catid is first
        if(isset($retArr['spec']['cat'][$catId])){
            $targetArr = $retArr['spec']['cat'][$catId];
            unset($retArr['spec']['cat'][$catId]);
            array_unshift($retArr['spec']['cat'],$targetArr);
        }
        elseif(isset($retArr['obs']['cat'][$catId])){
            $targetArr = $retArr['obs']['cat'][$catId];
            unset($retArr['obs']['cat'][$catId]);
            array_unshift($retArr['obs']['cat'],$targetArr);
        }
        return $retArr;
    }

	public function getLayersArr(){
        global $GEOSERVER_URL, $GEOSERVER_LAYER_WORKSPACE;
        $url = $GEOSERVER_URL.'/wms?service=wms&version=2.0.0&request=GetCapabilities';
        $xml = simplexml_load_file($url);
        $layers = $xml->Capability->Layer->Layer;
        $retArr = Array();
        foreach ($layers as $l){
            $nameArr = explode(":",(string)$l->Name);
            $workspace = $nameArr[0];
            $layername = $nameArr[1];
            if($workspace == $GEOSERVER_LAYER_WORKSPACE){
                $i = strtolower((string)$l->Title);
                $retArr[$i]['Name'] = $layername;
                $retArr[$i]['Title'] = (string)$l->Title;
                $retArr[$i]['Abstract'] = (string)$l->Abstract;
                $crsArr = $l->CRS;
                foreach ($crsArr as $c){
                    if(strpos($c, 'EPSG:') !== false) $retArr[$i]['DefaultCRS'] = (string)$c;
                }
                $keywordArr = $l->KeywordList->Keyword;
                foreach ($keywordArr as $k){
                    if($k == 'features') $retArr[$i]['layerType'] = 'vector';
                    elseif($k == 'GeoTIFF') $retArr[$i]['layerType'] = 'raster';
                }
                $retArr[$i]['legendUrl'] = (string)$l->Style->LegendURL->OnlineResource->attributes('xlink', TRUE)->href;
            }
        }
        ksort($retArr);

        return $retArr;
    }
	
	public function getOccStrFromGeoJSON($json){
        $returnStr = '';
        $occArr = array();
        $jsonArr = json_decode($json, true);
        $featureArr = $jsonArr['features'];
        foreach($featureArr as $f => $data){
            $occArr[] = $data['properties']['occid'];
        }
        $returnStr = implode(',',$occArr);

        return $returnStr;
    }

    public function getSynonyms($searchTarget,$taxAuthId = 1){
        $synArr = array();
        $targetTidArr = array();
        $searchStr = '';
        if(is_array($searchTarget)){
            if(is_numeric(current($searchTarget))){
                $targetTidArr = $searchTarget;
            }
            else{
                $searchStr = implode('","',$searchTarget);
            }
        }
        else{
            if(is_numeric($searchTarget)){
                $targetTidArr[] = $searchTarget;
            }
            else{
                $searchStr = $searchTarget;
            }
        }
        if($searchStr){
            //Input is a string, thus get tids
            $sql1 = 'SELECT tid FROM taxa WHERE sciname IN("'.$searchStr.'")';
            $rs1 = $this->conn->query($sql1);
            while($r1 = $rs1->fetch_object()){
                $targetTidArr[] = $r1->tid;
            }
            $rs1->free();
        }

        if($targetTidArr){
            //Get acceptd names
            $accArr = array();
            $rankId = 0;
            $sql2 = 'SELECT DISTINCT t.tid, t.sciname, t.rankid '.
                'FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted '.
                'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.tid IN('.implode(',',$targetTidArr).')) ';
            $rs2 = $this->conn->query($sql2);
            while($r2 = $rs2->fetch_object()){
                $accArr[] = $r2->tid;
                $rankId = $r2->rankid;
                //Put in synonym array if not target
                if(!in_array($r2->tid,$targetTidArr)) $synArr[$r2->tid] = $r2->sciname;
            }
            $rs2->free();

            if($accArr){
                //Get synonym that are different than target
                $sql3 = 'SELECT DISTINCT t.tid, t.sciname ' .
                    'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
                    'WHERE (ts.taxauthid = ' . $taxAuthId . ') AND (ts.tidaccepted IN(' . implode('', $accArr) . ')) ';
                $rs3 = $this->conn->query($sql3);
                while ($r3 = $rs3->fetch_object()) {
                    if (!in_array($r3->tid, $targetTidArr)) $synArr[$r3->tid] = $r3->sciname;
                }
                $rs3->free();

                //If rank is 220, get synonyms of accepted children
                if ($rankId == 220) {
                    $sql4 = 'SELECT DISTINCT t.tid, t.sciname ' .
                        'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
                        'WHERE (ts.parenttid IN(' . implode('', $accArr) . ')) AND (ts.taxauthid = ' . $taxAuthId . ') ' .
                        'AND (ts.TidAccepted = ts.tid)';
                    $rs4 = $this->conn->query($sql4);
                    while ($r4 = $rs4->fetch_object()) {
                        $synArr[$r4->tid] = $r4->sciname;
                    }
                    $rs4->free();
                }
            }
        }
        return $synArr;
    }

    public function outputFullMapCollArr($occArr){
        global $DEFAULTCATID, $CLIENT_ROOT;
        $collCnt = 0;
        if(isset($occArr['cat'])){
            $catArr = $occArr['cat'];
            ?>
            <table>
                <?php
                foreach($catArr as $catid => $catArr){
                    $name = $catArr["name"];
                    unset($catArr["name"]);
                    $idStr = $this->collArrIndex.'-'.$catid;
                    ?>
                    <tr>
                        <td>
                            <a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
                                <img id="plus-<?php echo $idStr; ?>" src="<?php echo $CLIENT_ROOT; ?>/images/plus_sm.png" style="<?php echo ($DEFAULTCATID==$catid?'display:none;':'') ?>" /><img id="minus-<?php echo $idStr; ?>" src="<?php echo $CLIENT_ROOT; ?>/images/minus_sm.png" style="<?php echo ($DEFAULTCATID==$catid?'':'display:none;') ?>" />
                            </a>
                        </td>
                        <td>
                            <input id="cat<?php echo $idStr; ?>Input" data-role="none" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" checked />
                        </td>
                        <td>
			    		<span style='text-decoration:none;color:black;font-size:14px;font-weight:bold;'>
				    		<a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?catid=<?php echo $catid; ?>' target="_blank" ><?php echo $name; ?></a>
				    	</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <div id="cat-<?php echo $idStr; ?>" style="<?php echo ($DEFAULTCATID==$catid?'':'display:none;') ?>margin:10px 0px;">
                                <table style="margin-left:15px;">
                                    <?php
                                    foreach($catArr as $collid => $collName2){
                                        ?>
                                        <tr>
                                            <td>
                                                <?php
                                                if($collName2["icon"]){
                                                    $cIcon = (substr($collName2["icon"],0,6)=='images'?$CLIENT_ROOT.'/':'').$collName2["icon"];
                                                    ?>
                                                    <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
                                                        <img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
                                                    </a>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                            <td style="padding:6px">
                                                <input name="db[]" value="<?php echo $collid; ?>" data-role="none" type="checkbox" class="cat-<?php echo $idStr; ?>" onchange="buildQueryStrings();" onclick="unselectCat('cat<?php echo $catid; ?>Input')" checked />
                                            </td>
                                            <td style="padding:6px">
                                                <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
                                                    <?php echo $collName2["collname"]." (".$collName2["instcode"].")"; ?>
                                                </a>
                                                <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
                                                    more info
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                        $collCnt++;
                                    }
                                    ?>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php
        }
        if(isset($occArr['coll'])){
            $collArr = $occArr['coll'];
            ?>
            <table>
                <?php
                foreach($collArr as $collid => $cArr){
                    ?>
                    <tr>
                        <td>
                            <?php
                            if($cArr["icon"]){
                                $cIcon = (substr($cArr["icon"],0,6)=='images'?$CLIENT_ROOT.'/':'').$cArr["icon"];
                                ?>
                                <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
                                    <img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
                                </a>
                                <?php
                            }
                            ?>
                            &nbsp;
                        </td>
                        <td style="padding:6px;">
                            <input name="db[]" value="<?php echo $collid; ?>" data-role="none" type="checkbox" onchange="buildQueryStrings();" onclick="uncheckAll(this.form)" checked />
                        </td>
                        <td style="padding:6px">
                            <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
                                <?php echo $cArr["collname"]." (".$cArr["instcode"].")"; ?>
                            </a>
                            <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
                                more info
                            </a>
                        </td>
                    </tr>
                    <?php
                    $collCnt++;
                }
                ?>
            </table>
            <?php
        }
        $this->collArrIndex++;
    }

    public function writeGPXFromGeoJSON($json){
        $returnStr = '<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" version="1.1" creator="Symbiota">';
        $jsonArr = json_decode($json, true);
        $featureArr = $jsonArr['features'];
        foreach($featureArr as $f => $data){
            $coordArr = $data['geometry']['coordinates'];
            $returnStr .= '<wpt lat="'.$coordArr[1].'" lon="'.$coordArr[0].'"/>';
        }
        $returnStr .= '</gpx>';

        return $returnStr;
    }

    public function writeKMLFromGeoJSON($json){
        $returnStr = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xsi:schemaLocation="http://www.opengis.net/kml/2.2 https://developers.google.com/kml/schema/kml22gx.xsd">';
        $jsonArr = json_decode($json, true);
        $featureArr = $jsonArr['features'];
        $returnStr .= '<Document>';
        foreach($featureArr as $f => $data){
            $returnStr .= '<Placemark>';
            $coordArr = $data['geometry']['coordinates'];
            $propArr = $data['properties'];
            $propKeys = array_keys($propArr);
            if($propArr){
                $returnStr .= '<ExtendedData>';
                foreach($propKeys as $k){
                    $propindex = htmlspecialchars($k, ENT_QUOTES);
                    $prop = htmlspecialchars((is_array($propArr[$k])?$propArr[$k][0]:$propArr[$k]), ENT_QUOTES);
                    if($propArr[$k]){
                        $returnStr .= '<Data name="'.$propindex.'"><value>'.$prop.'</value></Data>';
                    }
                    else{
                        $returnStr .= '<Data name="'.$propindex.'"/>';
                    }
                }
                $returnStr .= '</ExtendedData>';
            }
            $returnStr .= '<Point><coordinates>'.$coordArr[0].','.$coordArr[1].'</coordinates></Point>';
            $returnStr .= '</Placemark>';
        }
        $returnStr .= '</Document></kml>';

        return $returnStr;
    }
}
?>