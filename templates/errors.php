<?php if (!empty($errors)): ?>
<ul class="errors"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach ?></ul>
<?php endif ?>
