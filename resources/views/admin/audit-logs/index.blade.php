<x-app-layout title="Catatan Aktivitas" header="Catatan Aktivitas">
    <div class="space-y-5">
        <!-- Pencarian & Saring -->
        <div class="rtb-card rounded-2xl p-5">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2">
                    <label for="search" class="sr-only">Cari aktivitas atau pengguna</label>
                    <input id="search" name="search" type="text" value="{{ $filters['search'] }}"
                           placeholder="Cari aktivitas atau nama/email pengguna..."
                           class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label for="action" class="sr-only">Pilih Jenis Aktivitas</label>
                    <select id="action" name="action" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="">Semua Aktivitas</option>
                        @foreach ($availableActions as $act)
                            <option value="{{ $act }}" @selected($filters['action'] === $act)>
                                {{ $act }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 px-3.5 rounded-xl text-sm font-medium text-white bg-slate-800 hover:bg-slate-900">
                        Terapkan
                    </button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="py-2 px-3.5 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 whitespace-nowrap">
                        Atur Ulang
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabel Catatan Aktivitas -->
        <div class="rtb-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Waktu</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Pengguna</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Aktivitas</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Objek</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Keterangan</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Alamat IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs text-slate-500">
                                    {{ $log->created_at?->format('d M Y H:i:s') }}
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($log->user)
                                        <div class="font-medium text-slate-900">{{ $log->user->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-xs text-slate-500 italic">Sistem</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-medium bg-slate-100 text-slate-800">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs text-slate-600">
                                    @if ($log->subject_type)
                                        {{ $log->subject_type }} #{{ $log->subject_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-slate-700">
                                    {{ $log->description ?? '-' }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-xs font-mono text-slate-500">
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                    Belum ada catatan aktivitas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
