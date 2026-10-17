<?php

namespace App\Http\Controllers;

use App\Models\PhotoAlbum;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Show;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $heroImage = Setting::read(Setting::HOME_HERO_IMAGE);

        return view('home', [
            'heroImageUrl' => filled($heroImage) ? Storage::disk('public')->url($heroImage) : null,
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'MusicGroup',
                'name' => config('app.name'),
                'url' => route('home'),
                'image' => asset('images/capa.jpg'),
                'sameAs' => [config('site.instagram_url')],
            ],
            'tagline' => Setting::read(Setting::HOME_TAGLINE),
            'musicLinks' => Setting::musicLinks(),
            'shows' => Show::query()->published()->upcoming()->oldest('starts_at')->limit(3)->get(),
            // A featured video wins; otherwise the newest one is shown.
            'video' => Video::query()->published()->orderByDesc('is_featured')->latest('published_at')->latest('id')->first(),
            'posts' => Post::query()->published()->with('media')->latest('published_at')->latest('id')->limit(3)->get(),
            'albums' => PhotoAlbum::query()->published()->with('media')->latest('taken_at')->limit(3)->get(),
            'products' => Product::query()->active()->where('is_featured', true)->with(['media', 'variants'])->orderBy('name')->limit(4)->get(),
        ]);
    }
}
