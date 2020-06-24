<?php

#use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Taxadescrblock
 *
 * @ORM\Table(name="taxadescrblock")
 * @ORM\Entity
 */
class Taxadescrblock
{
  /**
   * @var integer
   *
   * @ORM\Column(name="tdbid")
   * @ORM\Id
   */
  private $tdbid;

  /**
   * @var integer
   *
   * @ORM\Column(name="tid")
   */
  private $tid;
  
  #caption, soruce, sourceurl, language
  
  /**
   * @var string|null
   *
   * @ORM\Column(name="caption")
   */
  private $caption;
  
  /**
   * @var string|null
   *
   * @ORM\Column(name="source")
   */
  private $source;
  
  /**
   * @var string|null
   *
   * @ORM\Column(name="sourceurl")
   */
  private $sourceurl;
  
  /**
   * @var string|null
   *
   * @ORM\Column(name="language")
   */
  private $language;
  
  /**
   * @var string|null
   *
   * @ORM\Column(name="displaylevel")
   */
  private $displaylevel;

  public function getTdbid() {
    return $this->tdbid;
  }

  public function getTid() {
    return $this->tid;
  }
  
  public function getDisplayLevel() {
    return $this->displaylevel;
  }
}

?>