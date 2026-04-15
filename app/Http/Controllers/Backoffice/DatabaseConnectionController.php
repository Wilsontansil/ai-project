<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DatabaseConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DatabaseConnectionController extends Controller
{
    public function index(): View
    {
        $connections = DatabaseConnection::query()->orderBy('name')->get();

        return view('backoffice.database-connections.index', [
            'connections' => $connections,
            'boActive' => 'database-connections',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.database-connections.create', [
            'boActive' => 'database-connections',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_\-]+$/', 'unique:database_connections,name'],
            'driver' => ['required', 'string', 'in:mysql,pgsql,sqlite'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'],
        ]);

        DatabaseConnection::create([
            'name' => trim($data['name']),
            'driver' => $data['driver'],
            'host' => trim($data['host']),
            'port' => (int) $data['port'],
            'database' => trim($data['database']),
            'username' => trim($data['username']),
            'password' => $data['password'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.database-connections.index')->with('success', 'Database connection berhasil ditambahkan.');
    }

    public function edit(DatabaseConnection $databaseConnection): View
    {
        return view('backoffice.database-connections.edit', [
            'connection' => $databaseConnection,
            'boActive' => 'database-connections',
        ]);
    }

    public function update(Request $request, DatabaseConnection $databaseConnection): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_\-]+$/', 'unique:database_connections,name,' . $databaseConnection->id],
            'driver' => ['required', 'string', 'in:mysql,pgsql,sqlite'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'],
        ]);

        $updateData = [
            'name' => trim($data['name']),
            'driver' => $data['driver'],
            'host' => trim($data['host']),
            'port' => (int) $data['port'],
            'database' => trim($data['database']),
            'username' => trim($data['username']),
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $databaseConnection->update($updateData);

        return redirect()->route('backoffice.database-connections.index')->with('success', 'Database connection berhasil diperbarui.');
    }

    public function destroy(DatabaseConnection $databaseConnection): RedirectResponse
    {
        $databaseConnection->delete();

        return redirect()->route('backoffice.database-connections.index')->with('success', 'Database connection berhasil dihapus.');
    }

    public function testConnection(DatabaseConnection $databaseConnection): RedirectResponse
    {
        try {
            $config = [
                'driver' => $databaseConnection->driver,
                'host' => $databaseConnection->host,
                'port' => $databaseConnection->port,
                'database' => $databaseConnection->database,
                'username' => $databaseConnection->username,
                'password' => $databaseConnection->decrypted_password ?? '',
            ];

            if ($databaseConnection->driver === 'pgsql') {
                $config['charset'] = 'utf8';
                $config['prefix'] = '';
                $config['prefix_indexes'] = true;
                $config['search_path'] = 'public';
                $config['sslmode'] = 'prefer';
                $config['options'] = [
                    \PDO::ATTR_TIMEOUT => 5,
                ];
            } else {
                $config['charset'] = 'utf8mb4';
                $config['collation'] = 'utf8mb4_unicode_ci';
                $config['options'] = [
                    \PDO::ATTR_TIMEOUT => 5,
                ];
            }

            config(["database.connections._test_conn" => $config]);
            DB::connection('_test_conn')->getPdo();
            DB::purge('_test_conn');

            return redirect()->back()->with('success', 'Koneksi berhasil! Database "' . $databaseConnection->database . '" terhubung.');
        } catch (\Throwable $e) {
            DB::purge('_test_conn');

            return redirect()->back()->with('error', 'Koneksi gagal: ' . $e->getMessage());
        }
    }
}
