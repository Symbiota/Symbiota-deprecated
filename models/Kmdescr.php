<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Kmdescr
 *
 * @ORM\Table(name="kmdescr")
 * @ORM\Entity
 */
class Kmdescr {

  /**
   * @var integer
   * @ORM\Id
   * @ORM\Column(name="tid", type="integer")
   */
  private $tid;

  /**
   * @var integer
   * @ORM\Id
   * @ORM\Column(name="cid", type="integer")
   */
  private $cid;

  /**
   * @var string
   * @ORM\Id
   * @ORM\Column(name="cs", type="integer")
   */
  private $cs;

  /**
   * @var integer
   * @ORM\Id
   * @ORM\Column(name="seq", type="integer")
   */
  private $seq;

  /**
   * @return int
   */
  public function getTid() {
    return $this->tid;
  }

  /**
   * @return int
   */
  public function getCid() {
    return $this->cid;
  }

  /**
   * @return string
   */
  public function getCs() {
    return $this->cs;
  }

  /**
   * @return int
   */
  public function getSeq() {
    return $this->seq;
  }
}
?>