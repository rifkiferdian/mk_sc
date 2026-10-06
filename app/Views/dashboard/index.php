<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="max-w-5xl">
    <p class="text-sm font-semibold text-orange-600">DASHBOARD</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Selamat datang, <?= esc($user['name']) ?>.</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">Ringkasan operasional akan tampil di sini setelah data toko, shift, dan laporan tersedia.</p>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Ringkasan awal">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Role aktif</p>
            <p class="mt-3 text-lg font-bold text-slate-900"><?= $roles === [] ? 'Belum ada role' : esc(implode(', ', $roles)) ?></p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Shift aktif</p>
            <p class="mt-3 text-lg font-bold text-slate-900">Belum ada data</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Notifikasi</p>
            <p class="mt-3 text-lg font-bold text-slate-900">Belum ada data</p>
        </article>
    </section>

    <section class="mt-6 rounded-2xl border border-dashed border-orange-200 bg-orange-50 p-6">
        <h2 class="text-base font-bold text-slate-900">Mulai konfigurasi operasional</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Tambahkan master toko, area, kamera, dan shift terlebih dahulu. Setelah modul tersebut tersedia, dashboard akan menampilkan status shift, jadwal monitoring, serta laporan yang benar-benar berasal dari database.</p>
    </section>
</div>
<?= $this->endSection() ?>
