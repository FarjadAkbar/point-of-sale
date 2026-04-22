<?php

namespace App\Support;

/**
 * Ultimate POS–style permission groups for role forms (checkboxes + radio groups).
 *
 * @phpstan-type CheckboxItem array{value: string, label: string}
 * @phpstan-type RadioGroup array{name: string, options: array<int, array{value: string, label: string}>}
 * @phpstan-type Group array{id: string, title: string, select_all?: bool, help?: string, checkboxes?: list<CheckboxItem>, radios?: list<RadioGroup>}
 */
final class PosPermissionCatalog
{
    /**
     * @return list<Group>
     */
    public static function groups(): array
    {
        return require __DIR__.'/../../config/pos_permission_catalog.php';
    }

    /**
     * @return list<string>
     */
    public static function allCheckboxKeys(): array
    {
        $keys = [];
        foreach (self::groups() as $group) {
            foreach ($group['checkboxes'] ?? [] as $item) {
                $keys[] = $item['value'];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, string>
     */
    public static function defaultRadioSelections(): array
    {
        $defaults = [];
        foreach (self::groups() as $group) {
            foreach ($group['radios'] ?? [] as $radio) {
                $defaults[$radio['name']] = $radio['options'][0]['value'];
            }
        }

        return $defaults;
    }

    /**
     * @return list<string>
     */
    public static function allRadioValues(): array
    {
        $values = [];
        foreach (self::groups() as $group) {
            foreach ($group['radios'] ?? [] as $radio) {
                foreach ($radio['options'] ?? [] as $option) {
                    $values[] = $option['value'];
                }
            }
        }

        return array_values(array_unique($values));
    }
}
