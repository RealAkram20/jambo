@extends('frontend::layouts.blank')

@section('content')
    <div class="mx-3">
      <div class="row">
        <div class="col-sm-12 p-0">
          <div class="coming-soon-background" style="background-image: url('{{asset('./frontend/images/pages/01.webp')}}')">
            <div class="st-coming">
              <div class="st-coming-inner rounded-3">
                <div class="iq-maintenance-message">
                  <h1 class="iq-maintenance-title">{{__('frontendheader.coming_soon')}}</h1>
                  <p class="iq-maintenance-desc">{{__('streamPrivacypolicy.we_will_be')}}</p>
                </div>

                <div class="expire_date"></div>
                <ul class="list-inline example mb-0 ps-0 countdown" data-date="Dec 30 2025 20:20:22">
                  <li>
                    <span class="days" data-days>13</span>
                    <p class="days_text">{{__('frontendcountdown.days')}}</p>
                  </li>
                  <li>
                    <span class="hours" data-hours>02</span>
                    <p class="hours_text">{{__('frontendcountdown.hours')}}</p>
                  </li>
                  <li>
                    <span class="minutes" data-minutes>18</span>
                    <p class="minutes_text">{{__('frontendcountdown.minutes')}}</p>
                  </li>
                  <li>
                    <span class="seconds" data-seconds>53</span>
                    <p class="seconds_text">{{__('frontendcountdown.seconds')}}</p>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
@endsection
