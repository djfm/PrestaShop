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

    public function __construct($attributeGroupId = null, $attributeId = null, $enabled = false)
    {
        $this->setAttributeGroupId($attributeGroupId);
        $this->setAttributeId($attributeId);
        $this->setEnabled($enabled);
    }

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

    public function getDataDomain()
    {
        return 'attributes';
    }

    public function serializeCriterium()
    {
        return json_encode([
            $this->getAttributeGroupId(),
            $this->getAttributeId()
        ]);
    }

    public function unserializeCriterium($criterium)
    {
        list($attributeGroupId, $attributeId) = json_decode($criterium, true);
        $this->setAttributeId($attributeId);
        $this->setAttributeGroupId($attributeGroupId);
        return $this;
    }
}
