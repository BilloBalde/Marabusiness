<?php

if (!function_exists('format_price')) {
    function format_price($amount, $currencyCode = 'USD')
    {
        $amount = is_numeric($amount) ? (float) $amount : 0;
        
        try {
            return \Illuminate\Support\Number::currency($amount, $currencyCode);
        } catch (\Exception $e) {
            return number_format($amount, 2) . ' ' . $currencyCode;
        }
    }
}