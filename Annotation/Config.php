<?php

namespace HistorizationBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;


/**
 * @Annotation
 * @Target("CLASS")
 */
class Config
{
    /** @var string */
    public $historizable;

    /**
     * Config constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        if (isset($data['historizable'])) {
            $this->historizable = $data['historizable'];
        }
    }

    public function getHistorizable()
    {
        return $this->historizable;
    }
}
