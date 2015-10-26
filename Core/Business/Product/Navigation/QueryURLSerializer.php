<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;
class QueryURLSerializer
{
    private $db;

    public function __construct(
        AutoPrefixingDatabase $db
    ) {
        $this->db = $db;
    }

    public function getURLFragmentFromQuery(Query $query)
    {
        $fragments = [];
        foreach ($query->getFacets() as $facet) {
            $label = $facet->getLabel();
            while (array_key_exists($label, $fragments)) {
                $label .= '-';
            }
            foreach ($facet->getFilters() as $filter) {
                if ($filter->isEnabled()) {
                    $fragments[$label][] = $filter->getLabel();
                }
            }
        }

        return (new URLFragmentSerializer())->serialize($fragments);
    }

    public function getQueryFromURLFragment(
        QueryContext $context,
        DatabaseQuery $defaultQuery,
        $fragmentString
    ) {
        $query = new DatabaseQuery($this->db, $context, new Query());
        $fragment = (new URLFragmentSerializer())->unserialize($fragmentString);

        $labelToFacet = [];

        foreach ($defaultQuery->getFacets() as $facet) {
            $label = $facet->getLabel();
            while (array_key_exists($label, $labelToFacet)) {
                $label .= '-';
            }
            $labelToFacet[$label] = $facet;
        }

        foreach ($fragment as $facetLabel => $filterLabels) {
            $facet = $labelToFacet[$facetLabel];
            $facet->clearFilters();
            foreach ($filterLabels as $label) {
                $filter = $facet->buildFilterFromLabel($label);
                $filter->setEnabled();
                $facet->addFilter($filter);
            }
            $query->addFacet($facet);
        }

        return $query;
    }
}
