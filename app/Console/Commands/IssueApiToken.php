<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Terbitkan (atau terbitkan ulang) token API Sanctum untuk aplikasi klien
 * (CBT, Perpustakaan Digital, dsb) yang akan memanggil routes/api.php.
 *
 * Token diikat ke sebuah "service user" khusus (bukan akun admin manusia)
 * supaya tidak bisa dipakai login lewat form biasa (account_status dibuat
 * bukan 'active') dan mudah dicabut/diaudit terpisah dari akun staf.
 *
 * Pemakaian:
 *   php artisan api:token cbt
 *   php artisan api:token perpus
 *   php artisan api:token cbt --abilities=datacenter.read,datacenter.auth
 */
class IssueApiToken extends Command
{
    protected $signature = 'api:token {client : Nama klien, mis. cbt / perpus}
        {--abilities=datacenter.read,datacenter.auth : Daftar ability, pisahkan dengan koma}
        {--fresh : Cabut semua token lama klien ini sebelum menerbitkan yang baru}';

    protected $description = 'Terbitkan token API Sanctum untuk aplikasi klien (CBT/Perpus) yang memanggil API Data Center';

    public function handle(): int
    {
        $client = Str::slug((string) $this->argument('client'));
        $abilities = array_filter(array_map('trim', explode(',', (string) $this->option('abilities'))));

        $email = "svc-{$client}@datacenter.local";

        $serviceUser = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Service Client: '.strtoupper($client),
                'password' => Str::random(40), // tidak pernah dipakai untuk login form biasa
                'role' => 'service',
                'account_status' => 'service', // bukan 'active' -> AuthController akan menolak login form biasa
                'is_aktif' => false,
            ]
        );

        if ($this->option('fresh')) {
            $serviceUser->tokens()->delete();
        }

        $token = $serviceUser->createToken($client, $abilities);

        $this->newLine();
        $this->info("Token API untuk klien \"{$client}\" berhasil dibuat.");
        $this->line('Ability: '.implode(', ', $abilities));
        $this->newLine();
        $this->warn('SIMPAN token ini sekarang — tidak akan ditampilkan lagi:');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line("Tempel ke .env aplikasi {$client} sebagai:");
        $this->line('DATACENTER_API_URL=http://datacenter.test/api');
        $this->line('DATACENTER_API_TOKEN='.$token->plainTextToken);

        return self::SUCCESS;
    }
}
