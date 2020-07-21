<?php


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fmprojects
 *
 * @ORM\Table(name="fmprojects")
 * @ORM\Entity
 */
class Fmprojects
{
  /**
   * @var integer
   *
   * @ORM\Column(name="pid")
   * @ORM\Id
   */
  private $pid;

  /**
   * @var string
   *
   * @ORM\Column(name="projname")
   */
  private $projname;

  /**
   * @var string|null
   *
   * @ORM\Column(name="displayname")
   */
  private $displayname;

  /**
   * @var string|null
   *
   * @ORM\Column(name="managers")
   */
  private $managers;

  /**
   * @var string|null
   *
   * @ORM\Column(name="briefdescription")
   */
  private $briefdescription;

  /**
   * @var string|null
   *
   * @ORM\Column(name="fulldescription")
   */
  private $fulldescription;

  /**
   * @var string|null
   *
   * @ORM\Column(name="notes")
   */
  private $notes;
  
  /**
   * @var integer
   *
   * @ORM\Column(name="occurrencesearch")
   */
  private $occurrencesearch;
  
  /**
   * @var integer
   *
   * @ORM\Column(name="ispublic")
   */
  private $ispublic;

  /**
   * @var string|null
   *
   * @ORM\Column(name="dynamicproperties")
   */
  private $dynamicproperties;

  /**
   * @var integer
   *
   * @ORM\Column(name="parentpid")
   */
  private $parentpid;

  /**
   * @var integer
   *
   * @ORM\Column(name="sortsequence")
   */
  private $sortsequence;

  public function getPid() {
    return $this->pid;
  }
  public function getProjname() {
    return $this->projname;
  }
  public function getManagers() {
    return $this->managers;
  }
  public function getBriefDescription() {
    return $this->briefdescription;
  }
  public function getFullDescription() {
    return $this->fulldescription;
  }
  public function getIsPublic() {
    return $this->ispublic;
  }
}

?>