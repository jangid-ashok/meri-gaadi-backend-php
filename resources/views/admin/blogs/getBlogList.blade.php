@forelse($blogs as $blog)
    <tr>
        <td>{{ $blog->title ?? '-' }}</td>
        <td>{{ $blog->sub_title ?? '-' }}</td>
        <td>{{ optional($blog->categories->first())->title ?? '-' }}</td>
        <td><span class="badge {{ $blog->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">{{ ucfirst($blog->status ?? 'inactive') }}</span></td>
        <td><a class="badge rounded-pill bg-label-info" href="{{ url('admin/blog/gallery/'.$blog->id) }}">Gallery</a></td>
        <td class="text-end text-nowrap">
            <div class="dropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-label="Actions for {{ $blog->title }}" aria-expanded="false">Actions</button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ url('admin/blog/edit/'.$blog->id) }}">Edit</a>
                    <button class="dropdown-item delete-popup-btn" type="button" data-url="{{ url('admin/blog/delete') }}" data-user="{{ $blog->id }}">Delete</button>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="6" class="text-center py-5"><strong>{{ request()->filled('search') ? 'No blogs match your search.' : 'No blogs have been added yet.' }}</strong></td></tr>
@endforelse
