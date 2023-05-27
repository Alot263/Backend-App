@foreach($delivery_companies as $key=>$delivery_company)
    <tr>
        <td>{{$key+1}}</td>
        <td>
            <div>
                <a href="{{route('admin.delivery-company.view', $delivery_company->id)}}" class="table-rest-info"
                   alt="view delivery company">
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
            <label class="toggle-switch toggle-switch-sm" for="featuredCheckbox{{$delivery_company->id}}">
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
                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$delivery_company->id}}">
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
                <form action="{{route('admin.delivery-company.delete',[$delivery_company['id']])}}" method="post"
                      id="partner-{{$delivery_company['id']}}">
                    @csrf @method('delete')
                </form>
            </div>
        </td>
    </tr>
@endforeach
