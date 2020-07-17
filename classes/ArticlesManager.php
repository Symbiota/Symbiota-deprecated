<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");

class ArticlesManager {

  // ORM Model
  protected $model;

  protected $articles;

  public function __construct($articles_id=-1) {
    if ($articles_id !== -1) {
      $em = SymbosuEntityManager::getEntityManager();
      $repo = $em->getRepository("Articles");
      $this->model = $repo->find($articles_id);
      $this->articles = ArticlesManager::populateArticles();
    } else {
      $this->checklists = [];
    }
  }

  public static function fromModel($model) {
    $newInventory = new InventoryManager();
    $newInventory->model = $model;
    $newInventory->articles = InventoryManager::populateArticles();
    return $newInventory;
  }
  
  public function getArticlesId() {
    return $this->model->getArticlesId();
  }
  public function getVolume() {
    return $this->model->getVolume();
  }
  public function getIssue() {
    return $this->model->getIssue();
  }
  public function getIssueStr() {
    return $this->model->getIssueStr();
  }
  public function getTitle() {
    return $this->model->getTitle();
  }
  public function getAuthors() {
    return $this->model->getAuthors();
  }
  public function getPdf() {
    return $this->model->getPdf();
  }
  public function getArticleOrder() {
    return $this->model->getArticleOrder();
  }
/*
$results = mysqli_query( $con, "select volume, issue, issue_str, title, authors, pdf, article_order  
    from articles group by volume, issue, article_order, issue_str, title, authors, pdf 
    order by volume desc, issue desc, article_order asc;" );
    */
  private static function populateArticles() {
    $em = SymbosuEntityManager::getEntityManager();
    $articles = $em->createQueryBuilder()
      ->select(["a.volume, a.issue, a.issue_str, a.title, a.authors, a.pdf, a.article_order"])
      ->from("Articles", "a")
			->orderBy("cl.sortsequence, cl.name")
      ->setParameter("articles_id", $articles_id)
      ->getQuery()
      ->execute();
      
    return $checklists;
  }
}


?>