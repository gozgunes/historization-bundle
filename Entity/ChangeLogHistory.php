<?php

namespace HistorizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;


/**
 * @ORM\Entity(repositoryClass="HistorizationBundle\Repository\ChangeLogHistoryRepository")
 * @ORM\Table(name="historization_change_log", indexes={@Index(name="class_name_idx", columns={"class_name"})})
 */
class ChangeLogHistory
{
    CONST ACTION_TYPE_INSERT = 1;
    CONST ACTION_TYPE_INSERT_TEXT = "INSERT";
    CONST ACTION_TYPE_UPDATE = 2;
    CONST ACTION_TYPE_UPDATE_TEXT = "UPDATE";
    CONST ACTION_TYPE_DELETE = 3;
    CONST ACTION_TYPE_DELETE_TEXT = "DELETE";

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="action_type", type="integer", length=1)
     */
    private $actionType;

    /**
     * @var string
     * @ORM\Column(name="class_id", type="string")
     */
    private $classId;

    /**
     * @var string
     * @ORM\Column(name="class_name", type="string")
     */
    private $className;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="HistorizationBundle\Entity\UpdateSet",
     *     mappedBy="changeLogHistory",
     *     cascade={"persist"},
     *     fetch="EAGER"
     * )
     */
    private $updates;

    /**
     * @var string
     * @ORM\Column(name="username", type="string", length=25)
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(name="created_at", type="integer")
     */
    private $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function __construct(string $classId, int $actionType, string $className, string $username)
    {
        $this->classId = $classId;
        $this->actionType = $actionType;
        $this->className = $className;
        $this->username = $username;
        $this->createdAt = time();
        $this->updates = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getUpdates()
    {
        return $this->updates;
    }

    public function addUpdateSet(UpdateSet $updateSet)
    {
        $updateSet->setChangeLogHistory($this);
        if (!$this->updates->contains($updateSet)) {
            $this->updates->add($updateSet);
        }
    }

    /**
     * @return int
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
