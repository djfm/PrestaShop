<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\FacetQueryHelper;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryAssembler;

class FeatureQueryHelper extends AbstractFacetQueryHelper
{
    public function getJoinsSQLForQuery($facetIndex, Facet $facet)
    {
        $id = (int)$facet->getCondition()['id_feature'];
        return "INNER JOIN prefix_feature_product feature_product{$facetIndex}
                    ON feature_product{$facetIndex}.id_product = product.id_product
                    AND feature_product{$facetIndex}.id_feature = $id"
        ;
    }

    public function getFilterConditionSQLForQuery($facetIndex, Filter $filter)
    {
        $id_feature_value = (int)$filter->getCondition()['id_feature_value'];
        return "feature_product{$facetIndex}.id_feature_value = $id_feature_value";
    }

    public function getUpdatedFacet(array $queryParts, Facet $initialFacet)
    {
        $queryParts['select']   = 'feature_value_lang.id_feature_value, feature_value_lang.value, count(DISTINCT product.id_product) as magnitude';
        $queryParts['groupBy']  = 'feature_value_lang.id_feature_value';
        $queryParts['orderBy']  = 'feature.position';
        $queryParts['from']    .= ' ' . $this->getJoinsSQLForQuery('', $initialFacet)
                                . ' INNER JOIN prefix_feature_value_lang feature_value_lang
                                       ON feature_value_lang.id_feature_value = feature_product.id_feature_value
                                           AND feature_value_lang.id_lang = ' . (int)$this->context->getLanguageId()
                                . ' INNER JOIN prefix_feature feature ON feature.id_feature = feature_product.id_feature'
        ;

        $sql = (new QueryAssembler)->assemble($queryParts);

        $rows = $this->db->select($sql);

        $facet = clone $initialFacet;
        $facet->clearFilters();

        foreach ($rows as $row) {
            $facet->addFilter((new Filter)
                ->setCondition(['id_feature_value' => (int)$row['id_feature_value']])
                ->setMagnitude((int)$row['magnitude'])
                ->setLabel($row['value'])
                ->setEnabled(false)
            );
        }

        return $facet;
    }

    public function buildFilterFromLabel($label)
    {
        $id_feature_value = (int)$this->db->getValue('
            SELECT fl.id_feature_value FROM prefix_feature_value_lang fl
            INNER JOIN ps_feature_value fv
                ON fv.id_feature_value = fl.id_feature_value
            INNER JOIN ps_feature_shop fs
                ON fs.id_feature = fv.id_feature
                AND fs.id_shop = ' . (int)$this->context->getShopId() . '
            WHERE id_lang = ' . (int)$this->context->getLanguageId() . '
                AND fl.value = "' . $this->db->escape($label) . '"'
        );

        $filter = new Filter;
        $filter
            ->setCondition(['id_feature_value' => $id_feature_value])
            ->setLabel($label)
        ;
        return $filter;
    }
}
