<?php

  include_once("../../config/symbini.php");
  include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
  include_once("$SERVER_ROOT/classes/TaxaManager.php");

  /**
   * Returns canned searches for the react page
   */
  function get_canned_searches() {
    $em = SymbosuEntityManager::getEntityManager();
    $checklistRepo = $em->getRepository("Fmchecklists");
    $gardenChecklists = $checklistRepo->findBy([ "parentclid" => Fmchecklists::$CLID_GARDEN_ALL ]);
    $results = [];

    foreach ($gardenChecklists as $cl) {
      array_push($results, [
        "clid" => $cl->getClid(),
        "name" => $cl->getName(),
        "iconUrl" => $cl->getIconurl(),
        "description" => ucfirst($cl->getTitle())
      ]);
    }

    return $results;
  }

  function get_garden_characteristics($cid) {
    $em = SymbosuEntityManager::getEntityManager();
    $charStateRepo = $em->getRepository("Kmcs");
    $csQuery = $charStateRepo->findBy([ "cid" => $cid ], ["sortsequence" => "ASC"]);
    return array_map(function($cs) { return $cs->getCharstatename(); }, $csQuery);
  }

  /**
   * Returns all unique taxa with thumbnail urls
   * @params $_GET
   */
  function get_garden_taxa($params) {
    $memory_limit = ini_get("memory_limit");
    ini_set("memory_limit", "1G");
    set_time_limit(0);

    $search = null;
    $results = [];

    if (key_exists("search", $params) && $params["search"] !== "" && $params["search"] !== null) {
      $search = strtolower(preg_replace("/[;()-]/", '', $params["search"]));
    }

    $em = SymbosuEntityManager::getEntityManager();
    $taxaRepo = $em->getRepository("Taxa");

    // All tids that belong to Garden checklist
    $gardenTaxaQuery = $taxaRepo->createQueryBuilder("t")
      ->innerJoin("Fmchklsttaxalink", "tl", "WITH", "t.tid = tl.tid")
      ->innerJoin("Fmchecklists", "cl", "WITH", "tl.clid = cl.clid")
      ->where("cl.parentclid = " . Fmchecklists::$CLID_GARDEN_ALL);

    if ($search !== null) {
      $gardenTaxaQuery
        ->innerJoin("Taxavernaculars", "tv", "WITH", "t.tid = tv.tid")
        ->andWhere($gardenTaxaQuery->expr()->orX(
          $gardenTaxaQuery->expr()->like("t.sciname", ":search"),
          $gardenTaxaQuery->expr()->like("tv.vernacularname", ":search")
        ))
        ->groupBy("t.tid")
        ->setParameter("search", "$search%");
    }

    $gardenTaxaModels = $gardenTaxaQuery->getQuery()->execute();

    foreach ($gardenTaxaModels as $taxaModel) {
      $taxa = TaxaManager::fromModel($taxaModel);

      array_push($results, array_merge(
          $taxa->getCharacteristics(),
          [
            "tid" => $taxa->getTid(),
            "sciName" => $taxa->getSciname(),
            "vernacular" => [
              "basename" => $taxa->getBasename(),
              "names" => $taxa->getVernacularNames(),
            ],
            "image" => $taxa->getThumbnail(),
            "checklists" => $taxa->getChecklists()
          ]
        )
      );
    }

    ini_set("memory_limit", $memory_limit);
    set_time_limit(30);
    return $results;
  }

  $searchResults = [];
  if (key_exists("canned", $_GET) && $_GET["canned"] === "true") {
    $searchResults = get_canned_searches();
  } else if (key_exists("attr", $_GET) && is_numeric($_GET['attr'])) {
    $searchResults = get_garden_characteristics(intval($_GET['attr']));
  } else {
    $searchResults = get_garden_taxa($_GET);
  }

  // Begin View
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode($searchResults, JSON_NUMERIC_CHECK);
?>
