@props([
    'title' => null,
    'description' => 'Site oficial da banda Soul True Noise: shows, fotos, vídeos, notícias e loja.',
    'image' => null,
    'type' => 'website',
    'noindex' => false,
])

@php
    $navigation = [
        'shows.index' => 'Shows',
        'news.index' => 'Notícias',
        'photos.index' => 'Fotos',
        'videos.index' => 'Vídeos',
        'band' => 'A Banda',
        'store.index' => 'Loja',
        'contact' => 'Contato',
    ];
    $pageTitle = $title ? $title.' — '.config('app.name') : config('app.name');
    $shareImage = $image ?? asset('images/capa.jpg');
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description }}">
        <meta name="theme-color" content="#0e0d10">

        <title>{{ $pageTitle }}</title>

        @if ($noindex)
            <meta name="robots" content="noindex">
        @else
            <link rel="canonical" href="{{ url()->current() }}">
        @endif

        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="pt_BR">
        <meta property="og:type" content="{{ $type }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $shareImage }}">
        <meta name="twitter:card" content="summary_large_image">

        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('head')
    </head>
    <body class="flex min-h-screen flex-col bg-ink font-sans text-white antialiased">
        <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-brand focus:px-4 focus:py-2 focus:text-ink">
            Pular para o conteúdo
        </a>

        <header x-data="{ open: false }" class="fixed inset-x-0 top-0 z-40 border-b border-white/10 bg-ink/95 backdrop-blur">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 lg:h-28 lg:px-8">
                <a href="{{ route('home') }}" class="shrink-0">
                    <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name') }}" width="716" height="657" class="h-16 w-auto lg:h-24">
                </a>

                <nav class="hidden items-center gap-8 lg:flex" aria-label="Principal">
                    @foreach ($navigation as $routeName => $label)
                        <a
                            href="{{ route($routeName) }}"
                            @class([
                                'font-display text-sm font-medium uppercase tracking-widest transition hover:text-brand',
                                'text-brand' => request()->routeIs(Str::before($routeName, '.').'*'),
                            ])
                            @if (request()->routeIs(Str::before($routeName, '.').'*')) aria-current="page" @endif
                        >{{ $label }}</a>
                    @endforeach
                </nav>

                <button
                    type="button"
                    class="-mr-2 p-2 lg:hidden"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open.toString()"
                    aria-controls="menu-mobile"
                    aria-label="Abrir menu"
                >
                    <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path x-show="! open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <nav id="menu-mobile" x-show="open" x-cloak x-transition.opacity class="border-t border-white/10 lg:hidden" aria-label="Principal (celular)">
                <div class="mx-auto flex max-w-7xl flex-col px-4 py-2">
                    @foreach ($navigation as $routeName => $label)
                        <a
                            href="{{ route($routeName) }}"
                            @class([
                                'border-b border-white/5 py-4 font-display text-lg font-medium uppercase tracking-widest last:border-b-0',
                                'text-brand' => request()->routeIs(Str::before($routeName, '.').'*'),
                            ])
                        >{{ $label }}</a>
                    @endforeach
                </div>
            </nav>
        </header>

        <main id="conteudo" class="flex-1 pt-20 lg:pt-28">
            {{ $slot }}
        </main>

        <footer class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-10 text-center lg:flex-row lg:justify-between lg:px-8 lg:text-left">
                <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name') }}" width="716" height="657" loading="lazy" class="h-20 w-auto">

                <div class="flex flex-wrap justify-center gap-x-6 gap-y-3 font-display text-sm font-medium uppercase tracking-widest">
                    <a href="{{ config('site.instagram_url') }}" target="_blank" rel="noopener" class="transition hover:text-brand">
                        Instagram · @stn_soul_true_noise
                    </a>

                    @foreach (\App\Models\Setting::musicLinks() as $musicLink)
                        <a href="{{ $musicLink['url'] }}" target="_blank" rel="noopener" class="transition hover:text-brand">{{ $musicLink['name'] }}</a>
                    @endforeach
                </div>

                <p class="text-sm text-white/60">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
                    <a href="{{ route('privacy') }}" class="underline transition hover:text-brand">Privacidade</a>
                </p>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
