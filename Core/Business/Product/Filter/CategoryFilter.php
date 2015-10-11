<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

class CategoryFilter extends AbstractProductFilter
{
    /**
     * @var int
     */
    private $categoryId;

    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function toJSON()
    {
        return json_encode([
            'categoryId' => $this->getCategoryId(),
            'className'  => 'CategoryFilter'
        ]);
    }
}
