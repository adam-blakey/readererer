<?php

namespace App\Console\Commands;

use App\Models\Ensemble;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:prune
                            {--hours=24 : Leave files alone until they are at least this old}
                            {--dry-run : Report what would be deleted without deleting anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete uploaded images that no record points at any more';

    /**
     * Models whose `image` column can hold an uploaded file (see App\Traits\HasImage).
     *
     * @var array<int, class-string>
     */
    protected array $models = [
        Ensemble::class,
        User::class,
    ];

    /**
     * Execute the console command.
     *
     * Uploads are never deleted at the point of replacement — a failed save
     * would otherwise take the only copy of an image with it — so this is the
     * one place files go away. A file survives while any record still names it
     * (soft-deleted records included, since restoring one brings its image
     * back) or while it is younger than the grace period, which keeps a file
     * uploaded moments ago from being swept before its row is written.
     */
    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dry_run = (bool) $this->option('dry-run');
        $cutoff = now()->subHours(max(0, (int) $this->option('hours')))->getTimestamp();

        $deleted = 0;
        $kept = 0;

        foreach ($this->models as $model) {
            $in_use = array_flip(
                $model::withTrashed()->whereNotNull('image')->pluck('image')->all()
            );

            foreach ($disk->files($model::imageDirectory()) as $file) {
                if (isset($in_use[$file]) || $disk->lastModified($file) > $cutoff) {
                    $kept++;

                    continue;
                }

                $this->line($dry_run ? "Would delete {$file}" : "Deleting {$file}");

                if (! $dry_run) {
                    $disk->delete($file);
                }

                $deleted++;
            }
        }

        $this->info($dry_run
            ? "{$deleted} unused image(s) would be deleted, {$kept} kept."
            : "{$deleted} unused image(s) deleted, {$kept} kept.");

        return self::SUCCESS;
    }
}
