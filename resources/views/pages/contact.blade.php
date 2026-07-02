<x-site.layout :title="$title" :seo="$seo">
    <div class="tnf-page-content mx-auto max-w-5xl">
        @if(session('success'))
            <div class="mb-6 rounded-tnf-lg border border-green-200 bg-green-50 px-4 py-3 text-tnf-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6">
            <h1 class="tnf-section-title mb-2">Contact Us</h1>
            <p class="max-w-2xl text-tnf-sm text-tnf-muted">
                Reach {{ $company }} for editorial queries, partnerships, advertising, or technical support.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
            <div class="space-y-4">
                <div class="rounded-tnf-lg bg-white p-5 shadow-card">
                    <h2 class="text-tnf-sm font-bold uppercase tracking-wide text-tnf-muted">Publisher</h2>
                    <p class="mt-2 text-tnf-base font-semibold text-tnf-navy">{{ $company }}</p>
                    <p class="mt-1 text-tnf-sm text-tnf-muted">TNF Today — Hindi news, videos, and ePaper</p>
                </div>

                <div class="rounded-tnf-lg bg-white p-5 shadow-card">
                    <h2 class="text-tnf-sm font-bold uppercase tracking-wide text-tnf-muted">Contact details</h2>
                    <ul class="mt-3 space-y-3 text-tnf-sm">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-tnf-gray text-tnf-red" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <span>
                                <span class="block font-semibold text-tnf-navy">Email</span>
                                <a href="mailto:{{ $email }}" class="text-tnf-red hover:text-tnf-red-dark">{{ $email }}</a>
                            </span>
                        </li>
                        @if($phone)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-tnf-gray text-tnf-red" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </span>
                                <span>
                                    <span class="block font-semibold text-tnf-navy">Phone</span>
                                    @if($phoneTel)
                                        <a href="tel:{{ $phoneTel }}" class="text-tnf-red hover:text-tnf-red-dark">{{ $phone }}</a>
                                    @else
                                        <span class="text-tnf-navy">{{ $phone }}</span>
                                    @endif
                                </span>
                            </li>
                        @endif
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-tnf-gray text-tnf-red" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                            </span>
                            <span>
                                <span class="block font-semibold text-tnf-navy">Website</span>
                                <a href="{{ url('/') }}" class="text-tnf-red hover:text-tnf-red-dark">{{ parse_url(url('/'), PHP_URL_HOST) }}</a>
                            </span>
                        </li>
                        @if($address)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-tnf-gray text-tnf-red" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <span>
                                    <span class="block font-semibold text-tnf-navy">Address</span>
                                    <span class="text-tnf-navy">{{ $address }}</span>
                                </span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="rounded-tnf-lg bg-white p-6 shadow-card">
                <h2 class="mb-1 text-tnf-lg font-bold text-tnf-navy">Send us a message</h2>
                <p class="mb-5 text-tnf-sm text-tnf-muted">Fill in the form below and our team will respond as soon as possible.</p>

                <form action="{{ route('page.contact.submit') }}" method="POST" class="tnf-contact-form space-y-4">
                    @csrf

                    <div class="tnf-field">
                        <label for="contact_name" class="tnf-label">Your name</label>
                        <input id="contact_name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                               class="tnf-input @error('name') tnf-input--error @enderror">
                        @error('name')<p class="tnf-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="tnf-field">
                        <label for="contact_email" class="tnf-label">Email address</label>
                        <input id="contact_email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                               class="tnf-input @error('email') tnf-input--error @enderror">
                        @error('email')<p class="tnf-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="tnf-field">
                        <label for="contact_phone" class="tnf-label">Phone <span class="font-normal text-tnf-muted">(optional)</span></label>
                        <input id="contact_phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel"
                               class="tnf-input @error('phone') tnf-input--error @enderror">
                        @error('phone')<p class="tnf-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="tnf-field">
                        <label for="contact_subject" class="tnf-label">Subject</label>
                        <input id="contact_subject" name="subject" type="text" value="{{ old('subject') }}" required
                               class="tnf-input @error('subject') tnf-input--error @enderror">
                        @error('subject')<p class="tnf-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="tnf-field">
                        <label for="contact_message" class="tnf-label">Message</label>
                        <textarea id="contact_message" name="message" rows="5" required
                                  class="tnf-input tnf-input--textarea @error('message') tnf-input--error @enderror">{{ old('message') }}</textarea>
                        @error('message')<p class="tnf-field-error">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="tnf-btn-primary w-full sm:w-auto">Send message</button>
                </form>
            </div>
        </div>
    </div>
</x-site.layout>
