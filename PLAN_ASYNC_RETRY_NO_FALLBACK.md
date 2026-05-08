# Plan Update: Async Retry Tanpa Fallback Message ke User

## Tujuan

Menghilangkan pengiriman fallback message ke user pada jalur async (`telegram` dan `whatsapp`) dengan mengalihkan kegagalan ke mekanisme queue retry dan `failed_jobs`, sehingga UX tetap bersih dan incident ditangani operasional.

## Ruang Lingkup

In scope:

- Channel async berbasis job: `telegram`, `whatsapp`.
- Job utama: `ProcessAiReply`.
- Service AI: `AIService`.
- Policy kegagalan transport (khusus WAHA dan send channel).
- Ketahanan payload agar retry tidak kehilangan konteks pesan.
- Observability minimum untuk triage error.

Out of scope (fase awal):

- Migrasi LiveChat synchronous menjadi async.
- Redesign besar arsitektur queue.
- Perubahan schema besar di luar kebutuhan observability dasar.

## Masalah Saat Ini

1. Exception di `AIService::reply()` ditangkap dan diubah menjadi fallback text.
2. `ProcessAiReply` menganggap fallback text itu sebagai success path.
3. Job tidak gagal, sehingga retry/failure pipeline queue tidak aktif.
4. Pada jalur debounce, payload berisiko hilang jika buffer sudah dikonsumsi sebelum proses benar-benar sukses.
5. Pada kegagalan transport send, error cenderung hanya tercatat log dan tidak selalu dipromosikan menjadi failure queue-level.
6. Klasifikasi status `422` belum cukup tegas antara kasus transient vs permanen.

## Prinsip Desain

1. Tidak mengirim fallback message ke user untuk channel async.
2. Retry hanya untuk error transient; error permanen fail-fast.
3. Pertahankan backward compatibility untuk jalur sync.
4. Prioritaskan solusi sederhana yang aman terhadap message loss.
5. Semua keputusan retry/fail harus observable lewat log dan failed jobs.

## Desain Solusi

### 1) Mode `throwOnFailure` pada AIService

Rencana:

- Tambah parameter pada `AIService::reply()`:
    - `bool $throwOnFailure = false`
- Bila `throwOnFailure = true`:
    - Exception tidak dikonversi menjadi fallback text.
    - Exception dilempar ulang agar queue bisa retry/fail.
- Bila `throwOnFailure = false`:
    - Pertahankan perilaku lama untuk jalur sync (compatibility).

Catatan:

- Tetap klasifikasikan error di layer job agar retry hanya untuk error yang memang transient.

### 2) ProcessAiReply mode strict async

Rencana:

- Panggil `AIService::reply(..., throwOnFailure: true)`.
- Jangan kirim fallback message ke user saat AI gagal.
- Klasifikasikan exception:
    - Retryable: timeout, connection issue, rate-limit, upstream 5xx.
    - Non-retryable: konfigurasi invalid, request invalid permanen.
- Retryable dilempar ulang agar queue retry berjalan.
- Non-retryable dicatat jelas untuk triage dan dibiarkan fail sesuai policy.

### 3) Policy transport send (WAHA dan channel send)

Tujuan:

- Menjamin kegagalan kirim message ikut masuk lifecycle retry/fail di queue.

Rencana klasifikasi:

- Retryable:
    - `429`, `500`, `502`, `503`, `504`, timeout/connection issue.
    - Action: throw exception agar job di-retry.
- Non-retryable:
    - `400`, `401`, `403`, `404`.
    - Action: fail-fast (hindari retry berulang).
- Ambiguous (`422`): - Action: evaluasi response body ringkas untuk menentukan transient/permanen. - Rule klasifikasi `422` (disarankan): 1. Mengandung `invalid chat id`, `invalid payload`, `malformed request` -> permanen (fail-fast). 2. Mengandung `session not ready`, `session reconnecting`, `temporary unavailable` -> transient (retry). 3. Mengandung `unauthorized api key`, `forbidden` -> permanen (fail-fast). 4. Reason tidak dikenali -> default transient untuk 1 retry tambahan, lalu fail-fast bila reason sama berulang.

Catatan implementasi:

- Pastikan status failure transport tidak berhenti di log saja; harus dipromosikan ke keputusan retry/fail queue-level.
- Untuk `422`, log minimal: `status`, `endpoint`, `reason_summary` (dipotong), dan `decision` (retry/fail-fast).

### 4) Ketahanan payload retry (debounce-safe)

Pendekatan utama (lebih sederhana):

- Gunakan payload job (`combinedText`) sebagai sumber utama retry agar tidak tergantung buffer yang sudah ter-clear.
- Buffer hanya dipakai saat memang job dipicu dengan payload kosong.

Pendekatan opsional (lanjutan, jika masih ada gap):

- Tambahkan snapshot inflight key sebelum AI process.
- Retry membaca inflight dulu.
- Clear inflight hanya saat send sukses.

### 5) Backward compatibility jalur sync

Rencana:

- LiveChat tetap menggunakan default `throwOnFailure = false` pada fase ini.
- Tidak ada perubahan kontrak perilaku jalur sync.

## Rencana Implementasi Bertahap

### Fase 1 - Core Async Failure Semantics (wajib)

1. Tambah parameter `throwOnFailure` di `AIService::reply()`.
2. Ubah `ProcessAiReply` agar mode async memanggil throw mode.
3. Pastikan exception retryable benar-benar membuat job gagal dan masuk retry pipeline.

Acceptance criteria Fase 1:

- OpenAI transient failure tidak mengirim fallback message ke user async.
- Job retry berjalan sesuai `tries/backoff`.
- Jika tetap gagal, job tercatat di `failed_jobs`.

### Fase 2 - Transport Error Policy (wajib)

1. Terapkan klasifikasi status-based di jalur send.
2. Retryable transport failure melempar exception.
3. Non-retryable transport failure fail-fast.
4. `422` diklasifikasi berbasis reason ringkas dari body response.

Acceptance criteria Fase 2:

- WAHA `5xx/429` memicu retry queue-level.
- WAHA `4xx` permanen tidak loop retry.
- Log menyertakan status + reason ringkas untuk triage.

### Fase 3 - Payload Durability (direkomendasikan)

1. Finalisasi strategi anti message loss (payload-first atau inflight snapshot).
2. Uji crash di tengah proses dan verifikasi payload tetap tersedia untuk retry.

Acceptance criteria Fase 3:

- Tidak ada message loss pada skenario retry/crash yang diuji.
- Retry memproses batch pesan yang sama secara konsisten.

### Fase 4 - Operasional & Runbook (wajib produksi)

1. SOP monitoring `failed_jobs`.
2. SOP retry manual dan triage root-cause.
3. Dashboard/query top error reasons per channel.
4. Alert numerik dan SLA respon incident.

Acceptance criteria Fase 4:

- Root-cause utama dapat diidentifikasi cepat saat incident.
- Tim ops dapat replay failed jobs dengan prosedur standar.
- Alert aktif dengan threshold numerik yang disepakati.

Threshold alert (rekomendasi awal):

- Warning: `failed jobs >= 20` dalam 10 menit.
- High: `failed jobs >= 50` dalam 10 menit.
- Critical: `failed jobs >= 100` dalam 10 menit atau success rate `< 95%` selama 15 menit.

SLA triage (rekomendasi awal):

- Warning: respons awal <= 30 menit.
- High: respons awal <= 15 menit.
- Critical: respons awal <= 5 menit (on-call).

## File Target Perubahan

- `app/Services/AIService.php`
- `app/Jobs/ProcessAiReply.php`
- `app/Support/ResilientHttp.php` (jika dibutuhkan untuk policy transport)
- `README.md` atau `DEPLOY.md` (opsional, untuk runbook operasional)

## Konfigurasi Queue (baseline)

- `QUEUE_CONNECTION=database`
- `DB_QUEUE_RETRY_AFTER=210`
- `REDIS_QUEUE_RETRY_AFTER=210`

Catatan:

- `retry_after` harus tetap lebih besar dari worker timeout agar menghindari double processing.

## Operasional Command (runbook)

Lihat failed jobs:

- `php artisan queue:failed`

Retry satu job:

- `php artisan queue:retry <uuid>`

Retry semua failed:

- `php artisan queue:retry all`

Hapus failed jobs lama (hati-hati):

- `php artisan queue:flush`

## Risiko dan Mitigasi

1. Risiko: volume failed jobs naik saat incident upstream.

- Mitigasi: alerting + SOP retry + klasifikasi retryable/non-retryable.

2. Risiko: message loss saat retry karena debit buffer.

- Mitigasi: payload-first retry strategy; gunakan inflight snapshot hanya jika masih diperlukan.

3. Risiko: `422` bercampur antara permanen dan transient.

- Mitigasi: logging reason ringkas dari body dan policy klasifikasi per sub-kasus.

4. Risiko: retry berulang untuk error permanen.

- Mitigasi: fail-fast policy untuk `4xx` permanen + observability reason code.

## Strategy Testing

1. Unit/feature test `AIService` mode throw vs non-throw.

2. Integration test retry AI path:

- Simulasikan exception pada attempt-1.
- Verifikasi attempt berikutnya berjalan.
- Verifikasi fallback text tidak terkirim ke user async.

3. Integration test transport classification:

- Stub `500` atau timeout -> retry.
- Stub `400/401/403/404` -> fail-fast.
- Stub `422` dengan body berbeda -> klasifikasi sesuai policy.

4. Integration test gabungan AI + transport:

- Attempt-1 AI transient fail, attempt-2 AI success, send success -> user hanya menerima 1 balasan final tanpa fallback.
- AI success, WAHA send gagal `500` lalu success -> retry queue-level berjalan, tanpa fallback ke user.
- AI success, WAHA send gagal `422` permanen -> fail-fast, masuk `failed_jobs`, tidak loop retry.
- AI permanent fail -> send tidak dieksekusi, reason tercatat jelas untuk triage.

5. Test payload durability:

- Simulasikan crash setelah buffer consume / sebelum send sukses.
- Verifikasi retry tetap punya payload yang sama.

## Rollback Plan

Jika terjadi dampak tidak diinginkan:

1. Nonaktifkan throw mode di jalur async (sementara kembali ke fallback behavior lama).
2. Pertahankan logging tambahan untuk investigasi.
3. Hotfix terarah pada policy status code/klasifikasi exception.

## Definition of Done

Semua kondisi berikut terpenuhi:

1. Tidak ada fallback message "Terjadi error..." terkirim ke user pada channel async.
2. Error transient AI/transport masuk retry queue otomatis.
3. Kegagalan persisten tercatat di `failed_jobs` dan bisa direplay.
4. Tidak ada kehilangan payload user pada skenario retry yang diuji.
5. Tim ops memiliki SOP jelas untuk monitor, klasifikasi, dan replay failed jobs.
