<?php


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fmchklstprojlink
 *
 * @ORM\Table(name="fmchklstprojlink")
 * @ORM\Entity
 */
class Fmchklstprojlink
{
  /**
   * @var integer
   *
   * @ORM\Column(name="pid")
   * @ORM\Id
   */
  private $pid;
  
  /**
   * @var integer
   *
   * @ORM\Column(name="clid")
   * @ORM\Id
   */
  private $clid;

  /**
   * @var string
   *
   * @ORM\Column(name="clnameoverride")
   */
  private $clnameoverride;
  
  /**
   * @var integer
   *
   * @ORM\Column(name="mapchecklist")
   * @ORM\Id
   */
  private $mapchecklist;

  /**
   * @var string|null
   *
   * @ORM\Column(name="notes")
   */
  private $notes;

/*
  public function getPid() {
    return $this->pid;
  }

  public function getTid() {
    return $this->tid;
  }
  public function getUrl() {
    return $this->url;
  }
  public function getTitle() {
    return $this->title;
  }*/
}

?>