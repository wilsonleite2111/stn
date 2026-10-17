<?php

use App\Filament\Pages\ManageSite;
use App\Models\Setting;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('redirects guests away from the site settings', function (): void {
    $this->get('/admin/site')->assertRedirect('/admin/login');
});

it('opens with the saved settings', function (): void {
    Setting::write(Setting::HOME_TAGLINE, 'Rock de verdade');
    Setting::write(Setting::CONTACT_EMAIL, 'banda@example.com');

    $this->actingAs(User::factory()->create())->get('/admin/site')->assertOk();

    Livewire::test(ManageSite::class)->assertSchemaStateSet([
        'tagline' => 'Rock de verdade',
        'contact_email' => 'banda@example.com',
    ]);
});

it('saves the hero photos, carousel interval, tagline and contact address, which the home page then uses', function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSite::class)
        ->assertSchemaStateSet(['hero_interval' => 3])
        ->fillForm([
            'hero_images' => [
                UploadedFile::fake()->image('first.jpg', 2400, 1200),
                UploadedFile::fake()->image('second.jpg', 2400, 1200),
            ],
            'hero_interval' => 5,
            'tagline' => 'Rock de verdade',
            'contact_email' => 'banda@example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Configurações salvas');

    $heroImages = Setting::heroImages();

    expect($heroImages)->toHaveCount(2)
        ->and($heroImages[0])->toStartWith('site/')
        ->and(Setting::heroInterval())->toBe(5)
        ->and(Setting::read(Setting::CONTACT_EMAIL))->toBe('banda@example.com');
    Storage::disk('public')->assertExists($heroImages);

    $this->get('/')
        ->assertSeeInOrder([Storage::disk('public')->url($heroImages[0]), Storage::disk('public')->url($heroImages[1])], escape: false)
        ->assertSee('5000')
        ->assertSee('Rock de verdade');
});

it('accepts at most five hero photos', function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSite::class)
        ->fillForm(['hero_images' => array_map(fn (int $number): UploadedFile => UploadedFile::fake()->image("hero-{$number}.jpg"), range(1, 6))])
        ->call('save')
        ->assertHasFormErrors(['hero_images' => 'max']);

    expect(Setting::heroImages())->toBe([]);
});

it('rejects a carousel interval outside 1 to 60 seconds', function (int $seconds): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSite::class)
        ->fillForm(['hero_interval' => $seconds])
        ->call('save')
        ->assertHasFormErrors(['hero_interval']);
})->with([0, 61]);

it('rejects an invalid contact address', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSite::class)
        ->fillForm(['contact_email' => 'not-an-address'])
        ->call('save')
        ->assertHasFormErrors(['contact_email' => 'email']);

    expect(Setting::read(Setting::CONTACT_EMAIL))->toBeNull();
});

it('rejects a hero upload that is not an image', function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSite::class)
        ->fillForm(['hero_images' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')]])
        ->call('save')
        ->assertHasFormErrors(['hero_images']);
});

it('saves the streaming links, which the home page and footer then link to', function (): void {
    $this->actingAs(User::factory()->create());
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(ManageSite::class)
        ->fillForm([
            'music_links' => [
                ['name' => 'Spotify', 'url' => 'https://open.spotify.com/artist/stn'],
                ['name' => 'Deezer', 'url' => 'https://www.deezer.com/artist/stn'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    expect(Setting::musicLinks())->toBe([
        ['name' => 'Spotify', 'url' => 'https://open.spotify.com/artist/stn'],
        ['name' => 'Deezer', 'url' => 'https://www.deezer.com/artist/stn'],
    ]);

    $this->get('/')
        ->assertSee('Ouça a banda')
        ->assertSeeInOrder(['https://open.spotify.com/artist/stn', 'https://www.deezer.com/artist/stn']);

    $this->get('/contato')->assertSee('https://open.spotify.com/artist/stn');
});

it('rejects a streaming link that is not a web address', function (): void {
    $this->actingAs(User::factory()->create());
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(ManageSite::class)
        ->fillForm(['music_links' => [['name' => 'Spotify', 'url' => 'javascript:alert(1)']]])
        ->call('save')
        ->assertHasFormErrors(['music_links.0.url']);

    $undoRepeaterFake();

    expect(Setting::musicLinks())->toBe([]);
});
