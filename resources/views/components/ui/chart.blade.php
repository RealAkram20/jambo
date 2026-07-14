@props([
    'type' => 'area',       // area|line|bar|donut|radialBar ...
    'height' => 300,
    'series' => [],         // PHP array → JS
    'options' => [],        // extra ApexCharts options merged over defaults
    'colors' => null,       // array of hex; defaults to Bootstrap primary
])

@php
    // Sensible, theme-neutral defaults so every chart looks consistent.
    $defaults = [
        'chart'      => ['type' => $type, 'height' => (int) $height, 'toolbar' => ['show' => false], 'fontFamily' => 'inherit', 'parentHeightOffset' => 0],
        'dataLabels' => ['enabled' => false],
        'stroke'     => ['curve' => 'smooth', 'width' => $type === 'bar' ? 0 : 2.5],
        'grid'       => ['borderColor' => 'rgba(128,128,128,.15)', 'strokeDashArray' => 4, 'padding' => ['left' => 4, 'right' => 4]],
        'fill'       => $type === 'area'
                        ? ['type' => 'gradient', 'gradient' => ['shadeIntensity' => 1, 'opacityFrom' => .35, 'opacityTo' => .02, 'stops' => [0, 90]]]
                        : ['opacity' => 1],
        'legend'     => ['position' => 'bottom', 'fontSize' => '12px', 'markers' => ['radius' => 12]],
        'tooltip'    => ['theme' => 'dark'],
    ];
    if ($colors) $defaults['colors'] = $colors;

    $merged = array_replace_recursive($defaults, $options);
    $merged['series'] = $series;
@endphp

<div
    x-data="{
        chart: null,
        init() {
            if (typeof ApexCharts === 'undefined') {
                this.$el.innerHTML = '<div class=&quot;text-danger small p-3&quot;>ApexCharts JS not loaded. Import it in your bundle or add the CDN.</div>';
                return;
            }
            this.chart = new ApexCharts(this.$refs.canvas, @js($merged));
            this.chart.render();
        }
    }"
    x-init="init()"
    {{ $attributes }}
>
    <div x-ref="canvas"></div>
</div>
