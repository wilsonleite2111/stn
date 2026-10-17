<?php

use App\Models\PhotoAlbum;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subscriber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('gives every public page a canonical address and share tags', function (string $uri): void {
    $this->get($uri)
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url($uri).'">', escape: false)
        ->assertSee('<meta property="og:url" content="'.url($uri).'">', escape: false)
        ->assertSee('<meta property="og:site_name" content="Soul True Noise">', escape: false)
        ->assertSee('<meta property="og:image" content="'.asset('images/capa.jpg').'">', escape: false)
        ->assertDontSee('noindex');
})->with(['/shows', '/shows/anteriores', '/noticias', '/fotos', '/videos', '/banda', '/loja', '/contato', '/privacidade']);

it('leaves the query string out of the canonical address', function (): void {
    $this->get('/videos?categoria=live')
        ->assertSee('<link rel="canonical" href="'.url('/videos').'">', escape: false);
});

it('describes the band to search engines on the home page', function (): void {
    $this->get('/')
        ->assertSee('"@type":"MusicGroup"', escape: false)
        ->assertSee('"sameAs":["https://www.instagram.com/stn_soul_true_noise"]', escape: false);
});

it('shares the hero image on the home page once one is set', function (): void {
    Setting::write(Setting::HOME_HERO_IMAGES, json_encode(['site/hero.jpg', 'site/second.jpg']));

    $this->get('/')->assertSee('<meta property="og:image" content="'.Storage::disk('public')->url('site/hero.jpg').'">', escape: false);
});

it('shares news as an article with its cover and structured data', function (): void {
    Storage::fake('public');
    $post = Post::factory()->create(['slug' => 'big-news', 'title' => 'Big News', 'excerpt' => 'A short summary.']);
    $post->addMedia(UploadedFile::fake()->image('cover.jpg', 1600, 900))->toMediaCollection(Post::COVER);

    $this->get('/noticias/big-news')
        ->assertSee('<meta property="og:type" content="article">', escape: false)
        ->assertSee('<meta property="og:image" content="'.$post->coverUrl('large').'">', escape: false)
        ->assertSee('<meta name="description" content="A short summary.">', escape: false)
        ->assertSee('"@type":"NewsArticle"', escape: false)
        ->assertSee('"headline":"Big News"', escape: false);
});

it('gives search engines the price and availability of a product', function (bool $inStock, string $availability): void {
    Product::factory()->create(['slug' => 'band-cap', 'price_cents' => 4500, 'stock' => $inStock ? 3 : 0]);

    $this->get('/loja/band-cap')
        ->assertSee('"@type":"Product"', escape: false)
        ->assertSee('"priceCurrency":"BRL","price":"45.00"', escape: false)
        ->assertSee("\"availability\":\"https://schema.org/{$availability}\"", escape: false);
})->with([
    'in stock' => [true, 'InStock'],
    'sold out' => [false, 'OutOfStock'],
]);

it('shares an album with its first photo', function (): void {
    Storage::fake('public');
    $album = PhotoAlbum::factory()->create(['slug' => 'the-album']);
    $album->addMedia(UploadedFile::fake()->image('photo.jpg', 1200, 800))->toMediaCollection(PhotoAlbum::PHOTOS);

    $this->get('/fotos/the-album')
        ->assertSee('<meta property="og:image" content="'.$album->cover()->getUrl('large').'">', escape: false);
});

it('keeps text written in the admin from breaking out of the structured data', function (): void {
    Post::factory()->create(['slug' => 'unsafe', 'title' => '</script><script>alert(1)</script>']);

    $this->get('/noticias/unsafe')->assertDontSee('</script><script>alert(1)</script>', escape: false);
});

it('keeps newsletter pages out of search engines', function (): void {
    $subscriber = Subscriber::factory()->create();

    $this->get("/newsletter/sair/{$subscriber->token}")
        ->assertSee('<meta name="robots" content="noindex">', escape: false)
        ->assertDontSee('rel="canonical"', escape: false);
});
