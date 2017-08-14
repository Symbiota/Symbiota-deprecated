<?php
include_once($serverRoot.'/config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');

abstract class TaxaSearchType {
    const  SCIENTIFIC_NAME         = 0;
    const  FAMILY_GENUS_OR_SPECIES = 1;
    const  FAMILY_ONLY             = 2;
    const  SPECIES_NAME_ONLY       = 3;
    const  HIGHER_TAXONOMY         = 4;
    const  COMMON_NAME             = 5;
    const  ANY_NAME                = 6;
    
    public static $_list           = array(0,1,2,3,4,5,6);
    
    private static function getLangStr ( $keyPrefix, $idx ) {
        global $LANG;
        $key = $keyPrefix.$idx;
        if (array_key_exists($key,$LANG)) {
            return $LANG[$key];
        }
        return "Unsupported";
        
    }
    public static function selectionPrompt ( $taxaSearchType ) {
        return TaxaSearchType::getLangStr('SELECT_1-',$taxaSearchType);
    }
    public static function anyNameSearchTag ( $taxaSearchType ) {
        return TaxaSearchType::getLangStr('TSTYPE_1-',$taxaSearchType);
    }
    public static function taxaSearchTypeFromAnyNameSearchTag ( $searchTag ) {
        foreach (TaxaSearchType::$_list as $taxaSearchType) {
            if (TaxaSearchType::anyNameSearchTag($taxaSearchType) == $searchTag) {
                return $taxaSearchType;
            }
        }
        return 3;
    }
}

class SearchManager {
    // TODO: There likely quite a bit more that is common among the various *Manager.php classes.
    
    protected $conn                   = null;

    protected $taxaArr;
    
    public function __construct(){
        $this->conn = MySQLiConnectionFactory::getCon('readonly');
    }
    public function __destruct(){
        if ((!($this->conn === false)) && (!($this->conn === null))) {
            $this->conn->close();
            $this->conn = null;
        }
    }
    
    protected function cleanInStr($str){
        $newStr = trim($str);
        $newStr = preg_replace('/\s\s+/', ' ',$newStr);
        $newStr = $this->conn->real_escape_string($newStr);
        return $newStr;
    }
    
    protected function setTaxaArr ( $useThes, $baseSearchType, $taxaSearchTerms ) {
        $this->taxaArr = array();
        foreach ($taxaSearchTerms as $taxaSearchTerm) {
            $taxaSearchType = $baseSearchType;
            $taxaSearchTerm = trim($taxaSearchTerm);
            if ($taxaSearchType == TaxaSearchType::ANY_NAME) {
                $n = explode(': ',$taxaSearchTerm);
                if (count($n) > 1) {
                    $taxaSearchType = TaxaSearchType::taxaSearchTypeFromAnyNameSearchTag($n[0]);
                    $taxaSearchTerm = $n[1];
                } else {
                    // All terms for ANY_NAME should have a search type tag, but if not assume something.
                    $taxaSearchType = TaxaSearchType::SCIENTIFIC_NAME;
                }
            }
            $this->taxaArr[$taxaSearchTerm] = array();
            $this->taxaArr[$taxaSearchTerm]['taxontype'] = $taxaSearchType;
            if (($baseSearchType != TaxaSearchType::ANY_NAME   ) &&
                ($taxaSearchType != TaxaSearchType::COMMON_NAME)   ) {
                $sql = 'SELECT sciname, tid, rankid FROM taxa WHERE sciname IN("'.implode('","',array_keys($this->taxaArr)).'")';
                $rs = $this->conn->query($sql);
                while($r = $rs->fetch_object()){
                    $this->taxaArr[$r->sciname]['tid'] = $r->tid;
                    $this->taxaArr[$r->sciname]['rankid'] = $r->rankid;
                    if($r->rankid == 140){
                        $this->taxaArr[$r->sciname]['taxontype'] = TaxaSearchType::FAMILY_ONLY;
                    }elseif($r->rankid > 179){
                        $this->taxaArr[$r->sciname]['taxontype'] = TaxaSearchType::SPECIES_NAME_ONLY;
                    }elseif($r->rankid < 180){
                        $this->taxaArr[$r->sciname]['taxontype'] = TaxaSearchType::HIGHER_TAXONOMY;
                    }
                }
                $rs->free();
                if ($useThes) {
                    $this->setSynonymsFor($taxaSearchTerm);
                }
            }
        }
        $this->setSciNamesByVerns();
    }
    
    private function setSciNamesByVerns () {
        $sql = "SELECT DISTINCT v.VernacularName, t.tid, t.sciname, ts.family, t.rankid ".
            "FROM (taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID) ".
            "INNER JOIN taxa t ON t.TID = ts.tidaccepted ";
        $whereStr = "";
        foreach($this->taxaArr as $key => $value){                                             // mbaenrm
            if ($value['taxontype'] == TaxaSearchType::COMMON_NAME) {
                $whereStr .= "OR v.VernacularName = '".$key."' ";
            }
        }
        if($whereStr != ""){
            $sql .= "WHERE (ts.taxauthid = 1) AND (".substr($whereStr,3).") ORDER BY t.rankid LIMIT 20";
            //echo "<div>sql: ".$sql."</div>";
            $result = $this->conn->query($sql);
            if($result->num_rows){
                while($row = $result->fetch_object()){
                  //$vernName = strtolower($row->VernacularName);
                    $vernName = $row->VernacularName;
                    if($row->rankid < 140){
                        $this->taxaArr[$vernName]["tid"][] = $row->tid;
                    }
                    elseif($row->rankid == 140){
                        $this->taxaArr[$vernName]["families"][] = $row->sciname;
                    }
                    else{
                        $this->taxaArr[$vernName]["scinames"][] = $row->sciname;
                    }
                }
            }
            else{
                $this->taxaArr["no records"]["scinames"][] = "no records";
                $this->taxaArr["no records"]["taxontype"][] = TaxaSearchType::COMMON_NAME;
            }
            $result->close();
        }
    }
    
    private function setSynonymsFor ( $taxaArrKey ){
        $taxaVal = $this->taxaArr[$taxaArrKey];
        $synArr  = null;
        if(array_key_exists("scinames",$taxaVal)){
            if(!in_array("no records",$taxaVal["scinames"])){
                $synArr = $this->getSynonyms($taxaVal["scinames"]);
            }
        }
        else{
            $synArr = $this->getSynonyms($taxaArrKey);
        }
        if($synArr) $this->taxaArr[$taxaArrKey]["synonyms"] = $synArr;
    }

    private function getSynonyms($searchTarget,$taxAuthId = 1){
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
    
    
}
?>