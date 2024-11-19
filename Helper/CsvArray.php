<?php

namespace Railsformers\EcarImport\Helper;

class CsvArray{
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * @param \Magento\Framework\File\Csv $csvProcessor
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor
    ) {
        $this->csvProcessor = $csvProcessor;
    }

    /**
     * Read CSV file and convert to associative array
     *
     * @param string $filePath
     * @return array
     */
    public function csvToArray($filePath)
    {
        $data = $this->csvProcessor->getData($filePath);
        if (count($data) > 0) {
            $headers = array_shift($data);
            $associativeArray = [];
            foreach ($data as $row) {
                $associativeArray[] = array_combine($headers, $row);
            }
            return $associativeArray;
        }
        return [];
    }

    /**
     * Merge two CSV files base on 'IdCzesci' column
     * @param string $pricesFile
     * @param string $stockFile
     * @return array
     */
    public function mergeCsvFilesById(string $pricesFile, string $stockFile): array
    {
        $pricesArr = $this->csvToArray($pricesFile);
        $stockArr = $this->csvToArray($stockFile);
        
        $mergedArr = array();
        $tmpPricesArr = array();

        foreach($pricesArr as $item)
        {
            if($item['WersjaDanych'] != 'AutoGaleri_KLNT')
                continue;

            if((float)$item['Cena'] < 0.1)
                continue;

            $tmpPricesArr[$item['IdCzesci']] = $item;

        }

        foreach($stockArr as $item)
        {
            if(isset($tmpPricesArr[$item['IdCzesci']]))
            {
                $mergedArr[$item['IdCzesci']] = array_merge($tmpPricesArr[$item['IdCzesci']], $item);
            }
        }

        return $mergedArr;
    }
}