@extends('streaming::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('streaming.name') !!}</p>
@endsection
