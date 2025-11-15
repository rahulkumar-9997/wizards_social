@extends('backend.pages.layouts.master')
@section('title','Add new User')
@push('styles')
<link href="{{asset('backend/assets/plugins/select2/select2.css')}}" rel="stylesheet" type="text/css" media="screen" />
<link href="{{asset('backend/assets/plugins/multi-select/css/multi-select.css')}}" rel="stylesheet" type="text/css" media="screen" />
@endpush
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h3 class="card-title mb-0">Create New User</h3>
               <div class="float-end">
                  <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                     <i class="fas fa-arrow-left"></i> Back to Users
                  </a>
               </div>
            </div>

            <div class="card-body">
               <form action="{{ route('users.store') }}" method="POST">
                  @csrf

                  <div class="row">
                     <div class="col-md-6">
                        <div class="form-group">
                           <label for="name" class="form-label">Full Name *</label>
                           <input type="text" class="form-control @error('name') is-invalid @enderror"
                              id="name" name="name" value="{{ old('name') }}" required>
                           @error('name')
                           <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="col-md-6">
                        <div class="form-group">
                           <label for="email" class="form-label">Email Address *</label>
                           <input type="email" class="form-control @error('email') is-invalid @enderror"
                              id="email" name="email" value="{{ old('email') }}" required>
                           @error('email')
                           <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>

                  <div class="row mt-3">
                     <div class="col-md-6">
                        <div class="form-group">
                           <label for="phone_number" class="form-label">Phone Number</label>
                           <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                              id="phone_number" name="phone_number" value="{{ old('phone_number') }}" maxlength="10">
                           @error('phone_number')
                           <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="col-md-6">
                        <div class="form-group">
                           <label for="roles" class="form-label">Roles *</label>
                           <select class="js-example-basic-multiple @error('roles') is-invalid @enderror"
                              id="roles" name="roles[]" multiple="multiple">
                              @foreach($roles as $role)
                              <option value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'selected' : '' }}>
                                 {{ $role->name }}
                              </option>
                              @endforeach
                           </select>
                           @error('roles')
                           <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>

                  <div class="row mt-3">
                     <div class="col-md-6">
                        <div class="form-group">
                           <label for="password" class="form-label">Password *</label>
                           <input type="password" class="form-control @error('password') is-invalid @enderror"
                              id="password" name="password" required>
                           @error('password')
                           <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="col-md-6">
                        <div class="form-group">
                           <label for="password_confirmation" class="form-label">Confirm Password *</label>
                           <input type="password" class="form-control"
                              id="password_confirmation" name="password_confirmation" required>
                        </div>
                     </div>
                  </div>

                  <div class="form-group mt-3">
                     <div class="form-check">
                        <!-- Ensure unchecked checkbox submits 0 -->
                        <input type="hidden" name="status" value="0">
                        <input type="checkbox" class="form-check-input" id="status" name="status" value="1" checked>
                        <label class="form-check-label" for="status">Active User</label>
                     </div>
                  </div>

                  <div class="form-group mt-3">
                     <button type="submit" class="btn btn-primary">Create User</button>
                     <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                  </div>

               </form>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection
@push('scripts')
<script src="{{asset('backend/assets/plugins/select2/select2.min.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/plugins/multi-select/js/jquery.multi-select.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/plugins/multi-select/js/jquery.quicksearch.js')}}" type="text/javascript"></script>
<script>
   $(document).ready(function() {
     roleMultiple();
   });

   function roleMultiple() {
      $('.js-example-basic-multiple').select2({
         placeholder: "Select Roles",
         allowClear: true
      });
      console.log('Select2 initialized for existing and new elements.');
   }
</script>
@endpush