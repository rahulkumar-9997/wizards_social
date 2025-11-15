@extends('backend.pages.layouts.master')
@section('title','Users List')
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="mb-1">Users Management</h4>
               <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">Add New User</a>
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
                  <table class="table table-striped table-hover">
                        <tr>
                           <th>#</th>
                           <th>Name</th>
                           <th>Email</th>
                           <th>User ID</th>
                           <th>Phone</th>
                           <th>Role</th>
                           <th>Status</th>
                           <th>Created At</th>
                           <th>Actions</th>
                        </tr>
                        @forelse($users as $key => $user)
                           <tr>
                              <td>{{ $key + 1 }}</td>
                              <td>{{ $user->name }}</td>
                              <td>{{ $user->email }}</td>
                              <td>{{ $user->user_id }}</td>
                              <td>{{ $user->phone_number ?? 'N/A' }}</td>
                              <td>
                                 @foreach($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                 @endforeach
                              </td>
                              <td>
                                 <span class="badge {{ $user->status == 'active' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($user->status) }}
                                 </span>
                              </td>
                              <td>{{ $user->created_at->format('d M Y') }}</td>
                              <td>
                                 <div class="btn-group" role="group">
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">
                                       <i class="fas fa-edit"></i> Edit
                                    </a>
                                    @if($user->id !== auth()->id() && !$user->hasRole('super-admin'))
                                       <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                                          @csrf
                                          @method('DELETE')
                                          <button type="submit" class="btn btn-sm btn-danger" 
                                                  onclick="return confirm('Are you sure you want to delete this user?')">
                                             <i class="fas fa-trash"></i> Delete
                                          </button>
                                       </form>
                                    @endif
                                 </div>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="9" class="text-center">No users found.</td>
                           </tr>
                        @endforelse
                  </table>
               </div>
               
               <div class="d-flex justify-content-center mt-3">
                  {{ $users->links() }}
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection