<?php

namespace PrestaShop\PrestaShop\Core\Business\Product;

class ProductQueryContext
{
    private $shopId;
    private $languageId;
    private $customerGroupId;

    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        return $this;
    }

    public function getShopId()
    {
        return $this->shopId;
    }

    public function setLanguageId($languageId)
    {
        $this->languageId = $languageId;
        return $this;
    }

    public function getLanguageId()
    {
        return $this->languageId;
    }

    public function setCustomerGroupId($customerGroupId)
    {
        $this->customerGroupId = $customerGroupId;
        return $this;
    }

    public function getCustomerGroupId()
    {
        return $this->customerGroupId;
    }
}
