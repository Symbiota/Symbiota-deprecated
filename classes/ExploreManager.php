<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");

class ExploreManager {

  // ORM Model
  protected $model;  

	protected $pid;
  protected $taxa;
  protected $taxaVouchers;
  protected $searchTerm;
  protected $searchName = '';
  protected $searchSynonyms = false;
  protected $lat = '';
  protected $lng = '';
	

  public function __construct($clid=-1) {
    if ($clid !== -1) {
      $em = SymbosuEntityManager::getEntityManager();
      $repo = $em->getRepository("Fmchecklists");
      $this->model = $repo->find($clid);
      #$this->taxa = ExploreManager::populateTaxa($this->getClid());
    } else {
      $this->taxa = [];
    }
  }

  public static function fromModel($model) {
    $newChecklist = new ExploreManager();
    $newChecklist->model = $model;
    #$newChecklist->taxa = ExploreManager::populateTaxa($model->getClid());
    return $newChecklist;
  }
  
  public function getClid() {
    return $this->model->getClid();
  }
  #these next two seem wrong, but the function names make more sense than the model/DB names
  public function getTitle() {
    return $this->model->getName();
  }
  public function getIntro() {
    return $this->model->getTitle();
  }
  public function getAbstract() {
    return cleanWindows($this->model->getAbstract());
  }
  public function getAuthors() {
    return $this->model->getAuthors();
  }
  public function getIconUrl() {
    return $this->model->getIconurl();
  }
  public function getLat() {
    return $this->model->getLatcentroid();
  }
  public function getLng() {
    return $this->model->getLongcentroid();
  }
  public function getTaxa() {
  	$this->taxa = $this->populateTaxa($this->getClid());
    return $this->taxa;
  }
  public function getVouchers() {
  	foreach ($this->taxa as $rowArr) {
  		$this->taxaVouchers[$rowArr['tid']] = $this->populateVouchers($rowArr['tid']);
  	}
  	return $this->taxaVouchers;
  }
  public function setPid($pid) {
  	$this->pid = $pid;
  }
  public function getPid() {
  	return $this->pid;
  }
  public function setSearchTerm($term) {
  	$this->searchTerm = $term;
  }
  public function setSearchName($name = '') {
  	if (in_array($name,array('sciname','commonname'))) {
  		$this->searchName = $name;
  	}
  }
  public function setSearchSynonyms($bool) {
  	$this->searchSynonyms = ($bool === true? true: false);
  }
  
  private function populateTaxa($clid) {
  
    $em = SymbosuEntityManager::getEntityManager();
    $taxa = $em->createQueryBuilder();
    $qb = $em->createQueryBuilder();
  
  	$leftJoins = array();
  	$innerJoins = array();
  	$wheres = array(); 
  	$params = array();
  	$orderBy = 't.sciname';
  	
  	$leftJoins[] = array("Fmchklstprojlink", "cpl", "WITH", "ctl.clid = cpl.clid");
  	
  	$innerJoins[] = array("Fmchklsttaxalink", "ctl", "WITH", "t.tid = ctl.tid");
  	#$innerJoins[] = array("Fmchecklists", "cl", "WITH", "ctl.clid = cl.clid");
  	$innerJoins[] = array("Taxstatus", "ts", "WITH", "t.tid = ts.tid");
  	
  	$wheres[] = "ctl.clid = :clid";
  	#$wheres[] = "cl.parentclid = 1";
  	$wheres[] = "cpl.pid = :pid";
  	$wheres[] = "ts.taxauthid = 1";
  	
  	$params[] = array(":clid",$clid);
  	$params[] = array(":pid",$this->pid);
  	
  	if ($this->searchTerm != '' && $this->searchName != '') {
  		switch($this->searchName) {
  			case 'commonname':
  				$innerJoins[] = array("Taxavernaculars", "v", "WITH", "t.tid = v.tid");
  				$params[] = array(":search",'%' . $this->searchTerm . '%');
					if ($this->searchSynonyms) {
						$wheres[] = $qb->expr()->orX(
								$qb->expr()->like('v.vernacularname',':search'),
								$qb->expr()->in(
															"ts.tidaccepted",#array(1,2,3))
															$em->createQueryBuilder()
																->select("ts2.tidaccepted")
																->from("Taxavernaculars","v2")
																->innerJoin("Taxstatus","ts2","WITH","v2.tid = ts2.tid")
																->where('v2.vernacularname LIKE :search')
																->getDQL()
															)
						);
					}else{
	  				$wheres[] = "v.vernacularname LIKE :search";
	  			}
  				break;
  			case 'sciname':
  				$params[] = array(":search",'%' . $this->searchTerm . '%');
  				if ($this->searchSynonyms) {
						$wheres[] = $qb->expr()->orX(
								$qb->expr()->like('t.sciname',':search'),
								$qb->expr()->in(
															"ts.tidaccepted",#array(1,2,3))
															$em->createQueryBuilder()
																->select("ts2.tidaccepted")
																->from("Taxa","t2")
																->innerJoin("Taxstatus","ts2","WITH","t2.tid = ts2.tid")
																->where('t2.sciname LIKE :search')
																->getDQL()
															)
						);
					}else{
	  				$wheres[] = "t.sciname LIKE :search";
	  			}
  				break;
  		}
  	}
  
    $taxa->select(["t.tid","COALESCE(ctl.familyoverride,ts.family) AS family"])
      ->from("Taxa", "t"); 
        
		foreach ($innerJoins as $innerJoin) {
			$taxa->innerJoin(...$innerJoin);
		}  
		foreach ($leftJoins as $leftJoin) {
			$taxa->leftJoin(...$leftJoin);
		} 
		if (sizeof($wheres)) {
			foreach ($wheres as $where) {
				$taxa->andWhere($where);
			}
		}
		foreach ($params as $param) {
			$taxa->setParameter(...$param);
		}

		$taxa->distinct();
		$taxa->orderBy($orderBy);
		
		$tquery = $taxa->getQuery();
		#var_dump($tquery->getSQL());exit;
		$results = $tquery->execute();
    return $results;

  }
  private function populateVouchers($tid) {
    $em = SymbosuEntityManager::getEntityManager();
    $vouchers = $em->createQueryBuilder()
      ->select(["v.tid","v.occid","v.notes","c.institutioncode","o.catalognumber","o.recordedby","o.recordnumber","o.eventdate"])
      ->from("Fmvouchers", "v")
      ->innerJoin("Omoccurrences", "o", "WITH", "v.occid = o.occid")
      ->innerJoin("Omcollections", "c", "WITH", "o.collid = c.collid")
      ->where("v.clid = :clid")
      ->andWhere("v.tid = :tid")
      ->setParameter(":clid", $this->getClid())
      ->setParameter(":tid", $tid)
			->distinct()
      ->getQuery()
      ->execute();
      
    foreach ($vouchers as $idx => $voucher) {
    	if ($voucher['eventdate']) {
    		$vouchers[$idx]['eventdate'] = $voucher['eventdate']->format('Y-m-d');
    	}
    }
    return $vouchers;
  
  }
  
  
}


?>