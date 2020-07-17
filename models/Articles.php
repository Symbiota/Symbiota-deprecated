<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Articles
 *
 * @ORM\Table(name="articles", uniqueConstraints={@ORM\UniqueConstraint(name="PRIMARY", columns={"articles_id"})})
 * @ORM\Entity
 */
class Articles
{
    /**
     * @var int
     *
     *
     * @ORM\Id
     * @ORM\Column(name="articles_id", type="integer", nullable=false)
     */
    private $articles_id;
    
    /**
     * @var \int|null
     * 
     * @ORM\Id
     * @ORM\Column(name="volume", type="integer", nullable=true)
     */
    private $volume;
    
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(name="issue", type="integer", nullable=true)
     */
    private $issue;

    /**
     * @var string|null
     *
     * @ORM\Column(name="issue_str", type="string", length=255, nullable=true)
     */
    private $issue_str;

    /**
     * @var int|null
     *
     * @ORM\Column(name="volume_year", type="integer", nullable=true)
     */
    private $volume_year;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="authors", type="string", length=255, nullable=true)
     */
    private $authors;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="pdf", type="string", length=255, nullable=true)
     */
    private $pdf;
    
    /**
     * @var int|null
     *
     * @ORM\Column(name="article_order", type="integer", nullable=true)
     */
    private $article_order;
    

    /**
     * Get articles_id.
     *
     * @return string
     */
    public function getArticlesId()
    {
        return $this->articles_id;
    }


    /**
     * Get volume.
     *
     * @return string
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Get issue.
     *
     * @return string
     */
    public function getIssue()
    {
        return $this->issue;
    }


    /**
     * Get issue_str.
     *
     * @return string|null
     */
    public function getIssueStr()
    {
        return $this->issue_str;
    }

    /**
     * Get volume_year.
     *
     * @return string|null
     */
    public function getVolumeYear()
    {
        return $this->volume_year;
    }
 
    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get authors.
     *
     * @return string|null
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Get pdf.
     *
     * @return string|null
     */
    public function getPdf()
    {
        return $this->pdf;
    }

    /**
     * Get article_order.
     *
     * @return int|null
     */
    public function getArticleOrder()
    {
        return $this->article_order;
    }
}
