# Plan Update: Async Retry Tanpa Fallback Message ke User

## Tujuan

Menghilangkan pengiriman pesan fallback seperti "Terjadi error. Silakan coba beberapa saat lagi." pada jalur async (Telegram/WhatsApp), dan menggantinya dengan mekanisme queue retry + failed jobs agar error bisa ditangani operasional tanpa memperburuk UX user.

## Ruang Lingkup

In scope:

- Channel async berbasis job: `telegram`, `whatsapp`.
- Job utama: `ProcessAiReply`.
- Service AI: `AIService`.
- Klasifikasi error kirim platform (khusus WAHA).
- Ketahanan payload untuk retry (anti message loss saat debounce).

Out of scope (fase awal):

- Perubahan arsitektur LiveChat synchronous menjadi async.
- Perubahan besar schema database di luar kebutuhan observability.

## Masalah Saat Ini

1. Exception di `AIService::reply()` ditangkap lalu diubah menjadi teks fallback user.
2. `ProcessAiReply` menerima string fallback itu sebagai success path.
3. Job tidak gagal, sehingga retry/failure pipeline queue tidak bekerja.
4. Pada flow debounce, pesan bisa hilang saat retry jika buffer sudah ter-clear sebelum job benar-benar sukses.

## Desain Solusi

### 1) Tambah mode "throw on failure" di AIService

Rencana:

- Tambah parameter pada `AIService::reply()`:
    - `bool $throwOnFailure = false`
- Bila `throwOnFailure = true`:
    - Exception tidak dikonversi menjadi fallback text.
    - Exception dilempar ulang (rethrow).
- Bila `throwOnFailure = false`:
    - Pertahankan perilaku lama (untuk jalur sync, misalnya LiveChat).

Dampak:

- Channel async bisa mengandalkan mekanisme queue native (tries/backoff/failed_jobs).

### 2) Ubah ProcessAiReply untuk mode strict async

Rencana:

- Panggil `AIService::reply(..., throwOnFailure: true)`.
- Jangan kirim fallback message ke user bila AI gagal.
- Biarkan exception bubble agar job ditandai gagal dan retry otomatis.

Dampak:

- Tidak ada lagi fallback chat yang menyesatkan user saat incident transient.

### 3) Error policy untuk transport send (WAHA)

Rencana klasifikasi:

- Retryable:
    - `429`, `500`, `502`, `503`, `504`, timeout/connection issue.
    - Action: throw exception agar retry.
- Non-retryable:
    - `400`, `401`, `403`, `404`, `422` (tergantung root-cause validasi permanen).
    - Action: fail-fast (tidak retry berulang), masuk failed_jobs untuk tindak lanjut.

Catatan penting:

- Untuk `422`, simpan ringkasan response body agar root-cause bisa dipisahkan antara:
    - invalid payload/chat id,
    - session state,
    - membership/permission issue.

### 4) Hardening debounce agar aman saat retry

Masalah:

- Payload bisa hilang jika buffer diambil lalu job gagal sebelum send sukses.

Rencana:

- Simpan snapshot batch message ke key "inflight" sebelum proses AI.
- Retry attempt membaca dulu dari key inflight.
- Hapus key inflight hanya setelah send sukses.

Dampak:

- Retry tidak kehilangan konten pesan user.

### 5) LiveChat tetap backward-compatible

Rencana:

- Jalur sync LiveChat tetap pakai mode default (`throwOnFailure = false`) pada fase ini.
- Alasan: endpoint webhook tetap harus merespons cepat dan konsisten.

## Rencana Implementasi Bertahap

### Fase 1 - Core behavior (wajib)

1. Tambah parameter `throwOnFailure` di `AIService::reply()`.
2. Ubah `ProcessAiReply` agar memanggil mode throw.
3. Pastikan exception pada AI path memicu retry queue.

Acceptance criteria Fase 1:

- Saat OpenAI timeout/transient error, user tidak menerima fallback text.
- Job di-retry sesuai `tries/backoff`.
- Jika tetap gagal, job masuk `failed_jobs`.

### Fase 2 - Transport policy (wajib)

1. Terapkan status-based classification untuk WAHA send/typing.
2. Retryable error melempar exception.
3. Non-retryable error fail-fast ke failed_jobs.

Acceptance criteria Fase 2:

- WAHA 5xx/429 memicu retry.
- WAHA 4xx permanen tidak loop retry tanpa akhir.
- Logs menyertakan informasi status + reason ringkas.

### Fase 3 - Debounce durability (sangat dianjurkan)

1. Tambah penyimpanan inflight snapshot.
2. Gunakan inflight snapshot pada retry.
3. Clear inflight hanya saat sukses kirim.

Acceptance criteria Fase 3:

- Simulasi crash di tengah proses tidak menyebabkan pesan hilang.
- Retry tetap memproses batch yang sama.

### Fase 4 - Operasional (wajib produksi)

1. SOP monitoring failed jobs.
2. SOP retry manual dan triage root-cause.
3. Dashboard/log query untuk top error reason.

Acceptance criteria Fase 4:

- Tim ops dapat mengidentifikasi root-cause dalam <= 10 menit.
- Tim ops dapat replay failed jobs dengan prosedur standar.

## File Target Perubahan

- `app/Services/AIService.php`
- `app/Jobs/ProcessAiReply.php`
- (opsional observability) `app/Support/ResilientHttp.php` atau logger util terkait
- (opsional docs) `README.md` / `DEPLOY.md` untuk SOP failed-jobs

## Konfigurasi Queue (sudah sesuai baseline)

- `QUEUE_CONNECTION=database`
- `DB_QUEUE_RETRY_AFTER=210`
- `REDIS_QUEUE_RETRY_AFTER=210`

Catatan:

- Nilai retry_after sudah lebih besar dari worker timeout 180 detik, sesuai best practice untuk mencegah double-processing.

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

2. Risiko: pesan user hilang saat retry karena debounce buffer.

- Mitigasi: implementasi inflight snapshot (Fase 3).

3. Risiko: 422 WAHA bercampur antara permanen dan transient.

- Mitigasi: log body reason, evaluasi policy 422 per sub-kasus.

## Strategy Testing

1. Unit/feature test AIService mode throw/non-throw.
2. Integration test job retry:

- Simulasikan exception pada AI call attempt-1.
- Verifikasi attempt-2 berjalan.
- Verifikasi fallback text tidak terkirim.

3. Integration test WAHA classification:

- Stub 500 -> retry.
- Stub 422 -> fail-fast.

4. Test debounce durability:

- Simulasikan crash setelah consume buffer.
- Verifikasi retry memakai inflight snapshot.

## Rollback Plan

Jika terjadi dampak tidak diinginkan:

1. Disable mode throw pada jalur async (kembali ke fallback text sementara).
2. Pertahankan logging yang ditambah untuk investigasi.
3. Lakukan hotfix terarah pada policy status code yang bermasalah.

## Definition of Done

Semua kondisi berikut harus terpenuhi:

1. Tidak ada fallback message "Terjadi error..." terkirim ke user pada channel async.
2. Error AI/transient masuk retry queue otomatis.
3. Kegagalan persisten tercatat di `failed_jobs` dan bisa direplay.
4. Tidak ada kehilangan payload user pada retry scenario.
5. Tim ops memiliki SOP jelas untuk monitor dan replay failed jobs.
