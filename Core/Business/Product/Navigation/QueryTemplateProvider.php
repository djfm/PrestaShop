<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;

class QueryTemplateProvider
{
    private $db;

    public function __construct(AutoPrefixingDatabase $db)
    {
        $this->db = $db;
    }

    public function getQueryTemplate(
        QueryContext $context,
        Query $initialQuery
    ) {
        $facets = [];

        /*
         * Categories
         */

        $categoryFacet = new DatabaseFacet\CategoryFacet($this->db, $context, new Facet());
        $categoryFacet
            ->setLabel('Category')
            ->setIdentifier('categories')
        ;

        $facets[] = $categoryFacet;

        /*
         * Attributes
         */

        $sql = 'SELECT agl.id_attribute_group, agl.public_name FROM prefix_attribute_group_lang agl
                INNER JOIN prefix_attribute_group ag
                    ON ag.id_attribute_group = agl.id_attribute_group
                    WHERE agl.id_lang = '.(int) $context->getLanguageId().'
                ORDER BY ag.position';

        foreach ($this->db->select($sql) as $group) {
            $facet = new DatabaseFacet\AttributeGroupFacet($this->db, $context, new Facet());
            $facet
                ->setLabel($group['public_name'])
                ->setIdentifier('attributeGroup'.(int) $group['id_attribute_group'])
                ->setCondition([
                    'id_attribute_group' => (int) $group['id_attribute_group'],
                ])
            ;
            $facets[] = $facet;
        }

        /*
         * Features
         */

        $sql = 'SELECT fl.id_feature, fl.name FROM prefix_feature_lang fl
                    INNER JOIN prefix_feature_shop fs ON fs.id_feature = fl.id_feature
                    INNER JOIN prefix_feature f ON f.id_feature = fs.id_feature
                WHERE fl.id_lang = '.(int) $context->getLanguageId().'
                AND fs.id_shop = '.(int) $context->getShopId()
        ;

        foreach ($this->db->select($sql) as $feature) {
            $facet = new DatabaseFacet\FeatureFacet($this->db, $context, new Facet());
            $facet
                ->setLabel($feature['name'])
                ->setIdentifier('feature'.(int) $feature['id_feature'])
                ->setCondition([
                    'id_feature' => (int) $feature['id_feature'],
                ])
            ;
            $facets[] = $facet;
        }

        /*
         * Suppliers
         */

         $suppliersFacet = new DatabaseFacet\SupplierFacet($this->db, $context, new Facet());
        $suppliersFacet
             ->setLabel('Supplier')
             ->setIdentifier('suppliers')
         ;

        $facets[] = $suppliersFacet;

        $template = new DatabaseQuery($this->db, $context);

        foreach ($facets as $position => $facet) {
            $facet->setPosition($position);
            if (($initial = $initialQuery->getFacetByIdentifier(
                $facet->getIdentifier()
             ))) {
                $template->addFacet($initial);
            } else {
                $template->addFacet($facet);
            }
        }

        $template->setPagination($initialQuery->getPagination());
        $template->setSortOption($initialQuery->getSortOption());

        return $template->sortFacets();
    }
}
