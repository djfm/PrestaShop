<?php

use PrestaShop\PrestaShop\Core\Business\Product\ProductPresenter;
use PrestaShop\PrestaShop\Core\Business\Product\ProductPresentationSettings;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryContext;

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
        return Adapter_ServiceLocator::get('PrestaShop\PrestaShop\Core\Business\Product\ProductLister');
    }

    protected function getProductQueryContext()
    {
        return (new ProductQueryContext())
            ->setLanguageId($this->context->language->id)
            ->setShopId($this->context->shop->id)
        ;
    }
}
