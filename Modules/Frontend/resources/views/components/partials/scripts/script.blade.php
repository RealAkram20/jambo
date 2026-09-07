<!-- Lodash Utility -->
<script src="{{ asset('frontend/vendor/lodash/lodash.min.js') }}"></script>
<!-- countdown Script -->
<script src="{{ asset('frontend/js/plugins/countdown.js') }}"></script>
<!-- utility Script -->
<script src="{{ asset('frontend/js/utility.js') }}"></script>
<!-- Jambo Script -->
<script src="{{ versioned_asset('frontend/js/streamit.js') }}" defer></script>
<script src="{{ versioned_asset('frontend/js/swiper.js') }}" defer></script>
{{-- Auto-rotates the big banners only (hero, Movies/Series Today, listing
     banners). Poster rails stay still on purpose. Jambo's file, not the
     template's — see ADR-0001. --}}
<script src="{{ versioned_asset('frontend/js/jambo-banner-rotate.js') }}" defer></script>
