@extends('errors::error-content')

@section('error_code', '500')
@section('error_title', __('Server error'))
@section('subtitle', __('An internal server error has occured. This is our fault, not yours.'))

@section('button-route', route('home'))
@section('button-icon', 'home')
@section('button-text', __('Home'))
