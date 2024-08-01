<?php

namespace App\Http\Controllers;

use App\Models\CodePage;
use App\Models\DeliveryAreas;
use App\Models\MailForm;
use App\Models\Order;
use App\Models\OrderSetting;
use App\Models\UserShop;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Magarrent\LaravelCurrencyFormatter\Facades\Currency;

class OrderController extends Controller
{
    // Function for Display Client Orders
    public function index()
    {
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';
        $data['orders'] = Order::where('shop_id',$shop_id)->whereIn('order_status',['pending','accepted'])->orderBy('id','DESC')->get();
        $delivery_orders = Order::where('shop_id',$shop_id)->whereIn('order_status',['pending','accepted'])->where('checkout_type','delivery')->get();

        //Staff

        $shop_settings = getClientSettings($shop_id);
        // Shop Currency
        $currency = (isset($shop_settings['default_currency']) && !empty($shop_settings['default_currency'])) ? $shop_settings['default_currency'] : 'EUR';

        $data['location_array'] = [];

        if(count($delivery_orders) > 0)
        {
            foreach($delivery_orders as $order)
            {
                $ord_data = [];
                $ord_data[] = $order->address;
                $ord_data[] = $order->latitude;
                $ord_data[] = $order->longitude;
                $ord_data[] = $order->order_status;
                $ord_data[] = $order->order_id;
                $ord_data[] = Currency::currency($currency)->format($order->discount_value);

                $data['location_array'][] = $ord_data;
            }
        }

        // Subscrption ID
        $subscription_id = Auth::user()->hasOneSubscription['subscription_id'];

        // Get Package Permissions
        $package_permissions = getPackagePermission($subscription_id);

        if(isset($package_permissions['ordering']) && !empty($package_permissions['ordering']) && $package_permissions['ordering'] == 1)
        {
            return view('client.orders.orders',$data);
        }
        else
        {
            return redirect()->route('client.dashboard')->with('error','Unauthorized Action!');
        }
    }



    // Function for Get Orders with Map
    public function ordersMap()
    {
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';
        $delivery_orders = Order::where('shop_id',$shop_id)->whereIn('order_status',['pending','accepted'])->where('checkout_type','delivery')->get();

        $shop_settings = getClientSettings($shop_id);
        // Shop Currency
        $currency = (isset($shop_settings['default_currency']) && !empty($shop_settings['default_currency'])) ? $shop_settings['default_currency'] : 'EUR';

        $data['location_array'] = [];

        if(count($delivery_orders) > 0)
        {
            foreach($delivery_orders as $order)
            {
                $ord_data = [];
                $ord_data[] = $order->address;
                $ord_data[] = $order->latitude;
                $ord_data[] = $order->longitude;
                $ord_data[] = $order->order_status;
                $ord_data[] = $order->id;
                $ord_data[] = Currency::currency($currency)->format($order->discount_value);

                $data['location_array'][] = $ord_data;
            }
        }

        // Subscrption ID
        $subscription_id = Auth::user()->hasOneSubscription['subscription_id'];

        // Get Package Permissions
        $package_permissions = getPackagePermission($subscription_id);

        if(isset($package_permissions['ordering']) && !empty($package_permissions['ordering']) && $package_permissions['ordering'] == 1)
        {
            return view('client.orders.orders_map',$data);
        }
        else
        {
            return redirect()->route('client.dashboard')->with('error','Unauthorized Action!');
        }
    }


    // Function for Get newly created order
    public function getNewOrders()
    {

        $html = '';
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';

        $shop_settings = getClientSettings($shop_id);
        // Shop Currency
        $currency = (isset($shop_settings['default_currency']) && !empty($shop_settings['default_currency'])) ? $shop_settings['default_currency'] : 'EUR';

        // Order Settings
        $order_setting = getOrderSettings($shop_id);
        $auto_print = (isset($order_setting['auto_print']) && !empty($order_setting['auto_print'])) ? $order_setting['auto_print'] : 0;
        $enable_print = (isset($order_setting['enable_print']) && !empty($order_setting['enable_print'])) ? $order_setting['enable_print'] : 0;

        // Orders
        $orders = Order::where('shop_id',$shop_id)->whereIn('order_status',['pending','accepted'])->orderBy('id','DESC')->get();

        if(count($orders) > 0)
        {
            foreach($orders as $order)
            {
                $discount_type = (isset($order->discount_type) && !empty($order->discount_type)) ? $order->discount_type : 'percentage';
                $coupon_type = (isset($order->coupon_type) && !empty($order->coupon_type)) ? $order->coupon_type : 'percentage';
                if($order->checkout_type == 'delivery')
                {
                    $staffs = Staff::where('shop_id',$shop_id)->where('status',1)->whereIn('type',[0, 2])->get();
                }else{
                    $staffs = Staff::where('shop_id',$shop_id)->where('status',1)->whereIn('type',[1,2])->get();
                }
                $html .= '<div class="order">';
                    $html .= '<div class="order-btn d-flex align-items-center justify-content-end">';
                        $html .= '<select name="staff_id" id="staff_id" class="form-select me-2 staff_id" style="width:150px" onchange="ChangeStaff(this,'.$order->id.');"'.($order->staff_id != '' ? "disabled" : "").'>';
                                $html .='<option value="">--Select Staff--</option>';
                                foreach ($staffs as $staff)
                                {
                                    $html .= '<option value="'.$staff->id.'"'.($staff->id == $order->staff_id ? "selected" : "").'>'.$staff->name.'</option>';
                                }
                         $html .= '</select>';
                        $html .= '<div class="d-flex align-items-center flex-wrap">'.__('Estimated time of arrival').' <input type="number" onchange="changeEstimatedTime(this)" name="estimated_time" id="estimated_time" value="'.$order->estimated_time.'" class="form-control mx-1 estimated_time" style="width: 100px!important" ord-id="'.$order->id.'"';
                        if($order->order_status == 'accepted')
                        {
                            $html .= 'disabled';
                        }
                        else
                        {
                            $html .= '';
                        }
                        $html .= '> '.__('Minutes').'.</div>';

                        if($order->order_status == 'pending')
                        {
                            $html .= '<a class="btn btn-sm btn-primary ms-3" onclick="acceptOrder('.$order->id.')"><i class="bi bi-check-circle" data-bs-toggle="tooltip" title="Accept"></i> '.__('Accept').'</a>';
                            $html .= '<a class="btn btn-sm btn-danger ms-3" onclick="rejectOrder('.$order->id.')"><i class="bi bi-x-circle" data-bs-toggle="tooltip" title="Reject"></i> '.__('Reject').'</a>';
                        }
                        elseif($order->order_status == 'accepted')
                        {
                            $html .= '<a class="btn btn-sm btn-success ms-3" onclick="finalizedOrder('.$order->id.')"><i class="bi bi-check-circle" data-bs-toggle="tooltip" title="Complete"></i> '.__('Finalize').'</a>';
                        }

                        if($enable_print == 1)
                        {
                            $html .= '<a class="btn btn-sm btn-primary ms-3" onclick="printReceipt('.$order->id .')"><i class="bi bi-printer"></i> Print</a>';
                        }

                    $html .= '</div>';

                    $html .= '<div class="order-info">';
                        $html .= '<ul>';
                            $html .= '<li><strong>'.__('Order No.').': #'.$order->order_id.'</strong></li>';
                            $html .= '<li><strong>'.__('Date').': </strong>'.date('d-m-Y',strtotime($order->created_at)).'</li>';
                            $html .= '<li><strong>'.__('Time').': </strong>'.date('h:i:s',strtotime($order->created_at)).'</li>';
                            $html .= '<li><strong>'.__('Order Type').': </strong>'.$order->checkout_type.'</li>';
                            $html .= '<li><strong>'.__('Payment Method').': </strong>'.$order->payment_method.'</li>';

                            if($order->checkout_type == 'takeaway')
                            {
                                $html .= '<li><strong>'.__('Customer').': </strong>'.$order->firstname.' '.$order->lastname.'</li>';
                                $html .= '<li><strong>'.__('Telephone').': </strong> '.$order->phone.'</li>';
                                $html .= '<li><strong>'.__('Email').': </strong> '.$order->email.'</li>';
                            }
                            elseif($order->checkout_type == 'table_service')
                            {
                                $html .= '<li><strong>'.__('Table No.').': </strong> '.$order->table.'</li>';
                            }
                            elseif($order->checkout_type == 'room_delivery')
                            {
                                $html .= '<li><strong>'.__('Customer').': </strong>'.$order->firstname.' '.$order->lastname.'</li>';
                                $html .= '<li><strong>'.__('Room No.').': </strong> '.$order->room.'</li>';
                                $html .= '<li><strong>'.__('Floor.').': </strong> '.$order->floor.'</li>';
                                if(!empty($order->delivery_time ))
                                {
                                    $html .= '<li><strong>'.__('Delivery Time').': </strong> '.$order->delivery_time.'</li>';
                                }
                            }
                            elseif($order->checkout_type == 'delivery')
                            {
                                $html .= '<li><strong>'.__('Customer').': </strong>'.$order->firstname.' '.$order->lastname.'</li>';
                                $html .= '<li><strong>'.__('Telephone').': </strong> '.$order->phone.'</li>';
                                $html .= '<li><strong>'.__('Email').': </strong> '.$order->email.'</li>';
                                $html .= '<li><strong>'.__('Address').': </strong> '.$order->address.'</li>';
                                $html .= '<li><strong>'.__('Street Number').': </strong> '.$order->street_number.'</li>';
                                $html .= '<li><strong>'.__('Floor').': </strong> '.$order->floor.'</li>';
                                $html .= '<li><strong>'.__('Door Bell').': </strong> '.$order->door_bell.'</li>';
                                $html .= '<li><strong>'.__('Google Map').': </strong> <a href="https://maps.google.com?q='.$order->address.'" target="_blank">Address Link</a></li>';
                            }
                                if($order->instructions){
                                    $html .= '<li><strong>'.__('Order Comments').': </strong> '.$order->instructions.'</li>';
                                }

                        $html .= '</ul>';
                    $html .= '</div>';

                    $html .= '<hr>';

                    $html .= '<div class="order-info mt-2">';
                        $html .= '<div class="row">';
                            $html .= '<div class="col-md-4">';
                                $html .= '<table class="table">';

                                    $total_amount = $order->order_total;

                                    $html .= '<tr>';
                                        $html .= '<td><b>'. __('Sub Total') .'</b></td>';
                                        $html .= '<td class="text-end">'. Currency::currency($currency)->format($total_amount) .'</td>';
                                    $html .= '</tr>';

                                    if($order->discount_per > 0)
                                    {
                                        $html .= '<td><b>'. __('Discount') .'</b></td>';
                                        if($discount_type == 'fixed')
                                        {
                                            $discount_amount = $order->discount_per;
                                            $html .= '<td class="text-end">- '. Currency::currency($currency)->format($order->discount_per) .'</td>';
                                        }
                                        else
                                        {
                                            $discount_amount = ($total_amount * $order->discount_per) / 100;
                                            $html .= '<td class="text-end">- '.$order->discount_per.'%</td>';
                                        }
                                        $total_amount = $total_amount - $discount_amount;
                                    }

                                    if($order->coupon_per > 0)
                                    {
                                        $html .='<tr>';
                                        $html .= '<td><b>'. __('Coupon Discount') .'</b></td>';
                                        if($coupon_type == 'fixed')
                                        {
                                            $coupon_amount = $order->coupon_per;
                                            $html .= '<td class="text-end">- '. Currency::currency($currency)->format($order->coupon_per) .'</td>';
                                        }
                                        else
                                        {
                                            $coupon_amount = ($total_amount * $order->coupon_per) / 100;
                                            $html .= '<td class="text-end">- '.$order->coupon_per.'%</td>';
                                        }
                                        $html .='</tr>';
                                        $total_amount = $total_amount - $coupon_amount;
                                    }

                                    if(($order->payment_method == 'paypal' || $order->payment_method == 'every_pay') && $order->tip > 0)
                                    {
                                        $total_amount = $total_amount + $order->tip;
                                        $html .= '<tr>';
                                            $html .= '<td><b>'. __('Tip') .'</b></td>';
                                            $html .= '<td class="text-end">+ '.Currency::currency($currency)->format($order->tip).'</td>';
                                        $html .= '</tr>';
                                    }

                                    $html .= '<tr><td><b>'. __('Total') .'</b></td><td class="text-end"><strong>'.Currency::currency($currency)->format($total_amount).'</strong></td></tr>';

                                $html .= '</table>';
                            $html .= '</div>';
                        $html .= '</div>';
                    $html .= '</div>';

                    $html .= '<hr>';

                    $html .= '<div class="order-items">';
                        $html .= '<div class="row">';
                            if(count($order->order_items) > 0)
                            {
                                $html .= '<div class="col-md-8">';
                                    $html .= '<table class="table">';
                                        foreach ($order->order_items as $ord_item)
                                        {
                                            $sub_total = ( $ord_item['sub_total'] / $ord_item['item_qty']);
                                            $option = unserialize($ord_item['options']);

                                            $html .= '<tr>';
                                                $html .= '<td>';
                                                    $html .= '<b>'.$ord_item['item_qty'].' x '.$ord_item['item_name'].'</b>';
                                                    if(!empty($option))
                                                    {
                                                        $html .= '<br> '.implode(', ',$option);
                                                    }
                                                $html .= '</td>';
                                                $html .= '<td width="25%" class="text-end">'.Currency::currency($currency)->format($sub_total).'</td>';
                                                $html .= '<td width="25%" class="text-end">'.$ord_item['sub_total_text'].'</td>';
                                            $html .= '</tr>';
                                        }
                                    $html .= '</table>';
                                $html .= '</div>';
                            }
                        $html .= '</div>';
                    $html .= '</div>';

                $html .= '</div>';
            }
        }
        else
        {
            $html .= '<div class="row">';
                $html .= '<div class="col-md-12 text-center">';
                    $html .= '<h3>Orders Not Available</h3>';
                $html .= '</div>';
            $html .= '</div>';
        }

        // New Order Locations
        $delivery_orders = Order::where('shop_id',$shop_id)->whereIn('order_status',['pending','accepted'])->where('checkout_type','delivery')->get();

        $location_array = [];

        if(count($delivery_orders) > 0)
        {
            foreach($delivery_orders as $order)
            {
                $ord_data = [];
                $ord_data[] = $order->address;
                $ord_data[] = $order->latitude;
                $ord_data[] = $order->longitude;
                $ord_data[] = $order->order_status;
                $ord_data[] = $order->order_id;
                $ord_data[] = Currency::currency($currency)->format($order->discount_value);

                $location_array[] = $ord_data;
            }
        }


        return response()->json([
            'success' => 1,
            'data' => $html,
            'location_array' => $location_array,
        ]);
    }


    // Function for Display Client Orders History
    public function ordersHistory(Request $request)
    {
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';
        $data['payment_method'] = '';
        $data['status_filter'] = '';
        $data['day_filter'] = '';
        $data['total_text'] = 'Total Amount';
        $data['total'] = 0.00;
        $data['tip_amount'] = 0.00;
        $data['start_date'] = Carbon::now();
        $data['end_date'] = Carbon::now();
        $data['StartDate'] = '';
        $data['EndDate'] = '';

        if($request->isMethod('get'))
        {
            $data['orders'] = Order::where('shop_id',$shop_id)->get();
            $data['total'] = Order::where('shop_id',$shop_id)->sum('discount_value');
            $data['tip_amount'] = Order::where('shop_id',$shop_id)->sum('tip');
        }
        else
        {
            $orders = Order::where('shop_id',$shop_id);
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $data['payment_method'] = (isset($request->filter_by_payment_method)) ? $request->filter_by_payment_method : '';
            $data['status_filter'] = (isset($request->filter_by_status)) ? $request->filter_by_status : '';

            // Payment Method Filter
            if(!empty($data['payment_method']))
            {
                $orders = $orders->where('payment_method',$data['payment_method']);
                $data['total'] = $orders->sum('discount_value');
                $data['tip_amount'] = $orders->sum('tip');
            }
            else
            {
                $data['total'] = $orders->sum('discount_value');
                $data['tip_amount'] = $orders->sum('tip');
            }

            // Status Filter
            if(!empty($data['status_filter']))
            {
                $orders = $orders->where('order_status',$data['status_filter']);
                $data['total'] = $orders->sum('discount_value');
                $data['tip_amount'] = $orders->sum('tip');
            }
            else
            {
                $data['total'] = $orders->sum('discount_value');
                $data['tip_amount'] = $orders->sum('tip');
            }

            if(!empty($start_date) && !empty($end_date))
            {
                $data['start_date'] = $start_date;
                $data['StartDate'] = $start_date;
                $data['end_date'] = $end_date;
                $data['EndDate'] = $end_date;

                $orders = $orders->whereBetween('created_at', [$data['start_date'], $data['end_date']]);
                $data['total'] = $orders->sum('discount_value');
                $data['tip_amount'] = $orders->sum('tip');
                $data['orders'] = $orders->get();
            }
            else
            {

                // Day Filter
                $data['day_filter'] = (isset($request->filter_by_day)) ? $request->filter_by_day : '';
                if(!empty($data['day_filter']))
                {
                    if($data['day_filter'] == 'today')
                    {
                        $today = Carbon::today();
                        $orders = $orders->whereDate('created_at', $today);
                        $data['total_text'] = "Today's Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                    elseif($data['day_filter'] == 'this_week')
                    {
                        $startOfWeek = Carbon::now()->startOfWeek();
                        $endOfWeek = Carbon::now()->endOfWeek();
                        $orders = $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                        $data['total_text'] = "This Week Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                    elseif($data['day_filter'] == 'last_week')
                    {
                        $startOfWeek = Carbon::now()->subWeek()->startOfWeek();
                        $endOfWeek = Carbon::now()->subWeek()->endOfWeek();
                        $orders = $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                        $data['total_text'] = "Last Week Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                    elseif($data['day_filter'] == 'this_month')
                    {
                        $currentMonth = Carbon::now()->format('Y-m');
                        $orders = $orders->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth]);
                        $data['total_text'] = "This Month Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                    elseif($data['day_filter'] == 'last_month')
                    {
                        $startDate = Carbon::now()->subMonth()->startOfMonth();
                        $endDate = Carbon::now()->subMonth()->endOfMonth();
                        $orders = $orders->whereBetween('created_at', [$startDate, $endDate]);
                        $data['total_text'] = "Last Month Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                    elseif($data['day_filter'] == 'last_six_month')
                    {
                        $startDate = Carbon::now()->subMonths(6)->startOfMonth();
                        $endDate = Carbon::now()->subMonth()->endOfMonth();
                        $orders = $orders->whereBetween('created_at', [$startDate, $endDate]);
                        $data['total_text'] = "Last Six Months Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                    elseif($data['day_filter'] == 'this_year')
                    {
                        $startOfYear = Carbon::now()->startOfYear();
                        $endOfYear = Carbon::now()->endOfYear();
                        $orders = $orders->whereBetween('created_at', [$startOfYear, $endOfYear]);
                        $data['total_text'] = "This Year Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                    elseif($data['day_filter'] == 'last_year')
                    {
                        $startOfYear = Carbon::now()->subYear()->startOfYear();
                        $endOfYear = Carbon::now()->subYear()->endOfYear();
                        $orders = $orders->whereBetween('created_at', [$startOfYear, $endOfYear]);
                        $data['total_text'] = "Last Year Total Amount";
                        $data['total'] = $orders->sum('discount_value');
                        $data['tip_amount'] = $orders->sum('tip');
                    }
                }
            }

            $data['orders'] = $orders->get();
        }

        // Subscrption ID
        $subscription_id = Auth::user()->hasOneSubscription['subscription_id'];

        // Get Package Permissions
        $package_permissions = getPackagePermission($subscription_id);

        if(isset($package_permissions['ordering']) && !empty($package_permissions['ordering']) && $package_permissions['ordering'] == 1)
        {
            return view('client.orders.orders_history',$data);
        }
        else
        {
            return redirect()->route('client.dashboard')->with('error','Unauthorized Action!');
        }
    }


    // function for view OrderSettings
    public function OrderSettings()
    {
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';
        $data['order_settings'] = getOrderSettings($shop_id);
        $data['deliveryAreas'] = DeliveryAreas::where('shop_id',$shop_id)->get();

        return view('client.orders.order_settings',$data);
    }


    // Function for View Printer Settings
    public function PrinterSettings()
    {
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';
        $data['printer_settings'] = getOrderSettings($shop_id);
        $data['code_pages'] = CodePage::get();

        return view('client.orders.printer_settings',$data);
    }


    // Function for Update Order Settings
    public function UpdateOrderSettings(Request $request)
    {
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';

        $all_data['delivery'] = (isset($request->delivery)) ? $request->delivery : 0;
        $all_data['takeaway'] = (isset($request->takeaway)) ? $request->takeaway : 0;
        $all_data['room_delivery'] = (isset($request->room_delivery)) ? $request->room_delivery : 0;
        $all_data['table_service'] = (isset($request->table_service)) ? $request->table_service : 0;
        $all_data['only_cart'] = (isset($request->only_cart)) ? $request->only_cart : 0;
        $all_data['auto_order_approval'] = (isset($request->auto_order_approval)) ? $request->auto_order_approval : 0;
        $all_data['scheduler_active'] = (isset($request->scheduler_active)) ? $request->scheduler_active : 0;
        $all_data['min_amount_for_delivery'] = (isset($request->min_amount_for_delivery)) ? serialize($request->min_amount_for_delivery) : '';
        $all_data['discount_percentage'] = (isset($request->discount_percentage)) ? $request->discount_percentage : '';
        $all_data['order_arrival_minutes'] = (isset($request->order_arrival_minutes)) ? $request->order_arrival_minutes : 30;
        $all_data['schedule_array'] = $request->schedule_array;
        $all_data['discount_type'] = $request->discount_type;
        $all_data['play_sound'] = (isset($request->play_sound)) ? $request->play_sound : 0;
        $all_data['notification_sound'] = (isset($request->notification_sound)) ? $request->notification_sound : 'buzzer-01.mp3';
        $all_data['shop_address'] = (isset($request->shop_address)) ? $request->shop_address : '';
        $all_data['shop_latitude'] = (isset($request->shop_latitude)) ? $request->shop_latitude : '';
        $all_data['shop_longitude'] = (isset($request->shop_longitude)) ? $request->shop_longitude : '';

        try
        {
            // Insert or Update Settings
            foreach($all_data as $key => $value)
            {
                $query = OrderSetting::where('shop_id',$shop_id)->where('key',$key)->first();
                $setting_id = isset($query->id) ? $query->id : '';

                if (!empty($setting_id) || $setting_id != '')  // Update
                {
                    $settings = OrderSetting::find($setting_id);
                    $settings->value = $value;
                    $settings->update();
                }
                else // Insert
                {
                    $settings = new OrderSetting();
                    $settings->shop_id = $shop_id;
                    $settings->key = $key;
                    $settings->value = $value;
                    $settings->save();
                }
            }

            // Insert Delivery Zones Area
            $delivery_zones = (isset($request->new_coordinates) && !empty($request->new_coordinates)) ? json_decode($request->new_coordinates,true) : [];

            if(count($delivery_zones) > 0)
            {
                foreach($delivery_zones as $delivery_zone)
                {
                    $polygon = serialize($delivery_zone);

                    $delivery_area = new DeliveryAreas();
                    $delivery_area->shop_id = $shop_id;
                    $delivery_area->coordinates = $polygon;
                    $delivery_area->save();
                }
            }

            return response()->json([
                'success' => 1,
                'message' => 'Setting has been Updated SuccessFully...',
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }
    }


    // Function for Update Printer Settings
    public function UpdatePrinterSettings(Request $request)
    {
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';

        $all_data['enable_print'] = (isset($request->enable_print)) ? $request->enable_print : 0;
        $all_data['auto_print'] = (isset($request->auto_print)) ? $request->auto_print : 0;
        $all_data['raw_printing'] = (isset($request->raw_printing)) ? $request->raw_printing : 0;
        $all_data['greek_list'] = (isset($request->greek_list)) ? $request->greek_list : 0;
        $all_data['printer_paper'] = (isset($request->printer_paper)) ? $request->printer_paper : '';
        $all_data['default_printer'] = (isset($request->default_printer)) ? $request->default_printer : '';
        $all_data['receipt_intro'] = $request->receipt_intro;
        $all_data['print_font_size'] = (isset($request->print_font_size)) ? $request->print_font_size : '';
        $all_data['printer_tray'] = (isset($request->printer_tray)) ? $request->printer_tray : '';
        $all_data['default_code_page'] = (isset($request->default_code_page)) ? $request->default_code_page : '';

        try
        {
            // Insert or Update Settings
            foreach($all_data as $key => $value)
            {
                $query = OrderSetting::where('shop_id',$shop_id)->where('key',$key)->first();
                $setting_id = isset($query->id) ? $query->id : '';

                if (!empty($setting_id) || $setting_id != '')  // Update
                {
                    $settings = OrderSetting::find($setting_id);
                    $settings->value = $value;
                    $settings->update();
                }
                else // Insert
                {
                    $settings = new OrderSetting();
                    $settings->shop_id = $shop_id;
                    $settings->key = $key;
                    $settings->value = $value;
                    $settings->save();
                }
            }

            return redirect()->route('printer.settings')->with('success', 'Setting has been Updated SuccessFully...');
        }
        catch (\Throwable $th)
        {
            return redirect()->route('printer.settings')->with('error', 'Internal Server Error!');
        }
    }


    // Function for Enable/Disable Map View Order
    public function mapViewOrderSetting(Request $request)
    {
        $status = $request->status;
        $status_message = ($request->status == 1) ? 'Map View has been Enabled SuccessFully...' : 'Map View has been Disabled SuccessFully...';
        $key = 'google_map_order_view';
        $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';

        try
        {
            $query = OrderSetting::where('shop_id',$shop_id)->where('key',$key)->first();
            $setting_id = isset($query->id) ? $query->id : '';

            if (!empty($setting_id) || $setting_id != '')  // Update
            {
                $settings = OrderSetting::find($setting_id);
                $settings->value = $status;
                $settings->update();
            }
            else // Insert
            {
                $settings = new OrderSetting();
                $settings->shop_id = $shop_id;
                $settings->key = $key;
                $settings->value = $status;
                $settings->save();
            }

            return response()->json([
                'success' => 1,
                'message' => $status_message,
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }

    }


    // Function for Clear Delivery Range Settings
    public function clearDeliveryRangeSettings()
    {
        $shop_id = (isset(Auth::user()->hasOneShop->shop['id'])) ? Auth::user()->hasOneShop->shop['id'] : '';

        DeliveryAreas::where('shop_id',$shop_id)->delete();

        return redirect()->route('order.settings')->with('success',"Setting has been Updated SuccessFully..");

    }


    // Function for Change Order Estimated Time
    public function changeOrderEstimate(Request $request)
    {
        $order_id = $request->order_id;
        $estimated_time = $request->estimate_time;
        if($estimated_time == '' || $estimated_time == 0 || $estimated_time < 0)
        {
            $estimated_time = '30';
        }

        try
        {
            $order = Order::find($order_id);
            $order->estimated_time = $estimated_time;
            $order->update();

            return response()->json([
                'success' => 1,
                'message' => 'Time has been Changed SuccessFully...',
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }

    }


    // Function for Accpeting Order
    public function acceptOrder(Request $request)
    {
        $order_id = $request->order_id;
        try
        {
            // Shop ID
            $shop_id = (isset(Auth::user()->hasOneShop->shop['id'])) ? Auth::user()->hasOneShop->shop['id'] : '';
            $shop_name = isset(Auth::user()->hasOneShop->shop['name']) ? Auth::user()->hasOneShop->shop['name'] : '';
            $shop_url = (isset(Auth::user()->hasOneShop->shop['shop_slug'])) ? Auth::user()->hasOneShop->shop['shop_slug'] : '';
            $shop_slug = (isset(Auth::user()->hasOneShop->shop['shop_slug'])) ? Auth::user()->hasOneShop->shop['shop_slug'] : '';
            $shop_url = asset($shop_url);
            $shop_name = '<a href="'.$shop_url.'">'.$shop_name.'</a>';

            // Update Order Status
            $order = Order::find($order_id);
            $order->order_status = 'accepted';
            $order->is_new = 0;
            $order->update();

            // Get Shop Settings
            $shop_settings = getClientSettings($shop_id);

            $shop_theme_id = isset($shop_settings['shop_active_theme']) ? $shop_settings['shop_active_theme'] : '';
            $theme_settings = themeSettings($shop_theme_id);

            $layout = isset($theme_settings['desk_layout']) ? $theme_settings['desk_layout'] : '';

            if($layout == 'layout_1'){
                // Shop Logo
                $shop_logo = (isset($shop_settings['logo_layout_1']) && !empty($shop_settings['logo_layout_1'])) ? $shop_settings['logo_layout_1'] : '';
            }elseif($layout == 'layout_2'){
                // Shop Logo
                $shop_logo = (isset($shop_settings['logo_layout_2']) && !empty($shop_settings['logo_layout_2'])) ? $shop_settings['logo_layout_2'] : '';
            }elseif($layout == 'layout_3'){
                // Shop Logo
                $shop_logo = (isset($shop_settings['logo_layout_3']) && !empty($shop_settings['logo_layout_3'])) ? $shop_settings['logo_layout_3'] : '';
            }else{
                $shop_logo = "";
            }

            if(!empty($shop_logo)){
                $imagePath=asset('public/client_uploads/shops/'.$shop_slug.'/top_logos/'.$shop_logo);
                $shop_logo = '<img src="'.$imagePath.'" width="200">';
            }else{
                $shop_logo = '<img src="'.asset('public/client_images/not-found/your_logo_1.png'). '" width="200">';
            }

            $primary_lang_details = clientLanguageSettings($shop_id);
            $language = getLangDetails(isset($primary_lang_details['primary_language']) ? $primary_lang_details['primary_language'] : '');
            $language_code = isset($language['code']) ? $language['code'] : '';

            // Form Key
            $form_key = $language_code."_form";

            // Mail Form
            $mail_forms = MailForm::where('shop_id',$shop_id)->where('mail_form_key','orders_mail_form_customer')->first();
            $orders_mail_form_customer = (isset($mail_forms[$form_key])) ? $mail_forms[$form_key] : $mail_forms['en_form'];

            // Shop Currency
            $currency = (isset($shop_settings['default_currency']) && !empty($shop_settings['default_currency'])) ? $shop_settings['default_currency'] : 'EUR';

            // Get Contact Emails
            $shop_user = UserShop::with(['user'])->where('shop_id',$shop_id)->first();
            $contact_emails = (isset($shop_user->user['contact_emails']) && !empty($shop_user->user['contact_emails'])) ? unserialize($shop_user->user['contact_emails']) : '';

            // Sent Mail to Customer
            if($order->id)
            {
                $order_items = (isset($order->order_items) && count($order->order_items) > 0) ? $order->order_items : [];
                $discount_type = (isset($order->discount_type) && !empty($order->discount_type)) ? $order->discount_type : 'percentage';

                $checkout_type =  (isset($order->checkout_type)) ? $order->checkout_type : '';
                $payment_method =  (isset($order->payment_method)) ? $order->payment_method : '';

                $from_email = (isset($order->email)) ? $order->email : '';

                if($checkout_type == 'takeaway' || $checkout_type == 'delivery')
                {
                    if(!empty($from_email) && count($contact_emails) > 0 && !empty($orders_mail_form_customer))
                    {
                        $to = $from_email;
                        $from = $contact_emails[0];
                        $subject = "Order Placed";
                        $fname = (isset($order->firstname)) ? $order->firstname : '';
                        $lname = (isset($order->lastname)) ? $order->lastname : '';
                        $estimated_time = (isset($order->estimated_time)) ? $order->estimated_time : '';

                        $message = $orders_mail_form_customer;
                        $message = str_replace('{shop_logo}',$shop_logo,$message);
                        $message = str_replace('{shop_name}',$shop_name,$message);
                        $message = str_replace('{firstname}',$fname,$message);
                        $message = str_replace('{lastname}',$lname,$message);
                        $message = str_replace('{order_id}',$order->id,$message);
                        $message = str_replace('{order_type}',$checkout_type,$message);
                        $message = str_replace('{payment_method}',$payment_method,$message);
                        $message = str_replace('{order_status}','Accepted',$message);
                        $message = str_replace('{estimated_time}',$estimated_time,$message);

                        // Order Items
                        $order_html  = "";
                        $order_html .= '<div>';
                            $order_html .= '<table style="width:100%; border:1px solid gray;border-collapse: collapse;">';
                                $order_html .= '<thead style="background:lightgray; color:white">';
                                    $order_html .= '<tr style="text-transform: uppercase!important;    font-weight: 700!important;">';
                                        $order_html .= '<th style="text-align: left!important;width: 60%;padding:10px">Item</th>';
                                        $order_html .= '<th style="text-align: center!important;padding:10px">Qty.</th>';
                                        $order_html .= '<th style="text-align: right!important;padding:10px">Item Total</th>';
                                    $order_html .= '</tr>';
                                $order_html .= '</thead>';
                                $order_html .= '<tbody style="font-weight: 600!important;">';

                                    if(count($order_items) > 0)
                                    {
                                        foreach($order_items as $order_item)
                                        {
                                            $item_dt = itemDetails($order_item['item_id']);
                                            $item_image = (isset($item_dt['image']) && !empty($item_dt['image']) && file_exists('public/client_uploads/shops/'.$shop_slug.'/items/'.$item_dt['image'])) ? asset('public/client_uploads/shops/'.$shop_slug.'/items/'.$item_dt['image']) : asset('public/client_images/not-found/no_image_1.jpg');
                                            $options_array = (isset($order_item['options']) && !empty($order_item['options'])) ? unserialize($order_item['options']) : '';
                                            if(count($options_array) > 0)
                                            {
                                                $options_array = implode(', ',$options_array);
                                            }

                                            $order_html .= '<tr>';

                                                $order_html .= '<td style="text-align: left!important;padding:10px; border-bottom:1px solid gray;">';
                                                    $order_html .= '<div style="align-items: center!important;display: flex!important;">';
                                                        $order_html .= '<a style="display: inline-block;
                                                        flex-shrink: 0;position: relative;border-radius: 0.75rem;">';
                                                            $order_html .= '<span style="width: 50px;
                                                            height: 50px;display: flex;
                                                            align-items: center;
                                                            justify-content: center;
                                                            font-weight: 500;background-repeat: no-repeat;
                                                            background-position: center center;
                                                            background-size: cover;
                                                            border-radius: 0.75rem; background-image:url('.$item_image.')"></span>';
                                                        $order_html .= '</a>';
                                                        $order_html .= '<div style="display: block;    margin-left: 3rem!important;">';
                                                            $order_html .= '<a style="font-weight: 700!important;color: #7e8299;
                                                            ">'.$order_item->item_name.'</a>';

                                                            if(!empty($options_array))
                                                            {
                                                                $order_html .= '<div style="color: #a19e9e;display: block;">'.$options_array.'</div>';
                                                            }
                                                            else
                                                            {
                                                                $order_html .= '<div style="color: #a19e9e;display: block;"></div>';
                                                            }

                                                        $order_html .= '</div>';
                                                    $order_html .= '</div>';
                                                $order_html .= '</td>';

                                                $order_html .= '<td style="text-align: center!important;padding:10px; border-bottom:1px solid gray;">';
                                                    $order_html .= $order_item['item_qty'];
                                                $order_html .= '</td>';

                                                $order_html .= '<td style="text-align: right!important;padding:10px; border-bottom:1px solid gray;">';
                                                    $order_html .= $order_item['sub_total_text'];
                                                $order_html .= '</td>';

                                            $order_html .= '</tr>';
                                        }
                                    }

                                $order_html .= '</tbody>';
                            $order_html .= '</table>';
                        $order_html .= '</div>';
                        $message = str_replace('{items}',$order_html,$message);

                        // Order Total
                        $order_tot_amount = $order->order_total;
                        $order_total_html = "";
                        $order_total_html .= '<div>';
                            $order_total_html .= '<table style="width:50%; border:1px solid gray;border-collapse: collapse;">';
                                $order_total_html .= '<tbody style="font-weight: 700!important;">';
                                    $order_total_html .= '<tr>';
                                        $order_total_html .= '<td style="padding:10px; border-bottom:1px solid gray">Sub Total : </td>';
                                        $order_total_html .= '<td style="padding:10px; border-bottom:1px solid gray">'.Currency::currency($currency)->format($order_tot_amount).'</td>';
                                    $order_total_html .= '</tr>';

                                    if($order->discount_per > 0)
                                    {
                                        $order_total_html .= '<tr>';
                                            $order_total_html .= '<td style="padding:10px; border-bottom:1px solid gray">Discount : </td>';
                                            if($discount_type == 'fixed')
                                            {
                                                $discount_amount = $order->discount_per;
                                                $order_total_html .= '<td style="padding:10px; border-bottom:1px solid gray">- '.Currency::currency($currency)->format($order->discount_per).'</td>';
                                            }
                                            else
                                            {
                                                $discount_amount = ($order_tot_amount * $order->discount_per) / 100;
                                                $order_total_html .= '<td style="padding:10px; border-bottom:1px solid gray">- '.$order->discount_per.'%</td>';
                                            }
                                            $order_tot_amount = $order_tot_amount - $discount_amount;
                                        $order_total_html .= '</tr>';
                                    }

                                    if(($order->payment_method == 'paypal' || $order->payment_method == 'every_pay') && $order->tip > 0)
                                    {
                                        $order_tot_amount = $order_tot_amount + $order->tip;
                                        $order_total_html .= '<tr>';
                                            $order_total_html .= '<td style="padding:10px; border-bottom:1px solid gray">Tip : </td>';
                                            $order_total_html .= '<td style="padding:10px; border-bottom:1px solid gray">+ '.Currency::currency($currency)->format($order->tip).'</td>';
                                        $order_total_html .= '</tr>';
                                    }

                                    $order_total_html .= '<tr>';
                                        $order_total_html .= '<td style="padding:10px;">Total : </td>';
                                        $order_total_html .= '<td style="padding:10px;">';
                                            $order_total_html .= Currency::currency($currency)->format($order_tot_amount);
                                        $order_total_html .= '</td>';
                                    $order_total_html .= '</tr>';

                                $order_total_html .= '</tbody>';
                            $order_total_html .= '</table>';
                        $order_total_html .= '</div>';

                        $message = str_replace('{total}',$order_total_html,$message);

                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

                        // More headers
                        $headers .= 'From: <'.$from.'>' . "\r\n";

                        mail($to,$subject,$message,$headers);

                    }
                }
            }

            return response()->json([
                'success' => 1,
                'message' => 'Order has been Accepted SuccessFully...',
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }
    }


    // Function for Reject Order
    public function rejectOrder(Request $request)
    {
        $order_id = $request->order_id;
        $reject_reason = $request->reject_reason;
        try
        {
            // Update Order Status
            $order = Order::find($order_id);
            $order->order_status = 'rejected';
            $order->is_new = 0;
            $order->reject_reason = $reject_reason;
            $order->update();

            return response()->json([
                'success' => 1,
                'message' => 'Order has been Rejected SuccessFully...',
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }
    }


    // Function for Finalized Order
    public function finalizedOrder(Request $request)
    {
        $order_id = $request->order_id;
        try
        {
            $order = Order::find($order_id);
            $order->order_status = 'completed';
            $order->update();

            return response()->json([
                'success' => 1,
                'message' => 'Order has been Completed SuccessFully...',
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }
    }


    // Function for view Order
    public function viewOrder($order_id)
    {
        try
        {
            $order_id = decrypt($order_id);
            $data['order'] = Order::with(['order_items','staff'])->where('id',$order_id)->first();

            return view('client.orders.order_details',$data);
        }
        catch (\Throwable $th)
        {
            return redirect()->route('client.orders')->with('error',"Internal Server Error!");
        }
    }


    // Function for Set Delivery Address in Session
    public function setDeliveryAddress(Request $request)
    {
        $lat = $request->latitude;
        $lng = $request->longitude;
        $address = $request->address;
        $shop_id = $request->shop_id;
        $street_number = $request->street_number;

        try
        {
            session()->put('cust_lat',$lat);
            session()->put('cust_long',$lng);
            session()->put('cust_address',$address);
            session()->put('cust_street',$street_number);
            session()->save();

            $delivey_avaialbility = checkDeliveryAvilability($shop_id,$lat,$lng);

            return response()->json([
                'success' => 1,
                'message' => 'Address has been set successfully...',
                'available' => $delivey_avaialbility,
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }

    }


    // Function for Check Min Amount for Delivery
    public function checkMinAmountforDelivery(Request $request)
    {
        $user_lat = $request->latitude;
        $user_lng = $request->longitude;
        $address = $request->address;
        $shop_id = $request->shop_id;
        $currency = $request->currency;
        $total_amount = number_format($request->total_amount,2);

        // Current Languge Code
        $current_lang_code = (session()->has('locale')) ? session()->get('locale') : 'en';

        try
        {
            // Order Settings
            $order_settings = getOrderSettings($shop_id);
            $shop_latitude = (isset($order_settings['shop_latitude'])) ? $order_settings['shop_latitude'] : "";
            $shop_longitude = (isset($order_settings['shop_longitude'])) ? $order_settings['shop_longitude'] : "";
            $min_amount_for_delivery = (isset($order_settings['min_amount_for_delivery']) && !empty($order_settings['min_amount_for_delivery'])) ? unserialize($order_settings['min_amount_for_delivery']) : [];

            // Distance Alert Message
            $distance_alert_message = moreTranslations($shop_id,'distance_alert_message');
            $distance_alert_message = (isset($distance_alert_message[$current_lang_code."_value"]) && !empty($distance_alert_message[$current_lang_code."_value"])) ? $distance_alert_message[$current_lang_code."_value"] : 'Left for the minimum order';

            // Distance in Kilometers
            $distance = getDistance($shop_latitude,$shop_longitude,$user_lat,$user_lng);

            if(count($min_amount_for_delivery) > 0)
            {
                foreach ($min_amount_for_delivery as $min_amt_key => $min_amount)
                {
                    $from = $min_amount['from'];
                    $to = $min_amount['to'];
                    $amount = $min_amount['amount'];

                    if($distance >= $from && $distance <= $to)
                    {
                        if($total_amount >= $amount)
                        {
                            return response()->json([
                                'success' => 1,
                            ]);
                        }
                        else
                        {
                            $remain_amount = Currency::currency($currency)->format($amount - $total_amount);
                            $message = "<div class='delivery_message_box'><code class='fs-6'>$remain_amount ".$distance_alert_message."</code></div>";

                            return response()->json([
                                'success' => 0,
                                'message' => $message,
                            ]);
                        }
                    }
                }
            }
            else
            {
                return response()->json([
                    'success' => 1,
                ]);
            }

            return response()->json([
                'success' => 1,
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Internal Server Error!',
            ]);
        }

    }


    // Function for set Printer JS License Key
    public function setPrinterLicense()
    {
        $license_owner = 'Dimitris Bourlos - 1 WebApp Lic - 1 WebServer Lic - (Basic Edition)';
        $license_key  = '82603F1C4E2F66FDBD405B74E724442F5CDDB383';

        //DO NOT MODIFY THE FOLLOWING CODE
        $timestamp = request()->query('timestamp');
        $license_hash = hash('sha256', $license_key . $timestamp, false);
        $resp = $license_owner . '|' . $license_hash;

        return response($resp)->header('Content-Type', 'text/plain');
    }


    // Function for Get Order Receipt
    public function getOrderReceipt(Request $request)
    {
        $data['order_id'] = $request->order_id;
        $user_details = Auth::user();
        $data['shop_address'] = (isset($user_details['address'])) ? $user_details['address'] : '';
        $data['shop_telephone'] = (isset($user_details['telephone'])) ? $user_details['telephone'] : '';
        $data['shop_mobile'] = (isset($user_details['mobile'])) ? $user_details['mobile'] : '';
        $data['shop_city'] = (isset($user_details['city'])) ? $user_details['city'] : '';
        $html = '';

        try
        {
            $order = Order::with(['order_items','shop'])->where('id',$data['order_id'])->first();
            $discount_type = (isset($order->discount_type) && !empty($order->discount_type)) ? $order->discount_type : 'percentage';
            $coupon_type = (isset($order->coupon_type) && !empty($order->coupon_type)) ? $order->coupon_type : 'percentage';
            $data['shop_id'] = (isset($order->shop['id'])) ? $order->shop['id'] : '';
            $shop_name = (isset(Auth::user()->hasOneShop->shop['name'])) ? Auth::user()->hasOneShop->shop['name'] : '';

            $data['shop_settings'] = getClientSettings($data['shop_id']);
            $business_telephone = (isset($data['shop_settings']['business_telephone'])) ? $data['shop_settings']['business_telephone'] : '';

            $data['order_setting'] = getOrderSettings($data['shop_id']);
            $data['receipt_intro'] = (isset($data['order_setting']['receipt_intro']) && !empty($data['order_setting']['receipt_intro'])) ? $data['order_setting']['receipt_intro'] : 'INVOICE';
            $data['raw_printing'] = (isset($data['order_setting']['raw_printing']) && !empty($data['order_setting']['raw_printing'])) ? $data['order_setting']['raw_printing'] : 0;

            // Shop Currency
            $currency = (isset($data['shop_settings']['default_currency']) && !empty($data['shop_settings']['default_currency'])) ? $data['shop_settings']['default_currency'] : 'EUR';

            $data['order_inv'] = (isset($order->order_id)) ? $order->order_id : '';
            $data['order_date'] = (isset($order->created_at)) ? date('d-m-Y',strtotime($order->created_at)): '';
            $data['order_time'] = (isset($order->created_at)) ? date('h:i:s',strtotime($order->created_at)): '';
            $data['payment_method'] = (isset($order->payment_method)) ? str_replace('_',' ',$order->payment_method) : '';
            $data['checkout_type'] = (isset($order->checkout_type)) ? $order->checkout_type : '';
            $data['customer'] = $order->firstname." ".$order->lastname;
            $data['phone'] = (isset($order->phone)) ? $order->phone : '';
            $data['email'] = (isset($order->email)) ? $order->email : '';
            $data['instructions'] = (isset($order->instructions)) ? $order->instructions : '';
            $data['address'] = (isset($order->address)) ? $order->address : '';
            $data['floor'] = (isset($order->floor)) ? $order->floor : '';
            $data['table_no'] = (isset($order->table)) ? $order->table : '';
            $data['room_no'] = (isset($order->room)) ? $order->room : '';
            $data['delivery_time'] = (isset($order->delivery_time)) ? $order->delivery_time : '';
            $data['door_bell'] = (isset($order->door_bell)) ? $order->door_bell : '';
            $data['street_number'] = (isset($order->street_number)) ? $order->street_number : '';
            $items = (isset($order->order_items)) ? $order->order_items : [];
            $data['order_total_text'] = (isset($order->order_total_text)) ? $order->order_total_text : '';

            $items_arr = [];
            if(count($items) > 0)
            {
                foreach($items as $item_dt)
                {
                    $item = [];
                    $item['item_name'] = (isset($item_dt['item_name'])) ? $item_dt['item_name'] : '';
                    $item['item_qty'] = (isset($item_dt['item_qty'])) ? $item_dt['item_qty'] : 0;
                    $item['sub_total_text'] = (isset($item_dt['sub_total'])) ? Currency::currency($currency)->format($item_dt['sub_total']) : Currency::currency($currency)->format(0);

                    $option = unserialize($item_dt['options']);
                    if(!empty($option))
                    {
                        $item['options'] = implode(', ',$option);
                    }
                    else
                    {
                        $item['options'] = '';
                    }

                    $items_arr[] = $item;
                }
            }

            $total_amount = $order->order_total;

            // SubTotal
            $data['subtotal'] = Currency::currency($currency)->format($total_amount);

            // Discount
            if($order->discount_per > 0)
            {
                if($discount_type == 'fixed')
                {
                    $discount_amount = $order->discount_per;
                    $discount_text = "- " . Currency::currency($currency)->format($order->discount_per);
                }
                else
                {
                    $discount_amount = ($total_amount * $order->discount_per) / 100;
                    $discount_text = "- " . $order->discount_per . "%";
                }
                $total_amount = $total_amount - $discount_amount;
            }
            else
            {
                $discount_text = 0;
            }
            $data['discount'] = $discount_text;

            // Coupon Discount
            if($order->coupon_per > 0)
            {
                if($coupon_type == 'fixed')
                {
                    $coupon_amount = $order->coupon_per;
                    $coupon_text = "- " . Currency::currency($currency)->format($order->coupon_per);
                }
                else
                {
                    $coupon_amount = ($total_amount * $order->coupon_per) / 100;
                    $coupon_text = "- " . $order->coupon_per . "%";
                }
                $total_amount = $total_amount - $coupon_amount;
            }
            else
            {
                $coupon_text = 0;
            }
            $data['coupon_discount'] = $coupon_text;

            // Tip
            if(($order->payment_method == 'paypal' || $order->payment_method == 'every_pay') && $order->tip > 0)
            {
                $total_amount = $total_amount + $order->tip;
                $tip_text = "+" . Currency::currency($currency)->format($order->tip);
            }
            else
            {
                $tip_text = 0;
            }
            $data['tip'] = $tip_text;
            $data['total_amount'] = Currency::currency($currency)->format($total_amount);
            $data['items'] = $items_arr;


            if($data['raw_printing'] == 1)
            {
                $res_data = $data;
            }
            else
            {
                $html .= '<div class="row justify-content-center">';
                    $html .= '<div class="col-md-10">';
                        $html .= '<div class="card">';
                            $html .= '<div class="card-body" style="font-size:38px!important;">';
                                $html .= '<div class="row">';
                                    $html .= '<div class="col-md-12 text-center mb-3">';
                                        $html .= '<p class="m-0"><strong>'.$data['receipt_intro'].'</strong></p>';
                                    $html .= '</div>';
                                    $html .= '<div class="col-md-12">';
                                        $html .= '<ul class="p-2 m-0 list-unstyled" style="border-bottom:2px solid #000 !important; border-top: 2px dotted #ccc;">';

                                            $html .= '<li><b>'.__('Order').' '.__('Id').': </b>'.$order->order_id.'</li>';
                                            $html .= '<li><b>'.__('Order Type').': </b> '.ucfirst(str_replace('_',' ',$data['checkout_type'])).'</li>';
                                            $html .= '<li><b>'.__('Payment Method').': </b> '.ucfirst($data['payment_method']).'</li>';
                                            $html .= '<li><b>'.__('Date').': </b> '.$data['order_date'].'</li>';
                                            $html .= '<li><b>'.__('Time').': </b> '.$data['order_time'].'</li>';

                                            if($data['checkout_type'] == 'takeaway' || $data['checkout_type'] == 'delivery' || $data['checkout_type'] == 'room_delivery')
                                            {
                                                $html .= '<li><b>'.__('Customer').': </b> '.$data['customer'].'</li>';
                                            }

                                            if($data['checkout_type'] == 'delivery')
                                            {
                                                $html .= '<li><b>'.__('Address').': </b> '.$data['address'].'</li>';
                                                $html .= '<li><b>'.__('Street Number').': </b> '.$data['street_number'].'</li>';
                                                $html .= '<li><b>'.__('Floor').': </b> '.$data['floor'].'</li>';
                                                $html .= '<li><b>'.__('Door Bell').': </b> '.$data['door_bell'].'</li>';
                                            }

                                            if($data['checkout_type'] == 'room_delivery')
                                            {
                                                $html .= '<li><b>'.__('Room No.').': </b> '.$data['room_no'].'</li>';
                                                if(!empty($delivery_time))
                                                {
                                                    $html .= '<li><b>'.__('Delivery Time').': </b> '.$delivery_time.'</li>';
                                                }
                                            }

                                            if($data['checkout_type'] == 'table_service')
                                            {
                                                $html .= '<li><b>'.__('Table No.').': </b> '.$data['table_no'].'</li>';
                                            }

                                            if($data['checkout_type'] == 'takeaway' || $data['checkout_type'] == 'delivery')
                                            {
                                                $html .= '<li><b>'.__('Telephone').': </b> '.$data['phone'].'</li>';
                                                // $html .= '<li><b>'.__('Email').': </b> '.$data['email'].'</li>';
                                            }

                                        $html .= '</ul>';
                                    $html .= '</div>';
                                $html .= '</div>';
                                $html .= '<div class="row">';
                                    $html .= '<div class="col-md-12">';
                                        $html .= '<table class="table border-0 m-0" style="border-bottom:2px solid #000 !important">';
                                            $html .= '<thead style="border-bottom: 2px solid #000;">';
                                                $html .= '<tr><th class="border-0" width="10%">'.__('S.No').'</th><th class="border-0">'.__('Item').'</th><th class="border-0" width="10%">'.__('Qty.').'</th><th width="25%" class="text-end border-0">'.__('Price').'</th></tr>';
                                            $html .= '</thead>';
                                            $html .= '<tbody>';
                                                if(count($items) > 0)
                                                {
                                                    $i=1;
                                                    foreach($items as $item)
                                                    {
                                                        $item_name = (isset($item['item_name'])) ? $item['item_name'] : '';
                                                        $item_qty = (isset($item['item_qty'])) ? $item['item_qty'] : 0;
                                                        $sub_total_text = (isset($item['sub_total_text'])) ? $item['sub_total_text'] : 0;
                                                        $option = unserialize($item['options']);

                                                        $html .= '<tr>';
                                                            $html .= '<td class="border-0">'.$i.'</td>';
                                                            $html .= '<td class="border-0">'.$item_name;
                                                            if(!empty($option))
                                                            {
                                                                $html .= '<br>'.implode(', ',$option);
                                                            }
                                                            $html .= '</td>';
                                                            $html .= '<td class="border-0">'.$item_qty.'</td>';
                                                            $html .= '<td class="text-end border-0">'.$sub_total_text.'</td>';
                                                        $html .= '</tr>';
                                                        $i++;
                                                    }
                                                }
                                            $html .= '</tbody>';
                                        $html .= '</table>';
                                    $html .= '</div>';
                                    $html .= '<div class="col-md-12 ord-rec-body">';
                                        $html .= '<table class="table m-0 border-0" style="border-bottom:2px solid #000 !important">';

                                            $total_amount = $order->order_total;

                                            $html .= '<tr>';
                                                $html .= '<td width="25%"><strong>'.__('Comments').': </strong></td>';
                                                $html .= '<td class="text-end">'.$data['instructions'].'</td>';
                                            $html .= '</tr>';

                                            $html .= '<tr>';
                                                $html .= '<td><strong>'.__('Sub Total').': </strong></td>';
                                                $html .= '<td class="text-end">'.Currency::currency($currency)->format($total_amount).'</td>';
                                            $html .= '</tr>';

                                            if($order->discount_per > 0)
                                            {
                                                $html .= '<tr>';
                                                    $html .= '<td><strong>'.__('Discount').': </strong></td>';
                                                    if($order->discount_per == 'fixed')
                                                    {
                                                        $discount_amount = $order->discount_per;
                                                        $html .= '<td class="text-end">- '.Currency::currency($currency)->format($order->discount_per).'</td>';
                                                    }
                                                    else
                                                    {
                                                        $discount_amount = ($total_amount * $order->discount_per) / 100;
                                                        $html .= '<td class="text-end">- '.$order->discount_per.'%</td>';
                                                    }
                                                $html .= '</tr>';
                                                $total_amount = $total_amount - $discount_amount;
                                            }

                                            if($order->coupon_per > 0)
                                            {
                                                $html .= '<tr>';
                                                    $html .= '<td><strong>'.__('Coupon Discount').': </strong></td>';
                                                    if($order->coupon_per == 'fixed')
                                                    {
                                                        $coupon_amount = $order->coupon_per;
                                                        $html .= '<td class="text-end">- '.Currency::currency($currency)->format($order->coupon_per).'</td>';
                                                    }
                                                    else
                                                    {
                                                        $coupon_amount = ($total_amount * $order->coupon_per) / 100;
                                                        $html .= '<td class="text-end">- '.$order->coupon_per.'%</td>';
                                                    }
                                                $html .= '</tr>';
                                                $total_amount = $total_amount - $coupon_amount;
                                            }

                                            if(($order->payment_method == 'paypal' || $order->payment_method == 'every_pay') && $order->tip > 0)
                                            {
                                                $total_amount = $total_amount + $order->tip;
                                                $html .= '<tr>';
                                                    $html .= '<td><strong>'. __('Tip') .'</strong></td>';
                                                    $html .= '<td class="text-end">+ '.Currency::currency($currency)->format($order->tip).'</td>';
                                                $html .= '</tr>';
                                            }

                                            $html .= '<tr>';
                                                $html .= '<td><strong>'. __('Total') .'</strong></td>';
                                                $html .= '<td class="text-end" colspan="2"><strong>'.Currency::currency($currency)->format($total_amount).'</strong></td>';
                                            $html .= '</tr>';

                                        $html .= '</table>';
                                    $html .= '</div>';
                                $html .= '</div>';
                                $html .= '<div class="row">';
                                    $html .= '<div class="col-md-12 text-center mt-2">';
                                        $html .= '<p class="m-0 p=0">';
                                            if(!empty($data['shop_telephone'])){
                                                $html .= 'T:'.$data['shop_telephone'];
                                            }

                                            if(!empty($data['shop_mobile'])){
                                                $html .= '&nbsp;&nbsp;M:'.$data['shop_mobile'];
                                            }

                                            if(!empty($data['shop_address'])){
                                                $html .= '&nbsp;&nbsp;'.$data['shop_address'];
                                            }

                                            if(!empty($data['shop_city'])){
                                                $html .= ',&nbsp;&nbsp;'.$data['shop_city'];
                                            }
                                        $html .= '</p>';
                                        $html .= '<p class="p-0 m-0 ord-rec-body-start">Thank You.</p>';
                                    $html .= '</div>';
                                $html .= '</div>';
                            $html .= '</div>';
                        $html .= '</div>';
                    $html .= '</div>';
                $html .= '</div>';

                $res_data = $html;
            }

            return response()->json([
                'success' => 1,
                'message' => "Receipt Generated",
                'data' => $res_data,
                'raw_printing' =>  $data['raw_printing'],
            ]);

        }
        catch (\Throwable $th)
        {
            return response()->json([
                'success' => 0,
                'message' => "Internal Server Error!",
            ]);
        }

    }


    // Function for Get Order Notification
    public function orderNotification(Request $request)
    {
        $html = '';
        $shop_id = (isset(Auth::user()->hasOneShop->shop['id'])) ? Auth::user()->hasOneShop->shop['id'] : '';
        $new_order_count = Order::where('shop_id',$shop_id)->where('order_status','pending')->where('is_new',1)->count();

        if($new_order_count > 0)
        {
            $html .= 'You Have '.$new_order_count.' New Notification';
            $html .= '<a href="'.route('client.orders').'"><span class="badge rounded-pill bg-primary p-2 ms-2">View All</span></a>';
        }
        else
        {
            $html .= 'You Have 0 New Orders';
            $html .= '<a href="'.route('client.orders').'"><span class="badge rounded-pill bg-primary p-2 ms-2">View All</span></a>';
        }


        return response()->json([
            'success' => 1,
            'data' => $html,
            'count' => $new_order_count,
        ]);
    }

    public function deliveryOrder(Request $request)
    {
        try {
           $from_email = isset(Auth::user()->email) ? Auth::user()->email : '';

            $delivery = Order::find($request->order_id);
            $delivery->staff_id = $request->staff_id;
            $delivery->save();

            $data['shop_settings'] = getClientSettings($delivery->shop_id);

            // Shop Currency
            $currency = (isset($shop_settings['default_currency']) && !empty($shop_settings['default_currency'])) ? $shop_settings['default_currency'] : 'EUR';

            $staff = Staff::find($request->staff_id);

            if(!empty($from_email) && !empty($staff) ){

                $to = $staff->email;
                $from = $from_email;
                $subject = "Order Delivery";
                $discount_type = (isset($delivery->discount_type) && !empty($delivery->discount_type)) ? $delivery->discount_type : 'percentage';

                $message = '';
                $message .= '<div>';
                    $message .= '<table style="width:100%; border:1px solid gray;border-collapse: collapse;">';
                        $message .= '<tbody style="font-weight: 700!important;">';
                            $message .= '<tr>';
                                $message .= '<td style="padding:10px; border-bottom:1px solid gray">Order No : </td>';
                                $message .= '<td style="padding:10px; border-bottom:1px solid gray">#'.$delivery->order_id.'</td>';
                            $message .= '</tr>';
                            if ($delivery->checkout_type == 'takeaway') {
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Customer: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->firstname.' '.$delivery->lastname.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Telephone: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->phone.'</td>';
                                $message .= '</tr>';
                            }elseif($delivery->checkout_type == 'table_service') {
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Table No: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->table.'</td>';
                                $message .= '</tr>';
                            }elseif($delivery->checkout_type == 'room_delivery') {
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Customer: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->firstname.' '.$delivery->lastname.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Room No: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->room.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Floor: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->floor.'</td>';
                                $message .= '</tr>';
                            }elseif($delivery->checkout_type == 'delivery') {
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Customer: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->firstname.' '.$delivery->lastname.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Telephone: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->phone.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Address: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->address.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Street Number: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->street_number.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Floor: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->floor.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Door Bell: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->door_bell.'</td>';
                                $message .= '</tr>';
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Google Map: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray"><a href="https://maps.google.com?q='.$delivery->address.'" target="_blank">Address Link</a></td>';
                                $message .= '</tr>';
                            }
                            if($delivery->instructions){
                                $message .= '<tr>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">Order Comment: </td>';
                                    $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.$delivery->instructions.'</td>';
                                $message .= '</tr>';

                            }

                        $message .= '</tbody>';
                    $message .= '</table>';
                $message .= '</div>';

                // Order Total
                $order_tot_amount = $delivery->order_total;
                $message .= '<div>';
                $message .= '<table style="width:100%; border:1px solid gray;border-collapse: collapse;">';
                $message .= '<tbody style="font-weight: 700!important;">';
                    $message .= '<tr>';
                        $message .= '<td style="padding:10px; border-bottom:1px solid gray">Sub Total : </td>';
                        $message .= '<td style="padding:10px; border-bottom:1px solid gray">'.Currency::currency($currency)->format($order_tot_amount).'</td>';
                    $message .= '</tr>';

                    if($delivery->discount_per > 0)
                    {
                        $message .= '<tr>';
                            $message .= '<td style="padding:10px; border-bottom:1px solid gray">Discount : </td>';
                            if($discount_type == 'fixed')
                            {
                                $discount_amount = $delivery->discount_per;
                                $message .= '<td style="padding:10px; border-bottom:1px solid gray">- '.Currency::currency($currency)->format($delivery->discount_per).'</td>';
                            }
                            else
                            {
                                $discount_amount = ($order_tot_amount * $delivery->discount_per) / 100;
                                $message .= '<td style="padding:10px; border-bottom:1px solid gray">- '.$delivery->discount_per.'%</td>';
                            }
                            $order_tot_amount = $order_tot_amount - $discount_amount;
                        $message .= '</tr>';
                    }

                    if($delivery->coupon_per > 0)
                    {
                        $message .= '<tr>';
                            $message .= '<td style="padding:10px; border-bottom:1px solid gray">Coupon Discount : </td>';
                            if($discount_type == 'fixed')
                            {
                                $coupon_amount = $delivery->coupon_per;
                                $message .= '<td style="padding:10px; border-bottom:1px solid gray">- '.Currency::currency($currency)->format($delivery->coupon_per).'</td>';
                            }
                            else
                            {
                                $coupon_amount = ($order_tot_amount * $delivery->coupon_per) / 100;
                                $message .= '<td style="padding:10px; border-bottom:1px solid gray">- '.$delivery->coupon_per.'%</td>';
                            }
                            $order_tot_amount = $order_tot_amount - $coupon_amount;
                        $message .= '</tr>';
                    }

                    if(($delivery->payment_method == 'paypal' || $delivery->payment_method == 'every_pay') && $delivery->tip > 0)
                    {
                        $order_tot_amount = $order_tot_amount + $delivery->tip;
                        $message .= '<tr>';
                            $message .= '<td style="padding:10px; border-bottom:1px solid gray">Tip : </td>';
                            $message .= '<td style="padding:10px; border-bottom:1px solid gray">+ '.Currency::currency($currency)->format($delivery->tip).'</td>';
                        $message .= '</tr>';
                    }

                    $message .= '<tr>';
                        $message .= '<td style="padding:10px;">Total : </td>';
                        $message .= '<td style="padding:10px;">';
                            $message .= Currency::currency($currency)->format($order_tot_amount);
                        $message .= '</td>';
                    $message .= '</tr>';

                $message .= '</tbody>';
                $message .= '</table>';
                $message .= '</div>';


                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

                // More headers
                $headers .= 'From: <'.$from.'>' . "\r\n";

                mail($to,$subject,$message,$headers);
            }

            return response()->json([
                'success' => 1,
                'message' => 'Order Delivery Send SuccessFully...',
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => 0,
                'message' => "Internal Server Error!",
            ]);
        }
    }

}
