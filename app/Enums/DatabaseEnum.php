<?php

declare(strict_types=1);

namespace App\Enums;

enum DatabaseEnum: string
{
    case SQLite = 'sqlite';
    case Mysql = 'mysql';
    case Mariadb = 'mariadb';
    case Postgres = 'pgsql';

    /**
     * Version passée à `ddev config --database`. Ces valeurs sont écrites à la
     * main et invisibles pour Renovate : les relire à chaque nouvelle ligne
     * stable, en vérifiant que DDEV la prend en charge.
     *
     * - MariaDB 11.4 : LTS maintenue jusqu'en mai 2029, plus longtemps que 11.8.
     * - MySQL 8.4 : LTS ; la 8.0 n'est plus maintenue depuis avril 2026.
     * - PostgreSQL 18 : dernière version majeure stable.
     */
    public function getLatestVersion(): string
    {
        return match ($this) {
            self::Mariadb => 'mariadb:11.4',
            self::Mysql => 'mysql:8.4',
            self::Postgres => 'postgres:18',
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
