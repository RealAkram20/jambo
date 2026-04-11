<!-- Footer Section Start -->
<footer class="footer">
    <div class="footer-body">
        <ul class="left-panel list-inline mb-0 p-0">
            <li class="list-inline-item"><a
                    href="{{ route('dashboard.privacy-policy') }}">{{ __('frontendheader.privacy_policy') }}</a></li>
            <li class="list-inline-item"><a
                    href="{{ route('dashboard.terms-of-use') }}">{{ __('header.terms_of_use') }}</a></li>
        </ul>
        <div class="right-panel">
            &copy; {{ date('Y') }} <span data-setting="app_name">{{ setting('app_name') ?: 'Jambo' }}</span>. All rights reserved.
        </div>
    </div>
</footer>
<!-- Footer Section End -->
