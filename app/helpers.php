<?php

    use App\Models\{AdminSettings, Category, CategoryProductTags,ClientSettings, CodePage, DeliveryAreas, Ingredient,ItemPrice, Items, Languages,LanguageSettings, OrderSetting, OtherSetting, PaymentSettings, ShopBanner,Subscriptions,ThemeSettings,User,UserShop,UsersSubscriptions,Shop,DefaultShopLayout, ShopRoom, ShopTable};
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;

    // Get Admin's Settings
    function getAdminSettings()
    {
        // Keys
        $keys = ([
            'favourite_client_limit',
            'copyright_text',
            'logo',
            'loader',
            'login_form_logo',
            'login_form_background',
            'default_light_theme_image',
            'default_dark_theme_image',
            'theme_main_screen_demo',
            'theme_category_screen_demo',
            'theme_main_screen_layout_two_demo',
            'theme_category_screen_layout_two_demo',
            'theme_main_screen_layout_three_demo',
            'theme_category_screen_layout_three_demo',
            'default_special_item_image',
            'contact_us_email',
            'google_map_api',
            'contact_us_mail_template',
            'subscription_expire_mail',
            'days_for_send_first_expiry_mail',
            'days_for_send_second_expiry_mail',
            'subscription_expiry_mails',
            'cart_modal_screen_layout',
            'is_client_loader',
            'disable_menu_url',
        ]);

        $settings = [];

        foreach($keys as $key)
        {
            $query = AdminSettings::select('value')->where('key',$key)->first();
            $settings[$key] = isset($query->value) ? $query->value : '';
        }

        return $settings;
    }


    // Get Client's Settings
    function getClientSettings($shopID="")
    {

        if(!empty($shopID))
        {
            $shop_id = $shopID;
        }
        else
        {
            $shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';
        }

        // Keys
        $keys = ([
            'shop_view_header_logo',
            'shop_intro_icon',
            'shop_intro_icon_1',
            'shop_intro_icon_2',
            'shop_intro_icon_3',
            'shop_intro_icon_4',
            'shop_intro_icon_link_1',
            'shop_intro_icon_link_2',
            'shop_intro_icon_link_3',
            'shop_intro_icon_link_4',
            'shop_intro_icon_is_cube',
            'intro_icon_status',
            'intro_icon_duration',
            'business_name',
            'default_currency',
            'business_telephone',
            'instagram_link',
            'pinterest_link',
            'twitter_link',
            'facebook_link',
            'foursquare_link',
            'tripadvisor_link',
            'map_url',
            'website_url',
            'shop_active_theme',
            'orders_mail_form_client',
            'orders_mail_form_customer',
            'check_in_mail_form',
            'shop_loader',
            'is_loader',
            'label_font_size',
            'shop_start_time',
            'shop_end_time',
            'grading-email-required',
            'waiter_call_status',
            'waiter_call_on_off_sound',
            'waiter_call_sound',
            'is_sub_title',
            'table_enable_status',
            'room_enable_status',
            'default_timezone',
            'logo_layout_1',
            'logo_layout_2',
            'logo_layout_3'
        ]);

        $settings = [];

        foreach($keys as $key)
        {
            $query = ClientSettings::select('value')->where('shop_id',$shop_id)->where('key',$key)->first();
            $settings[$key] = isset($query->value) ? $query->value : '';
        }

        return $settings;
    }


    // Get Order Settings
    function getOrderSettings($shopID)
    {
        // Keys
        $keys = ([
            'delivery',
            'takeaway',
            'room_delivery',
            'table_service',
            'only_cart',
            'auto_order_approval',
            'scheduler_active',
            'min_amount_for_delivery',
            'discount_percentage',
            'order_arrival_minutes',
            'schedule_array',
            'default_printer',
            'receipt_intro',
            'auto_print',
            'raw_printing',
            'printer_paper',
            'printer_tray',
            'play_sound',
            'notification_sound',
            'enable_print',
            'print_font_size',
            'discount_type',
            'shop_address',
            'shop_latitude',
            'shop_longitude',
            'google_map_order_view',
            'default_code_page',
            'greek_list',
        ]);

        $settings = [];

        foreach($keys as $key)
        {
            $query = OrderSetting::select('value')->where('shop_id',$shopID)->where('key',$key)->first();
            $settings[$key] = isset($query->value) ? $query->value : '';
        }

        return $settings;
    }


    // Get Code Page Details
    function getCodePageSettings($code_page_id = null)
    {
        if($code_page_id){
            $code_page_details = CodePage::find($code_page_id);
        }else{
            $code_page_details = '';
        }
        return $code_page_details;
    }


    // Get Payment Settings
    function getPaymentSettings($shopID)
    {
        // Keys
        $keys = [
            'cash',
            'cash_pos',
            'paypal',
            'paypal_mode',
            'paypal_public_key',
            'paypal_private_key',
            'every_pay',
            'everypay_mode',
            'every_pay_public_key',
            'every_pay_private_key',
        ];

        $settings = [];

        foreach($keys as $key)
        {
            $query = PaymentSettings::select('value')->where('shop_id',$shopID)->where('key',$key)->first();
            $settings[$key] = isset($query->value) ? $query->value : '';
        }

        return $settings;
    }


    // Get Client's LanguageSettings
    function clientLanguageSettings($shopID)
    {
        // Keys
        $keys = ([
            'primary_language',
            'google_translate',
        ]);

        $settings = [];

        foreach($keys as $key)
        {
            $query = LanguageSettings::select('value')->where('key',$key)->where('shop_id',$shopID)->first();
            $settings[$key] = isset($query->value) ? $query->value : '';
        }

        return $settings;
    }


    // Get Package Permissions
    function getPackagePermission($subID)
    {
        $details = Subscriptions::where('id',$subID)->first();
        $permission = (isset($details['permissions']) && !empty($details['permissions'])) ? unserialize($details['permissions']) : '';
        return $permission;
    }


    // Get Subscription ID
    function getClientSubscriptionID($shop_id)
    {
        $user_shop = UserShop::where('shop_id',$shop_id)->first();
        $user_id = (isset($user_shop['user_id'])) ? $user_shop['user_id'] : '';
        $user_subscription = UsersSubscriptions::where('user_id',$user_id)->first();
        $subscription_id = (isset($user_subscription['subscription_id'])) ? $user_subscription['subscription_id'] : '';
        return $subscription_id;
    }


    // Get Theme Settings
    function themeSettings($themeID)
    {
        // Keys
        $keys = ([
            'desk_layout',
            'slider_effect',
            'header_color',
            'sticky_header',
            'language_bar_position',
            'logo_position',
            'search_box_position',
            'banner_position',
            'banner_type',
            'banner_slide_button',
            'banner_delay_time',
            'category_image_sider',
            'category_image_slider_delay_time',
            'category_side',
            'background_color',
            'font_color',
            'label_color',
            'social_media_icon_color',
            'category_slider_effect',
            'categories_bar_color',
            'menu_bar_font_color',
            'category_title_and_description_color',
            'price_color',
            'cart_icon_color',
            'item_box_shadow',
            'item_box_shadow_color',
            'item_box_shadow_thickness',
            'item_divider',
            'item_divider_color',
            'item_divider_thickness',
            'item_divider_type',
            'item_divider_position',
            'item_divider_font_color',
            'tag_font_color',
            'tag_label_color',
            'category_bar_type',
            'theme_preview_image',
            'search_box_icon_color',
            'read_more_link_color',
            'banner_height',
            'label_color_transparency',
            'item_box_background_color',
            'item_title_color',
            'item_description_color',
            'icon_bg_color',
            'special_discount_backgound_color',
            'header_image',
            'category_view',
            'special_discount_text_color',
            'cart_animation_color',
            'modal_item_title_color',
            'modal_item_des_color',
            'modal_item_price_color',
            'modal_close_icon_color',
            'modal_close_bg_color',
            'modal_add_btn_color',
            'modal_add_btn_text_color',
            'modal_quantity_icon_color',
            'modal_quantity_bg_color',
            'modal_price_label_color',
            'modal_igradient_type_color',
            'modal_body_bg_color',
            'special_day_effect_box',
            'special_day_effect_color',
            'category_box_shadow',
            'category_box_title_icon_color',
            'category_box_text_color',
            'category_box_background',
            'category_box_title_background',
            'label_font_size',
            'cart_modal_screen_layout',
            'modal_item_title_font_size',
            'back_arrow_bg_color',
            'back_arrow_icon_color',
            'category_box_act_txt_bg',
            'header_bg_color_opc',
            'header_effect_bg_color',
            'rating_service_name_color',
            'bar_icon_color',
            'bar_icon_bg_color',
            'cover_link_icon_color',
            'cover_link_bg_color',
            'bg_image',
            'rating_star_icon_color',
            'tag_background_color',
            'active_tag_background_color',
            'category_bar_slider_buttons_color',
        ]);

        $settings = [];

        foreach($keys as $key)
        {
            $query = ThemeSettings::select('value')->where('key',$key)->where('theme_id',$themeID)->first();
            $settings[$key] = isset($query->value) ? $query->value : '';
        }

        return $settings;
    }


    // Get Language Details
    function getLangDetails($langID)
    {
        $language = Languages::where('id',$langID)->first();
        return $language;
    }


    // Get Language Details by Code
    function getLangDetailsbyCode($langCode)
    {
        $language = Languages::where('code',$langCode)->first();
        return $language;
    }


    // Get Tags Product
    function getTagsProducts($tagID,$catID)
    {
        if(!empty($tagID) && !empty($catID))
        {
            // $items = CategoryProductTags::with(['product'])->where('tag_id',$tagID)->where('category_id',$catID)->get();
            $items = CategoryProductTags::join('items','items.id','category_product_tags.item_id')->where('tag_id',$tagID)->where('category_product_tags.category_id',$catID)->orderBy('items.order_key')->where('items.published',1)->get();
        }
        else
        {
            $items = [];
        }
        return $items;
    }


    // Get Ingredients Details
    function getIngredientDetail($id)
    {
        $ingredient = Ingredient::where('id',$id)->first();
        return $ingredient;
    }


    // Get Banner Settings
    function getBanners($shopID)
    {
        $banners = ShopBanner::where('shop_id',$shopID)->where('key','shop_banner')->where('status',1)->get();
        return $banners;
    }


    // Get Favourite Clients List
    function FavClients($limit)
    {
        $clients = User::with(['hasOneShop','hasOneSubscription'])->where('user_type',2)->where('is_fav',1)->limit($limit)->get();
        return $clients;
    }


    // Function for Hex to RGB
    function hexToRgb($hex)
    {
        $hex      = str_replace('#', '', $hex);
        $length   = strlen($hex);
        $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));

        return $rgb;
    }


    // Function for Get Item Price
    function getItemPrice($itemID)
    {
        $prices = ItemPrice::where('item_id',$itemID)->get();
        return $prices;
    }


    // Function for Genrate random Token
    function genratetoken($length = 32)
    {
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($string) - 1;
        $token = '';

        for ($i = 0; $i < $length; $i++)
        {
            $token .= $string[mt_rand(0, $max)];
        }

        return $token;
    }


    // Check Schedule
    function checkCategorySchedule($catID,$shop_id)
    {
        date_default_timezone_set('Europe/Athens');
        $current_date = Carbon::now();
        $today = strtolower($current_date->format('l'));
        $current_time = strtotime($current_date->format('G:i'));
        $cat_details = Category::where('id',$catID)->where('shop_id',$shop_id)->first();
        $schedule = (isset($cat_details['schedule'])) ? $cat_details['schedule'] : 0;

        if($schedule == 0)
        {
            return 1;
        }
        else
        {
            $schedule_type = (isset($cat_details['schedule_type']) && !empty($cat_details['schedule_type'])) ? $cat_details['schedule_type'] : 'time';

            if($schedule_type == 'time')
            {
                $schedule_arr = (isset($cat_details['schedule_value']) && !empty($cat_details['schedule_value'])) ? json_decode($cat_details['schedule_value'],true) : '';
                if(count($schedule_arr) > 0)
                {
                    $current_day = (isset($schedule_arr[$today])) ? $schedule_arr[$today] : '';
                    if(isset($current_day['enabled']) && $current_day['enabled'] == 1)
                    {
                        $time_schedule_arr = isset($current_day['timesSchedules']) ? $current_day['timesSchedules'] : [];

                        if(count($time_schedule_arr) > 0)
                        {
                            $count = 1;
                            $total_count = count($time_schedule_arr);
                            foreach($time_schedule_arr as $tsarr)
                            {
                                $start_time = strtotime($tsarr['startTime']);
                                $end_time = strtotime($tsarr['endTime']);

                                if($current_time > $start_time && $current_time < $end_time)
                                {
                                    return 1;
                                }
                                else
                                {
                                    if($count == $total_count)
                                    {
                                        return 0;
                                    }
                                }
                                $count ++;
                            }
                        }
                        else
                        {
                            return 0;
                        }
                    }
                    else
                    {
                        return 0;
                    }
                }
                else
                {
                    return 0;
                }
            }
            else
            {
                $start_date =  strtotime($cat_details['sch_start_date']);
                $end_date =  strtotime($cat_details['sch_end_date']);

                if(empty($start_date) || empty($end_date))
                {
                    return 1;
                }
                else
                {
                    $curr_date = strtotime($current_date);

                    if($curr_date > $start_date && $curr_date < $end_date)
                    {
                        return 1;
                    }
                    else
                    {
                        return 0;
                    }

                }

            }
        }
    }


    // Check Delivery Schedule
    function checkDeliverySchedule($shop_id)
    {
        date_default_timezone_set('Europe/Athens');

        $current_date = Carbon::now();
        $today = strtolower($current_date->format('l'));
        $current_time = strtotime($current_date->format('G:i'));

        // Order Settings
        $sch_enable_setting = OrderSetting::where('shop_id',$shop_id)->where('key','scheduler_active')->first();
        $sch_array_setting = OrderSetting::where('shop_id',$shop_id)->where('key','schedule_array')->first();

        $schedule = (isset($sch_enable_setting['value']) && $sch_enable_setting['value'] == 1) ? 1 : 0;
        $schedule_arr = (isset($sch_array_setting['value']) && !empty($sch_array_setting['value'])) ? json_decode($sch_array_setting['value'],true) : '';

        if($schedule == 0)
        {
            return 1;
        }
        else
        {
            if(count($schedule_arr) > 0)
            {
                $current_day = (isset($schedule_arr[$today])) ? $schedule_arr[$today] : '';
                if(isset($current_day['enabled']) && $current_day['enabled'] == 1)
                {
                    $time_schedule_arr = isset($current_day['timesSchedules']) ? $current_day['timesSchedules'] : [];

                    if(count($time_schedule_arr) > 0)
                    {
                        $count = 1;
                        $total_count = count($time_schedule_arr);
                        foreach($time_schedule_arr as $tsarr)
                        {
                            $start_time = strtotime($tsarr['startTime']);
                            $end_time = strtotime($tsarr['endTime']);

                            if($current_time > $start_time && $current_time < $end_time)
                            {
                                return 1;
                            }
                            else
                            {
                                if($count == $total_count)
                                {
                                    return 0;
                                }
                            }
                            $count ++;
                        }
                    }
                    else
                    {
                        return 0;
                    }
                }
                else
                {
                    return 0;
                }
            }
            else
            {
                return 0;
            }
        }

    }


    // Function for Check Delivery Available in Customer Zone
    function checkDeliveryAvilability($shop_id,$latitude,$longitude)
    {
        $delivery_areas = DeliveryAreas::where('shop_id',$shop_id)->get();
        $inside = 0;

        if(count($delivery_areas) > 0)
        {
            foreach($delivery_areas as $delivery_area)
            {
                $coordinates = (isset($delivery_area['coordinates']) && !empty($delivery_area['coordinates'])) ? unserialize($delivery_area['coordinates']) : '';

                $vertices = $coordinates;
                $vertexCount = count($vertices);

                for ($i = 0, $j = $vertexCount - 1; $i < $vertexCount; $j = $i++)
                {
                    $xi = $vertices[$i]['lat'];
                    $yi = $vertices[$i]['lng'];
                    $xj = $vertices[$j]['lat'];
                    $yj = $vertices[$j]['lng'];

                    $intersect = (($yi > $longitude) != ($yj > $longitude)) && ($latitude < ($xj - $xi) * ($longitude - $yi) / ($yj - $yi) + $xi);

                    if ($intersect)
                    {
                        $inside = 1;
                    }
                }

            }
        }
        else
        {
            $inside = 0;
        }
        return $inside;
    }


    // Get total Quantity of Cart
    function getCartQuantity()
    {
        $cart = session()->get('cart', []);
        $total_quantity = 0;
        if(count($cart) > 0)
        {
            foreach($cart as $cart_data)
            {
                if(count($cart_data) > 0)
                {
                    foreach ($cart_data as $cart_val)
                    {
                        if(count($cart_val) > 0)
                        {
                            foreach($cart_val as $item)
                            {
                                $total_quantity += (isset($item['quantity'])) ? $item['quantity'] : 0;
                            }
                        }
                    }
                }
            }
        }

        if($total_quantity == 0)
        {
            session()->forget('cart');
            session()->save();
        }

        return $total_quantity;
    }


    // Get Total of Cart
    function getCartTotal()
    {
        $cart = session()->get('cart', []);
        $total = 0;
        if(count($cart) > 0)
        {
            foreach($cart as $cart_data)
            {
                if(count($cart_data) > 0)
                {
                    foreach($cart_data as $cart_val)
                    {
                        if(count($cart_val) > 0)
                        {
                            foreach($cart_val as $cart_item)
                            {
                                $total += (isset($cart_item['total_amount'])) ? $cart_item['total_amount'] : 0;
                            }
                        }
                    }
                }
            }
        }
        return $total;
    }


    // Get Item Details
    function itemDetails($itemID)
    {
        $item_details = Items::with('categories')->where('id',$itemID)->first();
        return $item_details;
    }


    // Function for get client PayPal Config
    function getPayPalConfig($shop_slug)
    {
        $shop = Shop::where('shop_slug',$shop_slug)->first();
        $shop_id = isset($shop['id']) ? $shop['id'] : '';

        // Get Payment Settings
        $payment_settings = getPaymentSettings($shop_id);

        $paypal_config = [
            'client_id' => (isset($payment_settings['paypal_public_key'])) ? $payment_settings['paypal_public_key'] : '',
            'secret' => (isset($payment_settings['paypal_private_key'])) ? $payment_settings['paypal_private_key'] : '',
            'settings' => [
                'mode' => (isset($payment_settings['paypal_mode'])) ? $payment_settings['paypal_mode'] : '',
                'http.ConnectionTimeOut' => 30,
                'log.LogEnabled' => 1,
                'log.FileName' => storage_path() . '/logs/paypal.log',
                'log.LogLevel' => 'ERROR',
            ]
        ];
        return $paypal_config;
    }


    // Function for get client EveryPay Config
    function getEveryPayConfig($shop_slug)
    {
        $shop = Shop::where('shop_slug',$shop_slug)->first();
        $shop_id = isset($shop['id']) ? $shop['id'] : '';

        // Get Payment Settings
        $payment_settings = getPaymentSettings($shop_id);

        $every_pay_config = [
            'public_key' => (isset($payment_settings['every_pay_public_key'])) ? $payment_settings['every_pay_public_key'] : '',
            'secret_key' => (isset($payment_settings['every_pay_private_key'])) ? $payment_settings['every_pay_private_key'] : '',
            'mode' => (isset($payment_settings['everypay_mode'])) ? $payment_settings['everypay_mode'] : 1,
        ];
        return $every_pay_config;
    }


    // Function for Check Category Type Permission
    function checkCatTypePermission($catType,$shop_id)
    {
        $permission = 0;
        // Get Subscription ID
        $subscription_id = getClientSubscriptionID($shop_id);

        // Get Package Permissions
        $package_permissions = getPackagePermission($subscription_id);

        if($catType == 'parent_category' || $catType == 'product_category')
        {
            $permission = 1;
        }
        else
        {
            if($catType == 'page')
            {
                if(isset($package_permissions['page']) && !empty($package_permissions['page']) && $package_permissions['page'] == 1)
                {
                    $permission = 1;
                }
            }
            elseif($catType == 'link')
            {
                if(isset($package_permissions['link']) && !empty($package_permissions['link']) && $package_permissions['link'] == 1)
                {
                    $permission = 1;
                }
            }
            elseif($catType == 'pdf_page')
            {
                if(isset($package_permissions['pdf_file']) && !empty($package_permissions['pdf_file']) && $package_permissions['pdf_file'] == 1)
                {
                    $permission = 1;
                }
            }
            elseif($catType == 'gallery')
            {
                if(isset($package_permissions['gallery']) && !empty($package_permissions['gallery']) && $package_permissions['gallery'] == 1)
                {
                    $permission = 1;
                }
            }
            elseif($catType == 'check_in')
            {
                if(isset($package_permissions['check_in']) && !empty($package_permissions['check_in']) && $package_permissions['check_in'] == 1)
                {
                    $permission = 1;
                }
            }
        }

        return $permission;
    }


    // Function for get More Translations
    function moreTranslations($shop_id,$key)
    {
        $more_translations = OtherSetting::where('shop_id',$shop_id)->where('key',$key)->first();
        return $more_translations;
    }


    // Function for get Distance
    function getDistance($lat1=null,$long1=null,$lat2=null,$long2=null,$unit='KM')
    {
        $distanceUnit = strtoupper($unit);
        $theta = $long1 - $long2;

        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);

        $miles = $dist * 60 * 1.1515;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        $feets = $miles * 5280;
        $yards = $feets / 3;
        $nauticalmiles = $miles * 0.8684;

        if($distanceUnit == 'ML') // Miles
        {
            // return number_format($miles,2).' ML.';
            return number_format($miles,2);
        }
        elseif($distanceUnit == 'KM') // Kilometers
        {
            // return number_format($kilometers,2).' KM.';
            return number_format($kilometers,2);
        }
        elseif($distanceUnit == 'MT') // Meters
        {
            // return number_format($meters,2).' MT.';
            return number_format($meters,2);
        }
        elseif($distanceUnit == 'FT') // Feets
        {
            // return number_format($feets,2).' FT.';
            return number_format($feets,2);
        }
        elseif($distanceUnit == 'YD') // Yards
        {
            // return number_format($yards,2).' YD.';
            return number_format($yards,2);
        }
        elseif($distanceUnit == 'NM') // NauticalMiles
        {
            // return number_format($nauticalmiles,2).' NM.';
            return number_format($nauticalmiles,2);
        }
    }


    function getcheckLayout(){
        $shopId = Auth::user()->hasOneShop->shop_id;

        $layout = DefaultShopLayout::where('shop_id',$shopId)->where('layout_id',3)->first();

        return $layout;
    }

    function getShopSlug()
    {
        // Get the full URL
        $fullUrl = request()->url();
        $dynamicPart = str_replace(url('/'),'',$fullUrl);
        $shopSlug = trim($dynamicPart,'/');

        return $shopSlug;
    }


    function  getAllItems($cat_id)
    {
        // $allItems = Items::where('category_id',$cat_id)->orderBy('order_key')->where('published',1)->get();
        $allItems = Items::where('published', 1)
                        ->whereHas('categories', function ($query) use ($cat_id) {
                            $query->where('id', $cat_id);
                        })
                        ->orderBy('order_key')
                        ->get();
        return $allItems;
    }

    function getTagName($item_id)
    {
        $tag_name = CategoryProductTags::where('item_id',$item_id)->with('hasOneTag')->first();

        return $tag_name;
    }

    function getShopRooms($shop_id)
    {
        $rooms = ShopRoom::where('shop_id',$shop_id)->where('status',1)->get();

        return $rooms;
    }

    function getShopTables($shop_id)
    {
        $tables =  ShopTable::where('shop_id',$shop_id)->where('status',1)->get();

        return $tables;
    }


    function getChildCategories($parent_id)
    {
        $datas = Category::with(['categoryImages', 'items'])->where('published', 1)->where('parent_id', $parent_id)->orderBy('order_key')->get();

        return $datas;
    }

    function getCategoryDetail($id)
    {
        $cat = Category::with(['categoryImages', 'items'])->where('id', $id)->first();

        return $cat;
    }

    function checkShopStatus($shop_id) {
        $users_shop = UserShop::where('shop_id', $shop_id)->first();
        $user_id = $users_shop['user_id'] ?? "";
        $user_details = User::where('id', $user_id)->first();
        $is_active = $user_details['status'] ?? 0;

        $user_subscription = UsersSubscriptions::where('user_id', $user_id)->first();

        if(isset($user_subscription['end_date']) && !empty($user_subscription['end_date'])){
            $end_date = $user_subscription['end_date'];
            $end_date = Carbon::now()->diffInDays($end_date, false);

            if($end_date > 0){
                if($is_active == 1){
                    return 1;
                }else{
                    return 0;
                }
            }else{
                if($is_active == 1){
                    return 1;
                }else{
                    return 0;
                }
            }
        }else{
            return 0;
        }
    }

?>
