<?php

namespace App\Models;

use App\Models\Concerns\ResolvesPublicAssetUrl;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use ResolvesPublicAssetUrl;

    /**
     * Campos permitidos para asignaciÃ³n masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trade_name',
        'legal_name',
        'tax_id',
        'website',
        'email',
        'phone',
        'whatsapp',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'currency_code',
        'timezone',
        'tax_rate',
        'invoice_prefix',
        'invoice_next_number',
        'logo',
        'favicon',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'youtube_url',
        'store_enabled',
        'maintenance_message',
    ];

    /**
     * ConversiÃ³n automÃ¡tica de tipos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'invoice_next_number' => 'integer',
            'store_enabled' => 'boolean',
        ];
    }

    /**
     * Obtiene o crea la configuraciÃ³n principal de la empresa.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            [],
            [
                'trade_name' => config('app.name', 'Shopping Admin'),
                'country' => 'PerÃº',
                'currency_code' => 'PEN',
                'timezone' => 'America/Lima',
                'tax_rate' => 0,
                'invoice_prefix' => 'FAC',
                'invoice_next_number' => 1,
                'store_enabled' => true,
            ]
        );
    }

    /**
     * URL pública del logotipo empresarial.
     */
    protected function logoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): ?string =>
                $this->resolvePublicAssetUrl(
                    $this->attributes['logo'] ?? null
                )
        );
    }

    /**
     * URL pública del favicon.
     */
    protected function faviconUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): ?string =>
                $this->resolvePublicAssetUrl(
                    $this->attributes['favicon'] ?? null
                )
        );
    }}