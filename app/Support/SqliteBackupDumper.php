<?php

namespace App\Support;

use PDO;
use Spatie\DbDumper\Databases\Sqlite;

/**
 * Copies the database with VACUUM INTO rather than the sqlite3 command, which the host's disabled proc_open cannot run.
 */
class SqliteBackupDumper extends Sqlite
{
    public function dumpToFile(string $dumpFile): void
    {
        new PDO('sqlite:'.$this->dbName)->prepare('VACUUM INTO ?')->execute([$dumpFile]);
    }
}
