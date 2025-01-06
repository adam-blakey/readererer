@extends('errors::error-content')

@section('error_code', '402')
@section('error_title', __('Payment required'))
@section('subtitle', __('This is a peculiar error. You might actually be the very first person in existence to see this very page. In fact, it would be great if you could take a selfie with the error page and email it over to us, as we\'d love to meet the person who finally did it. Thank you.'))

@section('button-route', route('home'))
@section('button-icon', 'home')
@section('button-text', __('Home'))
