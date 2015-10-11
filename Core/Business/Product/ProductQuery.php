<?php

namespace PrestaShop\PrestaShop\Core\Business\Product;

class ProductQuery implements ProductQueryInterface
{
    const OPERATOR_OR = "or";
    const OPERATOR_AND = "and";

    private $operator;
    private $filters = [];

    public function __construct($operator)
    {
        if (!in_array($operator, [
            static::OPERATOR_OR,
            static::OPERATOR_AND
        ])) {
            throw new Exception(sprintf(
                'Invalid operator type "%s".',
                $operator
            ));
        }
        $this->operator = $operator;
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function isEnabled()
    {
        return true;
    }
}
