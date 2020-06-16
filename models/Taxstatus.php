<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Taxstatus
 *
 * @ORM\Table(name="taxstatus")
 * @ORM\Entity
 */
class Taxstatus
{
    /**
     * @var int
     *
     *
     * @ORM\Id
     * @ORM\Column(name="tidaccepted", type="integer", nullable=false)
     */
    private $tidaccepted;
    
    /**
     * @var \Taxa
     * 
     * @ORM\Id
     * @ORM\Column(name="tid", type="integer", nullable=false)
     */
    private $tid;
    
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="taxauthid", type="integer", nullable=false)
     */
    private $taxauthid;

    /**
     * @var int
     *
     * @ORM\Column(name="parenttid", type="integer", nullable=false)
     */
    private $parenttid;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="hierarchystr", type="string", length=200, nullable=true)
     */
    private $hierarchystr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="family", type="string", length=50, nullable=true)
     */
    private $family;

    /**
     * @var string|null
     *
     * @ORM\Column(name="UnacceptabilityReason", type="string", length=250, nullable=true)
     */
    private $unacceptabilityreason;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=250, nullable=true)
     */
    private $notes;
    
    /**
     * @var int|null
     *
     * @ORM\Column(name="SortSequence", type="integer", nullable=true, options={"default"="50"})
     */
    private $sortsequence = '50';
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="initialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * Set tidaccepted.
     *
     * @param int $tidaccepted
     *
     * @return Taxavernaculars
     */
    public function setTidAccepted($tidaccepted)
    {
        $this->tidaccepted = $tidaccepted;

        return $this;
    }

    /**
     * Get tidaccepted.
     *
     * @return string
     */
    public function getTidAccepted()
    {
        return $this->tidaccepted;
    }


    /**
     * Set taxauthid.
     *
     * @param int $taxauthid
     *
     * @return Taxavernaculars
     */
    public function setTaxauthid($taxauthid)
    {
        $this->taxauthid = $taxauthid;

        return $this;
    }

    /**
     * Get taxauthid.
     *
     * @return string
     */
    public function getTaxauthid()
    {
        return $this->taxauthid;
    }


    /**
     * Set parenttid.
     *
     * @param int $parenttid
     *
     * @return Taxavernaculars
     */
    public function setParenttid($parenttid)
    {
        $this->parenttid = $parenttid;

        return $this;
    }

    /**
     * Get parenttid.
     *
     * @return string
     */
    public function getParenttid()
    {
        return $this->parenttid;
    }

    /**
     * Set hierarchystr.
     *
     * @param string|null $hierarchystr
     *
     * @return Taxavernaculars
     */
    public function setHierarchystr($hierarchystr = null)
    {
        $this->hierarchystr = $hierarchystr;

        return $this;
    }

    /**
     * Get hierarchystr.
     *
     * @return string|null
     */
    public function getHierarchystr()
    {
        return $this->hierarchystr;
    }
        
    /**
     * Set family.
     *
     * @param string|null $family
     *
     * @return Taxavernaculars
     */
    public function setFamily($family = null)
    {
        $this->family = $family;

        return $this;
    }

    /**
     * Get family.
     *
     * @return string|null
     */
    public function getFamily()
    {
        return $this->family;
    }
    
    /**
     * Set unacceptabilityreason.
     *
     * @param string|null $unacceptabilityreason
     *
     * @return Taxavernaculars
     */
    public function setUnacceptabilityreason($unacceptabilityreason = null)
    {
        $this->unacceptabilityreason = $unacceptabilityreason;

        return $this;
    }

    /**
     * Get unacceptabilityreason.
     *
     * @return string|null
     */
    public function getUnacceptabilityreason()
    {
        return $this->unacceptabilityreason;
    }


    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Taxavernaculars
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
     * Set username.
     *
     * @param string|null $username
     *
     * @return Taxavernaculars
     */
    public function setUsername($username = null)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set sortsequence.
     *
     * @param int|null $sortsequence
     *
     * @return Taxavernaculars
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
     * @return Taxavernaculars
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
     * Set tid.
     *
     * @param \Taxa|null $tid
     *
     * @return Taxstatus
     */
    public function setTid(\Taxa $tid = null)
    {
        $this->tid = $tid;

        return $this;
    }

    /**
     * Get tid.
     *
     * @return \Taxa|null
     */
    public function getTid()
    {
        return $this->tid;
    }
}
