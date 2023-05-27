@extends('layouts.admin.app')

@section('title',$delivery_company->name)

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">

    @include('admin-views.delivery-partner.view.partials._header',['delivery_company'=>$delivery_company])

    <!-- Page Heading -->
    <div class="row g-3 text-capitalize">
        <!-- Earnings (Monthly) Card Example -->
        <div class="col-md-4">
            <div class="card h-100 card--bg-1">
                <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                    <h5 class="cash--subtitle text-white">
                        {{translate('messages.collected_cash_by_delivery_company')}}
                    </h5>
                    <div class="d-flex align-items-center justify-content-center mt-3">
                        <div class="cash-icon mr-3">
                            <img src="{{asset('public/assets/admin/img/cash.png')}}" alt="img">
                        </div>
                        <h2 class="cash--title text-white">{{\App\CentralLogics\Helpers::format_currency($wallet->collected_cash)}}</h2>
                    </div>
                </div>
                <div class="card-footer pt-0 bg-transparent border-0">
                        <a class="btn text-white text-capitalize bg--title h--45px w-100" href="{{$delivery_company->partner->status ? route('admin.transactions.account-transaction.index') : '#'}}" title="{{translate('messages.goto')}} {{translate('messages.account_transaction')}}">{{translate('messages.collect_cash_from_delivery_company')}}</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row g-3">
                <!-- Panding Withdraw Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card card--bg-2">
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->pending_withdraw)}}</h4>
                        <div class="subtitle">{{translate('messages.pending')}} {{translate('messages.withdraw')}}</div>
                        <img class="resturant-icon w--30" src="{{asset('public/assets/admin/img/transactions/pending.png')}}" alt="transaction">
                    </div>
                </div>

                <!-- Earnings (Monthly) Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card card--bg-3">
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->total_withdrawn)}}</h4>
                        <div class="subtitle">{{translate('messages.total_withdrawal_amount')}}</div>
                        <img class="resturant-icon w--30" src="{{asset('public/assets/admin/img/transactions/withdraw-amount.png')}}" alt="transaction">
                    </div>
                </div>

                <!-- Collected Cash Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card card--bg-4">
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->balance)}}</h4>
                        <div class="subtitle">{{translate('messages.withdraw_able_balance')}}</div>
                        <img class="resturant-icon w--30" src="{{asset('public/assets/admin/img/transactions/withdraw-balance.png')}}" alt="transaction">
                    </div>
                </div>

                <!-- Pending Requests Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card card--bg-1">
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->total_earning)}}</h4>
                        <div class="subtitle">{{translate('messages.total_earning')}}</div>
                        <img class="resturant-icon w--30" src="{{asset('public/assets/admin/img/transactions/earning.png')}}" alt="transaction">
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title m-0 d-flex align-items-center">
                <span class="card-header-icon mr-2">
                    <i class="tio-shop-outlined"></i>
                </span>
                <span class="ml-1">{{translate('messages.delivery_company')}} {{translate('messages.info')}}</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-lg-6">
                    <div class="resturant--info-address">
                        <div class="logo">
                            <img onerror="this.src='{{asset('public/assets/admin/img/100x100/1.png')}}'"
                        src="{{asset('storage/app/public/delivery_company')}}/{{$delivery_company->logo}}" alt="{{$delivery_company->name}} Logo">
                        </div>
                        <ul class="address-info list-unstyled list-unstyled-py-3 text-dark">
                            <li>
                                <h5 class="name">{{$delivery_company->name}}</h5>
                            </li>
                            <li>
                                <i class="tio-city nav-icon"></i>
                                <span>{{translate('messages.address')}}</span> <span>:</span> <span>{{$delivery_company->address}}</span>
                            </li>

                            <li>
                                <i class="tio-call-talking nav-icon"></i>
                                <span>{{translate('messages.email')}}</span> <span>:</span> <span>{{$delivery_company->email}}</span>
                            </li>
                            <li>
                                <i class="tio-email nav-icon"></i>
                                <span>{{translate('messages.phone')}}</span> <span>:</span> <span>{{$delivery_company->phone}}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div id="map" class="single-page-map"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row pt-3 g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0 d-flex align-items-center">
                        <span class="card-header-icon mr-2">
                            <i class="tio-user"></i>
                        </span>
                        <span class="ml-1">{{translate('messages.owner')}} {{translate('messages.info')}}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="resturant--info-address">
                        <div class="avatar avatar-xxl avatar-circle avatar-border-lg">
                            <img class="avatar-img" onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                        src="{{asset('storage/app/public/partner')}}/{{$delivery_company->partner->image}}" alt="Image Description">
                        </div>
                        <ul class="address-info address-info-2 list-unstyled list-unstyled-py-3 text-dark">
                            <li>
                                <h5 class="name">{{$delivery_company->partner->f_name}} {{$delivery_company->partner->l_name}}</h5>
                            </li>
                            <li>
                                <i class="tio-call-talking nav-icon"></i>
                                <span class="pl-1">{{$delivery_company->partner->email}}</span>
                            </li>
                            <li>
                                <i class="tio-email nav-icon"></i>
                                <span class="pl-1">{{$delivery_company->partner->phone}}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0 d-flex align-items-center">
                        <span class="card-header-icon mr-2">
                            <i class="tio-museum"></i>
                        </span>
                        <span class="ml-1">{{translate('messages.bank_info')}}</span>
                    </h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <ul class="list-unstyled list-unstyled-py-3 text-dark">
                        @if($delivery_company->partner->bank_name)
                        <li class="pb-1 pt-1">
                            <strong class="text--title">{{translate('messages.bank_name')}}:</strong> {{$delivery_company->partner->bank_name ? $delivery_company->partner->bank_name : 'No Data found'}}
                        </li>
                        <li class="pb-1 pt-1">
                            <strong class="text--title">{{translate('messages.branch')}}  :</strong> {{$delivery_company->partner->branch ? $delivery_company->partner->branch : 'No Data found'}}
                        </li>
                        <li class="pb-1 pt-1">
                            <strong class="text--title">{{translate('messages.holder_name')}} :</strong> {{$delivery_company->partner->holder_name ? $delivery_company->partner->holder_name : 'No Data found'}}
                        </li>
                        <li class="pb-1 pt-1">
                            <strong class="text--title">{{translate('messages.account_no')}}  :</strong> {{$delivery_company->partner->account_no ? $delivery_company->partner->account_no : 'No Data found'}}
                        </li>
                        @else
                        <li class="my-auto">
                            <center class="card-subtitle">{{ translate('messages.No Data found') }}</center>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <!-- Page level plugins -->
    <script>
        // Call the dataTables jQuery plugin
        $(document).ready(function () {
            $('#dataTable').DataTable();
        });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{\App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value}}&callback=initMap&v=3.45.8" ></script>
    <script>
        const myLatLng = { lat: {{$delivery_company->latitude}}, lng: {{$delivery_company->longitude}} };
        let map;
        initMap();
        function initMap() {
                 map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: myLatLng,
            });
            new google.maps.Marker({
                position: myLatLng,
                map,
                title: "{{$delivery_company->name}}",
            });
        }
    </script>
    <script>
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

            $('#column3_search').on('change', function () {
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

    function request_alert(url, message) {
        Swal.fire({
            title: '{{translate('messages.are_you_sure')}}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: '{{translate('messages.no')}}',
            confirmButtonText: '{{translate('messages.yes')}}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = url;
            }
        })
    }
    </script>
@endpush
