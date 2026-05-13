<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SumberDana: string implements HasLabel, HasColor, HasIcon
{
    case BOSNAS = 'BOSNAS';
    case BOP = 'BOP';

    public function getLabel(): string
    {
        return match ($this) {
            self::BOSNAS => 'BOSNAS',
            self::BOP => 'BOP',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::BOSNAS => 'info',
            self::BOP => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::BOSNAS => 'heroicon-o-academic-cap',
            self::BOP => 'heroicon-o-building-library',
        };
    }
}
