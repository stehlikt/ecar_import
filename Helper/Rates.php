<?php

namespace Railsformers\EcarImport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Rates extends AbstractHelper
{
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function getDefaultRate()
    {
        return $this->scopeConfig->getValue(
            'railsformers_ecar_import/rates/rate_default',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getRate($rate)
    {
        return $this->scopeConfig->getValue(
            'railsformers_ecar_import/rates/rate_'.$rate,
            ScopeInterface::SCOPE_STORE
        );
    }
}