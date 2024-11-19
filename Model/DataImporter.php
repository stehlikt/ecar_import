<?php

namespace Railsformers\EcarImport\Model;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Filesystem;
use Railsformers\EcarImport\Helper\CsvArray;
use Railsformers\EcarImport\Model\EcarImport;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\CategoryRepository;
use Railsformers\EcarImport\Helper\Rates;
use Railsformers\EcarImport\Helper\Sales;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Adapter as ImportAdapter;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\Product\Action as ProductAction;

class DataImporter
{
    protected $filesystem;
    protected $csvArray;
    protected $ecarImport;
    protected $productCollectionFactory;
    protected $categoryRepository;
    protected $rates;
    protected $sales;
    protected $importModel;
    protected $resourceConnection;
    protected $eavConfig;
    protected $productAction;

    public function __construct(
        Context $context,
        Filesystem $filesystem,
        CsvArray $csvArray,
        EcarImport $ecarImport,
        ProductCollectionFactory $productCollectionFactory,
        CategoryRepository $categoryRepository,
        Rates $rates,
        Sales $sales,
        Import $importModel,
        ResourceConnection $resourceConnection,
        EavConfig $eavConfig,
        ProductAction $productAction
    ) {
        $this->filesystem = $filesystem;
        $this->csvArray = $csvArray;
        $this->ecarImport = $ecarImport;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->rates = $rates;
        $this->sales = $sales;
        $this->importModel = $importModel;
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->productAction = $productAction;
    }

    public function execute($force = false)
    {
        $mediaDir = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();

        $mapEcar = $mediaDir . 'ecar_import/data1w.ecar';
        $dataEcar = $mediaDir . 'ecar_import/data0.ecar';

        $codesCsv = $mediaDir .'ecar_import/codes.csv';
        $pricesCsv = $mediaDir .'ecar_import/prices.csv';
        $stockCsv = $mediaDir .'ecar_import/stock.csv';

        // Export kódů do CSV
        if (file_exists($mapEcar)) {
            if(((file_exists($codesCsv) && filemtime($mapEcar) > filemtime($codesCsv)) || $force))
            {
                $cmd = 'mdb-export ' . $mapEcar . ' Czesci  > ' . $codesCsv;
                exec($cmd, $output, $ret);
                if ($ret == 1) {
                    echo 'Chyba pri exportu z ' . $mapEcar . PHP_EOL;
                    exit;
                }
                echo("vytvoření souboru: ". $codesCsv . "\n");
                $tmpCodesArr = $this->csvArray->csvToArray($codesCsv);
                $codesArr = array();

                foreach ($tmpCodesArr as $item)
                {
                    $codesArr[$item['Numer']] = $item;
                }

                $allExistingProducts = $this->getAllProducts();

                foreach($allExistingProducts as $product)
                {
                    if(isset($codesArr[$product->getData('code_ek')]))
                    {
                        $ecarImportData = $this->ecarImport->getByEcarCode($product->getData('code_ek'));
                        if (!$ecarImportData) {
                            $this->ecarImport->insertRow($codesArr[$product->getData('code_ek')]);
                            echo("Přidání produktu s ecar_codem " . $product->getData('code_ek') . "\n");
                        }
                    }
                }
            }
        } else {
            echo('Soubor ' . $mapEcar . ' neexistuje.');
        }

        // Export cen a skladovosti do CSV
        if (file_exists($dataEcar)) {
            if ((file_exists($stockCsv) && filemtime($dataEcar) > filemtime($stockCsv)) || $force)
            {
                // Ceny
                $cmd = 'mdb-export ' . $dataEcar . ' DaneCeny  > ' . $pricesCsv;
                exec($cmd, $output, $ret);
                if ($ret == 1) {
                    echo "Chyba pri exportu z " . $dataEcar . PHP_EOL;
                    exit;
                }
                echo("vytvoření souboru: ". $pricesCsv . "\n");

                // Skladovost
                $cmd = 'mdb-export ' . $dataEcar . ' CzesciStany  > ' . $stockCsv;
                exec($cmd, $output, $ret);
                if ($ret == 1) {
                    echo 'Chyba pri exportu z ' . $dataEcar . PHP_EOL;
                    exit;
                }
                echo("vytvoření souboru: ". $stockCsv . "\n");

                $dataArr = $this->csvArray->mergeCsvFilesById($pricesCsv, $stockCsv);

                $this->ecarImport->resetUpdate();
                $allEcarImportProducts = $this->ecarImport->getAllEcarProducts();
                foreach ($allEcarImportProducts as $product)
                {
                    if(isset($dataArr[$product['czesci_id']]))
                    {
                        $importProduct = $dataArr[$product['czesci_id']];

                        if(round((float)$importProduct['Cena']) != $product['price_pl'])
                        {
                            $this->ecarImport->updatePrice($importProduct);
                            echo("Aktualizovala se cena u souboru s Id Czesci:". $importProduct['IdCzesci'] . "\n");
                        }

                        if($importProduct['Magazyn1'] != $product['stock'])
                        {
                            if(!(($importProduct['Magazyn1'] == '>5' || $importProduct['Magazyn1'] == '>10') && $product['stock'] == 100)){
                                $this->ecarImport->updateStock($importProduct);
                                echo("Aktualizovala se skladovost u souboru s Id Czesci:". $importProduct['IdCzesci'] . "\n");
                            }
                        }
                    }
                    else
                    {
                        echo("V csv neexistuje produkt s Ecar code: ".$product['ecar_code'] . ', ID czesci: ' . $product['czesci_id'] . "\n");
                    }
                }

                if($force)
                {
                    $this->ecarImport->setUpdateForAllProducts();
                }

                $importProducts = $this->ecarImport->getProductsForImport();
                if($importProducts)
                {
                    echo("Importuji ".count($importProducts)." produktů \n");
                    $updateFilePath = $mediaDir.'ecar_import/update.csv';
                    $skus_in_csv = $this->createUpdateCsv($importProducts,$updateFilePath);
                    $this->importProductsFromCsv($updateFilePath);

                    echo("Nastavení is_updated na TRUE \n");
                    $this->updateIsUpdatedAttribute($skus_in_csv, true);
                    echo("Nastavení is_updated na FALSE \n");
                    $this->updateIsUpdatedAttribute($skus_in_csv, false);
                }
            }
            elseif(!file_exists($stockCsv) || !file_exists($pricesCsv))
            {
                echo('Soubor ' . $stockCsv . ' nebo soubor ' . $pricesCsv . " neexistuje. Spustě prosím příkaz s parametrem -f \n");
            }
        } else {
            echo("Soubor " . $dataEcar . " neexistuje");
        }
    }

    protected function getAllProducts()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*'); // Vybere všechny atributy

        return $collection;
    }

    protected function getProductsByEcarCode($code)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('code_ek', ['eq' => $code]);

        return $collection;
    }

    protected function getFourthLevelCategoryName($product)
    {
        $categoryIds = $product->getCategoryIds();
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                if ($category->getLevel() == 4) {
                    return $category->getName();
                }
            }
        }
        return '';
    }

    public function normalizeString($str) {
        $str = mb_strtolower($str, "UTF-8");

        $str = str_replace(' a ',' ',$str);

        $transliterationTable = [
            'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i',
            'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u',
            'ů' => 'u', 'ý' => 'y', 'ž' => 'z',
            'ä' => 'a', 'ľ' => 'l', 'ĺ' => 'l', 'ô' => 'o', 'ŕ' => 'r',
            'Á' => 'a', 'Č' => 'c', 'Ď' => 'd', 'É' => 'e', 'Ě' => 'e', 'Í' => 'i',
            'Ň' => 'n', 'Ó' => 'o', 'Ř' => 'r', 'Š' => 's', 'Ť' => 't', 'Ú' => 'u',
            'Ů' => 'u', 'Ý' => 'y', 'Ž' => 'z',
            'Ä' => 'a', 'Ľ' => 'l', 'Ĺ' => 'l', 'Ô' => 'o', 'Ŕ' => 'r'
        ];
        $str = strtr($str, $transliterationTable);

        $str = preg_replace('/[^\w]/', ' ', $str);

        $str = str_replace(' ', '_', $str);

        $str = preg_replace('/_+/', '_', $str);

        return $str;
    }

    public function createUpdateCsv($products, $filePath)
    {
        $csvFile = fopen($filePath, 'w');
        $headers = ['sku', 'price', 'special_price', 'qty', 'is_in_stock'];
        fputcsv($csvFile, $headers);

        $defaultRate = 1.23 * $this->rates->getDefaultRate();

        $skus = array();

        foreach ($products as $item) {
            $productCollection = $this->getProductsByEcarCode($item['ecar_code']);
            foreach ($productCollection as $product) {
                if ($product->getId()) {
                    $categoryName = $this->normalizeString($this->getFourthLevelCategoryName($product));

                    $rate = $this->rates->getRate($categoryName);
                    if ($rate)
                        $rate = 1.23 * $rate;
                    else
                        $rate = $defaultRate;

                    $price = round($rate * $item['price_pl']);

                    $sale = $this->sales->getSale($categoryName);
                    if ($sale)
                        $special_price = round($rate * $item['price_pl'] - $rate * $item['price_pl'] * ($sale / 100));
                    else
                        $special_price = $price;

                    $field = [
                        'sku' => $product->getSku(),
                        'price' => $price,
                        'special_price' => $special_price,
                        'qty' => $item['stock'],
                        'is_in_stock' => $item['stock'] > 0 ? 1 : 0
                    ];

                    $skus[] = $product->getSku();
                    fputcsv($csvFile, $field);
                }
            }
        }
        fclose($csvFile);

        return $skus;
    }

    /**
     * Import products from a CSV file using Magento's import functionality.
     *
     * @param string $filePath Path to the CSV file.
     */
    public function importProductsFromCsv($filePath)
    {
        if (!file_exists($filePath)) {
            echo('CSV file does not exist. \n');
            return;
        }

        $this->importModel->setData([
            'entity' => 'catalog_product',
            'behavior' => 'append',
            'validation_strategy' => 'validation-stop-on-errors',
        ]);

        try {
            $directoryRead = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
            $source = ImportAdapter::findAdapterFor($filePath, $directoryRead);
            $this->importModel->validateSource($source);

            if ($this->importModel->importSource()) {
                $this->importModel->invalidateIndex();
                echo('Import successful \n');
            } else {
                echo('Import failed \n');
            }

        } catch (\Exception $e) {
            echo("Error: " . $e->getMessage());
        }
    }

    protected function updateIsUpdatedAttribute($skus, $is_updated)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('sku');

        if (!$is_updated) {
            $productCollection->addAttributeToFilter('sku', ['nin' => $skus]);
        } else {
            $productCollection->addAttributeToFilter('sku', ['in' => $skus]);
        }

        $productIds = $productCollection->getAllIds();

        if (!empty($productIds)) {
            $this->productAction->updateAttributes($productIds, ['is_updated' => $is_updated], 0);
        }
    }
}
