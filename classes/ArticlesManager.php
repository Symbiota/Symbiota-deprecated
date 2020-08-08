<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");

class ArticlesManager {

  // ORM Model
  protected $model;

  protected $articles;

  public function __construct() {
    $em = SymbosuEntityManager::getEntityManager();
    #$repo = $em->getRepository("Articles");
    #$this->model = $repo->find($articles_id);
    $this->articles = ArticlesManager::populateArticles();

  }

  public static function fromModel($model) {
    $newInventory = new InventoryManager();
    $newInventory->model = $model;
    $newInventory->articles = InventoryManager::populateArticles();
    return $newInventory;
  }
  public function getArticles() {
  	return $this->articles;
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
      #->groupBy('a.volume')#,a.issue,a.article_order,a.issue_str,a.title,a.authors,a.pdf
			->orderBy("a.volume","DESC")
			->AddOrderBy("a.issue","DESC")
			->addOrderBy("a.article_order","ASC")
      ->getQuery()
      ->execute();
      
    $return = array();
    foreach ($articles as $article) {
    	$volume = $article['volume'];
    	$issue = $article['issue'];
    	$return[$volume . '-' . $issue][] = $article;
    }
    #sort($return);
    #rsort($return);
    return $return;
  }
  /*  
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
  */
  
}


?>