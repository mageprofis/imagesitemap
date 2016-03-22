<?php

class MageProfis_ImageSitemap_Model_Sitemap extends Mage_Sitemap_Model_Sitemap
{
    /**
     *
     * @var Varien_Io_File
     */
    protected $io;

    /**
     * Generate XML file
     *
     * @return Mage_Sitemap_Model_Sitemap
     */
    public function generateXml()
    {
        $this->io = new Varien_Io_File();
        $this->io->setAllowCreateFolders(true);
        $this->io->open(array('path' => $this->getPath()));

        if ($this->io->fileExists($this->getSitemapFilename()) && !$this->io->isWriteable($this->getSitemapFilename())) {
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }

        $this->io->streamOpen($this->getSitemapFilename());

        $storeId = $this->getStoreId();
        $date    = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

        if (strstr($this->getSitemapPath(), 'images') != '') {
            $this->io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            $this->io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">');

            $this->generateImagesXml($storeId, $date, $baseUrl);
        } else {
            $this->io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            $this->io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

            $this->generateCategoriesXml($storeId, $date, $baseUrl);
            $this->generateProductsXml($storeId, $date, $baseUrl);
            $this->generatePagesXml($storeId, $date, $baseUrl);
        }

        $this->io->streamWrite('</urlset>');
        $this->io->streamClose();

        $this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }

    /**
     * Generate categories sitemap
     *
     * @param int    $storeId
     * @param string $date
     * @param string $baseUrl
     */
    protected function generateCategoriesXml($storeId, $date, $baseUrl)
    {
        $changefreq = (string)Mage::getStoreConfig('sitemap/category/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/category/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_category')->getCollection($storeId);
        $categories = new Varien_Object();
        $categories->setItems($collection);
        Mage::dispatchEvent('sitemap_categories_generating_before', array(
            'collection' => $categories
        ));
        foreach ($categories->getItems() as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $this->io->streamWrite($xml);
        }
        unset($collection);
    }

    /**
     * Generate products sitemap
     *
     * @param int    $storeId
     * @param string $date
     * @param string $baseUrl
     */
    protected function generateProductsXml($storeId, $date, $baseUrl)
    {
        $changefreq = (string)Mage::getStoreConfig('sitemap/product/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/product/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
        $products = new Varien_Object();
        $products->setItems($collection);
        Mage::dispatchEvent('sitemap_products_generating_before', array(
            'collection' => $products
        ));
        foreach ($products->getItems() as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $this->io->streamWrite($xml);
        }
        unset($collection);
    }


    /**
     * Generate cms pages sitemap
     *
     * @param int    $storeId
     * @param string $date
     * @param string $baseUrl
     */
    protected function generatePagesXml($storeId, $date, $baseUrl)
    {
        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/page/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/cms_page')->getCollection($storeId);
        foreach ($collection as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $this->io->streamWrite($xml);
        }
        unset($collection);
    }

    /**
     * Generate images sitemap
     *
     * @param int    $storeId
     * @param string $date
     * @param string $baseUrl
     */
    protected function generateImagesXml($storeId, $date, $baseUrl)
    {
        $collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
        foreach ($collection as $item) {
            $_product    = Mage::getModel('catalog/product')->load($item->getId());
            $title       = str_replace('&', '', $_product->getName());
            $galleryData = $_product->getData('media_gallery');

            $xmlImg = '';
            foreach ($galleryData['images'] as &$image) {
                $filename = htmlspecialchars(Mage::getBaseUrl('media') . 'catalog/product' . $image['file']);
                $xmlImg .= '<image:image><image:loc>' . $filename . '</image:loc><image:title>' . $title . '</image:title></image:image>';
            }
            $xml = sprintf('<url><loc>%s</loc>%s</url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $xmlImg
            );
            $this->io->streamWrite($xml);
        }
        unset($collection);
    }
}
