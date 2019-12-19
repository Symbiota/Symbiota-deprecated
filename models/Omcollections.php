<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Omcollections
 *
 * @ORM\Table(name="omcollections", uniqueConstraints={@ORM\UniqueConstraint(name="Index_inst", columns={"InstitutionCode", "CollectionCode"})}, indexes={@ORM\Index(name="FK_collid_iid_idx", columns={"iid"})})
 * @ORM\Entity
 */
class Omcollections
{
    /**
     * @var int
     *
     * @ORM\Column(name="CollID", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $collid;

    /**
     * @var string
     *
     * @ORM\Column(name="InstitutionCode", type="string", length=45, nullable=false)
     */
    private $institutioncode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="CollectionCode", type="string", length=45, nullable=true)
     */
    private $collectioncode;

    /**
     * @var string
     *
     * @ORM\Column(name="CollectionName", type="string", length=150, nullable=false)
     */
    private $collectionname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="collectionId", type="string", length=100, nullable=true)
     */
    private $collectionid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="datasetName", type="string", length=100, nullable=true)
     */
    private $datasetname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fulldescription", type="string", length=2000, nullable=true)
     */
    private $fulldescription;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Homepage", type="string", length=250, nullable=true)
     */
    private $homepage;

    /**
     * @var string|null
     *
     * @ORM\Column(name="IndividualUrl", type="string", length=500, nullable=true)
     */
    private $individualurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Contact", type="string", length=250, nullable=true)
     */
    private $contact;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=45, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="latitudedecimal", type="decimal", precision=8, scale=6, nullable=true)
     */
    private $latitudedecimal;

    /**
     * @var string|null
     *
     * @ORM\Column(name="longitudedecimal", type="decimal", precision=9, scale=6, nullable=true)
     */
    private $longitudedecimal;

    /**
     * @var string|null
     *
     * @ORM\Column(name="icon", type="string", length=250, nullable=true)
     */
    private $icon;

    /**
     * @var string
     *
     * @ORM\Column(name="CollType", type="string", length=45, nullable=false, options={"default"="Preserved Specimens","comment"="Preserved Specimens, General Observations, Observations"})
     */
    private $colltype = 'Preserved Specimens';

    /**
     * @var string|null
     *
     * @ORM\Column(name="ManagementType", type="string", length=45, nullable=true, options={"default"="Snapshot","comment"="Snapshot, Live Data"})
     */
    private $managementtype = 'Snapshot';

    /**
     * @var int
     *
     * @ORM\Column(name="PublicEdits", type="integer", nullable=false, options={"default"="1","unsigned"=true})
     */
    private $publicedits = '1';

    /**
     * @var string|null
     *
     * @ORM\Column(name="collectionguid", type="string", length=45, nullable=true)
     */
    private $collectionguid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="securitykey", type="string", length=45, nullable=true)
     */
    private $securitykey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="guidtarget", type="string", length=45, nullable=true)
     */
    private $guidtarget;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rightsHolder", type="string", length=250, nullable=true)
     */
    private $rightsholder;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rights", type="string", length=250, nullable=true)
     */
    private $rights;

    /**
     * @var string|null
     *
     * @ORM\Column(name="usageTerm", type="string", length=250, nullable=true)
     */
    private $usageterm;

    /**
     * @var int|null
     *
     * @ORM\Column(name="publishToGbif", type="integer", nullable=true)
     */
    private $publishtogbif;

    /**
     * @var int|null
     *
     * @ORM\Column(name="publishToIdigbio", type="integer", nullable=true)
     */
    private $publishtoidigbio;

    /**
     * @var string|null
     *
     * @ORM\Column(name="aggKeysStr", type="string", length=1000, nullable=true)
     */
    private $aggkeysstr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dwcaUrl", type="string", length=250, nullable=true)
     */
    private $dwcaurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bibliographicCitation", type="string", length=1000, nullable=true)
     */
    private $bibliographiccitation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="accessrights", type="string", length=1000, nullable=true)
     */
    private $accessrights;

    /**
     * @var int|null
     *
     * @ORM\Column(name="SortSeq", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $sortseq;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="InitialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var \Institutions
     *
     * @ORM\ManyToOne(targetEntity="Institutions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="iid", referencedColumnName="iid")
     * })
     */
    private $iid;


    /**
     * Get collid.
     *
     * @return int
     */
    public function getCollid()
    {
        return $this->collid;
    }

    /**
     * Set institutioncode.
     *
     * @param string $institutioncode
     *
     * @return Omcollections
     */
    public function setInstitutioncode($institutioncode)
    {
        $this->institutioncode = $institutioncode;

        return $this;
    }

    /**
     * Get institutioncode.
     *
     * @return string
     */
    public function getInstitutioncode()
    {
        return $this->institutioncode;
    }

    /**
     * Set collectioncode.
     *
     * @param string|null $collectioncode
     *
     * @return Omcollections
     */
    public function setCollectioncode($collectioncode = null)
    {
        $this->collectioncode = $collectioncode;

        return $this;
    }

    /**
     * Get collectioncode.
     *
     * @return string|null
     */
    public function getCollectioncode()
    {
        return $this->collectioncode;
    }

    /**
     * Set collectionname.
     *
     * @param string $collectionname
     *
     * @return Omcollections
     */
    public function setCollectionname($collectionname)
    {
        $this->collectionname = $collectionname;

        return $this;
    }

    /**
     * Get collectionname.
     *
     * @return string
     */
    public function getCollectionname()
    {
        return $this->collectionname;
    }

    /**
     * Set collectionid.
     *
     * @param string|null $collectionid
     *
     * @return Omcollections
     */
    public function setCollectionid($collectionid = null)
    {
        $this->collectionid = $collectionid;

        return $this;
    }

    /**
     * Get collectionid.
     *
     * @return string|null
     */
    public function getCollectionid()
    {
        return $this->collectionid;
    }

    /**
     * Set datasetname.
     *
     * @param string|null $datasetname
     *
     * @return Omcollections
     */
    public function setDatasetname($datasetname = null)
    {
        $this->datasetname = $datasetname;

        return $this;
    }

    /**
     * Get datasetname.
     *
     * @return string|null
     */
    public function getDatasetname()
    {
        return $this->datasetname;
    }

    /**
     * Set fulldescription.
     *
     * @param string|null $fulldescription
     *
     * @return Omcollections
     */
    public function setFulldescription($fulldescription = null)
    {
        $this->fulldescription = $fulldescription;

        return $this;
    }

    /**
     * Get fulldescription.
     *
     * @return string|null
     */
    public function getFulldescription()
    {
        return $this->fulldescription;
    }

    /**
     * Set homepage.
     *
     * @param string|null $homepage
     *
     * @return Omcollections
     */
    public function setHomepage($homepage = null)
    {
        $this->homepage = $homepage;

        return $this;
    }

    /**
     * Get homepage.
     *
     * @return string|null
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * Set individualurl.
     *
     * @param string|null $individualurl
     *
     * @return Omcollections
     */
    public function setIndividualurl($individualurl = null)
    {
        $this->individualurl = $individualurl;

        return $this;
    }

    /**
     * Get individualurl.
     *
     * @return string|null
     */
    public function getIndividualurl()
    {
        return $this->individualurl;
    }

    /**
     * Set contact.
     *
     * @param string|null $contact
     *
     * @return Omcollections
     */
    public function setContact($contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return string|null
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return Omcollections
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set latitudedecimal.
     *
     * @param string|null $latitudedecimal
     *
     * @return Omcollections
     */
    public function setLatitudedecimal($latitudedecimal = null)
    {
        $this->latitudedecimal = $latitudedecimal;

        return $this;
    }

    /**
     * Get latitudedecimal.
     *
     * @return string|null
     */
    public function getLatitudedecimal()
    {
        return $this->latitudedecimal;
    }

    /**
     * Set longitudedecimal.
     *
     * @param string|null $longitudedecimal
     *
     * @return Omcollections
     */
    public function setLongitudedecimal($longitudedecimal = null)
    {
        $this->longitudedecimal = $longitudedecimal;

        return $this;
    }

    /**
     * Get longitudedecimal.
     *
     * @return string|null
     */
    public function getLongitudedecimal()
    {
        return $this->longitudedecimal;
    }

    /**
     * Set icon.
     *
     * @param string|null $icon
     *
     * @return Omcollections
     */
    public function setIcon($icon = null)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon.
     *
     * @return string|null
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set colltype.
     *
     * @param string $colltype
     *
     * @return Omcollections
     */
    public function setColltype($colltype)
    {
        $this->colltype = $colltype;

        return $this;
    }

    /**
     * Get colltype.
     *
     * @return string
     */
    public function getColltype()
    {
        return $this->colltype;
    }

    /**
     * Set managementtype.
     *
     * @param string|null $managementtype
     *
     * @return Omcollections
     */
    public function setManagementtype($managementtype = null)
    {
        $this->managementtype = $managementtype;

        return $this;
    }

    /**
     * Get managementtype.
     *
     * @return string|null
     */
    public function getManagementtype()
    {
        return $this->managementtype;
    }

    /**
     * Set publicedits.
     *
     * @param int $publicedits
     *
     * @return Omcollections
     */
    public function setPublicedits($publicedits)
    {
        $this->publicedits = $publicedits;

        return $this;
    }

    /**
     * Get publicedits.
     *
     * @return int
     */
    public function getPublicedits()
    {
        return $this->publicedits;
    }

    /**
     * Set collectionguid.
     *
     * @param string|null $collectionguid
     *
     * @return Omcollections
     */
    public function setCollectionguid($collectionguid = null)
    {
        $this->collectionguid = $collectionguid;

        return $this;
    }

    /**
     * Get collectionguid.
     *
     * @return string|null
     */
    public function getCollectionguid()
    {
        return $this->collectionguid;
    }

    /**
     * Set securitykey.
     *
     * @param string|null $securitykey
     *
     * @return Omcollections
     */
    public function setSecuritykey($securitykey = null)
    {
        $this->securitykey = $securitykey;

        return $this;
    }

    /**
     * Get securitykey.
     *
     * @return string|null
     */
    public function getSecuritykey()
    {
        return $this->securitykey;
    }

    /**
     * Set guidtarget.
     *
     * @param string|null $guidtarget
     *
     * @return Omcollections
     */
    public function setGuidtarget($guidtarget = null)
    {
        $this->guidtarget = $guidtarget;

        return $this;
    }

    /**
     * Get guidtarget.
     *
     * @return string|null
     */
    public function getGuidtarget()
    {
        return $this->guidtarget;
    }

    /**
     * Set rightsholder.
     *
     * @param string|null $rightsholder
     *
     * @return Omcollections
     */
    public function setRightsholder($rightsholder = null)
    {
        $this->rightsholder = $rightsholder;

        return $this;
    }

    /**
     * Get rightsholder.
     *
     * @return string|null
     */
    public function getRightsholder()
    {
        return $this->rightsholder;
    }

    /**
     * Set rights.
     *
     * @param string|null $rights
     *
     * @return Omcollections
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
     * Set usageterm.
     *
     * @param string|null $usageterm
     *
     * @return Omcollections
     */
    public function setUsageterm($usageterm = null)
    {
        $this->usageterm = $usageterm;

        return $this;
    }

    /**
     * Get usageterm.
     *
     * @return string|null
     */
    public function getUsageterm()
    {
        return $this->usageterm;
    }

    /**
     * Set publishtogbif.
     *
     * @param int|null $publishtogbif
     *
     * @return Omcollections
     */
    public function setPublishtogbif($publishtogbif = null)
    {
        $this->publishtogbif = $publishtogbif;

        return $this;
    }

    /**
     * Get publishtogbif.
     *
     * @return int|null
     */
    public function getPublishtogbif()
    {
        return $this->publishtogbif;
    }

    /**
     * Set publishtoidigbio.
     *
     * @param int|null $publishtoidigbio
     *
     * @return Omcollections
     */
    public function setPublishtoidigbio($publishtoidigbio = null)
    {
        $this->publishtoidigbio = $publishtoidigbio;

        return $this;
    }

    /**
     * Get publishtoidigbio.
     *
     * @return int|null
     */
    public function getPublishtoidigbio()
    {
        return $this->publishtoidigbio;
    }

    /**
     * Set aggkeysstr.
     *
     * @param string|null $aggkeysstr
     *
     * @return Omcollections
     */
    public function setAggkeysstr($aggkeysstr = null)
    {
        $this->aggkeysstr = $aggkeysstr;

        return $this;
    }

    /**
     * Get aggkeysstr.
     *
     * @return string|null
     */
    public function getAggkeysstr()
    {
        return $this->aggkeysstr;
    }

    /**
     * Set dwcaurl.
     *
     * @param string|null $dwcaurl
     *
     * @return Omcollections
     */
    public function setDwcaurl($dwcaurl = null)
    {
        $this->dwcaurl = $dwcaurl;

        return $this;
    }

    /**
     * Get dwcaurl.
     *
     * @return string|null
     */
    public function getDwcaurl()
    {
        return $this->dwcaurl;
    }

    /**
     * Set bibliographiccitation.
     *
     * @param string|null $bibliographiccitation
     *
     * @return Omcollections
     */
    public function setBibliographiccitation($bibliographiccitation = null)
    {
        $this->bibliographiccitation = $bibliographiccitation;

        return $this;
    }

    /**
     * Get bibliographiccitation.
     *
     * @return string|null
     */
    public function getBibliographiccitation()
    {
        return $this->bibliographiccitation;
    }

    /**
     * Set accessrights.
     *
     * @param string|null $accessrights
     *
     * @return Omcollections
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
     * Set sortseq.
     *
     * @param int|null $sortseq
     *
     * @return Omcollections
     */
    public function setSortseq($sortseq = null)
    {
        $this->sortseq = $sortseq;

        return $this;
    }

    /**
     * Get sortseq.
     *
     * @return int|null
     */
    public function getSortseq()
    {
        return $this->sortseq;
    }

    /**
     * Set initialtimestamp.
     *
     * @param \DateTime $initialtimestamp
     *
     * @return Omcollections
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
     * Set iid.
     *
     * @param \Institutions|null $iid
     *
     * @return Omcollections
     */
    public function setIid(\Institutions $iid = null)
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * Get iid.
     *
     * @return \Institutions|null
     */
    public function getIid()
    {
        return $this->iid;
    }
}
