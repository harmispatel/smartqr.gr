@php


// Shop ID & Slug
$shop_id = isset(Auth::user()->hasOneShop->shop['id']) ? Auth::user()->hasOneShop->shop['id'] : "";
$shop_slug = isset(Auth::user()->hasOneShop->shop['shop_slug']) ? Auth::user()->hasOneShop->shop['shop_slug'] : '';

// Primary Language Details
$primary_lang_details = clientLanguageSettings($shop_id);
$language = getLangDetails(isset($primary_lang_details['primary_language']) ? $primary_lang_details['primary_language'] : '');
$language_code = isset($language['code']) ? $language['code'] : '';

// Name Language Key
$name_key = $language_code."_name";

// Subscrption ID
$subscription_id = Auth::user()->hasOneSubscription['subscription_id'];

// Get Package Permissions
$package_permissions = getPackagePermission($subscription_id);

// Shop Currency
$currency = isset($shop_settings['default_currency']) && !empty($shop_settings['default_currency']) ? $shop_settings['default_currency'] : 'EUR';

// Language Details
$language_details = getLangDetailsbyCode($language_code);

// Column Keys
$price_label_key = $language_code . '_label';
@endphp

@extends('client.layouts.client-layout')

@section('title', __('Items'))

@section('content')

{{-- Add Modal --}}
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">{{ __('Create New Item')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm" enctype="multipart/form-data">
                    @csrf

                    @if((isset($package_permissions['ordering']) && !empty($package_permissions['ordering']) && $package_permissions['ordering'] == 1))
                    <div class="row mb-3">
                        <div class="col-md-12 text-end delivery">
                            <div class="form-group">
                                <label class="switch me-2">
                                    <input type="checkbox" id="delivery" name="delivery" value="1" checked>
                                    <span class="slider round">
                                        <i class="fa-solid fa-circle-check check_icon"></i>
                                        <i class="fa-sharp fa-solid fa-circle-xmark uncheck_icon"></i>
                                    </span>
                                </label>
                                <label for="delivery" class="form-label">{{ __('Ordering')}}</label>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Product Type --}}
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="type" class="form-label">{{ __('Type')}}</label>
                            <select name="type" id="type" onchange="togglePrice('addItemModal')" class="form-control">
                                <option value="1">{{ __('Product')}}</option>
                                <option value="2">{{ __('Divider')}}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="category" class="form-label">{{ __('Category')}}</label>
                            <select name="categories[]" id="categories" class="form-control" multiple>
                                <option value="">Choose Category</option>
                                @if(count($categories) > 0)
                                @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($cat_id == $cat->id) ? 'selected' : '' }}>{{ $cat->en_name }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="name" class="form-label">{{ __('Name')}}</label>
                            <input type="text" name="name" class="form-control" id="name" placeholder="Item Name">
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="row price_div">
                        <div class="col-md-12 priceDiv" id="priceDiv">
                            <label for="price" class="form-label">{{ __('Price')}}</label>
                            <div class="row mb-3 align-items-center price price_1">
                                <div class="col-md-5 mb-1">
                                    <input type="text" name="price[price][]" class="form-control" placeholder="Enter Price">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <input type="text" name="price[label][]" class="form-control" placeholder="Enter Price Label">
                                </div>
                                <div class="col-md-1 mb-1">
                                    <a onclick="$('.price_1').remove()" class="btn btn-sm btn-danger">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4 priceDiv price_div justify-content-end">
                        <div class="col-md-3">
                            <a onclick="addPrice('addItemModal')" class="btn addPriceBtn btn-info text-white">{{ __('Add Price')}}</a>
                        </div>
                    </div>

                    {{-- More Details --}}
                    <div class="row mb-3">
                        <div class="col-md-12 text-center">
                            <a class="btn btn-sm btn-primary" style="cursor: pointer" onclick="toggleMoreDetails('addItemModal')" id="more_dt_btn">More Details.. <i class="bi bi-eye-slash"></i></a>
                        </div>
                    </div>
                    <div id="more_details" style="display: none;">

                        {{-- Discount Type --}}
                        <div class="row mb-3 discount-div">
                            <div class="col-md-12">
                                <label for="description" class="form-label">{{ __('Discount Type')}}</label>
                                <select name="discount_type" id="discount_type" class="form-select">
                                    <option value="percentage">{{ __('Percentage %') }}</option>
                                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Discount Value --}}
                        <div class="row mb-3 discount-div">
                            <div class="col-md-12">
                                <label for="discount" class="form-label">{{ __('Discount') }}</label>
                                <input type="number" name="discount" id="discount" value="0" class="form-control">
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="description" class="form-label">{{ __('Description')}}</label>
                                <textarea class="form-control" name="description" id="description" rows="5" placeholder="Item Description"></textarea>
                            </div>
                        </div>

                        {{-- Crop Size --}}
                        <div class="col-md-12 crop_size" style="display: none;">
                            <label for="crop_size" class="form-label">{{ __('Image Size')}}</label>
                            <select name="crop_size" id="crop_size" class="form-select">
                                <option value="400">400*400</option>
                                <option value="700">700*400</option>
                            </select>
                        </div>

                        {{-- Image --}}
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="image" class="form-label">{{ __('Small Image')}}</label>
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div id="img-label">
                                                <label for="image" style="cursor: pointer">
                                                    <img src="{{ asset('public/client_images/not-found/no_image_1.jpg') }}" class="w-100 h-100" id="crp-img-prw" style="border-radius: 10px;">
                                                </label>
                                            </div>
                                            <input type="file" name="image" id="image" class="form-control" style="display: none;">
                                            <input type="hidden" name="og_image" id="og_image">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <code>Upload Image in (400*400) Dimensions</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8 img-crop-sec mb-2" style="display: none">
                                <img src="" alt="" id="resize-image" class="w-100">
                                <div class="mt-3">
                                    <a class="btn btn-sm btn-success" onclick="saveCropper('addItemForm')">Save</a>
                                    <a class="btn btn-sm btn-danger" onclick="resetCropper()">Reset</a>
                                    <a class="btn btn-sm btn-secondary" onclick="cancelCropper('addItemForm')">Cancel</a>
                                </div>
                            </div>
                            <div class="col-md-4 img-crop-sec" style="display: none;">
                                <div class="preview" style="width: 200px; height:200px; overflow: hidden;margin: 0 auto;"></div>
                            </div>
                        </div>

                        <!-- Image Detail -->
                        <div class="row mb-3 image-detail" style="display:none;">
                            <div class="col-md-12">
                                <label for="image_detail" class="form-label">{{ __('Large Image')}}</label>
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div id="img-detail-label">
                                                <label for="image_detail" style="cursor: pointer">
                                                    <img src="{{ asset('public/client_images/not-found/no_image_1.jpg') }}" class="w-100 h-100" id="crp-img-detail-prw" style="border-radius: 10px;">
                                                </label>
                                            </div>
                                            <input type="file" name="image_detail" id="image_detail" class="form-control" style="display: none;">
                                            <input type="hidden" name="og_image_detail" id="og_image_detail">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <code>Upload Image in (700*400) Dimensions</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8 img-detail-crop-sec mb-2" style="display: none">
                                <img src="" alt="" id="resize-image-detail" class="w-100">
                                <div class="mt-3">
                                    <a class="btn btn-sm btn-success" onclick="saveDetailCropper('addItemForm')">Save</a>
                                    <a class="btn btn-sm btn-danger" onclick="resetDetailCropper()">Reset</a>
                                    <a class="btn btn-sm btn-secondary" onclick="cancelDetailCropper('addItemForm')">Cancel</a>
                                </div>
                            </div>
                            <div class="col-md-4 img-detail-crop-sec" style="display: none;">
                                <div class="preview_detail" style="width: 200px; height:200px; overflow: hidden;margin: 0 auto;"></div>
                            </div>
                        </div>

                        {{-- Indicative Icons --}}
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="ingredients" class="form-label">{{ __('Indicative Icons')}}</label>
                                <select name="ingredients[]" id="ingredients" class="form-control" multiple>
                                    @if(count($ingredients) > 0)
                                    @foreach ($ingredients as $ingredient)
                                    @php
                                    $parent_id = (isset($ingredient->parent_id)) ? $ingredient->parent_id : NULL;
                                    @endphp

                                    @if((isset($package_permissions['special_icons']) && !empty($package_permissions['special_icons']) && $package_permissions['special_icons'] == 1) || $parent_id != NULL)
                                    <option value="{{ $ingredient->id }}">{{ $ingredient->name }}</option>
                                    @endif
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        {{-- Recomendation Item --}}
                        <div class="row mb-3 recomendation_items_div">
                            <div class="col-md-12">
                                <label for="recomendation_items" class="form-label">{{ __('Recomendation Items')}}</label>
                                <select name="recomendation_items[]" id="recomendation_items" class="form-control" multiple>
                                    @if(count($recomendation_items) > 0)
                                    @foreach ($recomendation_items as $recomendation_item)
                                    <option value="{{ (isset($recomendation_item->id)) ? $recomendation_item->id : '' }}">{{ (isset($recomendation_item[$name_key])) ? $recomendation_item[$name_key] : '' }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        {{-- Tags --}}
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="tags" class="form-label">{{ __('Tags')}}</label>
                                <select name="tags[]" id="tags" class="form-control" multiple>
                                    @if(count($tags) > 0)
                                    @foreach ($tags as $tag)
                                    <option value="{{ (isset($tag[$name_key])) ? $tag[$name_key] : '' }}">{{ (isset($tag[$name_key])) ? $tag[$name_key] : '' }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        {{-- Calories --}}
                        <div class="row mb-3 calories_div">
                            <div class="col-md-12">
                                <label for="calories" class="form-label">{{ __('Calories')}}</label>
                                <input type="text" name="calories" class="form-control" id="calories" placeholder="Enter Calories">
                            </div>
                        </div>

                        {{-- Attributes --}}
                        @if((isset($package_permissions['ordering']) && !empty($package_permissions['ordering']) && $package_permissions['ordering'] == 1))
                        <div class="row mb-3 attributes-div">
                            <div class="col-md-12">
                                <label for="options" class="form-label">{{ __('Attributes')}}</label>
                                <select name="options[]" id="options" class="form-control" multiple>
                                    @if(count($options) > 0)
                                    @foreach ($options as $option)
                                    <option value="{{ $option->id }}">{{ $option->title }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        @endif

                        {{-- Status Buttons --}}
                        <div class="row mb-3">
                            <div class="col-md-6 mark_new">
                                <div class="form-group">
                                    <label class="switch me-2">
                                        <input type="checkbox" id="mark_new" name="is_new" value="1">
                                        <span class="slider round">
                                            <i class="fa-solid fa-circle-check check_icon"></i>
                                            <i class="fa-sharp fa-solid fa-circle-xmark uncheck_icon"></i>
                                        </span>
                                    </label>
                                    <label for="mark_new" class="form-label">{{ __('New')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6 mark_sign">
                                <div class="form-group">
                                    <label class="switch me-2">
                                        <input type="checkbox" id="mark_sign" name="is_sign" value="1">
                                        <span class="slider round">
                                            <i class="fa-solid fa-circle-check check_icon"></i>
                                            <i class="fa-sharp fa-solid fa-circle-xmark uncheck_icon"></i>
                                        </span>
                                    </label>
                                    <label for="mark_sign" class="form-label">{{ __('Recommended')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2 day_special">
                                <div class="form-group">
                                    <label class="switch me-2">
                                        <input type="checkbox" id="day_special" name="day_special" value="1">
                                        <span class="slider round">
                                            <i class="fa-solid fa-circle-check check_icon"></i>
                                            <i class="fa-sharp fa-solid fa-circle-xmark uncheck_icon"></i>
                                        </span>
                                    </label>
                                    <label for="day_special" class="form-label">{{ __('Day Special')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2 review_rating">
                                <div class="form-group">
                                    <label class="switch me-2">
                                        <input type="checkbox" id="review_rating" name="review_rating" value="1">
                                        <span class="slider round">
                                            <i class="fa-solid fa-circle-check check_icon"></i>
                                            <i class="fa-sharp fa-solid fa-circle-xmark uncheck_icon"></i>
                                        </span>
                                    </label>
                                    <label for="review_rating" class="form-label">{{ __('Review & Rating')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="form-group">
                                    <label class="switch me-2">
                                        <input type="checkbox" id="publish" name="published" value="1" checked>
                                        <span class="slider round">
                                            <i class="fa-solid fa-circle-check check_icon"></i>
                                            <i class="fa-sharp fa-solid fa-circle-xmark uncheck_icon"></i>
                                        </span>
                                    </label>
                                    <label for="publish" class="form-label">{{ __('Published')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn close-btn btn-secondary" data-bs-dismiss="modal">{{ __('Close')}}</button>
                <a class="btn btn-primary" id="saveItem" onclick="saveItem()">{{ __('Save')}}</a>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editItemModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">{{ __('Edit Item')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="item_lang_div">
            </div>
            <div class="modal-footer">
                <a class="btn btn-sm btn-success" onclick="updateItem()">{{ __('Update') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- EditTag Modal --}}
<div class="modal fade" id="editTagModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTagModalLabel">{{ __('Edit Tag')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="tag_edit_div">
            </div>
            <div class="modal-footer">
                <a class="btn btn-sm btn-success" onclick="updateTag()">{{ __('Update') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- Cat ID --}}
<input type="hidden" name="cat_id" id="cat_id" value="{{ $cat_id }}">

{{-- Shop ID --}}
<input type="hidden" name="shop_id" id="shop_id" value="{{ $shop_id }}">

{{-- Page Title --}}
<div class="pagetitle">
    <h1>{{ __('Items')}}</h1>
    <div class="row">
        <div class="col-md-8">
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('categories') }}">{{ __('Categories')}}</a></li>
                    <li class="breadcrumb-item active">{{ (isset($category->en_name) && !empty($category->en_name)) ? $category->en_name : 'All' }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

{{-- Items Section --}}
<section class="section dashboard">
    <div class="row">
        {{-- Error Message Section --}}
        @if (session()->has('error'))
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @endif

        {{-- Success Message Section --}}
        @if (session()->has('success'))
        <div class="col-md-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @endif

        <div class="main_section">
            <div class="container-fluid">
                <div class="main_section_inr">
                    <div class="sec_title">
                        <h3>{{ __('Tags')}}</h3>
                    </div>
                    <div class="row mb-4 connectedSortableTags" id="tagsSorting">
                        {{-- Tags Section --}}
                        @if(count($cat_tags) > 0)
                        @foreach ($cat_tags as $tag)
                        <div class="col-sm-2" tag-id="{{ $tag->hasOneTag['id'] }}">
                            <div class="product-tags">
                                {{ isset($tag->hasOneTag[$name_key]) ? $tag->hasOneTag[$name_key] : '' }}
                                <i class="fa fa-edit" onclick="editTag({{ $tag->hasOneTag['id'] }})" style="cursor: pointer"></i>
                                <i class="fa fa-trash" onclick="deleteTag({{ $tag->hasOneTag['id'] }})" style="cursor: pointer"></i>
                            </div>
                        </div>
                        @endforeach
                        @endif
                    </div>
                    <div class="sec_title">
                        <h3>{{ __('Items')}}</h3>
                        <div class="row justify-content-end mt mt-3">
                            <div class="col-md-3">
                                <select name="grid_name" id="view_select" class="form-control">
                                    <option>{{ __('Select View')}}</option>
                                    <option value="grid" {{ ($listName == 'grid') ? 'selected' : '' }}>{{ __('Grid View')}}</option>
                                    <option value="list" {{ ($listName == 'list') ? 'selected' : '' }}>{{ __('List View')}}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="grid_name" id="bulk_select" class="form-control" disabled>
                                    <option>{{ __('Bulk Action')}}</option>
                                    <option value="active">{{ __('Activate Order for Items')}}</option>
                                    <option value="deactive">{{ __('Deactivate Order for Items')}}</option>
                                    <option value="status_active">{{ __('Activate Selected Items')}}</option>
                                    <option value="status_deactive">{{ __('Deactivate Selected Items')}}</option>
                                    <option value="duplicate">{{ __('Duplicate Selected Items')}}</option>
                                    <option value="delete">{{ __('Delete Selected Items')}}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="cat_filter" id="cat_filter" class="form-control">
                                    <option value="">{{ __('Filter By Category')}}</option>
                                    @if(count($categories) > 0)
                                    @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ ($cat_id == $category->id) ? 'selected' : '' }}>{{ (isset($category->$name_key)) ? $category->$name_key : '' }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="search_box m-0">
                                    <div class="form-group position-relative">
                                        <input type="text" id="search" class="form-control" placeholder="{{ __('Search')}}">
                                        <i class="fa-solid fa-magnifying-glass search_icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($listName == 'list')
                    <div class="item_table_list">
                        <div class="add_item_table_list text-end mb-3 px-3">
                            <a data-bs-toggle="modal" data-bs-target="#addItemModal" id="NewItemBtn" class="btn btn-sm new-amenity btn-primary">
                                <i class="bi bi-plus-lg"></i>
                            </a>
                        </div>

                        <div class="connectedSortableItemsTable" id="ItemTableSection">
                            <table class="table table-striped" id="clientsTable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select_all"></th>
                                        <th>{{ __('Image')}}</th>
                                        <th>{{ __('Name')}}</th>
                                        <th>{{ __('Price')}}</th>
                                        <th class="text-center">{{ __('Tag')}}</th>
                                        <th>{{ __('Type')}}</th>
                                        <th>{{ __('Order')}}</th>
                                        <th>{{ __('Status')}}</th>
                                        <th class="text-center">{{ __('Actions')}}</th>
                                    </tr>
                                </thead>
                                <tbody id="TableBodySection">
                                    @if(count($items) > 0)
                                    @foreach ($items as $item)
                                    <tr item-id="{{ $item->id }}">
                                        <!-- checkbox -->
                                        <td>
                                            <div class="form-check p-0">
                                                <input type="checkbox" data-id="{{$item->id}}" class="item_checkbox">
                                            </div>
                                        </td>

                                        <!-- image -->
                                        <td>
                                            @if(!empty($item->image) && file_exists('public/client_uploads/shops/'.$shop_slug.'/items/'.$item->image))
                                            <img src="{{ asset('public/client_uploads/shops/'.$shop_slug.'/items/'.$item->image) }}" width="70">
                                            @else
                                            <img src="{{ asset('public/client_images/not-found/no_image_1.jpg') }}" width="70">
                                            @endif
                                        </td>

                                        <!-- name -->
                                        <td class="item_tab_name">
                                            {{ isset($item[$name_key]) ? $item[$name_key] : '' }}
                                        </td>

                                        <!-- price -->
                                        <td>
                                            @php
                                                $price_arr = getItemPrice($item['id']);
                                                $item_discount = isset($item['discount']) ? $item['discount'] : 0;
                                                $item_discount_type = isset($item['discount_type']) ? $item['discount_type'] : 'percentage';
                                            @endphp
                                            @if (count($price_arr) > 0)
                                                <ul class="price_ul" style="list-style-type: none; padding: 0; margin: 0;">
                                                    @foreach ($price_arr as $key => $value)
                                                        @php
                                                            $price = Currency::currency($currency)->format($value['price']);
                                                            $price_label = isset($value[$price_label_key]) ? $value[$price_label_key] : '';
                                                        @endphp
                                                        <li>
                                                            @if ($item_discount > 0)
                                                                @php
                                                                    if ($item_discount_type == 'fixed') {
                                                                        $new_amount = number_format($value['price'] - $item_discount, 2);
                                                                    } else {
                                                                        $per_value = ($value['price'] * $item_discount) / 100;
                                                                        $new_amount = number_format($value['price'] - $per_value, 2);
                                                                    }
                                                                @endphp
                                                                <p>
                                                                    {{ $price_label }} <span
                                                                        class="text-decoration-line-through">{{ $price }}</span>
                                                                    <span>{{ Currency::currency($currency)->format($new_amount) }}</span>
                                                                </p>
                                                            @else
                                                                <p>
                                                                    {{ $price_label }}
                                                                    <span>{{ $price }}</span>
                                                                </p>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>

                                        <!-- tag -->
                                        <td class="text-center">
                                          @php
                                          $categoryIds = $item->categories->pluck('id')->toArray();

                                          if ($cat_id){
                                            $item_cat_tags = App\Models\CategoryProductTags::with(['hasOneTag'])->where('item_id',$item['id'])->where('category_id',$cat_id)->get();
                                          }else{
                                            $item_cat_tags = App\Models\CategoryProductTags::with(['hasOneTag'])
                                            ->where('item_id',$item['id'])
                                            ->whereIn('category_id',$categoryIds)
                                            ->get();
                                          }
                                          $tagNames = [];
                                          @endphp

                                            @if(count($item_cat_tags) > 0)
                                                @foreach ($item_cat_tags as $key => $value)
                                                @php
                                                    // Check if hasOneTag exists and get the tag name
                                                    $tagName = isset($value->hasOneTag) ? ($value->hasOneTag[$language_code.'_name'] ?? '') : '';
                                                    if (!empty($tagName)) {
                                                        $tagNames[] = $tagName;
                                                    }
                                                    @endphp
                                                @endforeach
                                            @endif
                                            @php
                                             $uniqueTagNames = array_unique($tagNames);
                                            @endphp
                                            @if (count($uniqueTagNames) > 0)

                                            <p>{{ implode(', ', $uniqueTagNames) }}</p>

                                            @else
                                                <p>-</p>
                                            @endif
                                        </td>


                                        <!-- type -->
                                        <td class="text-center">
                                            @if($item->type == 1)
                                            <h6>{{ __('Item')}}</h6>
                                            @elseif($item->type == 2)
                                            <h6>{{ __('Divider')}}</h6>
                                            @else
                                            <h6> - </h6>
                                            @endif
                                        </td>

                                        <!-- order -->
                                        <td class="text-center">
                                            @if((isset($package_permissions['ordering']) && !empty($package_permissions['ordering']) && $package_permissions['ordering'] == 1) && $item['type'] == 1)
                                            <div class="form-check form-switch me-2" data-bs-toggle="tooltip" title="Ordering">
                                                @php
                                                $newDelivery = ($item->delivery == 1) ? 0 : 1;
                                                @endphp
                                                <input class="form-check-input" type="checkbox" name="status" role="switch" id="status" onclick="changeItemDelivery({{ $item->id }},{{ $newDelivery }})" value="1" {{ ($item->delivery == 1) ? 'checked' : '' }}>
                                            </div>
                                            @endif
                                        </td>

                                        <!-- status -->
                                        <td>
                                            <div class="form-check form-switch" data-bs-toggle="tooltip" title="Status">
                                                @php
                                                $newStatus = ($item->published == 1) ? 0 : 1;
                                                @endphp
                                                <input class="form-check-input" type="checkbox" name="status" role="switch" id="status" onclick="changeStatus({{ $item->id }},{{ $newStatus }})" value="1" {{ ($item->published == 1) ? 'checked' : '' }}>
                                            </div>
                                        </td>

                                        <!-- action -->
                                        <!-- <td class="text-center ">
                                            <div class="item_table_action">
                                                <a onclick="editItem({{ $item->id }})" class="me-2 btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <div class="item_table_action">
                                                    <a class="item_action_btn" onclick="OpenButtton()">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul id="action_ul">
                                                        <li><a onclick="deleteItem({{ $item->id }})"><i class="fa-regular fa-trash-can"></i>Delete</a></li>
                                                        <li><a><i class="fa-light fa-copy"></i>Doplicate</a></li>
                                                    </ul>
                                                </div>
                                            </div> -->

                                            <td class="text-center">
                                                <div class="item_table_action">
                                                    <a onclick="editItem({{ $item->id }})" class="me-2 btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <div class="item_table_action">
                                                        <a class="item_action_btn" onclick="toggleButton(event, {{ $item->id }})">
                                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul id="action_ul_{{ $item->id }}" class="action_ul">
                                                            <li>
                                                                <a onclick="deleteItem({{ $item->id }})">
                                                                    <i class="fa-regular fa-trash-can me-2"></i>Delete</a>
                                                            </li>
                                                            <li>
                                                                <a>
                                                                <i class="fa-solid fa-copy me-2"></i>Duplicate
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- <a onclick="deleteItem({{ $item->id }})" class=" m-1 btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>-->
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        @endif


                        @if ($listName == 'grid')

                        <div class="row connectedSortableItems" id="ItemSection">
                            {{-- Itens Section --}}
                            @if(count($items) > 0)
                            @foreach ($items as $item)

                            <div class="col-md-3" item-id="{{ $item->id }}">
                                <div class="item_box">
                                    <div class="item_img">
                                        <a>
                                            @if(!empty($item->image) && file_exists('public/client_uploads/shops/'.$shop_slug.'/items/'.$item->image))
                                            <img src="{{ asset('public/client_uploads/shops/'.$shop_slug.'/items/'.$item->image) }}" class="w-100">
                                            @else
                                            <img src="{{ asset('public/client_images/not-found/no_image_1.jpg') }}" class="w-100">
                                            @endif
                                        </a>
                                        <div class="edit_item_bt">
                                            <button class="btn edit_category abc" onclick="editItem({{ $item->id }})">{{ __('EDIT ITEM')}}</button>
                                        </div>
                                        <a class="delet_bt" onclick="deleteItem({{ $item->id }})" style="cursor: pointer;">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                        <a class="cat_edit_bt" onclick="editItem({{ $item->id }})">
                                            <i class="fa-solid fa-edit"></i>
                                        </a>
                                    </div>
                                    <div class="item_info">
                                        <div class="item_name">
                                            <h3 class="text-center">{{ isset($item[$name_key]) ? $item[$name_key] : '' }}</h3>
                                            @if((isset($package_permissions['ordering']) && !empty($package_permissions['ordering']) && $package_permissions['ordering'] == 1) && $item['type'] == 1)
                                            <div class="form-check form-switch me-2" data-bs-toggle="tooltip" title="Ordering">
                                                @php
                                                $newDelivery = ($item->delivery == 1) ? 0 : 1;
                                                @endphp
                                                <input class="form-check-input" type="checkbox" name="status" role="switch" id="status" onclick="changeItemDelivery({{ $item->id }},{{ $newDelivery }})" value="1" {{ ($item->delivery == 1) ? 'checked' : '' }}>
                                            </div>
                                            @endif
                                            <div class="form-check form-switch" data-bs-toggle="tooltip" title="Status">
                                                @php
                                                $newStatus = ($item->published == 1) ? 0 : 1;
                                                @endphp
                                                <input class="form-check-input" type="checkbox" name="status" role="switch" id="status" onclick="changeStatus({{ $item->id }},{{ $newStatus }})" value="1" {{ ($item->published == 1) ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                        @if($item->type == 1)
                                        <h2>{{ __('Item')}}</h2>
                                        @else
                                        <h2>{{ __('Divider')}}</h2>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @endif

                            {{-- Add New Item Section --}}
                            <div class="col-md-3">
                                <div class="item_box">
                                    <div class="item_img add_category">
                                        <a data-bs-toggle="modal" data-bs-target="#addItemModal" class="add_category_bt" id="NewItemBtn">
                                            <i class="fa-solid fa-plus"></i>
                                        </a>
                                    </div>
                                    <div class="item_info text-center">
                                        <h2>{{ __('Add New Item')}}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</section>

@endsection


{{-- Custom JS --}}
@section('page-js')

<script type="text/javascript">
    var cropper;
    var cropperDetail;
    var addItemEditor;
    var editItemEditor;




    // bulk_select
    $(document).ready(function() {

        function updateBulkSelect() {
            const anyChecked = $(".item_checkbox:checked").length > 0;
            $("#bulk_select").prop('disabled', !anyChecked);
        }

            $('#select_all').on('click',function(e){
                    if($(this).is(':checked',true))
                    {
                        $(".item_checkbox").prop('checked', true);
                        $("#bulk_select").prop('disabled', false);
                    } else {
                        $(".item_checkbox").prop('checked',false);
                        $("#bulk_select").prop('disabled', true);
                    }
                    updateBulkSelect();
            });

            $('.item_checkbox').on('change', function() {
                updateBulkSelect();
            });

            $('#bulk_select').on('change', function() {
                const action = $(this).val();
                const selectedItems = $(".item_checkbox:checked").map(function() {
                    return $(this).data('id');
                }).get();
                    if (selectedItems.length > 0) {
                        if (action === 'delete') {
                            handleDelete(selectedItems);
                        }
                        else if (action === 'active')
                        {
                            const status = "1";
                            changeItemDeliveryBulk(selectedItems,status);
                        }
                        else if(action === 'deactive'){
                            const status = "0";
                            changeItemDeliveryBulk(selectedItems,status);
                        }
                        else if(action === 'status_active')
                        {
                            const status = "1";
                            changeStatusBluk(selectedItems,status);
                        }
                        else if(action === 'status_deactive')
                        {
                            const status = "0";
                            changeStatusBluk(selectedItems,status);
                        }
                    }
            });

            function handleDelete(selectedItems) {
                swal({
                    title: "Are you sure You want to Delete It ?",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then((willDeleteItem) => {
                    if (willDeleteItem) {
                        $.ajax({
                            type: "POST",
                            url: '{{ route("items.delete") }}',
                            data: {
                                "_token": "{{ csrf_token() }}",
                                'ids': selectedItems,
                            },
                            dataType: 'JSON',
                            success: function(response) {
                                if (response.success == 1) {
                                    toastr.success(response.message);
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1300);
                                } else {
                                    toastr.error(response.message);
                                }
                            }
                        });
                    } else {
                        swal("Cancelled", "", "error");
                    }
                });
            }

            function changeItemDeliveryBulk(selectedItems,status){
                console.log(selectedItems);
                $.ajax({
                    type: "POST",
                    url: '{{ route("items.delivery.status") }}',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        'delivery': status,
                        'ids': selectedItems
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        if (response.success == 1) {
                            toastr.success(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1300);
                        } else {
                            toastr.error(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1300);
                        }
                    }
                });
            }

            function changeStatusBluk(selectedItems, status) {
                $.ajax({
                    type: "POST",
                    url: '{{ route("items.status") }}',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        'status': status,
                        'ids': selectedItems
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        if (response.success == 1) {
                            toastr.success(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1300);
                        } else {
                            toastr.error(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1300);
                        }
                    }
                });
            }
    });

    function newItemBtn() {
        // Reset addItemForm
        $('#addItemForm').trigger('reset');

        // Remove Validation Class
        $('#addItemForm #name').removeClass('is-invalid');
        $('#addItemForm #category').removeClass('is-invalid');
        $('#addItemForm #image').removeClass('is-invalid');

        // Clear all Toastr Messages
        toastr.clear();

        // Intialized Ingredients SelectBox
        $("#addItemForm #categories").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Choose Categtory",
        });

        // Intialized Ingredients SelectBox
        $("#addItemForm #ingredients").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Indicative Icons",
        });

        // Intialized Recomendation Name SelectBox
        $("#addItemForm #recomendation_items").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Recomendation Items",
        });

        // Intialized Options SelectBox
        $("#addItemForm #options").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Attributes",
        });

        // Intialized Tags SelectBox
        $("#addItemForm #tags").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Tags",
            tags: true,
            // tokenSeparators: [',', ' ']
        });

        $('.ck-editor').remove();
        addItemEditor = "";

        var item_textarea = $('#addItemForm #description')[0];


        // Text Editor
        CKEDITOR.ClassicEditor.create(item_textarea, {
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
            'height': 500,
            fontSize: {
                options: [10, 12, 14, 'default', 18, 20, 22],
                supportAllValues: true
            },
            htmlSupport: {
                allow: [{
                    name: /.*/,
                    attributes: true,
                    classes: true,
                    styles: true
                }]
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
                feeds: [{
                    marker: '@',
                    feed: [
                        '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                        '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                        '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                        '@sugar', '@sweet', '@topping', '@wafer'
                    ],
                    minimumCharacters: 1
                }]
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
        }).then(editor => {
            addItemEditor = editor;
        });

        if (cropper) {
            cancelCropper('addItemForm');
        }
        if (cropperDetail) {
            cancelDetailCropper('addItemForm');
        }
    }

    // Reset AddItem Modal & Form
    $('#NewItemBtn').on('click', function() {

        // Reset addItemForm
        $('#addItemForm').trigger('reset');

        // Remove Validation Class
        $('#addItemForm #name').removeClass('is-invalid');
        $('#addItemForm #category').removeClass('is-invalid');
        $('#addItemForm #image').removeClass('is-invalid');

        // Clear all Toastr Messages
        toastr.clear();

        // Intialized Ingredients SelectBox
        $("#addItemForm #categories").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Choose Categtory",
        });

        // Intialized Ingredients SelectBox
        $("#addItemForm #ingredients").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Indicative Icons",
        });

        // Intialized Recomendation Name SelectBox
        $("#addItemForm #recomendation_items").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Recomendation Items",
        });

        // Intialized Options SelectBox
        $("#addItemForm #options").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Attributes",
        });

        // Intialized Tags SelectBox
        $("#addItemForm #tags").select2({
            dropdownParent: $("#addItemModal"),
            placeholder: "Select Tags",
            tags: true,
            // tokenSeparators: [',', ' ']
        });

        $('.ck-editor').remove();
        addItemEditor = "";

        var item_textarea = $('#addItemForm #description')[0];


        // Text Editor
        CKEDITOR.ClassicEditor.create(item_textarea, {
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
            'height': 500,
            fontSize: {
                options: [10, 12, 14, 'default', 18, 20, 22],
                supportAllValues: true
            },
            htmlSupport: {
                allow: [{
                    name: /.*/,
                    attributes: true,
                    classes: true,
                    styles: true
                }]
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
                feeds: [{
                    marker: '@',
                    feed: [
                        '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                        '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                        '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                        '@sugar', '@sweet', '@topping', '@wafer'
                    ],
                    minimumCharacters: 1
                }]
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
        }).then(editor => {
            addItemEditor = editor;
        });

        if (cropper) {
            cancelCropper('addItemForm');
        }
        if (cropperDetail) {
            cancelDetailCropper('addItemForm');
        }

    });

    // Remove Some Fetaures when Close Add Modal
    $('#addItemModal .btn-close, #addItemModal .close-btn').on('click', function() {
        deleteDetailCropper('addItemForm');
        deleteCropper('addItemForm');
        $('.ck-editor').remove();
        addItemEditor = "";
        $('#addItemForm').trigger('reset');
        toggleMoreDetails('addItemModal')
    });

    // Remove Text Editor from Edit Item Modal
    $('#editItemModal .btn-close').on('click', function() {
        editItemEditor = "";
        $('.ck-editor').remove();
        if (cropper) {
            cropper.destroy();
        }

        if (cropperDetail) {
            cropperDetail.destroy();
        }

        $('#editItemModal #item_lang_div').html('');
    });

    // Function for add New Price
    function addPrice(ModalName) {
        if (ModalName === 'addItemModal') {
            var formType = "#addItemForm #priceDiv";
        } else {
            var formType = "#edit_item_form #priceDiv";
        }

        var count = $(formType).children('.price').length;
        var html = "";
        count++;

        html += '<div class="row mb-3 align-items-center price price_' + count + '">';
        html += '<div class="col-md-5 mb-1">';
        html += '<input type="text" name="price[price][]" class="form-control" placeholder="Enter Price">';
        html += '</div>';
        html += '<div class="col-md-6 mb-1">';
        html += '<input type="text" name="price[label][]" class="form-control" placeholder="Enter Price Label">';
        html += '</div>';
        html += '<div class="col-md-1 mb-1">';
        html += '<a onclick="$(\'.price_' + count + '\').remove()" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>';
        html += '</div>';
        html += '</div>';

        $(formType).append(html);
    }


    // Set TextEditor
    function setTextEditor(formID) {
        var my_item_textarea = $('#item_description_' + formID)[0];
        editItemEditor = "";
        $('.ck-editor').remove();

        // Text Editor
        CKEDITOR.ClassicEditor.create(my_item_textarea, {
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
            'height': 500,
            fontSize: {
                options: [10, 12, 14, 'default', 18, 20, 22],
                supportAllValues: true
            },
            htmlSupport: {
                allow: [{
                    name: /.*/,
                    attributes: true,
                    classes: true,
                    styles: true
                }]
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
                feeds: [{
                    marker: '@',
                    feed: [
                        '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                        '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                        '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                        '@sugar', '@sweet', '@topping', '@wafer'
                    ],
                    minimumCharacters: 1
                }]
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
        }).then(editor => {
            editItemEditor = editor;
        });
    }


    // Save New Item
    function saveItem() {

        const myFormData = new FormData(document.getElementById('addItemForm'));
        myFormData.set('description', addItemEditor.getData());

        // Remove Validation Class
        $('#addItemForm #name').removeClass('is-invalid');
        $('#addItemForm #category').removeClass('is-invalid');
        $('#addItemForm #image').removeClass('is-invalid');

        // Clear all Toastr Messages
        toastr.clear();

        $.ajax({
            type: "POST",
            url: "{{ route('items.store') }}",
            data: myFormData,
            dataType: "JSON",
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                if (response.success == 1) {
                    $('#addItemForm').trigger('reset');
                    $('#addItemModal').modal('hide');
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    $('#addItemForm').trigger('reset');
                    $('#addItemModal').modal('hide');
                    toastr.error(response.message);
                }
            },
            error: function(response) {
                // All Validation Errors
                const validationErrors = (response?.responseJSON?.errors) ? response.responseJSON.errors : '';

                if (validationErrors != '') {
                    // Name Error
                    var nameError = (validationErrors.name) ? validationErrors.name : '';
                    if (nameError != '') {
                        $('#addItemForm #name').addClass('is-invalid');
                        toastr.error(nameError);
                    }

                    // Category Error
                    var categoryError = (validationErrors.categories) ? validationErrors.categories : '';
                    if (categoryError != '') {
                        $('#addItemForm #categories').addClass('is-invalid');
                        toastr.error(categoryError);
                    }

                    // Image Error
                    var imageError = (validationErrors.image) ? validationErrors.image : '';
                    if (imageError != '') {
                        $('#addItemForm #image').addClass('is-invalid');
                        toastr.error(imageError);
                    }
                }
            }
        });
    }



    // Function for Delete Item
    function deleteItem(itemId) {
        swal({
                title: "Are you sure You want to Delete It ?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDeleteItem) => {
                if (willDeleteItem) {
                    $.ajax({
                        type: "POST",
                        url: '{{ route("items.delete") }}',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            'id': itemId,
                        },
                        dataType: 'JSON',
                        success: function(response) {
                            if (response.success == 1) {
                                toastr.success(response.message);
                                setTimeout(() => {
                                    location.reload();
                                }, 1300);
                            } else {
                                toastr.error(response.message);
                            }
                        }
                    });
                } else {
                    swal("Cancelled", "", "error");
                }
            });
    }



    // Function for Change Item Status
    function changeStatus(itemId, status) {
        $.ajax({
            type: "POST",
            url: '{{ route("items.status") }}',
            data: {
                "_token": "{{ csrf_token() }}",
                'status': status,
                'id': itemId
            },
            dataType: 'JSON',
            success: function(response) {
                if (response.success == 1) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1300);
                } else {
                    toastr.error(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1300);
                }
            }
        });
    }



    // // Function for Get Filterd Items
    $('#search').on('keyup', function() {
        var keywords = $(this).val();
        var catId = $('#cat_id').val();
        var listName = $('#view_select :selected').val();

        $.ajax({
            type: "POST",
            url: '{{ route("items.search") }}',
            data: {
                "_token": "{{ csrf_token() }}",
                'keywords': keywords,
                'id': catId,
                'design':listName,
            },
            dataType: 'JSON',
            success: function(response) {
                if (response.success == 1) {
                    if(response.design == 'grid'){
                        $('#ItemSection').html('');
                        $('#ItemSection').append(response.data);
                    }else{
                        $('#TableBodySection').html('');
                        $('#TableBodySection').append(response.data);
                    }
                } else {
                    toastr.error(response.message);
                }
            }
        });

    });


    // Function for Edit Item
    function editItem(itemID) {
        // Reset All Form
        $('#editItemModal #item_lang_div').html('');

        // Clear all Toastr Messages
        toastr.clear();

        $('.ck-editor').remove();
        editItemEditor = "";

        $.ajax({
            type: "POST",
            url: "{{ route('items.edit') }}",
            dataType: "JSON",
            data: {
                '_token': "{{ csrf_token() }}",
                'id': itemID,
            },
            success: function(response) {
                if (response.success == 1) {
                    $('#editItemModal #item_lang_div').html('');
                    $('#editItemModal #item_lang_div').append(response.data);

                    // Item Type
                    const itemType = response.item_type;

                    // If Item Type is Divider Then Hide Price Divs
                    if (itemType == 2) {
                        $('#editItemModal .price_div').hide();
                        $('#editItemModal .calories_div').hide();
                        $('#editItemModal .day_special').hide();
                        $('#editItemModal .mark_sign').hide();
                        $('#editItemModal .mark_new').hide();
                        $('#editItemModal .review_rating').hide();
                        $('#editItemModal .delivery').hide();
                        $('#editItemModal .crop_size').show();
                        $('#editItemModal .image-detail').hide();
                        $('#editItemModal .discount-div').hide();
                        $('#editItemModal .recomendation_items_div').hide();
                        $('#editItemModal .attributes-div').hide();
                    } else {
                        $('#editItemModal .price_div').show();
                        $('#editItemModal .calories_div').show();
                        $('#editItemModal .day_special').show();
                        $('#editItemModal .mark_sign').show();
                        $('#editItemModal .mark_new').show();
                        $('#editItemModal .review_rating').show();
                        $('#editItemModal .delivery').show();
                        $('#editItemModal .crop_size').hide();
                        $('#editItemModal .image-detail').show();
                        $('#editItemModal .discount-div').show();
                        $('#editItemModal .recomendation_items_div').show();
                        $('#editItemModal .attributes-div').show();
                    }

                    var categoriesEle = "#editItemModal #categories";
                    $(categoriesEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Categories",
                    });

                    // Intialized Ingredients SelectBox
                    var ingredientsEle = "#editItemModal #ingredients";
                    $(ingredientsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Indicative Icons",
                    });

                    // Intialized Recomendation Items SelectBox
                    var recomendationitems = '#editItemModal #recomendation_items';
                    $(recomendationitems).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Recomendation Items",
                    })

                    // Intialized Tags SelectBox
                    var tagsEle = "#editItemModal #tags";
                    $(tagsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Add New Tags",
                        tags: true,
                    });

                    // Intialized Options SelectBox
                    var optionsEle = "#editItemModal #options";
                    $(optionsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Attributes",
                    });

                    // Description Text Editor
                    $('.ck-editor').remove();
                    editItemEditor = "";
                    var my_item_textarea = $('#item_description')[0];
                    CKEDITOR.ClassicEditor.create(my_item_textarea, {
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
                        'height': 500,
                        fontSize: {
                            options: [10, 12, 14, 'default', 18, 20, 22],
                            supportAllValues: true
                        },
                        htmlSupport: {
                            allow: [{
                                name: /.*/,
                                attributes: true,
                                classes: true,
                                styles: true
                            }]
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
                            feeds: [{
                                marker: '@',
                                feed: [
                                    '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                                    '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                                    '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                                    '@sugar', '@sweet', '@topping', '@wafer'
                                ],
                                minimumCharacters: 1
                            }]
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
                    }).then(editor => {
                        editItemEditor = editor;
                    });

                    $('#editItemModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    }


    // Update Item By Language Code
    function updateItemByCode(next_lang_code) {
        var formID = "edit_item_form";
        var myFormData = new FormData(document.getElementById(formID));
        myFormData.set('item_description', editItemEditor.getData());
        myFormData.append('next_lang_code', next_lang_code);

        // Clear all Toastr Messages
        toastr.clear();

        $.ajax({
            type: "POST",
            url: "{{ route('items.update.by.lang') }}",
            data: myFormData,
            dataType: "JSON",
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                if (response.success == 1) {
                    $('#editItemModal #item_lang_div').html('');
                    $('#editItemModal #item_lang_div').append(response.data);

                    // Item Type
                    const itemType = response.item_type;

                    // If Item Type is Divider Then Hide Price Divs
                    if (itemType == 2) {
                        $('#editItemModal .price_div').hide();
                        $('#editItemModal .calories_div').hide();
                        $('#editItemModal .day_special').hide();
                        $('#editItemModal .mark_sign').hide();
                        $('#editItemModal .mark_new').hide();
                        $('#editItemModal .review_rating').hide();
                        $('#editItemModal .delivery').hide();
                        $('#editItemModal .crop_size').show();
                        $('#editItemModal .image-detail').hide();
                        $('#editItemModal .discount-div').hide();
                        $('#editItemModal .recomendation_items_div').hide();
                        $('#editItemModal .attributes-div').hide();
                    } else {
                        $('#editItemModal .price_div').show();
                        $('#editItemModal .calories_div').show();
                        $('#editItemModal .day_special').show();
                        $('#editItemModal .mark_sign').show();
                        $('#editItemModal .mark_new').show();
                        $('#editItemModal .review_rating').show();
                        $('#editItemModal .delivery').show();
                        $('#editItemModal .crop_size').hide();
                        $('#editItemModal .image-detail').show();
                        $('#editItemModal .discount-div').show();
                        $('#editItemModal .recomendation_items_div').show();
                        $('#editItemModal .attributes-div').show();
                    }

                    var categoriesEle = "#editItemModal #categories";
                    $(categoriesEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Categories",
                    });

                    // Intialized Ingredients SelectBox
                    var ingredientsEle = "#editItemModal #ingredients";
                    $(ingredientsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Indicative Icons",
                    });

                    // Intialized Recomendation Items SelectBox
                    var recomendationitems = '#editItemModal #recomendation_items';
                    $(recomendationitems).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Recomendation Items",
                    })

                    // Intialized Tags SelectBox
                    var tagsEle = "#editItemModal #tags";
                    $(tagsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Add New Tags",
                        tags: true,
                    });

                    // Intialized Options SelectBox
                    var optionsEle = "#editItemModal #options";
                    $(optionsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Attributes",
                    });

                    // Description Text Editor
                    $('.ck-editor').remove();
                    editItemEditor = "";
                    var my_item_textarea = $('#item_description')[0];
                    CKEDITOR.ClassicEditor.create(my_item_textarea, {
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
                        'height': 500,
                        fontSize: {
                            options: [10, 12, 14, 'default', 18, 20, 22],
                            supportAllValues: true
                        },
                        htmlSupport: {
                            allow: [{
                                name: /.*/,
                                attributes: true,
                                classes: true,
                                styles: true
                            }]
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
                            feeds: [{
                                marker: '@',
                                feed: [
                                    '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                                    '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                                    '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                                    '@sugar', '@sweet', '@topping', '@wafer'
                                ],
                                minimumCharacters: 1
                            }]
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
                    }).then(editor => {
                        editItemEditor = editor;
                    });
                } else {
                    $('#editItemModal').modal('hide');
                    $('#editItemModal #item_lang_div').html('');
                    toastr.error(response.message);
                }
            },
            error: function(response) {
                if (response.responseJSON.errors) {
                    $.each(response.responseJSON.errors, function(i, error) {
                        toastr.error(error);
                    });
                }
            }
        });
    }


    // Function for Update Item
    function updateItem() {
        var formID = "edit_item_form";
        var myFormData = new FormData(document.getElementById(formID));
        myFormData.set('item_description', editItemEditor.getData());

        // Clear all Toastr Messages
        toastr.clear();

        $.ajax({
            type: "POST",
            url: "{{ route('items.update') }}",
            data: myFormData,
            dataType: "JSON",
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                // Old Code
                // if(response.success == 1){
                //     toastr.success(response.message);
                // }else{
                //     $('#editItemModal').modal('hide');
                //     toastr.error(response.message);
                //     setTimeout(() => {
                //         location.reload();
                //     }, 1000);
                // }

                // New Code
                if (response.success == 1) {
                    $('#editItemModal #item_lang_div').html('');
                    $('#editItemModal #item_lang_div').append(response.data);

                    // Item Type
                    const itemType = response.item_type;

                    // If Item Type is Divider Then Hide Price Divs
                    if (itemType == 2) {
                        $('#editItemModal .price_div').hide();
                        $('#editItemModal .calories_div').hide();
                        $('#editItemModal .day_special').hide();
                        $('#editItemModal .mark_sign').hide();
                        $('#editItemModal .mark_new').hide();
                        $('#editItemModal .review_rating').hide();
                        $('#editItemModal .delivery').hide();
                        $('#editItemModal .crop_size').show();
                        $('#editItemModal .image-detail').hide();
                        $('#editItemModal .discount-div').hide();
                        $('#editItemModal .recomendation_items_div').hide();
                    } else {
                        $('#editItemModal .price_div').show();
                        $('#editItemModal .calories_div').show();
                        $('#editItemModal .day_special').show();
                        $('#editItemModal .mark_sign').show();
                        $('#editItemModal .mark_new').show();
                        $('#editItemModal .review_rating').show();
                        $('#editItemModal .delivery').show();
                        $('#editItemModal .crop_size').hide();
                        $('#editItemModal .image-detail').show();
                        $('#editItemModal .discount-div').show();
                        $('#editItemModal .recomendation_items_div').show();
                    }

                    var categoriesEle = "#editItemModal #categories";
                    $(categoriesEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Categories",
                    });

                    // Intialized Ingredients SelectBox
                    var ingredientsEle = "#editItemModal #ingredients";
                    $(ingredientsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Indicative Icons",
                    });

                    // Intialized Recomendation Items SelectBox
                    var recomendationitems = '#editItemModal #recomendation_items';
                    $(recomendationitems).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Recomendation Items",
                    })

                    // Intialized Tags SelectBox
                    var tagsEle = "#editItemModal #tags";
                    $(tagsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Add New Tags",
                        tags: true,
                    });

                    // Intialized Options SelectBox
                    var optionsEle = "#editItemModal #options";
                    $(optionsEle).select2({
                        dropdownParent: $("#editItemModal"),
                        placeholder: "Select Attributes",
                    });

                    // Description Text Editor
                    $('.ck-editor').remove();
                    editItemEditor = "";
                    var my_item_textarea = $('#item_description')[0];
                    CKEDITOR.ClassicEditor.create(my_item_textarea, {
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
                        'height': 500,
                        fontSize: {
                            options: [10, 12, 14, 'default', 18, 20, 22],
                            supportAllValues: true
                        },
                        htmlSupport: {
                            allow: [{
                                name: /.*/,
                                attributes: true,
                                classes: true,
                                styles: true
                            }]
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
                            feeds: [{
                                marker: '@',
                                feed: [
                                    '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                                    '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                                    '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                                    '@sugar', '@sweet', '@topping', '@wafer'
                                ],
                                minimumCharacters: 1
                            }]
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
                    }).then(editor => {
                        editItemEditor = editor;
                    });

                    toastr.success(response.message);
                } else {
                    $('#editItemModal').modal('hide');
                    $('#editItemModal #item_lang_div').html('');
                    toastr.error(response.message);
                }
            },
            error: function(response) {
                if (response.responseJSON.errors) {
                    $.each(response.responseJSON.errors, function(i, error) {
                        toastr.error(error);
                    });
                }
            }
        });

    }

    $(document).ready(function() {
        togglePrice('addItemModal');
    });

    // Function for Hide & Show Price
    function togglePrice(ModalName) {
        var currVal = $('#' + ModalName + ' #type :selected').val();

        if (currVal == 2) {
            $("#" + ModalName + " .price_div").hide();
            $("#" + ModalName + " .calories_div").hide();
            $("#" + ModalName + " .day_special").hide();
            $("#" + ModalName + " .mark_sign").hide();
            $("#" + ModalName + " .mark_new").hide();
            $("#" + ModalName + " .review_rating").hide();
            $("#" + ModalName + " .delivery").hide();
            $("#" + ModalName + " .crop_size").show();
            $("#" + ModalName + " .image-detail").hide();
            $("#" + ModalName + " .discount-div").hide();
            $("#" + ModalName + " .recomendation_items_div").hide();
            $("#" + ModalName + " .attributes-div").hide();
        } else {
            $("#" + ModalName + " .price_div").show();
            $("#" + ModalName + " .calories_div").show();
            $("#" + ModalName + " .day_special").show();
            $("#" + ModalName + " .mark_sign").show();
            $("#" + ModalName + " .mark_new").show();
            $("#" + ModalName + " .review_rating").show();
            $("#" + ModalName + " .delivery").show();
            $("#" + ModalName + " .crop_size").show();
            $("#" + ModalName + " .image-detail").show();
            $("#" + ModalName + " .discount-div").show();
            $("#" + ModalName + " .recomendation_items_div").show();
            $("#" + ModalName + " .attributes-div").show();
        }
    }



    // Function for Delete Tag
    function deleteTag(Id) {
        $.ajax({
            type: "POST",
            url: '{{ route("tags.destroy") }}',
            data: {
                "_token": "{{ csrf_token() }}",
                'id': Id,
            },
            dataType: 'JSON',
            success: function(response) {
                if (response.success == 1) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1300);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    }



    // Function for Edit Tag
    function editTag(tagID) {
        // Reset All Form
        $('#editTagModal #tag_edit_div').html('');

        // Clear all Toastr Messages
        toastr.clear();

        $.ajax({
            type: "POST",
            url: "{{ route('tags.edit') }}",
            dataType: "JSON",
            data: {
                '_token': "{{ csrf_token() }}",
                'id': tagID,
            },
            success: function(response) {
                if (response.success == 1) {
                    $('#editTagModal #tag_edit_div').html(response.data);
                    $('#editTagModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    }


    // Update Tag By Language Code
    function updateByCode(next_lang_code) {
        const myFormData = new FormData(document.getElementById('editTagForm'));
        myFormData.append('next_lang_code', next_lang_code);

        // Clear all Toastr Messages
        toastr.clear();

        $.ajax({
            type: "POST",
            url: "{{ route('tags.update-by-lang') }}",
            data: myFormData,
            dataType: "JSON",
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                if (response.success == 1) {
                    $('#editTagModal #tag_edit_div').html('');
                    $('#editTagModal #tag_edit_div').html(response.data);
                } else {
                    $('#editTagModal').modal('hide');
                    $('#editTagModal #tag_edit_div').html('');
                    toastr.error(response.message);
                }
            },
            error: function(response) {
                $.each(response.responseJSON.errors, function(i, error) {
                    toastr.error(error);
                });
            }
        });
    }


    // Update Tag
    function updateTag() {
        const myFormData = new FormData(document.getElementById('editTagForm'));

        // Clear all Toastr Messages
        toastr.clear();

        $.ajax({
            type: "POST",
            url: "{{ route('tags.update') }}",
            data: myFormData,
            dataType: "JSON",
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                if (response.success == 1) {
                    $('#editTagModal').modal('hide');
                    $('#editTagModal #tag_edit_div').html('');
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    $('#editTagModal').modal('hide');
                    $('#editTagModal #tag_edit_div').html('');
                    toastr.error(response.message);
                }
            },
            error: function(response) {
                $.each(response.responseJSON.errors, function(i, error) {
                    toastr.error(error);
                });
            }
        });

    }


    // Sort Tags
    $(function() {
        // Sorting Tags
        $("#tagsSorting").sortable({
            connectWith: ".connectedSortableTags",
            opacity: 0.5,
        }).disableSelection();

        $(".connectedSortableTags").on("sortupdate", function(event, ui) {
            var tagsArr = [];

            $("#tagsSorting .col-sm-2").each(function(index) {
                tagsArr[index] = $(this).attr('tag-id');
            });

            $.ajax({
                type: "POST",
                url: '{{ route("tags.sorting") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'sortArr': tagsArr,
                },
                dataType: 'JSON',
                success: function(response) {
                    if (response.success == 1) {
                        toastr.success(response.message);
                    }
                }
            });

        });

        $("#ItemTableSection tbody").sortable({
            connectWith: ".connectedSortableItemsTable tbody",
            opacity: 0.5,
        }).disableSelection();

        $(".connectedSortableItemsTable").on("sortupdate", function(event, ui) {
            var catId = $('#cat_id').val();
            var shop_id = $('#shop_id').val();

            var itemsArr = [];
            $("#ItemTableSection tbody tr").each(function(index) {
                itemsArr[index] = $(this).attr('item-id');
            });

            $.ajax({
                type: "POST",
                url: '{{ route("items.sorting") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'sortArr': itemsArr,
                    'catId': catId,
                    'shop_id': shop_id,
                },
                dataType: 'JSON',
                success: function(response) {
                    if (response.success == 1) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        });


        // Sorting Items
        $("#ItemSection").sortable({
            connectWith: ".connectedSortableItems",
            opacity: 0.5,
        }).disableSelection();

        $(".connectedSortableItems").on("sortupdate", function(event, ui) {
            var catId = $('#cat_id').val();
            var shop_id = $('#shop_id').val();

            var itemsArr = [];
            $("#ItemSection .col-md-3").each(function(index) {
                itemsArr[index] = $(this).attr('item-id');
            });

            $.ajax({
                type: "POST",
                url: '{{ route("items.sorting") }}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    'sortArr': itemsArr,
                    'catId': catId,
                    'shop_id': shop_id,
                },
                dataType: 'JSON',
                success: function(response) {
                    if (response.success == 1) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        });

    });


    // Function for Get Items By Category ID
    $('#cat_filter').on('change', function() {
        var catID = $('#cat_filter :selected').val();
        var listName = $('#view_select :selected').val();
        var Url = "{{ route('items') }}";
        location.href = Url + "/" + listName + "/" + catID;
    });

    $('#view_select').on('change', function() {
        var catID = $('#cat_filter :selected').val();
        var listName = $('#view_select :selected').val();
        var Url = "{{ route('items') }}";
        location.href = Url + "/" + listName + "/" + catID;
    });

    // Remove Item Price
    function deleteItemPrice(priceID, count) {
        $.ajax({
            type: "POST",
            url: "{{ route('items.delete.price') }}",
            data: {
                "_token": "{{ csrf_token() }}",
                "price_id": priceID,
            },
            dataType: "JSON",
            success: function(response) {
                if (response.success == 1) {
                    $('.price_' + count).remove();
                }
            }
        });

    }

    // Devider Size change
    $('#addItemModal #crop_size').on('change', function() {
        const formID = this.form.id;
        cropper.destroy();
        $('#' + formID + ' #resize-image').attr('src', "");
        $('#' + formID + ' .img-crop-sec').hide();
        $('#' + formID + ' #image').val('');
        $('#' + formID + ' #item_image').val('');
        $('#' + formID + ' #og_image').val('');
    });

    // Devider Size change
    $(document).on('change', '#editItemModal #crop_size', function() {
        const formID = this.form.id;
        cropper.destroy();
        $('#' + formID + ' #resize-image').attr('src', "");
        $('#' + formID + ' .img-crop-sec').hide();
        $('#' + formID + ' #image').val('');
        $('#' + formID + ' #item_image').val('');
        $('#' + formID + ' #og_image').val('');
    });

    // Image detail Functionality for Add Modal
    $('#addItemModal #image_detail').on('change', function() {
        const myFormID = this.form.id;
        const currentFile = this.files[0];
        var fitPreview = 0;

        if (currentFile) {
            var catImage = new Image();
            catImage.src = URL.createObjectURL(currentFile);
            catImage.onload = function() {
                if (this.width === 700 && this.height === 400) {
                    fitPreview = 1;
                }

                var currRatio = 700 / 400; // Setting aspect ratio to 700:400

                fileSize = currentFile.size / 1024 / 1024;
                fileName = currentFile.name;
                fileType = fileName.split('.').pop().toLowerCase();

                if (fileSize > 2) {
                    toastr.error("File is too Big " + fileSize.toFixed(2) + "MiB. Max File size : 2 MiB.");
                    $('#' + myFormID + ' #image_detail').val('');
                    return false;
                } else {
                    if ($.inArray(fileType, ['gif', 'png', 'jpg', 'jpeg']) == -1) {
                        toastr.error("The Item Image must be a file of type: png, jpg, svg, jpeg");
                        $('#' + myFormID + ' #image_detail').val('');
                        return false;
                    } else {
                        if (cropperDetail) {
                            cropperDetail.destroy();
                        }

                        $('#' + myFormID + ' #resize-image-detail').attr('src', "");
                        $('#' + myFormID + ' #resize-image-detail').attr('src', URL.createObjectURL(currentFile));
                        $('#' + myFormID + ' .img-detail-crop-sec').show();

                        const CrpImage = document.getElementById('resize-image-detail');
                        cropperDetail = new Cropper(CrpImage, {
                            aspectRatio: currRatio,
                            zoomable: false,
                            cropBoxResizable: false,
                            preview: '#' + myFormID + ' .preview_detail',
                            autoCropArea: fitPreview,
                        });
                    }
                }
            }
        }
    });

    // Image Cropper Functionality for Add Model
    $('#addItemModal #image').on('change', function() {
        const myFormID = this.form.id;
        const currentFile = this.files[0];
        var fitPreview = 0;
        var item_type = $('#addItemModal #type').val();
        var crop_size = $('#addItemModal #crop_size').val();

        if (currentFile) {
            var catImage = new Image();
            catImage.src = URL.createObjectURL(currentFile);
            catImage.onload = function() {
                if (this.width === 400 && this.height === 400) {
                    fitPreview = 1;
                }

                if (item_type == 1) {
                    currRatio = 1;
                } else {
                    if (crop_size == 400) {
                        currRatio = 1;
                    } else {
                        currRatio = 700 / 400;
                    }
                }

                fileSize = currentFile.size / 1024 / 1024;
                fileName = currentFile.name;
                fileType = fileName.split('.').pop().toLowerCase();

                if (fileSize > 2) {
                    toastr.error("File is to Big " + fileSize.toFixed(2) + "MiB. Max File size : 2 MiB.");
                    $('#' + myFormID + ' #image').val('');
                    return false;
                } else {
                    if ($.inArray(fileType, ['gif', 'png', 'jpg', 'jpeg']) == -1) {
                        toastr.error("The Item Image must be a file of type: png, jpg, svg, jpeg");
                        $('#' + myFormID + ' #image').val('');
                        return false;
                    } else {
                        if (cropper) {
                            cropper.destroy();
                        }

                        $('#' + myFormID + ' #resize-image').attr('src', "");
                        $('#' + myFormID + ' #resize-image').attr('src', URL.createObjectURL(currentFile));
                        $('#' + myFormID + ' .img-crop-sec').show();

                        const CrpImage = document.getElementById('resize-image');
                        cropper = new Cropper(CrpImage, {
                            aspectRatio: currRatio,
                            zoomable: false,
                            cropBoxResizable: false,
                            preview: '#' + myFormID + ' .preview',
                            autoCropArea: fitPreview,
                        });
                    }
                }
            }
        }
    });




    // Image Cropper Functionality for Edit Modal
    function imageDetailCropper(formID, ele) {

        var currentFile = ele.files[0];
        var myFormID = formID;

        var fitPreview = 0;
        var item_type = $('#editItemModal #type').val();



        if (currentFile) {
            var catImage = new Image();
            catImage.src = URL.createObjectURL(currentFile);
            catImage.onload = function() {
                if (this.width === 700 && this.height === 400) {
                    fitPreview = 1;
                }

                var currRatio = 700 / 400; // Setting aspect ratio to 700:400

                fileSize = currentFile.size / 1024 / 1024;
                fileName = currentFile.name;
                fileType = fileName.split('.').pop().toLowerCase();

                if (fileSize > 2) {
                    toastr.error("File is to Big " + fileSize.toFixed(2) + "MiB. Max File size : 2 MiB.");
                    $('#' + myFormID + ' #item_image_detail').val('');
                    return false;
                } else {
                    if ($.inArray(fileType, ['gif', 'png', 'jpg', 'jpeg']) == -1) {
                        toastr.error("The Item Image must be a file of type: png, jpg, svg, jpeg");
                        $('#' + myFormID + ' #item_image_detail').val('');
                        return false;
                    } else {
                        if (cropperDetail) {
                            cropperDetail.destroy();
                            $('#' + myFormID + ' #resize-image-detail').attr('src', "");
                            $('#' + myFormID + ' .img-detail-crop-sec').hide();
                        }

                        $('#' + myFormID + ' #resize-image-detail').attr('src', "");
                        $('#' + myFormID + ' #resize-image-detail').attr('src', URL.createObjectURL(currentFile));
                        $('#' + myFormID + ' .img-detail-crop-sec').show();

                        // const CrpImage = document.getElementById('resize-image');
                        const CrpImage = $('#' + myFormID + ' #resize-image-detail')[0];

                        cropperDetail = new Cropper(CrpImage, {
                            aspectRatio: currRatio,
                            zoomable: false,
                            cropBoxResizable: false,
                            preview: '#' + myFormID + ' .preview',
                            autoCropArea: fitPreview,
                        });
                    }
                }
            }
        }
    }

    // Image Cropper Functionality for Edit Modal
    function imageCropper(formID, ele) {
        var currentFile = ele.files[0];
        var myFormID = formID;

        var fitPreview = 0;
        var item_type = $('#editItemModal #type').val();
        var crop_size = $('#editItemModal #crop_size').val();



        if (currentFile) {
            var catImage = new Image();
            catImage.src = URL.createObjectURL(currentFile);
            catImage.onload = function() {
                if (this.width === 400 && this.height === 400) {
                    fitPreview = 1;
                }

                if (item_type == 1) {
                    currRatio = 1;
                } else {
                    if (crop_size == 400) {
                        currRatio = 1;
                    } else {
                        currRatio = 700 / 400;
                    }

                }

                fileSize = currentFile.size / 1024 / 1024;
                fileName = currentFile.name;
                fileType = fileName.split('.').pop().toLowerCase();

                if (fileSize > 2) {
                    toastr.error("File is to Big " + fileSize.toFixed(2) + "MiB. Max File size : 2 MiB.");
                    $('#' + myFormID + ' #item_image').val('');
                    return false;
                } else {
                    if ($.inArray(fileType, ['gif', 'png', 'jpg', 'jpeg']) == -1) {
                        toastr.error("The Item Image must be a file of type: png, jpg, svg, jpeg");
                        $('#' + myFormID + ' #item_image').val('');
                        return false;
                    } else {
                        if (cropper) {
                            cropper.destroy();
                            $('#' + myFormID + ' #resize-image').attr('src', "");
                            $('#' + myFormID + ' .img-crop-sec').hide();
                        }

                        $('#' + myFormID + ' #resize-image').attr('src', "");
                        $('#' + myFormID + ' #resize-image').attr('src', URL.createObjectURL(currentFile));
                        $('#' + myFormID + ' .img-crop-sec').show();

                        // const CrpImage = document.getElementById('resize-image');
                        const CrpImage = $('#' + myFormID + ' #resize-image')[0];

                        cropper = new Cropper(CrpImage, {
                            aspectRatio: currRatio,
                            zoomable: false,
                            cropBoxResizable: false,
                            preview: '#' + myFormID + ' .preview',
                            autoCropArea: fitPreview,
                        });
                    }
                }
            }
        }
    }

    // Save Cropper Image for Image Detail
    function saveDetailCropper(formID) {

        var item_type = $('#' + formID + " #type").val();

        var canvas = cropperDetail.getCroppedCanvas({
            width: 700,
            height: 400 // Adjust dimensions as needed
        });

        canvas.toBlob(function(blob) {
            $('#' + formID + " #crp-img-detail-prw").attr('src', URL.createObjectURL(blob));
            var reader = new FileReader();
            reader.readAsDataURL(blob);
            reader.onloadend = function() {
                var base64data = reader.result;
                $('#' + formID + ' #og_image_detail').val(base64data);
            };
        });

        cropperDetail.destroy();
        $('#' + formID + ' #resize-image-detail').attr('src', "");
        $('#' + formID + ' .img-detail-crop-sec').hide();

        if (formID == 'addItemForm') {
            $('#' + formID + " #img-detail-label").append('<a class="btn btn-sm btn-danger" id="del-img-detail" style="border-radius:50%" onclick="deleteDetailCropper(\'' + formID + '\')"><i class="fa fa-trash"></i></a>');
        } else {
            $('#' + formID + ' #edit-img-detail').hide();
            $('#' + formID + " #img-detail-label").append('<a class="btn btn-sm btn-danger" id="del-img-detail" style="border-radius:50%" onclick="deleteDetailCropper(\'' + formID + '\')"><i class="fa fa-trash"></i></a>');
            $('#' + formID + ' #rep-image-detail').show();
        }

    }

    function resetDetailCropper() {
        cropperDetail.reset();
    }

    // Cancel Cropper for Image Detail
    function cancelDetailCropper(formID) {
        cropperDetail.destroy();
        $('#' + formID + ' #resize-image-detail').attr('src', "");
        $('#' + formID + ' .img-detail-crop-sec').hide();
        $('#' + formID + ' #image_detail').val('');
        $('#' + formID + ' #og_image_detail').val('');
    }

    // Delete Cropper for Image Detail
    function deleteDetailCropper(formID) {
        if (cropperDetail) {

            cropperDetail.destroy();

        }

        $('#' + formID + ' #resize-image-detail').attr('src', "");
        $('#' + formID + ' .img-detail-crop-sec').hide();
        $('#' + formID + ' #og_image_detail').val('');
        $('#' + formID + " #del-img-detail").remove();

        if (formID == 'addItemForm') {
            $('#' + formID + ' #image_detail').val('');
            $('#' + formID + " #crp-img-det-prw").attr('src', "{{ asset('public/client_images/not-found/no_image_1.jpg') }}");
        } else {
            $('#' + formID + ' #image_detail').val('');
            $('#' + formID + " #crp-img-det-prw").attr('src', "{{ asset('public/client_images/not-found/no_image_1.jpg') }}");
            $('#' + formID + ' #edit-img-detail').show();
            $('#' + formID + ' #rep-image-detail').hide();
        }
    }



    // Reset Copper
    function resetCropper() {
        cropper.reset();
    }


    // Canel Cropper
    function cancelCropper(formID) {
        cropper.destroy();
        $('#' + formID + ' #resize-image').attr('src', "");
        $('#' + formID + ' .img-crop-sec').hide();
        $('#' + formID + ' #image').val('');
        $('#' + formID + ' #item_image').val('');
        $('#' + formID + ' #og_image').val('');
    }


    // Save Cropper Image
    function saveCropper(formID) {
        var item_type = $('#' + formID + " #type").val();
        var crop_size = $('#' + formID + " #crop_size").val();


        if (item_type == 1) {
            var canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400
            });
        } else {
            if (crop_size == 400) {
                var canvas = cropper.getCroppedCanvas({
                    width: 400,
                    height: 400
                });
            } else {
                var canvas = cropper.getCroppedCanvas({
                    width: 700,
                    height: 400
                });
            }
        }


        canvas.toBlob(function(blob) {
            $('#' + formID + " #crp-img-prw").attr('src', URL.createObjectURL(blob));
            url = URL.createObjectURL(blob);
            var reader = new FileReader();
            reader.readAsDataURL(blob);
            reader.onloadend = function() {
                var base64data = reader.result;
                $('#' + formID + ' #og_image').val(base64data);
            };
        });

        cropper.destroy();
        $('#' + formID + ' #resize-image').attr('src', "");
        $('#' + formID + ' .img-crop-sec').hide();

        if (formID == 'addItemForm') {
            $('#' + formID + " #img-label").append('<a class="btn btn-sm btn-danger" id="del-img" style="border-radius:50%" onclick="deleteCropper(\'' + formID + '\')"><i class="fa fa-trash"></i></a>');
        } else {
            $('#' + formID + ' #edit-img').hide();
            $('#' + formID + " #img-label").append('<a class="btn btn-sm btn-danger" id="del-img" style="border-radius:50%" onclick="deleteCropper(\'' + formID + '\')"><i class="fa fa-trash"></i></a>');
            $('#' + formID + ' #rep-image').show();
        }
    }


    // Delete Cropper
    function deleteCropper(formID) {
        if (cropper) {
            cropper.destroy();
        }

        $('#' + formID + ' #resize-image').attr('src', "");
        $('#' + formID + ' .img-crop-sec').hide();
        $('#' + formID + ' #og_image').val('');
        $('#' + formID + " #del-img").remove();

        if (formID == 'addItemForm') {
            $('#' + formID + ' #image_detail').val('');
            $('#' + formID + " #crp-img-prw").attr('src', "{{ asset('public/client_images/not-found/no_image_1.jpg') }}");
        } else {
            $('#' + formID + ' #item_image').val('');
            $('#' + formID + " #crp-img-prw").attr('src', "{{ asset('public/client_images/not-found/no_image_1.jpg') }}");
            $('#' + formID + ' #edit-img').show();
            $('#' + formID + ' #rep-image').hide();
        }

    }


    // Function for Toggle more Information
    function toggleMoreDetails(ModalName) {
        if (ModalName == 'addItemModal') {
            var formId = '#addItemForm';
        } else {
            var formId = '#edit_item_form';
        }

        var curr_icon = $(formId + ' #more_dt_btn i').attr('class');
        if (curr_icon == 'bi bi-eye-slash') {
            $(formId + ' #more_dt_btn i').attr('class', 'bi bi-eye');
        } else {
            $(formId + ' #more_dt_btn i').attr('class', 'bi bi-eye-slash');
        }
        $(formId + ' #more_details').toggle();
    }


    // Function for Change Item Delivery
    function changeItemDelivery(itemID, status) {
        $.ajax({
            type: "POST",
            url: '{{ route("items.delivery.status") }}',
            data: {
                "_token": "{{ csrf_token() }}",
                'delivery': status,
                'id': itemID
            },
            dataType: 'JSON',
            success: function(response) {
                if (response.success == 1) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1300);
                } else {
                    toastr.error(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1300);
                }
            }
        });
    }

    function toggleButton(event, itemId) {
        event.stopPropagation();

        // Get the action menu for the specific item
        const actionList = document.getElementById('action_ul_' + itemId);

        // Toggle the 'open' class
        if (actionList.classList.contains('open')) {
            actionList.classList.remove('open');
        } else {
            const allActionLists = document.querySelectorAll('.action_ul');
            allActionLists.forEach((list) => {
                list.classList.remove('open');
            });
            actionList.classList.add('open');
        }
    }

    function handleClickOutside(){
        const actionLists = document.querySelectorAll('.action_ul.open');
        actionLists.forEach((list) => {
            if (!list.contains(event.target) && !event.target.classList.contains('item_action_btn')) {
                list.classList.remove('open');
            }
        });
    }
    document.addEventListener('click', handleClickOutside);
</script>

@endsection
