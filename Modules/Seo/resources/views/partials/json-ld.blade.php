{{--
    Renders one or more schema.org graphs as JSON-LD.

    Usage from a content page (must be at top level, outside
    @section('content'), so it's evaluated before the layout renders
    the @stack('seo:head') in head-tags):

        @push('seo:head')
            @include('seo::partials.json-ld', ['schemas' => [
                \Modules\Seo\app\Support\StructuredData::movie($movie),
                \Modules\Seo\app\Support\StructuredData::breadcrumbs([...]),
            ]])
        @endpush

    Empty graphs are skipped — StructuredData returns [] rather than
    throwing when a model is too incomplete to describe (an orphaned
    episode, a video with no thumbnail), and an empty <script> block
    is worse than no block at all.

    JSON_UNESCAPED_SLASHES keeps URLs readable in view-source;
    JSON_UNESCAPED_UNICODE keeps non-ASCII titles intact. HEX_TAG /
    HEX_AMP / HEX_APOS / HEX_QUOT escape the characters that could
    otherwise break out of the <script> element — a synopsis containing
    "</script>" would end the block early and dump the rest of the JSON
    into the DOM as text.
--}}
@php
    $ldBlocks = [];

    foreach ($schemas ?? [] as $schema) {
        if (empty($schema) || !is_array($schema)) {
            continue;
        }

        $json = json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        // json_encode returns false on malformed UTF-8 (a synopsis
        // pasted from Word can carry invalid bytes). Skip that one
        // graph rather than emitting the literal string "false".
        if ($json !== false) {
            $ldBlocks[] = $json;
        }
    }
@endphp

@foreach ($ldBlocks as $ldJson)
    <script type="application/ld+json">{!! $ldJson !!}</script>
@endforeach
