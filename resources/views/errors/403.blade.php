@extends('errors::error-content')

@section('error_code', '403')
@section('error_title', __('Forbidden'))
@section('subtitle', __('You are not authorized to access this page.'))
