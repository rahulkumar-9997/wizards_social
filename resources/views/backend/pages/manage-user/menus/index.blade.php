@extends('backend.pages.layouts.master')
@section('title','Menus List')
@section('main-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h3 class="card-title mb-0">Menu Management</h3>
                    <div class="float-end">
                        <a href="{{ route('menus.create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-plus"></i> Add Menu
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="menusTable">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Menu Name</th>
                                    <th>Type</th>
                                    <th>Roles</th>
                                    <th>URL</th>
                                    <th>Sidebar Display Status</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable">
                                @foreach($menus as $menu)
                                <!-- Parent Menu Row -->
                                <tr data-id="{{ $menu->id }}" class="parent-menu">
                                    <td>{{ $menus->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($menu->icon)
                                            <iconify-icon icon="{{ $menu->icon }}" width="20" height="20" class="me-2"></iconify-icon>
                                            @endif
                                            <strong>{{ $menu->name }}</strong>
                                            @if($menu->children->count())
                                            <span class="badge bg-info ms-2">{{ $menu->children->count() }} children</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">Parent</span>
                                    </td>
                                    <td>
                                        @forelse($menu->roles as $role)
                                        <span class="badge bg-success text-light mb-1">{{ $role->name }}</span>
                                        @empty
                                        <span class="text-muted">No Role</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        {{ $menu->url ?? '#' }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input type="checkbox"
                                                class="form-check-input sidebar-status-toggle"
                                                data-id="{{ $menu->id }}"
                                                data-url="{{ route('menus.sidebar-status', $menu) }}"
                                                {{ $menu->display_sidebar_status ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="order-handle">
                                        <i class="ti ti-arrows-sort"></i> {{ $menu->order }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input type="checkbox"
                                                class="form-check-input status-toggle"
                                                data-id="{{ $menu->id }}"
                                                data-url="{{ route('menus.status', $menu) }}"
                                                {{ $menu->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('menus.edit', $menu) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            @if($menu->children->count() == 0)
                                            <form action="{{ route('menus.destroy', $menu) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger show_confirm" data-name="{{ $menu->name }}" title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                            @else
                                            <button class="btn btn-sm btn-secondary" title="Has children - cannot delete" disabled>
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <!-- Child Menus Rows -->
                                @foreach($menu->children as $child)
                                <tr data-id="{{ $child->id }}" class="child-menu">
                                    <td></td>
                                    <td>
                                        <div class="d-flex align-items-center ms-4">
                                            @if($child->icon)
                                            <iconify-icon icon="{{ $child->icon }}" width="16" height="16" class="me-2"></iconify-icon>
                                            @else
                                            <iconify-icon icon="mdi:circle-small" width="16" height="16" class="me-2 text-muted"></iconify-icon>
                                            @endif
                                            <span>{{ $child->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Child</span>
                                    </td>
                                    <td>
                                        @forelse($child->roles as $role)
                                        <span class="badge bg-success text-light mb-1">{{ $role->name }}</span>
                                        @empty
                                        <span class="text-muted">No Role</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        {{ $child->url ?? '#' }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input type="checkbox"
                                                class="form-check-input sidebar-status-toggle"
                                                data-id="{{ $child->id }}"
                                                data-url="{{ route('menus.sidebar-status', $child->id) }}"
                                                {{ $child->display_sidebar_status ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="order-handle">
                                        <i class="ti ti-arrows-sort"></i> {{ $child->order }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input type="checkbox"
                                                class="form-check-input status-toggle"
                                                data-id="{{ $child->id }}"
                                                data-url="{{ route('menus.status', $child) }}"
                                                {{ $child->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('menus.edit', $child) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <form action="{{ route('menus.destroy', $child) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger show_confirm" data-name="{{ $child->name }}" title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $menus->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script>
    $(document).ready(function() {
        $('.show_confirm').click(function(event) {
            var form = $(this).closest("form");
            var name = $(this).data("name");
            event.preventDefault();
            Swal.fire({
                title: `Delete ${name}?`,
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
        $(document).on('change', '.status-toggle', function() {
            var menu_id = $(this).data('id');
            var url = $(this).data('url');
            var is_active = $(this).is(':checked') ? 1 : 0;
            var $toggle = $(this);
            $toggle.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    is_active: is_active
                },
                success: function(response) {
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        className: response.status ? "bg-success" : "bg-danger",
                        close: true
                    }).showToast();
                },
                error: function(xhr) {
                    $toggle.prop('checked', !is_active);
                    Toastify({
                        text: 'Failed to update status!',
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        className: "bg-danger",
                        close: true
                    }).showToast();
                },
                complete: function() {
                    $toggle.prop('disabled', false);
                }
            });
        });
        /*Menu sidebar status */
        $(document).on('change', '.sidebar-status-toggle', function() {
            var menu_id = $(this).data('id');
            var url = $(this).data('url');
            var display_sidebar_status = $(this).is(':checked') ? 1 : 0;
            var $toggle = $(this);
            $toggle.prop('disabled', true);
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    display_sidebar_status: display_sidebar_status
                },
                success: function(response) {
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        className: response.status ? "bg-success" : "bg-danger",
                        close: true
                    }).showToast();
                },
                error: function(xhr) {
                    $toggle.prop('checked', !is_active);
                    Toastify({
                        text: 'Failed to update status!',
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        className: "bg-danger",
                        close: true
                    }).showToast();
                },
                complete: function() {
                    $toggle.prop('disabled', false);
                }
            });
        });
        /*Menu sidebar status */
        $("#sortable").sortable({
            handle: ".order-handle",
            items: "tr[data-id]",
            placeholder: "ui-state-highlight",
            start: function(event, ui) {
                ui.item.css('background-color', '#f8f9fa');
            },
            stop: function(event, ui) {
                ui.item.css('background-color', '');
            },
            update: function(event, ui) {
                var order = [];
                $('#sortable tr[data-id]').each(function(index) {
                    order.push({
                        id: $(this).data('id'),
                        position: index + 1
                    });
                });

                $.ajax({
                    url: "{{ route('menus.reorder') }}",
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        order: order
                    },
                    success: function(response) {
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            className: "bg-success",
                            close: true
                        }).showToast();
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        Toastify({
                            text: 'Failed to update menu order!',
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            className: "bg-danger",
                            close: true
                        }).showToast();
                    }
                });
            }
        }).disableSelection();
    });
</script>
@endpush