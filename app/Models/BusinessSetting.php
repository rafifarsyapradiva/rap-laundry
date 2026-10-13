<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    public const KEY_BUSINESS_NAME = 'business_name';

    public const KEY_BUSINESS_ADDRESS = 'business_address';

    public const KEY_BUSINESS_PHONE = 'business_phone';

    public const KEY_BUSINESS_LOGO_PATH = 'business_logo_path';

    public const KEY_ORDER_NUMBER_PREFIX = 'order_number_prefix';

    public const KEY_RECEIPT_FOOTER_NOTE = 'receipt_footer_note';

    public const KEY_DEFAULT_ESTIMATED_HOURS = 'default_estimated_hours';

    public const KEY_PRINT_FORMAT = 'print_format';

    public const KEY_OPERATING_HOURS = 'operating_hours';

    public const KEY_CASHIER_MAX_DISCOUNT_PERCENT = 'cashier_max_discount_percent';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::query()->where('setting_key', $key)->first();

        return $setting?->setting_value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
    }
}
