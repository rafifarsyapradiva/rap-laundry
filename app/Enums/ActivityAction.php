<?php

namespace App\Enums;

enum ActivityAction: string
{
    case AuthLogin = 'auth.login';
    case AuthLogout = 'auth.logout';
    case OrderCreated = 'order.created';
    case OrderStatusChanged = 'order.status_changed';
    case OrderCancelled = 'order.cancelled';
    case PaymentRecorded = 'payment.recorded';
    case PaymentVoided = 'payment.voided';
    case ServicePriceChanged = 'service.price_changed';
    case SettingUpdated = 'setting.updated';

    public function label(): string
    {
        return match ($this) {
            self::AuthLogin => 'Login',
            self::AuthLogout => 'Logout',
            self::OrderCreated => 'Pesanan dibuat',
            self::OrderStatusChanged => 'Status cucian diubah',
            self::OrderCancelled => 'Pesanan dibatalkan',
            self::PaymentRecorded => 'Pembayaran dicatat',
            self::PaymentVoided => 'Pembayaran dibatalkan (void)',
            self::ServicePriceChanged => 'Harga layanan diubah',
            self::SettingUpdated => 'Pengaturan usaha diubah',
        };
    }
}
