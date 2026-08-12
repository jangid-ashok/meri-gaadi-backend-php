@if (isset($blogCategories) && count($blogCategories) > 0)
    @foreach($blogCategories as $key => $category)
        <tr class="text-center">
            <td>
                {{ $category->title ?? '-' }}
            </td>
            <td>{{ $category->sub_title ?? '-' }}</td>
            <td><span class="badge {{ $category->status == 'active' ? 'bg-label-primary' : 'bg-label-danger' }} me-1">{{ ucfirst($category->status ?? 'inactive') }}</span></td>
            <td>
                <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                        data-bs-toggle="dropdown">
                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ url('admin/blog-categories/edit/'.$category->id) }}" data-user="{{ $category->id }}"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                        <a class="dropdown-item delete-popup-btn" href="javascript:void(0);" data-url="{{ url('admin/blog-categories/delete') }}" data-user="{{ $category->id }}"><i class="icon-base bx bx-trash me-1"></i> Delete</a>
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr class="text-center">
        <td colspan="5">No Categories found</td>
    </tr>
@endif