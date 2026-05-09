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

    public function handle(): void
    {
        $dbConnection = config('database.default');
        $filename = 'parishhub-backup-' . now()->format('Y-m-d-His') . '.sql.gz';
        $backupPath = storage_path('app/public/backups/' . $filename);

        // Ensure backups directory exists
        if (!Storage::disk('public')->exists('backups')) {
            Storage::disk('public')->makeDirectory('backups');
        }

        try {
            if ($dbConnection === 'sqlite') {
                $this->backupSQLite($backupPath);
            } else {
                $this->backupMySQL($backupPath);
            }

            // Keep only last 8 backups
            $this->cleanupOldBackups();

            // Log completion to audit logs
            AuditLog::create([
                'action' => 'database_backup',
                'user_id' => 1, // System user
                'auditable_type' => 'Database',
                'auditable_id' => null,
                'old_values' => [],
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
            $host,
            $username,
            $password,
            $database,
            $backupPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('MySQL dump failed with code: ' . $returnCode);
        }
    }

    protected function cleanupOldBackups(): void
    {
        $files = Storage::disk('public')->files('backups');
        $backups = collect($files)->sortByDesc(function ($file) {
            return Storage::disk('public')->lastModified($file);
        });

        // Keep only last 8 backups
        $backups->slice(8)->each(function ($file) {
            Storage::disk('public')->delete($file);
        });
    }
}
