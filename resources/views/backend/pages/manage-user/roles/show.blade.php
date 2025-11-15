@extends('backend.pages.layouts.master')
@section('title','View Role')

@section('main-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h3 class="card-title mb-0">Role Details</h3>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Roles
                    </a>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Role Name:</strong> {{ $role->name }}
                        </div>
                        <div class="col-md-6">
                            <strong>Guard Name:</strong> {{ $role->guard_name }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Permissions:</strong>
                        <div>
                            @forelse($role->permissions as $permission)
                            <span class="badge bg-primary">{{ $permission->name }}</span>
                            @empty
                            <span>N/A</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Role
                        </a>
                        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back to Roles</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection