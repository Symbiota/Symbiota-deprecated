<?php
include_once("OccurrenceManager.php");

class OccurrenceMapManager extends OccurrenceManager{
	
	private $sqlWhere = '';
	
	public function __construct(){
    	global $clientRoot;
 		parent::__construct();
    }

	public function __destruct(){
 		parent::__destruct();
	}
	
	public function getOccurSqlWhere(){
		$this->sqlWhere = $this->getSqlWhere();
		return $this->sqlWhere;
	}

    //Setters and getters
    public function getTaxaArr(){
    	return $this->taxaArr;
    }
	
	public function getSearchTermsArr(){
    	return $this->searchTermsArr;
    }
}
?>