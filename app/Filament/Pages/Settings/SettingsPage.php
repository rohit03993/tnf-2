<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

abstract class SettingsPage extends Page
{
    use ManagesSettings;

    public ?array $data = [];

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->fullWidth(false),
            ]);
    }

    protected function getSaveAction(): Action
    {
        return Action::make('save')
            ->label('Save settings')
            ->submit('save');
    }

    protected function getFormActions(): array
    {
        return [$this->getSaveAction()];
    }
}
