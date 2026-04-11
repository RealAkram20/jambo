@extends('layouts.app', ['module_title' => 'Ph Bold'])

@php
    $icons = [
        ['className' => 'ph-bold ph-bell', 'name' => 'bell'],
        ['className' => 'ph-bold ph-phone', 'name' => 'phone'],
        ['className' => 'ph-bold ph-dog', 'name' => 'dog'],
        ['className' => 'ph-bold ph-fish', 'name' => 'fish'],
        ['className' => 'ph-bold ph-bug', 'name' => 'bug'],
        ['className' => 'ph-bold ph-caret-down', 'name' => 'caret-down'],
        ['className' => 'ph-bold ph-caret-left', 'name' => 'caret-left'],
        ['className' => 'ph-bold ph-caret-right', 'name' => 'caret-right'],
        ['className' => 'ph-bold ph-caret-up', 'name' => 'caret-up'],
        ['className' => 'ph-bold ph-caret-circle-double-left', 'name' => 'caret-circle-double-left'],
        ['className' => 'ph-bold ph-caret-circle-double-right', 'name' => 'caret-circle-double-right'],
        ['className' => 'ph-bold ph-download', 'name' => 'download'],
        ['className' => 'ph-bold ph-navigation-arrow', 'name' => 'location-arrow'],
        ['className' => 'ph-bold ph-share', 'name' => 'share'],
        ['className' => 'ph-bold ph-rewind', 'name' => 'rewind'],
        ['className' => 'ph-bold ph-play', 'name' => 'play'],
        ['className' => 'ph-bold ph-pause', 'name' => 'pause'],
        ['className' => 'ph-bold ph-arrows-clockwise', 'name' => 'arrows-clockwise'],
        ['className' => 'ph-bold ph-speaker-low', 'name' => 'speaker-low'],
        ['className' => 'ph-bold ph-speaker-simple-x', 'name' => 'speaker-simple-x'],
        ['className' => 'ph-bold ph-speaker-x', 'name' => 'speaker-x'],
        ['className' => 'ph-bold ph-speaker-high', 'name' => 'speaker-high'],
        ['className' => 'ph-bold ph-youtube-logo', 'name' => 'youtube-logo'],
        ['className' => 'ph-bold ph-car', 'name' => 'car'],
        ['className' => 'ph-bold ph-truck', 'name' => 'truck'],
        ['className' => 'ph-bold ph-tree', 'name' => 'tree'],
        ['className' => 'ph-bold ph-pinterest-logo', 'name' => 'pinterest-logo'],
        ['className' => 'ph-bold ph-file-js', 'name' => 'file-js'],
        ['className' => 'ph-bold ph-buildings', 'name' => 'buildings'],
        ['className' => 'ph-bold ph-note-pencil', 'name' => 'note-pencil'],
        ['className' => 'ph-bold ph-copy', 'name' => 'copy'],
        ['className' => 'ph-bold ph-scissors', 'name' => 'cut'],
        ['className' => 'ph-bold ph-pen', 'name' => 'pen'],
        ['className' => 'ph-bold ph-tag', 'name' => 'tag'],
        ['className' => 'ph-bold ph-sim-card', 'name' => 'sim-card'],
        ['className' => 'ph-bold ph-list-checks', 'name' => 'tasks'],
        ['className' => 'ph-bold ph-chat-centered-text', 'name' => 'comment'],
        ['className' => 'ph-bold ph-video', 'name' => 'video'],
        ['className' => 'ph-bold ph-smiley', 'name' => 'smile'],
    ];
@endphp

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="card">
                <div class="card-body">
                    <h4 class="fw-bold">{{ __('sidebar.ph-bold') }}</h4>
                    <div class="border-bottom mt-3"></div>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4">
                        @foreach ($icons as $icon)
                            <div class="col">
                                @include('components.widget.IconComponent', [
                                    'className' => $icon['className'],
                                    'name' => __('fontawesome.' . $icon['name']),
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
