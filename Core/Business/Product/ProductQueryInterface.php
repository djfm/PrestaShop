<?php

namespace PrestaShop\PrestaShop\Core\Business\Product;

interface ProductQueryInterface
{
    public function getFilters();
    public function getOperator();
    public function isEnabled();
}
