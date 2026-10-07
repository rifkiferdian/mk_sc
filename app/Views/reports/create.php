<?= $this->extend('layouts/dashboard') ?>
<?= $this->section('content') ?>
<div class="mx-auto max-w-2xl"><a href="<?= site_url('reports') ?>" class="text-sm font-semibold text-orange-600">← Kembali</a><h1 class="mt-5 text-2xl font-bold text-slate-900">Mulai Laporan Shift</h1><p class="mt-2 text-sm text-slate-500">Identitas laporan diambil dari master data. Waktu mulai dicatat oleh sistem.</p><?php if (session('error')): ?><div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('reports') ?>" class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6"><?= csrf_field() ?>
<div><label class="mb-2 block text-sm font-semibold">Toko</label><select name="store_id" class="w-full rounded-xl border border-slate-300 p-3" required><option value="">Pilih toko</option><?php foreach ($stores as $store): ?><option value="<?= $store['id'] ?>" <?= old('store_id') == $store['id'] ? 'selected' : '' ?>><?= esc($store['code'] . ' — ' . $store['name']) ?></option><?php endforeach; ?></select></div>
<div><label class="mb-2 block text-sm font-semibold">Shift</label><select id="shift_template_id" name="shift_template_id" class="w-full rounded-xl border border-slate-300 p-3 disabled:cursor-not-allowed disabled:bg-slate-100" required disabled><option value="">Pilih toko terlebih dahulu</option><?php foreach ($shifts as $shift): ?><option value="<?= $shift['id'] ?>" data-store-id="<?= $shift['store_id'] ?>" <?= old('shift_template_id') == $shift['id'] ? 'selected' : '' ?>><?= esc($shift['name'] . ' (' . substr($shift['start_time'], 0, 5) . '–' . substr($shift['end_time'], 0, 5) . ')') ?></option><?php endforeach; ?></select><p class="mt-2 text-xs text-slate-500">Daftar shift otomatis disesuaikan dengan toko yang dipilih.</p></div>
<div><label class="mb-2 block text-sm font-semibold">Template checklist</label><select name="checklist_template_id" class="w-full rounded-xl border border-slate-300 p-3" required><?php foreach ($templates as $template): ?><option value="<?= $template['id'] ?>"><?= esc($template['name'] . ' v' . $template['version']) ?></option><?php endforeach; ?></select></div>
<div><label class="mb-2 block text-sm font-semibold">Supervisor <span class="font-normal text-slate-400">(opsional)</span></label><select name="supervisor_id" class="w-full rounded-xl border border-slate-300 p-3"><option value="">Belum ditentukan</option><?php foreach ($supervisors as $supervisor): ?><option value="<?= $supervisor['id'] ?>"><?= esc($supervisor['name'] . ' (' . $supervisor['username'] . ')') ?></option><?php endforeach; ?></select></div>
<button class="w-full rounded-xl bg-orange-500 p-3 text-sm font-bold text-white hover:bg-orange-600">Buat draft laporan</button></form></div>
<script>
const storeSelect = document.querySelector('select[name="store_id"]');
const shiftSelect = document.querySelector('#shift_template_id');
const shiftOptions = [...shiftSelect.options];
function filterShifts() {
    const storeId = storeSelect.value;
    shiftSelect.disabled = !storeId;
    shiftOptions.forEach((option, index) => {
        if (index === 0) return;
        option.hidden = option.dataset.storeId !== storeId;
        option.disabled = option.dataset.storeId !== storeId;
    });
    if (!storeId || shiftSelect.selectedOptions[0]?.dataset.storeId !== storeId) shiftSelect.selectedIndex = 0;
    shiftOptions[0].text = storeId ? 'Pilih shift' : 'Pilih toko terlebih dahulu';
}
storeSelect.addEventListener('change', filterShifts);
filterShifts();
</script>
<?= $this->endSection() ?>
