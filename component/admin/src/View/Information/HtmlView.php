<?php
namespace xdecaro\Component\Tasks\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use Throwable;
use xdecaro\Component\Tasks\Administrator\Extension\TasksComponent;

final class HtmlView extends BaseHtmlView
{
    public $diagnostics = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication(); $component = $app->bootComponent('com_xdecarotasks');
        if ($component instanceof TasksComponent) { $component->getCoreIntegrationService()->useAssets($app->getDocument()->getWebAssetManager()); }
        $db = Factory::getContainer()->get(DatabaseInterface::class); $prefix = $db->getPrefix(); $tables = (array) $db->getTableList();
        $expected = ['items','assignees','checklist','comments','history']; $tableStatus = [];
        foreach ($expected as $name) { $tableStatus['#__xdecarotasks_' . $name] = in_array($prefix . 'xdecarotasks_' . $name, $tables, true); }
        $notifications = false;
        try { $notificationsComponent = $app->bootComponent('com_xdecaronotifications'); $notifications = is_object($notificationsComponent) && method_exists($notificationsComponent, 'getNotificationService'); } catch (Throwable $e) { $notifications = false; }
        $this->diagnostics = ['version' => '1.0.0', 'joomla' => defined('JVERSION') ? JVERSION : '', 'php' => PHP_VERSION, 'tables' => $tableStatus, 'core' => class_exists('xdecaro\\Core\\Integration\\CapabilityRegistry'), 'notifications' => $notifications];
        parent::display($tpl);
    }
}
