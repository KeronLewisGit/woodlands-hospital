@props(['code', 'exception' => null])
@php
    $copy = [
        '403' => ['Access not allowed', 'This page is not available to your account, or the link has expired.'],
        '404' => ['Page not found', 'The page may have moved. Try the menu, or call us and we will help.'],
        '419' => ['Page expired', 'For your security, forms expire after a while. Go back, refresh the page and try again.'],
        '429' => ['Too many attempts', 'Please wait a minute before trying again.'],
        '500' => ['Something went wrong', 'We have been notified. Please try again, or call us.'],
        '503' => ['Back shortly', 'The website is being updated. The hospital is open as usual.'],
    ][$code] ?? ['Something went wrong', 'Please try again.'];
    $message = $exception && $exception->getMessage() && in_array($code, ['403'], true) ? $exception->getMessage() : $copy[1];
@endphp
<x-layouts.site :title="$copy[0]" :mobile-bar="true">
    <x-page-header :title="$copy[0]" :lead="$message" :sign="'Error '.$code" code="!" />
    <section class="wrap mt-12 flex flex-wrap gap-3">
        <a href="{{ route('home') }}" class="btn btn-primary">Go to the home page</a>
        <a href="{{ tel_href(setting('phone_main')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(setting('phone_main'), true) }}</a>
        <a href="{{ tel_href(setting('ambulance_phone')) }}" class="btn btn-er"><x-icon name="ambulance" class="h-4 w-4" />Ambulance {{ format_phone(setting('ambulance_phone')) }}</a>
    </section>
</x-layouts.site>
