<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-1">
                <h4 class="card-title flex-grow-1">
                    All Table List (Select Tables to Truncate)
                </h4>

                <button wire:click="backupDatabase" class="btn btn-sm btn-primary">
                    Backup Database
                </button>
            </div>

            <div class="card-body">
                <form wire:submit.prevent="truncateSelected">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>Table Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tables as $table)
                                <tr>
                                    <td>
                                        @if(in_array($table, ['users','migrations','menu_permission','menu_role','menus','model_has_permissions','model_has_roles','permissions','role_has_permissions','roles','order_status']))
                                            <input class="form-check-input" type="checkbox" disabled>
                                        @else
                                            <input type="checkbox" wire:model="selectedTables" class="form-check-input table-checkbox" value="{{ $table }}">
                                        @endif
                                    </td>
                                    <td>{{ $table }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-danger btn-sm">
                            Truncate Selected Tables
                        </button>
                    </div>
                </form>
            </div>
         </div>
      </div>
   </div>

   <!-- SweetAlert -->
   <script>
      // select all checkbox
      document.addEventListener('DOMContentLoaded', function () {
          const selectAll = document.getElementById('selectAll');
          if (selectAll) {
              selectAll.addEventListener('change', function () {
                  const checkboxes = document.querySelectorAll('.table-checkbox');
                  checkboxes.forEach(checkbox => checkbox.checked = this.checked);
              });
          }
      });

      // SweetAlert notifications
      window.addEventListener('notify', event => {
          Swal.fire({
              icon: event.detail.type,
              title: event.detail.message,
              timer: 2500,
              showConfirmButton: false
          });
      });
   </script>
</div>
