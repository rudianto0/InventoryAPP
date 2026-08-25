@extends('layouts.app')

@section('title', 'Role Management')
@section('page-title', 'Role Management')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Role List</h4>
        @hasPermission('roles.create')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Role
            </a>
        @endhasPermission
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('roles.index') }}" class="row g-2 mb-3">
                <div class="col-md-9">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Search name or slug...">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Users Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="text-nowrap">
                                    @hasPermission('roles.update')
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endhasPermission
                                    @hasPermission('roles.delete')
                                        @if ($role->slug !== 'super-admin')
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete this role?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endhasPermission
                                </td>
                                <td>{{ $role->name }}</td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td><span class="badge bg-secondary">{{ $role->users_count }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No role data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
