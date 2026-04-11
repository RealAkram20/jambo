@extends('subscriptions::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('subscriptions.name') !!}</p>
@endsection
