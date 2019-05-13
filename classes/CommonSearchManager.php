<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class CommonSearchManager {

    private $conn;
    private $searchParamsArr = array();
    private $sqlWhereArr = array();
    private $sql = '';
    private $commonName = '';
    private $display = 'grid';
    private $orderBy = 'common';

    function __construct($commonValue){
        $this->conn = MySQLiConnectionFactory::getCon("readonly");
        $this->setCommonName($this->conn->escape_string($commonValue));
        $this->setSQL();
    }

    public function __destruct(){
        if(!($this->conn === null)) $this->conn->close();
    }

    public function getCharacterStateArr($char,$sortseq){
        $returnArr = Array();
        $sql = 'SELECT CharStateName, cid, cs, Description '.
            'FROM kmcs '.
            'WHERE cid = '.$char.' '.
            'ORDER BY '.($sortseq?'SortSequence':'CharStateName').' ';
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $returnArr[$row->CharStateName]["cid"] = $row->cid;
            $returnArr[$row->CharStateName]["cs"] = $row->cs;
            $returnArr[$row->CharStateName]["description"] = $row->Description;
        }
        $result->free();

        return $returnArr;
    }

    public function setSQL(){
        $sql = 'SELECT t.TID, t.SciName, v.VernacularName FROM taxa AS t ';
        $sql .= 'LEFT JOIN taxaenumtree AS te ON t.TID = te.parenttid ';
        $sql .= 'LEFT JOIN taxavernaculars AS v ON t.TID = v.TID ';
        $sql .= 'WHERE v.VernacularName LIKE "%';
        $sql .= $this->commonName;
        $sql .= '%" GROUP BY t.TID ORDER BY v.VernacularName';
        $this->sql = $sql;
    }

    public function getDataArr(){
        $returnArr = array();
        $cnt = 0;
        //echo $this->sql; exit;
        $result = $this->conn->query($this->sql);
        while($row = $result->fetch_object()){
            $cnt ++;
            $tid = $row->TID;
            if(!isset($returnArr[$cnt]['sciname'])) $returnArr[$cnt]['sciname'] = $row->SciName;
            $returnArr[$cnt]['tid'] = $tid;
            if(!isset($returnArr[$cnt]['common'])) $returnArr[$cnt]['common'] = $row->VernacularName;
            if($this->display == 'grid'){
            	//run query on images table to get thumbnail image.
	            $sql="SELECT i.thumbnailurl, i.url FROM images AS i WHERE tid = ".$this->conn->escape_string($tid) . " ORDER BY i.sortsequence LIMIT 1";
	            //show large image instead of thumbnail in grid, as thumb is too small
	            $imgThumbnail = $this->conn->query($sql)->fetch_object()->url;
                //prepend image domain if image does not already contain a domain
                if(array_key_exists("IMAGE_DOMAIN",$GLOBALS)){
                    if(substr($imgThumbnail,0,1)=="/") $imgThumbnail = $GLOBALS["IMAGE_DOMAIN"].$imgThumbnail;
                }
                $returnArr[$cnt]['url'] = $imgThumbnail;
            }
            elseif($this->display == 'list'){
                $cid = $row->CID;
	            //run query on images table to get thumbnail image.
	            $sql="SELECT i.thumbnailurl FROM images AS i WHERE tid = ".$this->conn->escape_string($tid) . " ORDER BY i.sortsequence LIMIT 1";
	            $imgThumbnail = $this->conn->query($sql)->fetch_object()->thumbnailurl;
	            if(array_key_exists("IMAGE_DOMAIN",$GLOBALS)){
		            if(substr($imgThumbnail,0,1)=="/") $imgThumbnail = $GLOBALS["IMAGE_DOMAIN"].$imgThumbnail;
	            }
		            $returnArr[$cnt]['url'] = $imgThumbnail;
	            //build additional attribute values
                $attribs = $this->getGridAttribs($this->conn->escape_string($tid));
                $returnArr[$cnt]['type'] = $attribs['type'];
                $returnArr[$cnt]['sunlight'] = $attribs['sunlight'];
                $returnArr[$cnt]['moisture'] = $attribs['moisture'];
                $returnArr[$cnt]['height_string'] = $attribs['height_string'];
                $returnArr[$cnt]['width_string'] = $attribs['width_string'];
                $returnArr[$cnt]['ease'] = $attribs['ease'];
            }
        }
        $result->free();
        return $returnArr;
    }

    public function setCommonName($common_name){
        $this->commonName = $common_name;
    }

    public function getGridAttribs($tid) {
        $attribs = array();
        $sql = "Select d.cid, c.charname, cs.charstatename, cs.cs FROM kmdescr d ";
        $sql .= "Left Join kmcharacters c ON c.CID = d.CID ";
        $sql .= "Left Join kmcs as cs ON d.CS = cs.cs AND cs.cid = d.cid ";
        $sql .= "where tid = ";
        $sql .= $this->conn->escape_string($tid);
        $sql .= " order by  d.cid, cs.cs ";
        //echo $sql;
        $result = $this->conn->query($sql);

        while ($row = $result->fetch_array()) {
            $tmp[$row["cid"]][] = array(
                "charname" => $row["charname"],
                "charstatename" => $row["charstatename"],
                "cs" => $row["cs"] );
        }
        //var_dump($tmp);
        foreach ($tmp[137] as $value) { //habit
            switch ($value['charstatename']) {
                case "tree":
                    $attribs["type"] .= "<img src='../images/plant_type_icon1.png' alt='Tree' class='large woody plants with typically one main stem (trunk)' title='asdf' >";
                    break;
                case "shrub":
                    $attribs["type"] .= "<img src='../images/plant_type_icon2.png' alt='Shrub' class='tooltip' title='woody plants, multi-stemmed, typically less than 10’ tall' >";
                    break;
                case "vine":
                    $attribs["type"] .= "<img src='../images/plant_type_icon2.png' alt='Vine' class='tooltip' title='climbing or trailing plants with long flexible stems, often supported by tendrils' >";
                    break;
                case "herb":
                    $attribs["type"] .= "<img src='../images/plant_type_icon4.png' alt='Herb' class='tooltip' title='flowering plants (annual, biennial, or perennial) with non-woody stems' >";
                    break;
                case "grass or grass-like":
                    $attribs["type"] .= "<img src='../images/plant_type_icon5.png' alt='Grass or grass-like' class='tooltip' title='plants typically with non-showy, wind-pollinated flowers; includes sedges, rushes, and some other monocots' >";
                    break;
                case "fern or fern ally":
                    $attribs["type"] .= "<img src='../images/plant_type_icon6.png' alt='Fern or fern ally' class='tooltip' title='plant that typically have feathery fronds (leaves) and no flowers' >";
                    break;
            }
        }
        foreach ($tmp[680] as $value) { //sunlight
            switch ($value['charstatename']) {
                case "sun":
                    $attribs["sunlight"] .= "<img src='../images/sunlight_icon1.png' alt='Sun' class='tooltip' title='tolerates light conditions that are predominately full sun' >";
                    break;
                case "part shade":
                    $attribs["sunlight"] .= "<img src='../images/sunlight_icon3.png' alt='Part Shade' class='tooltip' title='tolerates light conditions that are predominately partial shade' >";
                    break;
                case "shade":
                    $attribs["sunlight"] .= "<img src='../images/sunlight_icon4.png' alt='Shade' class='tooltip' title='tolerates light conditions that are predominately full shade' >";
                    break;
            }
        }
        foreach ($tmp[683] as $value) { //moisture
            switch ($value['charstatename']) {
                case "dry":
                    $attribs["moisture"].= "<img src='../images/moisture_icon1.png' alt='Dry' class='tooltip' title='tolerating year-round soil moisture conditions that are predominately dry' >";
                    break;
                case "moist":
                    $attribs["moisture"].= "<img src='../images/moisture_icon3.png' alt='Moist' class='tooltip' title='tolerating year-round soil moisture conditions that are predominately moist' >";
                    break;
                case "wet":
                    $attribs["moisture"].= "<img src='../images/moisture_icon4.png' alt='Wet' class='tooltip' title='tolerating year-round soil moisture conditions that are predominately wet' >";
                    break;
            }
        }
        $attribs["minheight"] = is_array($tmp["140"]) ? min(array_column($tmp["140"], 'charstatename')) : '';
        $attribs["maxheight"] = is_array($tmp["140"]) ? max(array_column($tmp["140"], 'charstatename')) : '';
        $attribs["height_string"] = !empty($attribs['minheight']) && !($attribs['minheight'] == $attribs['maxheight']) ? $attribs["minheight"] . "-" : "";
        $attribs["height_string"] .= $attribs["maxheight"];
        $attribs["height_string"] .= (isset($attribs["minheight"]) || isset($attribs["maxheight"])) ? "H;" : "";
        $attribs["minwidth"] = is_array($tmp["738"]) ? min(array_column($tmp["738"], 'charstatename')) : '';
        $attribs["maxwidth"] = is_array($tmp["738"]) ? max(array_column($tmp["738"], 'charstatename')) : '';
        $attribs["width_string"] = !empty($attribs['minwidth']) && !($attribs['minwidth'] == $attribs['maxwidth']) ? $attribs["minwidth"] . "-" : "";
        $attribs["width_string"] .= $attribs["maxwidth"];
        $attribs["width_string"] .= (isset($attribs["minwidth"]) || isset($attribs["maxwidth"])) ? "W" : "";
        $attribs["ease"] = implode(", ",array_map(function($a){
            return ucwords($a["charstatename"]);
        },$tmp[684]));

        //var_dump($attribs);
        return $attribs;
    }
}
?>