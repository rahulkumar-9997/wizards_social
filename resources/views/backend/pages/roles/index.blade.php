@extends('backend.pages.layouts.master')
@section('title','Roles List')
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="mb-1">Roles Management</h4>
               <a href="{{ route('roles.create') }}" class="btn btn-sm btn-primary">Add New Role</a>
            </div>
            <div class="card-body">
               @if(session('success'))
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                     {{ session('success') }}
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
               @endif

               @if(session('error'))
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                     {{ session('error') }}
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
               @endif

               <div class="table-responsive">
                  <table class="table table-hover">
                     <thead>
                        <tr>
                           <th>#</th>
                           <th>Role Name</th>
                           <th>Users Count</th>
                           <th>Permissions Count</th>
                           <th>Created At</th>
                           <th>Actions</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($roles as $key => $role)
                           <tr>
                              <td>{{ $key + 1 }}</td>
                              <td>
                                 {{ $role->name }}
                                 @if($role->name == 'super-admin')
                                    <span class="badge bg-danger ms-1">Super Admin</span>
                                 @endif
                              </td>
                              <td>
                                 <span class="badge bg-info">{{ $role->users_count }}</span>
                              </td>
                              <td>
                                 <span class="badge bg-secondary">{{ $role->permissions_count }}</span>
                              </td>
                              <td>{{ $role->created_at->format('d M Y') }}</td>
                              <td>
                                 <div class="btn-group" role="group">
                                    @if($role->name != 'super-admin')
                                       <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-warning">
                                          <i class="fas fa-edit"></i> Edit
                                       </a>
                                       <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline">
                                          @csrf
                                          @method('DELETE')
                                          <button type="submit" class="btn btn-sm btn-danger" 
                                                  onclick="return confirm('Are you sure you want to delete this role?')">
                                             <i class="fas fa-trash"></i> Delete
                                          </button>
                                       </form>
                                    @else
                                       <span class="text-muted">Protected</span>
                                    @endif
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="6" class="text-center">No roles found.</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection