@extends('backend.pages.layouts.master')
@section('title','Manage User')
@push('styles')
<!-- <link href="{{asset('backend/assets//plugins/datatables/css/jquery.dataTables.css')}}" rel="stylesheet" type="text/css" media="screen" />
<link href="{{asset('backend/assets/plugins/datatables/extensions/TableTools/css/dataTables.tableTools.min.css')}}" rel="stylesheet" type="text/css" media="screen" />
<link href="{{asset('backend/assets/plugins/datatables/extensions/Responsive/css/dataTables.responsive.css')}}" rel="stylesheet" type="text/css" media="screen" />
<link href="{{asset('backend/assets/plugins/datatables/extensions/Responsive/bootstrap/3/dataTables.bootstrap.css')}}" rel="stylesheet" type="text/css" media="screen" /> -->
@endpush
@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h4 class="card-title flex-grow-1">Users List</h4>
               <a href="{{ route('users.create') }}"  class="btn btn-sm btn-primary" data-bs-original-title="Add Brand">
                  Create Users
               </a>
               
            </div>
            <div class="card-body">

            </div>
         </div>
      </div>
   </div>
</div>
@endsection