<?php $log_file = $form->getObject()->getLogFile(); ?>
<?php if ($log_file && file_exists($log_file)): ?>
<div style="margin: 20px"> 
<pre><?php echo htmlentities(file_get_contents($log_file)); ?>
</pre>
</div>
<?php else: ?>
  <div style="margin: 20px">
  <?php echo __('Log file does not exists or was purged)'); ?>
  </div>
<?php endif; ?>