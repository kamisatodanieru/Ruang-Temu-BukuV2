<?php

namespace Database\Seeders;

use App\Enums\LoanStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Role;
use App\Models\User;
use App\Services\FinePaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FineTestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('FineTestSeeder dilewati pada lingkungan production.');

            return;
        }

        $this->call(RoleSeeder::class);

        $roleAnggota = Role::query()->where('name', RoleName::Anggota->value)->firstOrFail();
        $plainPassword = (string) (env('FINE_TEST_USER_PASSWORD') ?: Str::password(16));

        $user = User::query()->firstOrNew(['email' => 'denda@ruangtemubuku.local']);
        $user->name = 'Budi Penguji Denda';
        $user->password = $plainPassword;
        $user->role_id = $roleAnggota->id;
        $user->status = UserStatus::Active;
        $user->save();

        $category = Category::query()->firstOrCreate(['name' => 'Pengembangan Diri']);

        $book = Book::query()->first();
        if ($book === null) {
            $book = new Book([
                'category_id' => $category->id,
                'title' => 'Filosofi Teras',
                'author' => 'Henry Manampiring',
                'publisher' => 'Penerbit Buku Kompas',
                'isbn' => '9786024125189',
                'publication_year' => 2018,
                'total_stock' => 5,
                'description' => 'Filsafat Yunani-Romawi Kuno untuk mental tangguh masa kini.',
            ]);
            $book->available_stock = 4;
            $book->save();
        }

        $loan = new Loan([
            'member_id' => $user->id,
            'book_id' => $book->id,
            'requested_at' => now()->subDays(12),
        ]);
        $loan->borrowed_at = now()->subDays(12);
        $loan->due_at = now()->subDays(5)->addMinutes(30);
        $loan->status = LoanStatus::Borrowed;
        $loan->save();

        $fine = app(FinePaymentService::class)->ensureFineForLoan($loan);

        $this->command?->info('Berhasil membuat akun uji denda:');
        $this->command?->info("- Email      : {$user->email}");
        $this->command?->info("- Password   : {$plainPassword}");
        $this->command?->info("- Buku       : {$book->title} (Loan #{$loan->id})");
        $this->command?->info("- Keterlambatan: {$fine?->overdue_days} hari");
        $this->command?->info("- Total Denda: {$fine?->formattedAmount()} (Fine #{$fine?->id})");
    }
}
