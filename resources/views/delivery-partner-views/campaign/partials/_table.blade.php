@php($delivery_company_id = \App\CentralLogics\Helpers::get_delivery_company_id())
@foreach($campaigns as $key=>$campaign)
<tr>
    <td>{{$key+1}}</td>
    <td>
        <span class="d-block font-size-sm text-body">
            {{Str::limit($campaign['title'],25,'...')}}
        </span>
    </td>
    <td>
        <div class="overflow-hidden">
            <img class="img--vertical max--200 mw--200" src="{{asset('storage/app/public/campaign')}}/{{$campaign['image']}}"onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'">
        </div>
    </td>
    <td>
        <span class="bg-gradient-light text-dark">{{$campaign->start_date?$campaign->start_date->format('d M, Y'). ' - ' .$campaign->end_date->format('d M, Y'): 'N/A'}}</span>
    </td>
    <td>
        <span class="bg-gradient-light text-dark">{{$campaign->start_time?date(config('timeformat'),strtotime($campaign->start_time)). ' - ' .date(config('timeformat'),strtotime($campaign->end_time)): 'N/A'}}</span>
    </td>
    <?php
    $delivery_company_ids = [];
    $delivery_company_status = '--';
    foreach($campaign->delivery_companies as $delivery_company)
        {
            if ($delivery_company->id == $delivery_company_id && $delivery_company->pivot) {
                $delivery_company_status = $delivery_company->pivot->campaign_status;
            }
            $delivery_company_ids[] = $delivery_company->id;
        }
     ?>
    <td class="text-capitalize">
        @if ($delivery_company_status == 'pending')
            <span class="badge badge-soft-info">
                {{ translate('messages.not_approved') }}
            </span>
        @elseif($delivery_company_status == 'confirmed')
            <span class="badge badge-soft-success">
                {{ translate('messages.confirmed') }}
            </span>
        @elseif($delivery_company_status == 'rejected')
            <span class="badge badge-soft-danger">
                {{ translate('messages.rejected') }}
            </span>
        @else
            <span class="badge badge-soft-info">
                {{ translate(str_replace('_', ' ', $delivery_company_status)) }}
            </span>
        @endif

    </td>
    <td class="text-center">
        @if ($delivery_company_status == 'rejected')
            <span class="badge badge-pill badge-danger">{{ translate('Rejected') }}</span>
        @else
            @if(in_array($delivery_company_id,$delivery_company_ids))
            <!-- <button type="button" onclick="location.href='{{route('partner.campaign.remove-delivery_company',[$campaign['id'],$delivery_company_id])}}'" title="You are already joined. Click to out from the campaign." class="btn btn-outline-danger">Out</button> -->
            <span type="button" onclick="form_alert('campaign-{{$campaign['id']}}','{{translate('messages.alert_delivery_company_out_from_campaign')}}')" title="You are already joined. Click to out from the campaign." class="badge btn--danger text-white">{{translate('messages.leave')}}</span>
            <form action="{{route('partner.campaign.remove-delivery_company',[$campaign['id'],$delivery_company_id])}}"
                    method="GET" id="campaign-{{$campaign['id']}}">
                @csrf
            </form>
            @else
            <span type="button" class="badge btn--primary text-white" onclick="form_alert('campaign-{{$campaign['id']}}','{{translate('messages.alert_delivery_company_join_campaign')}}')" title="Click to join the campaign">{{translate('messages.join')}}</span>
            <form action="{{route('partner.campaign.add-delivery_company',[$campaign['id'],$delivery_company_id])}}"
                    method="GET" id="campaign-{{$campaign['id']}}">
                @csrf
            </form>
            @endif
        @endif
    </td>
</tr>
@endforeach
