<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Storage;

test('the settings page shows the latest backup', function () {
    Storage::fake('local');
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('settings.edit'))->assertSee('No backups yet.');

    Storage::disk('local')->put('readererer/2026-11-20-03-17-00.zip', str_repeat('x', 2048));

    $this->actingAs($admin)->get(route('settings.edit'))->assertSee('Latest backup: 20 Nov 2026 03:17 (2 KB).');
});

test('a backup zips a copy of the SQLite database without the sqlite3 command', function () {
    Storage::fake('local');
    $database = tempnam(sys_get_temp_dir(), 'backup-test');
    $pdo = new PDO('sqlite:'.$database);
    $pdo->exec("CREATE TABLE pieces (name TEXT); INSERT INTO pieces VALUES ('Nimrod')");
    config(['database.connections.sqlite.database' => $database, 'backup.backup.source.databases' => ['sqlite']]);

    $this->artisan('backup:run', ['--disable-notifications' => true])->assertSuccessful();

    $zips = Storage::disk('local')->files('readererer');
    expect($zips)->toHaveCount(1);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($zips[0]));
    $dump = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->first(fn ($name) => str_ends_with($name, '.sqlite'));
    $copy = tempnam(sys_get_temp_dir(), 'backup-copy');
    file_put_contents($copy, $zip->getFromName($dump));

    expect(new PDO('sqlite:'.$copy)->query('SELECT name FROM pieces')->fetchColumn())->toBe('Nimrod');

    unlink($database);
    unlink($copy);
});

test('backups are scheduled at the backup time', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('backup:run')->expectsOutputToContain('backup:clean');
});
