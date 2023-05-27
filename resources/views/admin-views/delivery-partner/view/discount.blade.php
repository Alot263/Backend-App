@extends('layouts.admin.app')

@section('title',$delivery_company->name."'s ".translate('messages.discount'))

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">

@endpush

@section('content')
<div class="content container-fluid">
    @include('admin-views.delivery-partner.view.partials._header',['delivery_company'=>$delivery_company])
    <!-- Page Heading -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="partner">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-info"></i>
                        <span>{{translate('messages.discount')}} {{translate('messages.info')}}</span>
                    </h5>
                    <div class="btn--container justify-content-end">
                        @if($delivery_company->discount)
                        <button type="button" class="btn-sm btn--primary" data-toggle="modal" data-target="#updatesettingsmodal">
                            <i class="tio-open-in-new"></i> {{translate('messages.update')}}
                        </button>
                        <button type="button" onclick="form_alert('discount-{{$delivery_company->id}}','{{ translate('Want to remove discount?') }}')" class="btn btn--danger text-white"><i class="tio-delete-outlined"></i>  {{translate('messages.delete')}}</button>
                        @else
                        <button type="button" class="btn-sm btn--primary" data-toggle="modal" data-target="#updatesettingsmodal">
                            <i class="tio-add"></i> {{translate('messages.add').' '.translate('messages.discount')}}
                        </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($delivery_company->discount)
                    <div class="text--info mb-3">
                        {{translate('* This discount is applied on all the items in your delivery company')}}
                    </div>
                    <div class="row gy-3">
                        <div class="col-md-4 align-self-center text-center">

                            <div class="discount-item text-center">
                                <h5 class="subtitle">{{translate('messages.discount')}} {{translate('messages.amount')}}</h5>
                                <h4 class="amount">{{$delivery_company->discount?round($delivery_company->discount->discount):0}}%</h4>
                            </div>

                        </div>
                        <div class="col-md-4 text-center text-md-left">
                            <div class="discount-item">
                                <h5 class="subtitle">{{translate('messages.duration')}}</h5>
                                <ul class="list-unstyled list-unstyled-py-3 text-dark">
                                    <li class="p-0 pt-1 justify-content-center justify-content-md-start">
                                        <span>{{translate('messages.start')}} {{translate('messages.date')}} :</span>
                                        <strong>{{$delivery_company->discount?date('Y-m-d',strtotime($delivery_company->discount->start_date)):''}} {{$delivery_company->discount?date(config('timeformat'), strtotime($delivery_company->discount->start_time)):''}}</strong>
                                    </li>
                                    <li class="p-0 pt-1 justify-content-center justify-content-md-start">
                                        <span>{{translate('messages.end')}} {{translate('messages.date')}} :</span>
                                        <strong>{{$delivery_company->discount?date('Y-m-d', strtotime($delivery_company->discount->end_date)):''}} {{$delivery_company->discount?date(config('timeformat'), strtotime($delivery_company->discount->end_time)):''}}</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4 text-center text-md-left">

                            <h5 class="subtitle">{{translate('purchase_conditions')}}</h5>

                            <ul class="list-unstyled list-unstyled-py-3 text-dark">
                                <li class="p-0 pt-1 justify-content-center justify-content-md-start">
                                    <span>{{translate('messages.max')}} {{translate('messages.discount')}} :</span>
                                    <strong>{{\App\CentralLogics\Helpers::format_currency($delivery_company->discount?$delivery_company->discount->max_discount:0)}}</strong>
                                </li>
                                <li class="p-0 pt-1 justify-content-center justify-content-md-start">
                                    <span>{{translate('messages.min')}} {{translate('messages.purchase')}} :</span>
                                    <strong>{{\App\CentralLogics\Helpers::format_currency($delivery_company->discount?$delivery_company->discount->min_purchase:0)}}</strong>
                                </li>
                            </ul>

                        </div>
                    </div>
                    @else
                    <div class="text-center">
                        <span class="card-subtitle">{{translate('no_discount_created_yet')}}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="updatesettingsmodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header shadow py-3">
        <h5 class="modal-title" id="exampleModalCenterTitle">{{$delivery_company->discount?translate('messages.update'):translate('messages.add')}} {{translate('messages.discount')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body pb-4 pt-4">
        <form action="{{route('admin.delivery-company.discount',[$delivery_company['id']])}}" method="post" id="discount-form">
            @csrf
            <div class="row gx-2">
                <div class="col-md-4 col-6">
                    <div class="form-group">
                        <label class="input-label" for="title">{{translate('messages.discount_amount')}} (%)</label>
                        <input type="number" min="0" max="100" step="0.01" name="discount" class="form-control" required value="{{$delivery_company->discount?$delivery_company->discount->discount:'0'}}">
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="form-group">
                        <label class="input-label" for="title">{{translate('messages.min')}} {{translate('messages.purchase')}} ({{\App\CentralLogics\Helpers::currency_symbol()}})</label>
                        <input type="number" name="min_purchase" step="0.01" min="0" max="100000" class="form-control" placeholder="100" value="{{$delivery_company->discount?$delivery_company->discount->min_purchase:'0'}}">
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="form-group">
                        <label class="input-label" for="title">{{translate('messages.max')}} {{translate('messages.discount')}} ({{\App\CentralLogics\Helpers::currency_symbol()}})</label>
                        <input type="number" min="0" max="1000000" step="0.01" name="max_discount" class="form-control" value="{{$delivery_company->discount?$delivery_company->discount->max_discount:'0'}}">
                    </div>
                </div>
            </div>
            <div class="row gx-2">
                <div class="col-md-6 col-6">
                    <div class="form-group">
                        <label class="input-label" for="title">{{translate('messages.start')}} {{translate('messages.date')}}</label>
                        <input type="date" id="date_from" class="form-control" required name="start_date" value="{{$delivery_company->discount?date('Y-m-d',strtotime($delivery_company->discount->start_date)):''}}">
                    </div>
                </div>
                <div class="col-md-6 col-6">
                    <div class="form-group">
                        <label class="input-label" for="title">{{translate('messages.end')}} {{translate('messages.date')}}</label>
                        <input type="date" id="date_to" class="form-control" required name="end_date" value="{{$delivery_company->discount?date('Y-m-d', strtotime($delivery_company->discount->end_date)):''}}">
                    </div>

                </div>
                <div class="col-md-6 col-6">
                    <div class="form-group">
                        <label class="input-label" for="title">{{translate('messages.start')}} {{translate('messages.time')}}</label>
                        <input type="time" id="start_time" class="form-control" required name="start_time" value="{{$delivery_company->discount?date('H:i',strtotime($delivery_company->discount->start_time)):'00:00'}}">
                    </div>
                </div>
                <div class="col-md-6 col-6">
                    <label class="input-label" for="title">{{translate('messages.end')}} {{translate('messages.time')}}</label>
                    <input type="time" id="end_time" class="form-control" required name="end_time" value="{{$delivery_company->discount?date('H:i', strtotime($delivery_company->discount->end_time)):'23:59'}}">
                </div>
            </div>
            <div class="btn--container justify-content-end">
                <button type="reset" class="btn btn--reset">{{translate('reset')}}</button>
                @if($delivery_company->discount)
                    <button type="submit" class="btn btn--primary"><i class="tio-open-in-new"></i> {{translate('messages.update')}}</button>
                @else
                    <button type="submit" class="btn btn--primary">{{translate('messages.add')}}</button>
                @endif
            </div>
        </form>
        <form action="{{route('admin.delivery-company.clear-discount',[$delivery_company->id])}}" method="post" id="discount-{{$delivery_company->id}}">
            @csrf @method('delete')
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script_2')
    <script>
        $(document).on('ready', function () {
            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
            $('#date_from').attr('min',(new Date()).toISOString().split('T')[0]);
            $('#date_to').attr('min',(new Date()).toISOString().split('T')[0]);

            $("#date_from").on("change", function () {
                $('#date_to').attr('min',$(this).val());
            });

            $("#date_to").on("change", function () {
                $('#date_from').attr('max',$(this).val());
            });
        });

        $('#discount-form').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.delivery-company.discount',[$delivery_company['id']])}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });

                        setTimeout(function () {
                            location.href = '{{route('admin.delivery-company.view', ['delivery_company'=>$delivery_company->id, 'tab'=> 'discount'])}}';
                        }, 2000);
                    }
                }
            });
        });
    </script>
@endpush
