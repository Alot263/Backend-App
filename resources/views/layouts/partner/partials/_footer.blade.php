<div class="footer">
    <div class="row justify-content-between align-items-center">
        <div class="col">
            <p class="font-size-sm mb-0">
                &copy; {{Str::limit(\App\CentralLogics\Helpers::get_delivery_company_data()->name, 50, '...')}}. <span
                    class="d-none d-sm-inline-block"></span>
            </p>
        </div>
        <div class="col-auto">
            <div class="d-flex justify-content-end">
                <!-- List Dot -->
                <ul class="list-inline list-separator">
                    <li class="list-inline-item">
                        <a class="list-separator-link" href="{{route('partner.business-settings.delivery-company-setup')}}">{{translate('messages.delivery_company')}} {{translate('messages.settings')}}</a>
                    </li>

                    <li class="list-inline-item">
                        <a class="list-separator-link" href="{{route('partner.shop.view')}}">{{translate('messages.profile')}}</a>
                    </li>

                    <li class="list-inline-item">
                        <!-- Keyboard Shortcuts Toggle -->
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle"
                               href="{{route('partner.dashboard')}}">
                                <i class="tio-home-outlined"></i>
                            </a>
                        </div>
                        <!-- End Keyboard Shortcuts Toggle -->
                    </li>
                    <li class="list-inline-item">
                        <label class="badge badge-soft-primary m-0">
                            {{translate('messages.software_version')}} : {{env('SOFTWARE_VERSION')}}
                        </label>
                    </li>
                </ul>
                <!-- End List Dot -->
            </div>
        </div>
    </div>
</div>
