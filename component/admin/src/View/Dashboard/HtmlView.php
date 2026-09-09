<?php
namespace xdecaro\Component\Tasks\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use xdecaro\Component\Tasks\Administrator\Extension\TasksComponent;

final class HtmlView extends BaseHtmlView
{
    public $stats = [];
    public $recent = [];

    public function display($tpl = null): void
    {
        $component = Factory::getApplication()->bootComponent('com_xdecarotasks');
        if ($component instanceof TasksComponent) {
            $component->getCoreIntegrationService()->useAssets(Factory::getApplication()->getDocument()->getWebAssetManager());
            $this->stats = $component->getTaskService()->getDashboardStats();
            $this->recent = $component->getTaskService()->query(['limit' => 8]);
        }
        parent::display($tpl);
    }
}
