@extends('backend.pages.layouts.master')
@section('title','Edit Role')

@section('main-content')
<div class="container-fluid">
   <div class="row">
      <div class="col-xl-12">
         <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center gap-1">
               <h3 class="card-title mb-0">Edit Role: {{ $role->name }}</h3>
               <a href="{{ route('roles.index') }}" class="btn btn-success btn-sm">
                  <i class="ti ti-arrow-left"></i> Back to Roles
               </a>
            </div>

            <div class="card-body">
               <form action="{{ route('roles.update', $role->id) }}" method="POST">
                  @csrf
                  @method('PUT')

                  {{-- Role name --}}
                  <div class="row">
                     <div class="col-md-6">
                        <div class="mb-3">
                           <label for="name" class="form-label">Role Name *</label>
                           <input type="text" name="name" id="name"
                              class="form-control @error('name') is-invalid @enderror"
                              value="{{ old('name', $role->name) }}">
                           @error('name')
                           <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>

                  {{-- Menus Tree --}}
                  <div class="form-group">
                     <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0"><strong>Assign Menus</strong></label>
                        <button type="button" id="toggleAll" class="btn btn-outline-primary btn-sm">
                           <i class="ti ti-check"></i> Select All
                        </button>
                     </div>

                     <div class="border rounded p-3" style="max-height: 450px; overflow-y: auto;">
                        @foreach($menus as $menu)
                           <div class="mb-2">
                              {{-- Parent menu --}}
                              <div class="form-check">
                                 <input type="checkbox"
                                       name="menus[]"
                                       value="{{ $menu->id }}"
                                       class="form-check-input parent-checkbox"
                                       id="menu_{{ $menu->id }}"
                                       {{ in_array($menu->id, old('menus', $assignedMenus)) ? 'checked' : '' }}>
                                 <label class="form-check-label fw-bold" for="menu_{{ $menu->id }}">
                                    <i class="{{ $menu->icon }}"></i> {{ $menu->name }}
                                 </label>
                              </div>

                              {{-- Child menus --}}
                              @if($menu->children->count())
                                 <div class="ms-4 mt-1">
                                    @foreach($menu->children as $child)
                                       <div class="form-check mb-1">
                                          <input type="checkbox"
                                                name="menus[]"
                                                value="{{ $child->id }}"
                                                class="form-check-input child-checkbox"
                                                data-parent="{{ $menu->id }}"
                                                id="menu_{{ $child->id }}"
                                                {{ in_array($child->id, old('menus', $assignedMenus)) ? 'checked' : '' }}>
                                          <label class="form-check-label" for="menu_{{ $child->id }}">
                                             <i class="{{ $child->icon }}"></i> {{ $child->name }}
                                          </label>
                                       </div>
                                    @endforeach
                                 </div>
                              @endif
                           </div>
                        @endforeach
                     </div>
                  </div>

                  {{-- Actions --}}
                  <div class="form-group mt-3">
                     <button type="submit" class="btn btn-primary">Update Role</button>
                     <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
                  </div>
               </form>
            </div>

         </div>
      </div>
   </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Parent -> Children toggle
    document.querySelectorAll('.parent-checkbox').forEach(parent => {
        parent.addEventListener('change', function () {
            const parentId = this.value;
            document.querySelectorAll(`.child-checkbox[data-parent="${parentId}"]`)
                .forEach(child => child.checked = this.checked);
        });
    });

    // Child -> Parent toggle
    document.querySelectorAll('.child-checkbox').forEach(child => {
        child.addEventListener('change', function () {
            const parentId = this.dataset.parent;
            const parent = document.querySelector(`#menu_${parentId}`);
            if (this.checked) {
                parent.checked = true;
            } else {
                const siblings = document.querySelectorAll(`.child-checkbox[data-parent="${parentId}"]`);
                const anyChecked = Array.from(siblings).some(cb => cb.checked);
                if (!anyChecked) parent.checked = false;
            }
        });
    });

    // Select All / Deselect All button
    const toggleBtn = document.getElementById('toggleAll');
    let allChecked = false;
    toggleBtn.addEventListener('click', function () {
        allChecked = !allChecked;
        document.querySelectorAll('.form-check-input').forEach(cb => cb.checked = allChecked);
        toggleBtn.innerHTML = allChecked
            ? '<i class="ti ti-x"></i> Deselect All'
            : '<i class="ti ti-check"></i> Select All';
    });

});
</script>
@endpush
