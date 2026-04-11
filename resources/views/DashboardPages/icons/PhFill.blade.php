@extends('layouts.app', ['module_title' => 'Ph Fill'])

@php
    $icons = [
        ['className' => 'ph-fill ph-bell', 'name' => 'bell'],
        ['className' => 'ph-fill ph-phone', 'name' => 'phone'],
        ['className' => 'ph-fill ph-dog', 'name' => 'dog'],
        ['className' => 'ph-fill ph-fish', 'name' => 'fish'],
        ['className' => 'ph-fill ph-bug', 'name' => 'bug'],
        ['className' => 'ph-fill ph-caret-down', 'name' => 'caret-down'],
        ['className' => 'ph-fill ph-caret-left', 'name' => 'caret-left'],
        ['className' => 'ph-fill ph-caret-right', 'name' => 'caret-right'],
        ['className' => 'ph-fill ph-caret-up', 'name' => 'caret-up'],
        ['className' => 'ph-fill ph-caret-circle-double-left', 'name' => 'caret-circle-double-left'],
        ['className' => 'ph-fill ph-caret-circle-double-right', 'name' => 'caret-circle-double-right'],
        ['className' => 'ph-fill ph-download', 'name' => 'download'],
        ['className' => 'ph-fill ph-navigation-arrow', 'name' => 'location-arrow'],
        ['className' => 'ph-fill ph-share', 'name' => 'share'],
        ['className' => 'ph-fill ph-rewind', 'name' => 'rewind'],
        ['className' => 'ph-fill ph-play', 'name' => 'play'],
        ['className' => 'ph-fill ph-pause', 'name' => 'pause'],
        ['className' => 'ph-fill ph-arrows-clockwise', 'name' => 'arrows-clockwise'],
        ['className' => 'ph-fill ph-speaker-low', 'name' => 'speaker-low'],
        ['className' => 'ph-fill ph-speaker-simple-x', 'name' => 'speaker-simple-x'],
        ['className' => 'ph-fill ph-speaker-x', 'name' => 'speaker-x'],
        ['className' => 'ph-fill ph-speaker-high', 'name' => 'speaker-high'],
        ['className' => 'ph-fill ph-youtube-logo', 'name' => 'youtube-logo'],
        ['className' => 'ph-fill ph-car', 'name' => 'car'],
        ['className' => 'ph-fill ph-truck', 'name' => 'truck'],
        ['className' => 'ph-fill ph-tree', 'name' => 'tree'],
        ['className' => 'ph-fill ph-pinterest-logo', 'name' => 'pinterest-logo'],
        ['className' => 'ph-fill ph-file-js', 'name' => 'file-js'],
        ['className' => 'ph-fill ph-buildings', 'name' => 'buildings'],
        ['className' => 'ph-fill ph-note-pencil', 'name' => 'note-pencil'],
        ['className' => 'ph-fill ph-copy', 'name' => 'copy'],
        ['className' => 'ph-fill ph-scissors', 'name' => 'cut'],
        ['className' => 'ph-fill ph-pen', 'name' => 'pen'],
        ['className' => 'ph-fill ph-tag', 'name' => 'tag'],
        ['className' => 'ph-fill ph-sim-card', 'name' => 'sim-card'],
        ['className' => 'ph-fill ph-list-checks', 'name' => 'tasks'],
        ['className' => 'ph-fill ph-chat-centered-text', 'name' => 'comment'],
        ['className' => 'ph-fill ph-video', 'name' => 'video'],
        ['className' => 'ph-fill ph-smiley', 'name' => 'smile'],
    ];
@endphp

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="card">
                <div class="card-body">
                    <h4 class="fw-bold">{{ __('sidebar.ph-fill') }}</h4>
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
