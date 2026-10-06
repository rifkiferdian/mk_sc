<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Manna Kampus') ?></title>
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <?= $this->renderSection('content') ?>
</body>
</html>
