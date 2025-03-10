<?php

declare(strict_types=1);

namespace App\Enums;

enum DatabaseEnum: string
{
    case SQLite = 'sqlite';
    case Mysql = 'mysql';
    case Mariadb = 'mariadb';
    case Postgres = 'pgsql';

    public function getLatestVersion(): string
    {
        return match ($this) {
            self::Mariadb => 'mariadb:11.4',
            self::Mysql => 'mysql:8.0',
            self::Postgres => 'postgres:17',
            self::SQLite => 'sqlite',
        };
    }

    public function getDefaultPort(): int
    {
        return match ($this) {
            self::Postgres => 5432,
            self::Mysql, self::Mariadb => 3306,
            self::SQLite => 0,
        };
    }
}
