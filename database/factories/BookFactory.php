<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalStock = fake()->numberBetween(1, 10);

        return [
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'isbn' => fake()->unique()->isbn13(),
            'publication_year' => (int) fake()->year(),
            'total_stock' => $totalStock,
            'available_stock' => $totalStock,
            'cover_path' => null,
            'description' => fake()->paragraph(),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_stock' => 0,
        ]);
    }
}
