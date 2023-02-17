@extends('layouts.master')
@section('title', __('label.field_mapping_preset_detail'))
@section('page-link', 'field-mapping-preset')

@section('content')
    <field-mapping-preset-detail
        :detail="{{ $detail }}">
    </field-mapping-preset-detail>
@endsection
