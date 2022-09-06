<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ManageBooking;

class BookingController extends Controller
{
    use ManageBooking;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = auth()->guard('admin')->user();
            return $next($request);
        });
        $this->userType = "admin";
        $this->column = "admin_id";
    }
}
