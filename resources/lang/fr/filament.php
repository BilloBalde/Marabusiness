<?php

return [
    'nav' => [
        'brands' => 'Marques',
        'categories' => 'Categories',
        'dashboard' => 'Tableau de bord',
        'contacts' => 'Contacts',
        'currencies' => 'Monnaies',
        'orders' => 'Commandes',
        'paiements' => 'Paiements',
        'products' => 'Produits',
        'projects' => 'Projets',
        'purchases' => 'Achats',
        'realisations' => 'Realisations',
        'services' => 'Services',
        'shipments' => 'Logistiques',
        'suppliers' => 'Fournisseurs',
        'users' => 'Utilisateurs',
        'vendors' => 'Boutiques',
        'customers' => 'Clients',
        'settings' => 'Paramètres',
        'pos' => 'PDV',
        'chat' => 'Conversations',
    ],

    'groups' => [
        'extras' => 'Extras',
        'sales' => 'Ventes',
        'civil_eng' => 'Genie Civil',
        'catalog' => 'Catalogue',
        'configuration' => 'Configuration',
    ],

    // Onglets de la liste des commandes. « negotiating » manquait en même temps
    // que le statut lui-même : une commande en discussion n'apparaissait que sous
    // « Toutes », si bien que la pastille de navigation comptait des clients en
    // attente d'un prix sans aucune liste vers laquelle cliquer.
    'order_tabs' => [
        'all' => 'Toutes',
        'negotiating' => 'Négociations',
        'new' => 'Nouvelles',
        'processing' => 'En préparation',
        'shipped' => 'Expédiées',
        'delivered' => 'Livrées',
        'cancelled' => 'Annulées',
    ],
];
