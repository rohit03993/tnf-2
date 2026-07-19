<?php

use App\Support\SiteContact;

$whatsappTel = SiteContact::phoneTel();
$whatsappDigits = $whatsappTel ? preg_replace('/\D+/', '', $whatsappTel) : '';
$whatsappUrl = filled($whatsappDigits)
    ? 'https://wa.me/'.$whatsappDigits.'?text='.rawurlencode('नमस्ते, मुझे TNF Today ePaper सदस्यता चाहिए।')
    : null;
?>

<x-site.layout :seo="$seo">
    <div class="tnf-page-content mx-auto max-w-lg">
        <div class="rounded-tnf-lg bg-white p-8 text-center shadow-card">
            <p class="text-tnf-xs font-bold uppercase tracking-wider text-tnf-red">सदस्य ePaper</p>
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
                    इस संस्करण को पढ़ने के लिए साइन इन करें।
                </p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    पुराने संस्करण आर्काइव में बिना अकाउंट के उपलब्ध हो सकते हैं।
                </p>
            @else
                <p class="mt-6 text-tnf-base text-tnf-navy">
                    यह संस्करण TNF Today सदस्यों के लिए है।
                </p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    सदस्यता सक्रिय करें और पूरा डिजिटल अखबार पढ़ें।
                </p>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if($isGuest ?? false)
                    <a href="{{ route('login', ['redirect_to' => request()->url()]) }}" class="tnf-btn-primary">साइन इन</a>
                @elseif($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" class="tnf-btn-primary" target="_blank" rel="noopener">
                        WhatsApp पर सदस्यता लें
                    </a>
                @else
                    <a href="{{ route('page.contact') }}" class="tnf-btn-primary">सदस्यता के लिए संपर्क</a>
                @endif
                <a href="{{ route('epaper.index') }}" class="tnf-btn-outline">सभी संस्करण</a>
            </div>

            @unless($isGuest ?? false)
                <p class="mt-4 text-tnf-xs text-tnf-muted">
                    <a href="{{ route('account') }}" class="underline hover:text-tnf-red">मेरा अकाउंट</a>
                </p>
            @endunless
        </div>
    </div>
</x-site.layout>
