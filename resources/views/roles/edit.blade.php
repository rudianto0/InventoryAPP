@extends('layouts.app')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
    <div class="card">
        <div class="card-header">Form Edit Role</div>
        <div class="card-body">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Nama Role</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}"
                           class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" value="{{ $role->slug }}" class="form-control" disabled>
                    <div class="form-text">Slug tidak dapat diubah.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea name="description" id="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Permissions</label>
                    @if ($role->slug === 'super-admin')
                        <div class="alert alert-info mb-0">Super Admin otomatis memiliki semua permission.</div>
                    @else
                        @foreach ($permissions as $module => $items)
                            <div class="card mb-2">
                                <div class="card-header bg-light py-2">
                                    <strong>{{ $module }}</strong>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        @foreach ($items as $permission)
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                           id="perm-{{ $permission->id }}" class="form-check-input"
                                                           {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->all())) ? 'checked' : '' }}>
                                                    <label for="perm-{{ $permission->id }}" class="form-check-label">{{ $permission->name }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                    @error('permissions')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
