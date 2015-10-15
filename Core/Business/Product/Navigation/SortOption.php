<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class SortOption
{
    private $fieldName;
    private $sortOrder;
    private $label;

    public function __construct($fieldName = null, $sortOrder = null, $label = null)
    {
        $this->setFieldName($fieldName);
        $this->setSortOrder($sortOrder);
        $this->setLabel($label);
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }
}
