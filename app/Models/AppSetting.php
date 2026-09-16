<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function values(array $except = []): array
    {
        return self::query()->when($except, fn ($query) => $query->whereNotIn('key', $except))->pluck('value', 'key')->all();
    }

    public static function value(string $key): ?string
    {
        return self::query()->where('key', $key)->value('value');
    }
}
