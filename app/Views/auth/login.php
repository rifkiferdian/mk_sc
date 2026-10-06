<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<main class="grid min-h-screen lg:grid-cols-2">
    <section class="hidden bg-orange-500 p-12 lg:flex lg:flex-col lg:justify-between">
        <div class="flex items-center gap-3 text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 font-bold">MK</span>
            <div>
                <p class="font-bold">Manna Kampus</p>
                <p class="text-sm text-orange-100">Monitoring CCTV</p>
            </div>
        </div>
        <div class="max-w-md text-white">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-100">Operasional keamanan</p>
            <h1 class="mt-4 text-4xl font-bold leading-tight">Pantau dengan lebih terstruktur.</h1>
            <p class="mt-5 leading-7 text-orange-50">Kelola laporan shift, temuan, dan serah terima monitoring dalam satu tempat.</p>
        </div>
        <p class="text-sm text-orange-100">© <?= date('Y') ?> Manna Kampus</p>
    </section>

    <section class="flex items-center justify-center px-5 py-10 sm:px-10">
        <div class="w-full max-w-md">
            <div class="mb-10 lg:hidden">
                <div class="flex items-center gap-3 text-slate-900">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 font-bold text-white">MK</span>
                    <div><p class="font-bold">Manna Kampus</p><p class="text-sm text-slate-500">Monitoring CCTV</p></div>
                </div>
            </div>
            <p class="text-sm font-semibold text-orange-600">SELAMAT DATANG</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Masuk ke akun Anda</h2>
            <p class="mt-3 text-sm leading-6 text-slate-500">Gunakan akun internal yang sudah diberikan oleh administrator.</p>

            <?php if (session('error')): ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= esc(session('error')) ?></div>
            <?php endif; ?>
            <?php if (session('success')): ?>
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status"><?= esc(session('success')) ?></div>
            <?php endif; ?>

            <form class="mt-7 space-y-5" action="<?= site_url('login') ?>" method="post">
                <?= csrf_field() ?>
                <div>
                    <label for="identity" class="mb-2 block text-sm font-semibold text-slate-700">Username atau email</label>
                    <input id="identity" name="identity" type="text" value="<?= esc(old('identity')) ?>" autocomplete="username" required autofocus class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-100" placeholder="Masukkan username atau email">
                    <?php if (session('errors.identity')): ?><p class="mt-2 text-sm text-red-600"><?= esc(session('errors.identity')) ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-orange-500 focus:ring-4 focus:ring-orange-100" placeholder="Masukkan kata sandi">
                    <?php if (session('errors.password')): ?><p class="mt-2 text-sm text-red-600"><?= esc(session('errors.password')) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="w-full rounded-xl bg-orange-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-orange-600 focus:outline-none focus:ring-4 focus:ring-orange-200">Masuk</button>
            </form>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
