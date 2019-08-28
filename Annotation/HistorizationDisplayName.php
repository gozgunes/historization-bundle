<?php

namespace HistorizationBundle\Annotation;


use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class HistorizationDisplayName
{
    /** @var string */
    public $name;

    /**
     * Config constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
    }

    public function getHistorizationDisplayName()
    {
        return $this->name;
    }
}
