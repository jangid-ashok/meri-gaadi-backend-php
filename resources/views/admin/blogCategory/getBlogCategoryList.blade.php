@forelse($blogCategories as $category)
    <tr>
        <td>{{ $category->title ?? '-' }}</td>
        <td>{{ $category->sub_title ?? '-' }}</td>
        <td><span class="badge {{ $category->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">{{ ucfirst($category->status ?? 'inactive') }}</span></td>
        <td class="text-end text-nowrap">
            <div class="dropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-label="Actions for {{ $category->title }}" aria-expanded="false">Actions</button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ url('admin/blog-categories/edit/'.$category->id) }}">Edit</a>
                    <button class="dropdown-item delete-popup-btn" type="button" data-url="{{ url('admin/blog-categories/delete') }}" data-user="{{ $category->id }}">Delete</button>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="4" class="text-center py-5"><strong>{{ request()->filled('search') ? 'No categories match your search.' : 'No categories have been added yet.' }}</strong></td></tr>
@endforelse
