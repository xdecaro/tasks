<?php
namespace xdecaro\Component\Tasks\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Throwable;
use xdecaro\Component\Tasks\Administrator\Extension\TasksComponent;

final class TaskController extends BaseController
{
    public function save(): void
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication(); $user = $app->getIdentity();
        $data = (array) $app->input->post->get('jform', [], 'array');
        $id = (int) ($data['id'] ?? 0);
        if (!$user->authorise($id > 0 ? 'core.edit' : 'core.create', 'com_xdecarotasks')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        try {
            Form::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . '/forms');
            $form = Form::getInstance('com_xdecarotasks.task', 'task', ['control' => 'jform']);
            $filtered = $form->filter($data);
            if ($form->validate($filtered) === false) { throw new \InvalidArgumentException(Text::_('COM_XDECAROTASKS_ERROR_VALIDATION')); }
            $component = $this->component(); $service = $component->getTaskService();
            if ($id > 0) { $service->update($id, $filtered, (int) $user->id); } else { $id = $service->create($filtered, (int) $user->id); }
            $recipientType = trim((string) ($filtered['recipient_type'] ?? '')); $recipientId = trim((string) ($filtered['recipient_id'] ?? ''));
            if ($recipientId !== '' && $user->authorise('tasks.assign', 'com_xdecarotasks')) {
                $notify = (bool) ComponentHelper::getParams('com_xdecarotasks')->get('notify_assignments', 1);
                $service->assign($id, $recipientType ?: 'user', $recipientId, (int) $user->id, $notify);
            }
            $app->enqueueMessage(Text::_('COM_XDECAROTASKS_TASK_SAVED'), 'success');
        } catch (Throwable $exception) { $app->enqueueMessage($exception->getMessage(), 'error'); }
        $this->setRedirect(Route::_('index.php?option=com_xdecarotasks&view=task&id=' . $id, false));
    }

    public function complete(): void
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication(); $user = $app->getIdentity();
        if (!$user->authorise('tasks.complete', 'com_xdecarotasks')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        $id = $app->input->getInt('id');
        try { $this->component()->getTaskService()->complete($id, (int) $user->id); $app->enqueueMessage(Text::_('COM_XDECAROTASKS_TASK_COMPLETED'), 'success'); } catch (Throwable $e) { $app->enqueueMessage($e->getMessage(), 'error'); }
        $this->setRedirect(Route::_('index.php?option=com_xdecarotasks&view=task&id=' . $id, false));
    }

    public function cancel(): void
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication(); $user = $app->getIdentity();
        if (!$user->authorise('core.edit', 'com_xdecarotasks')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        $id = $app->input->getInt('id');
        try { $this->component()->getTaskService()->cancel($id, (int) $user->id); $app->enqueueMessage(Text::_('COM_XDECAROTASKS_TASK_CANCELLED'), 'success'); } catch (Throwable $e) { $app->enqueueMessage($e->getMessage(), 'error'); }
        $this->setRedirect(Route::_('index.php?option=com_xdecarotasks&view=task&id=' . $id, false));
    }

    public function addChecklist(): void
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication(); $user = $app->getIdentity();
        if (!$user->authorise('core.edit', 'com_xdecarotasks')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        $id = $app->input->getInt('id'); $label = $app->input->post->getString('label');
        try { $this->component()->getTaskService()->addChecklistItem($id, $label, (int) $user->id); } catch (Throwable $e) { $app->enqueueMessage($e->getMessage(), 'error'); }
        $this->setRedirect(Route::_('index.php?option=com_xdecarotasks&view=task&id=' . $id, false));
    }

    public function toggleChecklist(): void
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication(); $user = $app->getIdentity();
        if (!$user->authorise('core.edit', 'com_xdecarotasks')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        $id = $app->input->getInt('id'); $itemId = $app->input->getInt('item_id'); $done = $app->input->getBool('done');
        try { $this->component()->getTaskService()->toggleChecklistItem($id, $itemId, $done, (int) $user->id); } catch (Throwable $e) { $app->enqueueMessage($e->getMessage(), 'error'); }
        $this->setRedirect(Route::_('index.php?option=com_xdecarotasks&view=task&id=' . $id, false));
    }

    public function addComment(): void
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication(); $user = $app->getIdentity();
        if (!$user->authorise('tasks.comment', 'com_xdecarotasks')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        $id = $app->input->getInt('id'); $body = $app->input->post->getString('body');
        try { $this->component()->getTaskService()->addComment($id, $body, (int) $user->id); } catch (Throwable $e) { $app->enqueueMessage($e->getMessage(), 'error'); }
        $this->setRedirect(Route::_('index.php?option=com_xdecarotasks&view=task&id=' . $id, false));
    }

    private function component(): TasksComponent
    {
        $component = Factory::getApplication()->bootComponent('com_xdecarotasks');
        if (!$component instanceof TasksComponent) { throw new \RuntimeException('Tasks component unavailable.'); }
        return $component;
    }
}
