<?php

require_once __DIR__ . '/../Core/DB.php';

class ProfileValidator
{
    private DB $db;
    private array $profiles;

    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
    }

    public function run(): void
    {
        echo "=== PROFILE VALIDATION START ===\n\n";

        foreach ($this->profiles as $key => $profile) {

            echo "Checking: {$key}\n";

            $table = $profile['table'] ?? null;

            if (!$this->tableExists($table)) {
                echo "  ❌ Table not found: {$table}\n";
                continue;
            }

            $columns = $this->getColumns($table);

            $this->validatePrimaryKey($profile, $columns);
            $this->validateModes($profile, $columns);
            $this->validateAutoSession($profile, $columns);
            $this->validateRelations($profile, $columns);
            $this->validatePivot($profile);

            echo "  ✔ OK\n\n";
        }

        echo "=== VALIDATION END ===\n";
    }

    private function tableExists(string $table): bool
    {
        return (bool)$this->db->query(
            "SHOW TABLES LIKE ?",
            [$table]
        )->fetch();
    }

    private function getColumns(string $table): array
    {
        return array_column(
            $this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),
            'Field'
        );
    }

    private function validatePrimaryKey(array $profile, array $columns): void
    {
        $pk = $profile['primary_key'] ?? 'id';

        if (!in_array($pk, $columns)) {
            echo "  ❌ Primary key '{$pk}' not found\n";
        }
    }

    private function validateModes(array $profile, array $columns): void
    {
        if (empty($profile['modes'])) return;

        foreach ($profile['modes'] as $mode => $config) {

            foreach (['select', 'searchable'] as $fieldType) {

                if (empty($config[$fieldType])) continue;

                foreach ($config[$fieldType] as $field) {

                    if ($field === '*') continue;

                    if (!in_array($field, $columns)) {
                        echo "  ❌ {$mode}->{$fieldType}: '{$field}' not found\n";
                    }
                }
            }

            if (!empty($config['where']) && is_array($config['where'])) {

                foreach ($config['where'] as $field => $value) {

                    if (!in_array($field, $columns)) {
                        echo "  ❌ {$mode}->where: '{$field}' not found\n";
                    }
                }
            }
        }
    }

    private function validateAutoSession(array $profile, array $columns): void
    {
        if (empty($profile['auto_session'])) return;

        foreach ($profile['auto_session'] as $field) {

            if (!in_array($field, $columns)) {
                echo "  ❌ auto_session: '{$field}' not found\n";
            }
        }
    }

    private function validateRelations(array $profile, array $columns): void
    {
        if (empty($profile['relations'])) return;

        foreach ($profile['relations'] as $name => $relation) {

            $local = $relation['local_key'] ?? null;

            if ($local && !in_array($local, $columns)) {
                echo "  ❌ relation local_key '{$local}' not found\n";
            }
        }
    }

    private function validatePivot(array $profile): void
    {
        if (empty($profile['pivot'])) return;

        $pivotTable = $profile['pivot']['table'] ?? null;

        if (!$this->tableExists($pivotTable)) {
            echo "  ❌ Pivot table '{$pivotTable}' not found\n";
        }
    }
}