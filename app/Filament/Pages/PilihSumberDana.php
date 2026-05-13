<?php

namespace App\Filament\Pages;

use App\Enums\SumberDana;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PilihSumberDana extends Page
{
    protected string $view = 'filament.pages.pilih-sumber-dana';
    protected static ?string $title = 'Pilih Sumber Dana';
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        if (session()->has('sumber_dana')) {
            $this->redirect(filament()->getHomeUrl());
        }
    }

    public function pilih(string $nilai): void
    {
        $sumberDana = SumberDana::from($nilai);

        session(['sumber_dana' => $sumberDana->value]);

        Notification::make()
            ->success()
            ->title('Sumber Dana Dipilih')
            ->body("Sesi ini menggunakan anggaran {$sumberDana->value}.")
            ->send();

        $this->redirect(filament()->getHomeUrl());
    }
}
