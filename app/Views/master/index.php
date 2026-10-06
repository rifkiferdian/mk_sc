<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="max-w-6xl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-orange-600">MASTER DATA</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900"><?= esc($definition['title']) ?></h1>
            <p class="mt-2 text-sm text-slate-500">Data yang sudah tidak digunakan dapat dinonaktifkan, bukan dihapus.</p>
        </div>
        <a href="<?= site_url('master/' . $resource . '/create') ?>" class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-orange-600">+ Tambah <?= esc($definition['singular']) ?></a>
    </div>

    <nav class="mt-7 flex gap-2 overflow-x-auto pb-1 text-sm" aria-label="Navigasi master data">
        <?php foreach (['stores' => 'Toko', 'areas' => 'Area', 'cameras' => 'Kamera', 'shifts' => 'Shift', 'incident-types' => 'Jenis Insiden', 'checklists' => 'Checklist'] as $path => $label): ?>
            <a href="<?= site_url('master/' . $path) ?>" class="whitespace-nowrap rounded-lg px-3 py-2 font-medium <?= $resource === $path ? 'bg-orange-100 text-orange-700' : 'text-slate-500 hover:bg-slate-100' ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if (session('success')): ?><div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status"><?= esc(session('success')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= esc(session('error')) ?></div><?php endif; ?>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <?php if ($records === []): ?>
            <div class="px-6 py-14 text-center"><p class="font-semibold text-slate-700">Belum ada data <?= esc(strtolower($definition['title'])) ?>.</p><p class="mt-1 text-sm text-slate-500">Tambahkan data pertama untuk mulai menggunakannya.</p></div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><?php foreach ($definition['columns'] as $label): ?><th class="px-5 py-3 font-semibold"><?= esc($label) ?></th><?php endforeach; ?><th class="px-5 py-3 font-semibold">Status</th><th class="px-5 py-3"><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($records as $record): ?>
                        <tr class="text-slate-700">
                            <?php foreach ($definition['columns'] as $field => $label): ?><td class="max-w-xs px-5 py-4"><?= esc($record[$field] ?: '—') ?></td><?php endforeach; ?>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= (int) $record['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>"><?= (int) $record['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <?php if ($resource === 'checklists'): ?><a class="mr-3 font-semibold text-orange-600 hover:text-orange-700" href="<?= site_url('master/checklist-templates/' . $record['id'] . '/items') ?>">Item</a><?php endif; ?>
                                <a class="mr-3 font-semibold text-slate-600 hover:text-orange-700" href="<?= site_url('master/' . $resource . '/' . $record['id'] . '/edit') ?>">Ubah</a>
                                <?php if ((int) $record['is_active'] === 1): ?><form class="inline" method="post" action="<?= site_url('master/' . $resource . '/' . $record['id'] . '/deactivate') ?>" onsubmit="return confirm('Nonaktifkan data ini?');"><?= csrf_field() ?><button class="font-semibold text-red-600 hover:text-red-700">Nonaktifkan</button></form><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
