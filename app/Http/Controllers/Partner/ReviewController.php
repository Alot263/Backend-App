<?php

namespace App\Http\Controllers\Partner;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Review;
use App\Http\Controllers\Controller;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::whereHas('item', function($query){
            return $query->where('delivery_company_id', Helpers::get_delivery_company_id());
        })->latest()->paginate(config('default_pagination'));
        return view('delivery-partner-views.review.index', compact('reviews'));
    }
}
