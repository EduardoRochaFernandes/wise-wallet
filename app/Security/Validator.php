<?php
/**
 * WiseWallet 2.0 — Server-side input validation & sanitization.
 *
 * Every endpoint validates here BEFORE touching the database. Collects all
 * errors so the UI can show them at once, and exposes typed/clean values.
 */

declare(strict_types=1);

final class Validator
{
    private array $data;
    private array $errors = [];
    private array $clean  = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    private function val(string $field)
    {
        $v = $this->data[$field] ?? null;
        return is_string($v) ? trim($v) : $v;
    }

    public function required(string $field, string $label): self
    {
        $v = $this->val($field);
        if ($v === null || $v === '' || (is_array($v) && count($v) === 0)) {
            $this->errors[$field] = "{$label} é obrigatório.";
        } else {
            $this->clean[$field] = $v;
        }
        return $this;
    }

    public function email(string $field, string $label = 'Email'): self
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} inválido.";
        } elseif ($v) {
            $this->clean[$field] = strtolower($v);
        }
        return $this;
    }

    public function min(string $field, int $len, string $label): self
    {
        $v = (string) $this->val($field);
        if ($v !== '' && mb_strlen($v) < $len) {
            $this->errors[$field] = "{$label} tem de ter pelo menos {$len} caracteres.";
        }
        return $this;
    }

    public function max(string $field, int $len, string $label): self
    {
        $v = (string) $this->val($field);
        if (mb_strlen($v) > $len) {
            $this->errors[$field] = "{$label} não pode exceder {$len} caracteres.";
        }
        return $this;
    }

    /** Strong-ish password: ≥8 chars, upper, lower and a digit. */
    public function password(string $field, string $label = 'Palavra-passe'): self
    {
        $v = (string) $this->val($field);
        if ($v === '') {
            return $this;
        }
        if (mb_strlen($v) < 8
            || !preg_match('/[A-Z]/', $v)
            || !preg_match('/[a-z]/', $v)
            || !preg_match('/\d/', $v)) {
            $this->errors[$field] = "{$label} deve ter 8+ caracteres, com maiúscula, minúscula e número.";
        }
        return $this;
    }

    public function matches(string $field, string $other, string $label): self
    {
        if ($this->val($field) !== $this->val($other)) {
            $this->errors[$field] = "{$label} não coincide.";
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !is_numeric($v)) {
            $this->errors[$field] = "{$label} tem de ser um número.";
        } elseif (is_numeric($v)) {
            $this->clean[$field] = $v + 0;
        }
        return $this;
    }

    /** Value must be one of $allowed (enum / whitelist). */
    public function in(string $field, array $allowed, string $label): self
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !in_array($v, $allowed, true)) {
            $this->errors[$field] = "{$label} inválido.";
        }
        return $this;
    }

    public function date(string $field, string $label): self
    {
        $v = $this->val($field);
        if ($v) {
            $d = DateTime::createFromFormat('Y-m-d', $v);
            if (!$d || $d->format('Y-m-d') !== $v) {
                $this->errors[$field] = "{$label} tem uma data inválida.";
            }
        }
        return $this;
    }

    public function fails(): bool   { return $this->errors !== []; }
    public function passes(): bool  { return $this->errors === []; }
    public function errors(): array { return $this->errors; }
    public function firstError(): ?string { return $this->errors[array_key_first($this->errors)] ?? null; }

    /** Return a sanitized value (after validation). */
    public function get(string $field, $default = null)
    {
        $v = $this->clean[$field] ?? $this->val($field) ?? $default;
        return $v;
    }
}
