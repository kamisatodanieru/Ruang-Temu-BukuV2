<?php

namespace App\Http\Controllers\Staff;

use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\RejectLoanRequest;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Loan::class);

        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');

        $loans = Loan::query()
            ->select([
                'id',
                'member_id',
                'book_id',
                'approved_by',
                'requested_at',
                'borrowed_at',
                'due_at',
                'returned_at',
                'status',
                'rejection_reason',
            ])
            ->with([
                'member:id,name,email,status',
                'book:id,title,author,isbn,available_stock',
                'approver:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $query->where(function ($subQuery) use ($escaped) {
                    $subQuery->whereHas('member', function ($memberQuery) use ($escaped) {
                        $memberQuery->where('name', 'like', "%{$escaped}%")
                            ->orWhere('email', 'like', "%{$escaped}%");
                    })->orWhereHas('book', function ($bookQuery) use ($escaped) {
                        $bookQuery->withTrashed()
                            ->where(function ($innerBookQuery) use ($escaped) {
                                $innerBookQuery->where('title', 'like', "%{$escaped}%")
                                    ->orWhere('isbn', 'like', "%{$escaped}%");
                            });
                    });
                });
            })
            ->when($statusFilter === 'overdue', function ($query) {
                $query->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                    ->whereNull('returned_at')
                    ->where('due_at', '<', now());
            })
            ->when($statusFilter !== '' && $statusFilter !== 'overdue', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $pendingCount = Loan::query()->where('status', LoanStatus::Pending->value)->count();
        $borrowedCount = Loan::query()->where('status', LoanStatus::Borrowed->value)->count();
        $returnPendingCount = Loan::query()->where('status', LoanStatus::ReturnPending->value)->count();

        return view('staff.loans.index', [
            'loans' => $loans,
            'pendingCount' => $pendingCount,
            'borrowedCount' => $borrowedCount,
            'returnPendingCount' => $returnPendingCount,
            'statuses' => LoanStatus::cases(),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function show(Loan $loan): View
    {
        Gate::authorize('view', $loan);

        $loan->load([
            'member:id,name,email,status',
            'book:id,category_id,title,author,publisher,isbn,total_stock,available_stock,cover_path,deleted_at',
            'book.category:id,name',
            'approver:id,name',
            'rejector:id,name',
            'returnConfirmer:id,name',
            'fine.latestPayment',
        ]);

        return view('staff.loans.show', [
            'loan' => $loan,
        ]);
    }

    public function approve(Request $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        Gate::authorize('approve', $loan);

        $loanService->approveLoan($loan, $request->user(), $request);

        return redirect()
            ->back()
            ->with('success', 'Permintaan peminjaman berhasil disetujui. Buku kini berstatus sedang dipinjam.');
    }

    public function reject(RejectLoanRequest $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        Gate::authorize('reject', $loan);

        $loanService->rejectLoan(
            loan: $loan,
            actor: $request->user(),
            reason: (string) $request->validated('rejection_reason'),
            request: $request,
        );

        return redirect()
            ->back()
            ->with('success', 'Permintaan peminjaman telah ditolak.');
    }

    public function returnBook(Request $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        Gate::authorize('returnBook', $loan);

        $loanService->returnLoan($loan, $request->user(), $request);

        return redirect()
            ->back()
            ->with('success', 'Pengembalian buku berhasil dikonfirmasi dan stok tersedia telah diperbarui.');
    }
}
