<h1 {{ $attributes->merge(['class' => 'font-logo uppercase leading-none']) }}>
    <span class="sr-only">{{ config('app.name') }}</span>

    {{-- Mimics the official logo: metallic letters with the skull standing in for the O. --}}
    <span aria-hidden="true" class="bg-linear-to-b from-white via-brand to-brand-dark bg-clip-text text-transparent">
        S<img src="{{ asset('images/skull.webp') }}" alt="" width="126" height="160" class="mx-[0.02em] inline-block h-[0.86em] w-auto align-[-0.06em]">UL TRUE NOISE
    </span>
</h1>
