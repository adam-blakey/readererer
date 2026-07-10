<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class GenerateVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-version
                            {--tag= : Record this tag instead of asking git}
                            {--hash= : Record this commit hash instead of asking git}
                            {--date= : Record this commit date instead of asking git}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write version.json from git metadata so the app never runs git at runtime (read by config/_version.php)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $version = [
            'tag' => $this->option('tag') ?? $this->git(['git', 'describe', '--tags']),
            'hash' => $this->option('hash') ?? $this->git(['git', 'log', '--pretty=%h', '-n1', 'HEAD']),
            'date' => $this->option('date') ?? $this->git(['git', 'log', '--pretty=%cI', '-n1', 'HEAD']),
        ];

        file_put_contents(
            base_path('version.json'),
            json_encode($version, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        $this->info(sprintf(
            'Wrote version.json (tag: %s, hash: %s).',
            $version['tag'] !== '' ? $version['tag'] : 'none',
            $version['hash'] !== '' ? $version['hash'] : 'none',
        ));

        return self::SUCCESS;
    }

    /**
     * Run a git command, returning its trimmed output or an empty string on
     * failure (no repository, no tags yet, git not installed) so builds in
     * bare environments still succeed and the config falls back to defaults.
     */
    private function git(array $command): string
    {
        $result = Process::path(base_path())->run($command);

        return $result->successful() ? trim($result->output()) : '';
    }
}
