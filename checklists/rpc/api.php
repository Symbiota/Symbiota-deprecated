<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/ExploreManager.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");

$result = [];

function getEmpty() {
  return [
    "clid" => -1,
    "title" => '',
    "intro" => '',
    "iconUrl" => '',
    "authors" => '',
    "abstract" => '',
    "lat" => 0,
    "lng" => 0,
    "taxa" => [],
  ];
}

function managerToJSON($checklistObj) {

  $result = getEmpty();

  if ($checklistObj !== null) {
    $result["clid"] = $checklistObj->getClid();
    $result["title"] = $checklistObj->getTitle();
    $result["intro"] = ($checklistObj->getIntro()? $checklistObj->getIntro() :'') ;
    $result["iconUrl"] = ($checklistObj->getIconUrl()? $checklistObj->getIconUrl() :'') ;
    $result["authors"] = ($checklistObj->getAuthors()? $checklistObj->getAuthors() :'') ;
    $result["abstract"] = ($checklistObj->getAbstract()? $checklistObj->getAbstract() :'') ;
    $result["lat"] = ($checklistObj->getLat()? $checklistObj->getLat() :'') ;
    $result["lng"] = ($checklistObj->getLng()? $checklistObj->getLng() :'') ;
    $taxa = $checklistObj->getTaxa(); 
    if (sizeof($taxa)) {
			$taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");					
			$vouchers = $checklistObj->getVouchers();
			foreach($taxa as $rowArr){
				$taxaModel = $taxaRepo->find($rowArr['tid']);
				$taxa = TaxaManager::fromModel($taxaModel);
				$tjresult = [];
				$tjresult['tid'] = $taxa->getTid();
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
				$tjresult['sciname'] = $taxa->getSciname();
				/*if (sizeof(explode(" ",$tjresult['sciname'])) == 1) {
					$tjresult['sciname'] .= " sp.";#the old code does this, but Katie says it's unnecessary
				}*/
				$result["taxa"][] = $tjresult;
			}
			
			
		}
		$result['totals'] = TaxaManager::getTaxaCounts($result['taxa']);
  }
  return $result;
}




$result = [];
if (array_key_exists("clid", $_GET) && is_numeric($_GET["clid"])&& array_key_exists("pid", $_GET) && is_numeric($_GET["pid"])) {
  $em = SymbosuEntityManager::getEntityManager();
  $repo = $em->getRepository("Fmchecklists");
  $model = $repo->find($_GET["clid"]);
  $checklist = ExploreManager::fromModel($model);
  $checklist->setPid($_GET["pid"]);
  
	if ( 	 ( array_key_exists("search", $_GET) && !empty($_GET["search"]) )
			&& ( array_key_exists("name", $_GET) && in_array($_GET['name'],array('sciname','commonname')) )
	) {
		$checklist->setSearchTerm($_GET["search"]);
		$checklist->setSearchName($_GET['name']);
		
		$synonyms = (isset($_GET['synonyms']) && $_GET['synonyms'] == 'on') ? true : false;
		$checklist->setSearchSynonyms($synonyms);
	}
	$result = managerToJSON($checklist);
	
}else{
	#todo: generate error or redirect
}
// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>