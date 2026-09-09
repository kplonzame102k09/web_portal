<?php
use App\Http\Controllers\AuthController; use App\Http\Controllers\PortalController; use Illuminate\Support\Facades\Route;
Route::view('/','home')->name('home');
Route::get('/login',[AuthController::class,'showLogin'])->name('login');
Route::post('/login',[AuthController::class,'login'])->name('login.store');
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->prefix('portal/{role}')->whereIn('role',['student','parent','teacher','admin'])->group(function(){
 Route::get('/',[PortalController::class,'dashboard'])->name('portal.dashboard');
 Route::get('/backup',[PortalController::class,'backup'])->name('portal.backup');
 Route::get('/{module}/export',[PortalController::class,'export'])->whereIn('module',['enrollment','requirements','documents','grades','competencies','attendance','announcements','parent-links','users','students','children','notifications','audit-logs'])->name('portal.export');
 Route::post('/notifications/{record}/read',[PortalController::class,'readNotification'])->name('portal.notification.read');
 Route::get('/{module}',[PortalController::class,'module'])->whereIn('module',['enrollment','requirements','documents','grades','competencies','attendance','announcements','parent-links','users','profile','students','children','settings','notifications','audit-logs'])->name('portal.module');
 Route::post('/{module}',[PortalController::class,'store'])->whereIn('module',['enrollment','requirements','documents','grades','competencies','attendance','announcements','parent-links','users','profile','settings'])->name('portal.store');
 Route::patch('/{module}/{record}/status',[PortalController::class,'status'])->name('portal.status');
 Route::delete('/{module}/{record}',[PortalController::class,'destroy'])->name('portal.destroy');
});