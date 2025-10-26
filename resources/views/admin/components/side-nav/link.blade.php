@php
    $current_route = Route::currentRouteName();
@endphp
@if (isset($route) && $route != "")
    @php
        $check_permission = $check_permission ?? true;
    @endphp
    @if (!$check_permission || admin_permission_by_name($route))
        <li class="sidebar-menu-item @if ($current_route == $route) active @endif">
            @php
                $title = $title ?? "";
                $dusk = $dusk ?? null;
            @endphp
            <a href="{{ setRoute($route) }}" @if ($dusk) dusk="{{ $dusk }}" @endif>
                <i class="{{ $icon ?? "" }}"></i>
                <span class="menu-title">{{ __($title) }}</span>
            </a>
        </li>
    @endif
@endif
