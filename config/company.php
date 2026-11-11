<?php

/*
|--------------------------------------------------------------------------
| Who operates the site
|--------------------------------------------------------------------------
|
| The legal identity printed in the legal notice, the terms, the privacy policy and
| every page that shows a contact. There are no defaults on purpose: these pages used
| to carry an invented company — a registration number, a share capital, an address in
| Dakar, a "publication director", a lawyer and a data-protection officer with names and
| @marabusiness.com mailboxes — and a legal notice with made-up identifiers is worse than
| one that is incomplete. A line whose value is empty is simply left out of the page.
|
| Fill these in the .env file (see .env.example). The phone, e-mail and address also fall
| back to the contact details kept in the admin panel (Settings > Site Settings).
*/

return [
    // Registered name, for example "MARA BUSINESS SARL"
    'name' => env('COMPANY_NAME'),
    // SARL, SA, SAS, entreprise individuelle...
    'legal_form' => env('COMPANY_LEGAL_FORM'),
    'capital' => env('COMPANY_CAPITAL'),
    // Trade register number (RCCM)
    'registration' => env('COMPANY_REGISTRATION'),
    // Tax identification number (NIF / NINEA)
    'tax_id' => env('COMPANY_TAX_ID'),
    // Registered office
    'address' => env('COMPANY_ADDRESS'),
    'phone' => env('COMPANY_PHONE'),
    // Single mailbox for legal, privacy, security and press requests
    'email' => env('COMPANY_EMAIL'),
    // Data-protection contact, when different from the address above
    'dpo_email' => env('COMPANY_DPO_EMAIL'),
    // Legal representative of the company (manager, chairman...)
    'legal_representative' => env('COMPANY_LEGAL_REPRESENTATIVE'),
    // Name of the person responsible for the publication
    'publication_director' => env('COMPANY_PUBLICATION_DIRECTOR'),
    // Hosting provider named in the legal notice
    'host_name' => env('COMPANY_HOST_NAME'),
    'host_address' => env('COMPANY_HOST_ADDRESS'),
];
