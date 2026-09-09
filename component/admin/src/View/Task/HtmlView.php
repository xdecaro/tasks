<?php
namespace xdecaro\Component\Tasks\Administrator\View\Task;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use xdecaro\Component\Tasks\Administrator\Extension\TasksComponent;

final class HtmlView extends BaseHtmlView
{
    public $task;
    public $form;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication(); $id = $app->input->getInt('id');
        $component = $app->bootComponent('com_xdecarotasks');
        if ($component instanceof TasksComponent) {
            $component->getCoreIntegrationService()->useAssets($app->getDocument()->getWebAssetManager());
            $this->task = $id > 0 ? $component->getTaskService()->getTask($id) : null;
        }
        Form::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . '/forms');
        $this->form = Form::getInstance('com_xdecarotasks.task', 'task', ['control' => 'jform']);
        if ($this->task) { $this->form->bind($this->task); }
        parent::display($tpl);
    }
}
