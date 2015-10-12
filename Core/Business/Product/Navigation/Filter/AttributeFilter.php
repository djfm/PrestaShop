<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

class AttributeFilter extends AbstractProductFilter
{
    /**
     * @var int
     */
    private $attributeGroupId;

    /**
     * @var int
     */
    private $attributeId;

    public function setAttributeGroupId($attributeGroupId)
    {
        $this->attributeGroupId = $attributeGroupId;
        return $this;
    }

    public function getAttributeGroupId()
    {
        return $this->attributeGroupId;
    }

    public function setAttributeId($attributeId)
    {
        $this->attributeId = $attributeId;
        return $this;
    }

    public function getAttributeId()
    {
        return $this->attributeId;
    }
}
