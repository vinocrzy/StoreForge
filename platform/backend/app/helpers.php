<?php

if (!function_exists('tenant')) {
    function tenant(): ?\App\Models\Store
    {
        return app()->has('tenant') ? app('tenant') : null;
    }
}

if (!function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        return tenant()?->id;
    }
}

if (!function_exists('has_tenant')) {
    function has_tenant(): bool
    {
        return tenant() !== null;
    }
}
