<?php

namespace Database\Seeders;

use App\Models\ForbiddenBehaviour;
use Illuminate\Database\Seeder;

class ForbiddenBehaviourSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'title' => 'Dilarang membuat data player tanpa konfirmasi',
                'instruction' => 'AI dilarang membuat atau mendaftarkan data player baru jika belum ada konfirmasi eksplisit dari player. Selalu tanyakan dan pastikan player benar-benar ingin mendaftar sebelum menjalankan proses registrasi.',
                'level' => 'danger',
                'is_active' => true,
            ],
            [
                'title' => 'Dilarang membuat dummy player',
                'instruction' => 'AI dilarang membuat data player dummy, palsu, atau contoh dalam kondisi apapun. Semua data player yang dibuat harus berasal dari informasi asli yang diberikan oleh player.',
                'level' => 'danger',
                'is_active' => true,
            ],
            [
                'title' => 'Dilarang menghapus data database',
                'instruction' => 'AI dilarang menghapus data apapun dari database. Tidak boleh melakukan delete, truncate, atau operasi penghapusan data dalam bentuk apapun.',
                'level' => 'danger',
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            ForbiddenBehaviour::query()->updateOrCreate(
                ['title' => $rule['title']],
                $rule
            );
        }
    }
}
