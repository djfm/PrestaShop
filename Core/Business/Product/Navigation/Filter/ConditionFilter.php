<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

use Exception;

class ConditionFilter extends AbstractProductFilter
{
    const NEW_PRODUCT = 0;
    const USED_PRODUCT = 1;
    const REFURBISHED_PRODUCT = 2;

    private $condition;

    public function setCondition($condition)
    {
        if (!in_array($condition, [
            static::NEW_PRODUCT,
            static::USED_PRODUCT,
            static::REFURBISHED_PRODUCT
        ])) {
            throw new Exception(sprintf(
                "Invalid condition filter '%s'.",
                $condition
            ));
        }

        $this->condition = $condition;
        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }
}
