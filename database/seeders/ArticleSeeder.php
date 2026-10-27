<?php

namespace Database\Seeders;

use App\Models\Admin\Blogs as Article;
use App\Models\Admin\Tag;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('is_admin', true)->where('status', 'active')->oldest('id')->first();
        $category = Category::active()->ordered()->first();
        if (! $author || ! $category) return;
        $tags = Tag::active()->ordered()->take(2)->get();

        $samples = [
            ['title' => 'How to Improve Your Car Mileage', 'slug' => 'how-to-improve-your-car-mileage', 'excerpt' => 'Practical driving and maintenance habits that can help you get more from every litre.', 'description' => "Good mileage starts with smooth driving. Accelerate gently, anticipate traffic and keep tyres inflated to the recommended pressure.\n\nFollow the service schedule and remove unnecessary weight from the car. Compare your fuel use over several fill-ups to see which changes help.", 'meta_title' => 'How to Improve Car Mileage', 'meta_description' => 'Simple driving and maintenance tips to improve car fuel efficiency.'],
            ['title' => 'Things to Check Before Buying a Used Car', 'slug' => 'things-to-check-before-buying-a-used-car', 'excerpt' => 'A clear checklist for inspecting a used car before you make an offer.', 'description' => "Check the service history, ownership documents and any outstanding finance. Inspect the body in daylight and look for uneven panel gaps or paint differences.\n\nTake a test drive, check warning lights and arrange an independent inspection before completing the purchase.", 'meta_title' => 'Used Car Buying Checklist', 'meta_description' => 'What to inspect and verify before buying a used car.'],
        ];

        foreach ($samples as $sample) {
            $article = Article::withTrashed()->firstOrNew(['slug' => $sample['slug']]);
            $article->fill($sample + [
                'excerpt' => $sample['excerpt'],
                'user_id' => $author->id,
                'category_id' => $category->id,
                'publication_status' => 'published',
                'status' => 'active',
                'is_deleted' => 'no',
                'deleted_at' => null,
            ]);
            $article->published_at = $article->published_at ?? now();
            $article->save();
            $article->tags()->sync($tags->modelKeys());
        }
    }
}
