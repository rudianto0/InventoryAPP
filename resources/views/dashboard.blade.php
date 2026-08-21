@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="mb-2">Selamat datang, {{ auth()->user()->name }}!</h5>
            <p class="text-muted mb-0">
                Anda login sebagai:
                @foreach (auth()->user()->roles as $role)
                    <span class="badge bg-info text-dark">{{ $role->name }}</span>
                @endforeach
            </p>
        </div>
    </div>
@endsection
