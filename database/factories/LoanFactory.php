<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => User::factory()->anggota(),
            'book_id' => Book::factory(),
            'requested_at' => now(),
            'status' => LoanStatus::Pending,
        ];
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_by' => User::factory()->petugas(),
            'borrowed_at' => now(),
            'due_at' => now()->addDays((int) config('library.loan_duration_days', 7)),
            'status' => LoanStatus::Borrowed,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_by' => User::factory()->petugas(),
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
            'status' => LoanStatus::Borrowed,
        ]);
    }

    public function returnPending(): static
    {
        return $this->borrowed()->state(fn (array $attributes) => [
            'status' => LoanStatus::ReturnPending,
            'return_requested_at' => now(),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_by' => User::factory()->petugas(),
            'returned_confirmed_by' => User::factory()->petugas(),
            'borrowed_at' => now()->subDays(5),
            'due_at' => now()->addDays(2),
            'returned_at' => now(),
            'status' => LoanStatus::Returned,
        ]);
    }
}
