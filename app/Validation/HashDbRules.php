<?php
namespace App\Validation;

use Config\Database;


class HashDbRules
{
    public function is_not_unique_hash(string $str, string $field, array $data): bool
    {
        // Grab any data for exclusion of a single row.
        [$field, $whereField, $whereValue] = array_pad(explode(',', $field), 3, null);

        // Break the table and field apart
        sscanf($field, '%[^.].%[^.]', $table, $field);

        $db = Database::connect($data['DBGroup'] ?? null);

        $row = $db->table($table)
            ->select('1')
            ->where($field, decodeHashId($str))
            ->limit(1);

        if (! empty($whereField) && ! empty($whereValue) && ! preg_match('/^\{(\w+)\}$/', $whereValue)) {
            $row = $row->where($whereField, $whereValue);
        }

        return $row->get()->getRow() !== null;

    }

    public function is_unique_except_hash(string $str, string $field, array $data): bool
    {
        // Grab any data for exclusion of a single row.
        [$field, $ignoreField, $ignoreValue] = array_pad(explode(',', $field), 3, null);

        // Break the table and field apart
        sscanf($field, '%[^.].%[^.]', $table, $field);

        $db = Database::connect($data['DBGroup'] ?? null);

        $row = $db->table($table)
            ->select('1')
            ->where($field, $str)
            ->limit(1);

        if (! empty($ignoreField) && ! empty($ignoreValue) && ! preg_match('/^\{(\w+)\}$/', $ignoreValue)) {
            $row = $row->where("{$ignoreField} !=", decodeHashId($ignoreValue));
        }

        return $row->get()->getRow() === null;
    }
}