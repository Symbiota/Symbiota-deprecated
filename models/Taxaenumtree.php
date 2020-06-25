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
     * @var \Taxa
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Taxa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tid", referencedColumnName="TID")
     * })
     */
    private $tid;

    /**
     * @var \Taxa
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Taxa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parenttid", referencedColumnName="TID")
     * })
     */
    private $parenttid;

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
