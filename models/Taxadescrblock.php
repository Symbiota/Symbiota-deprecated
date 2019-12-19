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

  public function getTdbid() {
    return $this->tdbid;
  }

  public function getTid() {
    return $this->tid;
  }
}

?>