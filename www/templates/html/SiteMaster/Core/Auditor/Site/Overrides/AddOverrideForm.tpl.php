<h1>Add an override</h1>

<p>By adding an override, you will prevent a specific mark from appearing on future scans. <strong>Overrides must only be added if you have manually verified that it is not a problem.</strong></p>

<h2>Mark Details</h2>
<p>This override will match the following values, unless otherwise noted.</p>
<dl class="fix-mark-details">
    <dt>Mark</dt>
    <dd><?php echo $context->mark->name ?></dd>
    
    <dt>Page</dt>
    <dd>
        <?php echo $context->page->uri ?>)
    </dd>
    
    <dt>Value Found</dt>
    <dd><?php echo ($context->page_mark->value_found === null)?'(empty)':$context->page_mark->value_found ?></dd>

    <dt>HTML Line Number</dt>
    <dd><?php echo ($context->page_mark->line === null)?'(empty)':$context->page_mark->line ?></dd>

    <dt>HTML Column Number</dt>
    <dd><?php echo ($context->page_mark->col === null)?'(empty)':$context->page_mark->col ?></dd>
    
    <dt>HTML Context</dt>
    <dd>
        <?php if ($context->page_mark->context === null): ?>
            (empty)
        <?php else: ?>
            <pre><code><?php echo trim(htmlentities($context->page_mark->getRaw('context'), ENT_COMPAT | ENT_HTML401, 'UTF-8', false)) ?></code></pre>
        <?php endif; ?>
        
    </dd>
</dl>

<form method="post">
    <fieldset>
        <legend>(required) Scope</legend>
        <label>
            <input type="radio" name="scope" value="page" required checked>
            Just this page
        </label>
        <br />
        <label>
            <input type="radio" name="scope" value="site" required>
            Entire site (page, line, column, and html context will be ignored)
        </label>
    </fieldset>
    <label for="reason">(required) The reason for this override (describe how you determined that this is not an error)</label>
    <textarea id="reason" name="reason" required></textarea>
    <button>Submit</button>
</form>