<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('role');

        if ($user->isAdmin()) {
            return $this->renderAdminDashboard($user);
        }

        if ($user->isPetugas()) {
            return $this->renderPetugasDashboard($user);
        }

        return $this->renderAnggotaDashboard($user);
    }

    private function renderAdminDashboard(User $user): View
    {
        $stats = [
            'total_books' => Book::query()->count(),
            'total_stock' => (int) Book::query()->sum('total_stock'),
            'available_stock' => (int) Book::query()->sum('available_stock'),
            'active_members' => User::query()
                ->whereHas('role', fn ($q) => $q->where('name', RoleName::Anggota->value))
                ->where('status', UserStatus::Active->value)
                ->count(),
            'pending_loans' => Loan::query()
                ->where('status', LoanStatus::Pending->value)
                ->count(),
            'borrowed_loans' => Loan::query()
                ->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                ->count(),
            'overdue_loans' => Loan::query()
                ->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                ->whereNull('returned_at')
                ->where('due_at', '<', now())
                ->count(),
        ];

        $recentLoans = Loan::query()
            ->select(['id', 'member_id', 'book_id', 'requested_at', 'borrowed_at', 'due_at', 'returned_at', 'status'])
            ->with(['member:id,name,email', 'book:id,title'])
            ->latest('id')
            ->limit(6)
            ->get();

        $recentLogs = ActivityLog::query()
            ->select(['id', 'user_id', 'action', 'description', 'created_at'])
            ->with('user:id,name,email')
            ->latest('created_at')
            ->limit(8)
            ->get();

        return view('dashboard.index', [
            'user' => $user,
            'stats' => $stats,
            'recentLoans' => $recentLoans,
            'recentLogs' => $recentLogs,
        ]);
    }

    private function renderPetugasDashboard(User $user): View
    {
        $stats = [
            'pending_loans' => Loan::query()
                ->where('status', LoanStatus::Pending->value)
                ->count(),
            'borrowed_loans' => Loan::query()
                ->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                ->count(),
            'overdue_loans' => Loan::query()
                ->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                ->whereNull('returned_at')
                ->where('due_at', '<', now())
                ->count(),
        ];

        $pendingQueue = Loan::query()
            ->select(['id', 'member_id', 'book_id', 'requested_at', 'status'])
            ->where('status', LoanStatus::Pending->value)
            ->with(['member:id,name,email', 'book:id,title,available_stock'])
            ->oldest('requested_at')
            ->limit(6)
            ->get();

        $returnQueue = Loan::query()
            ->select(['id', 'member_id', 'book_id', 'borrowed_at', 'due_at', 'returned_at', 'status'])
            ->where('status', LoanStatus::ReturnPending->value)
            ->whereNull('returned_at')
            ->with(['member:id,name,email', 'book:id,title'])
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $recentLogs = ActivityLog::query()
            ->select(['id', 'user_id', 'action', 'description', 'created_at'])
            ->where('action', 'like', 'loan.%')
            ->with('user:id,name,email')
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('dashboard.index', [
            'user' => $user,
            'stats' => $stats,
            'pendingQueue' => $pendingQueue,
            'returnQueue' => $returnQueue,
            'recentLogs' => $recentLogs,
        ]);
    }

    private function renderAnggotaDashboard(User $user): View
    {
        $stats = [
            'pending_count' => $user->loans()
                ->where('status', LoanStatus::Pending->value)
                ->count(),
            'borrowed_count' => $user->loans()
                ->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
                ->count(),
            'overdue_count' => $user->overdueLoans()->count(),
            'max_active_loans' => (int) config('library.max_active_loans', 3),
        ];

        $activeLoans = $user->activeLoans()
            ->select(['id', 'member_id', 'book_id', 'requested_at', 'borrowed_at', 'due_at', 'returned_at', 'status'])
            ->with(['book:id,category_id,title,author,cover_path', 'book.category:id,name'])
            ->latest('id')
            ->get();

        $recentHistory = $user->loans()
            ->select(['id', 'member_id', 'book_id', 'requested_at', 'returned_at', 'rejected_at', 'cancelled_at', 'status', 'rejection_reason'])
            ->whereIn('status', [
                LoanStatus::Returned->value,
                LoanStatus::Rejected->value,
                LoanStatus::Cancelled->value,
            ])
            ->with('book:id,title,author')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'user' => $user,
            'stats' => $stats,
            'activeLoans' => $activeLoans,
            'recentHistory' => $recentHistory,
        ]);
    }
}
