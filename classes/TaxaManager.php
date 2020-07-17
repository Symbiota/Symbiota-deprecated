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

	# from TaxonProfileManager
	private $langArr = array();
	
  // ORM Model
  protected $model;

  protected $basename;
  protected $images;
  protected $characteristics;
  protected $checklists;
  protected $descriptions;
  protected $gardenId;
  protected $gardenDescription;
  protected $synonyms;
  protected $origin;
  protected $family;
  protected $parentTid;
  protected $taxalinks;
  protected $rarePlantFactSheet;
  protected $rankId;
  protected $spp;

  public function __construct($tid=-1) {
    if ($tid !== -1) {
      $em = SymbosuEntityManager::getEntityManager();
      $taxaRepo = $em->getRepository("Taxa");
      $this->model = $taxaRepo->find($tid);
      $this->basename = $this->populateBasename();
      $this->images = TaxaManager::populateImages($this->getTid());
      $this->checklists = TaxaManager::populateChecklists($this->getTid());
      #$this->characteristics = TaxaManager::populateCharacteristics($this->getTid());
      #$this->descriptions = $this->populateDescriptions($this->getTid());
      #$this->gardenDescription = $this->populateGardenDescription($this->getTid());
      #$this->populateTaxalinks($this->getTid());
      #$this->spp = $this->populateSpp($this->getTid());
    } else {
      $this->model = null;
      $this->basename = '';
      $this->images = [];
      $this->characteristics = [];
      $this->checklists = [];
      $this->descriptions = [];
      $this->gardenId = -1;
      $this->gardenDescription = '';
      $this->spp = [];
    }
  }

  public static function fromModel($model) {
    $newTaxa = new TaxaManager();
    $newTaxa->model = $model;
    $newTaxa->basename = $newTaxa->populateBasename();
    $newTaxa->images = TaxaManager::populateImages($model->getTid());
    $newTaxa->checklists = TaxaManager::populateChecklists($model->getTid());
    #$newTaxa->characteristics = TaxaManager::populateCharacteristics($model->getTid());
    #$newTaxa->descriptions = $newTaxa->populateDescriptions($model->getTid());
    #$newTaxa->gardenDescription = $newTaxa->populateGardenDescription($model->getTid());
    #$newTaxa->spp = $newTaxa->populateSpp($model->getTid());
    return $newTaxa;
  }
  
	public function setLanguage($lang){
		$lang = strtolower($lang);
		if($lang == 'en' || $lang == 'english') $this->langArr = array('en','english');
		elseif($lang == 'es' || $lang == 'spanish') $this->langArr = array('es','spanish','espanol');
		elseif($lang == 'fr' || $lang == 'french') $this->langArr =  array('fr','french');
	}

  public function getTid() {
    return $this->model->getTid();
  }
  public function getSciname() {
    return $this->model->getSciname();
  }
  public function getAuthor() {
    return $this->model->getAuthor();
  }
  public function getRankId() {
    return $this->model->getRankid();
  }
  public function getVernacularNames() {
    $vern = $this->model->getVernacularNames()
      ->filter(function($vn) { return strtolower($vn->getLanguage()) === "english"; })
      ->map(function($vn) { return $vn->getVernacularName(); })
      ->toArray();
    sort($vern);
    return $vern; 
  }

  public function getSynonyms() {
  	$this->synonyms = $this->populateSynonyms($this->getTid());
    return $this->synonyms;
  }
  public function getOrigin() {
  	$this->origin = $this->populateOrigin($this->getTid());
  	return $this->origin;
  }
  public function getFamily() {
  	$this->family = $this->populateStatusFields($this->getTid())['family'];
  	return $this->family;
  }
  public function getParentTid() {
  	$this->parentTid = $this->populateStatusFields($this->getTid())['parenttid'];
  	return $this->parentTid;
  }
	public function getGardenId() {
  	$this->gardenId = $this->populateGardenId($this->getTid());
		return $this->gardenId;
	}
  public function getTaxalinks() {
    $this->taxalinks = $this->populateTaxalinks($this->getTid());
  	return $this->taxalinks;
  }
  public function getRarePlantFactSheet() {
    $this->taxalinks = $this->populateTaxalinks($this->getTid());
  	return $this->rarePlantFactSheet;
  }
  public function getBasename() {
    return $this->basename;
  }
  public function getImages() {
    return $this->images;
  }
	public function getImagesByBasisOfRecord() {
		$return = array();
		foreach ($this->images as $image) {
			$return[$image['basisofrecord']][] = $image;
		}
		return $return;
	}
  public function getThumbnail() {
    return $this->images[0]["thumbnailurl"];
  }

  public function getCharacteristics() {
  	$this->characteristics = self::populateCharacteristics($this->getTid());
    return $this->characteristics;
  }

  public function getChecklists() {
    return $this->checklists;
  }

  public function getDescriptions() {
  	$this->descriptions = $this->populateDescriptions($this->getTid());
    return $this->descriptions;
  }

  public function getGardenDescription() {
  	$this->gardenDescription = $this->populateGardenDescription($this->getTid());
    return $this->gardenDescription;
  }
  public function getSpp() {
  	$this->spp = $this->populateSpp($this->getTid());
  	return $this->spp;
  }
  
  public function isGardenTaxa() {
    // Since we only populate children of garden checklist anyway
    return count($this->checklists) > 0;
  }

  private function populateGardenId() {
  /*
  	If this->tid is in Garden checklist, return it
  	else check if parentId is in garden checklist; if so, return it
  */
  	$return = -1;
  	if ($this->isGardenTaxa()) {
  		$return = $this->getTid();
  	}elseif ($this->getRankId() > 220){
  		$parentId = $this->getParentTid();

			$em = SymbosuEntityManager::getEntityManager();
			$clQuery = $em->createQueryBuilder()
				->select(["cl.clid"])
				->from("Fmchklsttaxalink", "tl")
				->innerJoin("Fmchecklists", "cl", "WITH", "tl.clid = cl.clid")
				->where("tl.tid = :tid")
				->andWhere("cl.parentclid = " . Fmchecklists::$CLID_GARDEN_ALL)
				->setParameter("tid", $parentId)
				->getQuery()
				->execute();

				if (sizeof($clQuery)) {
					$return = $parentId;
				}
  	}
  	
  	return $return;
  }
	private function populateSpp($tid = null) {
		$return = array();
  	if ($tid) {
  		if ($this->getRankId() >= 140) {#less complicated than what's in OSUTaxaManager::setSppData() for now
  	
				$em = SymbosuEntityManager::getEntityManager();
  			#$spp = $taxaRepo->createQueryBuilder("t")
				$spp = $em->createQueryBuilder()
					->select(["t.tid"])#, t.sciname, t.securitystatus
					->from("Taxa", "t")
					->innerJoin("Taxaenumtree", "te", "WITH", "t.tid = te.tid")
					->innerJoin("Taxstatus", "ts", "WITH", "t.tid = ts.tidaccepted")
					#->where("te.taxauthid = :taxauthid")#this line causes an error on live, but not on my machine; all values are 1 anyway so commenting out
					->andWhere("ts.taxauthid = :taxauthid")
					->andWhere("t.rankid >= :rankid")
					->andWhere("te.parenttid = :tid")
					->setParameter(":tid", $tid)
					->setParameter(":taxauthid", 1)
					->setParameter(":rankid", 220)
					->distinct()
					->getQuery()
					->execute();
					$return = $spp;
			}
		}
		return $return;
	}

  private function populateGardenDescription($tid = null) {
  	$return = '';
  	$descriptions = $this->getDescriptions();
  	foreach ($descriptions as $key => $block) {
  		if (strcasecmp($block['caption'],'Gardening with Natives') === 0) {
  			$statement = array_shift($block['desc']);		
  			if (!empty($statement)) {
	  			$return = $statement;
	  		}
  		}
  	}
  	return $return;  
  }
  private function populateDescriptions($tid = null) {
  	$retArr = array();
  	$emdash = html_entity_decode('&#x8212;', ENT_COMPAT, 'UTF-8');
  	if ($tid) {
			$em = SymbosuEntityManager::getEntityManager();
			$rsArr = $em->createQueryBuilder()
				->select(["ts.tid, tdb.tdbid, tdb.caption, tdb.language, tdb.source, tdb.sourceurl, tds.tdsid, tds.heading, tds.statement, tds.displayheader"])#
				->from("Taxstatus", "ts")
				->innerJoin("Taxadescrblock", "tdb", "WITH", "ts.tid = tdb.tid")
				->innerJoin("Taxadescrstmts", "tds", "WITH", "tds.tdbid = tdb.tdbid")
				->where("ts.tidaccepted = :tid")
				->andWhere("ts.taxauthid = 1")
				->orderBy("tdb.displaylevel,tds.sortsequence")
				->setParameter("tid", $tid)
				->getQuery()
				->execute();
				#var_dump($rsArr);exit;
				foreach ($rsArr as $idx => $rs) {
					#$rs[$idx] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $rs['statement']);#htmlentities($rs['statement']);
					#$rsArr[$idx] =  str_replace($emdash, '(mdash)', $rsArr[$idx]);
					$rsArr[$idx]['statement'] = mb_convert_encoding($rsArr[$idx]['statement'], "UTF-8", array("Windows-1252"));
				}
				
				/* copied from TaxonProfileManager */
        //Get descriptions associated with accepted name only
				$usedCaptionArr = array();
				foreach($rsArr as $n => $rowArr){
					if($rowArr['tid'] == $tid){
						$retArr = $this->loadDescriptionArr($rowArr, $retArr);
						$usedCaptionArr[] = $rowArr['caption'];
					}
				}
				//Then add description linked to synonyms ONLY if one doesn't exist with same caption
				reset($rsArr);
				foreach($rsArr as $n => $rowArr){
					if($rowArr['tid'] != $tid && !in_array($rowArr['caption'], $usedCaptionArr)){
						$retArr = $this->loadDescriptionArr($rowArr, $retArr);
					}
				}
        ksort($retArr);
		}
		if (sizeof($retArr)) {
    	return $retArr[0];#I don't know what situation would require the whole array, so this for now
		}else{
			return [];
		}
   	#return $retArr;
    /*
    $result = "";
    if (count($stmts) > 0) {
      // Somebody must've copied & pasted from Word or something
      $result = mb_convert_encoding($stmts[0]["statement"], "UTF-8", "Windows-1252");
      if (!$result) {
        return $stmts[0]["statement"];
      }
    }*/
  }
  /* copied from TaxonProfileManager */
	private function loadDescriptionArr($rowArr,$retArr){
		$indexKey = 0;
		#if(!in_array(strtolower($rowArr['language']), $this->langArr)){
		#	$indexKey = 1;
		#}
		if(!isset($retArr[$indexKey]) || !array_key_exists($rowArr['tdbid'],$retArr[$indexKey])){
			$retArr[$indexKey][$rowArr['tdbid']]["caption"] = $rowArr['caption'];
			$retArr[$indexKey][$rowArr['tdbid']]["source"] = $rowArr['source'];
			$retArr[$indexKey][$rowArr['tdbid']]["url"] = $rowArr['sourceurl'];
		}
		$retArr[$indexKey][$rowArr['tdbid']]["desc"][$rowArr['tdsid']] = ($rowArr['displayheader'] && $rowArr['heading']?"<b>".$rowArr['heading']."</b>: ":"").$rowArr['statement'];
		return $retArr;
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
  private function populateSynonyms($tid) {
    $em = SymbosuEntityManager::getEntityManager();
    $synonyms = $em->createQueryBuilder()
      ->select(["t.sciname", "t.author"])
      ->from("taxstatus", "ts")
      ->innerJoin("taxa", "t", "WITH", "ts.tid = t.tid")
      ->where("ts.tidaccepted = :tid")
      ->andWhere("t.tid != :tid")
      ->andWhere("ts.taxauthid = 1")
      ->andWhere("ts.sortsequence < 90")
      ->setParameter("tid", $tid)
      ->orderBy("ts.sortsequence, t.sciname")->getQuery()->execute();
    /*$synonyms = array_map(
      function ($sy) { return $sy["sciname"] . " " . $sy["author"]; },
      $synonyms->getQuery()->execute()
    );*/
    return $synonyms;
  }
  private function populateOrigin($tid = null){
  	$return = null;
  	if ($tid) {
			$em = SymbosuEntityManager::getEntityManager();
			$origin = $em->createQueryBuilder()
				->select(["ctl.nativity"])
				->from("Fmchklsttaxalink", "ctl")
				->where("ctl.tid = :tid")
				->andWhere("ctl.clid = 1")
				->setParameter("tid", $tid)
      	->getQuery()
      	->execute();
      if (isset($origin[0])) {
				$return = $origin[0]['nativity'];
			}
		}
		return $return;
 	}
 	private function populateStatusFields($tid = null) {
  	$return = null;
  	if ($tid) {
			$em = SymbosuEntityManager::getEntityManager();
			$status = $em->createQueryBuilder()
				->select(["ts.family, ts.parenttid"])
				->from("Taxstatus", "ts")
				->where("ts.tidaccepted = :tid")
				->setParameter("tid", $tid)
      	->getQuery()
      	->execute();
			$return = $status[0];
		}
		return $return;
 	
 	}
  
  private function populateTaxalinks($tid = null){
  	$return = null;
  	$this->rarePlantFactSheet = '';
  	if ($tid) {
			$em = SymbosuEntityManager::getEntityManager();
			$links = $em->createQueryBuilder()
				->select(["tl.url","tl.title"])
				->from("Taxalinks", "tl")
				->where("tl.tid = :tid")
				->setParameter("tid", $tid)
      	->getQuery()
      	->execute();
		}

  	foreach ($links as $idx => $arr) {
  		if (strcasecmp($arr['title'],"Rare Plant Fact Sheet") === 0) {
  			$this->rarePlantFactSheet = $arr['url'];
  			unset($links[$idx]);
  		}
  	}
  	sort($links);
  	return $links;
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
      ->select(["i.imgid, i.thumbnailurl", "i.url", "i.photographer", "i.owner", "i.copyright", "i.notes","o.year", "o.month", "o.day","o.country","o.stateprovince","o.county","o.locality","o.recordedby","o.basisofrecord","c.collectionname"])#
      ->from("Images", "i")
      ->innerJoin("omoccurrences","o","WITH","i.occid = o.occid")
      ->innerJoin("omcollections","c","WITH","c.collid = o.collid")
      ->where("i.tid = :tid")
      ->setParameter("tid", $tid)
      ->orderBy("i.sortsequence")
      ->getQuery()
      ->execute();
    
    $images = array_map("TaxaManager::processImageData",$images);
    /*
    #getimagesize is too slow here
    foreach ($images as $key => $image) {
    	list($width, $height) = getimagesize($image['url']);
    	echo $width;
    }*/
    
    $return = $images;
    return $return;
  }
  
  private static function processImageData($img) {
  		foreach ($img as $field => $value) {
  			if ($field == 'thumbnailurl' || $field == 'url') {
  				$img[$field] = resolve_img_path($value);
  			}elseif( $field == 'year') {
  				$img['fulldate'] = '';
  				if ($value == '' && !empty($img['notes'])){#Photographed: Aug 9, 2008 or Photographed: date unknown
						$date = str_replace("Photographed: ",'',$img['notes']);
						$img['fulldate'] = $date;
						/*$datestamp = strtotime($date);
						if (false !== $datestamp) {
							$img['year'] = date("Y",$datestamp);
							$img['day'] = date("j",$datestamp);
							$img['month'] = date("n",$datestamp);
						}*/
						
					}else{
						if (!empty($img['day']) && !empty($img['month'])) {
							$img['fulldate'] = $img['year'] . '-' . $img['month'] . '-' . $img['day'];
						}
					}
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
  
  
#    	global $LANG_TAG;
 #   var_dump($LANG_TAG);
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

	public static function getEmptyTaxon() {
		return [
			"tid" => -1,
			"sciname" => '',
			"author" => '',
			"parentTid" => -1,
			"rankId" => -1,
			"descriptions" => [],
			"gardenDescription" => '',
			"gardenId" => -1,
			"images" => [],
			"imagesBasis" => [],
			"vernacular" => [
				"basename" => '',
				"names" => []
			],
			"synonyms" => [],
			"taxalinks" => [],
			"rarePlantFactSheet" => '',
			"origin"	=> '',
			"family" 	=> '',
			"characteristics" => [],
			"spp" => [],
		];
	}

  
}

?>