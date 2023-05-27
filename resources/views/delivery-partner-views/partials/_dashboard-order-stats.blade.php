

<div class="col-sm-6 col-lg-12">
    <!-- Card -->
    <a class="resturant-card dashboard--card card--bg-4" href="{{route('partner.order.list',['item_on_the_way'])}}">
       <h4 class="title">{{$data['item_on_the_way']}}</h4>
       <span class="subtitle">{{translate('messages.item_on_the_way')}}</span>
       <img src="{{asset('public/assets/admin/img/dashboard/4.png')}}" alt="img" class="resturant-icon">
    </a>
    <!-- End Card -->
</div>


<div class="col-12">
    <div class="row g-2">
        <div class="col-sm-6 col-lg-6">
            <a class="order--card h-100" href="{{route('partner.order.list',['delivered'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/dashboard/statistics/1.png')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('messages.delivered')}}</span>
                    </h6>
                    <span class="card-title text-success">
                        {{$data['delivered']}}
                    </span>
                </div>
            </a>
        </div>





        <div class="col-sm-6 col-lg-6">
            <a class="order--card h-100" href="{{route('partner.order.list',['all'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/dashboard/statistics/4.png')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('messages.all')}}</span>
                    </h6>
                    <span class="card-title text-info">
                        {{$data['all']}}
                    </span>
                </div>
            </a>
        </div>
    </div>
</div>
