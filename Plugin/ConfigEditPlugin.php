<?php
namespace Railsformers\EcarImport\Plugin;

class ConfigEditPlugin
{
    protected $request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->request = $request;
    }

    public function beforeSetLayout(\Magento\Config\Block\System\Config\Edit $subject)
    {
        $section = $this->request->getParam('section');
        if ($section == 'railsformers_ecar_import') {
            $url = $subject->getUrl('railsformers_ecarimport/import/index');
            $subject->getToolbar()->addChild(
                'custom_button',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Force Import'),
                    'onclick' => "setLocation('{$url}')",
                    'class' => 'save primary'
                ]
            );
        }
    }
}
