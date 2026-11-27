<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('db:restore {path : SQL backup file path} {--force : Skip confirmation}')]
#[Description('Restore PostgreSQL database from a .sql backup file using psql')]
class DatabaseRestore extends Command
{
    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Restore akan menimpa data database saat ini. Lanjutkan?', false)) {
            $this->warn('Dibatalkan.');
            return self::INVALID;
        }

        $conn = config('database.connections.pgsql');
        $process = new Process([
            'psql',
            '--host=' . ($conn['host'] ?? '127.0.0.1'),
            '--port=' . ($conn['port'] ?? '5432'),
            '--username=' . ($conn['username'] ?? ''),
            '--no-password',
            '--dbname=' . ($conn['database'] ?? ''),
            '--file=' . $path,
        ], base_path(), ['PGPASSWORD' => (string) ($conn['password'] ?? '')]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'psql gagal. Pastikan psql terinstall dan ada di PATH.'));
            return self::FAILURE;
        }

        $this->info("Restore berhasil dari: {$path}");
        return self::SUCCESS;
    }
}
