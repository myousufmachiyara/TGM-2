@extends('layouts.app')

@section('title', 'Unauthorized | Access Denied')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 text-center py-5">
        <i class="fa fa-ban text-danger" style="font-size:64px;"></i>
        <h2 class="mt-3 font-weight-semibold">Access Denied</h2>
        <p class="text-muted">You do not have permission to access this page.</p>
        <a href="javascript:history.back()" class="btn btn-outline-secondary mt-2">
            <i class="fa fa-arrow-left"></i> Go Back
        </a>
    </div>
</div>
@endsection