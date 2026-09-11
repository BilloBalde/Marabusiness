<?php

return [
    'nav' => [
        'brands' => 'Brands',
        'categories' => 'Categories',
        'dashboard' => 'Dashboard',
        'currencies' => 'Currencies',
        'contacts' => 'Contacts',
        'orders' => 'Orders',
        'paiements' => 'Payments',
        'products' => 'Products',
        'projects' => 'Projects',
        'purchases' => 'Purchases',
        'realisations' => 'Realisations',
        'services' => 'Services',    
        'shipments' => 'Logistics',
        'suppliers' => 'Suppliers',
        'users' => 'Users',
        'vendors' => 'Vendors',
        'customers' => 'Customers',
        'settings' => 'Settings',
        'pos' => 'POS',
        'chat' => 'Chats',
    ],

    'groups' => [
        'extras' => 'Extras',
        'sales' => 'Sales',
        'civil_eng' => 'Civil Eng',
        'catalog' => 'Catalog',
        'configuration' => 'Configuration',
    ],

    // Tabs on the orders list. 'negotiating' was missing along with the status
    // itself: an order being haggled over appeared under "All" and nowhere else,
    // so the navigation badge counted buyers waiting on a price with no list to
    // click through to.
    'order_tabs' => [
        'all' => 'All',
        'negotiating' => 'Negotiations',
        'new' => 'New',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ],
];
