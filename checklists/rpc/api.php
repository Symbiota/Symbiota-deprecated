<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/ExploreManager.php");

$result = [];

function getEmpty() {
  return [
    "clid" => -1,
    "title" => '',
    "authors" => '',
    "abstract" => '',
    #"fullDescription" => '',
    #"isPublic" => -1,
    "taxa" => [],
  ];
}

function managerToJSON($checklistObj) {

  $result = getEmpty();

  if ($checklistObj !== null) {
    $result["clid"] = $checklistObj->getClid();
    $result["title"] = $checklistObj->getTitle();
    $result["authors"] = $checklistObj->getAuthors();
    $result["abstract"] = $checklistObj->getAbstract();
    $taxa = $checklistObj->getTaxa(); 
    if (sizeof($taxa)) {
			$taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");					
			$vouchers = $checklistObj->getVouchers();
			foreach($taxa as $rowArr){
				$taxaModel = $taxaRepo->find($rowArr['tid']);
				$taxa = TaxaManager::fromModel($taxaModel);
				$tjresult = [];
				$tjresult['tid'] = $taxa->getTid();
				$tjresult['sciname'] = $taxa->getSciname();
				$tjresult['family'] = $taxa->getFamily();
				$tjresult['author'] = $taxa->getAuthor();
				$tjresult['thumbnail'] = $taxa->getThumbnail();
				$tjresult["vernacular"] = [
					"basename" => $taxa->getBasename(),
					"names" => $taxa->getVernacularNames()
				];
				$tjresult['synonyms'] = $taxa->getSynonyms();
				#var_dump($vouchers);
				$tjresult['vouchers'] = $vouchers[$rowArr['tid']];
				$result["taxa"][] = $tjresult;
			}
		}
  }
  return $result;
}


function searchTaxa($searchTerm,$clid,$name,$synonyms) {
  $results = [];
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");
  $taxaResults = $taxaRepo->createQueryBuilder("t")
    ->innerJoin("Taxavernaculars", "v", "WITH", "t.tid = v.tid")
    #->innerJoin("Fmchklsttaxalink", "tl", "WITH", "t.tid = tl.tid")
		#->innerJoin("Fmchecklists", "cl", "WITH", "tl.clid = cl.clid")
   # ->andWhere("cl.parentclid = :clid")
    #->andWhere("tl.clid = :clid")
    #->orWhere("t.sciname LIKE :search")
    ->andWhere("v.vernacularname LIKE :search")
    #->groupBy("t.tid")
    ->setParameter("search", $searchTerm . '%')
    #->setParameter(":clid",$clid)
    ->getQuery()
    ->getResult();

  if ($taxaResults != null) {
    foreach ($taxaResults as $t) {
      $tm = TaxaManager::fromModel($t);
      $tj = taxaManagerToJSON($tm);
      array_push($results, $tj);
    }
  }

  return $results;
  /*
        ->from("Fmchklsttaxalink", "tl")
      ->innerJoin("Fmchecklists", "cl", "WITH", "tl.clid = cl.clid")
      ->where("tl.tid = :tid")
      ->andWhere("cl.parentclid = " . Fmchecklists::$CLID_GARDEN_ALL)
      ->setParameter("tid", $tid);
      */
}


$result = [];
if (array_key_exists("clid", $_GET) && is_numeric($_GET["clid"])) {
  $em = SymbosuEntityManager::getEntityManager();
  $repo = $em->getRepository("Fmchecklists");
  $model = $repo->find($_GET["clid"]);
  $checklist = ExploreManager::fromModel($model);
  
	if ( 	 ( array_key_exists("search", $_GET) && !empty($_GET["search"]) )
			&& ( array_key_exists("name", $_GET) && in_array($_GET['name'],array('sciname','commonname')) )
	) {
		$checklist->setSearchName($_GET['name']);
		
		$synonyms = (isset($_GET['synonyms']) && $_GET['synonyms'] == 'on') ? true : false;
		$checklist->setSynonyms($synonyms);
	}
	$result = managerToJSON($checklist);
	
}else{
	#todo: generate error or redirect
}
// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>