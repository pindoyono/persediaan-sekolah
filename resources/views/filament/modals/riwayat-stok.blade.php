<div class="space-y-5 p-1">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/40">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold tracking-wide text-gray-800 dark:text-gray-100">Informasi Barang</h3>
            <span class="inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-semibold {{ $currentStock <= $item->min_stock ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                Stok: {{ $currentStock }} {{ $item->satuan }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kode</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $item->kode }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $item->name }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kategori</dt>
                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $item->category->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Sumber Dana</dt>
                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ is_string($item->sumber_dana) ? $item->sumber_dana : $item->sumber_dana->value }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Satuan</dt>
                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $item->satuan }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Min. Stok</dt>
                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $item->min_stock }} {{ $item->satuan }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Transaksi</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-100">{{ $histories->count() }} transaksi</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900/40">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="text-sm font-semibold tracking-wide text-gray-800 dark:text-gray-100">Riwayat Transaksi</h3>
        </div>

        @if($histories->isEmpty())
            <div class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada riwayat transaksi untuk barang ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[920px] w-full table-auto text-sm text-gray-700 dark:text-gray-200">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600 dark:bg-gray-800/80 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">No</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Tanggal</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Kode Transaksi</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Tipe</th>
                            <th class="px-4 py-3 text-right font-semibold whitespace-nowrap">Qty</th>
                            <th class="px-4 py-3 text-right font-semibold whitespace-nowrap">Stok Kumulatif</th>
                            <th class="px-4 py-3 text-left font-semibold">Keterangan</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Dibuat Oleh</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($histories as $i => $detail)
                            @php
                                $tipe = is_string($detail->transaction->type) ? $detail->transaction->type : $detail->transaction->type->value;
                            @endphp
                            <tr class="bg-white odd:bg-white even:bg-gray-50/60 hover:bg-gray-100/70 dark:bg-transparent dark:odd:bg-transparent dark:even:bg-gray-800/40 dark:hover:bg-gray-800/70">
                                <td class="px-4 py-3 align-top text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 align-top whitespace-nowrap">{{ $detail->transaction->tanggal->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 align-top whitespace-nowrap font-mono text-xs">{{ $detail->transaction->kode }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold {{ $tipe === 'IN' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' }}">
                                        {{ $tipe === 'IN' ? 'IN' : 'OUT' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-right font-semibold {{ $tipe === 'IN' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $tipe === 'IN' ? '+' : '-' }}{{ $detail->qty }}
                                </td>
                                <td class="px-4 py-3 align-top text-right font-bold {{ $detail->running_balance <= 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-100' }}">
                                    {{ $detail->running_balance }}
                                </td>
                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">{{ $detail->transaction->keterangan ?: '-' }}</td>
                                <td class="px-4 py-3 align-top whitespace-nowrap">{{ $detail->transaction->creator->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">Ringkasan</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-gray-800 dark:text-gray-100">{{ $histories->count() }} transaksi</td>
                            <td class="px-4 py-3 text-right text-sm font-bold {{ $currentStock <= 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $currentStock }}</td>
                            <td colspan="2" class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">stok akhir</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
