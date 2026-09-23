<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        [$group, $name] = self::splitKey($key);

        return Cache::remember("setting.{$group}.{$name}", 60, function () use ($group, $name, $default) {
            $row = static::query()->where('group', $group)->where('key', $name)->first();

            return $row?->value ?? $default;
        });
    }

    public static function setValue(string $key, mixed $value): void
    {
        [$group, $name] = self::splitKey($key);

        static::query()->updateOrCreate(
            ['group' => $group, 'key' => $name],
            ['value' => $value],
        );

        Cache::forget("setting.{$group}.{$name}");
        Cache::forget("setting.group.{$group}");
    }

    public static function groupValues(string $group): array
    {
        return Cache::remember("setting.group.{$group}", 60, function () use ($group) {
            return static::query()->where('group', $group)->pluck('value', 'key')->all();
        });
    }

    public static function setGroupValues(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value],
            );
            Cache::forget("setting.{$group}.{$key}");
        }

        Cache::forget("setting.group.{$group}");
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected static function splitKey(string $key): array
    {
        if (! str_contains($key, '.')) {
            return ['general', $key];
        }

        return explode('.', $key, 2);
    }
}
