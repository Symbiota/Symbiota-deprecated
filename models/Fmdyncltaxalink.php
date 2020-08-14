<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Fmdyncltaxalink
 * @ORM\Entity
 * @ORM\Table(name="fmdyncltaxalink", uniqueConstraints={@ORM\UniqueConstraint(name="PRIMARY", columns={"dynclid","tid"})},indexes={@ORM\Index(name="FK_dyncltaxalink_taxa", columns={"tid"})}  )
 */
class Fmdyncltaxalink
{
    /**
     * @var int
     *
     *
     * @ORM\Id
     * @ORM\Column(name="dynclid", type="integer", nullable=false)
     */
    private $dynclid;
    
    /**
     * @var int
     *
     *
     * @ORM\Id
     * @ORM\Column(name="tid", type="integer", nullable=false)
     */
    private $tid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="InitialTimeStamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $initialtimestamp = 'CURRENT_TIMESTAMP';

 
}
