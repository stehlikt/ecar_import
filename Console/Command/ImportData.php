<?php

namespace Railsformers\EcarImport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Railsformers\EcarImport\Model\DataImporter;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\Manager;

class ImportData extends Command
{
    protected $objectManager;
    protected $scopeConfig;
    private $state;
    private $configWriter;
    private $cacheManager;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\State $state,
        WriterInterface $configWriter,
        Manager $cacheManager
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->state = $state;
        $this->configWriter = $configWriter;
        $this->cacheManager = $cacheManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            $output->writeln('Area code set to ADMINHTML.');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $output->writeln('Area code is already set.');
        }

        $dataImporter = $this->objectManager->create(DataImporter::class);

        $forceImportConfig = $this->scopeConfig->getValue(
            'railsformers_ecar_import/import_settings/force_import'
        );

        $force = $input->getOption('force');

        if ($forceImportConfig == 1) {
            $force = true;
            $this->configWriter->save(
                'railsformers_ecar_import/import_settings/force_import',
                0
            );
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
        }
        
        try {
            $output->writeln('Starting import...');
            $dataImporter->execute($force);
            $output->writeln('Import complete!');
        } catch (\Exception $e) {
            $output->writeln('ERROR: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function configure()
    {
        $this->setName("ecar:import");
        $this->setDescription("Import data from Ecar.");
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force import'
        );
        parent::configure();
    }
}
