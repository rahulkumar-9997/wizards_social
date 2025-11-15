@extends('backend.pages.layouts.master')
@section('title','View User')
@push('styles')
<link href="{{ asset('backend/assets/plugins/fontawesome/css/all.min.css') }}" rel="stylesheet">
@endpush
@section('main-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h3 class="card-title mb-0">User Details</h3>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Full Name:</strong> {{ $user->name }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Email:</strong> {{ $user->email }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Phone Number:</strong> {{ $user->phone_number ?? 'N/A' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Status:</strong> 
                            @if($user->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Roles:</strong>
                            @forelse($user->roles as $role)
                                <span class="badge bg-primary">{{ $role->name }}</span>
                            @empty
                                <span>N/A</span>
                            @endforelse
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Permissions:</strong>
                            @forelse($user->permissions as $permission)
                                <span class="badge bg-info">{{ $permission->name }}</span>
                            @empty
                                <span>N/A</span>
                            @endforelse
                        </div>
                        <div class="col-md-12 mt-3">
                            <strong>Created At:</strong> {{ $user->created_at->format('d M Y, h:i A') }} <br>
                            <strong>Last Updated:</strong> {{ $user->updated_at->format('d M Y, h:i A') }}
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit User
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to Users</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
