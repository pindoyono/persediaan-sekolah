<x-filament-panels::page>
    <div class="flex min-h-[60vh] flex-col items-center justify-center gap-8">
        <div class="space-y-2 text-center">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Selamat datang, {{ auth()->user()->name }} 👋
            </h2>
            <p class="text-base text-gray-500 dark:text-gray-400">
                Silakan pilih <strong>Sumber Dana</strong> untuk sesi kerja ini.<br>
                Semua input barang dan transaksi akan otomatis tercatat sesuai pilihan Anda.
            </p>
        </div>

        <div class="grid w-full max-w-lg grid-cols-1 gap-6 sm:grid-cols-2">
            <button
                wire:click="pilih('BOSNAS')"
                wire:loading.attr="disabled"
                @class([
                    'group flex cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl border-2 p-8 shadow-sm transition-all duration-200 hover:shadow-md',
                    'border-info-500 bg-info-50 ring-4 ring-info-300 dark:border-info-600 dark:bg-info-950 dark:ring-info-700' => session('sumber_dana') === 'BOSNAS',
                    'border-info-300 bg-info-50 hover:border-info-500 hover:bg-info-100 dark:border-info-700 dark:bg-info-950 dark:hover:bg-info-900' => session('sumber_dana') !== 'BOSNAS',
                ])
            >
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-info-500">
                    <x-heroicon-o-academic-cap class="h-9 w-9 text-white" />
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-info-700 dark:text-info-300">BOSNAS</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bantuan Operasional Sekolah Nasional</div>
                </div>
                @if (session('sumber_dana') === 'BOSNAS')
                    <span class="text-xs font-semibold text-info-600 dark:text-info-400">✓ Aktif Saat Ini</span>
                @endif
            </button>

            <button
                wire:click="pilih('BOP')"
                wire:loading.attr="disabled"
                @class([
                    'group flex cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl border-2 p-8 shadow-sm transition-all duration-200 hover:shadow-md',
                    'border-success-500 bg-success-50 ring-4 ring-success-300 dark:border-success-600 dark:bg-success-950 dark:ring-success-700' => session('sumber_dana') === 'BOP',
                    'border-success-300 bg-success-50 hover:border-success-500 hover:bg-success-100 dark:border-success-700 dark:bg-success-950 dark:hover:bg-success-900' => session('sumber_dana') !== 'BOP',
                ])
            >
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success-500">
                    <x-heroicon-o-building-library class="h-9 w-9 text-white" />
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-success-700 dark:text-success-300">BOP</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Biaya Operasional Pendidikan</div>
                </div>
                @if (session('sumber_dana') === 'BOP')
                    <span class="text-xs font-semibold text-success-600 dark:text-success-400">✓ Aktif Saat Ini</span>
                @endif
            </button>
        </div>

        <p class="text-center text-xs text-gray-400">
            Anda dapat mengganti sumber dana kapan saja melalui menu profil di pojok kanan atas.
        </p>
    </div>
</x-filament-panels::page>
