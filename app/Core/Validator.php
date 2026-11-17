<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Tiny declarative validator.
 *
 *   $errors = Validator::make($_POST, [
 *       'title' => 'required|min:3|max:150',
 *       'email' => 'required|email',
 *   ]);
 */
final class Validator
{
    /** @return array<string,string> field => first error message */
    public static function make(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $rules_ = explode('|', $ruleStr);
            $label  = ucfirst(str_replace('_', ' ', $field));

            // "required_with" style short-circuit: empty + not required => skip other rules
            $required = in_array('required', $rules_, true);
            $isEmpty  = $value === null || $value === '' || $value === [];

            if ($isEmpty) {
                if ($required) {
                    $errors[$field] = $label . ' is required.';
                }
                continue;
            }

            foreach ($rules_ as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $msg = self::apply((string)$name, $arg, $value, $label, $data);
                if ($msg !== null) {
                    $errors[$field] = $msg;
                    break;
                }
            }
        }
        return $errors;
    }

    private static function apply(string $name, ?string $arg, mixed $value, string $label, array $data): ?string
    {
        switch ($name) {
            case 'required':
            case 'nullable':
                return null;
            case 'min':
                $len = is_array($value) ? count($value) : mb_strlen((string)$value);
                return $len < (int)$arg ? sprintf('%s must be at least %s characters.', $label, $arg) : null;
            case 'max':
                $len = is_array($value) ? count($value) : mb_strlen((string)$value);
                return $len > (int)$arg ? sprintf('%s must be at most %s characters.', $label, $arg) : null;
            case 'email':
                return filter_var((string)$value, FILTER_VALIDATE_EMAIL) ? null : $label . ' must be a valid e-mail address.';
            case 'username':
                return preg_match('/^[a-zA-Z0-9_]{3,30}$/', (string)$value)
                    ? null : 'Username must be 3-30 letters, digits or underscores.';
            case 'slug':
                return preg_match('/^[a-z0-9][a-z0-9-]{1,60}$/', (string)$value)
                    ? null : $label . ' may contain only lowercase letters, digits and dashes.';
            case 'in':
                $list = explode(',', (string)$arg);
                $ok = is_array($value)
                    ? !array_diff($value, $list)
                    : in_array((string)$value, $list, true);
                return $ok ? null : $label . ' has an invalid value.';
            case 'int':
                return filter_var($value, FILTER_VALIDATE_INT) === false ? $label . ' must be a whole number.' : null;
            case 'number':
                return is_numeric($value) ? null : $label . ' must be a number.';
            case 'boolean':
                return in_array($value, [0, 1, '0', '1', true, false, 'on'], true) ? null : $label . ' must be true or false.';
            case 'date':
                $d = \DateTime::createFromFormat('Y-m-d H:i:s', (string)$value);
                return ($d && $d->format('Y-m-d H:i:s') === $value) ? null : $label . ' must be a valid date.';
            default:
                return null;
        }
    }
}
