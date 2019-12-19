<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Fmchklsttaxalink
 * @ORM\Entity
 * @ORM\Table(name="fmchklsttaxalink", indexes={@ORM\Index(name="FK_chklsttaxalink_cid", columns={"CLID"}), @ORM\Index(name="IDX_7E381424C4FE2EBB", columns={"TID"})})
 */
class Fmchklsttaxalink
{
    /**
     * @var string
     *
     * @ORM\Column(name="morphospecies", type="string", length=45, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $morphospecies = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="familyoverride", type="string", length=50, nullable=true)
     */
    private $familyoverride;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Habitat", type="string", length=250, nullable=true)
     */
    private $habitat;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Abundance", type="string", length=50, nullable=true)
     */
    private $abundance;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Notes", type="string", length=2000, nullable=true)
     */
    private $notes;

    /**
     * @var int|null
     *
     * @ORM\Column(name="explicitExclude", type="smallint", nullable=true)
     */
    private $explicitexclude;

    /**
     * @var string|null
     *
     * @ORM\Column(name="source", type="string", length=250, nullable=true)
     */
    private $source;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Nativity", type="string", length=50, nullable=true, options={"comment"="native, introducted"})
     */
    private $nativity;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Endemic", type="string", length=45, nullable=true)
     */
    private $endemic;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invasive", type="string", length=45, nullable=true)
     */
    private $invasive;

    /**
     * @var string|null
     *
     * @ORM\Column(name="internalnotes", type="string", length=250, nullable=true)
     */
    private $internalnotes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dynamicProperties", type="text", length=65535, nullable=true)
     */
    private $dynamicproperties;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="InitialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="clid", type="integer")
     */
    private $clid;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="tid", type="integer")
     */
    private $tid;

    /**
     * Set morphospecies.
     *
     * @param string $morphospecies
     *
     * @return Fmchklsttaxalink
     */
    public function setMorphospecies($morphospecies)
    {
        $this->morphospecies = $morphospecies;

        return $this;
    }

    /**
     * Get morphospecies.
     *
     * @return string
     */
    public function getMorphospecies()
    {
        return $this->morphospecies;
    }

    /**
     * Set familyoverride.
     *
     * @param string|null $familyoverride
     *
     * @return Fmchklsttaxalink
     */
    public function setFamilyoverride($familyoverride = null)
    {
        $this->familyoverride = $familyoverride;

        return $this;
    }

    /**
     * Get familyoverride.
     *
     * @return string|null
     */
    public function getFamilyoverride()
    {
        return $this->familyoverride;
    }

    /**
     * Set habitat.
     *
     * @param string|null $habitat
     *
     * @return Fmchklsttaxalink
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
     * Set abundance.
     *
     * @param string|null $abundance
     *
     * @return Fmchklsttaxalink
     */
    public function setAbundance($abundance = null)
    {
        $this->abundance = $abundance;

        return $this;
    }

    /**
     * Get abundance.
     *
     * @return string|null
     */
    public function getAbundance()
    {
        return $this->abundance;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Fmchklsttaxalink
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
     * Set explicitexclude.
     *
     * @param int|null $explicitexclude
     *
     * @return Fmchklsttaxalink
     */
    public function setExplicitexclude($explicitexclude = null)
    {
        $this->explicitexclude = $explicitexclude;

        return $this;
    }

    /**
     * Get explicitexclude.
     *
     * @return int|null
     */
    public function getExplicitexclude()
    {
        return $this->explicitexclude;
    }

    /**
     * Set source.
     *
     * @param string|null $source
     *
     * @return Fmchklsttaxalink
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
     * Set nativity.
     *
     * @param string|null $nativity
     *
     * @return Fmchklsttaxalink
     */
    public function setNativity($nativity = null)
    {
        $this->nativity = $nativity;

        return $this;
    }

    /**
     * Get nativity.
     *
     * @return string|null
     */
    public function getNativity()
    {
        return $this->nativity;
    }

    /**
     * Set endemic.
     *
     * @param string|null $endemic
     *
     * @return Fmchklsttaxalink
     */
    public function setEndemic($endemic = null)
    {
        $this->endemic = $endemic;

        return $this;
    }

    /**
     * Get endemic.
     *
     * @return string|null
     */
    public function getEndemic()
    {
        return $this->endemic;
    }

    /**
     * Set invasive.
     *
     * @param string|null $invasive
     *
     * @return Fmchklsttaxalink
     */
    public function setInvasive($invasive = null)
    {
        $this->invasive = $invasive;

        return $this;
    }

    /**
     * Get invasive.
     *
     * @return string|null
     */
    public function getInvasive()
    {
        return $this->invasive;
    }

    /**
     * Set internalnotes.
     *
     * @param string|null $internalnotes
     *
     * @return Fmchklsttaxalink
     */
    public function setInternalnotes($internalnotes = null)
    {
        $this->internalnotes = $internalnotes;

        return $this;
    }

    /**
     * Get internalnotes.
     *
     * @return string|null
     */
    public function getInternalnotes()
    {
        return $this->internalnotes;
    }

    /**
     * Set dynamicproperties.
     *
     * @param string|null $dynamicproperties
     *
     * @return Fmchklsttaxalink
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
     * Set initialtimestamp.
     *
     * @param \DateTime $initialtimestamp
     *
     * @return Fmchklsttaxalink
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
     * Set clid.
     *
     * @param integer $clid
     *
     * @return Fmchklsttaxalink
     */
    public function setClid($clid)
    {
        $this->clid = $clid;

        return $this;
    }

    /**
     * Get clid.
     *
     * @return integer
     */
    public function getClid()
    {
        return $this->clid;
    }

    /**
     * Set tid.
     *
     * @param integer $tid
     *
     * @return Fmchklsttaxalink
     */
    public function setTid($tid)
    {
        $this->tid = $tid;

        return $this;
    }

    /**
     * Get tid.
     *
     * @return integer
     */
    public function getTid()
    {
        return $this->tid;
    }
}
