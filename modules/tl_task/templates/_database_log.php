<?php $log = $form->getObject()->getLog(); ?>
<?php if (!empty($log)): ?>
<div style="margin: 20px"> 
<pre>
<?php echo htmlentities($log); ?>
</pre>
</div>
<?php else: ?>
  <div style="margin: 20px"> 
    <?php echo __('Database log not recorded'); ?>
  </div>
<?php endif; ?>