<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Dashboard') ?> · Manna Kampus</title>
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="border-b border-orange-100 bg-white px-5 py-5 lg:w-64 lg:border-r lg:border-b-0">
            <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-3 text-slate-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 font-bold text-white">MK</span>
                <span>
                    <span class="block text-sm font-bold">Manna Kampus</span>
                    <span class="block text-xs text-slate-500">Monitoring CCTV</span>
                </span>
            </a>
            <nav class="mt-8">
                <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-3 rounded-xl bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-700">
                    <span aria-hidden="true">▦</span> Dashboard
                </a>
                <p class="mt-7 px-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Menu akan hadir</p>
            </nav>
        </aside>
        <main class="min-w-0 flex-1">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 sm:px-8">
                <div>
                    <p class="text-xs font-medium text-slate-500">Toko aktif</p>
                    <p class="text-sm font-semibold text-slate-700">Belum dipilih</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-700"><?= esc($user['name']) ?></p>
                        <p class="text-xs text-slate-500"><?= esc($user['username']) ?></p>
                    </div>
                    <form action="<?= site_url('logout') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-700">Keluar</button>
                    </form>
                </div>
            </header>
            <div class="p-5 sm:p-8">
                <?= $this->renderSection('content') ?>
            </div>
        </main>
    </div>
</body>
</html>
