<?php echo $savvy->render($context->mark); ?>

<dl class="fix-mark-details">
    <dt>Found on page</dt>
    <dd>
        <?php echo $context->page->uri ?> (<a href="<?php echo $context->page->getURL() ?>">view page report</a> or <a href="<?php echo $context->page->uri ?>">View Page</a>)
    </dd>
    <dt>Points Deducted from the Metric Grade</dt>
    <dd>
        <?php
            $points_deducted = $context->page_mark->points_deducted;
            if ($context->metric_grade->isPassFail()) {
                if ($context->page_mark->points_deducted) {
                    $points_deducted = $context->page_mark->points_deducted . ' Fail';
                } else {
                    $points_deducted = ' 0 Pass';
                }
            }
            if ($context->page_mark->points_deducted === '0.00') {
                $points_deducted = '0 (notice, this is informational and does not count toward the metric grade)';
            }
            echo $points_deducted;
        ?>
    </dd>
    
    <?php
    if (!empty($context->page_mark->value_found)) {
        ?>
        <dt>Value Found</dt>
        <dd><?php echo $context->page_mark->value_found ?></dd>
    <?php
    }
    ?>
    <?php if (!empty($context->page_mark->help_text)): ?>
    <dt>Help Text</dt>
    <dd><?php  echo \Michelf\MarkdownExtra::defaultTransform($context->page_mark->help_text); ?></dd>
    <?php endif; ?>
    <dt>Location on the Page</dt>
    <?php
    $location = 'Page';
    if (!empty($context->page_mark->line) && !empty($context->page_mark->line)) {
        $location = 'Line ' . $context->page_mark->line . ', Column ' . $context->page_mark->col;
    }
    if (!empty($context->page_mark->context)) {
        $location = 'HTML Context: <pre><code>' . trim(htmlentities($context->page_mark->getRaw('context'), ENT_COMPAT | ENT_HTML401, 'UTF-8', false)) . '</code></pre>';
    }
    ?>
    <dd><?php echo $location ?></dd>
</dl>

<a href="<?php echo $context->page->getURL() ?>">Go back to the page report</a>

<h2>Override</h2>
<?php if ($context->page_mark->canBeOverridden()): ?>
    <?php if (!$context->page_mark->hasOverride()): ?>
        <p>
            If you have manually reviewed this and determined that it is not in fact an error, you can <a href="<?php echo $context->site->getURL() ?>overrides/add/?page_mark=<?php echo $context->page_mark->id ?>" class="button dcf-btn">create an override for this mark</a> to prevent it from showing up on future scans.
        </p>
    <?php else: ?>
        <p>This mark has already been overridden.</p>
    <?php endif; ?>
<?php else: ?>
    <p>This mark can not be overridden.</p>
<?php endif; ?>

<div class="pull-right dcf-float-right">
    <span class="machine_name">Machine Name: <?php echo $context->mark->machine_name ?></span>
</div>
