<?php


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Taxalinks
 *
 * @ORM\Table(name="taxalinks")
 * @ORM\Entity
 */
class Taxalinks
{
  /**
   * @var integer
   *
   * @ORM\Column(name="tlid")
   * @ORM\Id
   */
  private $tlid;

  /**
   * @var integer
   *
   * @ORM\Column(name="tid")
   */
  private $tid;

  /**
   * @var string
   *
   * @ORM\Column(name="url")
   */
  private $url;
  
  /**
   * @var string
   *
   * @ORM\Column(name="title")
   */
  private $title;

  /**
   * @var integer
   *
   * @ORM\Column(name="sortsequence")
   */
  private $sortsequence;

  public function getTlid() {
    return $this->tlid;
  }

  public function getTid() {
    return $this->tid;
  }
  public function getUrl() {
    return $this->url;
  }
  public function getTitle() {
    return $this->title;
  }
}

?>