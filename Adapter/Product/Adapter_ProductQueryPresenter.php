<?php

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AbstractProductFilter;

class Adapter_ProductQueryPresenter
{
    private function presentFilter(QueryContext $context, AbstractProductFilter $filter, $path)
    {
        $label = $filter->getFilterType();

        if ($filter instanceof CategoryFilter) {
            $label = Db::getInstance()->executeS(
                'SELECT name FROM ' . _DB_PREFIX_ . 'category_lang WHERE id_category = ' . (int)$filter->getCategoryId() . ' AND id_lang = ' . (int)$context->getLanguageId() . ' AND id_shop = ' . (int)$context->getShopId()
            )[0]['name'];
        }

        $inputValue = json_encode([
            'filterType' => $filter->getFilterType(),
            'criterium' => $filter->serializeCriterium(),
            'enabled'   => $filter->isEnabled()
        ]);

        return [
            'label'      => $label,
            'inputName'  => $path . '[]',
            'inputValue' => $inputValue,
            'enabled'    => $filter->isEnabled(),

        ];
    }

    private function presentFacet(QueryContext $context, Facet $facet, $path)
    {
        return [
            'name' => $facet->getName(),
            'filters' => array_map(function (AbstractProductFilter $filter) use ($context, $path) {
                return $this->presentFilter($context, $filter, $path);
            }, $facet->getFilters())
        ];
    }

    public function present(QueryContext $context, Query $query)
    {
        $counter = 0;
        return array_map(function (Facet $facet) use ($context, &$counter) {
            return $this->presentFacet($context, $facet, 'query[' . ($counter++) . ']');
        }, $query->getFacets());
    }
}
