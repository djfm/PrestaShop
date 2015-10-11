<?php

use PrestaShop\PrestaShop\Core\Business\Product\ProductQuery;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryInterface;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Filter\AbstractProductFilter;

class Adapter_ProductFilterPresenter
{
    private function presentFilter(ProductQueryContext $context, AbstractProductFilter $filter, $inputName)
    {
        if ($filter instanceof CategoryFilter) {

            $label = Db::getInstance()->executeS(
                'SELECT name FROM ' . _DB_PREFIX_ . 'category_lang WHERE id_category = ' . (int)$filter->getCategoryId() . ' AND id_lang = ' . (int)$context->getLanguageId() . ' AND id_shop = ' . (int)$context->getShopId()
            )[0]['name'];

            return [
                'name'  => $inputName . '/CategoryFilter][]',
                'value' => $filter->toJSON(),
                'enabled' => $filter->isEnabled(),
                'label' => $label
            ];
        }
    }

    private function doPresent(ProductQueryContext $context, ProductQuery $query, $inputName)
    {
        $inputName .= "/{$query->getOperator()}";
        $facets = [];

        foreach ($query->getFilters() as $filter) {
            if ($filter instanceof ProductQuery) {
                $facets[] = $this->doPresent($context, $filter, $inputName);
            } else {
                $classParts = explode('\\', get_class($filter));
                $type = end($classParts);
                if (!array_key_exists($type, $facets)) {
                    $facets[$type] = [
                        'type'      => $type,
                        'operator'  => 'or',
                        'choices'   => []
                    ];
                }
                $facets[$type]['choices'][] = $this->presentFilter($context, $filter, $inputName);
            }
        }

        return [
            'type' => null,
            'operator' => $query->getOperator(),
            'facets' => array_values($facets)
        ];
    }

    public function present(ProductQueryContext $context, ProductQuery $query)
    {
        return $this->doPresent($context, $query, 'query[');
    }
}
