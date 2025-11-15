@extends('backend.pages.layouts.master')
@section('title','Edit User')
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="mb-1">Edit User: {{ $user->name }}</h4>
               <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary">Back to Users</a>
            </div>
            <div class="card-body">
               <form action="{{ route('users.update', $user->id) }}" method="POST">
                  @csrf
                  @method('PUT')
                  <div class="row">
                     <div class="col-lg-4">
                        <div class="mb-2">
                           <label class="form-label" for="name">Name *</label>
                           <div class="controls">
                              <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                     name="name" value="{{ old('name', $user->name) }}" required>
                           </div>
                           @error('name')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                     <div class="col-lg-4">
                        <div class="mb-2">
                           <label class="form-label" for="email">Email *</label>
                           <div class="controls">
                              <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                     name="email" value="{{ old('email', $user->email) }}" required>
                           </div>
                           @error('email')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                     <div class="col-lg-4">
                        <div class="mb-2">
                           <label class="form-label" for="user_id">User ID *</label>
                           <div class="controls">
                              <input type="text" class="form-control @error('user_id') is-invalid @enderror" 
                                     name="user_id" value="{{ old('user_id', $user->user_id) }}" required>
                           </div>
                           @error('user_id')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>
                  
                  <div class="row">
                     <div class="col-lg-4">                  
                        <div class="mb-2">
                           <label class="form-label" for="phone_number">Phone Number</label>
                           <div class="controls">
                              <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                                     name="phone_number" value="{{ old('phone_number', $user->phone_number) }}">
                           </div>
                           @error('phone_number')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                     <div class="col-lg-4">
                        <div class="mb-2">
                           <label class="form-label" for="role">Role *</label>
                           <div class="controls">
                              <select class="form-control @error('role') is-invalid @enderror" name="role" required>
                                 <option value="">Select Role</option>
                                 @foreach($roles as $role)
                                    <option value="{{ $role->name }}" 
                                        {{ (old('role', $userRole[0] ?? '') == $role->name) ? 'selected' : '' }}>
                                       {{ $role->name }}
                                    </option>
                                 @endforeach
                              </select>
                           </div>
                           @error('role')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                     <div class="col-lg-4">
                        <div class="mb-2">
                           <label class="form-label" for="status">Status</label>
                           <div class="controls">
                              <select class="form-control @error('status') is-invalid @enderror" name="status">
                                 <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                                 <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                              </select>
                           </div>
                           @error('status')
                              <div class="text-danger">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>
                  
                  <div class="row">  
                     <div class="col-lg-12">               
                        <div class="form-group">
                           <div class="controls">
                              <button type="submit" class="btn btn-primary">Update User</button>
                              <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                           </div>
                        </div>
                     </div>
                  </div>
               </form>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection