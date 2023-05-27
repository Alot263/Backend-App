@extends('layouts.admin.app')

@section('title',translate('Delivery Company List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> {{translate('messages.')}} <span
                    class="badge badge-soft-dark ml-2" id="itemCount">{{$delivery_companies->total()}}</span></h1>
            <div class="page-header-select-wrapper">

                {{-- <div class="select-item">
                    <select name="module_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{url()->full()}}',this.value,'module_id')" title="{{translate('messages.select')}} {{translate('messages.modules')}}">
                        <option value="" {{!request('module_id') ? 'selected':''}}>{{translate('messages.all')}} {{translate('messages.modules')}}</option>
                        @foreach (\App\Models\Module::notParcel()->get() as $module)
                            <option
                                value="{{$module->id}}" {{request('module_id') == $module->id?'selected':''}}>
                                {{$module['module_name']}}
                            </option>
                        @endforeach
                    </select>
                </div> --}}
                @if(!isset(auth('admin')->user()->zone_id))
                    <div class="select-item">
                        <select name="zone_id" class="form-control js-select2-custom"
                                onchange="set_filter('{{url()->full()}}',this.value,'zone_id')">
                            <option
                                value="" {{!request('zone_id')?'selected':''}}>{{ translate('messages.All Zones') }}</option>
                            @foreach(\App\Models\Zone::orderBy('name')->get() as $z)
                                <option
                                    value="{{$z['id']}}" {{isset($zone) && $zone->id == $z['id']?'selected':''}}>
                                    {{$z['name']}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
        <!-- End Page Header -->


        <!-- Resturent Card Wrapper -->
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-1">
                    @php($total_delivery_company = \App\Models\DeliveryCompany::whereHas('partner', function($query){
                        return $query->where('status', 1);
                    })->where('module_id', Config::get('module.current_module_id'))->count())
                    @php($total_delivery_company = isset($total_delivery_company) ? $total_delivery_company : 0)
                    <h4 class="title">{{$total_delivery_company}}</h4>
                    <span class="subtitle">{{translate('messages.total_delivery_companies')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/total-delivery-company.png')}}" alt="delivery company">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-2">
                    @php($active_delivery_companies = \App\Models\DeliveryCompany::where(['status'=>1])->where('module_id', Config::get('module.current_module_id'))->count())
                    @php($active_delivery_companies = isset($active_delivery_companies) ? $active_delivery_companies : 0)
                    <h4 class="title">{{$active_delivery_companies}}</h4>
                    <span class="subtitle">{{translate('messages.active_deliery_companies')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/active-delivery-company.png')}}"
                         alt="Delivery Company">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-3">
                    @php($inactive_delivery_companies = \App\Models\DeliveryCompany::whereHas('partner', function($query){
                        return $query->where('status', 1);
                    })->where(['status'=>0])->where('module_id', Config::get('module.current_module_id'))->count())
                    @php($inactive_delivery_companies = isset($inactive_delivery_companies) ? $inactive_delivery_companies : 0)
                    <h4 class="title">{{$inactive_delivery_companies}}</h4>
                    <span class="subtitle">{{translate('messages.inactive_delivery_companies')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/close-delivery-company.png')}}" alt="delivery company">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-4">
                    @php($data = \App\Models\DeliveryCompany::where('created_at', '>=', now()->subDays(30)->toDateTimeString())->where('module_id', Config::get('module.current_module_id'))->count())
                    <h4 class="title">{{$data}}</h4>
                    <span class="subtitle">{{translate('messages.newly_joined_delivery_companies')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/add-delivery-company.png')}}" alt="delivery company">
                </div>
            </div>
        </div>
        <!-- Resturent Card Wrapper -->
        <!-- Transaction Information -->
        <ul class="transaction--information text-uppercase">
            <li class="text--info">
                <i class="tio-document-text-outlined"></i>
                <div>
                    @php($total_transaction = \App\Models\OrderTransaction::where('module_id', Config::get('module.current_module_id'))->count())
                    @php($total_transaction = isset($total_transaction) ? $total_transaction : 0)
                    <span>{{translate('messages.total_transactions')}}</span> <strong>{{$total_transaction}}</strong>
                </div>
            </li>
            <li class="seperator"></li>
            <li class="text--success">
                <i class="tio-checkmark-circle-outlined success--icon"></i>
                <div>
                    @php($comission_earned = \App\Models\AdminWallet::sum('total_commission_earning'))
                    @php($comission_earned = isset($comission_earned) ? $comission_earned : 0)
                    <span>{{translate('messages.commission_earned')}}</span>
                    <strong>{{\App\CentralLogics\Helpers::format_currency($comission_earned)}}</strong>
                </div>
            </li>
            <li class="seperator"></li>
            <li class="text--danger">
                <i class="tio-atm"></i>
                <div>
                    @php($delivery_company_withdraws = \App\Models\WithdrawRequest::where(['approved'=>1])->sum('amount'))
                    @php($delivery_company_withdraws = isset($delivery_company_withdraws) ? $delivery_company_withdraws : 0)
                    <span>{{translate('messages.total_delivery_company_withdraws')}}</span>
                    <strong>{{\App\CentralLogics\Helpers::format_currency($delivery_company_withdraws)}}</strong>
                </div>
            </li>
        </ul>
        <!-- Transaction Information -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{translate('messages.delivery_companies')}} {{translate('messages.list')}}</h5>
                    <form action="javascript:" id="search-form" class="search-form">
                        <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                   placeholder="{{translate('ex_:_Search_Delivery_Company_Name')}}"
                                   aria-label="{{translate('messages.search')}}" required>
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>
                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40"
                           href="javascript:;"
                           data-hs-unfold-options='{
                                    "target": "#usersExportDropdown",
                                    "type": "css-animation"
                                }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                             class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                            {{-- <span class="dropdown-header">{{ translate('messages.options') }}</span>
                            <a id="export-copy" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/illustrations/copy.svg"
                                    alt="Image Description">
                                {{ translate('messages.copy') }}
                            </a>
                            <a id="export-print" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/illustrations/print.svg"
                                    alt="Image Description">
                                {{ translate('messages.print') }}
                            </a>
                            <div class="dropdown-divider"></div> --}}
                            <span class="dropdown-header">{{ translate('messages.download') }}
                                {{ translate('messages.options') }}</span>
                            <a id="export-excel" class="dropdown-item"
                               href="{{route('admin.delivery-company.export', ['type'=>'excel',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                     alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                               href="{{route('admin.delivery-company.export', ['type'=>'csv',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                     alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>
                            {{-- <a id="export-pdf" class="dropdown-item" href="{{route('admin.delivery-company.export', ['type'=>'excel',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/pdf.svg"
                                    alt="Image Description">
                                {{ translate('messages.pdf') }}
                            </a> --}}
                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                       class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                       data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{translate('sl')}}</th>
                        <th class="border-0">{{translate('messages.delivery_company_information')}}</th>
                        <th class="border-0">{{translate('messages.module')}}</th>
                        <th class="border-0">{{translate('messages.owner_information')}}</th>
                        <th class="border-0">{{translate('messages.zone')}}</th>
                        <th class="text-uppercase border-0">{{translate('messages.featured')}}</th>
                        <th class="text-uppercase border-0">{{translate('messages.status')}}</th>
                        <th class="text-center border-0">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($delivery_companies as $key=>$delivery_company)
                        <tr>
                            <td>{{$key+$delivery_companies->firstItem()}}</td>
                            <td>
                                <div>
                                    <a href="{{route('admin.delivery-company.view', $delivery_company->id)}}"
                                       class="table-rest-info" alt="view delivery company">
                                        <img class="img--60 circle"
                                             onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                                             src="{{asset('storage/app/public/delivery_company')}}/{{$delivery_company['logo']}}">
                                        <div class="info">
                                            <div class="text--title">
                                                {{Str::limit($delivery_company->name,20,'...')}}
                                            </div>
                                            <div class="font-light">
                                                {{translate('messages.id')}}:{{$delivery_company->id}}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{Str::limit($delivery_company->module->module_name,20,'...')}}
                                </span>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{Str::limit($delivery_company->partner->f_name.' '.$delivery_company->partner->l_name,20,'...')}}
                                </span>
                                <div>
                                    {{$delivery_company['phone']}}
                                </div>
                            </td>
                            <td>
                                {{$delivery_company->zone?$delivery_company->zone->name:translate('messages.zone').' '.translate('messages.deleted')}}
                                {{--<span class="d-block font-size-sm">{{$banner['image']}}</span>--}}
                            </td>
                            <td>
                                <label class="toggle-switch toggle-switch-sm"
                                       for="featuredCheckbox{{$delivery_company->id}}">
                                    <input type="checkbox"
                                           onclick="location.href='{{route('admin.delivery-company.featured',[$delivery_company->id,$delivery_company->featured?0:1])}}'"
                                           class="toggle-switch-input"
                                           id="featuredCheckbox{{$delivery_company->id}}" {{$delivery_company->featured?'checked':''}}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </td>

                            <td>
                                @if(isset($delivery_company->partner->status))
                                    @if($delivery_company->partner->status)
                                        <label class="toggle-switch toggle-switch-sm"
                                               for="stocksCheckbox{{$delivery_company->id}}">
                                            <input type="checkbox"
                                                   onclick="status_change_alert('{{route('admin.delivery-company.status',[$delivery_company->id,$delivery_company->status?0:1])}}', '{{translate('messages.you_want_to_change_this_delivery_company_status')}}', event)"
                                                   class="toggle-switch-input"
                                                   id="stocksCheckbox{{$delivery_company->id}}" {{$delivery_company->status?'checked':''}}>
                                            <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                        </label>
                                    @else
                                        <span class="badge badge-soft-danger">{{translate('messages.denied')}}</span>
                                    @endif
                                @else
                                    <span class="badge badge-soft-danger">{{translate('messages.pending')}}</span>
                                @endif
                            </td>

                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                       href="{{route('admin.delivery-company.edit',[$delivery_company['id']])}}"
                                       title="{{translate('messages.edit')}} {{translate('messages.delivery_company')}}"><i
                                            class="tio-edit"></i>
                                    </a>
                                    <a class="btn action-btn btn--danger btn-outline-danger" href="javascript:"
                                       onclick="form_alert('partner-{{$delivery_company['id']}}','{{translate('You want to remove this delivery company')}}')"
                                       title="{{translate('messages.delete')}} {{translate('messages.delivery_company')}}"><i
                                            class="tio-delete-outlined"></i>
                                    </a>
                                    <form action="{{route('admin.delivery-company.delete',[$delivery_company['id']])}}"
                                          method="post" id="partner-{{$delivery_company['id']}}">
                                        @csrf @method('delete')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if(count($delivery_companies) !== 0)
                    <hr>
                @endif
                <div class="page-area">
                    {!! $delivery_companies->withQueryString()->links() !!}
                </div>
                @if(count($delivery_companies) === 0)
                    <div class="empty--data">
                        <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                        <h5>
                            {{translate('no_data_found')}}
                        </h5>
                    </div>
                @endif

            </div>
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }

        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function () {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function () {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function () {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function () {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script>
        $('#search-form').on('submit', function () {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.delivery-company.search')}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.total);
                    $('.page-area').hide();
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush