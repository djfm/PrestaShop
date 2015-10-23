<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class Filter
{
    private $label;
    private $condition;
    private $enabled = false;
    private $magnitude;

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setCondition($condition)
    {
        $this->condition = $condition;
        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function serializeCondition()
    {
        return json_encode($this->getCondition());
    }

    public function unserializeCondition($str)
    {
        return $this->setCondition(json_decode($str, true));
    }

    public function getIdentifier()
    {
        return $this->serializeCondition();
    }

    public function setMagnitude($magnitude)
    {
        $this->magnitude = $magnitude;
        return $this;
    }

    public function getMagnitude()
    {
        return $this->magnitude;
    }
}
