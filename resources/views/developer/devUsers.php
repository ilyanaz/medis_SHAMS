@extends('layouts.app')

@section('content')
    <section class="content-panel">
        <div class="page-header">
            <div>
                <div class="table-label">Developer Panel</div>
                <h2>{{ $pageTitle }}</h2>
                <p>
                    Manage developer users and system access.
                </p>
            </div>
        </div>
    </section>

    <section class="responsive-grid">
        <article class="table-card">
            <div class="table-header">
                <div>
                    <div class="table-label">User Management</div>
                    <h3>Developer Users</h3>
                </div>
                <span class="badge">Admin Only</span>
            </div>
            <p class="empty-copy">Developer user management interface coming soon.</p>
        </article>
    </section>
@endsection
