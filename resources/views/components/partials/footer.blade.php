<!-- Footer Section Start -->
<footer class="footer">
    <div class="footer-body">
        <ul class="left-panel list-inline mb-0 p-0">
            <li class="list-inline-item"><a
                    href="{{ route('frontend.privacy-policy') }}">{{ __('frontendheader.privacy_policy') }}</a></li>
            <li class="list-inline-item"><a
                    href="{{ route('frontend.terms-and-policy') }}">{{ __('header.terms_of_use') }}</a></li>
        </ul>
        <div class="right-panel">
            &copy; {{ date('Y') }} {{ setting('app_name') ?: 'Jambo' }}. All rights reserved.
        </div>
    </div>
</footer>
<!-- Footer Section End -->
