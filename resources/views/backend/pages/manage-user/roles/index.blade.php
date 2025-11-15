@extends('backend.pages.layouts.master')
@section('title','Rols List')

@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h3 class="card-title mb-0">Role Management</h3>
               <div class="float-end">
                  <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                     <i class="ti ti-plus"></i> Add Role
                  </a>
               </div>
            </div>
            <div class="card-body">
               <div class="table-responsive">
                  <table class="table table-striped" id="rolesTable">
                     <thead>
                        <tr>
                              <th>ID</th>
                              <th>Name</th>
                              <th>Menus</th> <!-- changed column name -->
                              <th>Guard</th>
                              <th>Created At</th>
                              <th>Actions</th>
                        </tr>
                     </thead>
                     <tbody>
                        @foreach($roles as $role)
                        <tr>
                              <td>{{ $role->id }}</td>
                              <td><span class="fw-bold">{{ $role->name }}</span></td>
                              <td>
                                 @foreach($role->menus as $menu)
                                 <span class="badge bg-success mb-1">{{ $menu->name }}</span>
                                 @endforeach
                              </td>
                              <td>{{ $role->guard_name }}</td>
                              <td>{{ $role->created_at->format('M d, Y') }}</td>
                              <td>
                                 <div class="d-flex gap-2">
                                    <a href="{{ route('roles.show', $role) }}" class="btn btn-sm btn-purple">
                                          <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-primary">
                                          <i class="ti ti-edit"></i>
                                    </a>
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline">
                                          @csrf
                                          @method('DELETE')
                                          <button type="submit" class="btn btn-sm btn-danger"
                                             onclick="return confirm('Are you sure you want to delete this role?')">
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
                  {{ $roles->links() }}
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection