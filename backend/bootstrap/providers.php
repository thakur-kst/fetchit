<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Shared\Providers\SharedServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\DBCore\Providers\DBCoreServiceProvider::class,
    Modules\HealthCheck\Providers\HealthCheckServiceProvider::class,
    Modules\SchemaMgr\Providers\SchemaMgrServiceProvider::class,
    Modules\GmailAccounts\Providers\GmailAccountServiceProvider::class,
    Modules\GmailSync\Providers\GmailSyncServiceProvider::class,
    Modules\Orders\Providers\OrderServiceProvider::class,
];