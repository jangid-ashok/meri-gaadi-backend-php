<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefactorBlogCategoriesAndIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blog_blog_category', function (Blueprint $table) {
            $table->unsignedBigInteger('blog_id');
            $table->unsignedBigInteger('blog_category_id');
            $table->timestamps();

            $table->primary(['blog_id', 'blog_category_id']);
            $table->foreign('blog_id')->references('id')->on('blogs')->cascadeOnDelete();
            $table->foreign('blog_category_id')->references('id')->on('blog_categories')->cascadeOnDelete();
            $table->index('blog_category_id');
            $table->index('blog_id');
        });

        $this->backfillPivotFromCategoryIds();

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('category_ids');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('status');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->index(['status', 'published_at'], 'blogs_status_published_at_index');
        });

        $this->backfillPublishedAt();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropIndex('blogs_status_published_at_index');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'published_at']);
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->string('category_ids')->after('description');
        });

        $this->restoreCategoryIdsFromPivot();

        Schema::dropIfExists('blog_blog_category');
    }

    protected function backfillPivotFromCategoryIds(): void
    {
        if (! Schema::hasColumn('blogs', 'category_ids')) {
            return;
        }

        $blogs = DB::table('blogs')->select('id', 'category_ids')->get();

        foreach ($blogs as $blog) {
            $raw = (string) $blog->category_ids;
            $ids = array_filter(array_map('trim', preg_split('/[,\s]+/', $raw) ?: []));

            foreach ($ids as $cid) {
                if (! ctype_digit((string) $cid)) {
                    continue;
                }

                DB::table('blog_blog_category')->updateOrInsert(
                    [
                        'blog_id' => $blog->id,
                        'blog_category_id' => (int) $cid,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    protected function backfillPublishedAt(): void
    {
        DB::table('blogs')
            ->whereNull('published_at')
            ->where('status', 'active')
            ->update(['published_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    protected function restoreCategoryIdsFromPivot(): void
    {
        $rows = DB::table('blog_blog_category')
            ->orderBy('blog_id')
            ->orderBy('blog_category_id')
            ->get()
            ->groupBy('blog_id');

        $allBlogIds = DB::table('blogs')->pluck('id');

        foreach ($allBlogIds as $blogId) {
            $ids = isset($rows[$blogId]) ? $rows[$blogId]->pluck('blog_category_id')->implode(',') : '';
            DB::table('blogs')->where('id', $blogId)->update(['category_ids' => $ids]);
        }
    }
}
