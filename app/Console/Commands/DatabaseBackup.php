<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

#[Signature('db:backup {--path= : Output .sql file path}')]
#[Description('Backup PostgreSQL database using pg_dump')]
class DatabaseBackup extends Command
{
    public function handle(): int
    {
        $conn = config('database.connections.pgsql');
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $file = $this->option('path') ?: $dir . DIRECTORY_SEPARATOR . 'pos_servis_' . now()->format('Ymd_His') . '.sql';

        $process = new Process([
            'pg_dump',
            '--host=' . ($conn['host'] ?? '127.0.0.1'),
            '--port=' . ($conn['port'] ?? '5432'),
            '--username=' . ($conn['username'] ?? ''),
            '--no-password',
            '--format=plain',
            '--file=' . $file,
            $conn['database'] ?? '',
        ], base_path(), ['PGPASSWORD' => (string) ($conn['password'] ?? '')]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'pg_dump gagal. Pastikan pg_dump terinstall dan ada di PATH.'));
            return self::FAILURE;
        }

        $this->info("Backup berhasil: {$file}");
        return self::SUCCESS;
    }
}
