@extends('backend.pages.layouts.master')
@section('title', 'Edit Menu')
@section('main-content')

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="mb-0">Edit Menu</h4>
                    <a href="{{ route('menus.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('menus.update', $menu->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Menu Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $menu->name) }}" required>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="url" class="form-label">URL</label>
                                    <input type="text" name="url" class="form-control" id="url"
                                        value="{{ old('url', $menu->url) }}">
                                    <small class="text-muted">Enter route name or URL path</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Menu Icon</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="icon-preview">
                                            <iconify-icon icon="{{ old('icon', $menu->icon ?? 'solar:menu-dots-bold-duotone') }}"width="22" height="22"></iconify-icon>
                                        </span>
                                        <select name="icon" id="icon" class="form-select">
                                            <option value="">-- Select Icon --</option>
                                            @foreach($icons as $value => $label)
                                                <option value="{{ $value }}" {{ old('icon', $menu->icon) == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>

                                    </div>
                                    <small class="text-muted">Select an icon — preview updates automatically</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="parent_id" class="form-label">Parent Menu</label>
                                    <select name="parent_id" class="form-select" id="parent_id">
                                        <option value="">-- None --</option>
                                        @foreach($parentMenus as $parent)
                                        <option value="{{ $parent->id }}"
                                            {{ old('parent_id', $menu->parent_id) == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-2">
                                <div class="mb-3">
                                    <label for="order" class="form-label">Order</label>
                                    <input type="number" name="order" class="form-control" id="order" value="{{ old('order', $menu->order) }}" readonly>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="mb-4">
                                    <label for="is_active" class="form-label">Status</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" {{ old('is_active', $menu->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="mb-4">
                                    <label for="display_sidebar_status" class="form-label">This Menu Display in Sidebar Menu *</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="display_sidebar_status" class="form-check-input" id="display_sidebar_status" {{ old('display_sidebar_status', $menu->display_sidebar_status) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="display_sidebar_status">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Roles</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}"
                                        {{ in_array($role->id, old('roles', $menu->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->name }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-success px-4">Update</button>
                            <a href="{{ route('menus.index') }}" class="btn btn-secondary px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const iconSelect = document.getElementById('icon');
        const iconPreview = document.querySelector('#icon-preview iconify-icon');
        iconSelect.addEventListener('change', function() {
            const selectedIcon = this.value || 'solar:menu-dots-bold-duotone';
            iconPreview.setAttribute('icon', selectedIcon);
        });
    });
</script>
@endpush