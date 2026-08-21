<nav class="navbar navbar-expand-lg navbar-light bg-white px-3">
    <button class="btn btn-outline-secondary" id="sidebarToggle" type="button">
        <i class="bi bi-list"></i>
    </button>

    <span class="navbar-text ms-2 d-none d-sm-inline fw-semibold">@yield('page-title', 'Dashboard')</span>

    <div class="ms-auto dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle fs-4 me-1"></i>
            <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>
</nav>
