<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryHelper\CategoriesQueryHelper;

class CategoryFilter extends AbstractProductFilter
{
    /**
     * @var int
     */
    private $categoryId;

    public function __construct($categoryId = null, $enabled = false)
    {
        $this->categoryId = $categoryId;
        $this->setEnabled($enabled);
    }

    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function serializeCriterium()
    {
        return $this->getCategoryId();
    }

    public function unserializeCriterium($categoryId)
    {
        return $this->setCategoryId((int)$categoryId);
    }

    public function getQueryHelper()
    {
        return new CategoriesQueryHelper;
    }
}
