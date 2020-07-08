<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");

class ExploreManager {

  // ORM Model
  protected $model;
  
  protected $taxa;

/*  protected $projname;
  protected $managers;
  protected $fullDescription;
  protected $isPublic;
*/

  public function __construct($clid=-1) {
    if ($clid !== -1) {
      $em = SymbosuEntityManager::getEntityManager();
      $repo = $em->getRepository("Fmchecklists");
      $this->model = $repo->find($clid);
      $this->taxa = ExploreManager::populateTaxa($this->getClid());
    } else {
      $this->taxa = [];
    }
  }

  public static function fromModel($model) {
    $newChecklist = new ExploreManager();
    $newChecklist->model = $model;
    $newChecklist->taxa = ExploreManager::populateTaxa($model->getClid());
    return $newChecklist;
  }
  
  public function getClid() {
    return $this->model->getClid();
  }
/*  public function getProjname() {
    return $this->model->getProjname();
  }
  public function getManagers() {
    return $this->model->getManagers();
  }
  public function getBriefDescription() {
    return $this->model->getBriefDescription();
  }
  public function getFullDescription() {
    return $this->model->getFullDescription();
  }
  public function getIsPublic() {
    return $this->model->getIsPublic();
  }*/
  public function getTaxa() {
    return $this->taxa;
  }
/*
	private static function getChildren($clid) {
    $em = SymbosuEntityManager::getEntityManager();
    $children = $em->createQueryBuilder()
    	->select(["fc.clidchild"])
    	->from("fmchklstchildren","fc")
    	->andWhere("fc.clid = :clid")
    	->setParameter(":clid",$clid)
      ->getQuery()
      ->execute();
    var_dump($children);
	}
*/
  private static function getTaxaByChecklist($clid) {
  
    $em = SymbosuEntityManager::getEntityManager();
    $taxa = $em->createQueryBuilder()
      ->select(["t.tid","COALESCE(ctl.familyoverride,ts.family) AS family"])
      ->from("Taxa", "t")
      ->innerJoin("Fmchklsttaxalink", "ctl", "WITH", "t.tid = ctl.tid")
      ->innerJoin("Taxstatus", "ts", "WITH", "t.tid = ts.tid")
      ->where("ctl.clid = :clid")
      ->andWhere("ts.taxauthid = 1")
			->orderBy("t.sciname")
      ->setParameter("clid", $clid)
			->distinct()
      ->getQuery()
      ->execute();
    return $taxa;
  }
  
  private static function populateTaxa($clid) {
  	$taxa = ExploreManager::getTaxaByChecklist($clid);
  	$taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");
  	$taxaArr = [];
		foreach($taxa as $taxon){
			$taxaModel = $taxaRepo->find($taxon['tid']);
			$taxa = TaxaManager::fromModel($taxaModel);
			$tj = TaxaManager::taxaManagerToJSON($taxa);
			$taxaArr[] = $tj;
		}
		return $taxaArr;
  }
}


?>