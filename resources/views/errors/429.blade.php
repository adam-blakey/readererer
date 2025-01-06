@extends('errors::error-content')

@section('error_code', '429')
@section('error_title', __('Too many requests'))
@section('subtitle', __('Hold your horses, you\'ve given us too many requests. Calm doon, like.'))

@section('button-route', route('home'))
@section('button-icon', 'home')
@section('button-text', __('Home'))
