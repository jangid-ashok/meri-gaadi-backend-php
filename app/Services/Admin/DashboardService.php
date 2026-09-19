<?php

namespace App\Services\Admin;

use App\Models\Admin\BlogCategory;
use App\Models\Admin\Blogs;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class DashboardService
{
    public function summary(): array
    {
        return [
            'statistics' => [
                'total_users' => User::query()->count(),
                'total_admins' => User::query()->where('is_admin', true)->count(),
                'total_roles' => Role::query()->count(),
                'total_permissions' => Permission::query()->count(),
                'total_blog_categories' => BlogCategory::query()->where('is_deleted', 'no')->count(),
                'total_blogs' => Blogs::query()->where('is_deleted', 'no')->count(),
                'published_blogs' => Blogs::query()->where('is_deleted', 'no')->where('status', 'active')->count(),
                'draft_blogs' => Blogs::query()->where('is_deleted', 'no')->where('status', 'inactive')->count(),
            ],
            'recent_activity' => [],
        ];
    }
}