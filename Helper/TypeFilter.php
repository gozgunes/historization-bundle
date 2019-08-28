<?php

namespace HistorizationBundle\Helper;


class TypeFilter
{
    /**
     * TypeFilter constructor.
     */
    public function filterTypes($value)
    {
        if ($value instanceof \DateTime) {
            $value = $this->filterDateTime($value);
        }

        return $value;
    }

    private function filterDateTime(\DateTime $value)
    {
        return $value->format('Y-m-d H:i:s');
    }
}