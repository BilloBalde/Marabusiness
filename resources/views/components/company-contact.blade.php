@props(['type' => 'email'])

{{-- A contact link for the legal pages. It shows the real address (config/company.php or
     the contact settings) and, when none is set, points at the contact form rather than
     at a mailbox nobody has created. --}}
@php
    $address = $type === 'dpo' ? \App\Support\Company::dpoEmail() : \App\Support\Company::get('email');
@endphp

@if($address)
    <a href="mailto:{{ $address }}" {{ $attributes }}>{{ $address }}</a>
@else
    <a href="/contact" {{ $attributes }}>notre page de contact</a>
@endif
