<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Taxauthority
 *
 * @ORM\Table(name="taxauthority")
 * @ORM\Entity
 */
class Taxauthority
{
  /**
   * @var integer
   *
   * @ORM\Column(name="taxauthid")
   * @ORM\Id
   */
  private $taxauthid;

  /**
   * @var integer
   *
   * @ORM\Column(name="isprimary")
   */
  private $isprimary;

  /**
   * @var integer
   *
   * @ORM\Column(name="isactive")
   */
  private $isactive;

  /**
   * @var string
   *
   * @ORM\Column(name="name")
   */
  private $name;
  
}

?>