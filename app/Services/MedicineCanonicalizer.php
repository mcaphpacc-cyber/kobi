<?php

declare(strict_types=1);

namespace App\Services;

use Normalizer;

/** Version-one identity rules. No SQL, clinical conversion or patient dose. */
class MedicineCanonicalizer
{
    public function id(mixed $value): string
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/\A[0-9]{1,20}\z/D', (string) $value)) {
            throw new MedicinePersistenceException('Invalid medicine reference.', 422);
        }
        $id = ltrim((string) $value, '0');
        $maximum = (string) PHP_INT_MAX;
        if ($id === '' || strlen($id) > strlen($maximum) || (strlen($id) === strlen($maximum) && strcmp($id, $maximum) > 0)) {
            throw new MedicinePersistenceException('Invalid medicine reference.', 422);
        }
        return $id;
    }

    public function fields(array $input, array $allowed, array $required = []): void
    {
        if (array_diff(array_keys($input), $allowed) || array_diff($required, array_keys($input))) {
            throw new MedicinePersistenceException('Invalid medicine input fields.', 422);
        }
    }

    public function text(mixed $value, int $limit): ?string
    {
        if ($value === null) { return null; }
        if (!is_string($value) || strlen($value) > $limit * 4 || !preg_match('//u', $value)) {
            throw new MedicinePersistenceException('Invalid medicine text.', 422);
        }
        if (!class_exists(Normalizer::class) || !function_exists('mb_strlen')) {
            throw new MedicinePersistenceException('Medicine normalization is unavailable.', 503);
        }
        $text = Normalizer::normalize($value, Normalizer::FORM_C);
        if ($text === false || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $text)) {
            throw new MedicinePersistenceException('Invalid medicine text.', 422);
        }
        $text = trim(preg_replace('/[\p{Z}\s]+/u', ' ', $text));
        if (mb_strlen($text, 'UTF-8') > $limit) { throw new MedicinePersistenceException('Medicine text is too long.', 422); }
        return $text === '' ? null : $text;
    }

    /** Input is already resolved against persisted chemical adapters by the service. */
    public function composition(array $references): array
    {
        if (!array_is_list($references) || count($references) < 1 || count($references) > 16) {
            throw new MedicinePersistenceException('Select between one and sixteen ingredients.', 422);
        }
        $seen = [];
        foreach ($references as &$reference) {
            if (!is_array($reference) || !in_array($reference['tag'] ?? null, ['S', 'F'], true)) {
                throw new MedicinePersistenceException('Invalid ingredient reference.', 422);
            }
            $reference['id'] = $this->id($reference['id'] ?? null);
            $key = $reference['tag'] . ':' . $reference['id'];
            if (isset($seen[$key])) { throw new MedicinePersistenceException('Duplicate medicine ingredient.', 422); }
            $seen[$key] = true;
        }
        unset($reference);
        usort($references, static function (array $a, array $b): int {
            if ($a['tag'] !== $b['tag']) { return $a['tag'] === 'S' ? -1 : 1; }
            return strlen($a['id']) <=> strlen($b['id']) ?: strcmp($a['id'], $b['id']);
        });
        $key = $this->json(['v' => 1, 'ingredients' => array_map(static fn(array $r): array => [$r['tag'], $r['id']], $references)], 8192);
        return ['references' => $references, 'key' => $key, 'hash' => hash('sha256', $key, true)];
    }

    public function presentation(string $identity, array $members, array $input): array
    {
        $this->fields($input, ['dosage_form_code', 'route_code', 'release_code', 'strengths', 'display_name'], ['dosage_form_code', 'route_code', 'release_code', 'strengths']);
        $form = $input['dosage_form_code']; $route = $input['route_code']; $release = $input['release_code'];
        $solid = in_array($form, ['tablet', 'capsule'], true);
        $liquid = in_array($form, ['solution', 'suspension'], true);
        $topical = in_array($form, ['cream', 'ointment'], true);
        if ((!$solid && !$liquid && !$topical) || $route !== ($topical ? 'cutaneous' : 'oral')
            || !in_array($release, $solid ? ['immediate', 'delayed', 'extended'] : ['not_applicable'], true)) {
            throw new MedicinePersistenceException('Unsupported medicine presentation.', 422);
        }
        if (!is_array($input['strengths']) || !array_is_list($input['strengths']) || count($input['strengths']) !== count($members)) {
            throw new MedicinePersistenceException('A complete ingredient strength vector is required.', 422);
        }
        $byReference = [];
        foreach ($input['strengths'] as $strength) {
            if (!is_array($strength)) { throw new MedicinePersistenceException('Invalid ingredient strength.', 422); }
            $id = $this->id($strength['chemical_ref_id'] ?? null);
            if (isset($byReference[$id])) { throw new MedicinePersistenceException('Duplicate ingredient strength.', 422); }
            unset($strength['chemical_ref_id']);
            $normalized = $this->strength($strength);
            if ($normalized['denominator_unit_code'] !== ($solid ? 'unit' : ($liquid ? 'mL' : 'g'))) {
                throw new MedicinePersistenceException('Strength basis does not match the presentation.', 422);
            }
            $byReference[$id] = $normalized;
        }
        $vector = []; $rows = [];
        foreach ($members as $member) {
            $ref = $this->id($member['chemical_ref_id']);
            $strength = $byReference[$ref] ?? null;
            if ($strength === null) { throw new MedicinePersistenceException('Strength belongs to an unrelated ingredient.', 422); }
            $vector[] = [$member['tag'], $this->id($member['id']), $strength['ratio_numerator'], $strength['ratio_denominator'], 'g', $strength['denominator_unit_code']];
            $rows[] = ['medicine_ingredient_id' => $this->id($member['medicine_ingredient_id'])] + $strength;
        }
        $key = $this->json(['v' => 1, 'identity' => $this->id($identity), 'strengths' => $vector, 'form' => $form, 'route' => $route, 'release' => $release], 16384);
        return ['key' => $key, 'hash' => hash('sha256', $key, true), 'rows' => $rows,
            'dosage_form_code' => $form, 'route_code' => $route, 'release_code' => $release,
            'display_name' => $this->text($input['display_name'] ?? null, 500)];
    }

    /** Accept decimal quantities OR integer ratio coefficients; never PHP floats. */
    public function strength(array $input): array
    {
        if (!function_exists('bcmod')) { throw new MedicinePersistenceException('Exact medicine arithmetic is unavailable.', 503); }
        $coefficientMode = array_key_exists('ratio_numerator', $input) || array_key_exists('ratio_denominator', $input);
        if ($coefficientMode) {
            $this->fields($input, ['ratio_numerator', 'ratio_denominator', 'numerator_unit_code', 'denominator_unit_code', 'original_strength_text'], ['ratio_numerator', 'ratio_denominator', 'numerator_unit_code', 'denominator_unit_code']);
            $n = $this->coefficient($input['ratio_numerator']); $d = $this->coefficient($input['ratio_denominator']);
            if ($input['numerator_unit_code'] !== 'g' || !in_array($input['denominator_unit_code'], ['unit', 'mL', 'g'], true)) {
                throw new MedicinePersistenceException('Unsupported strength unit.', 422);
            }
            $unit = $input['denominator_unit_code'];
        } else {
            $this->fields($input, ['numerator_quantity', 'numerator_unit', 'denominator_quantity', 'denominator_unit', 'original_strength_text'], ['numerator_quantity', 'numerator_unit', 'denominator_quantity', 'denominator_unit']);
            [$n, $nd] = $this->decimal($input['numerator_quantity']); [$d, $dd] = $this->decimal($input['denominator_quantity']);
            $numeratorUnit = $this->unit($input['numerator_unit']); $denominatorUnit = $this->unit($input['denominator_unit']);
            $mass = ['g' => 0, 'mg' => -3, 'ug' => -6]; $volume = ['mL' => 0, 'L' => 3];
            if (!array_key_exists($numeratorUnit, $mass)) { throw new MedicinePersistenceException('Unsupported strength unit.', 422); }
            if ($denominatorUnit === 'unit') {
                if ($dd !== '1') { throw new MedicinePersistenceException('Dosage-unit denominator must be an integer.', 422); }
                $unit = 'unit'; $denominatorPower = 0;
            } elseif (array_key_exists($denominatorUnit, $mass)) { $unit = 'g'; $denominatorPower = $mass[$denominatorUnit]; }
            elseif (array_key_exists($denominatorUnit, $volume)) { $unit = 'mL'; $denominatorPower = $volume[$denominatorUnit]; }
            else { throw new MedicinePersistenceException('Unsupported strength unit.', 422); }
            $n = bcmul($n, $dd, 0); $d = bcmul($d, $nd, 0);
            $power = $mass[$numeratorUnit] - $denominatorPower;
            if ($power > 0) { $n .= str_repeat('0', $power); } elseif ($power < 0) { $d .= str_repeat('0', -$power); }
        }
        $a = $n; $b = $d;
        while ($b !== '0') { $remainder = bcmod($a, $b, 0); $a = $b; $b = $remainder; }
        $n = bcdiv($n, $a, 0); $d = bcdiv($d, $a, 0);
        $this->coefficient($n); $this->coefficient($d);
        return ['ratio_numerator' => $n, 'ratio_denominator' => $d, 'numerator_unit_code' => 'g', 'denominator_unit_code' => $unit,
            'original_strength_text' => $this->text($input['original_strength_text'] ?? null, 512)];
    }

    private function coefficient(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/\A[1-9][0-9]{0,37}\z/D', $value)) {
            throw new MedicinePersistenceException('Strength coefficients must be positive integers of at most 38 digits.', 422);
        }
        return $value;
    }

    private function decimal(mixed $value): array
    {
        if (!is_string($value) || strlen($value) > 256 || !preg_match('/\A([0-9]+)(?:\.([0-9]+))?\z/D', $value, $parts)) {
            throw new MedicinePersistenceException('Invalid exact strength quantity.', 422);
        }
        $integer = ltrim($parts[1], '0'); $fraction = rtrim($parts[2] ?? '', '0');
        if (strlen($integer) > 12 || strlen($fraction) > 12 || ($integer === '' && $fraction === '')) {
            throw new MedicinePersistenceException('Strength quantity is outside the supported precision.', 422);
        }
        $n = ltrim(($integer === '' ? '0' : $integer) . $fraction, '0');
        return [$n, '1' . str_repeat('0', strlen($fraction))];
    }

    private function unit(mixed $value): string
    {
        if (!is_string($value)) { throw new MedicinePersistenceException('Unsupported strength unit.', 422); }
        return match ($value) { 'µg', 'μg' => 'ug', default => $value };
    }

    private function json(array $value, int $limit): string
    {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > $limit) { throw new MedicinePersistenceException('Medicine identity is too large.', 422); }
        return $json;
    }
}
