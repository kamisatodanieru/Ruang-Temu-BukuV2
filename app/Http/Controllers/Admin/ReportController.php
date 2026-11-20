<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\ActivityLogger;
use App\Services\CsvSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = $this->validatedFilters($request);

        $loans = $this->buildFilteredQuery($filters)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', [
            'loans' => $loans,
            'statuses' => LoanStatus::cases(),
            'filters' => $filters,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = $this->validatedFilters($request);

        ActivityLogger::record(
            action: 'report.exported_csv',
            description: 'Admin mengekspor laporan peminjaman ke format CSV.',
            subject: null,
            actor: $request->user(),
            request: $request,
        );

        $filename = 'laporan-peminjaman-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'ID Transaksi',
                'Nama Anggota',
                'Email Anggota',
                'Judul Buku',
                'ISBN',
                'Status',
                'Tanggal Pengajuan',
                'Tanggal Pinjam',
                'Tanggal Jatuh Tempo',
                'Tanggal Dikembalikan',
                'Disetujui Oleh',
                'Dikonfirmasi Kembali Oleh',
                'Catatan / Alasan Penolakan',
            ]);

            $this->buildFilteredQuery($filters)
                ->latest('id')
                ->chunk(200, function ($loans) use ($handle): void {
                    foreach ($loans as $loan) {
                        $statusLabel = $loan->isOverdue()
                            ? 'Terlambat'
                            : $loan->status->label();

                        $row = CsvSanitizer::sanitizeRow([
                            $loan->id,
                            $loan->member?->name ?? '-',
                            $loan->member?->email ?? '-',
                            $loan->book?->title ?? '-',
                            $loan->book?->isbn ?? '-',
                            $statusLabel,
                            $loan->requested_at?->format('Y-m-d H:i:s') ?? '-',
                            $loan->borrowed_at?->format('Y-m-d H:i:s') ?? '-',
                            $loan->due_at?->format('Y-m-d H:i:s') ?? '-',
                            $loan->returned_at?->format('Y-m-d H:i:s') ?? '-',
                            $loan->approver?->name ?? '-',
                            $loan->returnConfirmer?->name ?? '-',
                            $loan->rejection_reason ?? '-',
                        ]);

                        fputcsv($handle, $row);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array{search: string, status: string, start_date: string, end_date: string}
     */
    private function validatedFilters(Request $request): array
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:30'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'start_date.date' => 'Format tanggal mulai tidak valid.',
            'end_date.date' => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
        ]);

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'start_date' => (string) $request->query('start_date', ''),
            'end_date' => (string) $request->query('end_date', ''),
        ];
    }

    /**
     * @param  array{search: string, status: string, start_date: string, end_date: string}  $filters
     * @return Builder<Loan>
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        return Loan::query()
            ->select([
                'id',
                'member_id',
                'book_id',
                'approved_by',
                'returned_confirmed_by',
                'requested_at',
                'borrowed_at',
                'due_at',
                'returned_at',
                'status',
                'rejection_reason',
            ])
            ->with([
                'member:id,name,email',
                'book:id,title,author,isbn',
                'approver:id,name',
                'returnConfirmer:id,name',
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $escaped = addcslashes($filters['search'], '%_\\');
                $query->where(function (Builder $subQuery) use ($escaped) {
                    $subQuery->whereHas('member', function (Builder $memberQuery) use ($escaped) {
                        $memberQuery->where('name', 'like', "%{$escaped}%")
                            ->orWhere('email', 'like', "%{$escaped}%");
                    })->orWhereHas('book', function (Builder $bookQuery) use ($escaped) {
                        $bookQuery->withTrashed()
                            ->where(function (Builder $innerBookQuery) use ($escaped) {
                                $innerBookQuery->where('title', 'like', "%{$escaped}%")
                                    ->orWhere('isbn', 'like', "%{$escaped}%");
                            });
                    });
                });
            })
            ->when($filters['status'] === 'overdue', function (Builder $query) {
                $query->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                    ->whereNull('returned_at')
                    ->where('due_at', '<', now());
            })
            ->when($filters['status'] !== '' && $filters['status'] !== 'overdue', function (Builder $query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['start_date'] !== '', function (Builder $query) use ($filters) {
                $start = Carbon::parse($filters['start_date'])->startOfDay();
                $query->where('requested_at', '>=', $start);
            })
            ->when($filters['end_date'] !== '', function (Builder $query) use ($filters) {
                $end = Carbon::parse($filters['end_date'])->endOfDay();
                $query->where('requested_at', '<=', $end);
            });
    }
}
