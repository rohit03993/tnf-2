<?php

namespace App\Filament\Pages\Settings\Concerns;

use App\Models\Setting;
use Filament\Notifications\Notification;

trait ManagesSettings
{
    /** @return array<string, mixed> */
    abstract protected function settingKeys(): array;

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    /** @return array<string, mixed> */
    protected function loadSettings(): array
    {
        $data = [];

        foreach ($this->settingKeys() as $key => $default) {
            $data[is_int($key) ? $default : $key] = Setting::get(is_int($key) ? $default : $key, $default);
        }

        return $data;
    }

    /** @return list<string> */
    protected function secretKeys(): array
    {
        return [];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (in_array($key, $this->secretKeys(), true) && blank($value)) {
                continue;
            }

            Setting::set($key, $value);
        }

        Notification::make()->title('Settings saved')->success()->send();
    }

}
