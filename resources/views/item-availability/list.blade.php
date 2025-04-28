@extends('layouts.master')
@section('title', __('label.item_availability'))
@section('page-link', 'item-availability')

@section('content')
    <item-availability
        :web-app="{{$webAppHeader}}"
        :header="{{$header}}"
        :categories="{{$productCategories}}"
    ></item-availability>
@endsection