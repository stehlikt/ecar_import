<?php

namespace Railsformers\EcarImport\Model;

Use Magento\Framework\Model\AbstractModel;

class EcarImport extends AbstractModel{

    protected function _construct()
    {
        $this->_init('Railsformers\EcarImport\Model\ResourceModel\EcarImport');
    }

    public function getByEcarCode($code)
    {
        return $this->_getResource()->getProductByEcarCode($code);
    }

    public function getByCzesciId($id)
    {
        return $this->_getResource()->getProductByCzesciId($id);
    }
    public function getProductsForImport()
    {
       return $this->_getResource()->getProductsForImport();
    }
    public function insertRow($row)
    {
        $this->_getResource()->insertRow($row);
    }
    public function updatePrice($data)
    {
        $this->_getResource()->updatePrice($data);
    }
    public function updateStock($data)
    {
        $this->_getResource()->updateStock($data);
    }
    public function resetUpdate()
    {
        $this->_getResource()->resetUpdate();
    }
    public function setUpdateForAllProducts()
    {
        $this->_getResource()->setUpdateForAllProducts();
    }
    public function getAllEcarProducts()
    {
        return $this->_getResource()->getAllEcarProducts();
    }
}