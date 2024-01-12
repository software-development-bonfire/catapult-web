@extends('layouts.master')
@section('title', __('label.item_availability'))
@section('page-link', 'item-availability')

@section('content')
    <item-availability
        :header="{{$header}}"
        :categories="{{$productCategories}}"
    ></item-availability>
@endsection