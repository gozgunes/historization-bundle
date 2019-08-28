<?php

namespace HistorizationBundle\Annotation;


use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class JoinConfig
{
    /** @var string */
    public $joinTableColumnName;

    /**
     * Config constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        if (isset($data['historizeColumnName'])) {
            $this->joinTableColumnName = $data['historizeColumnName'];
        }
    }

    public function getJoinTableColumnName()
    {
        return $this->joinTableColumnName;
    }
}
