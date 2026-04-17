{{--
    Reusable Streaming source tabs (URL / Local / Dropbox).

    Props:
        model         — the Eloquent model (has video_url, dropbox_path, optional transcode_status/error)
        acceptLocal   — array of allowed local video extensions (default ['mp4','webm','mov','m4v','mkv'])
        showUpload    — bool; whether to render the native <input type=file> upload field (default false — file manager handles uploads)
--}}
@php
    $savedUrl     = $model->video_url ?? null;
    $savedDropbox = $model->dropbox_path ?? null;
    $savedIsLocal = $savedUrl && str_contains($savedUrl, '/storage/');

    if ($savedDropbox)      $savedSource = 'dropbox';
    elseif ($savedIsLocal)  $savedSource = 'local';
    elseif ($savedUrl)      $savedSource = 'url';
    else                    $savedSource = null;

    $videoUrlOld    = old('video_url',    $savedIsLocal ? '' : $savedUrl);
    $videoLocalOld  = old('video_local',  $savedIsLocal ? $savedUrl : '');
    $dropboxOld     = old('dropbox_path', $savedDropbox);
    $videoSourceOld = old('video_source', $savedSource ?? 'url');

    $activeTab = $videoSourceOld ?: 'url';
    if ($errors->has('dropbox_path')) $activeTab = 'dropbox';
    elseif ($errors->has('video_local') || $errors->has('video_file')) $activeTab = 'local';
    elseif ($errors->has('video_url')) $activeTab = 'url';

    $acceptLocal = $acceptLocal ?? ['mp4','webm','mov','m4v','mkv'];
    $acceptCsv   = implode(',', $acceptLocal);

    $sourceBadge = [
        'url'     => ['label' => 'URL',     'class' => 'bg-info-subtle text-info',       'icon' => 'ph-link'],
        'local'   => ['label' => 'Local',   'class' => 'bg-primary-subtle text-primary', 'icon' => 'ph-folder-open'],
        'dropbox' => ['label' => 'Dropbox', 'class' => 'bg-warning-subtle text-warning', 'icon' => 'ph-dropbox-logo'],
    ];
    $transcodeStatus = $model->transcode_status ?? null;
    $transcodeError  = $model->transcode_error ?? null;
@endphp

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Streaming</h6>
        <span class="badge rounded-pill d-inline-flex align-items-center gap-1
            @if ($savedSource) {{ $sourceBadge[$savedSource]['class'] }} @else bg-body-secondary text-secondary @endif"
            id="streaming-source-badge">
            <i class="ph {{ $savedSource ? $sourceBadge[$savedSource]['icon'] : 'ph-minus-circle' }}"></i>
            Source:
            <strong id="streaming-source-label">{{ $savedSource ? $sourceBadge[$savedSource]['label'] : 'None' }}</strong>
        </span>
    </div>
    <div class="card-body">
        <input type="hidden" name="video_source" id="video_source" value="{{ $videoSourceOld }}">

        <ul class="nav nav-tabs mb-3" id="streamingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'url' ? 'active' : '' }}" id="tab-stream-url"
                    data-bs-toggle="tab" data-bs-target="#pane-stream-url" type="button" role="tab">
                    <i class="ph ph-link me-1"></i> URL
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'local' ? 'active' : '' }}" id="tab-stream-local"
                    data-bs-toggle="tab" data-bs-target="#pane-stream-local" type="button" role="tab">
                    <i class="ph ph-folder-open me-1"></i> Local
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'dropbox' ? 'active' : '' }}" id="tab-stream-dropbox"
                    data-bs-toggle="tab" data-bs-target="#pane-stream-dropbox" type="button" role="tab">
                    <i class="ph ph-dropbox-logo me-1"></i> Dropbox
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'url' ? 'show active' : '' }}" id="pane-stream-url" role="tabpanel">
                <label for="video_url" class="form-label">Video URL</label>
                <input type="text" class="form-control @error('video_url') is-invalid @enderror"
                    id="video_url" name="video_url" value="{{ $videoUrlOld }}"
                    placeholder="https://www.youtube.com/watch?v=... or https://example.com/film.mp4">
                <div class="form-text">YouTube, Vimeo, or direct <code>.mp4</code> URL.</div>
                @error('video_url') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="tab-pane fade {{ $activeTab === 'local' ? 'show active' : '' }}" id="pane-stream-local" role="tabpanel">
                <input type="hidden" name="video_local" id="video_local" value="{{ $videoLocalOld }}">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary"
                        data-media-browse="video_local"
                        data-media-accept="{{ $acceptCsv }}">
                        <i class="ph ph-folder-open me-1"></i> Browse media library
                    </button>
                    <span class="text-secondary small flex-grow-1" id="video_local_display">
                        @if ($videoLocalOld)
                            Selected: <code>{{ $videoLocalOld }}</code>
                        @else
                            No local file selected
                        @endif
                    </span>
                    <button type="button" class="btn btn-outline-secondary btn-sm @if (!$videoLocalOld) d-none @endif"
                        id="video_local_clear" title="Clear selection">
                        <i class="ph ph-x"></i>
                    </button>
                    @if ($transcodeStatus)
                        <span class="badge
                            @switch($transcodeStatus)
                                @case('queued') bg-secondary @break
                                @case('downloading') bg-warning @break
                                @case('transcoding') bg-info @break
                                @case('ready') bg-success @break
                                @case('failed') bg-danger @break
                            @endswitch">{{ ucfirst($transcodeStatus) }}</span>
                    @endif
                </div>
                @error('video_local') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                @if ($transcodeStatus === 'failed' && $transcodeError)
                    <div class="text-danger small mt-2">{{ $transcodeError }}</div>
                @endif
            </div>

            <div class="tab-pane fade {{ $activeTab === 'dropbox' ? 'show active' : '' }}" id="pane-stream-dropbox" role="tabpanel">
                <label for="dropbox_path" class="form-label">Dropbox path</label>
                <input type="text" class="form-control @error('dropbox_path') is-invalid @enderror"
                    id="dropbox_path" name="dropbox_path" value="{{ $dropboxOld }}"
                    placeholder="/Jambo/movies/my-film.mp4">
                <div class="form-text">Legacy — resolved at playback.</div>
                @error('dropbox_path') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>

        <hr class="my-3">

        <div class="mb-0">
            <label for="video_url_low" class="form-label d-flex align-items-center gap-2">
                <i class="ph ph-film-strip"></i> 480p version
                <span class="badge bg-success-subtle text-success">Optional</span>
            </label>
            <input type="text" class="form-control @error('video_url_low') is-invalid @enderror"
                id="video_url_low" name="video_url_low"
                value="{{ old('video_url_low', $model->video_url_low ?? '') }}"
                placeholder="https://www.dropbox.com/.../movie-480p.mp4 or /Jambo/storage/media/...">
            <div class="form-text">
                Lower-quality version. When set, viewers get a <strong>Quality</strong> option in the player to switch between Original and 480p.
            </div>
            @error('video_url_low') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
