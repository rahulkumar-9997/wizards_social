<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Database extends Component
{
    public $tables = [];
    public $selectedTables = [];

    public function mount()
    {
        $database = env('DB_DATABASE');
        $tables = DB::select('SHOW TABLES');

        $this->tables = array_map(function ($table) use ($database) {
            return $table->{'Tables_in_' . $database};
        }, $tables);
    }

    public function truncateSelected()
    {
        if (empty($this->selectedTables)) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'No tables selected for truncation.'
            ]);
            return;
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($this->selectedTables as $table) {
                if (!in_array($table, $this->protectedTables())) {
                    DB::table($table)->truncate();
                }
            }
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => 'Selected tables truncated successfully.'
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Error truncating tables: ' . $e->getMessage()
            ]);
        }
    }

    public function backupDatabase()
    {
        try {
            $backupFileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backupDir = storage_path('app/backups');
            $backupPath = $backupDir . '/' . $backupFileName;

            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');
            $host = env('DB_HOST');
            $port = env('DB_PORT');

            $mysqldumpPath = exec('where mysqldump');
            if (!$mysqldumpPath) {
                // Local fallback
                $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            }

            if (!file_exists($mysqldumpPath)) {
                throw new \Exception('mysqldump command not found.');
            }

            $command = "\"$mysqldumpPath\" --user=$username --password=$password --host=$host --port=$port $database > \"$backupPath\"";
            exec($command . ' 2>&1', $output, $result);

            Log::info('Backup Output: ' . implode("\n", $output));

            if ($result === 0) {
                return response()->download($backupPath, $backupFileName)->deleteFileAfterSend(true);
            } else {
                throw new \Exception('Backup failed. ' . implode("\n", $output));
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Backup failed: ' . $e->getMessage()
            ]);
        }
    }

    private function protectedTables()
    {
        return [
            'users', 'migrations', 'menu_permission', 'menu_role', 'menus',
            'model_has_permissions', 'model_has_roles', 'permissions',
            'role_has_permissions', 'roles', 'order_status'
        ];
    }

    public function render()
    {
        return view('livewire.backend.database')
        ->layout('backend.pages.layouts.master', [
            'title' => 'Database Management',
        ]);
    }
}
