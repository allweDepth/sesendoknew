<?php

require_once __DIR__ . '/../Core/DB.php';

class ProfileGenerator
{
    private $db;
    private array $tables = [];

    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->tables = $this->getTables();
    }

    public function generate(): void
    {
        $profiles = [];

        foreach ($this->tables as $table) {

            $columns = $this->getColumns($table);
            $primaryKey = $this->getPrimaryKey($table);

            $profiles[$table] = [
                'table' => $table,
                'primary_key' => $primaryKey,
                'allowed_roles' => ['super_admin'],
                'soft_lock' => false,
                'modes' => [
                    'default' => [
                        'select' => $columns,
                        'searchable' => $this->guessSearchable($columns),
                        'order_by' => $primaryKey . ' DESC'
                    ],
                    'edit' => [
                        'select' => ['*'],
                        'searchable' => ['*'],
                        'order_by' => $primaryKey . ' ASC'
                    ]
                ]
            ];
        }

        $this->writeFile($profiles);
    }

    private function getTables(): array
    {
        return array_map(
            fn($row) => array_values($row)[0],
            $this->db->query("SHOW TABLES")->fetchAll()
        );
    }

    private function getColumns(string $table): array
    {
        return array_column(
            $this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),
            'Field'
        );
    }

    private function getPrimaryKey(string $table): string
    {
        $result = $this->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'")
            ->fetch();

        return $result['Column_name'] ?? 'id';
    }

    private function guessSearchable(array $columns): array
    {
        return array_values(
            array_filter($columns, function ($col) {
                return (
                    str_contains($col, 'nama') ||
                    str_contains($col, 'kode') ||
                    str_contains($col, 'uraian') ||
                    str_contains($col, 'judul') ||
                    str_contains($col, 'keterangan')
                );
            })
        );
    }

    private function writeFile(array $profiles): void
    {
        $export = "<?php\n\nreturn " . var_export($profiles, true) . ";\n";

        file_put_contents(
            __DIR__ . '/../Config/table_profiles.auto.php',
            $export
        );

        echo "Profile generated: table_profiles.auto.php\n";
    }
}