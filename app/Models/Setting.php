<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Site-wide content edited in the admin panel, stored as key/value pairs.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const string BAND_HISTORY = 'band.history';

    public const string STORE_WHATSAPP = 'store.whatsapp';

    public const string HOME_HERO_IMAGES = 'home.hero_images';

    public const string HOME_HERO_INTERVAL = 'home.hero_interval';

    public const string HOME_TAGLINE = 'home.tagline';

    public const string CONTACT_EMAIL = 'contact.email';

    public const string MUSIC_LINKS = 'music.links';

    /**
     * Reduces a phone number typed in any format to the digits wa.me expects, adding Brazil's country code when missing.
     */
    public static function normalizeWhatsapp(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (in_array(strlen($digits), [10, 11], true)) {
            $digits = '55'.$digits;
        }

        return in_array(strlen($digits), [12, 13], true) && str_starts_with($digits, '55') ? $digits : null;
    }

    /**
     * Paths on the public disk of the home page hero photos, in carousel order.
     *
     * @return list<string>
     */
    public static function heroImages(): array
    {
        /** @var list<string> $images */
        $images = json_decode(self::read(self::HOME_HERO_IMAGES) ?? '[]', true, flags: JSON_THROW_ON_ERROR);

        return $images;
    }

    /**
     * Seconds each hero photo stays on screen before the carousel moves on.
     */
    public static function heroInterval(): int
    {
        return (int) (self::read(self::HOME_HERO_INTERVAL) ?? 3);
    }

    /**
     * The band's profiles on streaming platforms, in the order set in the admin.
     *
     * @return list<array{name: string, url: string}>
     */
    public static function musicLinks(): array
    {
        /** @var list<array{name: string, url: string}> $links */
        $links = json_decode(self::read(self::MUSIC_LINKS) ?? '[]', true, flags: JSON_THROW_ON_ERROR);

        return $links;
    }

    public static function read(string $key): ?string
    {
        return self::query()->where('key', $key)->value('value');
    }

    public static function write(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
