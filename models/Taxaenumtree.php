<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Taxaenumtree
 *
 * @ORM\Table(name="taxaenumtree")
 * @ORM\Entity
 */
class Taxaenumtree
{


    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Taxauthority")
     * @ORM\JoinColumn(name="taxauthid", referencedColumnName="taxauthid")
     * 
     */
    private $taxauthid;

}
