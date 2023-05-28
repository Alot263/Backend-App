    <!-- Page Header -->
    <div class="page-header pb-0">
        <div class="d-flex justify-content-between">
            <div>
                <h1 class="page-header-title text-break">
                    <span class="page-header-icon">
                        <img src="{{asset('public/assets/admin/img/delivery_company.png')}}" class="w--26" alt="">
                    </span>
                    <span>{{$delivery_company->name}}</span>
                </h1>
            </div>
            <div>
                @if(Request::is("admin/delivery-company/view/{$delivery_company->id}"))
                    @if($delivery_company->partner->status)
                    <a href="{{route('admin.delivery-company.edit',[$delivery_company->id])}}" class="btn btn--primary float-right">
                        <i class="tio-edit"></i> {{translate('messages.edit')}} {{translate('messages.delivery_company')}}
                    </a>
                    @else
                        @if(!isset($delivery_company->partner->status))
                        <a class="btn btn--danger text-capitalize font-weight-bold float-right"
                        onclick="request_alert('{{route('admin.delivery-company.application',[$delivery_company['id'],0])}}','{{translate('messages.you_want_to_deny_this_application')}}')"
                            href="javascript:"><i class="tio-clear-circle-outlined font-weight-bold pr-1"></i> {{translate('messages.deny')}}</a>
                        @endif
                        <a class="btn btn--primary text-capitalize font-weight-bold float-right mr-2"
                        onclick="request_alert('{{route('admin.delivery-company.application',[$delivery_company['id'],1])}}','{{translate('messages.you_want_to_approve_this_application')}}')"
                            href="javascript:"><i class="tio-checkmark-circle-outlined font-weight-bold pr-1"></i>{{translate('messages.approve')}}</a>
                    @endif
                @endif
            </div>
        </div>
        @if($delivery_company->partner->status)
        <!-- Nav Scroller -->
        <div class="js-nav-scroller hs-nav-scroller-horizontal">
            <span class="hs-nav-scroller-arrow-prev d-none">
                <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                    <i class="tio-chevron-left"></i>
                </a>
            </span>

            <span class="hs-nav-scroller-arrow-next d-none">
                <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                    <i class="tio-chevron-right"></i>
                </a>
            </span>

            <!-- Nav -->
            <ul class="nav nav-tabs page-header-tabs mb-2">
                <li class="nav-item">
                    <a class="nav-link {{request('tab')==null?'active':''}}" href="{{route('admin.delivery-company.view', $delivery_company->id)}}">{{translate('messages.overview')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='order'?'active':''}}" href="{{route('admin.delivery-company.view', ['delivery_company'=>$delivery_company->id, 'tab'=> 'order'])}}"  aria-disabled="true">{{translate('messages.orders')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='reviews'?'active':''}}" href="{{route('admin.delivery-company.view', ['delivery_company'=>$delivery_company->id, 'tab'=> 'reviews'])}}"  aria-disabled="true">{{translate('messages.reviews')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='discount'?'active':''}}" href="{{route('admin.delivery-company.view', ['delivery_company'=>$delivery_company->id, 'tab'=> 'discount'])}}"  aria-disabled="true">{{translate('messages.discounts')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='transaction'?'active':''}}" href="{{route('admin.delivery-company.view', ['delivery_company'=>$delivery_company->id, 'tab'=> 'transaction'])}}"  aria-disabled="true">{{translate('messages.transactions')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='settings'?'active':''}}" href="{{route('admin.delivery-company.view', ['delivery_company'=>$delivery_company->id, 'tab'=> 'settings'])}}"  aria-disabled="true">{{translate('messages.settings')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='conversations'?'active':''}}" href="{{route('admin.delivery-company.view', ['delivery_company'=>$delivery_company->id, 'tab'=> 'conversations'])}}"  aria-disabled="true">{{translate('Conversations')}}</a>
                </li>
            </ul>
            <!-- End Nav -->
        </div>
        <!-- End Nav Scroller -->
        @endif
    </div>
    <!-- End Page Header -->
