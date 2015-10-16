<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use Exception;

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
        if ($fieldName !== null && !preg_match('/^\w+(?:\.\w+)?$/', $fieldName)) {
            throw new Exception('Invalid fieldName for SortOption.');
        }
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function setSortOrder($sortOrder)
    {
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC' && $sortOrder !== null) {
            throw new Exception('Invalid sortOrder for SortOption.');
        }
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function toArray()
    {
        return [
            'fieldName' => $this->getFieldName(),
            'sortOrder' => $this->getSortOrder(),
            'label'     => $this->getLabel()
        ];
    }

    public function fromArray(array $arr)
    {
        return $this
            ->setFieldName($arr['fieldName'])
            ->setSortOrder($arr['sortOrder'])
            ->setLabel($arr['label'])
        ;
    }
}
