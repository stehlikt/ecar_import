<?php

namespace Railsformers\EcarImport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Shell;
use Railsformers\EcarImport\Model\DataImporter as Import;

class Index extends Action
{
    private $import;
    private $shell;

    public function __construct(
        Context $context,
        Import $import,
        Shell $shell
    ) {
        parent::__construct($context);
        $this->import = $import;
        $this->shell = $shell;
    }

    public function execute()
    {
        try {
            // Spustí konzolový příkaz
            $command = '/usr/bin/php8.3 ' . BP . '/bin/magento ecar:import -f';
            $output = $this->shell->execute($command);

            // Zobrazí výstup příkazu v administračním rozhraní
            $this->messageManager->addSuccessMessage(__('Import úspěšně proběhl! Výstup: ' . $output));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Chyba při importu: ' . $e->getMessage()));
        }

        return $this->_redirect('adminhtml/system_config/edit', ['section' => 'railsformers_ecar_import']);
    }
}
