<?php
namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AttributeFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AbstractProductFilter;
use Core_Foundation_Database_DatabaseInterface;
use Core_Business_ConfigurationInterface;

class QueryPresenter
{
    private function presentFilter(AbstractProductFilter $filter, $path)
    {
        /*
        $label = $filter->getFilterType();

        if ($filter instanceof CategoryFilter) {
            $label = $this->getValue(
                'SELECT name FROM prefix_category_lang WHERE id_category = ' . (int)$filter->getCategoryId() . ' AND id_lang = ' . (int)$context->getLanguageId() . ' AND id_shop = ' . (int)$context->getShopId()
            )[0]['name'];
        } else if ($filter instanceof AttributeFilter) {
            $label = $this->getValue(
                'SELECT name FROM prefix_attribute_lang WHERE id_attribute = ' . (int)$filter->getAttributeId() . ' AND id_lang = ' . (int)$context->getLanguageId()
            );
        }*/

        $inputValue = json_encode([
            'filterType' => $filter->getFilterType(),
            'criterium' => $filter->serializeCriterium(),
            'enabled'   => $filter->isEnabled()
        ]);

        return [
            'label'      => $filter->getLabel(),
            'inputName'  => $path . '[]',
            'inputValue' => $inputValue,
            'enabled'    => $filter->isEnabled(),

        ];
    }

    private function presentFacet(Facet $facet, $path)
    {
        return [
            'name' => $facet->getName(),
            'identifier' => $facet->getIdentifier(),
            'filters' => array_values(
                array_map(function (AbstractProductFilter $filter) use ($path) {
                    return $this->presentFilter($filter, $path);
                }, $facet->getFilters())
            )
        ];
    }

    public function present(Query $query)
    {
        $counter = 0;
        return array_map(function (Facet $facet) use (&$counter) {
            return $this->presentFacet($facet, 'query[' . ($counter++) . ']');
        }, $query->getFacets());
    }
}
