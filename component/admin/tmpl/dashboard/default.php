<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdecaro-scope">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0"><?php echo Text::_('COM_XDECAROTASKS_DASHBOARD'); ?></h1><a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_xdecarotasks&view=task'); ?>"><?php echo Text::_('COM_XDECAROTASKS_NEW_TASK'); ?></a></div>
  <div class="row g-3 mb-4">
    <?php foreach (['open','in_progress','blocked','overdue','completed'] as $key) : ?><div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_XDECAROTASKS_STAT_' . strtoupper($key)); ?></div><div class="display-6"><?php echo (int) ($this->stats[$key] ?? 0); ?></div></div></div></div><?php endforeach; ?>
  </div>
  <div class="card"><div class="card-header d-flex justify-content-between"><strong><?php echo Text::_('COM_XDECAROTASKS_RECENT'); ?></strong><a href="<?php echo Route::_('index.php?option=com_xdecarotasks&view=tasks'); ?>"><?php echo Text::_('COM_XDECAROTASKS_VIEW_ALL'); ?></a></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th><?php echo Text::_('COM_XDECAROTASKS_FIELD_TITLE'); ?></th><th><?php echo Text::_('COM_XDECAROTASKS_FIELD_STATUS'); ?></th><th><?php echo Text::_('COM_XDECAROTASKS_FIELD_PRIORITY'); ?></th><th><?php echo Text::_('COM_XDECAROTASKS_FIELD_DUE_AT'); ?></th></tr></thead><tbody><?php foreach ($this->recent as $item) : ?><tr><td><a href="<?php echo Route::_('index.php?option=com_xdecarotasks&view=task&id=' . (int) $item['id']); ?>"><?php echo htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></a></td><td><?php echo htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) $item['priority'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($item['due_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
