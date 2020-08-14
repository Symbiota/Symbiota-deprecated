<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Fmdynamicchecklists
 * @ORM\Entity
 * @ORM\Table(name="fmdynamicchecklists", uniqueConstraints={@ORM\UniqueConstraint(name="PRIMARY", columns={"dynclid"})} )
 */
class Fmdynamicchecklists
{
    /**
     * @var int
     *
     *
     * @ORM\Id
     * @ORM\Column(name="dynclid", type="integer", nullable=false)
     */
    private $dynclid;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="details", type="string", length=250, nullable=true)
     */
    private $details;

    /**
     * @var string|null
     *
     * @ORM\Column(name="uid", type="string", length=45, nullable=true)
     */
    private $uid;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45, nullable=false)
     */
    private $type;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=250, nullable=true)
     */
    private $notes;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration", type="datetime", nullable=true)
     */
    private $expiration;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="initialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';
    
  /**
     * Get clid.
     *
     * @return int
     */
    public function getDynclid()
    {
        return $this->dynclid;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Fmdynamicchecklists
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set details.
     *
     * @param string|null $details
     *
     * @return Fmdynamicchecklists
     */
    public function setDetails($details = null)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return string|null
     */
    public function getDetails()
    {
        return $this->details;
    }


}
