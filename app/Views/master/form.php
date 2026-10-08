<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-2xl">
    <a href="<?= site_url('master/' . $resource) ?>" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-50"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12"/></svg>Kembali ke <?= esc(strtolower($definition['title'])) ?></a>
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
        <div class="mt-7 flex justify-end gap-3"><a href="<?= site_url('master/' . $resource) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m18 6-12 12M6 6l12 12"/></svg>Batal</a><button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-orange-600 hover:shadow-md"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h12l3 3v13H4V4h1Zm3 0v6h8V4M8 20v-7h8v7"/></svg>Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>
