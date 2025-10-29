<script src="{{asset('backend/assets/js/vendor.js')}}"></script>
<script src="{{asset('backend/assets/js/app.js')}}"></script>
<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="{{asset('backend/assets/vendor/datatables/js/jquery.dataTables.min.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/vendor/datatables/extensions/TableTools/js/dataTables.tableTools.min.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/vendor/datatables/extensions/Responsive/js/dataTables.responsive.min.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/vendor/datatables/extensions/Responsive/bootstrap/3/dataTables.bootstrap.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/js/datatable.js')}}" type="text/javascript"></script>
<script type="text/javascript" src="{{asset('backend/assets/js/daterangepicker/daterangepicker.min.js')}}"></script>
<script src="{{asset('backend/assets/plugins/select2/select2.min.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/plugins/multi-select/js/jquery.multi-select.js')}}" type="text/javascript"></script>
<script src="{{asset('backend/assets/plugins/multi-select/js/jquery.quicksearch.js')}}" type="text/javascript"></script> 

@if(session()->has('success'))
<script>
    Toastify({
        text: "{{ session()->get('success') }}",
        duration: 4000,
        gravity: "top",
        position: "right",
        className: "bg-success",
        close: true,
        onClick: function() {}
    }).showToast();
</script>
@endif
@if(session()->has('error'))
<script>
    Toastify({
        text: "{{ session()->get('error') }}",
        duration: 4000,
        gravity: "top",
        position: "right",
        className: "bg-danger",
        close: true,
        onClick: function() {}
    }).showToast();
</script>
@endif


@if($errors->any())
<script>
    @foreach($errors->all() as $error)
    Toastify({
        text: "{{ $error }}",
        duration: 4000,
        gravity: "top",
        position: "right",
        className: "bg-danger",
        close: true,
        onClick: function() {}
    }).showToast();
    @endforeach
</script>
@endif