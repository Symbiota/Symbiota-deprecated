<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Omoccurrences
 *
 * @ORM\Table(name="omoccurrences", uniqueConstraints={@ORM\UniqueConstraint(name="Index_collid", columns={"collid", "dbpk"})}, indexes={@ORM\Index(name="Index_occurDateEntered", columns={"dateEntered"}), @ORM\Index(name="Index_otherCatalogNumbers", columns={"otherCatalogNumbers"}), @ORM\Index(name="Index_country", columns={"country"}), @ORM\Index(name="Index_collector", columns={"recordedBy"}), @ORM\Index(name="FK_omoccurrences_tid", columns={"tidinterpreted"}), @ORM\Index(name="Index_collnum", columns={"recordNumber"}), @ORM\Index(name="Index_eventDate", columns={"eventDate"}), @ORM\Index(name="occelevmin", columns={"minimumElevationInMeters"}), @ORM\Index(name="idx_occrecordedby", columns={"recordedBy"}), @ORM\Index(name="Index_occurRecordEnteredBy", columns={"recordEnteredBy"}), @ORM\Index(name="Index_latestDateCollected", columns={"latestDateCollected"}), @ORM\Index(name="Index_sciname", columns={"sciname"}), @ORM\Index(name="Index_state", columns={"stateProvince"}), @ORM\Index(name="Index_gui", columns={"occurrenceID"}), @ORM\Index(name="FK_omoccurrences_uid", columns={"observeruid"}), @ORM\Index(name="Index_catalognumber", columns={"catalogNumber"}), @ORM\Index(name="Index_occurrences_procstatus", columns={"processingstatus"}), @ORM\Index(name="Index_occurrences_cult", columns={"cultivationStatus"}), @ORM\Index(name="Index_occurDateLastModifed", columns={"dateLastModified"}), @ORM\Index(name="Index_locality", columns={"locality"}), @ORM\Index(name="Index_family", columns={"family"}), @ORM\Index(name="Index_county", columns={"county"}), @ORM\Index(name="Index_ownerInst", columns={"ownerInstitutionCode"}), @ORM\Index(name="Index_municipality", columns={"municipality"}), @ORM\Index(name="FK_recordedbyid", columns={"recordedbyid"}), @ORM\Index(name="occelevmax", columns={"maximumElevationInMeters"}), @ORM\Index(name="Index_occurrences_typestatus", columns={"typeStatus"}), @ORM\Index(name="IDX_C48904CFEA1D339B", columns={"collid"})})
 * @ORM\Entity
 */
class Omoccurrences
{
    /**
     * @var int
     *
     * @ORM\Column(name="occid", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $occid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dbpk", type="string", length=150, nullable=true)
     */
    private $dbpk;

    /**
     * @var string|null
     *
     * @ORM\Column(name="basisOfRecord", type="string", length=32, nullable=true, options={"default"="PreservedSpecimen","comment"="PreservedSpecimen, LivingSpecimen, HumanObservation"})
     */
    private $basisofrecord = 'PreservedSpecimen';

    /**
     * @var string|null
     *
     * @ORM\Column(name="occurrenceID", type="string", length=255, nullable=true, options={"comment"="UniqueGlobalIdentifier"})
     */
    private $occurrenceid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="catalogNumber", type="string", length=32, nullable=true)
     */
    private $catalognumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="otherCatalogNumbers", type="string", length=255, nullable=true)
     */
    private $othercatalognumbers;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ownerInstitutionCode", type="string", length=32, nullable=true)
     */
    private $ownerinstitutioncode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="institutionID", type="string", length=255, nullable=true)
     */
    private $institutionid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="collectionID", type="string", length=255, nullable=true)
     */
    private $collectionid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="datasetID", type="string", length=255, nullable=true)
     */
    private $datasetid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="institutionCode", type="string", length=64, nullable=true)
     */
    private $institutioncode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="collectionCode", type="string", length=64, nullable=true)
     */
    private $collectioncode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="family", type="string", length=255, nullable=true)
     */
    private $family;

    /**
     * @var string|null
     *
     * @ORM\Column(name="scientificName", type="string", length=255, nullable=true)
     */
    private $scientificname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sciname", type="string", length=255, nullable=true)
     */
    private $sciname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="genus", type="string", length=255, nullable=true)
     */
    private $genus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="specificEpithet", type="string", length=255, nullable=true)
     */
    private $specificepithet;

    /**
     * @var string|null
     *
     * @ORM\Column(name="taxonRank", type="string", length=32, nullable=true)
     */
    private $taxonrank;

    /**
     * @var string|null
     *
     * @ORM\Column(name="infraspecificEpithet", type="string", length=255, nullable=true)
     */
    private $infraspecificepithet;

    /**
     * @var string|null
     *
     * @ORM\Column(name="scientificNameAuthorship", type="string", length=255, nullable=true)
     */
    private $scientificnameauthorship;

    /**
     * @var string|null
     *
     * @ORM\Column(name="taxonRemarks", type="text", length=65535, nullable=true)
     */
    private $taxonremarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="identifiedBy", type="string", length=255, nullable=true)
     */
    private $identifiedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dateIdentified", type="string", length=45, nullable=true)
     */
    private $dateidentified;

    /**
     * @var string|null
     *
     * @ORM\Column(name="identificationReferences", type="text", length=65535, nullable=true)
     */
    private $identificationreferences;

    /**
     * @var string|null
     *
     * @ORM\Column(name="identificationRemarks", type="text", length=65535, nullable=true)
     */
    private $identificationremarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="identificationQualifier", type="string", length=255, nullable=true, options={"comment"="cf, aff, etc"})
     */
    private $identificationqualifier;

    /**
     * @var string|null
     *
     * @ORM\Column(name="typeStatus", type="string", length=255, nullable=true)
     */
    private $typestatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="recordedBy", type="string", length=255, nullable=true, options={"comment"="Collector(s)"})
     */
    private $recordedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="recordNumber", type="string", length=45, nullable=true, options={"comment"="Collector Number"})
     */
    private $recordnumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associatedCollectors", type="string", length=255, nullable=true, options={"comment"="not DwC"})
     */
    private $associatedcollectors;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="eventDate", type="date", nullable=true)
     */
    private $eventdate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="latestDateCollected", type="date", nullable=true)
     */
    private $latestdatecollected;

    /**
     * @var int|null
     *
     * @ORM\Column(name="year", type="integer", nullable=true)
     */
    private $year;

    /**
     * @var int|null
     *
     * @ORM\Column(name="month", type="integer", nullable=true)
     */
    private $month;

    /**
     * @var int|null
     *
     * @ORM\Column(name="day", type="integer", nullable=true)
     */
    private $day;

    /**
     * @var int|null
     *
     * @ORM\Column(name="startDayOfYear", type="integer", nullable=true)
     */
    private $startdayofyear;

    /**
     * @var int|null
     *
     * @ORM\Column(name="endDayOfYear", type="integer", nullable=true)
     */
    private $enddayofyear;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verbatimEventDate", type="string", length=255, nullable=true)
     */
    private $verbatimeventdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="habitat", type="text", length=65535, nullable=true, options={"comment"="Habitat, substrait, etc"})
     */
    private $habitat;

    /**
     * @var string|null
     *
     * @ORM\Column(name="substrate", type="string", length=500, nullable=true)
     */
    private $substrate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fieldNotes", type="text", length=65535, nullable=true)
     */
    private $fieldnotes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fieldnumber", type="string", length=45, nullable=true)
     */
    private $fieldnumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="eventID", type="string", length=45, nullable=true)
     */
    private $eventid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="occurrenceRemarks", type="text", length=65535, nullable=true, options={"comment"="General Notes"})
     */
    private $occurrenceremarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="informationWithheld", type="string", length=250, nullable=true)
     */
    private $informationwithheld;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dataGeneralizations", type="string", length=250, nullable=true)
     */
    private $datageneralizations;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associatedOccurrences", type="text", length=65535, nullable=true)
     */
    private $associatedoccurrences;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associatedTaxa", type="text", length=65535, nullable=true, options={"comment"="Associated Species"})
     */
    private $associatedtaxa;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dynamicProperties", type="text", length=65535, nullable=true)
     */
    private $dynamicproperties;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verbatimAttributes", type="text", length=65535, nullable=true)
     */
    private $verbatimattributes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="behavior", type="string", length=500, nullable=true)
     */
    private $behavior;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reproductiveCondition", type="string", length=255, nullable=true, options={"comment"="Phenology: flowers, fruit, sterile"})
     */
    private $reproductivecondition;

    /**
     * @var int|null
     *
     * @ORM\Column(name="cultivationStatus", type="integer", nullable=true, options={"comment"="0 = wild, 1 = cultivated"})
     */
    private $cultivationstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="establishmentMeans", type="string", length=150, nullable=true)
     */
    private $establishmentmeans;

    /**
     * @var string|null
     *
     * @ORM\Column(name="lifeStage", type="string", length=45, nullable=true)
     */
    private $lifestage;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sex", type="string", length=45, nullable=true)
     */
    private $sex;

    /**
     * @var string|null
     *
     * @ORM\Column(name="individualCount", type="string", length=45, nullable=true)
     */
    private $individualcount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="samplingProtocol", type="string", length=100, nullable=true)
     */
    private $samplingprotocol;

    /**
     * @var string|null
     *
     * @ORM\Column(name="samplingEffort", type="string", length=200, nullable=true)
     */
    private $samplingeffort;

    /**
     * @var string|null
     *
     * @ORM\Column(name="preparations", type="string", length=100, nullable=true)
     */
    private $preparations;

    /**
     * @var string|null
     *
     * @ORM\Column(name="locationID", type="string", length=100, nullable=true)
     */
    private $locationid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="country", type="string", length=64, nullable=true)
     */
    private $country;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stateProvince", type="string", length=255, nullable=true)
     */
    private $stateprovince;

    /**
     * @var string|null
     *
     * @ORM\Column(name="county", type="string", length=255, nullable=true)
     */
    private $county;

    /**
     * @var string|null
     *
     * @ORM\Column(name="municipality", type="string", length=255, nullable=true)
     */
    private $municipality;

    /**
     * @var string|null
     *
     * @ORM\Column(name="waterBody", type="string", length=255, nullable=true)
     */
    private $waterbody;

    /**
     * @var string|null
     *
     * @ORM\Column(name="locality", type="text", length=65535, nullable=true)
     */
    private $locality;

    /**
     * @var int|null
     *
     * @ORM\Column(name="localitySecurity", type="integer", nullable=true, options={"comment"="0 = no security; 1 = hidden locality"})
     */
    private $localitysecurity = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="localitySecurityReason", type="string", length=100, nullable=true)
     */
    private $localitysecurityreason;

    /**
     * @var float|null
     *
     * @ORM\Column(name="decimalLatitude", type="float", precision=10, scale=0, nullable=true)
     */
    private $decimallatitude;

    /**
     * @var float|null
     *
     * @ORM\Column(name="decimalLongitude", type="float", precision=10, scale=0, nullable=true)
     */
    private $decimallongitude;

    /**
     * @var string|null
     *
     * @ORM\Column(name="geodeticDatum", type="string", length=255, nullable=true)
     */
    private $geodeticdatum;

    /**
     * @var int|null
     *
     * @ORM\Column(name="coordinateUncertaintyInMeters", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $coordinateuncertaintyinmeters;

    /**
     * @var string|null
     *
     * @ORM\Column(name="footprintWKT", type="text", length=65535, nullable=true)
     */
    private $footprintwkt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="coordinatePrecision", type="decimal", precision=9, scale=7, nullable=true)
     */
    private $coordinateprecision;

    /**
     * @var string|null
     *
     * @ORM\Column(name="locationRemarks", type="text", length=65535, nullable=true)
     */
    private $locationremarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verbatimCoordinates", type="string", length=255, nullable=true)
     */
    private $verbatimcoordinates;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verbatimCoordinateSystem", type="string", length=255, nullable=true)
     */
    private $verbatimcoordinatesystem;

    /**
     * @var string|null
     *
     * @ORM\Column(name="georeferencedBy", type="string", length=255, nullable=true)
     */
    private $georeferencedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="georeferenceProtocol", type="string", length=255, nullable=true)
     */
    private $georeferenceprotocol;

    /**
     * @var string|null
     *
     * @ORM\Column(name="georeferenceSources", type="string", length=255, nullable=true)
     */
    private $georeferencesources;

    /**
     * @var string|null
     *
     * @ORM\Column(name="georeferenceVerificationStatus", type="string", length=32, nullable=true)
     */
    private $georeferenceverificationstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="georeferenceRemarks", type="string", length=255, nullable=true)
     */
    private $georeferenceremarks;

    /**
     * @var int|null
     *
     * @ORM\Column(name="minimumElevationInMeters", type="integer", nullable=true)
     */
    private $minimumelevationinmeters;

    /**
     * @var int|null
     *
     * @ORM\Column(name="maximumElevationInMeters", type="integer", nullable=true)
     */
    private $maximumelevationinmeters;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verbatimElevation", type="string", length=255, nullable=true)
     */
    private $verbatimelevation;

    /**
     * @var int|null
     *
     * @ORM\Column(name="minimumDepthInMeters", type="integer", nullable=true)
     */
    private $minimumdepthinmeters;

    /**
     * @var int|null
     *
     * @ORM\Column(name="maximumDepthInMeters", type="integer", nullable=true)
     */
    private $maximumdepthinmeters;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verbatimDepth", type="string", length=50, nullable=true)
     */
    private $verbatimdepth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="previousIdentifications", type="text", length=65535, nullable=true)
     */
    private $previousidentifications;

    /**
     * @var string|null
     *
     * @ORM\Column(name="disposition", type="string", length=250, nullable=true)
     */
    private $disposition;

    /**
     * @var string|null
     *
     * @ORM\Column(name="storageLocation", type="string", length=100, nullable=true)
     */
    private $storagelocation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="genericcolumn1", type="string", length=100, nullable=true)
     */
    private $genericcolumn1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="genericcolumn2", type="string", length=100, nullable=true)
     */
    private $genericcolumn2;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modified", type="datetime", nullable=true, options={"comment"="DateLastModified"})
     */
    private $modified;

    /**
     * @var string|null
     *
     * @ORM\Column(name="language", type="string", length=20, nullable=true)
     */
    private $language;

    /**
     * @var string|null
     *
     * @ORM\Column(name="processingstatus", type="string", length=45, nullable=true)
     */
    private $processingstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="recordEnteredBy", type="string", length=250, nullable=true)
     */
    private $recordenteredby;

    /**
     * @var int|null
     *
     * @ORM\Column(name="duplicateQuantity", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $duplicatequantity;

    /**
     * @var string|null
     *
     * @ORM\Column(name="labelProject", type="string", length=50, nullable=true)
     */
    private $labelproject;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dynamicFields", type="text", length=65535, nullable=true)
     */
    private $dynamicfields;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="dateEntered", type="datetime", nullable=true)
     */
    private $dateentered;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateLastModified", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $datelastmodified = 'CURRENT_TIMESTAMP';

    /**
     * @var \Omcollections
     *
     * @ORM\ManyToOne(targetEntity="Omcollections")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="collid", referencedColumnName="CollID")
     * })
     */
    private $collid;

    /**
     * @var integer
     *
     */
    private $recordedbyid;

    /**
     * @var \Taxa
     *
     * @ORM\ManyToOne(targetEntity="Taxa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tidinterpreted", referencedColumnName="TID")
     * })
     */
    private $tidinterpreted;

    /**
     * @var integer
     *
     */
    private $observeruid;


    /**
     * Get occid.
     *
     * @return int
     */
    public function getOccid()
    {
        return $this->occid;
    }

    /**
     * Set dbpk.
     *
     * @param string|null $dbpk
     *
     * @return Omoccurrences
     */
    public function setDbpk($dbpk = null)
    {
        $this->dbpk = $dbpk;

        return $this;
    }

    /**
     * Get dbpk.
     *
     * @return string|null
     */
    public function getDbpk()
    {
        return $this->dbpk;
    }

    /**
     * Set basisofrecord.
     *
     * @param string|null $basisofrecord
     *
     * @return Omoccurrences
     */
    public function setBasisofrecord($basisofrecord = null)
    {
        $this->basisofrecord = $basisofrecord;

        return $this;
    }

    /**
     * Get basisofrecord.
     *
     * @return string|null
     */
    public function getBasisofrecord()
    {
        return $this->basisofrecord;
    }

    /**
     * Set occurrenceid.
     *
     * @param string|null $occurrenceid
     *
     * @return Omoccurrences
     */
    public function setOccurrenceid($occurrenceid = null)
    {
        $this->occurrenceid = $occurrenceid;

        return $this;
    }

    /**
     * Get occurrenceid.
     *
     * @return string|null
     */
    public function getOccurrenceid()
    {
        return $this->occurrenceid;
    }

    /**
     * Set catalognumber.
     *
     * @param string|null $catalognumber
     *
     * @return Omoccurrences
     */
    public function setCatalognumber($catalognumber = null)
    {
        $this->catalognumber = $catalognumber;

        return $this;
    }

    /**
     * Get catalognumber.
     *
     * @return string|null
     */
    public function getCatalognumber()
    {
        return $this->catalognumber;
    }

    /**
     * Set othercatalognumbers.
     *
     * @param string|null $othercatalognumbers
     *
     * @return Omoccurrences
     */
    public function setOthercatalognumbers($othercatalognumbers = null)
    {
        $this->othercatalognumbers = $othercatalognumbers;

        return $this;
    }

    /**
     * Get othercatalognumbers.
     *
     * @return string|null
     */
    public function getOthercatalognumbers()
    {
        return $this->othercatalognumbers;
    }

    /**
     * Set ownerinstitutioncode.
     *
     * @param string|null $ownerinstitutioncode
     *
     * @return Omoccurrences
     */
    public function setOwnerinstitutioncode($ownerinstitutioncode = null)
    {
        $this->ownerinstitutioncode = $ownerinstitutioncode;

        return $this;
    }

    /**
     * Get ownerinstitutioncode.
     *
     * @return string|null
     */
    public function getOwnerinstitutioncode()
    {
        return $this->ownerinstitutioncode;
    }

    /**
     * Set institutionid.
     *
     * @param string|null $institutionid
     *
     * @return Omoccurrences
     */
    public function setInstitutionid($institutionid = null)
    {
        $this->institutionid = $institutionid;

        return $this;
    }

    /**
     * Get institutionid.
     *
     * @return string|null
     */
    public function getInstitutionid()
    {
        return $this->institutionid;
    }

    /**
     * Set collectionid.
     *
     * @param string|null $collectionid
     *
     * @return Omoccurrences
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
     * Set datasetid.
     *
     * @param string|null $datasetid
     *
     * @return Omoccurrences
     */
    public function setDatasetid($datasetid = null)
    {
        $this->datasetid = $datasetid;

        return $this;
    }

    /**
     * Get datasetid.
     *
     * @return string|null
     */
    public function getDatasetid()
    {
        return $this->datasetid;
    }

    /**
     * Set institutioncode.
     *
     * @param string|null $institutioncode
     *
     * @return Omoccurrences
     */
    public function setInstitutioncode($institutioncode = null)
    {
        $this->institutioncode = $institutioncode;

        return $this;
    }

    /**
     * Get institutioncode.
     *
     * @return string|null
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
     * @return Omoccurrences
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
     * Set family.
     *
     * @param string|null $family
     *
     * @return Omoccurrences
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
     * Set scientificname.
     *
     * @param string|null $scientificname
     *
     * @return Omoccurrences
     */
    public function setScientificname($scientificname = null)
    {
        $this->scientificname = $scientificname;

        return $this;
    }

    /**
     * Get scientificname.
     *
     * @return string|null
     */
    public function getScientificname()
    {
        return $this->scientificname;
    }

    /**
     * Set sciname.
     *
     * @param string|null $sciname
     *
     * @return Omoccurrences
     */
    public function setSciname($sciname = null)
    {
        $this->sciname = $sciname;

        return $this;
    }

    /**
     * Get sciname.
     *
     * @return string|null
     */
    public function getSciname()
    {
        return $this->sciname;
    }

    /**
     * Set genus.
     *
     * @param string|null $genus
     *
     * @return Omoccurrences
     */
    public function setGenus($genus = null)
    {
        $this->genus = $genus;

        return $this;
    }

    /**
     * Get genus.
     *
     * @return string|null
     */
    public function getGenus()
    {
        return $this->genus;
    }

    /**
     * Set specificepithet.
     *
     * @param string|null $specificepithet
     *
     * @return Omoccurrences
     */
    public function setSpecificepithet($specificepithet = null)
    {
        $this->specificepithet = $specificepithet;

        return $this;
    }

    /**
     * Get specificepithet.
     *
     * @return string|null
     */
    public function getSpecificepithet()
    {
        return $this->specificepithet;
    }

    /**
     * Set taxonrank.
     *
     * @param string|null $taxonrank
     *
     * @return Omoccurrences
     */
    public function setTaxonrank($taxonrank = null)
    {
        $this->taxonrank = $taxonrank;

        return $this;
    }

    /**
     * Get taxonrank.
     *
     * @return string|null
     */
    public function getTaxonrank()
    {
        return $this->taxonrank;
    }

    /**
     * Set infraspecificepithet.
     *
     * @param string|null $infraspecificepithet
     *
     * @return Omoccurrences
     */
    public function setInfraspecificepithet($infraspecificepithet = null)
    {
        $this->infraspecificepithet = $infraspecificepithet;

        return $this;
    }

    /**
     * Get infraspecificepithet.
     *
     * @return string|null
     */
    public function getInfraspecificepithet()
    {
        return $this->infraspecificepithet;
    }

    /**
     * Set scientificnameauthorship.
     *
     * @param string|null $scientificnameauthorship
     *
     * @return Omoccurrences
     */
    public function setScientificnameauthorship($scientificnameauthorship = null)
    {
        $this->scientificnameauthorship = $scientificnameauthorship;

        return $this;
    }

    /**
     * Get scientificnameauthorship.
     *
     * @return string|null
     */
    public function getScientificnameauthorship()
    {
        return $this->scientificnameauthorship;
    }

    /**
     * Set taxonremarks.
     *
     * @param string|null $taxonremarks
     *
     * @return Omoccurrences
     */
    public function setTaxonremarks($taxonremarks = null)
    {
        $this->taxonremarks = $taxonremarks;

        return $this;
    }

    /**
     * Get taxonremarks.
     *
     * @return string|null
     */
    public function getTaxonremarks()
    {
        return $this->taxonremarks;
    }

    /**
     * Set identifiedby.
     *
     * @param string|null $identifiedby
     *
     * @return Omoccurrences
     */
    public function setIdentifiedby($identifiedby = null)
    {
        $this->identifiedby = $identifiedby;

        return $this;
    }

    /**
     * Get identifiedby.
     *
     * @return string|null
     */
    public function getIdentifiedby()
    {
        return $this->identifiedby;
    }

    /**
     * Set dateidentified.
     *
     * @param string|null $dateidentified
     *
     * @return Omoccurrences
     */
    public function setDateidentified($dateidentified = null)
    {
        $this->dateidentified = $dateidentified;

        return $this;
    }

    /**
     * Get dateidentified.
     *
     * @return string|null
     */
    public function getDateidentified()
    {
        return $this->dateidentified;
    }

    /**
     * Set identificationreferences.
     *
     * @param string|null $identificationreferences
     *
     * @return Omoccurrences
     */
    public function setIdentificationreferences($identificationreferences = null)
    {
        $this->identificationreferences = $identificationreferences;

        return $this;
    }

    /**
     * Get identificationreferences.
     *
     * @return string|null
     */
    public function getIdentificationreferences()
    {
        return $this->identificationreferences;
    }

    /**
     * Set identificationremarks.
     *
     * @param string|null $identificationremarks
     *
     * @return Omoccurrences
     */
    public function setIdentificationremarks($identificationremarks = null)
    {
        $this->identificationremarks = $identificationremarks;

        return $this;
    }

    /**
     * Get identificationremarks.
     *
     * @return string|null
     */
    public function getIdentificationremarks()
    {
        return $this->identificationremarks;
    }

    /**
     * Set identificationqualifier.
     *
     * @param string|null $identificationqualifier
     *
     * @return Omoccurrences
     */
    public function setIdentificationqualifier($identificationqualifier = null)
    {
        $this->identificationqualifier = $identificationqualifier;

        return $this;
    }

    /**
     * Get identificationqualifier.
     *
     * @return string|null
     */
    public function getIdentificationqualifier()
    {
        return $this->identificationqualifier;
    }

    /**
     * Set typestatus.
     *
     * @param string|null $typestatus
     *
     * @return Omoccurrences
     */
    public function setTypestatus($typestatus = null)
    {
        $this->typestatus = $typestatus;

        return $this;
    }

    /**
     * Get typestatus.
     *
     * @return string|null
     */
    public function getTypestatus()
    {
        return $this->typestatus;
    }

    /**
     * Set recordedby.
     *
     * @param string|null $recordedby
     *
     * @return Omoccurrences
     */
    public function setRecordedby($recordedby = null)
    {
        $this->recordedby = $recordedby;

        return $this;
    }

    /**
     * Get recordedby.
     *
     * @return string|null
     */
    public function getRecordedby()
    {
        return $this->recordedby;
    }

    /**
     * Set recordnumber.
     *
     * @param string|null $recordnumber
     *
     * @return Omoccurrences
     */
    public function setRecordnumber($recordnumber = null)
    {
        $this->recordnumber = $recordnumber;

        return $this;
    }

    /**
     * Get recordnumber.
     *
     * @return string|null
     */
    public function getRecordnumber()
    {
        return $this->recordnumber;
    }

    /**
     * Set associatedcollectors.
     *
     * @param string|null $associatedcollectors
     *
     * @return Omoccurrences
     */
    public function setAssociatedcollectors($associatedcollectors = null)
    {
        $this->associatedcollectors = $associatedcollectors;

        return $this;
    }

    /**
     * Get associatedcollectors.
     *
     * @return string|null
     */
    public function getAssociatedcollectors()
    {
        return $this->associatedcollectors;
    }

    /**
     * Set eventdate.
     *
     * @param \DateTime|null $eventdate
     *
     * @return Omoccurrences
     */
    public function setEventdate($eventdate = null)
    {
        $this->eventdate = $eventdate;

        return $this;
    }

    /**
     * Get eventdate.
     *
     * @return \DateTime|null
     */
    public function getEventdate()
    {
        return $this->eventdate;
    }

    /**
     * Set latestdatecollected.
     *
     * @param \DateTime|null $latestdatecollected
     *
     * @return Omoccurrences
     */
    public function setLatestdatecollected($latestdatecollected = null)
    {
        $this->latestdatecollected = $latestdatecollected;

        return $this;
    }

    /**
     * Get latestdatecollected.
     *
     * @return \DateTime|null
     */
    public function getLatestdatecollected()
    {
        return $this->latestdatecollected;
    }

    /**
     * Set year.
     *
     * @param int|null $year
     *
     * @return Omoccurrences
     */
    public function setYear($year = null)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year.
     *
     * @return int|null
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set month.
     *
     * @param int|null $month
     *
     * @return Omoccurrences
     */
    public function setMonth($month = null)
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Get month.
     *
     * @return int|null
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set day.
     *
     * @param int|null $day
     *
     * @return Omoccurrences
     */
    public function setDay($day = null)
    {
        $this->day = $day;

        return $this;
    }

    /**
     * Get day.
     *
     * @return int|null
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Set startdayofyear.
     *
     * @param int|null $startdayofyear
     *
     * @return Omoccurrences
     */
    public function setStartdayofyear($startdayofyear = null)
    {
        $this->startdayofyear = $startdayofyear;

        return $this;
    }

    /**
     * Get startdayofyear.
     *
     * @return int|null
     */
    public function getStartdayofyear()
    {
        return $this->startdayofyear;
    }

    /**
     * Set enddayofyear.
     *
     * @param int|null $enddayofyear
     *
     * @return Omoccurrences
     */
    public function setEnddayofyear($enddayofyear = null)
    {
        $this->enddayofyear = $enddayofyear;

        return $this;
    }

    /**
     * Get enddayofyear.
     *
     * @return int|null
     */
    public function getEnddayofyear()
    {
        return $this->enddayofyear;
    }

    /**
     * Set verbatimeventdate.
     *
     * @param string|null $verbatimeventdate
     *
     * @return Omoccurrences
     */
    public function setVerbatimeventdate($verbatimeventdate = null)
    {
        $this->verbatimeventdate = $verbatimeventdate;

        return $this;
    }

    /**
     * Get verbatimeventdate.
     *
     * @return string|null
     */
    public function getVerbatimeventdate()
    {
        return $this->verbatimeventdate;
    }

    /**
     * Set habitat.
     *
     * @param string|null $habitat
     *
     * @return Omoccurrences
     */
    public function setHabitat($habitat = null)
    {
        $this->habitat = $habitat;

        return $this;
    }

    /**
     * Get habitat.
     *
     * @return string|null
     */
    public function getHabitat()
    {
        return $this->habitat;
    }

    /**
     * Set substrate.
     *
     * @param string|null $substrate
     *
     * @return Omoccurrences
     */
    public function setSubstrate($substrate = null)
    {
        $this->substrate = $substrate;

        return $this;
    }

    /**
     * Get substrate.
     *
     * @return string|null
     */
    public function getSubstrate()
    {
        return $this->substrate;
    }

    /**
     * Set fieldnotes.
     *
     * @param string|null $fieldnotes
     *
     * @return Omoccurrences
     */
    public function setFieldnotes($fieldnotes = null)
    {
        $this->fieldnotes = $fieldnotes;

        return $this;
    }

    /**
     * Get fieldnotes.
     *
     * @return string|null
     */
    public function getFieldnotes()
    {
        return $this->fieldnotes;
    }

    /**
     * Set fieldnumber.
     *
     * @param string|null $fieldnumber
     *
     * @return Omoccurrences
     */
    public function setFieldnumber($fieldnumber = null)
    {
        $this->fieldnumber = $fieldnumber;

        return $this;
    }

    /**
     * Get fieldnumber.
     *
     * @return string|null
     */
    public function getFieldnumber()
    {
        return $this->fieldnumber;
    }

    /**
     * Set eventid.
     *
     * @param string|null $eventid
     *
     * @return Omoccurrences
     */
    public function setEventid($eventid = null)
    {
        $this->eventid = $eventid;

        return $this;
    }

    /**
     * Get eventid.
     *
     * @return string|null
     */
    public function getEventid()
    {
        return $this->eventid;
    }

    /**
     * Set occurrenceremarks.
     *
     * @param string|null $occurrenceremarks
     *
     * @return Omoccurrences
     */
    public function setOccurrenceremarks($occurrenceremarks = null)
    {
        $this->occurrenceremarks = $occurrenceremarks;

        return $this;
    }

    /**
     * Get occurrenceremarks.
     *
     * @return string|null
     */
    public function getOccurrenceremarks()
    {
        return $this->occurrenceremarks;
    }

    /**
     * Set informationwithheld.
     *
     * @param string|null $informationwithheld
     *
     * @return Omoccurrences
     */
    public function setInformationwithheld($informationwithheld = null)
    {
        $this->informationwithheld = $informationwithheld;

        return $this;
    }

    /**
     * Get informationwithheld.
     *
     * @return string|null
     */
    public function getInformationwithheld()
    {
        return $this->informationwithheld;
    }

    /**
     * Set datageneralizations.
     *
     * @param string|null $datageneralizations
     *
     * @return Omoccurrences
     */
    public function setDatageneralizations($datageneralizations = null)
    {
        $this->datageneralizations = $datageneralizations;

        return $this;
    }

    /**
     * Get datageneralizations.
     *
     * @return string|null
     */
    public function getDatageneralizations()
    {
        return $this->datageneralizations;
    }

    /**
     * Set associatedoccurrences.
     *
     * @param string|null $associatedoccurrences
     *
     * @return Omoccurrences
     */
    public function setAssociatedoccurrences($associatedoccurrences = null)
    {
        $this->associatedoccurrences = $associatedoccurrences;

        return $this;
    }

    /**
     * Get associatedoccurrences.
     *
     * @return string|null
     */
    public function getAssociatedoccurrences()
    {
        return $this->associatedoccurrences;
    }

    /**
     * Set associatedtaxa.
     *
     * @param string|null $associatedtaxa
     *
     * @return Omoccurrences
     */
    public function setAssociatedtaxa($associatedtaxa = null)
    {
        $this->associatedtaxa = $associatedtaxa;

        return $this;
    }

    /**
     * Get associatedtaxa.
     *
     * @return string|null
     */
    public function getAssociatedtaxa()
    {
        return $this->associatedtaxa;
    }

    /**
     * Set dynamicproperties.
     *
     * @param string|null $dynamicproperties
     *
     * @return Omoccurrences
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
     * Set verbatimattributes.
     *
     * @param string|null $verbatimattributes
     *
     * @return Omoccurrences
     */
    public function setVerbatimattributes($verbatimattributes = null)
    {
        $this->verbatimattributes = $verbatimattributes;

        return $this;
    }

    /**
     * Get verbatimattributes.
     *
     * @return string|null
     */
    public function getVerbatimattributes()
    {
        return $this->verbatimattributes;
    }

    /**
     * Set behavior.
     *
     * @param string|null $behavior
     *
     * @return Omoccurrences
     */
    public function setBehavior($behavior = null)
    {
        $this->behavior = $behavior;

        return $this;
    }

    /**
     * Get behavior.
     *
     * @return string|null
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * Set reproductivecondition.
     *
     * @param string|null $reproductivecondition
     *
     * @return Omoccurrences
     */
    public function setReproductivecondition($reproductivecondition = null)
    {
        $this->reproductivecondition = $reproductivecondition;

        return $this;
    }

    /**
     * Get reproductivecondition.
     *
     * @return string|null
     */
    public function getReproductivecondition()
    {
        return $this->reproductivecondition;
    }

    /**
     * Set cultivationstatus.
     *
     * @param int|null $cultivationstatus
     *
     * @return Omoccurrences
     */
    public function setCultivationstatus($cultivationstatus = null)
    {
        $this->cultivationstatus = $cultivationstatus;

        return $this;
    }

    /**
     * Get cultivationstatus.
     *
     * @return int|null
     */
    public function getCultivationstatus()
    {
        return $this->cultivationstatus;
    }

    /**
     * Set establishmentmeans.
     *
     * @param string|null $establishmentmeans
     *
     * @return Omoccurrences
     */
    public function setEstablishmentmeans($establishmentmeans = null)
    {
        $this->establishmentmeans = $establishmentmeans;

        return $this;
    }

    /**
     * Get establishmentmeans.
     *
     * @return string|null
     */
    public function getEstablishmentmeans()
    {
        return $this->establishmentmeans;
    }

    /**
     * Set lifestage.
     *
     * @param string|null $lifestage
     *
     * @return Omoccurrences
     */
    public function setLifestage($lifestage = null)
    {
        $this->lifestage = $lifestage;

        return $this;
    }

    /**
     * Get lifestage.
     *
     * @return string|null
     */
    public function getLifestage()
    {
        return $this->lifestage;
    }

    /**
     * Set sex.
     *
     * @param string|null $sex
     *
     * @return Omoccurrences
     */
    public function setSex($sex = null)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Get sex.
     *
     * @return string|null
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set individualcount.
     *
     * @param string|null $individualcount
     *
     * @return Omoccurrences
     */
    public function setIndividualcount($individualcount = null)
    {
        $this->individualcount = $individualcount;

        return $this;
    }

    /**
     * Get individualcount.
     *
     * @return string|null
     */
    public function getIndividualcount()
    {
        return $this->individualcount;
    }

    /**
     * Set samplingprotocol.
     *
     * @param string|null $samplingprotocol
     *
     * @return Omoccurrences
     */
    public function setSamplingprotocol($samplingprotocol = null)
    {
        $this->samplingprotocol = $samplingprotocol;

        return $this;
    }

    /**
     * Get samplingprotocol.
     *
     * @return string|null
     */
    public function getSamplingprotocol()
    {
        return $this->samplingprotocol;
    }

    /**
     * Set samplingeffort.
     *
     * @param string|null $samplingeffort
     *
     * @return Omoccurrences
     */
    public function setSamplingeffort($samplingeffort = null)
    {
        $this->samplingeffort = $samplingeffort;

        return $this;
    }

    /**
     * Get samplingeffort.
     *
     * @return string|null
     */
    public function getSamplingeffort()
    {
        return $this->samplingeffort;
    }

    /**
     * Set preparations.
     *
     * @param string|null $preparations
     *
     * @return Omoccurrences
     */
    public function setPreparations($preparations = null)
    {
        $this->preparations = $preparations;

        return $this;
    }

    /**
     * Get preparations.
     *
     * @return string|null
     */
    public function getPreparations()
    {
        return $this->preparations;
    }

    /**
     * Set locationid.
     *
     * @param string|null $locationid
     *
     * @return Omoccurrences
     */
    public function setLocationid($locationid = null)
    {
        $this->locationid = $locationid;

        return $this;
    }

    /**
     * Get locationid.
     *
     * @return string|null
     */
    public function getLocationid()
    {
        return $this->locationid;
    }

    /**
     * Set country.
     *
     * @param string|null $country
     *
     * @return Omoccurrences
     */
    public function setCountry($country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set stateprovince.
     *
     * @param string|null $stateprovince
     *
     * @return Omoccurrences
     */
    public function setStateprovince($stateprovince = null)
    {
        $this->stateprovince = $stateprovince;

        return $this;
    }

    /**
     * Get stateprovince.
     *
     * @return string|null
     */
    public function getStateprovince()
    {
        return $this->stateprovince;
    }

    /**
     * Set county.
     *
     * @param string|null $county
     *
     * @return Omoccurrences
     */
    public function setCounty($county = null)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get county.
     *
     * @return string|null
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set municipality.
     *
     * @param string|null $municipality
     *
     * @return Omoccurrences
     */
    public function setMunicipality($municipality = null)
    {
        $this->municipality = $municipality;

        return $this;
    }

    /**
     * Get municipality.
     *
     * @return string|null
     */
    public function getMunicipality()
    {
        return $this->municipality;
    }

    /**
     * Set waterbody.
     *
     * @param string|null $waterbody
     *
     * @return Omoccurrences
     */
    public function setWaterbody($waterbody = null)
    {
        $this->waterbody = $waterbody;

        return $this;
    }

    /**
     * Get waterbody.
     *
     * @return string|null
     */
    public function getWaterbody()
    {
        return $this->waterbody;
    }

    /**
     * Set locality.
     *
     * @param string|null $locality
     *
     * @return Omoccurrences
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
     * Set localitysecurity.
     *
     * @param int|null $localitysecurity
     *
     * @return Omoccurrences
     */
    public function setLocalitysecurity($localitysecurity = null)
    {
        $this->localitysecurity = $localitysecurity;

        return $this;
    }

    /**
     * Get localitysecurity.
     *
     * @return int|null
     */
    public function getLocalitysecurity()
    {
        return $this->localitysecurity;
    }

    /**
     * Set localitysecurityreason.
     *
     * @param string|null $localitysecurityreason
     *
     * @return Omoccurrences
     */
    public function setLocalitysecurityreason($localitysecurityreason = null)
    {
        $this->localitysecurityreason = $localitysecurityreason;

        return $this;
    }

    /**
     * Get localitysecurityreason.
     *
     * @return string|null
     */
    public function getLocalitysecurityreason()
    {
        return $this->localitysecurityreason;
    }

    /**
     * Set decimallatitude.
     *
     * @param float|null $decimallatitude
     *
     * @return Omoccurrences
     */
    public function setDecimallatitude($decimallatitude = null)
    {
        $this->decimallatitude = $decimallatitude;

        return $this;
    }

    /**
     * Get decimallatitude.
     *
     * @return float|null
     */
    public function getDecimallatitude()
    {
        return $this->decimallatitude;
    }

    /**
     * Set decimallongitude.
     *
     * @param float|null $decimallongitude
     *
     * @return Omoccurrences
     */
    public function setDecimallongitude($decimallongitude = null)
    {
        $this->decimallongitude = $decimallongitude;

        return $this;
    }

    /**
     * Get decimallongitude.
     *
     * @return float|null
     */
    public function getDecimallongitude()
    {
        return $this->decimallongitude;
    }

    /**
     * Set geodeticdatum.
     *
     * @param string|null $geodeticdatum
     *
     * @return Omoccurrences
     */
    public function setGeodeticdatum($geodeticdatum = null)
    {
        $this->geodeticdatum = $geodeticdatum;

        return $this;
    }

    /**
     * Get geodeticdatum.
     *
     * @return string|null
     */
    public function getGeodeticdatum()
    {
        return $this->geodeticdatum;
    }

    /**
     * Set coordinateuncertaintyinmeters.
     *
     * @param int|null $coordinateuncertaintyinmeters
     *
     * @return Omoccurrences
     */
    public function setCoordinateuncertaintyinmeters($coordinateuncertaintyinmeters = null)
    {
        $this->coordinateuncertaintyinmeters = $coordinateuncertaintyinmeters;

        return $this;
    }

    /**
     * Get coordinateuncertaintyinmeters.
     *
     * @return int|null
     */
    public function getCoordinateuncertaintyinmeters()
    {
        return $this->coordinateuncertaintyinmeters;
    }

    /**
     * Set footprintwkt.
     *
     * @param string|null $footprintwkt
     *
     * @return Omoccurrences
     */
    public function setFootprintwkt($footprintwkt = null)
    {
        $this->footprintwkt = $footprintwkt;

        return $this;
    }

    /**
     * Get footprintwkt.
     *
     * @return string|null
     */
    public function getFootprintwkt()
    {
        return $this->footprintwkt;
    }

    /**
     * Set coordinateprecision.
     *
     * @param string|null $coordinateprecision
     *
     * @return Omoccurrences
     */
    public function setCoordinateprecision($coordinateprecision = null)
    {
        $this->coordinateprecision = $coordinateprecision;

        return $this;
    }

    /**
     * Get coordinateprecision.
     *
     * @return string|null
     */
    public function getCoordinateprecision()
    {
        return $this->coordinateprecision;
    }

    /**
     * Set locationremarks.
     *
     * @param string|null $locationremarks
     *
     * @return Omoccurrences
     */
    public function setLocationremarks($locationremarks = null)
    {
        $this->locationremarks = $locationremarks;

        return $this;
    }

    /**
     * Get locationremarks.
     *
     * @return string|null
     */
    public function getLocationremarks()
    {
        return $this->locationremarks;
    }

    /**
     * Set verbatimcoordinates.
     *
     * @param string|null $verbatimcoordinates
     *
     * @return Omoccurrences
     */
    public function setVerbatimcoordinates($verbatimcoordinates = null)
    {
        $this->verbatimcoordinates = $verbatimcoordinates;

        return $this;
    }

    /**
     * Get verbatimcoordinates.
     *
     * @return string|null
     */
    public function getVerbatimcoordinates()
    {
        return $this->verbatimcoordinates;
    }

    /**
     * Set verbatimcoordinatesystem.
     *
     * @param string|null $verbatimcoordinatesystem
     *
     * @return Omoccurrences
     */
    public function setVerbatimcoordinatesystem($verbatimcoordinatesystem = null)
    {
        $this->verbatimcoordinatesystem = $verbatimcoordinatesystem;

        return $this;
    }

    /**
     * Get verbatimcoordinatesystem.
     *
     * @return string|null
     */
    public function getVerbatimcoordinatesystem()
    {
        return $this->verbatimcoordinatesystem;
    }

    /**
     * Set georeferencedby.
     *
     * @param string|null $georeferencedby
     *
     * @return Omoccurrences
     */
    public function setGeoreferencedby($georeferencedby = null)
    {
        $this->georeferencedby = $georeferencedby;

        return $this;
    }

    /**
     * Get georeferencedby.
     *
     * @return string|null
     */
    public function getGeoreferencedby()
    {
        return $this->georeferencedby;
    }

    /**
     * Set georeferenceprotocol.
     *
     * @param string|null $georeferenceprotocol
     *
     * @return Omoccurrences
     */
    public function setGeoreferenceprotocol($georeferenceprotocol = null)
    {
        $this->georeferenceprotocol = $georeferenceprotocol;

        return $this;
    }

    /**
     * Get georeferenceprotocol.
     *
     * @return string|null
     */
    public function getGeoreferenceprotocol()
    {
        return $this->georeferenceprotocol;
    }

    /**
     * Set georeferencesources.
     *
     * @param string|null $georeferencesources
     *
     * @return Omoccurrences
     */
    public function setGeoreferencesources($georeferencesources = null)
    {
        $this->georeferencesources = $georeferencesources;

        return $this;
    }

    /**
     * Get georeferencesources.
     *
     * @return string|null
     */
    public function getGeoreferencesources()
    {
        return $this->georeferencesources;
    }

    /**
     * Set georeferenceverificationstatus.
     *
     * @param string|null $georeferenceverificationstatus
     *
     * @return Omoccurrences
     */
    public function setGeoreferenceverificationstatus($georeferenceverificationstatus = null)
    {
        $this->georeferenceverificationstatus = $georeferenceverificationstatus;

        return $this;
    }

    /**
     * Get georeferenceverificationstatus.
     *
     * @return string|null
     */
    public function getGeoreferenceverificationstatus()
    {
        return $this->georeferenceverificationstatus;
    }

    /**
     * Set georeferenceremarks.
     *
     * @param string|null $georeferenceremarks
     *
     * @return Omoccurrences
     */
    public function setGeoreferenceremarks($georeferenceremarks = null)
    {
        $this->georeferenceremarks = $georeferenceremarks;

        return $this;
    }

    /**
     * Get georeferenceremarks.
     *
     * @return string|null
     */
    public function getGeoreferenceremarks()
    {
        return $this->georeferenceremarks;
    }

    /**
     * Set minimumelevationinmeters.
     *
     * @param int|null $minimumelevationinmeters
     *
     * @return Omoccurrences
     */
    public function setMinimumelevationinmeters($minimumelevationinmeters = null)
    {
        $this->minimumelevationinmeters = $minimumelevationinmeters;

        return $this;
    }

    /**
     * Get minimumelevationinmeters.
     *
     * @return int|null
     */
    public function getMinimumelevationinmeters()
    {
        return $this->minimumelevationinmeters;
    }

    /**
     * Set maximumelevationinmeters.
     *
     * @param int|null $maximumelevationinmeters
     *
     * @return Omoccurrences
     */
    public function setMaximumelevationinmeters($maximumelevationinmeters = null)
    {
        $this->maximumelevationinmeters = $maximumelevationinmeters;

        return $this;
    }

    /**
     * Get maximumelevationinmeters.
     *
     * @return int|null
     */
    public function getMaximumelevationinmeters()
    {
        return $this->maximumelevationinmeters;
    }

    /**
     * Set verbatimelevation.
     *
     * @param string|null $verbatimelevation
     *
     * @return Omoccurrences
     */
    public function setVerbatimelevation($verbatimelevation = null)
    {
        $this->verbatimelevation = $verbatimelevation;

        return $this;
    }

    /**
     * Get verbatimelevation.
     *
     * @return string|null
     */
    public function getVerbatimelevation()
    {
        return $this->verbatimelevation;
    }

    /**
     * Set minimumdepthinmeters.
     *
     * @param int|null $minimumdepthinmeters
     *
     * @return Omoccurrences
     */
    public function setMinimumdepthinmeters($minimumdepthinmeters = null)
    {
        $this->minimumdepthinmeters = $minimumdepthinmeters;

        return $this;
    }

    /**
     * Get minimumdepthinmeters.
     *
     * @return int|null
     */
    public function getMinimumdepthinmeters()
    {
        return $this->minimumdepthinmeters;
    }

    /**
     * Set maximumdepthinmeters.
     *
     * @param int|null $maximumdepthinmeters
     *
     * @return Omoccurrences
     */
    public function setMaximumdepthinmeters($maximumdepthinmeters = null)
    {
        $this->maximumdepthinmeters = $maximumdepthinmeters;

        return $this;
    }

    /**
     * Get maximumdepthinmeters.
     *
     * @return int|null
     */
    public function getMaximumdepthinmeters()
    {
        return $this->maximumdepthinmeters;
    }

    /**
     * Set verbatimdepth.
     *
     * @param string|null $verbatimdepth
     *
     * @return Omoccurrences
     */
    public function setVerbatimdepth($verbatimdepth = null)
    {
        $this->verbatimdepth = $verbatimdepth;

        return $this;
    }

    /**
     * Get verbatimdepth.
     *
     * @return string|null
     */
    public function getVerbatimdepth()
    {
        return $this->verbatimdepth;
    }

    /**
     * Set previousidentifications.
     *
     * @param string|null $previousidentifications
     *
     * @return Omoccurrences
     */
    public function setPreviousidentifications($previousidentifications = null)
    {
        $this->previousidentifications = $previousidentifications;

        return $this;
    }

    /**
     * Get previousidentifications.
     *
     * @return string|null
     */
    public function getPreviousidentifications()
    {
        return $this->previousidentifications;
    }

    /**
     * Set disposition.
     *
     * @param string|null $disposition
     *
     * @return Omoccurrences
     */
    public function setDisposition($disposition = null)
    {
        $this->disposition = $disposition;

        return $this;
    }

    /**
     * Get disposition.
     *
     * @return string|null
     */
    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * Set storagelocation.
     *
     * @param string|null $storagelocation
     *
     * @return Omoccurrences
     */
    public function setStoragelocation($storagelocation = null)
    {
        $this->storagelocation = $storagelocation;

        return $this;
    }

    /**
     * Get storagelocation.
     *
     * @return string|null
     */
    public function getStoragelocation()
    {
        return $this->storagelocation;
    }

    /**
     * Set genericcolumn1.
     *
     * @param string|null $genericcolumn1
     *
     * @return Omoccurrences
     */
    public function setGenericcolumn1($genericcolumn1 = null)
    {
        $this->genericcolumn1 = $genericcolumn1;

        return $this;
    }

    /**
     * Get genericcolumn1.
     *
     * @return string|null
     */
    public function getGenericcolumn1()
    {
        return $this->genericcolumn1;
    }

    /**
     * Set genericcolumn2.
     *
     * @param string|null $genericcolumn2
     *
     * @return Omoccurrences
     */
    public function setGenericcolumn2($genericcolumn2 = null)
    {
        $this->genericcolumn2 = $genericcolumn2;

        return $this;
    }

    /**
     * Get genericcolumn2.
     *
     * @return string|null
     */
    public function getGenericcolumn2()
    {
        return $this->genericcolumn2;
    }

    /**
     * Set modified.
     *
     * @param \DateTime|null $modified
     *
     * @return Omoccurrences
     */
    public function setModified($modified = null)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified.
     *
     * @return \DateTime|null
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set language.
     *
     * @param string|null $language
     *
     * @return Omoccurrences
     */
    public function setLanguage($language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set processingstatus.
     *
     * @param string|null $processingstatus
     *
     * @return Omoccurrences
     */
    public function setProcessingstatus($processingstatus = null)
    {
        $this->processingstatus = $processingstatus;

        return $this;
    }

    /**
     * Get processingstatus.
     *
     * @return string|null
     */
    public function getProcessingstatus()
    {
        return $this->processingstatus;
    }

    /**
     * Set recordenteredby.
     *
     * @param string|null $recordenteredby
     *
     * @return Omoccurrences
     */
    public function setRecordenteredby($recordenteredby = null)
    {
        $this->recordenteredby = $recordenteredby;

        return $this;
    }

    /**
     * Get recordenteredby.
     *
     * @return string|null
     */
    public function getRecordenteredby()
    {
        return $this->recordenteredby;
    }

    /**
     * Set duplicatequantity.
     *
     * @param int|null $duplicatequantity
     *
     * @return Omoccurrences
     */
    public function setDuplicatequantity($duplicatequantity = null)
    {
        $this->duplicatequantity = $duplicatequantity;

        return $this;
    }

    /**
     * Get duplicatequantity.
     *
     * @return int|null
     */
    public function getDuplicatequantity()
    {
        return $this->duplicatequantity;
    }

    /**
     * Set labelproject.
     *
     * @param string|null $labelproject
     *
     * @return Omoccurrences
     */
    public function setLabelproject($labelproject = null)
    {
        $this->labelproject = $labelproject;

        return $this;
    }

    /**
     * Get labelproject.
     *
     * @return string|null
     */
    public function getLabelproject()
    {
        return $this->labelproject;
    }

    /**
     * Set dynamicfields.
     *
     * @param string|null $dynamicfields
     *
     * @return Omoccurrences
     */
    public function setDynamicfields($dynamicfields = null)
    {
        $this->dynamicfields = $dynamicfields;

        return $this;
    }

    /**
     * Get dynamicfields.
     *
     * @return string|null
     */
    public function getDynamicfields()
    {
        return $this->dynamicfields;
    }

    /**
     * Set dateentered.
     *
     * @param \DateTime|null $dateentered
     *
     * @return Omoccurrences
     */
    public function setDateentered($dateentered = null)
    {
        $this->dateentered = $dateentered;

        return $this;
    }

    /**
     * Get dateentered.
     *
     * @return \DateTime|null
     */
    public function getDateentered()
    {
        return $this->dateentered;
    }

    /**
     * Set datelastmodified.
     *
     * @param \DateTime $datelastmodified
     *
     * @return Omoccurrences
     */
    public function setDatelastmodified($datelastmodified)
    {
        $this->datelastmodified = $datelastmodified;

        return $this;
    }

    /**
     * Get datelastmodified.
     *
     * @return \DateTime
     */
    public function getDatelastmodified()
    {
        return $this->datelastmodified;
    }

    /**
     * Set collid.
     *
     * @param \Omcollections|null $collid
     *
     * @return Omoccurrences
     */
    public function setCollid(\Omcollections $collid = null)
    {
        $this->collid = $collid;

        return $this;
    }

    /**
     * Get collid.
     *
     * @return \Omcollections|null
     */
    public function getCollid()
    {
        return $this->collid;
    }

    /**
     * Set recordedbyid.
     *
     * @param integer|null $recordedbyid
     *
     * @return Omoccurrences
     */
    public function setRecordedbyid($recordedbyid = null)
    {
        $this->recordedbyid = $recordedbyid;

        return $this;
    }

    /**
     * Get recordedbyid.
     *
     * @return integer|null
     */
    public function getRecordedbyid()
    {
        return $this->recordedbyid;
    }

    /**
     * Set tidinterpreted.
     *
     * @param \Taxa|null $tidinterpreted
     *
     * @return Omoccurrences
     */
    public function setTidinterpreted(\Taxa $tidinterpreted = null)
    {
        $this->tidinterpreted = $tidinterpreted;

        return $this;
    }

    /**
     * Get tidinterpreted.
     *
     * @return \Taxa|null
     */
    public function getTidinterpreted()
    {
        return $this->tidinterpreted;
    }

    /**
     * Set observeruid.
     *
     * @param integer|null $observeruid
     *
     * @return Omoccurrences
     */
    public function setObserveruid($observeruid = null)
    {
        $this->observeruid = $observeruid;

        return $this;
    }

    /**
     * Get observeruid.
     *
     * @return integer|null
     */
    public function getObserveruid()
    {
        return $this->observeruid;
    }
}
