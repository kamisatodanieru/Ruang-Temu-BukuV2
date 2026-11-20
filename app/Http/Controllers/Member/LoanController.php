<?php

namespace App\Http\Controllers\Member;

use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Models\Loan;
use App\Services\FinePaymentService;
use App\Services\LoanService;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->user();
        $statusFilter = (string) $request->query('status', '');

        $loans = Loan::query()
            ->select([
                'id',
                'member_id',
                'book_id',
                'requested_at',
                'borrowed_at',
                'due_at',
                'returned_at',
                'rejected_at',
                'cancelled_at',
                'status',
                'rejection_reason',
            ])
            ->where('member_id', $member->id)
            ->with(['book:id,category_id,title,author,cover_path', 'book.category:id,name', 'fine:id,loan_id,total_amount,status'])
            ->when($statusFilter === 'overdue', function ($query) {
                $query->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                    ->whereNull('returned_at')
                    ->where('due_at', '<', now());
            })
            ->when($statusFilter !== '' && $statusFilter !== 'overdue', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $activeCount = $member->activeLoans()->count();
        $overdueCount = $member->overdueLoans()->count();
        $maxActiveLoans = (int) config('library.max_active_loans', 3);

        return view('member.loans.index', [
            'loans' => $loans,
            'activeCount' => $activeCount,
            'overdueCount' => $overdueCount,
            'maxActiveLoans' => $maxActiveLoans,
            'statusFilter' => $statusFilter,
            'statuses' => LoanStatus::cases(),
        ]);
    }

    public function show(Loan $loan, MidtransService $midtransService, FinePaymentService $finePaymentService): View
    {
        Gate::authorize('view', $loan);

        $finePaymentService->ensureFineForLoan($loan);

        $loan->load([
            'book:id,category_id,title,author,publisher,isbn,cover_path',
            'book.category:id,name',
            'approver:id,name',
            'rejector:id,name',
            'returnConfirmer:id,name',
            'fine.latestPayment',
        ]);

        if ($loan->fine) {
            $finePaymentService->syncPaymentStatusFromMidtrans($loan->fine);
            $loan->load('fine.latestPayment');
        }

        return view('member.loans.show', [
            'loan' => $loan,
            'midtransClientKey' => $midtransService->getClientKey(),
            'midtransSnapJsUrl' => $midtransService->getSnapJsUrl(),
        ]);
    }

    public function store(StoreLoanRequest $request, LoanService $loanService): RedirectResponse
    {
        Gate::authorize('create', Loan::class);

        $loan = $loanService->createLoanRequest(
            member: $request->user(),
            bookId: (int) $request->validated('book_id'),
            request: $request,
        );

        return redirect()
            ->route('member.loans.show', $loan)
            ->with('success', 'Permintaan peminjaman berhasil diajukan dan sedang menunggu persetujuan petugas.');
    }

    public function cancel(Request $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        Gate::authorize('cancel', $loan);

        $loanService->cancelLoan($loan, $request->user(), $request);

        return redirect()
            ->route('member.loans.index')
            ->with('success', 'Permintaan peminjaman berhasil dibatalkan.');
    }

    public function requestReturn(Request $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        Gate::authorize('requestReturn', $loan);

        $loanService->requestReturn($loan, $request->user(), $request);

        return redirect()
            ->back()
            ->with('success', 'Pengajuan pengembalian berhasil dikirim. Silakan serahkan fisik buku ke meja petugas perpustakaan.');
    }
}
