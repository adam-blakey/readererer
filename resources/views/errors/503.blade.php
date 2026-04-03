@extends('errors::error-content')

@section('error_code', '503')
@section('error_title', __('Service unavailable'))
@section('subtitle', __('The app is currently unavailable. This could be due to routine maintenance or maybe our batteries have run flat. Try again later.'))

@section('button-route', route('home'))
@section('button-icon', 'home')
@section('button-text', __('Home'))
