@extends('errors::error-content')

@section('error_code', '403')
@section('error_title', __('Forbidden'))
@section('subtitle', __('You are not authorised to access this page. You might need to login below.'))

@section('button-route', route('login'))
@section('button-icon', 'login')
@section('button-text', __('Login'))
