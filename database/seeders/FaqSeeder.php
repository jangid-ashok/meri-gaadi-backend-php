<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::query()->where('category_type', 'content')->where('slug', 'buying-guides')->first();

        foreach ([
            ['question' => 'What is the difference between petrol and diesel cars?', 'answer' => 'Petrol engines are usually smoother and cheaper to buy, while diesel engines are more efficient for longer highway driving and towing.', 'status' => 'active', 'sort_order' => 1, 'category_id' => $category?->id ?? null],
            ['question' => 'What should I check before buying a used car?', 'answer' => 'Check the service history, ownership documents, accident records, tyre condition, engine health, and a pre-purchase inspection.', 'status' => 'active', 'sort_order' => 2, 'category_id' => $category?->id ?? null],
            ['question' => 'How often should I service my car?', 'answer' => 'Most cars need servicing every 6 to 12 months or according to the manufacturer schedule, whichever comes first.', 'status' => 'active', 'sort_order' => 3, 'category_id' => $category?->id ?? null],
            ['question' => 'What is the difference between manual and automatic transmission?', 'answer' => 'Manual cars require driver gear changes, while automatic transmissions shift gears automatically for convenience and ease of driving.', 'status' => 'active', 'sort_order' => 4, 'category_id' => $category?->id ?? null],
            ['question' => 'What is an EV?', 'answer' => 'An EV, or electric vehicle, runs on electricity instead of petrol or diesel and is typically charged from a power outlet or charging station.', 'status' => 'active', 'sort_order' => 5, 'category_id' => $category?->id ?? null],
            ['question' => 'How is car mileage calculated?', 'answer' => 'Mileage is usually calculated by dividing total distance travelled by the fuel consumed, giving kilometres per litre or miles per gallon.', 'status' => 'active', 'sort_order' => 6, 'category_id' => $category?->id ?? null],
            ['question' => 'What documents are required when buying a used car?', 'answer' => 'Typically, you need the registration certificate, previous ownership records, a valid insurance policy, and a clear inspection report.', 'status' => 'active', 'sort_order' => 7, 'category_id' => $category?->id ?? null],
            ['question' => 'What is the difference between AWD and FWD?', 'answer' => 'AWD sends power to all wheels for better traction in difficult conditions, while FWD powers only the front wheels and is common in everyday cars.', 'status' => 'active', 'sort_order' => 8, 'category_id' => $category?->id ?? null],
        ] as $faqData) {
            $slugBase = $faqData['question'];
            $slug = \Illuminate\Support\Str::slug($slugBase) ?: 'faq';
            $candidate = $slug;
            $suffix = 2;

            while (Faq::withTrashed()->where('slug', $candidate)->exists()) {
                $candidate = $slug.'-'.$suffix++;
            }

            Faq::updateOrCreate(
                ['question' => $faqData['question']],
                [
                    'category_id' => $faqData['category_id'],
                    'answer' => $faqData['answer'],
                    'slug' => $candidate,
                    'status' => $faqData['status'],
                    'sort_order' => $faqData['sort_order'],
                ]
            );
        }
    }
}
