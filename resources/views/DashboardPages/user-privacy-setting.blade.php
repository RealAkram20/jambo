@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ __('header.privacy_setting') }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="acc-privacy">
                            <div class="data-privacy">
                                <h4 class="mb-2">{{ __('header.account_privacy') }}</h4>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="customCheck5">
                                    <label class="form-check-label pl-2"
                                        for="customCheck5">{{ __('header.private_account') }}</label>
                                </div>
                                <p>{{ __('header.type_specimen_book') }}</p>
                            </div>
                            <hr>
                            <div class="data-privacy">
                                <h4 class="mb-2">{{ __('header.activity_status') }}</h4>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="activety">
                                    <label class="form-check-label pl-2"
                                        for="activety">{{ __('header.show_activity_status') }}</label>
                                </div>
                                <p>{{ __('header.long_established') }}</p>
                            </div>
                            <hr>
                            <div class="data-privacy">
                                <h4 class="mb-2">{{ __('header.story_sharing') }}</h4>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="story">
                                    <label class="form-check-label pl-2"
                                        for="story">{{ __('header.allow_sharing') }}</label>
                                </div>
                                <p>{{ __('header.long_established') }}</p>
                            </div>
                            <hr>
                            <div class="data-privacy">
                                <h4 class="mb-2">{{ __('header.photo_of_you') }}</h4>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio0" id="automatically"
                                        checked="">
                                    <label for="automatically"
                                        class="form-check-label pl-2">{{ __('header.add_automatically') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio0" id="manualy">
                                    <label for="manualy"
                                        class="form-check-label pl-2">{{ __('header.add_manualy') }}</label>
                                </div>
                                <p>{{ __('header.long_established') }}</p>
                            </div>
                            <hr>
                            <div class="data-privacy">
                                <h4 class="mb-2"> {{ __('header.your_profile') }} </h4>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio1" id="public">
                                    <label for="public" class="form-check-label pl-2">{{ __('header.public') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio1" id="friend">
                                    <label for="friend" class="form-check-label pl-2">{{ __('header.friend') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio1" id="spfriend">
                                    <label for="spfriend"
                                        class="form-check-label pl-2">{{ __('header.specific_friend') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio1" id="onlyme">
                                    <label for="onlyme" class="form-check-label pl-2">{{ __('header.only_me') }}</label>
                                </div>
                                <p>{{ __('header.long_established') }}</p>
                            </div>
                            <hr>
                            <div class="data-privacy">
                                <h4 class="mb-2"> {{ __('header.login_notification') }} </h4>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio2" id="enable">
                                    <label for="enable" class="form-check-label pl-2">{{ __('header.enable') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="customRadio2" id="disable">
                                    <label for="disable" class="form-check-label pl-2">{{ __('header.disable') }}</label>
                                </div>
                                <p>{{ __('header.long_established') }}</p>
                            </div>
                            <hr>
                            <div class="data-privacy">
                                <h4 class="mb-2">{{ __('header.privacy_help') }}</h4>
                                <a href="#"><i
                                        class="ri-customer-service-2-line me-2"></i>{{ __('header.support') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
