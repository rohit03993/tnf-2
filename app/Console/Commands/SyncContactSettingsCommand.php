<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SyncContactSettingsCommand extends Command
{
    protected $signature = 'tnf:sync-contact-settings';

    protected $description = 'Ensure Contact Us page settings (email, phone, company) are present';

    public function handle(): int
    {
        $defaults = [
            'contact_email' => config('tnf.contact_email', 'contact@tnftoday.com'),
            'contact_phone' => config('tnf.contact_phone', '+19412359817'),
            'contact_company' => config('tnf.contact_company', 'TNF Today Media Network Pvt Ltd'),
            'contact_address' => config('tnf.contact_address', ''),
        ];

        foreach ($defaults as $key => $value) {
            if (blank(Setting::get($key))) {
                Setting::set($key, $value);
                $this->info("Set {$key}");
            } else {
                $this->line("Kept existing {$key}");
            }
        }

        $this->newLine();
        $this->line('Contact page: '.url('/contact-us'));

        return self::SUCCESS;
    }
}
