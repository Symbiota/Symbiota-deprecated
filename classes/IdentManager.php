<?php

include_once("../../config/symbini.php");
#include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");

class IdentManager extends Manager {
/*
	# from TaxonProfileManager
	private $langArr = array();
	
  // ORM Model
  protected $model;
*/
  protected $clid;
  protected $pid;
  protected $dynClid;
  protected $clType;
	protected $attrs = array();
  protected $displayMode;
  protected $taxonFilter;
  protected $taxa;
  protected $relevanceValue = .9;
  private   $currQuery;
  protected $searchTerm;
  protected $searchName = 'sciname';
  protected $searchSynonyms = false;
  /*
  protected $basename;
  protected $images;
  protected $characteristics;
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
*/

	function __construct(){
		parent::__construct(null,'readonly');
	}

	function __destruct(){
		parent::__destruct();
	}
/*
  public static function fromModel($model) {
    $newTaxa = new TaxaManager();
    $newTaxa->model = $model;
    $newTaxa->basename = $newTaxa->populateBasename();
    $newTaxa->images = TaxaManager::populateImages($model->getTid());
    $newTaxa->characteristics = TaxaManager::populateCharacteristics($model->getTid());
    $newTaxa->checklists = TaxaManager::populateChecklists($model->getTid());
    $newTaxa->descriptions = $newTaxa->populateDescriptions($model->getTid());
    $newTaxa->gardenDescription = $newTaxa->populateGardenDescription($model->getTid());
    $newTaxa->spp = $newTaxa->populateSpp($model->getTid());
    return $newTaxa;
  }
  */
  
  public function setPid($pid) {
  	$this->pid = intval($pid);
  }
  public function setClid($clid) {
  	$this->clid = intval($clid);
  }
  public function setDynClid($dynClid) {
  	$this->dynClid = intval($dynClid);
  }
  public function setClType($clType) {
  	$this->clType = $clType;
  }
  public function setAttrs($attrs) {
  	$this->attrs = $attrs;
  }
  public function setTaxonFilter($taxonFilter) {
  	$this->taxonFilter = $taxonFilter;
  }
  public function setRelevanceValue($rv) {
  	$this->relevanceValue = $rv;
  }
  public function setSearchTerm($term) {
  	$this->searchTerm = $term;
  }
  public function setSearchName($name = '') {
  	if (in_array($name,array('sciname','commonname'))) {
  		$this->searchName = $name;
  	}
  }
  public function setTaxa() {
  	$leftJoins = array();
  	$innerJoins = array();
  	$wheres = array(); 
  	$params = array();
  	$results = null;
  	
  	if ($this->clid || $this->dynClid) {
			$em = SymbosuEntityManager::getEntityManager();
			$taxa = $em->createQueryBuilder()
				->select(["t.tid, ts.family, t.sciname, ts.parenttid, v.vernacularname, v.language, t.author"])
				->from("Taxa","t")
			;
			$leftJoins[] = array("Taxavernaculars","v","WITH","t.tid = v.tid");
			#$wheres[] = "v.sortsequence = 1";
			$innerJoins[] = array("Taxstatus","ts","WITH","t.tid = ts.tid");
			$wheres[] = $taxa->expr()->orX(
											$taxa->expr()->eq('v.language',"'English'"),
											$taxa->expr()->eq('v.language',"'Basename'")
										);
			
			$wheres[] = "ts.taxauthid = 1";
			$wheres[] = "t.rankid = 220";	
			
			if ($this->searchTerm != '' && $this->searchName != '') {
				switch($this->searchName) {
					case 'commonname':
						$innerJoins[] = array("Taxavernaculars", "v", "WITH", "t.tid = v.tid");
						$params[] = array(":search",'%' . $this->searchTerm . '%');
						if ($this->searchSynonyms) {
							$wheres[] = $qb->expr()->orX(
									$qb->expr()->like('v.vernacularname',':search'),
									$qb->expr()->in(
																"ts.tidaccepted",#array(1,2,3))
																$em->createQueryBuilder()
																	->select("ts2.tidaccepted")
																	->from("Taxavernaculars","v2")
																	->innerJoin("Taxstatus","ts2","WITH","v2.tid = ts2.tid")
																	->where('v2.vernacularname LIKE :search')
																	->getDQL()
																)
							);
						}else{
							$wheres[] = "v.vernacularname LIKE :search";
						}
						break;
					case 'sciname':
						$params[] = array(":search",'%' . $this->searchTerm . '%');
						if ($this->searchSynonyms) {
							$wheres[] = $qb->expr()->orX(
									$qb->expr()->like('t.sciname',':search'),
									$qb->expr()->in(
																"ts.tidaccepted",#array(1,2,3))
																$em->createQueryBuilder()
																	->select("ts2.tidaccepted")
																	->from("Taxa","t2")
																	->innerJoin("Taxstatus","ts2","WITH","t2.tid = ts2.tid")
																	->where('t2.sciname LIKE :search')
																	->getDQL()
																)
							);
						}else{
							$wheres[] = "t.sciname LIKE :search";
						}
						break;
				}
			}
	
			if ($this->dynClid) {
				$innerJoins[] = array("Fmdyncltaxalink","clk","WITH","t.tid = clk.tid");
				$wheres[] = "clk.dynclid = :dynclid";
				$params[] = array("dynclid",$this->dynClid);

			}else{
				if ($this->clType == 'dynamic') {#not finished/not in use?
					$innerJoins[] = array("Omoccurrences","o","WITH","t.tid = o.TidInterpreted");
					#wheres[] = $this->dynamicSQL;
				}else{
					$innerJoins[] = array("Fmchklsttaxalink","clk","WITH","t.tid = clk.tid");
					$wheres[] = "clk.clid = :clid";
					$params[] = array("clid",$this->clid);
				}
			}
			if (!empty($this->taxonFilter) && $this->taxonFilter != "All Species") {
				$wheres[] = $taxa->expr()->orX(
											$taxa->expr()->eq('ts.family',':taxon'),
											$taxa->expr()->eq('t.unitname1',':taxon')
										);
				$params[] = array("taxon",$this->taxonFilter);
			}
			#var_dump($this->attrs);
			if (sizeof($this->attrs)) {
        $count = 0;
				foreach ($this->attrs as $cid => $states) {
					$count++;
					$alias = 'D' . $count;#create a unique alias for each join
					$innerJoins[] = array("Kmdescr","{$alias}","WITH","t.tid = {$alias}.tid");
					$wheres[] = "{$alias}.cid = :{$alias}cid";
					$wheres[] = "{$alias}.cs IN (" . join(",",$states) . ")";
					$params[] = array(":{$alias}cid",$cid);
				}
			}

			#set EM
			foreach ($leftJoins as $leftJoin) {
				$taxa->leftJoin(...$leftJoin);
			}
			foreach ($innerJoins as $innerJoin) {
				$taxa->innerJoin(...$innerJoin);
			}
			if (sizeof($wheres)) {
				foreach ($wheres as $where) {
					$taxa->andWhere($where);
				}
			}
			foreach ($params as $param) {
				$taxa->setParameter(...$param);
			}
			$taxa->distinct();
			$taxa->orderBy("ts.family, t.sciname, v.sortsequence");
			$tquery = $taxa->getQuery();
			$this->currQuery = $tquery;
			$results = $tquery->getResult();

			$newResults = array();
			$currSciName = '';
			$currIdx = null;
			foreach ($results as $idx => $result) {

				if ($result['sciname'] == $currSciName) {
					if (strtolower($result['language']) == 'basename') {
						$newResults[$currIdx]['vernacular']['basename'] = $result['vernacularname'];
					}elseif(strtolower($result['language']) == 'english') {
						$newResults[$currIdx]['vernacular']['names'][] = $result['vernacularname'];
					}
				}else{
					$newResults[$idx] = $result;
					$newResults[$idx]['vernacular']['basename'] = '';
					$newResults[$idx]['vernacular']['names'] = [];
					if (strtolower($result['language']) == 'basename') {
						$newResults[$idx]['vernacular']['basename'] = $result['vernacularname'];
					}elseif(strtolower($result['language']) == 'english') {
						$newResults[$idx]['vernacular']['names'][] = $result['vernacularname'];
					}
					unset($newResults[$idx]['vernacularname']);
					unset($newResults[$idx]['language']);
					$currSciName = $result['sciname'];
					$currIdx = $idx;
				}
				$newResults[$idx]['author'] = cleanWindows($result[$idx]['author']);
			}		
		}
		$this->taxa = array_values($newResults);
  }
  
	public function getCharacteristics() {
		if (!empty($this->taxa)) {
			$taxa_tids = array_column($this->taxa,"tid");
			$charList = array();
			$em = SymbosuEntityManager::getEntityManager();
			$qb = $em->createQueryBuilder();
			$em3 = $em->createQueryBuilder();
			$chars = $em->createQueryBuilder()
				->select(['t.tid, d.cid'])
				->from("Taxa","t")
				->innerJoin("Kmdescr","d","WITH","t.tid = d.tid")
				->where("d.cs <> '-'")
				->andWhere($qb->expr()->in('t.tid',$taxa_tids))
				->distinct()
			;
			$cquery = $chars->getQuery();
			$results = $cquery->execute();
			
			#grouping and checking against relevanceValue in PHP b/c I can't find a way to do it in Doctrine
			$counts = array();
			foreach ($results as $result) {
				if (isset($counts[$result['cid']])){
					$counts[$result['cid']]++;
				}else{
					$counts[$result['cid']] = 1;
				}
			}
			$countMin = sizeof($this->taxa) * $this->relevanceValue;
			$cids = array();
			$loopCount = 0;
			while (empty($cids) && $loopCount++ < 10) {
				foreach ($counts as $cid => $count) {
					if ($count > $countMin) {
						$cids[] = $cid;
					}
				}
				$countMin = $countMin*0.9;
			}
			$cids = array_unique(array_merge($cids,array_keys($this->attrs)));
			
			$selects = [
				"d.tid",
				"chars.cid",
				"cs.cs",
				"cs.charstatename",
				"cs.description as csdescr",
				"chars.charname",
				"chars.description as chardescr",
				"chars.helpurl",
				"chars.difficultyrank",
				"chars.display",
				"chars.defaultlang",
				"Count(cs.cs) as ct",
				"chead.hid",
				"chead.headingname"
			];
			$groupBy = [
				"chead.language", 
				"cs.cid", 
				"cs.cs", 
				"cs.charstatename", 
				"chars.charname", 
				"chead.headingname", 
				"chars.helpurl",
				"chars.difficultyrank", 
				"chars.defaultlang", 
				"chars.chartype"
			];
			$having = [
				$qb->expr()->andX(
					$qb->expr()->eq('chead.language',':lang'),
					$qb->expr()->in('cs.cid',":cids"),
					$qb->expr()->neq('cs.cs',":cs"), 
					$qb->expr()->orX(
						$qb->expr()->eq('chars.chartype',":UM"),
						$qb->expr()->eq('chars.chartype',":OM")
					),
					$qb->expr()->lt('chars.difficultyrank',":difficultyrank")
				)
			];
			$chars = $em->createQueryBuilder()
				->select($selects)
				->from("Kmdescr","d")
				->innerJoin("Kmcs","cs","WITH",$qb->expr()->andX(
											$qb->expr()->eq('d.cs','cs.cs'),
											$qb->expr()->eq('d.cid','cs.cid')
										))
				->innerJoin("Kmcharacters","chars","WITH","chars.cid = cs.cid")
				->innerJoin("Kmcharheading","chead","WITH","chars.hid = chead.hid")
				->andWhere($qb->expr()->in('d.tid',':tids'))
				->setParameter(":tids",$taxa_tids)
				->setParameter(":cids",$cids)
				->setParameter(":lang","English")
				->setParameter(":difficultyrank",3)
				->setParameter(":cs",'-')
				->setParameter(":UM",'UM')
				->setParameter(":OM",'OM')
				->groupBy(join(", ",$groupBy))
				->having(join(", ",$having))
				->orderBy("chead.sortsequence, chars.sortsequence, chars.charname, cs.sortsequence, cs.charstatename")
				->distinct()
			;
			$cquery = $chars->getQuery();
			#var_dump($cquery->getSQL());
			$cresults = $cquery->execute();
			$results = [];

			foreach ($cresults as $cres) {
				if (	($key = array_search($cres['hid'],array_column($results,'hid'))) === false) {
					$tmp = [
						'headingname' => $cres['headingname'],
						'hid' => $cres['hid'],
						'characters' => []
					];
					$results[] = $tmp;
				}
				$key = array_search($cres['hid'],array_column($results,'hid'));
				if (	($skey = array_search($cres['cid'],array_column($results[$key]['characters'],'cid'))) === false) {
					$tmp = [
						'charname' => $cres['charname'],
						'cid' => $cres['cid'],
						'states' => []
					];
					$results[$key]['characters'][] = $tmp;
				}
		
				$skey = array_search($cres['cid'],array_column($results[$key]['characters'],'cid'));				

				$tmp = [
					'cid' => $cres['cid'],
					'cs' => $cres['cs'],
					'charstatename' => $cres['charstatename']
				];
				$results[$key]['characters'][$skey]['states'][] = $tmp;
		
			}
			#var_dump($results);
			#exit;
			return $results;
			#IN(4835,5242,5665,6117)
		}
	}

	public function getAttrs() {
		return $this->attrs;
	}
	public function getTaxa() {
		return $this->taxa;
	}
}

?>