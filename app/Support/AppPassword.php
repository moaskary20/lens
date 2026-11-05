<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class AppPassword
{
    public const MESSAGE = 'Password must be at least 8 characters and include letters, numbers, and a symbol.';

    public static function rule(bool $required = true): array
    {
        $complexity = Password::min(8)->letters()->numbers()->symbols();

        return $required
            ? ['required', 'string', $complexity]
            : ['nullable', 'string', $complexity];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $attribute = 'password'): array
    {
        return [
            $attribute.'.required' => self::MESSAGE,
            $attribute.'.min' => self::MESSAGE,
            $attribute.'.password' => self::MESSAGE,
            $attribute.'.letters' => self::MESSAGE,
            $attribute.'.numbers' => self::MESSAGE,
            $attribute.'.symbols' => self::MESSAGE,
            $attribute.'.mixed' => self::MESSAGE,
        ];
    }
}
