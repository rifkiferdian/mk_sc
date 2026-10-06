<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-2xl">
    <a href="<?= site_url('master/' . $resource) ?>" class="text-sm font-semibold text-orange-600 hover:text-orange-700">← Kembali ke <?= esc(strtolower($definition['title'])) ?></a>
    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900"><?= $record === [] ? 'Tambah' : 'Ubah' ?> <?= esc($definition['singular']) ?></h1>
    <p class="mt-2 text-sm text-slate-500">Lengkapi informasi di bawah. Kolom bertanda wajib harus diisi.</p>
    <?php if (session('error')): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= esc(session('error')) ?></div><?php endif; ?>
    <form class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" method="post" action="<?= $record === [] ? site_url('master/' . $resource) : site_url('master/' . $resource . '/' . $record['id']) ?>">
        <?= csrf_field() ?>
        <div class="space-y-5">
            <?php foreach ($definition['fields'] as $name => $field): $value = old($name, $record[$name] ?? ''); $required = str_contains($field['rules'], 'required'); ?>
                <div>
                    <label for="<?= esc($name) ?>" class="mb-2 block text-sm font-semibold text-slate-700"><?= esc($field['label']) ?><?php if ($required): ?> <span class="text-orange-600">*</span><?php endif; ?></label>
                    <?php if ($field['type'] === 'textarea'): ?>
                        <textarea id="<?= esc($name) ?>" name="<?= esc($name) ?>" rows="3" class="block w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-100" <?= $required ? 'required' : '' ?>><?= esc($value) ?></textarea>
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select id="<?= esc($name) ?>" name="<?= esc($name) ?>" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-100" required>
                            <option value="">Pilih <?= strtolower($field['label']) ?></option>
                            <?php foreach ($options[$name] as $optionValue => $optionLabel): ?><option value="<?= esc($optionValue) ?>" <?= (string) $value === (string) $optionValue ? 'selected' : '' ?>><?= esc($optionLabel) ?></option><?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input id="<?= esc($name) ?>" name="<?= esc($name) ?>" type="<?= esc($field['type']) ?>" value="<?= esc($value) ?>" class="block w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-100" <?= $required ? 'required' : '' ?>>
                    <?php endif; ?>
                    <?php if (session('errors.' . $name)): ?><p class="mt-2 text-sm text-red-600"><?= esc(session('errors.' . $name)) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-7 flex justify-end gap-3"><a href="<?= site_url('master/' . $resource) ?>" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</a><button type="submit" class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-bold text-white hover:bg-orange-600">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>
