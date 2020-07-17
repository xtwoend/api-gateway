<?php

namespace App\Providers;

use App\Gateway\RouteRegistry;
use Carbon\Carbon;
use Dusterio\LumenPassport\LumenPassport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // LumenPassport::routes($this->app);
        LumenPassport::allowMultipleTokens();
        LumenPassport::tokensExpireIn(Carbon::now()->addYears(50), 2); 
    }
}
