<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Kmcs
 *
 * @ORM\Table(name="kmcs", indexes={@ORM\Index(name="FK_cs_chars", columns={"cid"})})
 * @ORM\Entity
 */
class Kmcs
{
    /**
     * @var string
     *
     * @ORM\Column(name="cs", type="string", length=16, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $cs;

    /**
     * @var string|null
     *
     * @ORM\Column(name="CharStateName", type="string", length=255, nullable=true)
     */
    private $charstatename;

    /**
     * @var bool
     *
     * @ORM\Column(name="Implicit", type="boolean", nullable=false)
     */
    private $implicit = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="Notes", type="text", length=0, nullable=true)
     */
    private $notes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="IllustrationUrl", type="string", length=250, nullable=true)
     */
    private $illustrationurl;

    /**
     * @var int|null
     *
     * @ORM\Column(name="StateID", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $stateid;

    /**
     * @var int|null
     *
     * @ORM\Column(name="SortSequence", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $sortsequence;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="InitialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var string|null
     *
     * @ORM\Column(name="EnteredBy", type="string", length=45, nullable=true)
     */
    private $enteredby;

    /**
     * @var \Kmcharacters
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Kmcharacters", inversedBy="states")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cid", referencedColumnName="cid")
     * })
     */
    private $cid;


    /**
     * Set cs.
     *
     * @param string $cs
     *
     * @return Kmcs
     */
    public function setCs($cs)
    {
        $this->cs = $cs;

        return $this;
    }

    /**
     * Get cs.
     *
     * @return string
     */
    public function getCs()
    {
        return $this->cs;
    }

    /**
     * Set charstatename.
     *
     * @param string|null $charstatename
     *
     * @return Kmcs
     */
    public function setCharstatename($charstatename = null)
    {
        $this->charstatename = $charstatename;

        return $this;
    }

    /**
     * Get charstatename.
     *
     * @return string|null
     */
    public function getCharstatename()
    {
        return $this->charstatename;
    }

    /**
     * Set implicit.
     *
     * @param bool $implicit
     *
     * @return Kmcs
     */
    public function setImplicit($implicit)
    {
        $this->implicit = $implicit;

        return $this;
    }

    /**
     * Get implicit.
     *
     * @return bool
     */
    public function getImplicit()
    {
        return $this->implicit;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Kmcs
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
     * Set description.
     *
     * @param string|null $description
     *
     * @return Kmcs
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
     * Set illustrationurl.
     *
     * @param string|null $illustrationurl
     *
     * @return Kmcs
     */
    public function setIllustrationurl($illustrationurl = null)
    {
        $this->illustrationurl = $illustrationurl;

        return $this;
    }

    /**
     * Get illustrationurl.
     *
     * @return string|null
     */
    public function getIllustrationurl()
    {
        return $this->illustrationurl;
    }

    /**
     * Set stateid.
     *
     * @param int|null $stateid
     *
     * @return Kmcs
     */
    public function setStateid($stateid = null)
    {
        $this->stateid = $stateid;

        return $this;
    }

    /**
     * Get stateid.
     *
     * @return int|null
     */
    public function getStateid()
    {
        return $this->stateid;
    }

    /**
     * Set sortsequence.
     *
     * @param int|null $sortsequence
     *
     * @return Kmcs
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
     * @return Kmcs
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
     * Set enteredby.
     *
     * @param string|null $enteredby
     *
     * @return Kmcs
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
     * Set cid.
     *
     * @param \Kmcharacters $cid
     *
     * @return Kmcs
     */
    public function setCid(\Kmcharacters $cid)
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * Get cid.
     *
     * @return \Kmcharacters
     */
    public function getCid()
    {
        return $this->cid;
    }
}
