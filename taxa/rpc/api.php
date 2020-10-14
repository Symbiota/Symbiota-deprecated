<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");

$result = [];

$CLID_GARDEN_ALL = 54;

function getTaxon($tid) {
  $em = SymbosuEntityManager::getEntityManager();
  $taxaRepo = $em->getRepository("Taxa");
  $taxaModel = $taxaRepo->find($tid);
  $taxa = TaxaManager::fromModel($taxaModel);
  return taxaManagerToJSON($taxa);
}

function searchTaxa($searchTerm) {
  $results = [];
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");
  $taxaResults = $taxaRepo->createQueryBuilder("t")
    ->innerJoin("Taxavernaculars", "v", "WITH", "t.tid = v.tid")
    ->orWhere("t.sciname LIKE :search")
    ->orWhere("v.vernacularname LIKE :search")
    ->groupBy("t.tid")
		->orderBy("t.sciname")
    ->setParameter("search", '%' . $searchTerm . '%')
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
}

function getSubTaxa($parentTid) {#not sure this happens anymore
  $results = [];
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");
  $taxaResults = $taxaRepo->createQueryBuilder("t")
    ->innerJoin("Taxaenumtree", "te", "WITH", "t.tid = te.tid")
    ->where("te.parenttid = :parenttid")
    ->groupBy("t.tid")
    ->setParameter("parenttid", $parentTid)
    ->getQuery()
    ->getResult();

  if ($taxaResults != null) {
    foreach ($taxaResults as $t) {
      $tm = TaxaManager::fromModel($t);
      $tj = taxaManagerToJSON($tm,true);
      array_push($results, $tj);
    }
  }

  return $results;
}
  
function taxaManagerToJSON($taxaObj,$recursive = false,$taxaRankId = null) {
	/*taxaRankId = the rankId of the requested taxa; we set it below and pass it through
		so that recursive calls avoid expensive image collection; taxa.tid = 88 runs out of memory
	*/

	$result = TaxaManager::getEmptyTaxon();
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");

	if ($taxaObj !== null) {
		$result["tid"] = $taxaObj->getTid();
		$result["sciname"] = $taxaObj->getSciname();
		$result["parentTid"] = $taxaObj->getParentTid(); 
		$result["author"] = $taxaObj->getAuthor();
		$result['imagesBasis'] = [];
		$result['imagesBasis']['HumanObservation'] = [];
		$result['imagesBasis']['PreservedSpecimen'] = [];
		$result['imagesBasis']['LivingSpecimen'] = [];  
		$result["rankId"] = $taxaObj->getRankId();  
		if ($taxaRankId === null) {
			$taxaRankId = $taxaObj->getRankId();
		}
		
		if ($recursive === false) {#default
			$spp = $taxaObj->getSpp(); 
			foreach($spp as $rowArr){
				$taxaModel = $taxaRepo->find($rowArr['tid']);
				$taxa = TaxaManager::fromModel($taxaModel);
				$tj = taxaManagerToJSON($taxa,true,$taxaRankId);
				if (!isset($result["spp"])) {
					$result['spp'] = [];
				}
				$result["spp"][] = $tj;
			}
			$result["synonyms"] = $taxaObj->getSynonyms();
			$result["origin"] = $taxaObj->getOrigin();
			$result["family"] = $taxaObj->getFamily();
			$result["rarePlantFactSheet"] = $taxaObj->getRarePlantFactSheet();
			$result["characteristics"] = $taxaObj->getCharacteristics();
			$result["checklists"] = $taxaObj->getChecklists();
			$result["descriptions"] = $taxaObj->getDescriptions();
			$result["gardenDescription"] = $taxaObj->getGardenDescription();
			$result["gardenId"] = $taxaObj->getGardenId();
			$result["vernacular"] = [
				"basename" => $taxaObj->getBasename(),
				"names" => $taxaObj->getVernacularNames()
			];
			$result["taxalinks"] = $taxaObj->getTaxalinks();
			foreach ($result["taxalinks"] as $idx => $taxalink) {
				$result["taxalinks"][$idx]['url'] = str_replace("--SCINAME--",$result["sciname"],$taxalink['url']);
			}	
			
			$allImages = $taxaObj->getImagesByBasisOfRecord();
			$result["imagesBasis"]['HumanObservation'] = (isset($allImages['HumanObservation']) ? $allImages['HumanObservation'] : []);
			$result["imagesBasis"]['PreservedSpecimen'] = (isset($allImages['PreservedSpecimen']) ? $allImages['PreservedSpecimen'] : []);
			$result["imagesBasis"]['LivingSpecimen'] = (isset($allImages['LivingSpecimen']) ? $allImages['LivingSpecimen'] : []);
			
			if ($taxaRankId > 140) {
				foreach ($result['spp'] as $staxa) {#collate SPP images into bare taxon image lists

					if (isset($staxa['imagesBasis']['HumanObservation'])) {
						$result['imagesBasis']['HumanObservation'] = array_merge($result['imagesBasis']['HumanObservation'],$staxa['imagesBasis']['HumanObservation']);
					}
					if (isset($staxa['imagesBasis']['PreservedSpecimen'])) {
						$result['imagesBasis']['PreservedSpecimen'] = array_merge($result['imagesBasis']['PreservedSpecimen'],$staxa['imagesBasis']['PreservedSpecimen']);
					}
					if (isset($staxa['imagesBasis']['HumanObservation'])) {
						$result['imagesBasis']['LivingSpecimen'] = array_merge($result['imagesBasis']['LivingSpecimen'],$staxa['imagesBasis']['LivingSpecimen']);
					}
				}
			}

		}elseif($recursive === true){#getting SPP
			if ($taxaRankId > 140) {
				$images = $taxaObj->getImages();
			}else{
				$images = $taxaObj->getImage();#because getImages runs out of memory for tid 88
			}
			if (sizeof($images) === 0 || $images[0] === null) {
				$spp = $taxaObj->getSpp();
				foreach($spp as $rowArr){
					$sppTaxaModel = $taxaRepo->find($rowArr['tid']);
					$sppTaxa = TaxaManager::fromModel($sppTaxaModel);
					$sppImages = $sppTaxa->getImage();
					if (is_array($sppImages) && isset($sppImages[0]) && isset($sppImages[0]['imgid'])) {
						$result['imagesBasis'][$sppImages[0]['basisofrecord']] = $sppImages;
						break;
					}
				}	
			}else{
				$result['imagesBasis'][$images[0]['basisofrecord']] = $images;
			}
		}
	}
	return $result;
}
$result = [];
if (array_key_exists("search", $_GET)) {
  $result = searchTaxa(trim($_GET["search"]));
}else if (array_key_exists("taxon", $_GET) && is_numeric($_GET["taxon"])) {
  $result = getTaxon($_GET["taxon"]);
} else if (array_key_exists("family", $_GET) && is_numeric($_GET["family"])) {
  $result = getSubTaxa($_GET["family"]);
} else if (array_key_exists("genus", $_GET) && is_numeric($_GET["genus"])) {
  $result = getSubTaxa($_GET["genus"]);
}

array_walk_recursive($result,'cleanWindowsRecursive');#replace Windows characters
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>