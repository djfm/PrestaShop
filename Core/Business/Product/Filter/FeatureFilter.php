<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

class FeatureFilter extends AbstractProductFilter
{
    /**
     * @var int
     */
    private $featureId;

    /**
     * @var int
     */
    private $featureValueId;

    public function setFeatureId($featureId)
    {
        $this->featureId = $featureId;
        return $this;
    }

    public function getFeatureId()
    {
        return $this->featureId;
    }

    public function setFeatureValueId($featureValueId)
    {
        $this->featureValueId = $featureValueId;
        return $this;
    }

    public function getFeatureValueId()
    {
        return $this->featureValueId;
    }
}
