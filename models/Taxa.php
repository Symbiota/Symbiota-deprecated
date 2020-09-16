<?php


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Taxa
 * @ORM\Cache("READ_ONLY")
 * @ORM\Table(options={"collate"="utf8_general_ci"}, name="taxa", uniqueConstraints={@ORM\UniqueConstraint(name="sciname_unique", columns={"SciName", "RankId", "Author"})}, indexes={@ORM\Index(name="unitname1_index", columns={"UnitName1", "UnitName2"}), @ORM\Index(name="sciname_index", columns={"SciName"}), @ORM\Index(name="idx_taxacreated", columns={"InitialTimeStamp"}), @ORM\Index(name="rankid_index", columns={"RankId"}), @ORM\Index(name="FK_taxa_uid_idx", columns={"modifiedUid"})})
 * @ORM\Entity
 */
class Taxa
{
  /**
   * @var int
   *
   * @ORM\Column(name="TID", type="integer", nullable=false, options={"unsigned"=true})
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  private $tid;

  /**
   * @var string|null
   *
   * @ORM\Column(name="kingdomName", type="string", length=45, nullable=true)
   */
  private $kingdomname;

  /**
   * @var int|null
   *
   * @ORM\Column(name="RankId", type="smallint", nullable=true, options={"unsigned"=true})
   */
  private $rankid;

  /**
   * @var string
   *
   * @ORM\Column(name="SciName", type="string", length=250, nullable=false)
   */
  private $sciname;

  /**
   * @var string|null
   *
   * @ORM\Column(name="UnitInd1", type="string", length=1, nullable=true)
   */
  private $unitind1;

  /**
   * @var string
   *
   * @ORM\Column(name="UnitName1", type="string", length=50, nullable=false)
   */
  private $unitname1;

  /**
   * @var string|null
   *
   * @ORM\Column(name="UnitInd2", type="string", length=1, nullable=true)
   */
  private $unitind2;

  /**
   * @var string|null
   *
   * @ORM\Column(name="UnitName2", type="string", length=50, nullable=true)
   */
  private $unitname2;

  /**
   * @var string|null
   *
   * @ORM\Column(name="UnitInd3", type="string", length=7, nullable=true)
   */
  private $unitind3;

  /**
   * @var string|null
   *
   * @ORM\Column(name="UnitName3", type="string", length=35, nullable=true)
   */
  private $unitname3;

  /**
   * @var string|null
   *
   * @ORM\Column(name="Author", type="string", length=100, nullable=true)
   */
  private $author;

  /**
   * @var bool|null
   *
   * @ORM\Column(name="PhyloSortSequence", type="boolean", nullable=true)
   */
  private $phylosortsequence;

  /**
   * @var string|null
   *
   * @ORM\Column(name="Status", type="string", length=50, nullable=true)
   */
  private $status;

  /**
   * @var string|null
   *
   * @ORM\Column(name="Source", type="string", length=250, nullable=true)
   */
  private $source;

  /**
   * @var string|null
   *
   * @ORM\Column(name="Notes", type="string", length=250, nullable=true)
   */
  private $notes;

  /**
   * @var string|null
   *
   * @ORM\Column(name="Hybrid", type="string", length=50, nullable=true)
   */
  private $hybrid;

  /**
   * @var int
   *
   * @ORM\Column(name="SecurityStatus", type="integer", nullable=false, options={"unsigned"=true,"comment"="0 = no security; 1 = hidden locality"})
   */
  private $securitystatus = '0';

  /**
   * @var DateTime|null
   *
   * @ORM\Column(name="modifiedTimeStamp", type="datetime", nullable=true)
   */
  private $modifiedtimestamp;

  /**
   * @var DateTime
   *
   * @ORM\Column(name="InitialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
   */
  private $initialtimestamp = 'CURRENT_TIMESTAMP';

  /**
   * @var integer
   *
   */
  private $modifieduid;


/**
 * @var ArrayCollection
 * @ORM\Cache("READ_ONLY")
 * @ORM\OneToMany(targetEntity="Taxavernaculars", mappedBy="tid")
 * @ORM\OrderBy({ "sortsequence": "ASC" })
 */
  private $vernacularNames;


/**
 * @var ArrayCollection
 * @ORM\Cache("READ_ONLY")
 * @ORM\OneToMany(targetEntity="Images", mappedBy="tid")
 * @ORM\OrderBy({ "sortsequence": "ASC" })
 */
  private $images;

  public function __construct() {
    $this->vernacularNames = new ArrayCollection();
    $this->images = new ArrayCollection();
  }

  public function getVernacularNames() {
    return $this->vernacularNames;
  }

  public function getImages() {
    return $this->images;
  }

  public function getBaseName() {
    return '';
  }

  /**
   * Get tid.
   *
   * @return int
   */
  public function getTid()
  {
      return $this->tid;
  }

  /**
   * Set kingdomname.
   *
   * @param string|null $kingdomname
   *
   * @return Taxa
   */
  public function setKingdomname($kingdomname = null)
  {
      $this->kingdomname = $kingdomname;

      return $this;
  }

  /**
   * Get kingdomname.
   *
   * @return string|null
   */
  public function getKingdomname()
  {
      return $this->kingdomname;
  }

  /**
   * Set rankid.
   *
   * @param int|null $rankid
   *
   * @return Taxa
   */
  public function setRankid($rankid = null)
  {
      $this->rankid = $rankid;

      return $this;
  }

  /**
   * Get rankid.
   *
   * @return int|null
   */
  public function getRankid()
  {
      return $this->rankid;
  }

  /**
   * Set sciname.
   *
   * @param string $sciname
   *
   * @return Taxa
   */
  public function setSciname($sciname)
  {
      $this->sciname = $sciname;

      return $this;
  }

  /**
   * Get sciname.
   *
   * @return string
   */
  public function getSciname()
  {
      return $this->sciname;
  }

  /**
   * Set unitind1.
   *
   * @param string|null $unitind1
   *
   * @return Taxa
   */
  public function setUnitind1($unitind1 = null)
  {
      $this->unitind1 = $unitind1;

      return $this;
  }

  /**
   * Get unitind1.
   *
   * @return string|null
   */
  public function getUnitind1()
  {
      return $this->unitind1;
  }

  /**
   * Set unitname1.
   *
   * @param string $unitname1
   *
   * @return Taxa
   */
  public function setUnitname1($unitname1)
  {
      $this->unitname1 = $unitname1;

      return $this;
  }

  /**
   * Get unitname1.
   *
   * @return string
   */
  public function getUnitname1()
  {
      return $this->unitname1;
  }

  /**
   * Set unitind2.
   *
   * @param string|null $unitind2
   *
   * @return Taxa
   */
  public function setUnitind2($unitind2 = null)
  {
      $this->unitind2 = $unitind2;

      return $this;
  }

  /**
   * Get unitind2.
   *
   * @return string|null
   */
  public function getUnitind2()
  {
      return $this->unitind2;
  }

  /**
   * Set unitname2.
   *
   * @param string|null $unitname2
   *
   * @return Taxa
   */
  public function setUnitname2($unitname2 = null)
  {
      $this->unitname2 = $unitname2;

      return $this;
  }

  /**
   * Get unitname2.
   *
   * @return string|null
   */
  public function getUnitname2()
  {
      return $this->unitname2;
  }

  /**
   * Set unitind3.
   *
   * @param string|null $unitind3
   *
   * @return Taxa
   */
  public function setUnitind3($unitind3 = null)
  {
      $this->unitind3 = $unitind3;

      return $this;
  }

  /**
   * Get unitind3.
   *
   * @return string|null
   */
  public function getUnitind3()
  {
      return $this->unitind3;
  }

  /**
   * Set unitname3.
   *
   * @param string|null $unitname3
   *
   * @return Taxa
   */
  public function setUnitname3($unitname3 = null)
  {
      $this->unitname3 = $unitname3;

      return $this;
  }

  /**
   * Get unitname3.
   *
   * @return string|null
   */
  public function getUnitname3()
  {
      return $this->unitname3;
  }

  /**
   * Set author.
   *
   * @param string|null $author
   *
   * @return Taxa
   */
  public function setAuthor($author = null)
  {
      $this->author = $author;

      return $this;
  }

  /**
   * Get author.
   *
   * @return string|null
   */
  public function getAuthor()
  {
      return $this->author;
  }

  /**
   * Set phylosortsequence.
   *
   * @param bool|null $phylosortsequence
   *
   * @return Taxa
   */
  public function setPhylosortsequence($phylosortsequence = null)
  {
      $this->phylosortsequence = $phylosortsequence;

      return $this;
  }

  /**
   * Get phylosortsequence.
   *
   * @return bool|null
   */
  public function getPhylosortsequence()
  {
      return $this->phylosortsequence;
  }

  /**
   * Set status.
   *
   * @param string|null $status
   *
   * @return Taxa
   */
  public function setStatus($status = null)
  {
      $this->status = $status;

      return $this;
  }

  /**
   * Get status.
   *
   * @return string|null
   */
  public function getStatus()
  {
      return $this->status;
  }

  /**
   * Set source.
   *
   * @param string|null $source
   *
   * @return Taxa
   */
  public function setSource($source = null)
  {
      $this->source = $source;

      return $this;
  }

  /**
   * Get source.
   *
   * @return string|null
   */
  public function getSource()
  {
      return $this->source;
  }

  /**
   * Set notes.
   *
   * @param string|null $notes
   *
   * @return Taxa
   */
  public function setNotes($notes = null)
  {
      $this->notes = $notes;

      return $this;
  }

  /**
   * Get notes.
   *
   * @return string|null
   */
  public function getNotes()
  {
      return $this->notes;
  }

  /**
   * Set hybrid.
   *
   * @param string|null $hybrid
   *
   * @return Taxa
   */
  public function setHybrid($hybrid = null)
  {
      $this->hybrid = $hybrid;

      return $this;
  }

  /**
   * Get hybrid.
   *
   * @return string|null
   */
  public function getHybrid()
  {
      return $this->hybrid;
  }

  /**
   * Set securitystatus.
   *
   * @param int $securitystatus
   *
   * @return Taxa
   */
  public function setSecuritystatus($securitystatus)
  {
      $this->securitystatus = $securitystatus;

      return $this;
  }

  /**
   * Get securitystatus.
   *
   * @return int
   */
  public function getSecuritystatus()
  {
      return $this->securitystatus;
  }

  /**
   * Set modifiedtimestamp.
   *
   * @param DateTime|null $modifiedtimestamp
   *
   * @return Taxa
   */
  public function setModifiedtimestamp($modifiedtimestamp = null)
  {
      $this->modifiedtimestamp = $modifiedtimestamp;

      return $this;
  }

  /**
   * Get modifiedtimestamp.
   *
   * @return DateTime|null
   */
  public function getModifiedtimestamp()
  {
      return $this->modifiedtimestamp;
  }

  /**
   * Set initialtimestamp.
   *
   * @param DateTime $initialtimestamp
   *
   * @return Taxa
   */
  public function setInitialtimestamp($initialtimestamp)
  {
      $this->initialtimestamp = $initialtimestamp;

      return $this;
  }

  /**
   * Get initialtimestamp.
   *
   * @return DateTime
   */
  public function getInitialtimestamp()
  {
      return $this->initialtimestamp;
  }

  /**
   * Set modifieduid.
   *
   * @param integer $modifieduid
   *
   * @return Taxa
   */
  public function setModifieduid($modifieduid = null)
  {
      $this->modifieduid = $modifieduid;

      return $this;
  }

  /**
   * Get modifieduid.
   *
   * @return integer
   */
  public function getModifieduid()
  {
      return $this->modifieduid;
  }

/**
 * @return ArrayCollection
 */
public function getAttributes() {
  return $this->attributes;
}
}
