@extends('errors::error-content')

@section('error_code', '401')
@section('error_title', __('Unauthorised'))
@section('subtitle', __('You may need to login to access this page.'))

@section('button-route', route('login'))
@section('button-icon', 'login')
@section('button-text', __('Login'))
