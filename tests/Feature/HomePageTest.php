<?php

use App\Models\PhotoAlbum;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Show;
use App\Models\Video;

it('renders with no content, offering only the newsletter', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Receba as novidades')
        ->assertDontSee('Vídeo em destaque')
        ->assertDontSee('Agenda completa')
        ->assertDontSee('Todas as notícias')
        ->assertDontSee('Todos os álbuns')
        ->assertDontSee('Ver a loja')
        ->assertDontSee('Ouça a banda');
});

it('shows the official band photo until a hero image is set', function (): void {
    $officialPhoto = '<img src="'.asset('images/capa.jpg').'"';

    $this->get('/')->assertSee($officialPhoto, escape: false);

    Setting::write(Setting::HOME_HERO_IMAGE, 'site/hero.jpg');

    $this->get('/')
        ->assertSee('/storage/site/hero.jpg')
        ->assertDontSee($officialPhoto, escape: false);
});

it('shows the tagline written in the admin, escaped', function (): void {
    Setting::write(Setting::HOME_TAGLINE, 'Rock <b>de</b> verdade');

    $this->get('/')
        ->assertSee('Rock <b>de</b> verdade')
        ->assertDontSee('Rock <b>de</b> verdade', escape: false);
});

it('shows the next three published shows', function (): void {
    foreach ([4, 1, 3, 2] as $weeks) {
        Show::factory()->create(['title' => "Gig In {$weeks}", 'starts_at' => now()->addWeeks($weeks)]);
    }
    Show::factory()->past()->create(['title' => 'Old Gig']);
    Show::factory()->unpublished()->create(['title' => 'Draft Gig']);

    $this->get('/')
        ->assertSeeInOrder(['Gig In 1', 'Gig In 2', 'Gig In 3'])
        ->assertDontSee('Gig In 4')
        ->assertDontSee('Old Gig')
        ->assertDontSee('Draft Gig');
});

it('highlights the featured video over a newer one', function (): void {
    Video::factory()->create(['title' => 'Newest Video', 'published_at' => '2026-09-01']);
    Video::factory()->create(['title' => 'Featured Video', 'published_at' => '2026-01-01', 'is_featured' => true]);

    $this->get('/')->assertSee('Featured Video')->assertDontSee('Newest Video');
});

it('falls back to the newest published video when none is featured', function (): void {
    Video::factory()->create(['title' => 'Older Video', 'published_at' => '2026-01-01']);
    Video::factory()->create(['title' => 'Newest Video', 'published_at' => '2026-09-01']);
    Video::factory()->unpublished()->create(['title' => 'Draft Video', 'published_at' => '2026-10-01', 'is_featured' => true]);

    $this->get('/')
        ->assertSee('Newest Video')
        ->assertDontSee('Older Video')
        ->assertDontSee('Draft Video');
});

it('shows the latest visible news', function (): void {
    Post::factory()->create(['title' => 'Fresh News']);
    Post::factory()->scheduled()->create(['title' => 'Future News']);
    Post::factory()->unpublished()->create(['title' => 'Draft News']);

    $this->get('/')
        ->assertSee('Fresh News')
        ->assertDontSee('Future News')
        ->assertDontSee('Draft News');
});

it('shows the latest published albums', function (): void {
    PhotoAlbum::factory()->create(['title' => 'Fresh Album']);
    PhotoAlbum::factory()->unpublished()->create(['title' => 'Draft Album']);

    $this->get('/')->assertSee('Fresh Album')->assertDontSee('Draft Album');
});

it('shows only featured active products', function (): void {
    Product::factory()->create(['name' => 'Featured Shirt', 'is_featured' => true]);
    Product::factory()->create(['name' => 'Regular Shirt']);
    Product::factory()->inactive()->create(['name' => 'Hidden Shirt', 'is_featured' => true]);

    $this->get('/')
        ->assertSee('Featured Shirt')
        ->assertDontSee('Regular Shirt')
        ->assertDontSee('Hidden Shirt');
});

it('shows each streaming platform with its icon, or a generic one when unknown', function (): void {
    Setting::write(Setting::MUSIC_LINKS, json_encode([
        ['name' => 'YouTube Music', 'url' => 'https://music.youtube.com/channel/stn'],
        ['name' => 'Rádio da Esquina', 'url' => 'https://radio.example.com/stn'],
    ]));

    $youtubeMusicPath = 'M12 0C5.376 0 0 5.376 0 12s5.376 12 12 12';

    $this->get('/')
        ->assertSeeInOrder(['https://music.youtube.com/channel/stn', $youtubeMusicPath, 'YouTube Music'], escape: false)
        ->assertSeeInOrder(['https://radio.example.com/stn', 'data-slot="icon"', 'Rádio da Esquina'], escape: false);
});
