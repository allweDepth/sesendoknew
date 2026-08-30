<?php

require_once __DIR__ . '/../Core/DB.php';

class ProfileValidator
{
    private DB $db;
    private array $profiles;
    private int $errors = 0;
    private int $profileErrors = 0;

    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
    }

    public function run(): int
    {
        echo "=== PROFILE VALIDATION START ===\n\n";

        foreach ($this->profiles as $key => $profile) {

            echo "Checking: {$key}\n";
            $this->profileErrors = 0;

            $table = $profile['table'] ?? null;

            if (!$this->tableExists($table)) {
                $this->error("Table not found: {$table}");
                continue;
            }

            $columns = $this->getColumns($table);

            $this->validatePrimaryKey($profile, $columns);
            $this->validateModes($profile, $columns);
            $this->validateAutoSession($profile, $columns);
            $this->validateRelations($profile, $columns);
            $this->validatePivot($profile);

            echo $this->profileErrors === 0
                ? "  ✔ OK\n\n"
                : "  ✘ {$this->profileErrors} error(s)\n\n";
        }

        echo "=== VALIDATION END: {$this->errors} ERROR(S) ===\n";

        return $this->errors;
    }

    private function tableExists(string $table): bool
    {
        if ($table === '') {
            return false;
        }

        return (bool)$this->db->query(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = ?
             LIMIT 1",
            [$table]
        )->fetch();
    }

    private function getColumns(string $table): array
    {
        return array_column(
            $this->db->query(
                "SELECT column_name
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 ORDER BY ordinal_position",
                [$table]
            )->fetchAll(),
            'column_name'
        );
    }

    private function validatePrimaryKey(array $profile, array $columns): void
    {
        $pk = $profile['primary_key'] ?? 'id';

        if (!in_array($pk, $columns)) {
            $this->error("Primary key '{$pk}' not found");
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

                    // Qualified fields and SQL aliases are validated by the query/join
                    // layer, not against the physical columns of the base table.
                    if (str_contains($field, '.') || preg_match('/\s+AS\s+/i', $field)) {
                        continue;
                    }

                    if (!in_array($field, $columns)) {
                        $this->error("{$mode}->{$fieldType}: '{$field}' not found");
                    }
                }
            }

            if (!empty($config['where']) && is_array($config['where'])) {

                foreach ($config['where'] as $field => $value) {

                    if (!in_array($field, $columns)) {
                        $this->error("{$mode}->where: '{$field}' not found");
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
                $this->error("auto_session: '{$field}' not found");
            }
        }
    }

    private function validateRelations(array $profile, array $columns): void
    {
        if (empty($profile['relations'])) return;

        foreach ($profile['relations'] as $name => $relation) {

            $local = $relation['local_key'] ?? null;

            if ($local && !in_array($local, $columns)) {
                $this->error("relation local_key '{$local}' not found");
            }
        }
    }

    private function validatePivot(array $profile): void
    {
        if (empty($profile['pivot'])) return;

        $pivotTable = $profile['pivot']['table'] ?? null;

        if (!$this->tableExists($pivotTable)) {
            $this->error("Pivot table '{$pivotTable}' not found");
        }
    }

    private function error(string $message): void
    {
        $this->errors++;
        $this->profileErrors++;
        echo "  ❌ {$message}\n";
    }
}
