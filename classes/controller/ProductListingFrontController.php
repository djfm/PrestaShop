<?php

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryResult;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryPresenter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\SortOption;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryURLSerializer;

abstract class ProductListingFrontController extends ProductPresentingFrontController
{
    protected function getAutoPrefixingDatabase()
    {
        return Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase'
        );
    }

    private function getURLSerializer()
    {
        return Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryURLSerializer'
        );
    }

    private function getProductLister()
    {
        $productLister = new ProductLister(
            $this->getAutoPrefixingDatabase()
        );

        return $productLister;
    }

    protected function getProductQueryContext()
    {
        return (new QueryContext())
            ->setLanguageId($this->context->language->id)
            ->setShopId($this->context->shop->id)
        ;
    }

    private function setPaginationQuery(Query $query)
    {
        $pagination = new PaginationQuery;
        $pagination->setResultsPerPage(
            (int)Configuration::get('PS_PRODUCTS_PER_PAGE')
        );
        $pagination->setPage(max(1, (int)Tools::getValue('page')));
        $query->setPagination($pagination);

        return $this;
    }

    private function setSortOption(Query $query)
    {
        $sort_option = Tools::getValue('sort_option');
        if ($sort_option) {
            $option = new SortOption;
            $option->fromArray(json_decode($sort_option, true));
            $query->setSortOption($option);
        }
        return $this;
    }

    public function prepareProductForTemplate(array $product)
    {
        $presenter = $this->getProductPresenter();
        $settings  = $this->getProductPresentationSettings();

        return $presenter->presentForListing(
            $settings,
            $product,
            $this->context->language
        );
    }

    private function getQueryFromPost(array $rawQuery, array $rawFacets)
    {
        $query = new Query;

        foreach ($rawFacets as $rawFacet) {
            $facet = new Facet;
            $facet
                ->setIdentifier($rawFacet['identifier'])
                ->setLabel($rawFacet['label'])
                ->setCondition(json_decode($rawFacet['condition'], true))
            ;
            $query->addFacet($facet);
        }

        foreach ($rawQuery as $facetIdentifier => $filters) {
            $facet = $query->getFacetByIdentifier($facetIdentifier);
            foreach ($filters as $label => $rawFilter) {
                $filter = new Filter;
                $filter
                    ->setCondition(json_decode($rawFilter, true))
                    ->setLabel($label)
                    ->setEnabled()
                ;
                $facet->addFilter($filter);
            }
        }

        return $query;
    }

    private function getQueryFromURLFragment($fragment, Query $defaultQuery)
    {
        return $this
            ->getProductLister()
            ->getQueryFromURLFragment(
                $this->getProductQueryContext(),
                $defaultQuery,
                $fragment
            )
        ;
    }

    private function getQueryURL(QueryResult $result)
    {
        $query = $result->getUpdatedFilters();

        $fragment = $result->getURLFragment();

        $params = [];
        parse_str($_SERVER["QUERY_STRING"], $params);

        if ($fragment) {
            $params['q'] = $fragment;
        }

        if (($page = $result->getPaginationResult()->getPage()) !== 1) {
            $params['page'] = $page;
        }

        $queryString = urldecode(http_build_query($params));
        $url = "$_SERVER[REQUEST_SCHEME]://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]?$queryString";

        return $url;
    }

    protected function fetchProductsAndGetRelatedTemplateVariables(Query $query)
    {
        $templateVariables = [
            'sort_options' => [],
            'query'        => [],
            'products'     => [],
            'pagination'   => [],
            'query_url'    => ''
        ];

        $productLister = $this->getProductLister();
        $context       = $this->getProductQueryContext();

        if (($q = Tools::getValue('q'))) {
            $query = $this->getQueryFromURLFragment($q, $query);
        } else if (($rawQuery = Tools::getValue('query')) && ($rawFacets = Tools::getValue('facets'))) {
            $query = $this->getQueryFromPost($rawQuery, $rawFacets);
        }

        $this->setPaginationQuery($query);
        $this->setSortOption($query);

        $result = $productLister->listProducts($context, $query);

        $products = $this->assembleProducts($result->getProducts());

        $templateVariables['products']   = array_map([$this, 'prepareProductForTemplate'], $products);
        $templateVariables['query']      = (new QueryPresenter())->present($result->getUpdatedFilters());
        $templateVariables['pagination'] = $result->getPaginationResult()->buildLinks();
        $templateVariables['query_url']  = $this->getQueryURL($result);


        foreach ($result->getSortOptions() as $sortOption) {
            $templateVariables['sort_options'][] = [
                'enabled' => $sortOption == $query->getSortOption(),
                'serialized'  => json_encode($sortOption->toArray()),
                'label'   => $sortOption->getLabel()
            ];
        }

        return $templateVariables;
    }
}
