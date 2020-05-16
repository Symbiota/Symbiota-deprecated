<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Images
 * @ORM\Cache("READ_ONLY")
 * @ORM\Table(name="images", indexes={@ORM\Index(name="FK_images_occ", columns={"occid"}), @ORM\Index(name="FK_photographeruid", columns={"photographeruid"}), @ORM\Index(name="Index_tid", columns={"tid"}), @ORM\Index(name="Index_images_datelastmod", columns={"InitialTimeStamp"})})
 * @ORM\Entity
 */
class Images
{
    /**
     * @var int
     *
     * @ORM\Column(name="imgid", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $imgid;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="thumbnailurl", type="string", length=255, nullable=true)
     */
    private $thumbnailurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="originalurl", type="string", length=255, nullable=true)
     */
    private $originalurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="archiveurl", type="string", length=255, nullable=true)
     */
    private $archiveurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="photographer", type="string", length=100, nullable=true)
     */
    private $photographer;

    /**
     * @var string|null
     *
     * @ORM\Column(name="imagetype", type="string", length=50, nullable=true)
     */
    private $imagetype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="format", type="string", length=45, nullable=true)
     */
    private $format;

    /**
     * @var string|null
     *
     * @ORM\Column(name="caption", type="string", length=100, nullable=true)
     */
    private $caption;

    /**
     * @var string|null
     *
     * @ORM\Column(name="owner", type="string", length=250, nullable=true)
     */
    private $owner;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sourceurl", type="string", length=255, nullable=true)
     */
    private $sourceurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="referenceUrl", type="string", length=255, nullable=true)
     */
    private $referenceurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="copyright", type="string", length=255, nullable=true)
     */
    private $copyright;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rights", type="string", length=255, nullable=true)
     */
    private $rights;

    /**
     * @var string|null
     *
     * @ORM\Column(name="accessrights", type="string", length=255, nullable=true)
     */
    private $accessrights;

    /**
     * @var string|null
     *
     * @ORM\Column(name="locality", type="string", length=250, nullable=true)
     */
    private $locality;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=350, nullable=true)
     */
    private $notes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="anatomy", type="string", length=100, nullable=true)
     */
    private $anatomy;

    /**
     * @var string|null
     *
     * @ORM\Column(name="username", type="string", length=45, nullable=true)
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sourceIdentifier", type="string", length=150, nullable=true)
     */
    private $sourceidentifier;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mediaMD5", type="string", length=45, nullable=true)
     */
    private $mediamd5;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dynamicProperties", type="text", length=65535, nullable=true)
     */
    private $dynamicproperties;

    /**
     * @var int
     *
     * @ORM\Column(name="sortsequence", type="integer", nullable=false, options={"default"="50","unsigned"=true})
     */
    private $sortsequence = '50';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="InitialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var \Omoccurrences
     *
     * @ORM\ManyToOne(targetEntity="Omoccurrences")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="occid", referencedColumnName="occid")
     * })
     */
    private $occid;
  

    /**
     * @var integer
     *
     */
    private $photographeruid;

    /**
     * @var \Taxa
     *
     * @ORM\ManyToOne(targetEntity="Taxa", inversedBy="images")
     * @ORM\Cache("READ_ONLY")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tid", referencedColumnName="TID")
     * })
     */
    private $tid;


    /**
     * Get imgid.
     *
     * @return int
     */
    public function getImgid()
    {
        return $this->imgid;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return Images
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set thumbnailurl.
     *
     * @param string|null $thumbnailurl
     *
     * @return Images
     */
    public function setThumbnailurl($thumbnailurl = null)
    {
        $this->thumbnailurl = $thumbnailurl;

        return $this;
    }

    /**
     * Get thumbnailurl.
     *
     * @return string|null
     */
    public function getThumbnailurl()
    {
        return $this->thumbnailurl;
    }

    /**
     * Set originalurl.
     *
     * @param string|null $originalurl
     *
     * @return Images
     */
    public function setOriginalurl($originalurl = null)
    {
        $this->originalurl = $originalurl;

        return $this;
    }

    /**
     * Get originalurl.
     *
     * @return string|null
     */
    public function getOriginalurl()
    {
        return $this->originalurl;
    }

    /**
     * Set archiveurl.
     *
     * @param string|null $archiveurl
     *
     * @return Images
     */
    public function setArchiveurl($archiveurl = null)
    {
        $this->archiveurl = $archiveurl;

        return $this;
    }

    /**
     * Get archiveurl.
     *
     * @return string|null
     */
    public function getArchiveurl()
    {
        return $this->archiveurl;
    }

    /**
     * Set photographer.
     *
     * @param string|null $photographer
     *
     * @return Images
     */
    public function setPhotographer($photographer = null)
    {
        $this->photographer = $photographer;

        return $this;
    }

    /**
     * Get photographer.
     *
     * @return string|null
     */
    public function getPhotographer()
    {
        return $this->photographer;
    }

    /**
     * Set imagetype.
     *
     * @param string|null $imagetype
     *
     * @return Images
     */
    public function setImagetype($imagetype = null)
    {
        $this->imagetype = $imagetype;

        return $this;
    }

    /**
     * Get imagetype.
     *
     * @return string|null
     */
    public function getImagetype()
    {
        return $this->imagetype;
    }

    /**
     * Set format.
     *
     * @param string|null $format
     *
     * @return Images
     */
    public function setFormat($format = null)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format.
     *
     * @return string|null
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set caption.
     *
     * @param string|null $caption
     *
     * @return Images
     */
    public function setCaption($caption = null)
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Get caption.
     *
     * @return string|null
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Set owner.
     *
     * @param string|null $owner
     *
     * @return Images
     */
    public function setOwner($owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner.
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set sourceurl.
     *
     * @param string|null $sourceurl
     *
     * @return Images
     */
    public function setSourceurl($sourceurl = null)
    {
        $this->sourceurl = $sourceurl;

        return $this;
    }

    /**
     * Get sourceurl.
     *
     * @return string|null
     */
    public function getSourceurl()
    {
        return $this->sourceurl;
    }

    /**
     * Set referenceurl.
     *
     * @param string|null $referenceurl
     *
     * @return Images
     */
    public function setReferenceurl($referenceurl = null)
    {
        $this->referenceurl = $referenceurl;

        return $this;
    }

    /**
     * Get referenceurl.
     *
     * @return string|null
     */
    public function getReferenceurl()
    {
        return $this->referenceurl;
    }

    /**
     * Set copyright.
     *
     * @param string|null $copyright
     *
     * @return Images
     */
    public function setCopyright($copyright = null)
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * Get copyright.
     *
     * @return string|null
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * Set rights.
     *
     * @param string|null $rights
     *
     * @return Images
     */
    public function setRights($rights = null)
    {
        $this->rights = $rights;

        return $this;
    }

    /**
     * Get rights.
     *
     * @return string|null
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Set accessrights.
     *
     * @param string|null $accessrights
     *
     * @return Images
     */
    public function setAccessrights($accessrights = null)
    {
        $this->accessrights = $accessrights;

        return $this;
    }

    /**
     * Get accessrights.
     *
     * @return string|null
     */
    public function getAccessrights()
    {
        return $this->accessrights;
    }

    /**
     * Set locality.
     *
     * @param string|null $locality
     *
     * @return Images
     */
    public function setLocality($locality = null)
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * Get locality.
     *
     * @return string|null
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Images
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
     * Set anatomy.
     *
     * @param string|null $anatomy
     *
     * @return Images
     */
    public function setAnatomy($anatomy = null)
    {
        $this->anatomy = $anatomy;

        return $this;
    }

    /**
     * Get anatomy.
     *
     * @return string|null
     */
    public function getAnatomy()
    {
        return $this->anatomy;
    }

    /**
     * Set username.
     *
     * @param string|null $username
     *
     * @return Images
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
     * Set sourceidentifier.
     *
     * @param string|null $sourceidentifier
     *
     * @return Images
     */
    public function setSourceidentifier($sourceidentifier = null)
    {
        $this->sourceidentifier = $sourceidentifier;

        return $this;
    }

    /**
     * Get sourceidentifier.
     *
     * @return string|null
     */
    public function getSourceidentifier()
    {
        return $this->sourceidentifier;
    }

    /**
     * Set mediamd5.
     *
     * @param string|null $mediamd5
     *
     * @return Images
     */
    public function setMediamd5($mediamd5 = null)
    {
        $this->mediamd5 = $mediamd5;

        return $this;
    }

    /**
     * Get mediamd5.
     *
     * @return string|null
     */
    public function getMediamd5()
    {
        return $this->mediamd5;
    }

    /**
     * Set dynamicproperties.
     *
     * @param string|null $dynamicproperties
     *
     * @return Images
     */
    public function setDynamicproperties($dynamicproperties = null)
    {
        $this->dynamicproperties = $dynamicproperties;

        return $this;
    }

    /**
     * Get dynamicproperties.
     *
     * @return string|null
     */
    public function getDynamicproperties()
    {
        return $this->dynamicproperties;
    }

    /**
     * Set sortsequence.
     *
     * @param int $sortsequence
     *
     * @return Images
     */
    public function setSortsequence($sortsequence)
    {
        $this->sortsequence = $sortsequence;

        return $this;
    }

    /**
     * Get sortsequence.
     *
     * @return int
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
     * @return Images
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
     * Set occid.
     *
     * @param \Omoccurrences|null $occid
     *
     * @return Images
     */
    public function setOccid(\Omoccurrences $occid = null)
    {
        $this->occid = $occid;

        return $this;
    }

    /**
     * Get occid.
     *
     * @return \Omoccurrences|null
     */
    public function getOccid()
    {
        return $this->occid;
    }

    /**
     * Set photographeruid.
     *
     * @param integer|null $photographeruid
     *
     * @return Images
     */
    public function setPhotographeruid($photographeruid = null)
    {
        $this->photographeruid = $photographeruid;

        return $this;
    }

    /**
     * Get photographeruid.
     *
     * @return integer|null
     */
    public function getPhotographeruid()
    {
        return $this->photographeruid;
    }

    /**
     * Set tid.
     *
     * @param \Taxa|null $tid
     *
     * @return Images
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
