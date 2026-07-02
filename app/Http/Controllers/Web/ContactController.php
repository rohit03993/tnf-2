<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactInquiry;
use App\Services\SeoService;
use App\Support\SiteContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(SeoService $seo): View
    {
        return view('pages.contact', [
            'title' => 'Contact Us',
            'seo' => $seo->forContact(),
            'email' => SiteContact::email(),
            'phone' => SiteContact::phone(),
            'phoneTel' => SiteContact::phoneTel(),
            'company' => SiteContact::company(),
            'address' => SiteContact::address(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $inquiry = $request->validated();

        try {
            Mail::to(SiteContact::email())->send(new ContactInquiry($inquiry));
        } catch (\Throwable $exception) {
            Log::warning('Contact form mail failed', [
                'email' => $inquiry['email'],
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('page.contact')
            ->with('success', 'Thank you for contacting TNF Today. We will get back to you soon.');
    }
}
