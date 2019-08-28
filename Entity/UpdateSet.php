<?php

namespace HistorizationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity()
 * @ORM\Table(name="historization_update_sets")
 */
class UpdateSet
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var ChangeLogHistory
     * @ORM\ManyToOne(targetEntity="HistorizationBundle\Entity\ChangeLogHistory", cascade={"persist", "remove"}, inversedBy="updates", fetch="EAGER")
     */
    public $changeLogHistory;

    /**
     * @var string
     * @ORM\Column(name="column_name", type="string", length=255)
     */
    public $columnName;

    /**
     * @var string
     * @ORM\Column(name="old_record", type="string", length=255, nullable=true)
     */
    public $oldRecord;

    /**
     * @var string
     * @ORM\Column(name="new_record", type="string", length=255, nullable=true)
     */
    public $newRecord;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function __construct(string $columnName, ChangeLogHistory $changeLogHistory, $oldRecord, $newRecord)
    {
        $this->columnName = $columnName;
        $this->changeLogHistory = $changeLogHistory;
        $this->oldRecord = $oldRecord;
        $this->newRecord = $newRecord;
    }

    /**
     * @param ChangeLogHistory $changeLogHistory
     * @return UpdateSet
     */
    public function setChangeLogHistory($changeLogHistory)
    {
        $this->changeLogHistory = $changeLogHistory;
        return $this;
    }

    /**
     * @return ChangeLogHistory
     */
    public function getChangeLogHistory()
    {
        return $this->changeLogHistory;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getOldRecord()
    {
        return $this->oldRecord;
    }

    /**
     * @return string
     */
    public function getNewRecord()
    {
        return $this->newRecord;
    }

}
