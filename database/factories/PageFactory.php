<?php

namespace Bambamboole\FilamentPages\Database\Factories;

use Bambamboole\FilamentPages\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(rand(2, 4), true);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'parent_id' => null,
        ];
    }

    public function withParent(Page $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
        ]);
    }

    public function withContent(?string $content = null): static
    {
        return $this->state(fn () => [
            'content' => $content ?? fake()->paragraphs(3, true),
        ]);
    }

    public function withBlocks(array $blocks = []): static
    {
        return $this->state(fn () => [
            'blocks' => $blocks ?: [['type' => 'text', 'data' => ['content' => fake()->paragraph()]]],
        ]);
    }
}
