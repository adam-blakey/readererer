@extends('errors::error-content')

@section('error_code', '401')
@section('error_title', __('Page expired'))
@section('subtitle', __('This page has expired. Try refreshing below to try again.'))

@section('button-route', Request::fullUrl())
@section('button-icon', 'refresh')
@section('button-text', __('Refresh'))
