<nav id="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-box-seam"></i> {{ config('app.name', 'Inventory App') }}
    </div>

    <ul class="list-unstyled components mt-3">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        @hasPermission('users.read')
        <li class="nav-item">
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Manajemen User
            </a>
        </li>
        @endhasPermission

        @hasPermission('roles.read')
        <li class="nav-item">
            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> Manajemen Role
            </a>
        </li>
        @endhasPermission
    </ul>
</nav>
