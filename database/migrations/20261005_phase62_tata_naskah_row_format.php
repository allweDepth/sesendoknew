#!/usr/bin/env php
<?php

chdir(dirname(__DIR__, 2));
require_once 'app/Core/DB.php';

$db = DB::getInstance();
$templates = $db->query(
    'SELECT id,kode_form,schema_version,schema_json FROM ref_jenis_naskah ORDER BY id'
)->fetchAll();
$updatedTypes = 0;
$updatedTables = 0;
$removedSkOptions = 0;
$repairedTypes = 0;

$configureRowOptions = static function (array &$items, bool $isSk) use (&$configureRowOptions, &$updatedTables, &$removedSkOptions): void {
    foreach ($items as &$field) {
        if (!is_array($field)) {
            continue;
        }

        if (($field['type'] ?? null) === 'editable_table') {
            if ($isSk) {
                if (array_key_exists('row_options', $field)) {
                    unset($field['row_options']);
                    $removedSkOptions++;
                }
            } else {
                $name = (string)($field['name'] ?? '');
                $defaultType = match ($name) {
                    'menimbang', 'mengingat', 'tembusan' => 'numbered',
                    default => 'paragraph',
                };
                $changed = false;
                if (!isset($field['row_options']) || !is_array($field['row_options'])) {
                    $field['row_options'] = [
                        'types' => ['paragraph', 'list', 'numbered', 'alpha'],
                        'alignments' => ['left', 'center', 'right', 'justify'],
                        'styles' => ['bold', 'italic', 'underline'],
                        'formats' => ['label'],
                        'default_type' => $defaultType,
                        'default_align' => 'justify',
                    ];
                    $changed = true;
                } elseif ($name === 'menimbang' && ($field['row_options']['default_type'] ?? null) === 'alpha') {
                    $field['row_options']['default_type'] = 'numbered';
                    $changed = true;
                }
                if (($field['row_options']['hanging_indent'] ?? null) !== true) {
                    $field['row_options']['hanging_indent'] = true;
                    $changed = true;
                }
                if ($changed) {
                    $updatedTables++;
                }
            }
        }

        foreach (['fields', 'sections'] as $childrenKey) {
            if (isset($field[$childrenKey]) && is_array($field[$childrenKey])) {
                $configureRowOptions($field[$childrenKey], $isSk);
            }
        }
    }
    unset($field);
};

$db->begin();
try {
    foreach ($templates as $template) {
        $rawSchema = (string)$template['schema_json'];
        $schema = json_decode($rawSchema, true);
        $schemaRepaired = false;
        if (!is_array($schema)) {
            $schema = json_decode(rtrim($rawSchema).']', true);
            if (!is_array($schema) || !array_is_list($schema)) {
                throw new RuntimeException('Schema Tata Naskah tidak valid untuk jenis ID '.$template['id']);
            }

            $flatSchema = [];
            foreach ($schema as $item) {
                if (is_array($item) && array_is_list($item)) {
                    array_push($flatSchema, ...$item);
                } else {
                    $flatSchema[] = $item;
                }
            }
            $schema = $flatSchema;
            $schemaRepaired = true;
            $repairedTypes++;
        }

        $beforeTables = $updatedTables;
        $beforeSkOptions = $removedSkOptions;
        $configureRowOptions($schema, strtolower((string)$template['kode_form']) === 'sk');
        if (!$schemaRepaired && $updatedTables === $beforeTables && $removedSkOptions === $beforeSkOptions) {
            continue;
        }

        $encoded = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $db->query(
            'UPDATE ref_jenis_naskah SET schema_json=?,schema_version=schema_version+1 WHERE id=?',
            [$encoded, (int)$template['id']]
        );
        $db->query(
            'DELETE FROM cache_schema_naskah WHERE jenis_id=?',
            [(int)$template['id']]
        );
        $updatedTypes++;
    }

    $db->commit();
} catch (Throwable $error) {
    $db->rollback();
    throw $error;
}

echo "Konfigurasi format diperbarui untuk {$updatedTables} tabel editable non-SK; {$removedSkOptions} opsi format SK dihapus; {$repairedTypes} schema dengan penutup/array yang rusak diperbaiki pada {$updatedTypes} jenis naskah.\n";
