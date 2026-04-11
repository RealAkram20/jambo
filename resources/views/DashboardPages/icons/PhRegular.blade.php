@extends('layouts.app', ['module_title' => 'Ph Regular'])

@php
    $icons = [
        ['className' => 'ph ph-bell', 'name' => 'bell'],
        ['className' => 'ph ph-phone', 'name' => 'phone'],
        ['className' => 'ph ph-dog', 'name' => 'dog'],
        ['className' => 'ph ph-fish', 'name' => 'fish'],
        ['className' => 'ph ph-bug', 'name' => 'bug'],
        ['className' => 'ph ph-caret-down', 'name' => 'caret-down'],
        ['className' => 'ph ph-caret-left', 'name' => 'caret-left'],
        ['className' => 'ph ph-caret-right', 'name' => 'caret-right'],
        ['className' => 'ph ph-caret-up', 'name' => 'caret-up'],
        ['className' => 'ph ph-caret-circle-double-left', 'name' => 'caret-circle-double-left'],
        ['className' => 'ph ph-caret-circle-double-right', 'name' => 'caret-circle-double-right'],
        ['className' => 'ph ph-download', 'name' => 'download'],
        ['className' => 'ph ph-navigation-arrow', 'name' => 'location-arrow'],
        ['className' => 'ph ph-share', 'name' => 'share'],
        ['className' => 'ph ph-rewind', 'name' => 'rewind'],
        ['className' => 'ph ph-play', 'name' => 'play'],
        ['className' => 'ph ph-pause', 'name' => 'pause'],
        ['className' => 'ph ph-arrows-clockwise', 'name' => 'arrows-clockwise'],
        ['className' => 'ph ph-speaker-low', 'name' => 'speaker-low'],
        ['className' => 'ph ph-speaker-simple-x', 'name' => 'speaker-simple-x'],
        ['className' => 'ph ph-speaker-x', 'name' => 'speaker-x'],
        ['className' => 'ph ph-speaker-high', 'name' => 'speaker-high'],
        ['className' => 'ph ph-youtube-logo', 'name' => 'youtube-logo'],
        ['className' => 'ph ph-car', 'name' => 'car'],
        ['className' => 'ph ph-truck', 'name' => 'truck'],
        ['className' => 'ph ph-tree', 'name' => 'tree'],
        ['className' => 'ph ph-pinterest-logo', 'name' => 'pinterest-logo'],
        ['className' => 'ph ph-file-js', 'name' => 'file-js'],
        ['className' => 'ph ph-buildings', 'name' => 'buildings'],
        ['className' => 'ph ph-note-pencil', 'name' => 'note-pencil'],
        ['className' => 'ph ph-copy', 'name' => 'copy'],
        ['className' => 'ph ph-scissors', 'name' => 'cut'],
        ['className' => 'ph ph-pen', 'name' => 'pen'],
        ['className' => 'ph ph-tag', 'name' => 'tag'],
        ['className' => 'ph ph-sim-card', 'name' => 'sim-card'],
        ['className' => 'ph ph-list-checks', 'name' => 'tasks'],
        ['className' => 'ph ph-chat-centered-text', 'name' => 'comment'],
        ['className' => 'ph ph-video', 'name' => 'video'],
        ['className' => 'ph ph-smiley', 'name' => 'smile'],
    ];
@endphp

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="card">
                <div class="card-body">
                    <h4 class="fw-bold">{{ __('sidebar.ph-regular') }}</h4>
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
