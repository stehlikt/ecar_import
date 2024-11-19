<?php

namespace Railsformers\EcarImport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Sales extends AbstractHelper
{
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }
    public function getSale($sale)
    {
        return $this->scopeConfig->getValue(
            'railsformers_ecar_import/sales/sale_'.$sale,
            ScopeInterface::SCOPE_STORE
        );
    }
}