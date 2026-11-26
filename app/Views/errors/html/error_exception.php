<?php
http_response_code(500);
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Application Error</title></head>
<body style="font-family:Arial,sans-serif;padding:2rem;line-height:1.5">
<h1>Application Error</h1>
<p><?= esc($message) ?></p>
<?php if (ENVIRONMENT !== 'production') : ?>
<p><strong><?= esc(clean_path($file)) ?>:<?= esc($line) ?></strong></p>
<?php endif ?>
</body></html>
