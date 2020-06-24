<?php


use Doctrine\Common\Collections\ArrayCollection;
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

  
  /**
   * @var string|null
   *
   * @ORM\Column(name="source")
   */
  private $source;
  

}

?>