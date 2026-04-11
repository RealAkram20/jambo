@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div id="accordionExample" class="accordion custom-accordion iq-accordion-card">
                <div class="accordion-item">
                    <h2 id="headingOne" class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                            aria-expanded="true"
                            aria-controls="collapseOne">{{ __('dashboardprivacy_policy.Lorem_ipsum_dolor') }}</button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                        data-bs-parent="#accordionExample">
                        <div class="accordion-body"><strong>{{ __('dashboardprivacy_policy.Anim_reprehenderit') }},</strong>
                            {{ __('dashboardprivacy_policy.desc3') }}</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 id="headingTwo" class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseTwo" aria-expanded="false"
                            aria-controls="collapseTwo">{{ __('dashboardprivacy_policy.consectetur_adipiscing') }}</button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                        data-bs-parent="#accordionExample">
                        <div class="accordion-body"><strong>{{ __('dashboardprivacy_policy.Anim_reprehenderit') }},</strong>
                            {{ __('dashboardprivacy_policy.enim_eiusmod') }},
                            <code>{{ __('dashboardprivacy_policy.non_cupidatat') }}</code>
                            {{ __('dashboardprivacy_policy.etiam_sit_non') }}
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 id="headingThree" class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseThree" aria-expanded="false"
                            aria-controls="collapseThree">{{ __('dashboardprivacy_policy.etiam_sit_non') }}</button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                        data-bs-parent="#accordionExample">
                        <div class="accordion-body"><strong>{{ __('dashboardprivacy_policy.Anim_reprehenderit') }},</strong>
                            {{ __('dashboardprivacy_policy.enim_eiusmod_squid') }}. <code>
                                {{ __('dashboardprivacy_policy.non_cupidatat') }},</code>
                            {{ __('dashboardprivacy_policy.non_cupidatat') }}.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 id="headingFour" class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseFour" aria-expanded="false"
                            aria-controls="collapseFour">{{ __('dashboardprivacy_policy.velit_accumsan') }}</button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                        data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <strong>{{ __('dashboardprivacy_policy.Anim_reprehenderit') }},</strong>
                            {{ __('dashboardprivacy_policy.enim_eiusmod') }},<code>
                                {{ __('dashboardprivacy_policy.non_cupidatat') }}</code>
                            {{ __('dashboardprivacy_policy.food_truck') }}.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 id="headingFive" class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseFive" aria-expanded="false"
                            aria-controls="collapseFive">{{ __('dashboardprivacy_policy.donec_volutpat') }}</button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                        data-bs-parent="#accordionExample">
                        <div class="accordion-body"><strong>{{ __('dashboardprivacy_policy.Anim_reprehenderit') }},
                                {{ __('dashboardprivacy_policy.enim_eiusmod_squid') }}.</strong>
                            {{ __('dashboardprivacy_policy.moon_officia') }},<code>
                                {{ __('dashboardprivacy_policy.non_cupidatat') }}</code>
                            {{ __('dashboardprivacy_policy.food_truck') }}.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
