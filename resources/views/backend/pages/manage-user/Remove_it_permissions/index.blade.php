@extends('backend.layouts.master')
@section('title', 'Permission Management')
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h3 class="card-title mb-0">Permission Management</h3>
               <div class="float-end">
                  <a href="{{ route('permissions.create') }}" class="btn btn-primary btn-sm">
                     <i class="ti ti-plus"></i> Add Permission
                  </a>
               </div>
            </div>
            <div class="card-body">
               <div class="table-responsive">
                  <table class="table table-bordered table-striped" id="permissionsTable">
                     <thead>
                        <tr>
                           <th>ID</th>
                           <th>Name</th>
                           <th>Module</th>
                           <th>Description</th>
                           <th>Guard</th>
                           <th>Created At</th>
                           <th>Actions</th>
                        </tr>
                     </thead>
                     <tbody>
                        @foreach($permissions as $permission)
                        <tr>
                           <td>{{ $permission->id }}</td>
                           <td>
                              {{ $permission->name }}
                           </td>
                           <td>
                              <span class="badge bg-primary">{{ $permission->module ?? 'General' }}</span>
                           </td>
                           <td>{{ $permission->description ?? 'N/A' }}</td>
                           <td>{{ $permission->guard_name }}</td>
                           <td>{{ $permission->created_at->format('M d, Y') }}</td>
                           <td>
                              <div class="d-flex gap-2">
                                 <a href="{{ route('permissions.show', $permission) }}" class="btn btn-sm btn-info">
                                    <i class="ti ti-eye"></i>
                                 </a>
                                 <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-edit"></i>
                                 </a>
                                 <form action="{{ route('permissions.destroy', $permission) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this permission?')">
                                       <i class="ti ti-trash"></i>
                                    </button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                        @endforeach
                     </tbody>
                  </table>
               </div>
               <div class="d-flex justify-content-center">
                  {{ $permissions->links() }}
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
   @endsection