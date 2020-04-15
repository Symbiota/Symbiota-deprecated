<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");

class TaxaManager {
  # Basic characteristics
  private static $CID_SUNLIGHT = 680;
  private static $CID_MOISTURE = 683;
  private static $CID_WIDTH = 738;
  private static $CID_HEIGHT = 140;
  private static $CID_SPREADS = 739;
  private static $CID_OTHER_CULT_PREFS = 767;
  
  # Plant features
  private static $CID_FLOWER_COLOR = 612;
  private static $CID_BLOOM_MONTHS = 165;
  private static $CID_WILDLIFE_SUPPORT = 685;
  private static $CID_LIFESPAN = 136;
  private static $CID_FOLIAGE_TYPE = 100;
  private static $CID_PLANT_TYPE = 137;
  
  # Growth & maintenance
  private static $CID_LANDSCAPE_USES = 679;
  private static $CID_CULTIVATION_PREFS = 767;
  private static $CID_BEHAVIOR = 688;
  private static $CID_PROPAGATION = 740;
  private static $CID_EASE_GROWTH = 684;
  
  # Beyond the garden
  private static $CID_HABITAT = 163;
  private static $CID_ECOREGION = 19;

  // ORM Model
  protected $model;

  protected $basename;
  protected $images;
  protected $characteristics;
  protected $checklists;
  protected $description;

  public function __construct($tid=-1) {
    if ($tid !== -1) {
      $em = SymbosuEntityManager::getEntityManager();
      $taxaRepo = $em->getRepository("Taxa");
      $this->model = $taxaRepo->find($tid);
      $this->basename = $this->populateBasename();
      $this->images = TaxaManager::populateImages($this->getTid());
      $this->characteristics = TaxaManager::populateCharacteristics($this->getTid());
      $this->checklists = TaxaManager::populateChecklists($this->getTid());
      $this->description = $this->populateDescription();
    } else {
      $this->model = null;
      $this->basename = '';
      $this->images = [];
      $this->characteristics = [];
      $this->checklists = [];
      $this->description = "";
    }
  }

  public static function fromModel($model) {
    $newTaxa = new TaxaManager();
    $newTaxa->model = $model;
    $newTaxa->basename = $newTaxa->populateBasename();
    $newTaxa->images = TaxaManager::populateImages($model->getTid());
    $newTaxa->characteristics = TaxaManager::populateCharacteristics($model->getTid());
    $newTaxa->checklists = TaxaManager::populateChecklists($model->getTid());
    $newTaxa->description = $newTaxa->populateDescription($model->getTid());
    return $newTaxa;
  }

  public function isGardenTaxa() {
    // Since we only populate children of garden checklist anyway
    return count($this->checklists) > 0;
  }

  public function getTid() {
    return $this->model->getTid();
  }

  public function getSciname() {
    return $this->model->getSciname();
  }

  public function getVernacularNames() {
    return $this->model->getVernacularNames()
      ->map(function($vn) { return $vn->getVernacularName(); })
      ->toArray();
  }

  public function getBasename() {
    return $this->basename;
  }

  public function getImages() {
    return $this->images;
  }

  public function getThumbnail() {
    return $this->images[0]["thumbnailurl"];
  }

  public function getCharacteristics() {
    return $this->characteristics;
  }

  public function getChecklists() {
    return $this->checklists;
  }

  public function getDescription() {
    return $this->description;
  }

  private function populateDescription() {
    $em = SymbosuEntityManager::getEntityManager();
    $stmts = $em->createQueryBuilder()
      ->select(["ts.statement"])
      ->from("Taxadescrstmt", "ts")
      ->innerJoin("Taxadescrblock", "tb", "WITH", "ts.tdbid = tb.tdbid")
      ->where("tb.tid = :tid")
      ->orderBy("ts.sortsequence")
      ->setParameter("tid", $this->getTid())
      ->getQuery()
      ->execute();

    $result = "";
    if (count($stmts) > 0) {
      // Somebody must've copied & pasted from Word or something
      $result = mb_convert_encoding($stmts[0]["statement"], "UTF-8", "Windows-1252");
      if (!$result) {
        return $stmts[0]["statement"];
      }
    }
    return $result;
  }

  private static function populateChecklists($tid) {
    $em = SymbosuEntityManager::getEntityManager();
    $clQuery = $em->createQueryBuilder()
      ->select(["cl.clid"])
      ->from("Fmchklsttaxalink", "tl")
      ->innerJoin("Fmchecklists", "cl", "WITH", "tl.clid = cl.clid")
      ->where("tl.tid = :tid")
      ->andWhere("cl.parentclid = " . Fmchecklists::$CLID_GARDEN_ALL)
      ->setParameter("tid", $tid);

    return array_map(
      function ($cl) { return $cl["clid"]; },
      $clQuery->getQuery()->execute()
    );
  }

  private static function getEmptyCharacteristics() {
    return [
      "height" => [],
      "width" => [],
      "sunlight" => [],
      "moisture" => [],
      "features" => [
        "flower_color" => [],
        "bloom_months" => [],
        "wildlife_support" => [],
        "lifespan" => [],
        "foliage_type" => [],
        "plant_type" => []
      ],
      "growth_maintenance" => [
        "landscape_uses" => [],
        "cultivation_preferences" => [],
        "behavior" => [],
        "propagation" => [],
        "ease_of_growth" => [],
        "spreads_vigorously" => null,
        "other_cult_prefs" => []
      ],
      "beyond_garden" => [
        "ecoregion" => [],
        "habitat" => []
      ]
    ];
  }

  private static function populateCharacteristics($tid) {
    $em = SymbosuEntityManager::getEntityManager();
    $attributeQuery = $em->createQueryBuilder()
      ->select(["d.cid", "s.charstatename"])
      ->from("Kmdescr", "d")
      ->innerJoin("Kmcs", "s", "WITH", "(d.cid = s.cid AND d.cs = s.cs)")
      ->innerJoin("Kmcharacters", "c", "WITH", "d.cid = c.cid")
      ->where("d.tid = :tid");
    $attributeQuery = $attributeQuery
      ->andWhere($attributeQuery->expr()->in("d.cid", TaxaManager::getAllCids()))
      ->setParameter("tid", $tid);

    $attribs = $attributeQuery->getQuery()->execute();
    $attr_array = TaxaManager::getEmptyCharacteristics();
    foreach ($attribs as $attrib) {
      $attr_key = $attrib["cid"];
      $attr_val = $attrib["charstatename"];
      switch ($attr_key) {
        case TaxaManager::$CID_HEIGHT:
          array_push($attr_array["height"], intval($attr_val));
          break;
        case TaxaManager::$CID_WIDTH:
          array_push($attr_array["width"], intval($attr_val));
          break;
        case TaxaManager::$CID_SUNLIGHT:
          array_push($attr_array["sunlight"], $attr_val);
          break;
        case TaxaManager::$CID_MOISTURE:
          array_push($attr_array["moisture"], $attr_val);
          break;
        case TaxaManager::$CID_FLOWER_COLOR:
          array_push($attr_array["features"]["flower_color"], $attr_val);
          break;
        case TaxaManager::$CID_BLOOM_MONTHS:
          array_push($attr_array["features"]["bloom_months"], $attr_val);
          break;
        case TaxaManager::$CID_WILDLIFE_SUPPORT:
          array_push($attr_array["features"]["wildlife_support"], $attr_val);
          break;
        case TaxaManager::$CID_LIFESPAN:
          array_push($attr_array["features"]["lifespan"], $attr_val);
          break;
        case TaxaManager::$CID_FOLIAGE_TYPE:
          array_push($attr_array["features"]["foliage_type"], $attr_val);
          break;
        case TaxaManager::$CID_PLANT_TYPE:
          array_push($attr_array["features"]["plant_type"], $attr_val);
          break;
        case TaxaManager::$CID_LANDSCAPE_USES:
          array_push($attr_array["growth_maintenance"]["landscape_uses"], $attr_val);
          break;
        case TaxaManager::$CID_CULTIVATION_PREFS:
          array_push($attr_array["growth_maintenance"]["cultivation_preferences"], $attr_val);
          break;
        case TaxaManager::$CID_BEHAVIOR:
          array_push($attr_array["growth_maintenance"]["behavior"], $attr_val);
          break;
        case TaxaManager::$CID_PROPAGATION:
          array_push($attr_array["growth_maintenance"]["propagation"], $attr_val);
          break;
        case TaxaManager::$CID_EASE_GROWTH:
          array_push($attr_array["growth_maintenance"]["ease_of_growth"], $attr_val);
          break;
        case TaxaManager::$CID_SPREADS:
          $attr_array["growth_maintenance"]["spreads_vigorously"] = $attr_val;
          break;
        case TaxaManager::$CID_OTHER_CULT_PREFS:
          array_push($attr_array["growth_maintenance"]["other_cult_prefs"], $attr_val);
          break;
        case TaxaManager::$CID_ECOREGION:
          array_push($attr_array["beyond_garden"]["ecoregion"], $attr_val);
          break;
        case TaxaManager::$CID_HABITAT:
          array_push($attr_array["beyond_garden"]["habitat"], $attr_val);
          break;
        default:
          break;
      }
    }

    foreach (["width", "height"] as $k) {
      if (count($attr_array[$k]) > 1) {
        $tmp = [min($attr_array[$k]), max($attr_array[$k])];
        $attr_array[$k] = $tmp;
      }
    }

    return $attr_array;
  }

  private static function populateImages($tid) {
    $em = SymbosuEntityManager::getEntityManager();
    $images = $em->createQueryBuilder()
      ->select(["i.thumbnailurl", "i.url", "i.photographer", "i.owner", "i.copyright", "i.notes","o.year", "o.month", "o.day","o.country","o.stateprovince","o.county","o.locality","o.recordedby","c.collectionname"])#
      ->from("Images", "i")
      ->innerJoin("omoccurrences","o","WITH","i.occid = o.occid")
      ->innerJoin("omcollections","c","WITH","c.collid = o.collid")
      ->where("i.tid = :tid")
      ->setParameter("tid", $tid)
      ->orderBy("i.sortsequence")
      ->getQuery()
      ->execute();
    
    $images = array_map("TaxaManager::processImageData",$images);
    /*foreach ($images as $key => $image) {
    	list($width, $height) = getimagesize($image['url']);
    	echo $width;
    }*/
    
    $return = $images;
    #$return = array_map("TaxaManager::resolvePaths",$images);
    /*	$return = array_map(
			function($img) { return [ "thumbnailurl" => resolve_img_path($img["thumbnailurl"]), "url" => resolve_img_path($img["url"]) ]; },
				$images
			);*/
    return $return;
  }
  
  private static function processImageData($img) {
  		foreach ($img as $field => $value) {
  			if ($field == 'thumbnailurl' || $field == 'url') {
  				$img[$field] = resolve_img_path($value);
  			}elseif( $field == 'year' && $value == '' && !empty($img['notes'])) {
  				#Photographed: Aug 9, 2008
  				$date = str_replace("Photographed: ",'',$img['notes']);
  				$datestamp = strtotime($date);
  				$img['year'] = date("Y",$datestamp);
  				$img['day'] = date("j",$datestamp);
  				$img['month'] = date("n",$datestamp);
  			}
  		}
  		return $img;
  }

  private function populateBasename() {
    $basename = '';
    $baseNameCandidates = $this->model->getVernacularNames()
      ->filter(function($vn) { return strtolower($vn->getLanguage()) === "basename"; });

    if ($baseNameCandidates->count() > 0) {
      $basename = $baseNameCandidates->first()->getVernacularname();
    }
    return $basename;
  }
  
  private static function getAllCids() {
    return [
      # Basic characteristics
      TaxaManager::$CID_SUNLIGHT,
      TaxaManager::$CID_MOISTURE,
      TaxaManager::$CID_WIDTH,
      TaxaManager::$CID_HEIGHT,
  
        # Plant features
      TaxaManager::$CID_FLOWER_COLOR,
      TaxaManager::$CID_BLOOM_MONTHS,
      TaxaManager::$CID_WILDLIFE_SUPPORT,
      TaxaManager::$CID_LIFESPAN,
      TaxaManager::$CID_FOLIAGE_TYPE,
      TaxaManager::$CID_PLANT_TYPE,
  
        # Growth & maintenance
      TaxaManager::$CID_LANDSCAPE_USES,
      TaxaManager::$CID_CULTIVATION_PREFS,
      TaxaManager::$CID_BEHAVIOR,
      TaxaManager::$CID_PROPAGATION,
      TaxaManager::$CID_EASE_GROWTH,
      TaxaManager::$CID_SPREADS,
      TaxaManager::$CID_OTHER_CULT_PREFS,
  
        # Beyond the garden
      TaxaManager::$CID_HABITAT,
      TaxaManager::$CID_ECOREGION
    ];
  }
}

?>