<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class QueryPresenter
{
    private function presentFilter(Filter $filter, $path)
    {
        return [
            'label' => $filter->getLabel(),
            'magnitude' => $filter->getMagnitude(),
            'inputName' => $path.'['.$filter->getLabel().']',
            'inputValue' => json_encode($filter->getCondition()),
            'enabled' => $filter->isEnabled(),

        ];
    }

    private function presentFacet(Facet $facet, $path)
    {
        return [
            'label' => $facet->getLabel(),
            'identifier' => $facet->getIdentifier(),
            'condition' => json_encode($facet->getCondition()),
            'hidden' => empty($facet->getFilters()) || $facet->isHidden(),
            'filters' => array_values(
                array_map(function (Filter $filter) use ($path) {
                    return $this->presentFilter($filter, $path);
                }, $facet->getFilters())
            ),
        ];
    }

    public function present(Query $query)
    {
        return array_map(function (Facet $facet) {
            return $this->presentFacet($facet, 'query['.$facet->getIdentifier().']');
        }, $query->getFacets());
    }
}
