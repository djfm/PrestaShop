<?php

use PrestaShop\PrestaShop\Core\Business\Product\ProductPresenter;
use PrestaShop\PrestaShop\Core\Business\Product\ProductPresentationSettings;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;

abstract class ProductPresentingFrontControllerCore extends FrontController
{
    protected function getProductPresentationSettings()
    {
        $settings = new ProductPresentationSettings;

        $settings->catalog_mode = Configuration::get('PS_CATALOG_MODE');
        $settings->restricted_country_mode = $this->restricted_country_mode;
        $settings->include_taxes = !Product::getTaxCalculationMethod((int)$this->context->cookie->id_customer);
        $settings->allow_add_variant_to_cart_from_listing =  (int)Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY');
        $settings->stock_management_enabled = Configuration::get('PS_STOCK_MANAGEMENT');

        return $settings;
    }

    protected function getProductPresenter()
    {
        $imageRetriever = new Adapter_ImageRetriever($this->context->link);

        return new ProductPresenter(
            $imageRetriever,
            $this->context->link,
            new Adapter_PricePresenter,
            new Adapter_ProductColorsRetriever,
            new Adapter_Translator
        );
    }

    protected function getProductLister()
    {
        return Adapter_ServiceLocator::get('PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister');
    }

    protected function getProductQueryContext()
    {
        return (new QueryContext())
            ->setLanguageId($this->context->language->id)
            ->setShopId($this->context->shop->id)
        ;
    }

    protected function assembleProduct(array $product)
    {
        $nb_days_new_product = Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        if (!is_int($nb_days_new_product)) {
            $nb_days_new_product = 20;
        }

        if (empty($product['id_product_attribute'])) {
            $product['id_product_attribute'] = 0;
        }

        if (!array_key_exists('new', $product)) {
            $productAge = round((time() - strtotime($product['date_add'])) / 24 / 3600);
            $product['new'] = $productAge < $nb_days_new_product;
        }

        return Product::getProductProperties(
            $this->context->language->id,
            $product,
            $this->context
        );
    }

    protected function assembleProducts(array $products)
    {
        return array_map([$this, 'assembleProduct'], $products);
    }
}
