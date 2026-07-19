<?php

use App\Support\SiteContact;

$whatsappTel = SiteContact::phoneTel();
$whatsappDigits = $whatsappTel ? preg_replace('/\D+/', '', $whatsappTel) : '';
$whatsappUrl = filled($whatsappDigits)
    ? 'https://wa.me/'.$whatsappDigits.'?text='.rawurlencode('Hi, I would like a TNF Today ePaper subscription.')
    : null;
?>

<x-site.layout :seo="$seo">
    <div class="tnf-page-content mx-auto max-w-lg">
        <div class="rounded-tnf-lg bg-white p-8 text-center shadow-card">
            <p class="text-tnf-xs font-bold uppercase tracking-wider text-tnf-red">Members ePaper</p>
            <h1 class="mt-2 text-tnf-xl font-bold text-tnf-navy">{{ $edition->title }}</h1>

            @if($edition->featuredMedia?->url())
                <img
                    src="{{ $edition->featuredMedia->url() }}"
                    alt=""
                    class="tnf-epaper-card-cover mx-auto mt-6 max-w-xs rounded-tnf-lg opacity-80"
                >
            @endif

            @if($isGuest ?? false)
                <p class="mt-6 text-tnf-base text-tnf-navy">
                    Sign in to read this edition.
                </p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    Older archive editions may be available without an account.
                </p>
            @else
                <p class="mt-6 text-tnf-base text-tnf-navy">
                    This edition is for TNF Today members.
                </p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    Activate your membership to read the full digital newspaper.
                </p>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if($isGuest ?? false)
                    <a href="{{ route('login', ['redirect_to' => request()->url()]) }}" class="tnf-btn-primary">Sign in</a>
                @elseif($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" class="tnf-btn-primary" target="_blank" rel="noopener">
                        Subscribe on WhatsApp
                    </a>
                @else
                    <a href="{{ route('page.contact') }}" class="tnf-btn-primary">Contact for membership</a>
                @endif
                <a href="{{ route('epaper.index') }}" class="tnf-btn-outline">All editions</a>
            </div>

            @unless($isGuest ?? false)
                <p class="mt-4 text-tnf-xs text-tnf-muted">
                    <a href="{{ route('account') }}" class="underline hover:text-tnf-red">My account</a>
                </p>
            @endunless
        </div>
    </div>
</x-site.layout>
