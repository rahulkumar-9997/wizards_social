<div class="ms-3 mb-2">
    <div class="form-check">
        <input type="checkbox" name="menus[]" value="{{ $menu->id }}"
               class="form-check-input" id="menu_{{ $menu->id }}">
        <label class="form-check-label fw-bold" for="menu_{{ $menu->id }}">
            {{ $menu->name }}
        </label>
    </div>

    @if($menu->children && $menu->children->count())
        <div class="ms-4 border-start ps-3">
            @foreach($menu->children as $child)
                @include('backend.manage-user.roles.partials.menu-checkbox', ['menu' => $child])
            @endforeach
        </div>
    @endif
</div>
