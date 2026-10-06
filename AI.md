# AI.md — Aplikasi Laporan Monitoring CCTV Manna Kampus

## 1. Instruksi untuk AI developer

Bangun aplikasi web operasional yang siap digunakan, bukan sekadar mockup. Gunakan bahasa Indonesia pada antarmuka. Ikuti dokumen ini sebagai sumber kebutuhan produk. Kerjakan bertahap dengan migration, seed, implementasi, dan pengujian. Jangan mengganti stack tanpa persetujuan pengguna. Jika repository sudah berisi kode, pelajari dahulu dan pertahankan pola yang layak.

Stack wajib: Golang, Tailwind CSS melalui CLI, MySQL. Gunakan monolit server-side rendering dengan Gin, html/template, GORM dan driver MySQL. JavaScript vanilla untuk interaksi kecil. Tidak menggunakan SPA, React, Vite, Bootstrap atau Tailwind CDN. Node.js hanya diperlukan saat build aset. Gunakan versi dependensi stabil yang kompatibel; pin melalui go.mod/go.sum dan package-lock.json. Server produksi melayani CSS hasil build dan tidak memerlukan npm watch.

Implementasikan ACL berbasis permission dengan konsep role, permission, multi-role dan direct user permission seperti Spatie Laravel Permission. Ini implementasi Go sendiri, bukan pemasangan package PHP Spatie. Pemeriksaan permission harus di backend dan template.

## 2. Tujuan dan batas cakupan

Digitalisasi laporan harian petugas monitoring CCTV: checklist awal, monitoring rutin, insiden, checklist akhir, serah terima dua akun, verifikasi supervisor, rekap dan audit. Mendukung banyak toko dan petugas. Satu laporan adalah satu sesi kerja petugas pada satu toko/shift. Beberapa petugas boleh bertugas bersamaan; masing-masing mempunyai laporan sendiri. Satu petugas hanya memiliki satu sesi aktif pada saat yang sama.

Versi awal tidak mengintegrasikan live stream, facial recognition, WhatsApp, absensi HR atau perekaman CCTV otomatis. Rekaman lengkap tetap berada di DVR/NVR. Aplikasi menyimpan bukti ringan dan referensi rekaman. Tidak memakai status kamera hasil input manual sebagai klaim telemetri langsung.

## 3. Konvensi teknis

- UI dan PDF berbahasa Indonesia; identifiers kode/database berbahasa Inggris.
- Waktu database disimpan UTC; semua tampilan/jadwal menggunakan Asia/Jakarta.
- business_date mengikuti tanggal mulai shift lokal, termasuk shift lintas tengah malam.
- MySQL InnoDB, utf8mb4; foreign key, unique constraints dan index eksplisit.
- Migration SQL versioned menggunakan golang-migrate. Jangan mengandalkan AutoMigrate produksi.
- Handler menangani HTTP, service menangani aturan bisnis/transaksi, repository menangani query.
- Gunakan context, timeout HTTP/database, connection pooling terkonfigurasi dan graceful shutdown.
- Gunakan transaction untuk workflow, audit terkait dan notifikasi; tidak membuat goroutine bebas untuk penulisan penting.
- Config melalui environment; sertakan .env.example tanpa rahasia dan README setup.
- Log terstruktur, request ID dan pesan error pengguna yang aman.

Struktur awal:

```text
cmd/server/main.go
cmd/seed/main.go
internal/config/
internal/database/
internal/auth/
internal/acl/
internal/middleware/
internal/modules/{users,masters,reports,monitoring,incidents,handovers,reviews,faults,attachments,notifications,audit}/
internal/jobs/
internal/shared/
migrations/
web/templates/{layouts,partials,pages}/
web/assets/css/input.css
web/assets/js/
web/static/css/app.css
storage/private/
.env.example
package.json
README.md
```

## 4. Autentikasi

Login akun internal dengan username dan password; tidak ada registrasi publik. Password di-hash bcrypt. Sesi server-side tersimpan di MySQL, token acak kriptografis dengan hash di database. Cookie HttpOnly, SameSite=Lax, Secure saat HTTPS. Rotasi sesi ketika login, kedaluwarsa, logout menghapus sesi. CSRF untuk seluruh mutasi, termasuk upload dan logout. Rate limit login. User nonaktif langsung kehilangan akses. Jangan mengirim password/hash/token ke audit atau log.

Seed super-admin dibuat hanya melalui konfigurasi/command, tidak menggunakan password publik bawaan. Perubahan password atau deaktivasi akun mencabut sesi sesuai kebijakan. Menu profil hanya mengubah data profil yang diizinkan, bukan role atau penempatan toko.

## 5. ACL dan cakupan data

### 5.1 Tabel ACL

- roles: id, name UNIQUE, display_name, description, is_system, timestamps.
- permissions: id, name UNIQUE, module, display_name, description, timestamps.
- user_roles: user_id, role_id; composite primary/unique key.
- role_permissions: role_id, permission_id; composite primary/unique key.
- user_permissions: user_id, permission_id; composite primary/unique key.
- user_stores: user_id, store_id; composite primary/unique key.

Permission efektif adalah gabungan permission seluruh role dan direct permission user. Default deny. Direct permission hanya menambah hak; tidak ada deny override pada MVP. Pemeriksaan izin adalah exact match, bukan pencocokan prefix. Role bukan pengganti permission untuk otorisasi fitur.

Sediakan Can(user, permission), HasRole, RequirePermission, RequireAnyPermission dan template helper can. Super-admin dapat melewati pemeriksaan permission/cakupan toko, tetapi tetap tunduk aturan workflow, identitas penerima, larangan menerima serah terima sendiri dan larangan verifikasi laporan sendiri. Bypass tidak berarti dapat memalsukan konfirmasi orang lain.

Permission, penempatan toko, kepemilikan/penugasan objek dan status workflow semuanya harus lolos. Query list, detail, statistik, ekspor dan download bukti wajib memakai cakupan yang sama. Jangan mengambil semua data lalu memfilter di browser. User dengan stores.view tidak otomatis mendapat akses semua toko. Akses lintas toko eksplisit melalui stores.view_all. Tidak adanya penempatan toko berarti akses operasional kosong.

Perubahan role/direct permission/deaktivasi harus berlaku pada request berikutnya. MVP membaca izin setiap request; bila cache ditambahkan, wajib ada invalidasi konsisten untuk user terkait.

### 5.2 Permission seed

```text
dashboard.view
stores.view_all
users.view users.create users.update users.deactivate users.assign_roles users.assign_stores
roles.view roles.create roles.update roles.delete roles.assign_permissions
permissions.view users.assign_permissions
stores.view stores.create stores.update stores.deactivate
areas.view areas.create areas.update areas.deactivate
cameras.view cameras.create cameras.update cameras.deactivate
shifts.view shifts.create shifts.update shifts.deactivate
checklists.view checklists.create checklists.update checklists.deactivate
incident_types.view incident_types.create incident_types.update incident_types.deactivate
reports.view_own reports.view_store reports.view_all reports.create reports.update_own reports.submit reports.export
monitoring.create monitoring.update_own
incidents.view incidents.create incidents.update incidents.assign incidents.resolve incidents.export
attachments.upload attachments.view attachments.download
handovers.create handovers.view handovers.accept handovers.request_revision handovers.reassign
reviews.view reviews.verify reviews.request_revision
faults.view faults.create faults.update faults.assign faults.resolve
statistics.view statistics.export
audit.view
settings.view settings.update
```

### 5.3 Role default dan menu

| Role | Hak default | Menu |
|---|---|---|
| super-admin | Seluruh permission dan cakupan seluruh toko | Semua menu |
| admin | Manajemen user, ACL, master dan konfigurasi; akses operasional hanya jika diberikan | Pengguna, Role & Permission, Master, Pengaturan |
| cctv-staff | Dashboard, laporan sendiri, membuat/edit laporan sendiri, submit, monitoring, membuat insiden, membaca insiden terkait shift, upload/view bukti terkait, membuat/membaca/menerima serah terima, meminta perbaikan, membuat/membaca gangguan terkait | Dashboard Shift, Laporan Saya, Monitoring, Insiden, Serah Terima, Gangguan |
| supervisor | Dashboard, laporan toko, membaca insiden/bukti toko, tindak lanjut/assign/resolve insiden, review/verify/request revision, handover view/reassign, gangguan dan rekap toko | Dashboard Toko, Laporan, Review, Insiden, Serah Terima, Gangguan, Rekap |
| security-manager | Dashboard dan laporan seluruh toko, stores.view_all, insiden/gangguan view, lampiran view/download, statistik/ekspor, handover view | Dashboard Semua Toko, Laporan, Insiden, Gangguan, Rekap |
| security-responder | Opsional: dashboard, melihat insiden yang ditugaskan, update tindak lanjut, resolve sesuai penugasan, bukti terkait | Tugas Insiden |
| it-technician | Opsional: dashboard, melihat/update/resolve gangguan yang ditugaskan | Tugas Gangguan |

Seed harus menghasilkan mapping permission eksplisit sesuai tabel ini; jangan memberikan semua permission kepada admin atau supervisor. reports.view_all tetap memerlukan stores.view_all untuk melampaui user_stores. Manager tidak mendapat verify secara default. cctv-staff tidak mendapat incidents.assign/resolve secara default; tindakan awal dicatat saat create, tindak lanjut berikutnya sesuai penugasan. User boleh punya beberapa role. Semua role dapat dikelola lewat UI; lindungi super-admin terakhir agar tidak terhapus/dinonaktifkan atau kehilangan role.

Role biasa tidak boleh menaikkan hak sendiri, memberikan role super-admin, atau memberikan hak yang lebih tinggi dari otoritas administrasinya. Hanya super-admin dapat mengubah system role, memberi super-admin, stores.view_all dan permission administrasi ACL. Catalog permission berasal dari seed/kode; UI menampilkan dan mengassign, tidak membuat nama permission yang tidak dikenali handler.

## 6. Master data

- Toko: kode unik, nama, alamat, status aktif.
- Area: toko, nama, prioritas; kasir/POS, pintu masuk/keluar, gudang, rak bernilai tinggi, parkir, loading dock, koridor luar toilet/ruang ganti. Jangan membuat area kamera di ruang privat toilet/ruang ganti.
- Kamera: kode unik per toko, area, label, referensi channel DVR/NVR, status master aktif. Kondisi aktual berdasarkan observasi terpisah.
- Shift: toko, nama, jam mulai/selesai lokal, interval monitoring default 120 menit; dapat diatur 60/120 menit.
- Jenis insiden: pencurian, perilaku mencurigakan, kecelakaan/jatuh, kerusakan properti, keributan, dugaan fraud internal, lainnya.
- Template checklist: versioned, item, urutan, awal/akhir, wajib, izin N/A. Perubahan master tidak mengubah laporan lama.

Master yang sudah dipakai dinonaktifkan, bukan dihapus. Penempatan petugas/supervisor per toko melalui user_stores. Supervisor laporan dipilih dari user aktif berwenang pada toko yang sama.

## 7. Laporan shift dan checklist

Identitas: nomor laporan unik, petugas, toko/lokasi monitoring, supervisor, shift, business_date, planned_start/end dan actual_start/end. Snapshot nama shift, jam, interval, template dan nama identitas untuk riwayat. Actual times server-generated; waktu kejadian adalah input terpisah.

State laporan: draft -> submitted -> verified atau revision_requested -> submitted. verified read-only. Akhir kerja dan status review adalah dua konsep terpisah: ended_at tidak otomatis berarti verified. Laporan yang selesai boleh diperbaiki hanya saat revision_requested; simpan revisi dan audit.

Mulai shift membuat laporan, snapshot checklist dan slot monitoring dalam transaksi. Cegah sesi aktif ganda untuk user melalui row lock user dan pengecekan dalam transaksi. Shift 22.00–06.00 menghasilkan end tanggal berikutnya. Slot dimulai planned_start, bertambah interval, dengan slot terakhir sebelum planned_end; tidak melewati akhir shift. Jangan mengubah slot historis setelah master shift berubah.

Checklist awal wajib:
1. Monitor berfungsi normal.
2. Jumlah kamera aktif sesuai standar; input aktif/total.
3. Gambar jelas, tidak blur/terhalang.
4. Recording normal.
5. Alarm berfungsi.
6. HT/telepon berfungsi.
7. Log sebelumnya dibaca.
8. Area prioritas terpantau.

Checklist akhir wajib:
1. Rekaman tersimpan baik.
2. Clipping insiden dilakukan bila ada.
3. Laporan insiden diserahkan ke supervisor bila ada.
4. Peralatan kondisi baik.
5. Log lengkap.
6. Serah terima dilakukan.

Ya/Tidak, keterangan, catatan awal/akhir. Jawaban Tidak wajib keterangan. N/A hanya pada item yang mengizinkannya; clipping/penyerahan insiden dapat N/A saat nihil. active <= total dan angka nonnegatif. Item serah terima akhir berasal dari sistem: Ya setelah diterima, belum dilakukan saat pending; jangan memaksa petugas mengklaim selesai sebelum penerima menerima.

Submit memerlukan checklist awal/akhir lengkap, monitoring lengkap atau slot terlewat diberi alasan, catatan akhir, konfirmasi nihil bila tidak ada insiden, dan handover sudah dikirim minimal pending. Item akhir serah terima boleh pending secara eksplisit; supervisor hanya dapat verify setelah handover accepted, atau pengecualian non-handover resmi dengan alasan melalui supervisor berwenang. Pengecualian tidak pernah mencatat tanda tangan penerima fiktif.

## 8. Monitoring rutin

Setiap slot memiliki scheduled_at, actual_observed_at, submitted_at dan status pending/completed/missed/excused. Input satu atau beberapa area; kondisi normal/has_finding per area, deskripsi dan tindakan. has_finding mewajibkan uraian. Temuan rutin tidak semuanya insiden; tombol Buat Insiden menghubungkan log tanpa duplikasi.

Tampilkan jadwal dan reminder, bukan otomatis mengisi normal. Petugas boleh input terlambat dengan alasan; simpan timestamp asli. Jam observasi tidak boleh di masa depan atau di luar sesi aktual tanpa alasan koreksi yang diotorisasi. Simpan history perubahan. Duplicate submission slot/area ditolak dengan unique constraint. Tampilan tidak menganggap area yang belum diisi sebagai normal. Area prioritas yang belum tercakup ditandai; supervisor dapat melihat kelengkapan.

## 9. Insiden dan tindak lanjut

Insiden dapat dibuat kapan saja pada shift aktif, termasuk ketika checklist belum selesai. Fields: nomor unik, toko, report asal, log asal opsional, occurred_at, reported_at server, area, kamera opsional, jenis, tingkat prioritas, deskripsi, ciri orang terkait opsional, tindakan awal, assignee opsional, first_response_at, status dan resolution_note.

Ciri orang terkait: jenis kelamin/perkiraan umur/pakaian/ciri khusus opsional; gunakan istilah dugaan pada kejadian belum terkonfirmasi. Aksi: melapor supervisor, menghubungi security lantai, menyimpan clipping, menghubungi kepolisian, lainnya. Ini pencatatan tindakan, tidak otomatis menghubungi pihak luar.

State: new -> in_progress -> forwarded atau resolved; forwarded dapat kembali in_progress/resolved. Resolve memerlukan catatan penyelesaian dan permission + cakupan/penugasan. Reopen hanya supervisor dengan alasan dan audit. Response duration dihitung dari occurred_at ke first_response_at bila data valid; bedakan dari waktu input dan tampilkan kosong bila belum diketahui.

incident_updates append-only: actor, waktu, tindakan, catatan, perubahan status/penugasan, lampiran. Insiden diteruskan antarshift memakai nomor yang sama. Perubahan insiden setelah laporan diverifikasi tetap diperbolehkan sesuai izin dan tercatat sebagai timeline terpisah, tidak menulis ulang snapshot laporan terverifikasi.

## 10. Serah terima dua akun

Fields: source_report_id, sender_user_id, receiver_user_id, target_report_id nullable, summary, sent_at, received_at nullable, receiver_note, status, revision_reason, revision_number. Sertakan daftar insiden/gangguan yang diteruskan dan snapshot catatan.

State: draft -> pending_acceptance -> accepted; pending_acceptance -> revision_requested -> pending_acceptance. Tidak menerima handover sendiri.

Alur:
1. Pengirim menyelesaikan checklist akhir/catatan, memilih penerima aktif di toko sama, membaca ringkasan insiden/gangguan, dan mencentang pernyataan penyerahan.
2. Serahkan Tugas menyimpan nama/account/time server dan notifikasi penerima dalam transaksi. Akhir shift dapat ditandai; laporan tetap dapat disubmit sambil handover pending.
3. Penerima login sendiri, membaca detail, mengisi catatan opsional dan memilih Terima Tugas atau Minta Perbaikan (alasan wajib).
4. Hanya receiver yang tersimpan boleh accept/request revision. Permission saja tidak cukup. Jika account receiver nonaktif, harus reassign resmi.
5. Accept mencatat receiver account dan waktu server. Pengirim/supervisor/super-admin tidak boleh mengonfirmasi atas nama penerima.
6. Penerima dapat mulai shift dan membaca backlog sambil meminta perbaikan; handover kemudian ditautkan ke laporan penerima di toko sama. Penerimaan tidak otomatis berarti checklist awal sudah selesai.
7. Jika target report belum dibuat, accepted handover tetap valid; tautkan secara eksplisit saat mulai shift berikutnya. Validasi penerima pemilik target report, toko sama, urutan waktu masuk akal. Jangan menghubungkan otomatis hanya karena tanggal sama.
8. Supervisor dengan handovers.reassign dapat mengganti penerima sebelum accepted; alasan wajib, histori receiver dipertahankan, notifikasi penerima lama dibatalkan dan penerima baru diberi notifikasi. Setelah accepted tidak boleh reassign.

Versi awal tanda tangan berupa konfirmasi terautentikasi akun; PDF menulis “Dikonfirmasi melalui akun [nama] pada [waktu]”. Jangan mengklaim tanda tangan digital tersertifikasi. Gambar tanda tangan canvas opsional tahap berikutnya, bukan syarat MVP. Jam serah dan terima boleh berbeda; tidak dibuat sama secara paksa. Revision setelah pengiriman membatalkan versi pending lama dan meminta konfirmasi ulang versi terbaru. Data accepted read-only.

## 11. Verifikasi supervisor

Review hanya oleh user berpermission dan cakupan toko laporan; tidak boleh review/verify laporan sendiri. Supervisor membaca enam bagian, daftar ketidaknormalan, slot terlambat, lampiran, dan status handover. Bisa Request Revision (alasan wajib) atau Verify (catatan opsional). Verifikasi mencatat akun, timestamp server dan versi laporan. Tidak ada persetujuan otomatis hanya karena login supervisor.

Setelah verified, laporan terkunci; koreksi melalui revisi baru yang terhubung laporan asal dan harus diverifikasi kembali, tidak overwrite versi lama. MVP dapat membatasi koreksi verified menjadi pencatatan amendment oleh supervisor dengan audit dan versi; pilih satu mekanisme konsisten dan dokumentasikan.

## 12. Gangguan CCTV

Tiket: toko, kamera/peralatan, reporter, opened_at, uraian, prioritas, assignee, status open/in_progress/resolved, resolution_note, resolved_at. Checklist Tidak dapat menawarkan pembuatan tiket, bukan otomatis membuat duplikat setiap shift. Tampilkan tiket aktif kamera sama sebelum create. Gangguan ikut serah terima hingga selesai. Hanya technician assigned atau supervisor toko berizin boleh menangani/resolve.

## 13. Lampiran dan referensi rekaman

MVP upload JPEG/PNG/PDF, maksimal 10 MiB/file dan batas total/request terkonfigurasi. Validasi actual MIME/content, ukuran dan filename; nama storage random. Larang HTML/SVG/executable. Simpan di storage private di luar static root; download melalui endpoint ACL dan cek kepemilikan objek/toko/penugasan. Gunakan Content-Disposition aman dan nosniff. Metadata berisi path internal, original_name, mime, size, hash, uploader dan waktu. Jangan mengirim path server ke browser.

Referensi clipping: kamera/channel, start_at/end_at, recorder_name dan recording_reference tekstual. Tidak membuka path file pengguna langsung, tidak fetch URL bebas. Video upload opsional tahap lanjut dengan quota. Lampiran yang dirujuk laporan verified tidak dapat dihapus biasa; gunakan kebijakan retensi dan audit terpisah.

## 14. Database minimum

Setiap tabel transaksi memiliki primary key, timestamps UTC dan foreign key yang tepat. Gunakan row_version pada laporan/handover untuk optimistic concurrency. Soft delete hanya bila diperlukan; audit/timeline tidak dihapus lewat CRUD biasa.

| Tabel | Isi utama |
|---|---|
| users | username unik, nama, password_hash, is_active |
| roles, permissions, user_roles, role_permissions, user_permissions | ACL |
| user_stores | Cakupan toko |
| sessions | token_hash unik, user, expires_at |
| stores, areas, cameras, shift_templates, incident_types | Master |
| checklist_templates, checklist_template_items | Template versioned |
| shift_reports | Identitas/snapshot shift, status, start/end, row_version |
| report_checklist_items | Snapshot item, answer, note, answered_by/at |
| monitoring_slots | report, scheduled_at unik per report, status, reason |
| monitoring_logs | slot, area, condition, observed_at, submitted_at, notes/action; unique slot+area |
| incidents | Identitas, toko, origin report/log, waktu, jenis, status, assignee |
| incident_updates | Timeline append-only |
| incident_actions | Daftar tindakan awal/lanjutan |
| equipment_faults, fault_updates | Tiket gangguan/timeline |
| handovers | source report, receiver, target report, status, times, version |
| handover_incidents, handover_faults | Link item yang diteruskan |
| handover_events | Kirim, revisi, reassign, accept beserta actor/versi |
| report_reviews, report_revisions | Review dan snapshot revisi |
| attachments | Metadata file privat; gunakan FK eksplisit ke report/incident/fault/update sesuai konteks |
| notifications | user, jenis, reference, read_at; dedup key unik |
| audit_logs | actor, action, entity, before/after teredaksi, request ID, timestamp |
| number_sequences | Nomor per toko/periode dengan lock transaksi |
| app_settings | Config nonrahasia terkontrol |

Index pada store_id+tanggal/status, report_id, assigned_to+status, receiver_id+status, notification user+read_at dan audit entity+timestamp. Nomor contoh CCTV-[KODETOKO]-[YYYYMMDD]-[SEQ]; generator memakai transaksi/unique index, bukan MAX()+1 tanpa lock.

Satu handover aktif per source report dijamin unique source_report_id; revisi melalui events/version, bukan membuat row aktif ganda. Foreign key kamera/area/toko harus divalidasi konsistensinya di service. Constraints untuk pivot mencegah duplikasi assignment.

## 15. Halaman dan desain

Tema default orange-putih Manna Kampus dengan teks slate, indikator status yang jelas, responsive desktop/HP. Jangan membuat logo palsu; sediakan tempat logo konfigurasi. Layout sidebar desktop, drawer mobile, topbar user/toko aktif dan bell. Pemilihan toko hanya dari cakupan akun.

Halaman wajib: login; dashboard per role; daftar/detail/form laporan; checklist awal/akhir; monitoring; daftar/detail/form insiden dan timeline; daftar/detail/konfirmasi handover; queue review/detail; daftar/detail gangguan; master CRUD; pengguna dan role/permission matrix; riwayat audit; rekap dan print.

Form panjang dibagi tab/step: Identitas, Awal Shift, Monitoring, Insiden, Akhir Shift, Serah Terima, Review. Simpan draft per bagian dan tampilkan status tersimpan. Jangan kehilangan input saat error validasi. Tombol sensitif menggunakan confirm dialog. Loading, empty, forbidden, error dan success states harus tersedia. Keyboard navigation, label, focus, kontras dan status dengan teks selain warna.

Dashboard petugas: shift aktif, jadwal selanjutnya, slot belum diisi, tombol tambah log/lapor insiden/selesai shift, handover masuk. Supervisor: laporan pending, shift aktif, monitoring terlewat, insiden/gangguan terbuka. Manager: filter toko/periode, tren insiden/area dan kelengkapan laporan. Jangan memakai angka dummy dalam dashboard produksi.

## 16. Tailwind CLI

Gunakan CLI npm terpisah:

```bash
npm install -D tailwindcss @tailwindcss/cli
npx @tailwindcss/cli -i ./web/assets/css/input.css -o ./web/static/css/app.css --watch
npx @tailwindcss/cli -i ./web/assets/css/input.css -o ./web/static/css/app.css --minify
```

Isi input.css:

```css
@import "tailwindcss";
@source "../../templates";
@source "../js";
```

Tambahkan npm scripts css:watch/css:build. Jalur @source relatif terhadap input.css. Gunakan class lengkap yang terdeteksi scanner; jangan menghasilkan nama seperti bg-{{.Color}}-500. Mapping status menggunakan string class literal lengkap pada template/JS atau explicit source declarations. Uji CSS hasil produksi, bukan hanya watch. CSS print dapat memakai aturan @media print untuk laporan A4 hemat tinta.

Rujukan: https://tailwindcss.com/docs/installation/tailwind-cli

## 17. Route dan authorization

SSR GET/POST; mutations tidak melalui GET. Setiap route memiliki ACL dan service policy. Route contoh:

```text
GET/POST /login; POST /logout
GET /dashboard
GET /reports; GET /reports/:id
POST /reports/start
POST /reports/:id/checklists
POST /reports/:id/monitoring
POST /reports/:id/end; POST /reports/:id/submit
GET/POST /incidents; GET /incidents/:id
POST /incidents/:id/updates; POST /incidents/:id/assign; POST /incidents/:id/resolve
POST /reports/:id/handover
GET /handovers; GET /handovers/:id
POST /handovers/:id/send; POST /handovers/:id/accept
POST /handovers/:id/request-revision; POST /handovers/:id/reassign
GET /reviews
POST /reports/:id/verify; POST /reports/:id/request-revision
GET/POST /faults; POST /faults/:id/updates
POST /attachments; GET /attachments/:id/download
GET /statistics; GET /exports/reports
GET /reports/:id/print; GET /reports/:id/pdf
GET /admin/users; GET /admin/roles; GET /admin/permissions
GET /audit
```

Return 401/login redirect untuk unauthenticated, 403 untuk hak fitur, 404 untuk objek di luar cakupan agar tidak membocorkan keberadaan objek, 409 untuk versi/state konflik, validation errors jelas. Gunakan Post/Redirect/Get agar refresh tidak menggandakan transaksi. Bind input DTO yang eksplisit; actor/user/store/status server-owned tidak boleh diambil dari hidden input tanpa validasi.

## 18. Notifikasi, rekap, ekspor dan audit

Notifikasi internal untuk handover masuk, revisi, laporan pending review, insiden prioritas tinggi, dan gangguan ditugaskan. Job reminder menggunakan MySQL untuk dedup; aman ketika beberapa instance berjalan. Tidak membuat automation di ChatGPT. Tidak mengirim email/WhatsApp/polisi otomatis.

Rekap filter toko, periode, petugas, shift, area, jenis/status insiden. Statistik insiden memakai distinct incident_id, tidak menghitung ulang ketika diteruskan shift. Kelengkapan monitoring dihitung dari slot wajib; excused dan terlambat ditampilkan terpisah. Sediakan ekspor XLSX dengan cell teks aman dari formula injection.

PDF laporan mengikuti enam bagian form sumber dengan nama, waktu konfirmasi, catatan supervisor, nomor/versi laporan. Gunakan template print yang sama dan renderer Chromium headless yang terkonfigurasi; worker concurrency terbatas, timeout, tanpa fetch URL input pengguna. Jangan bergantung pada Microsoft Word COM. Print browser tetap tersedia jika renderer belum terpasang; jangan menyebut HTML sebagai file PDF.

Audit wajib untuk login event yang relevan, CRUD ACL/master, submit/review, perubahan data/revisi, insiden/penugasan, handover, ekspor dan download bukti. Tidak log isi file/password/token. Snapshot audit sensitif diringkas/redaksi. Access audit terbatas dan tidak ada tombol edit/delete audit.

## 19. Integritas dan pengujian

- Handovers accept/reassign/request revision dan verify harus menggunakan transaction + row lock atau compare-and-swap version/status.
- Double click/retry tidak menghasilkan penerimaan, nomor, notif atau review ganda.
- Jangan menerima versi handover lama setelah reassign/revisi; kirim expected_version dan validasi server.
- Penulisan file gagal tidak meninggalkan metadata sukses; penulisan database gagal membersihkan file yatim dengan aman.
- Pagination/filter server-side; SQL parameterized dan whitelist sort.
- Backup MySQL dan storage privat bersamaan; dokumentasikan restore dan retensi yang dapat dikonfigurasi. Jangan menghapus laporan/bukti otomatis tanpa kebijakan eksplisit.

Pengujian wajib, gunakan MySQL test instance untuk transaksi/constraint:
1. Permission role + direct permission union; multi-role, default deny, perubahan role langsung berlaku.
2. Akses antar toko ditolak pada list/detail/export/download, termasuk manipulasi URL.
3. Petugas tidak mengubah laporan orang lain atau field actor/state lewat request.
4. Penerimaan handover hanya receiver; self-accept ditolak; reassign/revisi race aman.
5. Supervisor tidak verify laporan sendiri; verified tidak dapat diubah biasa.
6. Shift 22.00–06.00 menghasilkan tanggal dan slot benar, sesi user ganda dicegah.
7. Slot kosong tidak dianggap normal; nihil tidak dipakai jika ada insiden asal.
8. Insiden forwarded mempertahankan ID dan statistik tidak terduplikasi.
9. Upload berbahaya/terlalu besar ditolak; file privat tidak dapat diakses lewat static URL.
10. CSRF, sesi expired/nonaktif, rate limit dan privilege escalation ACL diuji.
11. UI mobile/desktop dan print/PDF dibaca untuk memastikan enam bagian tidak terpotong.

Jalankan gofmt, go vet ./..., go test ./..., dan npm run css:build. Lakukan smoke test dengan akun petugas, penerima, supervisor, manager dan admin. Jangan melaporkan pengujian sukses jika belum dijalankan.

## 20. Urutan implementasi dan definisi selesai

1. Fondasi proyek, config, migration, Tailwind CLI, layout dan auth.
2. ACL lengkap + UI role/permission, users dan cakupan toko; seed idempotent.
3. Master, mulai/akhir shift, snapshot checklist dan monitoring.
4. Insiden, gangguan, bukti privat dan timeline.
5. Handover dua akun, notifikasi dan review supervisor.
6. Dashboard data nyata, rekap XLSX, print/PDF, audit, pengujian dan dokumentasi deploy.

Setiap tahap harus berfungsi sebelum tahap berikutnya. Seed demo hanya untuk development dan dipisahkan dari seed ACL produksi. Seed tidak menimpa role/permission pilihan admin setiap startup; perubahan katalog permission melalui migration/command eksplisit.

Selesai bila semua halaman bekerja dengan MySQL, permission diterapkan di server, laporan enam bagian lengkap, handover dua akun tidak dapat dipalsukan, history insiden berlanjut, ekspor mengikuti cakupan, CSS build mandiri, pengujian penting lulus, serta README memuat setup lokal Windows/Linux, migration/seed, build, environment, menjalankan binary, reverse proxy/HTTPS dan backup/restore. Jangan menambah modul lain di luar cakupan hanya untuk memperbesar aplikasi.
