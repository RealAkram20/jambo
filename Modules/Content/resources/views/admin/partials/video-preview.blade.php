{{--
    Admin video-preview card. Custom control bar (no native browser
    controls) so an admin can play, scrub, skip ±10s, test audio and go
    fullscreen before publishing.

    Expects: $model — a saved Movie or Episode.

    Uses the admin-only content-preview route (302 → resolved CDN source):
    no tier gate, no heartbeat, no view count, no watch-time accrual.
--}}
@php
    $kind   = $model instanceof \Modules\Content\app\Models\Episode ? 'episode' : 'movie';
    $src    = $model->exists ? $model->streamSource() : null;
    $poster = $kind === 'episode' ? ($model->still_url ?? null) : ($model->poster_url ?? null);
@endphp

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Preview</h6></div>
    <div class="card-body">
        @if (!$src)
            <p class="text-muted mb-0" style="font-size:13px;">No video URL set.</p>
        @elseif ($src['type'] === 'youtube')
            <div class="ratio ratio-16x9">
                <iframe src="{{ $src['embed_url'] }}" title="Video preview"
                        allow="accelerometer; encrypted-media; picture-in-picture"
                        allowfullscreen style="border:0;border-radius:8px;"></iframe>
            </div>
        @else
            <div class="jpv" data-jpv>
                <video class="jpv__video" data-jpv-video playsinline preload="metadata"
                       @if ($poster) poster="{{ media_url($poster) }}" @endif>
                    <source src="{{ route('admin.content-preview.'.$kind, $model) }}" type="{{ $src['mime'] }}">
                </video>

                <button type="button" class="jpv__big" data-jpv-big aria-label="Play">
                    <i class="ph ph-play"></i>
                </button>

                <div class="jpv__bar">
                    <button type="button" class="jpv__btn" data-jpv-toggle aria-label="Play / pause">
                        <i class="ph ph-play"></i>
                    </button>
                    <button type="button" class="jpv__btn" data-jpv-back aria-label="Back 10 seconds">
                        <i class="ph ph-rewind"></i>
                    </button>
                    <button type="button" class="jpv__btn" data-jpv-fwd aria-label="Forward 10 seconds">
                        <i class="ph ph-fast-forward"></i>
                    </button>

                    <span class="jpv__time" data-jpv-current>0:00</span>
                    <input type="range" class="jpv__range jpv__seek" data-jpv-seek
                           min="0" max="100" step="0.1" value="0" aria-label="Seek">
                    <span class="jpv__time" data-jpv-duration>0:00</span>

                    <button type="button" class="jpv__btn" data-jpv-mute aria-label="Mute">
                        <i class="ph ph-speaker-high"></i>
                    </button>
                    <input type="range" class="jpv__range jpv__vol" data-jpv-vol
                           min="0" max="1" step="0.05" value="1" aria-label="Volume">

                    <button type="button" class="jpv__btn" data-jpv-fs aria-label="Fullscreen">
                        <i class="ph ph-corners-out"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

@once
<style>
    .jpv{position:relative;background:#000;border-radius:8px;overflow:hidden;line-height:0}
    .jpv__video{width:100%;display:block;aspect-ratio:16/9;background:#000;cursor:pointer}
    .jpv__big{position:absolute;inset:0;margin:auto;width:64px;height:64px;border:0;border-radius:50%;
        background:rgba(26,152,255,.92);color:#fff;font-size:30px;cursor:pointer;display:flex;
        align-items:center;justify-content:center;transition:opacity .15s,transform .15s;line-height:1}
    .jpv__big:hover{transform:scale(1.06)}
    .jpv.is-playing .jpv__big{opacity:0;pointer-events:none}
    .jpv__bar{position:absolute;left:0;right:0;bottom:0;display:flex;align-items:center;gap:8px;
        padding:10px 12px;background:linear-gradient(0deg,rgba(0,0,0,.78),rgba(0,0,0,0));
        opacity:0;transition:opacity .18s;line-height:1}
    .jpv:hover .jpv__bar,.jpv.is-paused .jpv__bar{opacity:1}
    .jpv__btn{background:transparent;border:0;color:#fff;font-size:19px;cursor:pointer;padding:2px 4px;
        display:flex;align-items:center;opacity:.9;transition:opacity .12s,color .12s}
    .jpv__btn:hover{opacity:1;color:#1A98FF}
    .jpv__time{color:#e6e6e6;font-size:12px;font-variant-numeric:tabular-nums;white-space:nowrap;min-width:34px;text-align:center}
    .jpv__range{-webkit-appearance:none;appearance:none;height:4px;border-radius:4px;cursor:pointer;
        background:linear-gradient(to right,#1A98FF 0%,#1A98FF var(--val,0%),rgba(255,255,255,.28) var(--val,0%),rgba(255,255,255,.28) 100%)}
    .jpv__seek{flex:1 1 auto;min-width:60px}
    .jpv__vol{flex:0 0 68px;width:68px}
    .jpv__range::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:12px;height:12px;
        border-radius:50%;background:#fff;border:2px solid #1A98FF;box-shadow:0 0 2px rgba(0,0,0,.5)}
    .jpv__range::-moz-range-thumb{width:12px;height:12px;border-radius:50%;background:#fff;border:2px solid #1A98FF}
    .jpv:fullscreen{background:#000;display:flex;align-items:center}
    .jpv:fullscreen .jpv__video{height:100%;width:auto;margin:0 auto;aspect-ratio:auto}
</style>
<script>
(function () {
    function fmt(s){s=Math.max(0,Math.floor(s||0));var m=Math.floor(s/60),x=s%60;return m+':'+(x<10?'0':'')+x;}
    function setFill(r){var min=+r.min||0,max=+r.max||100,v=+r.value;r.style.setProperty('--val',((v-min)/(max-min)*100)+'%');}

    function wire(root){
        if(root.dataset.jpvReady)return; root.dataset.jpvReady='1';
        var v=root.querySelector('[data-jpv-video]'),
            big=root.querySelector('[data-jpv-big]'),
            toggle=root.querySelector('[data-jpv-toggle]'),
            back=root.querySelector('[data-jpv-back]'),
            fwd=root.querySelector('[data-jpv-fwd]'),
            seek=root.querySelector('[data-jpv-seek]'),
            cur=root.querySelector('[data-jpv-current]'),
            dur=root.querySelector('[data-jpv-duration]'),
            mute=root.querySelector('[data-jpv-mute]'),
            vol=root.querySelector('[data-jpv-vol]'),
            fs=root.querySelector('[data-jpv-fs]'),
            seeking=false;

        function icon(btn,name){var i=btn.querySelector('i');if(i)i.className='ph ph-'+name;}
        function syncPlay(){
            var playing=!v.paused&&!v.ended;
            root.classList.toggle('is-playing',playing);
            root.classList.toggle('is-paused',!playing);
            icon(toggle,playing?'pause':'play');
            icon(big,playing?'pause':'play');
        }
        function syncMute(){
            var m=v.muted||v.volume===0;
            icon(mute,m?'speaker-slash':(v.volume<.5?'speaker-low':'speaker-high'));
            vol.value=v.muted?0:v.volume;setFill(vol);
        }
        function playPause(){v.paused?v.play():v.pause();}

        big.addEventListener('click',playPause);
        toggle.addEventListener('click',playPause);
        v.addEventListener('click',playPause);
        v.addEventListener('play',syncPlay);
        v.addEventListener('pause',syncPlay);
        v.addEventListener('ended',syncPlay);

        back.addEventListener('click',function(){v.currentTime=Math.max(0,v.currentTime-10);});
        fwd.addEventListener('click',function(){v.currentTime=Math.min(v.duration||0,v.currentTime+10);});

        v.addEventListener('loadedmetadata',function(){dur.textContent=fmt(v.duration);seek.max=v.duration||100;});
        v.addEventListener('timeupdate',function(){
            if(seeking)return;
            cur.textContent=fmt(v.currentTime);
            seek.value=v.currentTime;setFill(seek);
        });
        seek.addEventListener('input',function(){seeking=true;cur.textContent=fmt(seek.value);setFill(seek);});
        seek.addEventListener('change',function(){v.currentTime=+seek.value;seeking=false;});

        mute.addEventListener('click',function(){v.muted=!v.muted;syncMute();});
        vol.addEventListener('input',function(){v.muted=false;v.volume=+vol.value;syncMute();});

        fs.addEventListener('click',function(){
            if(document.fullscreenElement){document.exitFullscreen();}
            else if(root.requestFullscreen){root.requestFullscreen();}
        });
        document.addEventListener('fullscreenchange',function(){
            icon(fs,document.fullscreenElement===root?'corners-in':'corners-out');
        });

        setFill(seek);syncMute();syncPlay();
    }

    function init(){document.querySelectorAll('[data-jpv]').forEach(wire);}
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
})();
</script>
@endonce
