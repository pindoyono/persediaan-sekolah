<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@sekolah.test'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );

        /** @var InventoryService $inventory */
        $inventory = app(InventoryService::class);

        /** @var TransactionService $trxService */
        $trxService = app(TransactionService::class);

        // Seed categories
        $categories = [
            ['name' => 'Alat Tulis Kantor'],
            ['name' => 'Furniture'],
            ['name' => 'Elektronik'],
            ['name' => 'Kebersihan'],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[] = $inventory->createCategory($cat);
        }

        // Seed items per category
        $itemsData = [
            $createdCategories[0]->id => [
                ['name' => 'Pensil 2B', 'satuan' => 'pcs', 'min_stock' => 20],
                ['name' => 'Pulpen Hitam', 'satuan' => 'pcs', 'min_stock' => 30],
                ['name' => 'Buku Tulis', 'satuan' => 'buah', 'min_stock' => 50],
                ['name' => 'Stapler', 'satuan' => 'buah', 'min_stock' => 5],
            ],
            $createdCategories[1]->id => [
                ['name' => 'Kursi Plastik', 'satuan' => 'buah', 'min_stock' => 10],
                ['name' => 'Meja Guru', 'satuan' => 'buah', 'min_stock' => 3],
            ],
            $createdCategories[2]->id => [
                ['name' => 'Proyektor', 'satuan' => 'unit', 'min_stock' => 2],
                ['name' => 'Printer A4', 'satuan' => 'unit', 'min_stock' => 1],
            ],
            $createdCategories[3]->id => [
                ['name' => 'Sapu', 'satuan' => 'buah', 'min_stock' => 10],
                ['name' => 'Cairan Pel', 'satuan' => 'botol', 'min_stock' => 5],
            ],
        ];

        $createdItems = [];
        foreach ($itemsData as $categoryId => $items) {
            foreach ($items as $itemData) {
                $createdItems[] = $inventory->createItem(array_merge($itemData, ['category_id' => $categoryId]));
            }
        }

        // Seed incoming transactions (stock IN)
        $trxService->create([
            'type'       => 'IN',
            'tanggal'    => now()->subDays(10)->toDateString(),
            'created_by' => $admin->id,
            'keterangan' => 'Penerimaan barang awal tahun',
            'details'    => collect($createdItems)->take(6)->map(fn(Item $item) => [
                'item_id' => $item->id,
                'qty'     => rand(50, 200),
            ])->values()->toArray(),
        ]);

        $trxService->create([
            'type'       => 'IN',
            'tanggal'    => now()->subDays(5)->toDateString(),
            'created_by' => $admin->id,
            'keterangan' => 'Penerimaan barang tambahan',
            'details'    => collect($createdItems)->skip(6)->map(fn(Item $item) => [
                'item_id' => $item->id,
                'qty'     => rand(10, 50),
            ])->values()->toArray(),
        ]);

        // Seed outgoing transaction (stock OUT)
        $trxService->create([
            'type'       => 'OUT',
            'tanggal'    => now()->subDays(2)->toDateString(),
            'created_by' => $admin->id,
            'keterangan' => 'Distribusi ke kelas',
            'details'    => [
                ['item_id' => $createdItems[0]->id, 'qty' => 10],
                ['item_id' => $createdItems[1]->id, 'qty' => 15],
                ['item_id' => $createdItems[2]->id, 'qty' => 20],
            ],
        ]);

        $this->command->info('Seeder completed. Login: admin@sekolah.test / password');
    }
}

