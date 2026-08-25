@extends('layouts.app')

@php
    $isEdit = isset($user) && $user->exists;
@endphp

@section('title', $isEdit ? 'Edit User' : 'Add User')
@section('page-title', $isEdit ? 'Edit User' : 'Add User')

@section('content')
    <div class="card">
        <div class="card-header">{{ $isEdit ? 'Edit User Form' : 'Add User Form' }}</div>
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}"
                           class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}"
                           class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password
                        @if ($isEdit)
                            <span class="text-muted">(leave blank to keep unchanged)</span>
                        @endif
                    </label>
                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror" {{ $isEdit ? '' : 'required' }}>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                           {{ old('is_active', $isEdit ? ($user->is_active ? '1' : '0') : '1') === '1' ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">Active</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Roles</label>
                    <div class="row">
                        @foreach ($roles as $role)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" id="role-{{ $role->id }}"
                                           class="form-check-input"
                                           {{ in_array($role->id, old('roles', $isEdit ? $user->roles->pluck('id')->all() : [])) ? 'checked' : '' }}>
                                    <label for="role-{{ $role->id }}" class="form-check-label">{{ $role->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('roles')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
