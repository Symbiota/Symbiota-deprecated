<?php


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Kmcharacters
 *
 * @ORM\Table(name="kmcharacters", indexes={@ORM\Index(name="Index_sort", columns={"sortsequence"}), @ORM\Index(name="FK_charheading_idx", columns={"hid"}), @ORM\Index(name="Index_charname", columns={"charname"})})
 * @ORM\Entity
 */
class Kmcharacters
{
    /**
     * @var int
     *
     * @ORM\Column(name="cid", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $cid;

    /**
     * @var string
     *
     * @ORM\Column(name="charname", type="string", length=150, nullable=false)
     */
    private $charname;

    /**
     * @var string
     *
     * @ORM\Column(name="chartype", type="string", length=2, nullable=false, options={"default"="UM"})
     */
    private $chartype = 'UM';

    /**
     * @var string
     *
     * @ORM\Column(name="defaultlang", type="string", length=45, nullable=false, options={"default"="English"})
     */
    private $defaultlang = 'English';

    /**
     * @var int
     *
     * @ORM\Column(name="difficultyrank", type="smallint", nullable=false, options={"default"="1","unsigned"=true})
     */
    private $difficultyrank = '1';

    /**
     * @var string|null
     *
     * @ORM\Column(name="units", type="string", length=45, nullable=true)
     */
    private $units;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     */
    private $notes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="display", type="string", length=45, nullable=true)
     */
    private $display;

    /**
     * @var string|null
     *
     * @ORM\Column(name="helpurl", type="string", length=500, nullable=true)
     */
    private $helpurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="enteredby", type="string", length=45, nullable=true)
     */
    private $enteredby;

    /**
     * @var int|null
     *
     * @ORM\Column(name="sortsequence", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $sortsequence;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="initialtimestamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var \Kmcharheading
     *
     * @ORM\ManyToOne(targetEntity="Kmcharheading")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="hid", referencedColumnName="hid")
     * })
     */
    private $hid;

  /**
   * @var ArrayCollection
   * @ORM\OneToMany(targetEntity="kmcs", mappedBy="cid")
   */
    private $states;

    public function __construct() {
      $this->states = new ArrayCollection();
    }

    public function getStates() {
      return $this->states;
    }


  /**
     * Get cid.
     *
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Set charname.
     *
     * @param string $charname
     *
     * @return Kmcharacters
     */
    public function setCharname($charname)
    {
        $this->charname = $charname;

        return $this;
    }

    /**
     * Get charname.
     *
     * @return string
     */
    public function getCharname()
    {
        return $this->charname;
    }

    /**
     * Set chartype.
     *
     * @param string $chartype
     *
     * @return Kmcharacters
     */
    public function setChartype($chartype)
    {
        $this->chartype = $chartype;

        return $this;
    }

    /**
     * Get chartype.
     *
     * @return string
     */
    public function getChartype()
    {
        return $this->chartype;
    }

    /**
     * Set defaultlang.
     *
     * @param string $defaultlang
     *
     * @return Kmcharacters
     */
    public function setDefaultlang($defaultlang)
    {
        $this->defaultlang = $defaultlang;

        return $this;
    }

    /**
     * Get defaultlang.
     *
     * @return string
     */
    public function getDefaultlang()
    {
        return $this->defaultlang;
    }

    /**
     * Set difficultyrank.
     *
     * @param int $difficultyrank
     *
     * @return Kmcharacters
     */
    public function setDifficultyrank($difficultyrank)
    {
        $this->difficultyrank = $difficultyrank;

        return $this;
    }

    /**
     * Get difficultyrank.
     *
     * @return int
     */
    public function getDifficultyrank()
    {
        return $this->difficultyrank;
    }

    /**
     * Set units.
     *
     * @param string|null $units
     *
     * @return Kmcharacters
     */
    public function setUnits($units = null)
    {
        $this->units = $units;

        return $this;
    }

    /**
     * Get units.
     *
     * @return string|null
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return Kmcharacters
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Kmcharacters
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
     * Set display.
     *
     * @param string|null $display
     *
     * @return Kmcharacters
     */
    public function setDisplay($display = null)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display.
     *
     * @return string|null
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set helpurl.
     *
     * @param string|null $helpurl
     *
     * @return Kmcharacters
     */
    public function setHelpurl($helpurl = null)
    {
        $this->helpurl = $helpurl;

        return $this;
    }

    /**
     * Get helpurl.
     *
     * @return string|null
     */
    public function getHelpurl()
    {
        return $this->helpurl;
    }

    /**
     * Set enteredby.
     *
     * @param string|null $enteredby
     *
     * @return Kmcharacters
     */
    public function setEnteredby($enteredby = null)
    {
        $this->enteredby = $enteredby;

        return $this;
    }

    /**
     * Get enteredby.
     *
     * @return string|null
     */
    public function getEnteredby()
    {
        return $this->enteredby;
    }

    /**
     * Set sortsequence.
     *
     * @param int|null $sortsequence
     *
     * @return Kmcharacters
     */
    public function setSortsequence($sortsequence = null)
    {
        $this->sortsequence = $sortsequence;

        return $this;
    }

    /**
     * Get sortsequence.
     *
     * @return int|null
     */
    public function getSortsequence()
    {
        return $this->sortsequence;
    }

    /**
     * Set initialtimestamp.
     *
     * @param \DateTime $initialtimestamp
     *
     * @return Kmcharacters
     */
    public function setInitialtimestamp($initialtimestamp)
    {
        $this->initialtimestamp = $initialtimestamp;

        return $this;
    }

    /**
     * Get initialtimestamp.
     *
     * @return \DateTime
     */
    public function getInitialtimestamp()
    {
        return $this->initialtimestamp;
    }

    /**
     * Set hid.
     *
     * @param \Kmcharheading|null $hid
     *
     * @return Kmcharacters
     */
    public function setHid(\Kmcharheading $hid = null)
    {
        $this->hid = $hid;

        return $this;
    }

    /**
     * Get hid.
     *
     * @return \Kmcharheading|null
     */
    public function getHid()
    {
        return $this->hid;
    }
}
