<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Kmcharheading
 *
 * @ORM\Table(name="kmcharheading", uniqueConstraints={@ORM\UniqueConstraint(name="unique_kmcharheading", columns={"headingname", "langid"})}, indexes={@ORM\Index(name="FK_kmcharheading_lang_idx", columns={"langid"}), @ORM\Index(name="HeadingName", columns={"headingname"})})
 * @ORM\Entity
 */
class Kmcharheading
{
    /**
     * @var int
     *
     * @ORM\Column(name="hid", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $hid;

    /**
     * @var string
     *
     * @ORM\Column(name="headingname", type="string", length=255, nullable=false)
     */
    private $headingname;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=45, nullable=false, options={"default"="English"})
     */
    private $language = 'English';

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", length=0, nullable=true)
     */
    private $notes;

    /**
     * @var int|null
     *
     * @ORM\Column(name="sortsequence", type="integer", nullable=true)
     */
    private $sortsequence;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="initialtimestamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $langid;


    /**
     * Set hid.
     *
     * @param int $hid
     *
     * @return Kmcharheading
     */
    public function setHid($hid)
    {
        $this->hid = $hid;

        return $this;
    }

    /**
     * Get hid.
     *
     * @return int
     */
    public function getHid()
    {
        return $this->hid;
    }

    /**
     * Set headingname.
     *
     * @param string $headingname
     *
     * @return Kmcharheading
     */
    public function setHeadingname($headingname)
    {
        $this->headingname = $headingname;

        return $this;
    }

    /**
     * Get headingname.
     *
     * @return string
     */
    public function getHeadingname()
    {
        return $this->headingname;
    }

    /**
     * Set language.
     *
     * @param string $language
     *
     * @return Kmcharheading
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Kmcharheading
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
     * Set sortsequence.
     *
     * @param int|null $sortsequence
     *
     * @return Kmcharheading
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
     * @return Kmcharheading
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
     * Get langid.
     *
     * @return integer
     */
    public function getLangid()
    {
        return $this->langid;
    }
}
