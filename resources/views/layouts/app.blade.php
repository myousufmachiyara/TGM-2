<!DOCTYPE html>
<html lang="en" class="fixed js flexbox flexboxlegacy no-touch csstransforms csstransforms3d no-overflowscrolling webkit chrome win js no-mobile-device custom-scroll sidebar-left-collapsed">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BillTrix')</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800|Shadows+Into+Light" rel="stylesheet">

    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/animate/animate.compat.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/magnific-popup/magnific-popup.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/media/css/dataTables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-multiselect/css/bootstrap-multiselect.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/dropzone/basic.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/dropzone/dropzone.css') }}">

    {{-- Theme CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/skins/default.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

    <style>
        #loader {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        #loader.hidden { display: none; }

        @media (min-width: 768px) {
            .cust-pad      { padding: 60px 10px 0 20px; }
            .home-cust-pad { padding: 60px 15px 0 15px; }
            .sidebar-logo  { width: 60%; height: auto; padding-top: 5px; }
        }
        @media (max-width: 767px) {
            .cust-pad     { padding-top: 0; }
            .sidebar-logo { height: 40%; }
        }
        .icon-container {
            background-size: auto;
            background-repeat: no-repeat;
            background-position: right bottom;
        }
    </style>

    {{-- jQuery in <head> — theme.js relies on $ at parse time. ONE copy only. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>
<body>

{{-- ── Page loader ──────────────────────────────────────────────────────── --}}
<div id="loader">
    <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading…</span>
    </div>
</div>

{{-- ── Change Password modal ─────────────────────────────────────────────── --}}
<div id="changePassword" class="zoom-anim-dialog modal-block modal-block-danger mfp-hide">
    <form id="changePasswordForm" method="POST" style="width:75%"
          enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
        @csrf
        <header class="card-header">
            <h2 class="card-title">Change Password</h2>
        </header>
        <div class="card-body">
            <div class="row form-group">
                <div class="col-12 mb-2">
                    <label>Current Password</label>
                    <input type="password" class="form-control" name="current_password"
                           placeholder="Current Password" required>
                </div>
                <div class="col-12 mb-2">
                    <label>New Password</label>
                    <input type="password" class="form-control" name="new_password"
                           placeholder="New Password" minlength="8" required>
                </div>
                <div class="col-12 mb-2">
                    <label>Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_new_password"
                           placeholder="Confirm New Password" minlength="8" required>
                </div>
            </div>
        </div>
        <footer class="card-footer">
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">Change Password</button>
                <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
            </div>
        </footer>
    </form>
</div>

{{-- ── Page header ───────────────────────────────────────────────────────── --}}
<header class="page-header">

    @php
        $userDropdown = '
            <li>
                <a role="menuitem" tabindex="-1"
                   href="#changePassword"
                   class="mb-1 mt-1 me-1 modal-with-zoom-anim ws-normal">
                    <i class="bx bx-lock"></i> Change Password
                </a>
            </li>
            <li>
                <form action="/logout" method="POST">
                    ' . csrf_field() . '
                    <button style="background:transparent;border:none;font-size:14px;"
                            type="submit" role="menuitem" tabindex="-1">
                        <i class="bx bx-power-off"></i> Logout
                    </button>
                </form>
            </li>
        ';
    @endphp

    {{-- Desktop --}}
    <div class="logo-container d-none d-md-block">
        <div id="userbox" class="userbox" style="float:right">
            @can('pos_system.index')
                <a href="{{ route('pos_system.index') }}" class="btn btn-danger btn-sm me-2" target="_blank">
                    <i class="fas fa-cash-register"></i> POS
                    <i class="fas fa-external-link-alt" style="font-size:9px;opacity:.8;"></i>
                </a>
            @endcan
            <a href="#" data-bs-toggle="dropdown" style="margin-right:20px;">
                <div class="profile-info">
                    <span class="name">{{ session('user_name') }}</span>
                    <span class="role">{{ session('role_name') }}</span>
                </div>
                <i class="fa custom-caret"></i>
            </a>
            <div class="dropdown-menu">
                <ul class="list-unstyled">{!! $userDropdown !!}</ul>
            </div>
        </div>
    </div>

    {{-- Mobile --}}
    <div class="logo-container d-md-none">
        <a href="/" class="logo">
            <img class="pt-2" src="{{ asset('assets/img/billtrix-logo-black.png') }}"
                 width="35%" alt="BillTrix Logo">
        </a>
        <div class="userbox" style="float:right">
            <a href="#" data-bs-toggle="dropdown" style="margin-right:20px;">
                <div class="profile-info">
                    <span class="name">{{ session('user_name') }}</span>
                    <span class="role">{{ session('role_name') }}</span>
                </div>
                <i class="fa custom-caret"></i>
            </a>
            <div class="dropdown-menu">
                <ul class="list-unstyled">{!! $userDropdown !!}</ul>
            </div>
            <i class="fas fa-bars toggle-sidebar-left"
               data-toggle-class="sidebar-left-opened"
               data-target="html"
               data-fire-event="sidebar-left-opened"
               aria-label="Toggle sidebar"></i>
        </div>
    </div>

</header>

{{-- ── Main body ────────────────────────────────────────────────────────── --}}
<section class="body">
    <div class="inner-wrapper cust-pad">
        @include('layouts.sidebar')
        <section role="main" class="content-body">
            @yield('content')
        </section>
    </div>
</section>

{{-- ── Footer ───────────────────────────────────────────────────────────── --}}
<footer>
    <div class="text-end">
        Powered By <a href="https://syitrix.com/" target="_blank">SyiTrix</a>
    </div>
</footer>

{{-- ════════════════════════════════════════════════════════════════════════
     SCRIPTS  —  end of <body> for performance.
     Load order:
       1. jQuery              ← already in <head>, NOT repeated here
       2. Bootstrap bundle    ← local vendor only (CDN copy removed)
       3. jQuery plugins      ← UI, nanoscroller, placeholder, appear, nestable
       4. DataTables          ← depends on jQuery
       5. UI plugins          ← Magnific, Datepicker, Select2, Multiselect, Dropzone
       6. Standalone utils    ← Moment, Chart.js, SortableJS, Fingerprint
       7. Theme core          ← depends on all vendor plugins
       8. Custom + examples   ← depend on theme
       9. Per-page via @stack('scripts')
════════════════════════════════════════════════════════════════════════ --}}

{{-- 2. Bootstrap bundle — local vendor, ONE copy (CDN stackpath removed) --}}
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

{{-- 3. jQuery plugins --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nanoscroller/0.8.7/jquery.nanoscroller.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-placeholder/2.3.1/jquery.placeholder.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.appear/0.4.1/jquery.appear.min.js"></script>
<script src="{{ asset('assets/vendor/jquery-nestable/jquery.nestable.js') }}"></script>

{{-- 4. DataTables --}}
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

{{-- 5. UI plugins --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('assets/vendor/select2/js/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/bootstrapv5-multiselect/js/bootstrap-multiselect.js') }}"></script>
<script src="{{ asset('assets/vendor/dropzone/dropzone.js') }}"></script>

{{-- 6. Standalone utilities --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js"></script>

{{-- 7. Theme core --}}
<script src="{{ asset('assets/js/theme.js') }}"></script>

{{-- 8. Custom + examples --}}
<script src="{{ asset('assets/js/custom.js') }}"></script>
<script src="{{ asset('assets/js/examples/examples.header.menu.js') }}"></script>
<script src="{{ asset('assets/js/examples/examples.dashboard.js') }}"></script>
<script src="{{ asset('assets/js/examples/examples.datatables.default.js') }}"></script>
<script src="{{ asset('assets/js/examples/examples.modals.js') }}"></script>
<script src="{{ asset('assets/js/theme.init.js') }}"></script>

{{-- 9. Per-page scripts — child views: @push('scripts') ... @endpush --}}
@stack('scripts')

</body>
</html>