<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Get setting value with proper type casting
    public function getValueAttribute($value)
    {
        return $this->castValue($value, $this->type);
    }

    // Set setting value
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = $this->serializeValue($value, $this->type);
    }

    // Cast value to appropriate type
    private function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }

    // Serialize value for storage
    private function serializeValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
            case 'array':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    // Static method to get setting value
    public static function get($key, $default = null, $group = 'app')
    {
        $cacheKey = "system_setting_{$group}_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $group) {
            $setting = static::where('group', $group)->where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    // Static method to set setting value
    public static function set($key, $value, $group = 'app', $type = 'string', $description = null)
    {
        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description
            ]
        );

        // Clear cache
        $cacheKey = "system_setting_{$group}_{$key}";
        Cache::forget($cacheKey);

        return $setting;
    }

    // Get all settings for a group
    public static function getGroup($group)
    {
        $cacheKey = "system_settings_group_{$group}";
        
        return Cache::remember($cacheKey, 3600, function () use ($group) {
            return static::where('group', $group)->get()->pluck('value', 'key');
        });
    }

    // Clear cache when model is updated
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("system_setting_{$setting->group}_{$setting->key}");
            Cache::forget("system_settings_group_{$setting->group}");
        });

        static::deleted(function ($setting) {
            Cache::forget("system_setting_{$setting->group}_{$setting->key}");
            Cache::forget("system_settings_group_{$setting->group}");
        });
    }

    // Scopes
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
