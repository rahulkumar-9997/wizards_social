@extends('backend.pages.layouts.master')
@section('title','Edit Permission')
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="mb-1">Edit Permission: {{ $permission->name }}</h4>
               <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-secondary">Back to Permissions</a>
            </div>
            <div class="card-body">
               <form action="{{ route('permissions.update', $permission->id) }}" method="POST">
                  @csrf
                  @method('PUT')
                  <div class="row">
                     <div class="col-lg-6">
                        <div class="mb-3">
                           <label class="form-label" for="name">Permission Name *</label>
                           <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                  name="name" value="{{ old('name', $permission->name) }}" 
                                  placeholder="e.g., user-create, post-edit" required>
                           <small class="form-text text-muted">
                              Use lowercase letters and hyphens only (e.g., user-create, post-edit)
                           </small>
                           @error('name')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                     <div class="col-lg-6">
                        <div class="mb-3">
                           <label class="form-label" for="guard_name">Guard Name</label>
                           <input type="text" class="form-control @error('guard_name') is-invalid @enderror" 
                                  name="guard_name" value="{{ old('guard_name', $permission->guard_name) }}">
                           <small class="form-text text-muted">
                              Usually 'web' for web interface
                           </small>
                           @error('guard_name')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>

                  <div class="row">
                     <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary">Update Permission</button>
                        <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Cancel</a>
                     </div>
                  </div>
               </form>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection