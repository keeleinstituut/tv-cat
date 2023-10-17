<?php

$root = realpath( dirname( __FILE__ ) . '/../' );
include_once $root . "/inc/Bootstrap.php";
Bootstrap::start();

class SQLMigrationsRunner
{
    private $rootDir;
    private $appliedMigrations;

    public function __construct()
    {
        $this->rootDir = realpath( dirname( __FILE__ ) . '/../' );
        $this->appliedMigrations = $this->getAppliedMigrationFiles();
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        $db = Database::obtain(INIT::$DB_SERVER, INIT::$DB_USER, INIT::$DB_PASS, INIT::$DB_DATABASE);
        $db->connect();
        $connection = $db->getConnection();
        foreach ($this->getMigrationFiles() as $migrationFile) {
            if ($this->isApplied($migrationFile)) {
                continue;
            }

            try {
                $connection->beginTransaction();
                $connection->exec(file_get_contents($migrationFile));
                $connection->commit();

                $this->saveAsApplied($migrationFile);
            } catch (Exception $e) {
                $connection->rollBack();
                $this->storeAppliedMigrations();
                echo "Migration $file failed", PHP_EOL;
                throw $e;
            }
        }

        $this->storeAppliedMigrations();
    }

    private function getMigrationFiles()
    {
        $files = glob( $this->rootDir . "/migrations/sql/*.sql" );
        sort($files);
        return $files;
    }

    private function getAppliedMigrationFiles()
    {
        $appliedMigrations = @file_get_contents($this->getAppliedMigrationsStoragePath());
        if (empty($appliedMigrations)) {
            return [];
        }

        return json_decode($appliedMigrations, true);
    }

    private function isApplied($file)
    {
        return in_array($file, $this->appliedMigrations);
    }

    private function saveAsApplied($file)
    {
        $this->appliedMigrations[] = $file;
    }

    private function storeAppliedMigrations()
    {
        file_put_contents($this->getAppliedMigrationsStoragePath(), json_encode($this->appliedMigrations));
    }

    private function getAppliedMigrationsStoragePath()
    {
        return $this->rootDir . "/migrations/sql/applied-migrations/applied-migrations.json";
    }
}


(new SQLMigrationsRunner())->run();