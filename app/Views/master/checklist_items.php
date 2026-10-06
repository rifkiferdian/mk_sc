<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-4xl">
    <a href="<?= site_url('master/checklists') ?>" class="text-sm font-semibold text-orange-600 hover:text-orange-700">← Kembali ke template checklist</a>
    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900"><?= esc($template['name']) ?> <span class="text-slate-400">v<?= esc($template['version']) ?></span></h1>
    <p class="mt-2 text-sm text-slate-500">Item template bersifat versioned. Untuk perubahan besar, buat template dengan versi baru agar riwayat laporan tetap konsisten.</p>
    <?php if (session('success')): ?><div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= esc(session('success')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= esc(session('error')) ?></div><?php endif; ?>
    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2" method="post" action="<?= site_url('master/checklist-templates/' . $template['id'] . '/items') ?>">
            <?= csrf_field() ?><h2 class="font-bold text-slate-900">Tambah item</h2>
            <div class="mt-4 space-y-4">
                <div><label class="mb-1 block text-sm font-semibold">Bagian</label><select name="phase" class="w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="opening">Awal shift</option><option value="closing">Akhir shift</option></select></div>
                <div><label class="mb-1 block text-sm font-semibold">Urutan</label><input name="sort_order" type="number" min="1" value="<?= esc(old('sort_order')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" required></div>
                <div><label class="mb-1 block text-sm font-semibold">Item</label><textarea name="item_text" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" required><?= esc(old('item_text')) ?></textarea></div>
                <label class="flex gap-2 text-sm"><input type="checkbox" name="is_required" value="1" checked> Wajib diisi</label><label class="flex gap-2 text-sm"><input type="checkbox" name="allows_na" value="1"> Boleh N/A</label>
                <button class="w-full rounded-xl bg-orange-500 px-4 py-3 text-sm font-bold text-white hover:bg-orange-600">Tambah item</button>
            </div>
        </form>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-3"><div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold">Daftar item</h2></div>
            <?php if ($items === []): ?><p class="p-6 text-sm text-slate-500">Belum ada item checklist.</p><?php else: ?><ol class="divide-y divide-slate-100"><?php foreach ($items as $item): ?><li class="flex gap-4 p-5"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700"><?= esc($item['sort_order']) ?></span><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?= $item['phase'] === 'opening' ? 'Awal shift' : 'Akhir shift' ?></p><p class="mt-1 text-sm text-slate-700"><?= esc($item['item_text']) ?></p><p class="mt-2 text-xs text-slate-500"><?= (int) $item['is_required'] ? 'Wajib' : 'Opsional' ?> · <?= (int) $item['allows_na'] ? 'N/A diizinkan' : 'N/A tidak diizinkan' ?></p></div></li><?php endforeach; ?></ol><?php endif; ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
