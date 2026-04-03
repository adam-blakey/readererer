@extends('errors::error-content')

@section('error_code', '404')
@section('error_title', __('Not found'))
@section('subtitle', __('This page cannot be found.'))

@section('button-route', route('home'))
@section('button-icon', 'home')
@section('button-text', __('Home'))
