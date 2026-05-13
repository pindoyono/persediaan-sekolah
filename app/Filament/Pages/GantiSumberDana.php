<?php

namespace App\Filament\Pages;

use App\Enums\SumberDana;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GantiSumberDana extends Page
{
    protected string $view = 'filament.pages.pilih-sumber-dana';
    protected static ?string $title = 'Ganti Sumber Dana';
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void {}

    public function pilih(string $nilai): void
    {
        $sumberDana = SumberDana::from($nilai);

        session(['sumber_dana' => $sumberDana->value]);

        Notification::make()
            ->success()
            ->title('Sumber Dana Diubah')
            ->body("Sesi ini sekarang menggunakan anggaran {$sumberDana->value}.")
            ->send();

        $this->redirect(filament()->getHomeUrl());
    }
}
