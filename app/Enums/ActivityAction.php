<?php

namespace App\Enums;

enum ActivityAction: string
{
    case AuthLogin = 'auth.login';
    case AuthLogout = 'auth.logout';
    case CustomerCreated = 'customer.created';
    case CustomerUpdated = 'customer.updated';
    case CustomerArchived = 'customer.archived';
    case CustomerRestored = 'customer.restored';
    case CustomerStatusChanged = 'customer.status_changed';
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
            self::CustomerCreated => 'Pelanggan baru ditambahkan',
            self::CustomerUpdated => 'Data pelanggan diubah',
            self::CustomerArchived => 'Pelanggan diarsipkan',
            self::CustomerRestored => 'Pelanggan dipulihkan',
            self::CustomerStatusChanged => 'Status aktif pelanggan diubah',
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
