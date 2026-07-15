<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\InstallController;
use App\Controllers\StoreController;
use App\Controllers\AppearanceController;

$r=$app->router;
$r->get('/install',[InstallController::class,'index']); $r->post('/install',[InstallController::class,'install']);
$r->get('/login',[AuthController::class,'show']); $r->post('/login',[AuthController::class,'login']); $r->post('/logout',[AuthController::class,'logout']);
$r->get('/',[StoreController::class,'home']); $r->get('/produk',[StoreController::class,'catalog']); $r->get('/produk/{slug}',[StoreController::class,'product']); $r->get('/kategori/{slug}',[StoreController::class,'catalog']); $r->get('/media/{file}',[StoreController::class,'image']); $r->get('/media/{collection}/{file}',[StoreController::class,'media']);
$r->get('/keranjang',[StoreController::class,'cart']); $r->post('/keranjang/tambah',[StoreController::class,'addCart']); $r->put('/keranjang',[StoreController::class,'updateCart']); $r->delete('/keranjang/{id}',[StoreController::class,'removeCart']); $r->get('/checkout',[StoreController::class,'checkout']); $r->post('/checkout',[StoreController::class,'placeOrderV2']); $r->get('/api/shipping/destinations',[StoreController::class,'shippingDestinations']); $r->post('/api/shipping/rates',[StoreController::class,'shippingRates']);
$r->get('/admin',[AdminController::class,'dashboard']);
$r->get('/admin/categories',[AdminController::class,'categories']); $r->post('/admin/categories',[AdminController::class,'saveCategory']); $r->delete('/admin/categories/{id}',[AdminController::class,'deleteCategory']);
$r->get('/admin/products',[AdminController::class,'products']); $r->get('/admin/products/create',[AdminController::class,'productForm']); $r->post('/admin/products',[AdminController::class,'saveProduct']); $r->get('/admin/products/{id}/edit',[AdminController::class,'productForm']); $r->put('/admin/products/{id}',[AdminController::class,'saveProduct']); $r->delete('/admin/products/{id}',[AdminController::class,'deleteProduct']);
$r->get('/admin/discounts',[AdminController::class,'discounts']); $r->post('/admin/discounts',[AdminController::class,'saveDiscount']);
$r->get('/admin/inventory',[AdminController::class,'inventory']); $r->post('/admin/inventory/{id}/adjust',[AdminController::class,'adjustStock']);
$r->get('/admin/stock-opnames',[AdminController::class,'opnames']); $r->post('/admin/stock-opnames',[AdminController::class,'createOpname']); $r->get('/admin/stock-opnames/{id}',[AdminController::class,'showOpname']); $r->post('/admin/stock-opnames/{id}/complete',[AdminController::class,'completeOpname']);
$r->get('/admin/orders',[AdminController::class,'orders']); $r->put('/admin/orders/{id}',[AdminController::class,'updateOrder']);
$r->get('/admin/settings',[AdminController::class,'settings']); $r->post('/admin/settings',[AdminController::class,'saveSettings']); $r->post('/admin/settings/shipping/test',[AdminController::class,'testShipping']);
$r->get('/admin/appearance/branding',[AppearanceController::class,'branding']); $r->post('/admin/appearance/branding',[AppearanceController::class,'saveBranding']); $r->get('/admin/appearance/homepage',[AppearanceController::class,'homepage']); $r->post('/admin/appearance/homepage',[AppearanceController::class,'saveHomepage']); $r->get('/admin/appearance/footer',[AppearanceController::class,'footer']); $r->post('/admin/appearance/footer',[AppearanceController::class,'saveFooter']);
