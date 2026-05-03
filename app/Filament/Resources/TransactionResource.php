<?php

namespace App\Filament\Resources;

use App\Exports\TransactionExport;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Item;
use App\Models\Transaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $modelLabel = 'Transaksi';
    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::ArrowsRightLeft;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('type')
                ->label('Tipe Transaksi')
                ->options(['IN' => 'Masuk (IN)', 'OUT' => 'Keluar (OUT)'])
                ->required()
                ->live(),

            DatePicker::make('tanggal')
                ->label('Tanggal')
                ->required()
                ->default(now()),

            Textarea::make('keterangan')
                ->label('Keterangan')
                ->nullable()
                ->rows(2),

            Repeater::make('details')
                ->label('Detail Barang')
                ->schema([
                    Select::make('item_id')
                        ->label('Barang')
                        ->options(Item::query()->with('category')->get()->mapWithKeys(
                            fn(Item $item) => [$item->id => "[{$item->kode}] {$item->name} ({$item->satuan})"]
                        ))
                        ->searchable()
                        ->required(),

                    TextInput::make('qty')
                        ->label('Qty')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                ])
                ->minItems(1)
                ->addActionLabel('Tambah Barang')
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'IN'  => 'success',
                        'OUT' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('creator.name')->label('Dibuat Oleh')->searchable(),
                TextColumn::make('details_count')
                    ->label('Jumlah Item')
                    ->counts('details'),
                TextColumn::make('keterangan')->label('Keterangan')->limit(40),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(['IN' => 'Masuk', 'OUT' => 'Keluar']),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $v) => $q->whereDate('tanggal', '>=', $v))
                            ->when($data['until'], fn($q, $v) => $q->whereDate('tanggal', '<=', $v));
                    }),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon(Heroicon::ArrowDownTray)
                    ->action(function () {
                        return Excel::download(new TransactionExport(), 'transactions-' . now()->format('Ymd-His') . '.xlsx');
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Transactions are immutable after creation
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view'   => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
