@extends('backend.pages.layouts.master')
@section('title','Permissions List')
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="mb-1">Permissions Management</h4>
               <a href="{{ route('permissions.create') }}" class="btn btn-sm btn-primary">Add New Permission</a>
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
                           <th>Permission Name</th>
                           <th>Guard Name</th>
                           <th>Roles Count</th>
                           <th>Created At</th>
                           <th>Actions</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($permissions as $key => $permission)
                           <tr>
                              <td>{{ $key + 1 }}</td>
                              <td>{{ $permission->name }}</td>
                              <td>
                                 <span class="badge bg-secondary">{{ $permission->guard_name }}</span>
                              </td>
                              <td>
                                 <span class="badge bg-info">{{ $permission->roles_count }}</span>
                              </td>
                              <td>{{ $permission->created_at->format('d M Y') }}</td>
                              <td>
                                 <div class="btn-group" role="group">
                                    <a href="{{ route('permissions.edit', $permission->id) }}" class="btn btn-sm btn-warning">
                                       <i class="fas fa-edit"></i> Edit
                                    </a>
                                    @if($permission->roles_count == 0)
                                       <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST" class="d-inline">
                                          @csrf
                                          @method('DELETE')
                                          <button type="submit" class="btn btn-sm btn-danger" 
                                                  onclick="return confirm('Are you sure you want to delete this permission?')">
                                             <i class="fas fa-trash"></i> Delete
                                          </button>
                                       </form>
                                    @else
                                       <button class="btn btn-sm btn-secondary" disabled title="Cannot delete - assigned to roles">
                                          <i class="fas fa-trash"></i> Delete
                                       </button>
                                    @endif
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="6" class="text-center">No permissions found.</td>
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