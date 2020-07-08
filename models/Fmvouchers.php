<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Fmvouchers
 *
 * @ORM\Table(name="fmvouchers", uniqueConstraints={@ORM\UniqueConstraint(name="PRIMARY", columns={"occid", "CLID"})}, indexes={@ORM\Index(name="chklst_taxavouchers", columns={"TID","CLID"})})
 * @ORM\Entity
 */
class Fmvouchers
{

    /**
     * @var int
     * 
     * @ORM\Id
     * @ORM\Column(name="TID", type="integer")
     */
    private $tid;
    
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="CLID", type="integer", nullable=false)
     */
    private $clid;

    /**
     * @var int
     *
     * @ORM\Column(name="occid", type="integer", nullable=false)
     */
    private $occid;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="editornotes", type="string", length=50, nullable=true)
     */
    private $editornotes;

    /**
     * @var int
     *
     * @ORM\Column(name="preferredImage", type="integer", nullable=false)
     */
    private $preferredimage;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=250, nullable=true)
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="initialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * Set tid.
     *
     * @param int $tid
     *
     * @return Fmvouchers
     */
    public function setTid($tid)
    {
        $this->tid = $tid;
        return $this;
    }

    /**
     * Get tid.
     *
     * @return string
     */
    public function getTid()
    {
        return $this->tid;
    }


    /**
     * Set clid.
     *
     * @param int $clid
     *
     * @return Fmvouchers
     */
    public function setClid($clid)
    {
        $this->clid = $clid;

        return $this;
    }

    /**
     * Get clid.
     *
     * @return string
     */
    public function getClid()
    {
        return $this->clid;
    }


    /**
     * Set occid.
     *
     * @param int $occid
     *
     * @return Fmvouchers
     */
    public function setOccid($occid)
    {
        $this->occid = $occid;

        return $this;
    }

    /**
     * Get occid.
     *
     * @return string
     */
    public function getOccid()
    {
        return $this->occid;
    }

    /**
     * Set editornotes.
     *
     * @param string|null $editornotes
     *
     * @return Fmvouchers
     */
    public function setEditorNotes($editornotes = null)
    {
        $this->editornotes = $editornotes;

        return $this;
    }

    /**
     * Get editornotes.
     *
     * @return string|null
     */
    public function getEditorNotes()
    {
        return $this->editornotes;
    }
        
    /**
     * Set preferredimage.
     *
     * @param string|null $preferredimage
     *
     * @return Fmvouchers
     */
    public function setPreferredImage($preferredimage = null)
    {
        $this->preferredimage = $preferredimage;

        return $this;
    }

    /**
     * Get preferredimage.
     *
     * @return string|null
     */
    public function getPreferredImage()
    {
        return $this->preferredimage;
    }
    
    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Fmvouchers
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
     * Set initialtimestamp.
     *
     * @param \DateTime $initialtimestamp
     *
     * @return Fmvouchers
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
}
