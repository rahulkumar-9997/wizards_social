@extends('backend.pages.layouts.master')
@section('title','Edit Role')
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="mb-1">Edit Role: {{ $role->name }}</h4>
               <a href="{{ route('roles.index') }}" class="btn btn-sm btn-secondary">Back to Roles</a>
            </div>
            <div class="card-body">
               <form action="{{ route('roles.update', $role->id) }}" method="POST">
                  @csrf
                  @method('PUT')
                  <div class="row">
                     <div class="col-lg-6">
                        <div class="mb-3">
                           <label class="form-label" for="name">Role Name *</label>
                           <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                  name="name" value="{{ old('name', $role->name) }}" required>
                           @error('name')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>

                  <div class="row">
                     <div class="col-lg-12">
                        <div class="mb-3">
                           <label class="form-label">Permissions *</label>
                           @error('permission')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                           
                           <div class="border p-3" style="max-height: 400px; overflow-y: auto;">
                              @foreach($permissions as $group => $groupPermissions)
                                 <div class="mb-4">
                                    <h6 class="text-primary mb-2">{{ ucfirst($group) }}</h6>
                                    <div class="row">
                                       @foreach($groupPermissions as $permission)
                                          <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                             <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permission[]" value="{{ $permission->id }}"
                                                       id="permission_{{ $permission->id }}"
                                                       {{ in_array($permission->id, old('permission', $rolePermissions)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                   {{ $permission->name }}
                                                </label>
                                             </div>
                                          </div>
                                       @endforeach
                                    </div>
                                    <hr>
                                 </div>
                              @endforeach
                           </div>
                        </div>
                     </div>
                  </div>

                  <div class="row">
                     <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary">Update Role</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
                     </div>
                  </div>
               </form>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection