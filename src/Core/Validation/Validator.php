<?php

declare(strict_types=1);

namespace GoniCore\Core\Validation;

use GoniCore\Core\Database\QueryBuilder;

/**
 * Input validator with a Laravel-style pipe syntax.
 *
 * Rules are defined as a pipe-separated string:
 *   'required|string|min:3|max:255'
 *   'nullable|email'
 *   'required|in:draft,published,archived'
 *
 * Supported rules:
 *   required          — value must be present and non-empty
 *   nullable          — skip all subsequent rules if value is null/empty
 *   string            — must be a string
 *   int               — must be an integer (or integer string)
 *   numeric           — must be numeric
 *   boolean           — must be a bool-like value
 *   email             — must be a valid email address
 *   min:<n>           — string: min length n; numeric: minimum value n
 *   max:<n>           — string: max length n; numeric: maximum value n
 *   in:<a,b,c>        — value must be one of the listed options
 *   unique:<table>[,column[,exceptId]]  — value must not exist in DB column
 *
 * Usage:
 *   $v = new Validator();
 *   $v->validate($request->json(), [
 *       'title' => 'required|string|min:3|max:500',
 *       'email' => 'required|email',
 *       'role'  => 'nullable|in:admin,editor,viewer',
 *   ]);
 *   // Throws ValidationException (HTTP 422) on failure.
 */
final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    public function __construct(private readonly ?QueryBuilder $qb = null) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Validate $data against $rules.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     * @throws ValidationException  on the first batch of errors found.
     */
    public function validate(array $data, array $rules): void
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = array_map('trim', explode('|', $ruleString));
            $value    = $data[$field] ?? null;

            // If `nullable` is present and value is empty, skip all other checks.
            if (in_array('nullable', $ruleList, true)
                && ($value === null || $value === '')) {
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }
                $this->applyRule($field, $value, $rule);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    // -------------------------------------------------------------------------
    // Rule dispatch
    // -------------------------------------------------------------------------

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = str_contains($rule, ':')
            ? explode(':', $rule, 2)
            : [$rule, null];

        match ($name) {
            'required' => $this->ruleRequired($field, $value),
            'string'   => $this->ruleString($field, $value),
            'int'      => $this->ruleInt($field, $value),
            'numeric'  => $this->ruleNumeric($field, $value),
            'boolean'  => $this->ruleBoolean($field, $value),
            'email'    => $this->ruleEmail($field, $value),
            'min'      => $this->ruleMin($field, $value, (int) $param),
            'max'      => $this->ruleMax($field, $value, (int) $param),
            'in'       => $this->ruleIn($field, $value, explode(',', $param ?? '')),
            'unique'   => $this->ruleUnique($field, $value, $param),
            default    => null,
        };
    }

    // -------------------------------------------------------------------------
    // Individual rules
    // -------------------------------------------------------------------------

    private function ruleRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$this->label($field)} field is required.");
        }
    }

    private function ruleString(string $field, mixed $value): void
    {
        if ($value !== null && !is_string($value)) {
            $this->addError($field, "The {$this->label($field)} must be a string.");
        }
    }

    private function ruleInt(string $field, mixed $value): void
    {
        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "The {$this->label($field)} must be an integer.");
        }
    }

    private function ruleNumeric(string $field, mixed $value): void
    {
        if ($value !== null && !is_numeric($value)) {
            $this->addError($field, "The {$this->label($field)} must be numeric.");
        }
    }

    private function ruleBoolean(string $field, mixed $value): void
    {
        $allowed = [true, false, 1, 0, '1', '0', 'true', 'false'];

        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->addError($field, "The {$this->label($field)} must be a boolean.");
        }
    }

    private function ruleEmail(string $field, mixed $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$this->label($field)} must be a valid email address.");
        }
    }

    private function ruleMin(string $field, mixed $value, int $min): void
    {
        if ($value === null) {
            return;
        }

        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "The {$this->label($field)} must be at least {$min} characters.");
        } elseif (is_numeric($value) && (float) $value < $min) {
            $this->addError($field, "The {$this->label($field)} must be at least {$min}.");
        }
    }

    private function ruleMax(string $field, mixed $value, int $max): void
    {
        if ($value === null) {
            return;
        }

        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "The {$this->label($field)} must not exceed {$max} characters.");
        } elseif (is_numeric($value) && (float) $value > $max) {
            $this->addError($field, "The {$this->label($field)} must not exceed {$max}.");
        }
    }

    private function ruleIn(string $field, mixed $value, array $options): void
    {
        if ($value !== null && !in_array((string) $value, $options, true)) {
            $this->addError(
                $field,
                "The {$this->label($field)} must be one of: " . implode(', ', $options) . '.',
            );
        }
    }

    /**
     * Unique DB check.
     * $param format: "table" | "table,column" | "table,column,exceptId"
     */
    private function ruleUnique(string $field, mixed $value, ?string $param): void
    {
        if ($value === null || $this->qb === null || $param === null) {
            return;
        }

        $parts    = explode(',', $param);
        $table    = $parts[0];
        $column   = $parts[1] ?? $field;
        $exceptId = $parts[2] ?? null;

        $query = $this->qb->table($table)->where($column, '=', $value);

        if ($exceptId !== null) {
            $query = $query->where('id', '!=', $exceptId);
        }

        if ($query->count() > 0) {
            $this->addError($field, "The {$this->label($field)} has already been taken.");
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function label(string $field): string
    {
        return str_replace('_', ' ', $field);
    }
}
