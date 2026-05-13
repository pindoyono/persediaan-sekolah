<div class="space-y-4 p-1">
    {{-- Info Barang --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Informasi Barang</h3>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div><span class="text-gray-500 dark:text-gray-400">Kode:</span> <span class="font-medium dark:text-white">{{ $item->kode }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Nama:</span> <span class="font-medium dark:text-white">{{ $item->name }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Kategori:</span> <span class="font-medium dark:text-white">{{ $item->category->name ?? '-' }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Satuan:</span> <span class="font-medium dark:text-white">{{ $item->satuan }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Sumber Dana:</span> <span class="font-medium dark:text-white">{{ is_string($item->sumber_dana) ? $item->sumber_dana : $item->sumber_dana->value }}</span></div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Stok Saat Ini:</span>
                <span class="font-bold {{ $currentStock <= $item->min_stock ? 'text-red-500' : 'text-green-500' }}">
                    {{ $currentStock }} {{ $item->satuan }}
                </span>
            </div>
            <div><span class="text-gray-500 dark:text-gray-400">Min. Stok:</span> <span class="font-medium dark:text-white">{{ $item->min_stock }} {{ $item->satuan }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Total Transaksi:</span> <span class="font-medium dark:text-white">{{ $histories->count() }} transaksi</span></div>
        </div>
    </div>

    {{-- Tabel Riwayat --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Riwayat Transaksi</h3>
        @if($histories->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>Belum ada riwayat transaksi untuk barang ini.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-3 py-2">No</th>
                            <th class="px-3 py-2">Tanggal</th>
                            <th class="px-3 py-2">Kode Transaksi</th>
                            <th class="px-3 py-2">Tipe</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2 text-right">Stok Kumulatif</th>
                            <th class="px-3 py-2">Keterangan</th>
                            <th class="px-3 py-2">Dibuat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($histories as $i => $detail)
                            @php
                                $tipe = is_string($detail->transaction->type) ? $detail->transaction->type : $detail->transaction->type->value;
                            @endphp
                            <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-3 py-2 dark:text-gray-200 whitespace-nowrap">{{ $detail->transaction->tanggal->format('d/m/Y') }}</td>
                                <td class="px-3 py-2 font-mono text-xs dark:text-gray-200">{{ $detail->transaction->kode }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                        {{ $tipe === 'IN' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        {{ $tipe === 'IN' ? '▲ IN' : '▼ OUT' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right font-medium
                                    {{ $tipe === 'IN' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $tipe === 'IN' ? '+' : '-' }}{{ $detail->qty }}
                                </td>
                                <td class="px-3 py-2 text-right font-bold
                                    {{ $detail->running_balance <= 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' }}">
                                    {{ $detail->running_balance }}
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ $detail->transaction->keterangan ?: '-' }}</td>
                                <td class="px-3 py-2 dark:text-gray-300 whitespace-nowrap">{{ $detail->transaction->creator->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700 border-t-2 border-gray-300 dark:border-gray-500">
                        <tr>
                            <td colspan="4" class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Total:</td>
                            <td class="px-3 py-2 text-right font-bold dark:text-gray-200">{{ $histories->count() }} transaksi</td>
                            <td class="px-3 py-2 text-right font-bold {{ $currentStock <= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $currentStock }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
