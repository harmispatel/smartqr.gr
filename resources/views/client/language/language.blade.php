@php
    $userID = Auth::user()->id;
    $userShopId = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : '';

    // Get Language Settings
    $language_settings = clientLanguageSettings($userShopId);
    $primary_lang_id = isset($language_settings['primary_language']) ? $language_settings['primary_language'] : '';
    $google_translate = isset($language_settings['google_translate']) ? $language_settings['google_translate'] : 0;

@endphp

@extends('client.layouts.client-layout')

@section('title', __('Language'))

@section('content')

    {{-- Edit Other Settings Modal --}}
    <div class="modal fade" id="editOtherSettingsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editOtherSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOtherSettingsModalLabel">{{ __('Edit More Trasnlations')}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="other_setting_lang_div">
                </div>
                <div class="modal-footer">
                    <a class="btn btn-sm btn-success" onclick="updateOtherSetting()">{{ __('Update') }}</a>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="shopID" id="shopID" value="{{ $userShopId }}">

    <section class="lang_main">
        <div class="row">
            <div class="col-md-3">
                <div class="lang_sidebar">
                    <div class="lang_title">
                        <h2>{{ __('Menu')}}</h2>
                    </div>
                    <ul class="nav flex-column lang_menu_ul" id="nav_accordion">
                        @if(count($categories) > 0)
                            @foreach ($categories as $category)
                                @php
                                    $cat_name_key = $lang_code."_name";
                                    $cat_name = $category[$cat_name_key];
                                @endphp
                                <li class="nav-item has-submenu">
                                    <a class="nav-link" style="cursor: pointer;">
                                        <span class="arrow-icon"><i class="fa-solid fa-chevron-right me-3"></i></span>
                                        <span onclick="getCatDetails({{ $category->id }})"><i class="fa-solid fa-cart-shopping me-1"></i> {{ $cat_name }}</span>
                                    </a>
                                    <ul class="submenu collapse lang_menu_ul">
                                        @if(isset($category->items) && count($category->items) > 0)
                                            @foreach ($category->items as $item)
                                            @php
                                                $item_name_key = $lang_code."_name";
                                                $item_name = $item[$item_name_key];
                                            @endphp
                                                <li><a class="nav-link" style="cursor: pointer;" onclick="getItemDetails({{ $item->id }})">{{ $item_name }}</a></li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </li>
                            @endforeach
                        @endif
                </div>
            </div>
            <div class="col-md-9">
                <div class="lang_right_side">
                    <div class="lang_title">
                        <h2>{{ __('Translations')}}</h2>
                        <p>On the left you can find your menu's structure. Click on every menu element and insert the translation of the selected additional languages. Note that primary language descriptions can only be changed through ‘Menu’ tab features.</p>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Primary Language')}}</label>
                                <select class="form-select" name="primary_language" id="primary_language" onchange="setPrimaryLanguage({{ $userShopId }})">
                                    @if(count($languages) > 0)
                                        @foreach ($languages as $language)
                                            <option value="{{ $language->id }}" {{ ($primary_lang_id == $language->id) ? 'selected' : '' }}>{{ $language->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        @php
                            $additional_lang_arr = [];
                            if(count($additional_languages) > 0)
                            {
                                foreach ($additional_languages as $key => $value)
                                {
                                    $additional_lang_arr[] = $value->language_id;
                                }
                            }
                        @endphp
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="additional_languages">{{ __('Additional Languages')}}</label>
                                <select name="additional_languages[]" id="additional_languages" class="form-select" multiple>
                                    @if(count($languages) > 0)
                                        @foreach ($languages as $key => $language)
                                            @if($primary_lang_id != $language->id)
                                                <option value="{{ $language->id }}" {{ (in_array($language->id,$additional_lang_arr)) ? 'selected' : '' }}>{{ $language->name }}</option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Google translate')}}</label>
                                <br/>
                                <label class="switch">
                                    <input type="checkbox" name="google_translate" id="google_translate" value="1" {{ ($google_translate == 1) ? 'checked' : '' }}>
                                    <span class="slider round">
                                        <i class="fa-solid fa-circle-check check_icon"></i>
                                        <i class="fa-sharp fa-solid fa-circle-xmark uncheck_icon"></i>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="select_lang">
                        <div class="row" id="langBox">
                            @if(count($additional_languages) > 0)
                                @foreach ($additional_languages as $key => $lang)
                                    <div class="col-md-3 mb-2 language_{{ $lang->language_id }}">
                                        <div class="select_lang_inr">
                                            <div class="">
                                                {{-- <input class="form-check-input" type="checkbox" value="1" id="cbox_{{ $lang->id }}"> --}}
                                                <label class="form-check-label" for="cbox_{{ $lang->id }}">{{ $lang->language['name'] }}</label>
                                            </div>
                                            <label class="switch">
                                                <input type="checkbox"  id="publish_{{ $lang->id }}" {{ ($lang->published == 1) ? 'checked' : '' }} onchange="changeLanguageStatus({{ $lang->id }})">
                                                <span class="slider round">
                                                    <span class="check_icon">{{ __('Publish')}}</span>
                                                    <span class="uncheck_icon">{{ __('Unpublish')}}</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="cate_lang_box">
                    </div>
                    <div class="item_lang_box">
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <h3>{{ __('More Trasnlations') }}</h3>
            <div class="col-md-12">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 10%">Sr.</th>
                            <th>{{ __('Name') }}</th>
                            <th style="width: 20%">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($other_settings) > 0)
                            @foreach ($other_settings as $other_setting)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($other_setting->key == 'read_more_link_label')
                                            {{ __('TITLE: For "Read More"') }}
                                        @elseif($other_setting->key == 'delivery_message')
                                            {{ __('ALERT: Address Outside Delivery Zone') }}
                                        @elseif ($other_setting->key == 'today_special_icon')
                                            {{ __('IMAGE: For "Today\'s Special"') }}
                                        @elseif ($other_setting->key == 'distance_message')
                                            {{ __('NOTICE: Minimum Order Per Distance') }}
                                        @elseif ($other_setting->key == 'distance_alert_message')
                                            {{ __('ALERT: Minimum Order Per Distance') }}
                                        @elseif ($other_setting->key == 'homepage_intro')
                                            {{ __('FOOTER: Alternative Text') }}
                                        @elseif ($other_setting->key == 'seo_message')
                                            {{ __('SEO: Page Title') }}
                                        @elseif ($other_setting->key == 'service_closed_message')
                                            {{ __('NOTICE: Service Is Closed') }}
                                        @elseif ($other_setting->key == 'header_text_1')
                                            {{ __('HEADER 1: Working Hours') }}
                                        @elseif ($other_setting->key == 'header_text_2')
                                            {{ __('HEADER 2: Sub Title') }}
                                        @endif
                                    </td>
                                    <td>
                                        <a onclick="editOtherSetting({{ $other_setting->id }},'{{ $other_setting->key }}')" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

@endsection

{{-- Custom JS --}}
@section('page-js')

    <script type="text/javascript">

        var deliveryMessageEditor;

        // Select2 for Primary Language
        var primarySelect = $('#primary_language').select2();

        // Function for Set User's Primary Language
        function setPrimaryLanguage(shopID)
        {
            var languageID = $('#primary_language :selected').val();

            $.ajax({
                type: "POST",
                url: '{{ route("language.set-primary") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'language_id': languageID,
                    'shop_id': shopID,
                },
                dataType: 'JSON',
                success: function(response)
                {
                    if (response.success == 1)
                    {
                        toastr.success(response.message);
                        setTimeout(() => {
                            location.reload()
                        }, 1200);
                    }
                    else
                    {
                        toastr.error(response.message);
                        setTimeout(() => {
                            location.reload()
                        }, 1200);
                    }
                }
            });

        }

        // Add Additional Language
        $('#additional_languages').select2().on("select2:select", function (event)
        {
            var languageIds = $('#additional_languages').val();
            var shopID = $('#shopID').val();

            $.ajax({
                type: "POST",
                url: '{{ route("language.set-additional") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'language_ids': languageIds,
                    'shop_id': shopID,
                },
                dataType: 'JSON',
                success: function(response)
                {
                    if (response.success == 1)
                    {
                        toastr.success(response.message);
                        $('#langBox').html('');
                        $('.cate_lang_box').html('');
                        $('.item_lang_box').html('');
                        $('#langBox').append(response.data);
                    }
                    else
                    {
                        toastr.error(response.message);
                        setTimeout(() => {
                            location.reload()
                        }, 1200);
                    }
                }
            });

        });


        // Remove Additional Language
        $('#additional_languages').select2().on("select2:unselect", function (event)
        {
            var languageID = event.params.data.id;
            var shopID = $('#shopID').val();

            $.ajax({
                type: "POST",
                url: '{{ route("language.delete-additional") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'language_id': languageID,
                    'shop_id': shopID,
                },
                dataType: 'JSON',
                success: function(response)
                {
                    if (response.success == 1)
                    {
                        toastr.success(response.message);
                        $('.language_'+languageID).remove();
                        $('.cate_lang_box').html('');
                        $('.item_lang_box').html('');
                    }
                    else
                    {
                        toastr.error(response.message);
                        setTimeout(() => {
                            location.reload()
                        }, 1200);
                    }
                }
            });
        });


        // Function for Change Status of Additional Language.
        function changeLanguageStatus(id)
        {
            var isChecked = $('#publish_'+id).is(":checked");
            isChecked = (isChecked == true) ? 1 : 0;

            toastr.clear();

            $.ajax({
                type: "POST",
                url: '{{ route("language.changeStatus") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'id': id,
                    'isChecked': isChecked,
                },
                dataType: 'JSON',
                success: function(response)
                {
                    if (response.success == 1)
                    {
                        toastr.success(response.message);
                    }
                    else
                    {
                        toastr.error(response.message);
                        setTimeout(() => {
                            location.reload()
                        }, 1200);
                    }
                }
            });

        }


        // Category Menu Accordian
        document.addEventListener("DOMContentLoaded", function()
        {
            document.querySelectorAll('.lang_sidebar .nav-link').forEach(function(element)
            {
                element.addEventListener('click', function (e)
                {
                    let nextEl = element.nextElementSibling;
                    let parentEl  = element.parentElement;

                    if(nextEl)
                    {
                        e.preventDefault();
                        let mycollapse = new bootstrap.Collapse(nextEl);

                        if(nextEl.classList.contains('show'))
                        {
                            mycollapse.hide();
                        }
                        else
                        {
                            mycollapse.show();
                            // find other submenus with class=show
                            var opened_submenu = parentEl.parentElement.querySelector('.submenu.show');
                            // if it exists, then close all of them
                            if(opened_submenu)
                            {
                                new bootstrap.Collapse(opened_submenu);
                            }
                        }
                    }

                });
            });
        });


        // Get Language wise Category Details
        function getCatDetails(id)
        {
            toastr.clear();

            $.ajax({
                type: "POST",
                url: '{{ route("language.categorydetails") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'id': id,
                },
                dataType: 'JSON',
                success: function(response)
                {
                    if (response.success == 1)
                    {
                        toastr.success(response.message);
                        $('.cate_lang_box').html('');
                        $('.item_lang_box').html('');
                        $('.cate_lang_box').append(response.data);
                    }
                    else
                    {
                        toastr.error(response.message);
                        setTimeout(() => {
                            location.reload()
                        }, 1200);
                    }
                }
            });
        }


        // Update Language Wise Category Details
        function updateCategoryDetail(langCode)
        {
            var formID = langCode+"_cat_form";
            var myFormData = new FormData(document.getElementById(formID));

            // Remove Validation Class
            $(formID+' #category_name').removeClass('is-invalid');
            $(formID+' #category_desc').removeClass('is-invalid');
            toastr.clear();

            $.ajax({
                type: "POST",
                url: "{{ route('language.update-category-details') }}",
                data: myFormData,
                dataType: "JSON",
                contentType: false,
                cache: false,
                processData: false,
                success: function (response)
                {
                    if(response.success == 1)
                    {
                        toastr.success(response.message);
                    }
                    else
                    {
                        toastr.error(response.message);
                        location.reload();
                    }
                },
                error: function(response)
                {
                    // All Validation Errors
                    const validationErrors = (response?.responseJSON?.errors) ? response.responseJSON.errors : '';

                    if (validationErrors != '')
                    {
                        // Name Error
                        var nameError = (validationErrors.category_name) ? validationErrors.category_name : '';
                        if (nameError != '')
                        {
                            $(formID+' #category_name').addClass('is-invalid');
                            toastr.error(nameError);
                        }

                        // Description Error
                        var descriptionError = (validationErrors.category_desc) ? validationErrors.category_desc : '';
                        if (descriptionError != '')
                        {
                            $(formID+' #category_desc').addClass('is-invalid');
                            toastr.error(descriptionError);
                        }
                    }
                }
            });

        }


        // Get Language wise Product Details
        function getItemDetails(id)
        {
            toastr.clear();

            $.ajax({
                type: "POST",
                url: '{{ route("language.itemdetails") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'id': id,
                },
                dataType: 'JSON',
                success: function(response)
                {
                    if (response.success == 1)
                    {
                        toastr.success(response.message);
                        $('.cate_lang_box').html('');
                        $('.item_lang_box').html('');
                        $('.item_lang_box').append(response.data);
                    }
                    else
                    {
                        toastr.error(response.message);
                        setTimeout(() => {
                            location.reload()
                        }, 1200);
                    }
                }
            });
        }


        // Update Language Wise Item Details
        function updateItemDetail(langCode)
        {
            var formID = langCode+"_item_form";
            var myFormData = new FormData(document.getElementById(formID));

            // Remove Validation Class
            $(formID+' #item_name').removeClass('is-invalid');
            $(formID+' #item_desc').removeClass('is-invalid');
            toastr.clear();

            $.ajax({
                type: "POST",
                url: "{{ route('language.update-item-details') }}",
                data: myFormData,
                dataType: "JSON",
                contentType: false,
                cache: false,
                processData: false,
                success: function (response)
                {
                    if(response.success == 1)
                    {
                        toastr.success(response.message);
                    }
                    else
                    {
                        toastr.error(response.message);
                        location.reload();
                    }
                },
                error: function(response)
                {
                    // All Validation Errors
                    const validationErrors = (response?.responseJSON?.errors) ? response.responseJSON.errors : '';

                    if (validationErrors != '')
                    {
                        // Name Error
                        var nameError = (validationErrors.item_name) ? validationErrors.item_name : '';
                        if (nameError != '')
                        {
                            $(formID+' #item_name').addClass('is-invalid');
                            toastr.error(nameError);
                        }

                        // Description Error
                        var descriptionError = (validationErrors.item_desc) ? validationErrors.item_desc : '';
                        if (descriptionError != '')
                        {
                            $(formID+' #item_desc').addClass('is-invalid');
                            toastr.error(descriptionError);
                        }
                    }
                }
            });

        }


        // Function for On/Off Google Translate Functionality
        $('#google_translate').on('change',function(){
            var isChecked = $(this).prop('checked');
            if(isChecked == true)
            {
                isChecked = 1;
            }
            else
            {
                isChecked = 0;
            }

            $.ajax({
                type: "POST",
                url: "{{ route('language.google.translate') }}",
                data: {
                    "_token" : "{{ csrf_token() }}",
                    'status' : isChecked,
                },
                dataType: "JSON",
                success: function (response)
                {
                    if(response.success == 1)
                    {
                        toastr.success(response.message);
                    }
                    else
                    {
                        toastr.error(response.message);
                    }
                }
            });

        });


        // Function for Edit Other Settings
        function editOtherSetting(settingID,settingKey)
        {
            // Clear Modal
            $('#editOtherSettingsModal #other_setting_lang_div').html('');

            // Clear all Toastr Messages
            toastr.clear();

            $('.ck-editor').remove();
            deliveryMessageEditor = "";

            $.ajax({
                type: "POST",
                url: "{{ route('other.settings.edit') }}",
                dataType: "JSON",
                data: {
                    '_token': "{{ csrf_token() }}",
                    'id': settingID,
                    'setting_key': settingKey,
                },
                success: function(response)
                {
                    if (response.success == 1)
                    {
                        $('#editOtherSettingsModal #other_setting_lang_div').html('');
                        $('#editOtherSettingsModal #other_setting_lang_div').append(response.data);

                        if(settingKey == 'delivery_message' || settingKey == 'homepage_intro' || settingKey == 'service_closed_message' || settingKey == 'header_text_2')
                        {
                            // Description Text Editor
                            $('.ck-editor').remove();
                            deliveryMessageEditor = "";
                            var message_textarea = $('#'+settingKey)[0];
                            CKEDITOR.ClassicEditor.create(message_textarea,
                            {
                                toolbar: {
                                    items: [
                                        'heading', '|',
                                        'bold', 'italic', 'strikethrough', 'underline', 'code', 'subscript', 'superscript', 'removeFormat', '|',
                                        'bulletedList', 'numberedList', 'todoList', '|',
                                        'outdent', 'indent', '|',
                                        'undo', 'redo',
                                        '-',
                                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                                        'alignment', '|',
                                        'link', 'insertImage', 'blockQuote', 'insertTable', 'mediaEmbed', 'codeBlock', 'htmlEmbed', '|',
                                        'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                                        'sourceEditing'
                                    ],
                                    shouldNotGroupWhenFull: true
                                },
                                list: {
                                    properties: {
                                        styles: true,
                                        startIndex: true,
                                        reversed: true
                                    }
                                },
                                'height':500,
                                fontSize: {
                                    options: [ 10, 12, 14, 'default', 18, 20, 22 ],
                                    supportAllValues: true
                                },
                                htmlSupport: {
                                    allow: [
                                        {
                                            name: /.*/,
                                            attributes: true,
                                            classes: true,
                                            styles: true
                                        }
                                    ]
                                },
                                htmlEmbed: {
                                    showPreviews: true
                                },
                                link: {
                                    decorators: {
                                        addTargetToExternalLinks: true,
                                        defaultProtocol: 'https://',
                                        toggleDownloadable: {
                                            mode: 'manual',
                                            label: 'Downloadable',
                                            attributes: {
                                                download: 'file'
                                            }
                                        }
                                    }
                                },
                                mention: {
                                    feeds: [
                                        {
                                            marker: '@',
                                            feed: [
                                                '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                                                '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                                                '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                                                '@sugar', '@sweet', '@topping', '@wafer'
                                            ],
                                            minimumCharacters: 1
                                        }
                                    ]
                                },
                                removePlugins: [
                                    'CKBox',
                                    'CKFinder',
                                    'EasyImage',
                                    'RealTimeCollaborativeComments',
                                    'RealTimeCollaborativeTrackChanges',
                                    'RealTimeCollaborativeRevisionHistory',
                                    'PresenceList',
                                    'Comments',
                                    'TrackChanges',
                                    'TrackChangesData',
                                    'RevisionHistory',
                                    'Pagination',
                                    'WProofreader',
                                    'MathType'
                                ]
                            }).then( editor => {
                                deliveryMessageEditor = editor;
                            });
                        }

                        $('#editOtherSettingsModal').modal('show');
                    }
                    else
                    {
                        toastr.error(response.message);
                    }
                }
            });
        }


        // Update Other Settings By Language Code
        function updateByCode(next_lang_code)
        {
            var formID = "otherSettingsForm";
            var setting_key = $('#otherSettingsForm #setting_key').val();
            var myFormData = new FormData(document.getElementById(formID));
            if(setting_key == 'delivery_message'){
                myFormData.set('delivery_message',deliveryMessageEditor.getData());
            }else if(setting_key == 'homepage_intro'){
                myFormData.set('homepage_intro',deliveryMessageEditor.getData());
            }else if(setting_key == 'service_closed_message'){
                myFormData.set('service_closed_message',deliveryMessageEditor.getData());
            }else if(setting_key == 'header_text_2'){
                myFormData.set('header_text_2',deliveryMessageEditor.getData());
            }
            myFormData.append('next_lang_code',next_lang_code);

            // Clear all Toastr Messages
            toastr.clear();

            $.ajax({
                type: "POST",
                url: "{{ route('other.settings.update.by.lang') }}",
                data: myFormData,
                dataType: "JSON",
                contentType: false,
                cache: false,
                processData: false,
                success: function (response)
                {
                    if(response.success == 1)
                    {
                        $('#editOtherSettingsModal #other_setting_lang_div').html('');
                        $('#editOtherSettingsModal #other_setting_lang_div').append(response.data);

                        if(setting_key == 'delivery_message' || setting_key == 'homepage_intro' || setting_key == 'service_closed_message' || setting_key == 'header_text_2')
                        {
                            // Description Text Editor
                            $('.ck-editor').remove();
                            deliveryMessageEditor = "";
                            var message_textarea = $('#'+setting_key)[0];
                            CKEDITOR.ClassicEditor.create(message_textarea,
                            {
                                toolbar: {
                                    items: [
                                        'heading', '|',
                                        'bold', 'italic', 'strikethrough', 'underline', 'code', 'subscript', 'superscript', 'removeFormat', '|',
                                        'bulletedList', 'numberedList', 'todoList', '|',
                                        'outdent', 'indent', '|',
                                        'undo', 'redo',
                                        '-',
                                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                                        'alignment', '|',
                                        'link', 'insertImage', 'blockQuote', 'insertTable', 'mediaEmbed', 'codeBlock', 'htmlEmbed', '|',
                                        'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                                        'sourceEditing'
                                    ],
                                    shouldNotGroupWhenFull: true
                                },
                                list: {
                                    properties: {
                                        styles: true,
                                        startIndex: true,
                                        reversed: true
                                    }
                                },
                                'height':500,
                                fontSize: {
                                    options: [ 10, 12, 14, 'default', 18, 20, 22 ],
                                    supportAllValues: true
                                },
                                htmlSupport: {
                                    allow: [
                                        {
                                            name: /.*/,
                                            attributes: true,
                                            classes: true,
                                            styles: true
                                        }
                                    ]
                                },
                                htmlEmbed: {
                                    showPreviews: true
                                },
                                link: {
                                    decorators: {
                                        addTargetToExternalLinks: true,
                                        defaultProtocol: 'https://',
                                        toggleDownloadable: {
                                            mode: 'manual',
                                            label: 'Downloadable',
                                            attributes: {
                                                download: 'file'
                                            }
                                        }
                                    }
                                },
                                mention: {
                                    feeds: [
                                        {
                                            marker: '@',
                                            feed: [
                                                '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                                                '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                                                '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                                                '@sugar', '@sweet', '@topping', '@wafer'
                                            ],
                                            minimumCharacters: 1
                                        }
                                    ]
                                },
                                removePlugins: [
                                    'CKBox',
                                    'CKFinder',
                                    'EasyImage',
                                    'RealTimeCollaborativeComments',
                                    'RealTimeCollaborativeTrackChanges',
                                    'RealTimeCollaborativeRevisionHistory',
                                    'PresenceList',
                                    'Comments',
                                    'TrackChanges',
                                    'TrackChangesData',
                                    'RevisionHistory',
                                    'Pagination',
                                    'WProofreader',
                                    'MathType'
                                ]
                            }).then( editor => {
                                deliveryMessageEditor = editor;
                            });
                        }
                    }
                    else
                    {
                        $('#editOtherSettingsModal').modal('hide');
                        $('#editOtherSettingsModal #other_setting_lang_div').html('');
                        toastr.error(response.message);
                    }
                },
                error: function(response)
                {
                    if(response.responseJSON.errors)
                    {
                        $.each(response.responseJSON.errors, function (i, error) {
                            toastr.error(error);
                        });
                    }
                }
            });
        }


        // Function for Update Other Settings
        function updateOtherSetting()
        {
            var formID = "otherSettingsForm";
            var setting_key = $('#otherSettingsForm #setting_key').val();
            var myFormData = new FormData(document.getElementById(formID));
            if(setting_key == 'delivery_message')
            {
                myFormData.set('delivery_message',deliveryMessageEditor.getData());
            }else if(setting_key == 'homepage_intro'){
                myFormData.set('homepage_intro',deliveryMessageEditor.getData());
            }else if(setting_key == 'service_closed_message'){
                myFormData.set('service_closed_message',deliveryMessageEditor.getData());
            }else if(setting_key == 'header_text_2'){
                myFormData.set('header_text_2',deliveryMessageEditor.getData());

            }

            // Clear all Toastr Messages
            toastr.clear();

            $.ajax({
                type: "POST",
                url: "{{ route('other.settings.update') }}",
                data: myFormData,
                dataType: "JSON",
                contentType: false,
                cache: false,
                processData: false,
                success: function (response)
                {
                    if(response.success == 1)
                    {
                        // $('#editOtherSettingsModal').modal('hide');
                        toastr.success(response.message);
                    }
                    else
                    {
                        $('#editOtherSettingsModal').modal('hide');
                        toastr.error(response.message);
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function(response)
                {
                    if(response.responseJSON.errors)
                    {
                        $.each(response.responseJSON.errors, function (i, error) {
                            toastr.error(error);
                        });
                    }
                }
            });

        }

    </script>

@endsection

