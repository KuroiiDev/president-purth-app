<?php

namespace App\Console\Commands;

use App\Services\ImageUploader;
use Illuminate\Console\Command;

class PurgeArchiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:purge {--days=90 : Hapus file arsip lebih tua dari N hari}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus file gambar arsip yang lebih tua dari N hari';

    /**
     * Execute the console command.
     */
    public function handle(ImageUploader $imageUploader): int
    {
        $days = (int) $this->option('days');

        $count = $imageUploader->purgeArchive($days);

        $this->info("Berhasil menghapus {$count} file arsip yang lebih tua dari {$days} hari.");

        return self::SUCCESS;
    }
}
