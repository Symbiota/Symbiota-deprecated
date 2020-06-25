<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Taxaenumtree
 *
 * @ORM\Table(name="taxaenumtree", uniqueConstraints={@ORM\UniqueConstraint(name="PRIMARY", columns={"tid", "taxauthid", "parenttid"})}, indexes={@ORM\Index(name="FK_tet_taxa2", columns={"parenttid"}), @ORM\Index(name="FK_tet_taxa", columns={"tid"}), @ORM\Index(name="FK_tet_taxauth", columns={"taxauthid"})})
 * @ORM\Entity
 */
class Taxaenumtree
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="initialtimestamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

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
