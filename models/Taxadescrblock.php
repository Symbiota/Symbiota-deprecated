<?php

#use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Taxadescrblock
 *
 * @ORM\Table(
 					name="taxadescrblock", 
 					uniqueConstraints={
 																@ORM\UniqueConstraint(name="PRIMARY", columns={"tbdid"}),
 																@ORM\UniqueConstraint(name="Index_unique", columns={"tid","displaylevel","language"})
 														}, 
 					indexes={
 										@ORM\Index(name="FK_taxadesc_lang_idx", columns={"langid"})
 									}
 	)
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
   * @ORM\Column(name="caption")
   */
  private $caption;
 
}

?>