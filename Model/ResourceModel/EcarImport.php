<?php

namespace Railsformers\EcarImport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class EcarImport extends AbstractDb{

    protected function _construct()
    {
        $this->_init('ecar_import', 'id');
    }
    public function insertRow($data)
    {
        $connection = $this->getConnection();
        $fields = [
            'czesci_id' => $data['IdCzesci'],
            'ecar_code' => $data['Numer'],
            'b_update' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $connection->insert($this->getMainTable(), $fields);
        return $connection->lastInsertId();
    }
    public function updatePrice($data)
    {
        $connection = $this->getConnection();
        $fields = [
            'b_update' => 1,
            'price_pl' => round($data['Cena']),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $connection->update($this->getMainTable(),$fields,['czesci_id = ?' => $data['IdCzesci']]);
    }
    public function updateStock($data)
    {
        $connection = $this->getConnection();

        $fields = [
            'b_update' => 1,
            'stock' => $data['Magazyn1'] == '>5' || $data['Magazyn1'] == '>10' ? 100 : $data['Magazyn1'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $connection->update($this->getMainTable(),$fields,['czesci_id = ?' => $data['IdCzesci']]);
    }
    public function getProductByEcarCode($code)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('ecar_code = ?', $code);

        return $connection->fetchRow($select);
    }
    public function getProductByCzesciId($id)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('czesci_id = ?', $id);
        
        return $connection->fetchRow($select);
    }
    public function getProductsForImport()
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('b_update = ?', 1);

        return $connection->fetchAll($select);
    }
    public function resetUpdate()
    {
        $connection = $this->getConnection();

        $data = ['b_update' => 0];
        $where = ['b_update = ?' => 1];

        $connection->update($this->getMainTable(),$data,$where);
    }
    public function setUpdateForAllProducts()
    {
        $connection = $this->getConnection();

        $data = ['b_update' => 1];

        $connection->update($this->getMainTable(), $data);
    }
    public function getAllEcarProducts()
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable());
        
        return $connection->fetchAll($select);
    }
}