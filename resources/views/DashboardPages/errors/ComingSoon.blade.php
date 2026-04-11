@extends('layouts.guest')

@section('content')
    <div class="mx-3">
        <div class="row">
            <div class="coming-soon-background"
                style="background-image: url('{{ asset('dashboard/images/login/login.webp') }}">
                <div class="col-sm-12">
                    <div class="st-coming">
                        <div class="st-coming-inner text-white rounded-3">
                            <div class="iq-maintenance-message">
                                <h1 class="iq-maintenance-title text-white">Coming Soon</h1>
                                <p class="iq-maintenance-desc">We will be back with new and
                                    professional Ideas</p>
                            </div>
                            <div class="expire_date" id="March30,2025"></div>
                            <ul class="list-inline example mb-0 ps-0 countdown" data-date="May 30 2025 20:20:22">
                                <li>
                                    <span class="days" data-days>13</span>
                                    <p class="days_text">Days</p>
                                </li>
                                <li>
                                    <span class="hours" data-hours>02</span>
                                    <p class="hours_text">Hours</p>
                                </li>
                                <li>
                                    <span class="minutes" data-minutes>18</span>
                                    <p class="minutes_text">Minutes</p>
                                </li>
                                <li>
                                    <span class="seconds" data-seconds>53</span>
                                    <p class="seconds_text">Seconds</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
