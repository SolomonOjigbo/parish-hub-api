<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Backups are written to the PRIVATE local disk (storage/app/backups)
     * and served only through the authenticated download endpoint —
     * never from public storage.
     */
    public function handle(): void
    {
        $dbConnection = config('database.default');
        $filename = 'parishhub-backup-' . now()->format('Y-m-d-His') . ($dbConnection === 'sqlite' ? '.sqlite' : '.sql.gz');

        if (!Storage::disk('local')->exists('backups')) {
            Storage::disk('local')->makeDirectory('backups');
        }

        $backupPath = Storage::disk('local')->path('backups/' . $filename);

        try {
            if ($dbConnection === 'sqlite') {
                $this->backupSQLite($backupPath);
            } else {
                $this->backupMySQL($backupPath);
            }

            $this->cleanupOldBackups();

            AuditLog::create([
                'action' => 'database_backup',
                'user_id' => null, // system-initiated
                'auditable_type' => 'Database',
                'auditable_id' => 0,
                'old_values' => null,
                'new_values' => ['filename' => $filename],
            ]);

            Log::info('Database backup completed successfully', ['filename' => $filename]);
        } catch (\Exception $e) {
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function backupSQLite(string $backupPath): void
    {
        $databasePath = config('database.connections.sqlite.database');
        copy($databasePath, $backupPath);
    }

    protected function backupMySQL(string $backupPath): void
    {
        $host = config('database.connections.mysql.host');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('MySQL dump failed with code: ' . $returnCode);
        }
    }

    protected function cleanupOldBackups(): void
    {
        $files = Storage::disk('local')->files('backups');
        $backups = collect($files)->sortByDesc(
            fn($file) => Storage::disk('local')->lastModified($file)
        );

        // Keep only the last 8 backups
        $backups->slice(8)->each(function ($file) {
            Storage::disk('local')->delete($file);
        });
    }
}
