<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister;

class QueryURLSerializer
{
    private $db;
    private $productLister;

    public function __construct(
        AutoPrefixingDatabase $db,
        ProductLister $productLister
    ) {
        $this->db = $db;
        $this->productLister = $productLister;
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

        return (new URLFragmentSerializer)->serialize($fragments);
    }

    public function getQueryFromURLFragment(
        QueryContext $context,
        Query $defaultQuery,
        $fragmentString
    ) {
        $query = new Query;
        $fragment = (new URLFragmentSerializer)->unserialize($fragmentString);

        $labelToFacet = [];
        $defaultFacets = $this->productLister->getAvailableFacets($context, $defaultQuery);

        foreach ($defaultFacets as $facet) {
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
                $filter = $this->productLister->buildFilterFromLabel(
                    $context,
                    $facet,
                    $label
                );
                $filter->setEnabled();
                $facet->addFilter($filter);
            }
            $query->addFacet($facet);
        }

        return $query;
    }
}
