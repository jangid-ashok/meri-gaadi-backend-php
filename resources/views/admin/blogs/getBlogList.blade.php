@if (isset($blogs) && count($blogs) > 0)
    @foreach($blogs as $key => $blog)
        <tr class="text-center">
            <td>
                {{ $blog->title ?? '-' }}
            </td>
            <td>{{ $blog->sub_title ?? '-' }}</td>
            <td>{{ optional($blog->categories->first())->title ?? '-' }}</td>
            <td><span class="badge {{ $blog->status == 'active' ? 'bg-label-primary' : 'bg-label-danger' }} me-1">{{ ucfirst($blog->status) ?? 'Inactive' }}</span></td>
            <td><a class="badge rounded-pill bg-label-info" href="{{ url('admin/blog/gallery/'.$blog->id) }}">Gallery</a></td>
            <td>
                <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                        data-bs-toggle="dropdown">
                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ url('admin/blog/edit/' . $blog->id) }}" data-user="{{ $blog->id }}"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                        <a class="dropdown-item delete-popup-btn" href="javascript:void(0);" data-url="{{ url('admin/blog/delete') }}" data-user="{{ $blog->id }}"><i class="icon-base bx bx-trash me-1"></i> Delete</a>
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr class="text-center">
        <td colspan="10"><strong>No Blogs found</strong></td>
    </tr>
@endif