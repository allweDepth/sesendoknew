<?php

/** Writes two-dimensional WGS84 SHP/SHX and UTF-8 DBF components. */
class MapsShapefileWriter
{
    public static function build(array $collection, array $fields): array
    {
        if (($collection['type'] ?? '') !== 'FeatureCollection' || !is_array($collection['features'] ?? null)
            || !array_is_list($collection['features']) || count($collection['features']) < 1 || count($collection['features']) > 50000) {
            throw new InvalidArgumentException('Layer harus memiliki 1–50.000 feature.');
        }
        $fields = self::validateFields($fields);
        $records = [];
        $properties = [];
        $allBounds = null;
        $shapeType = null;
        $pointCount = 0;
        foreach ($collection['features'] as $feature) {
            if (!is_array($feature) || ($feature['type'] ?? '') !== 'Feature' || !is_array($feature['geometry'] ?? null)
                || !is_array($feature['properties'] ?? [])) {
                throw new InvalidArgumentException('Feature tidak valid.');
            }
            [$type, $content, $bounds] = self::geometry($feature['geometry'], $pointCount);
            if ($shapeType !== null && $shapeType !== $type) throw new InvalidArgumentException('Satu SHP hanya boleh berisi satu jenis geometri.');
            $shapeType = $type;
            $allBounds = $allBounds === null ? $bounds : [min($allBounds[0], $bounds[0]), min($allBounds[1], $bounds[1]), max($allBounds[2], $bounds[2]), max($allBounds[3], $bounds[3])];
            $records[] = $content;
            $properties[] = $feature['properties'] ?? [];
        }
        $shp = '';
        $shx = '';
        $offset = 50;
        foreach ($records as $index => $content) {
            $length = intdiv(strlen($content), 2);
            $shx .= pack('NN', $offset, $length);
            $shp .= pack('NN', $index + 1, $length) . $content;
            $offset += 4 + $length;
        }
        $components = [
            'shp' => self::header(100 + strlen($shp), $shapeType, $allBounds) . $shp,
            'shx' => self::header(100 + strlen($shx), $shapeType, $allBounds) . $shx,
            'dbf' => self::dbf($properties, $fields),
            'prj' => 'GEOGCS["WGS 84",DATUM["WGS_1984",SPHEROID["WGS 84",6378137,298.257223563]],PRIMEM["Greenwich",0],UNIT["degree",0.0174532925199433],AUTHORITY["EPSG","4326"]]',
            'cpg' => 'UTF-8',
        ];
        foreach ($components as $data) {
            if (strlen($data) > 32 * 1024 * 1024) throw new InvalidArgumentException('Komponen SHP maksimal 32 MB.');
        }
        return $components;
    }

    public static function validateFields(array $fields): array
    {
        if (!array_is_list($fields) || count($fields) < 1 || count($fields) > 64) throw new InvalidArgumentException('Tentukan 1–64 field DBF.');
        $names = [];
        foreach ($fields as &$field) {
            if (!is_array($field)) throw new InvalidArgumentException('Definisi field tidak valid.');
            $name = trim((string)($field['name'] ?? ''));
            $type = (string)($field['type'] ?? 'C');
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,9}$/D', $name) || isset($names[strtoupper($name)])) {
                throw new InvalidArgumentException('Nama field harus unik, maksimal 10 karakter, memakai huruf, angka, atau underscore.');
            }
            $names[strtoupper($name)] = true;
            if (!in_array($type, ['C', 'N', 'L', 'D'], true)) throw new InvalidArgumentException('Tipe field harus teks, angka, boolean, atau tanggal.');
            $width = filter_var($field['width'] ?? ($type === 'C' ? 80 : 18), FILTER_VALIDATE_INT);
            $decimals = filter_var($field['decimals'] ?? 0, FILTER_VALIDATE_INT);
            if ($type === 'L') { $width = 1; $decimals = 0; }
            elseif ($type === 'D') { $width = 8; $decimals = 0; }
            elseif (!$width || $width < 1 || $width > ($type === 'C' ? 254 : 20)
                || $decimals === false || $decimals < 0 || $decimals > 8 || ($type === 'N' && $decimals > $width - 2)) {
                throw new InvalidArgumentException('Panjang atau desimal field tidak valid.');
            }
            if ($type === 'C') $decimals = 0;
            $field = ['name' => $name, 'type' => $type, 'width' => $width, 'decimals' => $decimals];
        }
        unset($field);
        return $fields;
    }

    private static function point($point, int &$count): array
    {
        if (!is_array($point) || count($point) !== 2 || !array_is_list($point)
            || !is_numeric($point[0]) || !is_numeric($point[1]) || !is_finite((float)$point[0]) || !is_finite((float)$point[1])
            || abs((float)$point[0]) > 180 || abs((float)$point[1]) > 90) {
            throw new InvalidArgumentException('Koordinat harus berupa longitude/latitude WGS84 dua dimensi.');
        }
        if (++$count > 500000) throw new InvalidArgumentException('Layer maksimal 500.000 titik.');
        return [(float)$point[0], (float)$point[1]];
    }

    private static function geometry(array $geometry, int &$count): array
    {
        $kind = $geometry['type'] ?? '';
        $coordinates = $geometry['coordinates'] ?? null;
        if (!is_array($coordinates) || !array_is_list($coordinates) || !$coordinates) throw new InvalidArgumentException('Geometri kosong.');
        if ($kind === 'Point') {
            $p = self::point($coordinates, $count);
            return [1, pack('Vee', 1, ...$p), [$p[0], $p[1], $p[0], $p[1]]];
        }
        $parts = [];
        if ($kind === 'MultiPoint') {
            $type = 8;
            $parts = [array_map(static function ($p) use (&$count) { return self::point($p, $count); }, $coordinates)];
        } elseif (in_array($kind, ['LineString', 'MultiLineString'], true)) {
            $type = 3;
            foreach ($kind === 'LineString' ? [$coordinates] : $coordinates as $line) {
                if (!is_array($line) || !array_is_list($line) || count($line) < 2) throw new InvalidArgumentException('Garis membutuhkan minimal dua titik.');
                $points = array_map(static function ($p) use (&$count) { return self::point($p, $count); }, $line);
                if (count(array_unique(array_map('serialize', $points))) < 2) throw new InvalidArgumentException('Garis membutuhkan dua titik berbeda.');
                $parts[] = $points;
            }
        } elseif (in_array($kind, ['Polygon', 'MultiPolygon'], true)) {
            $type = 5;
            foreach ($kind === 'Polygon' ? [$coordinates] : $coordinates as $polygon) {
                if (!is_array($polygon) || !array_is_list($polygon) || !$polygon) throw new InvalidArgumentException('Poligon kosong.');
                foreach ($polygon as $ringIndex => $ring) {
                    if (!is_array($ring) || !array_is_list($ring) || count($ring) < 4) throw new InvalidArgumentException('Poligon membutuhkan tiga titik berbeda dan ring tertutup.');
                    $points = array_map(static function ($p) use (&$count) { return self::point($p, $count); }, $ring);
                    if ($points[0] !== $points[count($points) - 1]) throw new InvalidArgumentException('Ring poligon harus tertutup.');
                    $area = 0;
                    for ($i = 0; $i < count($points) - 1; $i++) $area += $points[$i][0] * $points[$i + 1][1] - $points[$i + 1][0] * $points[$i][1];
                    if (abs($area) < 1e-14) throw new InvalidArgumentException('Poligon tidak memiliki luas.');
                    // SHP shells clockwise, holes counterclockwise.
                    if (($ringIndex === 0 && $area > 0) || ($ringIndex > 0 && $area < 0)) $points = array_reverse($points);
                    $parts[] = $points;
                }
            }
        } else throw new InvalidArgumentException('Jenis geometri belum didukung untuk penyuntingan.');
        $points = array_merge(...$parts);
        $xs = array_column($points, 0); $ys = array_column($points, 1);
        $bounds = [min($xs), min($ys), max($xs), max($ys)];
        $content = pack('Veeee', $type, ...$bounds);
        if ($type === 8) $content .= pack('V', count($points));
        else {
            $content .= pack('VV', count($parts), count($points));
            $offset = 0;
            foreach ($parts as $part) { $content .= pack('V', $offset); $offset += count($part); }
        }
        foreach ($points as $p) $content .= pack('ee', ...$p);
        return [$type, $content, $bounds];
    }

    private static function header(int $bytes, int $type, array $bounds): string
    {
        return pack('N7', 9994, 0, 0, 0, 0, 0, intdiv($bytes, 2)) . pack('VV', 1000, $type)
            . pack('e8', ...[...$bounds, 0, 0, 0, 0]);
    }

    private static function dbf(array $properties, array $fields): string
    {
        $recordLength = 1 + array_sum(array_column($fields, 'width'));
        $headerLength = 33 + 32 * count($fields);
        $dbf = pack('C4Vvv', 3, (int)date('Y') - 1900, (int)date('n'), (int)date('j'), count($properties), $headerLength, $recordLength) . str_repeat("\0", 20);
        foreach ($fields as $field) {
            $dbf .= str_pad($field['name'], 11, "\0") . $field['type'] . str_repeat("\0", 4)
                . pack('CC', $field['width'], $field['decimals']) . str_repeat("\0", 14);
        }
        $dbf .= "\r";
        foreach ($properties as $record) {
            $dbf .= ' ';
            foreach ($fields as $field) {
                $value = $record[$field['name']] ?? null;
                if ($value === null || $value === '') $text = '';
                elseif ($field['type'] === 'C') {
                    if (!is_scalar($value)) throw new InvalidArgumentException('Nilai teks harus berupa teks biasa.');
                    $text = (string)$value;
                    if (!preg_match('//u', $text) || preg_match('/[\x00-\x1F\x7F]/', $text)) throw new InvalidArgumentException('Teks field tidak valid.');
                } elseif ($field['type'] === 'N') {
                    if (!is_numeric($value) || !is_finite((float)$value)) throw new InvalidArgumentException('Field ' . $field['name'] . ' harus berupa angka.');
                    $text = number_format((float)$value, $field['decimals'], '.', '');
                    if ($field['decimals'] === 0 && (float)$text !== (float)$value) throw new InvalidArgumentException('Field bilangan bulat tidak boleh memiliki desimal.');
                } elseif ($field['type'] === 'L') {
                    if (!is_bool($value)) throw new InvalidArgumentException('Field boolean harus true/false.');
                    $text = $value ? 'T' : 'F';
                } else {
                    $text = str_replace('-', '', (string)$value);
                    if (!preg_match('/^\d{8}$/D', $text) || !checkdate((int)substr($text, 4, 2), (int)substr($text, 6, 2), (int)substr($text, 0, 4))) throw new InvalidArgumentException('Field tanggal harus tanggal yang valid (YYYY-MM-DD).');
                }
                if (strlen($text) > $field['width']) throw new InvalidArgumentException('Nilai field ' . $field['name'] . ' melebihi panjang field.');
                $dbf .= str_pad($text, $field['width'], ' ', $field['type'] === 'N' ? STR_PAD_LEFT : STR_PAD_RIGHT);
            }
        }
        return $dbf . "\x1A";
    }
}
