<?php if (isset($form)): ?>
  <?php $tl_task = $form->getObject(); ?>
  <div class="sf_admin_form_row sf_admin_boolean sf_admin_form_field">
    <div>
      <label for="batch_is_ok">Lenght</label>
      <?php if ($tl_task->getStartedAt(null) && $tl_task->getEndedAt(null)): ?>
        <?php $ended_at_var = $tl_task->getEndedAt(null); ?>
        <?php if(($ended_at_var instanceof DateTime) && is_callable(array($ended_at_var, 'getTimestamp'))): ?>
          <?php $seconds = $tl_task->getEndedAt(null)->getTimestamp() - $tl_task->getStartedAt(null)->getTimestamp(); ?>
          <?php echo date('H:i:s', $seconds); ?>
        <?php else: ?>
          <?php $seconds = strtotime($tl_task->getEndedAt()) - strtotime($tl_task->getStartedAt()); ?>
          <?php echo date('H:i:s', $seconds); ?>
        <?php endif; ?>
      <?php else: ?>
        <?php __('NA.') ?>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <?php if ($tl_task->getStartedAt(null) && $tl_task->getEndedAt(null)): ?>
    <?php $ended_at_var = $tl_task->getEndedAt(null); ?>
    <?php if(($ended_at_var instanceof DateTime) && is_callable(array($ended_at_var, 'getTimestamp'))): ?>
      <?php $seconds = $tl_task->getEndedAt(null)->getTimestamp() - $tl_task->getStartedAt(null)->getTimestamp(); ?>
      <?php echo date('H:i:s', $seconds); ?>
    <?php else: ?>
      <?php $seconds = strtotime($tl_task->getEndedAt()) - strtotime($tl_task->getStartedAt()); ?>
      <?php echo date('H:i:s', $seconds); ?>
    <?php endif; ?>
  <?php else: ?>
    <?php __('NA.') ?>
  <?php endif; ?>
<?php endif; ?>