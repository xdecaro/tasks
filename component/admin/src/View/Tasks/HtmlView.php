<?php
namespace xdecaro\Component\Tasks\Administrator\View\Tasks;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use xdecaro\Component\Tasks\Administrator\Extension\TasksComponent;

final class HtmlView extends BaseHtmlView
{
    public $items = [];
    public $filters = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->filters = ['search' => $app->input->getString('search'), 'status' => $app->input->getCmd('status'), 'priority' => $app->input->getCmd('priority'), 'overdue' => $app->input->getBool('overdue'), 'limit' => 200];
        $component = $app->bootComponent('com_xdecarotasks');
        if ($component instanceof TasksComponent) {
            $component->getCoreIntegrationService()->useAssets($app->getDocument()->getWebAssetManager());
            $this->items = $component->getTaskService()->query($this->filters);
        }
        parent::display($tpl);
    }
}
