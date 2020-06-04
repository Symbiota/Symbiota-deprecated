<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");

class InventoryManager {

  // ORM Model
  protected $model;

  protected $projname;
  protected $managers;
  protected $fullDescription;
  protected $isPublic;
  protected $checklists;

  public function __construct($pid=-1) {
    if ($pid !== -1) {
      $em = SymbosuEntityManager::getEntityManager();
      $repo = $em->getRepository("Fmprojects");
      $this->model = $repo->find($pid);
      $this->checklists = InventoryManager::populateChecklists($this->getPid());
    } else {
      $this->checklists = [];
    }
  }

  public static function fromModel($model) {
    $newInventory = new InventoryManager();
    $newInventory->model = $model;
    $newInventory->checklists = InventoryManager::populateChecklists($model->getPid());
    return $newInventory;
  }
  
  public function getPid() {
    return $this->model->getPid();
  }
  public function getProjname() {
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
  }
  public function getChecklists() {
    return $this->checklists;
  }
/*
  public function getParentTid() {
  	$this->parentTid = $this->populateStatusFields($this->getPid())['parenttid'];
  	return $this->parentTid;
  }
  */


  private static function populateChecklists($pid) {
    $em = SymbosuEntityManager::getEntityManager();
    $checklists = $em->createQueryBuilder()
      ->select(["cl.clid, cl.name, cl.latcentroid, cl.longcentroid, cl.access"])
      ->from("fmchklstprojlink", "cpl")
      ->innerJoin("Fmchecklists", "cl", "WITH", "cpl.clid = cl.clid")
      ->where("cpl.pid = :pid")
      ->andWhere("cl.access != 'private'")
			->orderBy("cl.sortsequence, cl.name")
      ->setParameter("pid", $pid)
      ->getQuery()
      ->execute();
      
    return $checklists;
  }
}


?>