@php
    $isPaid = $order->payment_status === 'paid';

    $statusLabels = [
        'paid'     => 'Payée',
        'pending'  => 'En attente',
        'partial'  => 'Partiellement payée',
        'refunded' => 'Remboursée',
        'failed'   => 'Échouée',
    ];
    $statusLabel = $statusLabels[$order->payment_status] ?? ucfirst($order->payment_status);

    // Libellé court pour le tampon rond en fond de page — un vrai tampon dit
    // « PAYÉ », jamais « Partiellement payée » en toutes lettres.
    $stampLabels = [
        'paid'     => 'PAYÉ',
        'pending'  => 'ATTENTE',
        'partial'  => 'PARTIEL',
        'refunded' => 'REMBOURSÉ',
        'failed'   => 'ÉCHOUÉ',
    ];
    $stampLabel = $stampLabels[$order->payment_status] ?? strtoupper($statusLabel);

    $statusColors = [
        'paid'     => ['text' => '#1B7A4B', 'bg' => '#E8F5EC'],
        'pending'  => ['text' => '#B45309', 'bg' => '#FCF0DE'],
        'partial'  => ['text' => '#B45309', 'bg' => '#FCF0DE'],
        'refunded' => ['text' => '#6B6560', 'bg' => '#F1EEE7'],
        'failed'   => ['text' => '#B91C1C', 'bg' => '#FBEAEA'],
    ];
    $statusColor = $statusColors[$order->payment_status] ?? $statusColors['failed'];

    $paymentMethodLabels = [
        'stripe'   => 'Carte bancaire',
        'lengopay' => 'LengoPay',
        'om'       => 'Orange Money',
        'cod'      => 'Paiement à la livraison',
        'cash'     => 'Comptant',
    ];

    // Mêmes libellés que resources/views/livewire/order-detail-page.blade.php,
    // où shipping_carrier est stocké comme code brut ('dhl', 'local', ...).
    $carrierLabels = [
        'local'  => 'Livraison locale (48h)',
        'chrono' => 'Chronopost',
        'dhl'    => 'DHL Express',
        'ups'    => 'UPS',
        'fedex'  => 'FedEx',
        'other'  => 'Autre transporteur',
    ];

    // Logo en base64 : nécessaire pour s'afficher dans le PDF (dompdf ne va pas
    // chercher les fichiers via une URL relative).
    $logoPath = public_path('assets/images/logo.png');
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

    $shipment = $order->shipments()->latest()->first();

    // Les commandes d'avant la scission du panier par vendeur (mai–nov. 2025)
    // n'ont aucun vendor_id : lire ->store_name / ->currency dessus levait une
    // ErrorException (donc un 500) pour toute facture de ces commandes.
    $vendor = $order->vendor;
    $currencySymbol = $vendor?->currency?->symbol ?? '$';

    $hasDiscount = $order->pre_negotiation_total && $order->pre_negotiation_total > $order->grand_total;
    $isSettled = (float) $order->total_remaining <= 0;
@endphp


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $order->order_number }}</title>

    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #262220;
            font-size: 13px;
        }

        .topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 7px;
            background: #C9A227;
        }

        .stamp {
            position: fixed;
            top: 46%; left: 78%;
            width: 190px;
            height: 190px;
            margin: 0;
            transform: translate(-50%, -50%) rotate(-18deg);
            border: 5px double {{ $statusColor['text'] }};
            border-radius: 50%;
            z-index: -1;
            text-align: center;
            line-height: 190px;
            font-family: 'DejaVu Serif', serif;
            font-weight: bold;
            font-size: 26px;
            letter-spacing: 1px;
            color: {{ $statusColor['text'] }};
            opacity: 0.4;
        }

        .header {
            background: #FBF7EE;
            padding: 22px 35px;
            border-bottom: 3px solid #C9A227;
        }

        .brand-eyebrow {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #96731A;
        }

        .brand-title {
            margin: 4px 0 6px;
            font-family: 'DejaVu Serif', serif;
            font-size: 19px;
            font-weight: bold;
            color: #262220;
        }

        .brand-date {
            font-size: 12px;
            color: #7A7466;
        }

        .status-pill {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: {{ $statusColor['text'] }};
            background: {{ $statusColor['bg'] }};
            border: 1px solid {{ $statusColor['text'] }};
        }

        .qr-card {
            display: inline-block;
            padding: 8px;
            background: #fff;
            border: 1px solid #E8E0CC;
            border-radius: 6px;
        }

        .section { padding: 19px 35px; page-break-inside: avoid; }

        .section-title {
            margin: 0 0 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #C9A227;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #96731A;
        }

        .card-label {
            margin: 0 0 4px;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #96731A;
        }

        .card {
            background: #FBF7EE;
            border-left: 4px solid #C9A227;
            padding: 14px 18px;
            vertical-align: top;
            font-size: 12.5px;
            line-height: 1.5;
        }

        .card strong { font-size: 14px; }

        table.data { width: 100%; border-collapse: collapse; }

        table.data th {
            padding: 9px 10px;
            font-size: 10.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #7A7466;
            background: #F3EFE3;
            border-bottom: 2px solid #E8E0CC;
        }

        table.data td {
            padding: 10px;
            font-size: 12.5px;
            border-bottom: 1px solid #EFEAE0;
        }

        table.data tbody tr:nth-child(even) td { background: #FCFAF5; }

        .num { text-align: right; }
        .ctr { text-align: center; }

        .thumb { height: 42px; border-radius: 4px; }

        table.totals { width: 62%; margin: 16px 0 0 auto; border-collapse: collapse; }
        table.totals td { padding: 7px 4px; font-size: 12.5px; border: none; }
        table.totals .label { color: #7A7466; text-align: right; padding-right: 14px; }
        table.totals .value { text-align: right; white-space: nowrap; }

        .row-original .value { color: #8B8577; text-decoration: line-through; }
        .row-discount .label, .row-discount .value { color: #1B7A4B; font-weight: bold; }

        .row-grand td {
            border-top: 2px solid #C9A227;
            padding-top: 12px;
        }
        .row-grand .label {
            font-family: 'DejaVu Serif', serif;
            font-size: 13px;
            color: #262220;
        }
        .row-grand .value {
            font-family: 'DejaVu Serif', serif;
            font-size: 19px;
            font-weight: bold;
            color: #96731A;
        }

        .row-remaining .value {
            font-weight: bold;
            color: {{ $isSettled ? '#1B7A4B' : '#B45309' }};
        }

        .closing { page-break-inside: avoid; }

        .footer {
            margin-top: 0;
            padding: 14px 35px 18px;
            text-align: center;
            font-size: 11px;
            color: #9B9585;
            border-top: 1px solid #E8E0CC;
        }

        .footer .brand { font-weight: bold; letter-spacing: 1px; color: #7A7466; }
    </style>
</head>

<body>

<div class="topbar"></div>

<div class="stamp">{{ $stampLabel }}</div>

{{-- EN-TÊTE --}}
<div class="header">
    <table width="100%">
        <tr>
            <td width="22%" align="left" style="vertical-align:middle;">
                <img src="{{ $logoBase64 }}" style="height:70px;">
            </td>

            <td width="56%" align="center" style="vertical-align:middle;">
                <p class="brand-eyebrow">Facture</p>
                <p class="brand-title">N° {{ $order->order_number }}</p>
                <span class="brand-date">{{ $order->created_at->format('d/m/Y') }}</span><br>
                <span class="status-pill">{{ $statusLabel }}</span>
            </td>

            <td width="22%" align="right" style="vertical-align:middle;">
                <div class="qr-card">
                    <img src="{{ $qrBase64 }}" width="90" height="90">
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- VENDEUR + CLIENT --}}
<div class="section">
    <table width="100%">
        <tr>
            <td width="48.5%" class="card">
                <p class="card-label">Vendeur</p>
                <strong>{{ $vendor?->store_name ?? config('app.name') }}</strong><br>
                {{ $vendor?->address ?? '---' }}<br>
                {{-- Vendor n'a pas de colonne phone — le vrai numéro vit sur son User.
                     $order->vendor->phone renvoyait toujours null, donc "---" sur
                     toutes les factures jamais générées. --}}
                Tél : {{ $vendor?->user?->phone ?? '---' }}
            </td>

            <td width="3%"></td>

            <td width="48.5%" class="card">
                <p class="card-label">Client</p>
                <strong>{{ $order->user->name }}</strong><br>
                {{ $order->user->address ?? '---' }}<br>
                Tél : {{ $order->user->phone ?? '---' }}
            </td>
        </tr>
    </table>
</div>

{{-- EXPÉDITION --}}
<div class="section">
    <h2 class="section-title">Expédition</h2>

    <table class="data">
        <tr>
            <th>Transporteur</th>
            <th>Suivi</th>
            <th>Arrivée estimée</th>
            <th>Lieu</th>
            <th>Notes</th>
        </tr>
        <tr>
            <td><strong>{{ $carrierLabels[$order->shipping_carrier] ?? $order->shipping_carrier }}</strong></td>
            <td>{{ $shipment->tracking_number ?? '---' }}</td>
            <td>{{ $shipment?->estimated_delivery_at?->format('d/m/Y') ?? '---' }}</td>
            <td>{{ $shipment->current_location ?? '---' }}</td>
            {{-- Checkout writes a technical tag ("Marketplace order — Vendor: …") into notes;
                 it is neither French nor for the customer, and the vendor is already named above. --}}
            <td>{{ ($order->notes && ! str_starts_with($order->notes, 'Marketplace order')) ? $order->notes : '---' }}</td>
        </tr>
    </table>
</div>

{{-- ARTICLES --}}
<div class="section">
    <h2 class="section-title">Articles commandés</h2>

    <table class="data">
        <thead>
            <tr>
                <th>Article</th>
                <th class="ctr">Aperçu</th>
                <th class="num">Prix unitaire</th>
                <th class="ctr">Qté</th>
                <th class="num">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach($order->items as $item)
                @php
                    // file_get_contents() tournait ici, avant le file_exists() de
                    // la ligne suivante : un article sans image donnait
                    // file_get_contents(null) — « ValueError: Path cannot be
                    // empty » — et toute la facture retournait une erreur 500.
                    // Un produit supprimé faisait la même chose, product? étant
                    // alors null. La lecture n'a lieu que si le fichier est là.
                    $img = $item->product?->images[0] ?? null;
                    $thumbPath = $img ? public_path('uploads/' . $img) : null;
                    $thumbBase64 = ($thumbPath && is_file($thumbPath))
                        ? 'data:image/png;base64,' . base64_encode(file_get_contents($thumbPath))
                        : null;
                @endphp

                <tr>
                    <td>{{ $item->product?->name }}</td>

                    <td class="ctr">
                        @if ($thumbBase64)
                            <img src="{{ $thumbBase64 }}" class="thumb">
                        @else
                            —
                        @endif
                    </td>

                    <td class="num">{{ number_format($item->unit_amount, 2) }} {{ $currencySymbol }}</td>
                    <td class="ctr">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format($item->total_amount, 2) }} {{ $currencySymbol }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Un prix négocié : les lignes ci-dessus portent déjà le tarif convenu
         (OrderNegotiation::repriceItems les répartit), si bien que la facture
         n'expliquait pas d'où venait l'écart avec le prix du catalogue. La
         remise est nommée ici, à l'échelle de la commande — c'est à ce niveau
         qu'elle a été accordée. --}}
    <table class="totals">
        @if ($hasDiscount)
            <tr class="row-original">
                <td class="label">Prix initial</td>
                <td class="value">{{ number_format($order->pre_negotiation_total, 2) }} {{ $currencySymbol }}</td>
            </tr>
            <tr class="row-discount">
                <td class="label">Remise négociée</td>
                <td class="value">−{{ number_format($order->pre_negotiation_total - $order->grand_total, 2) }} {{ $currencySymbol }}</td>
            </tr>
        @endif
        <tr class="row-grand">
            <td class="label">Total général</td>
            <td class="value">{{ number_format($order->grand_total, 2) }} {{ $currencySymbol }}</td>
        </tr>
        <tr>
            <td class="label">Total payé</td>
            <td class="value">{{ number_format($order->total_paid, 2) }} {{ $currencySymbol }}</td>
        </tr>
        <tr class="row-remaining">
            <td class="label">Reste à payer</td>
            <td class="value">{{ number_format($order->total_remaining, 2) }} {{ $currencySymbol }}</td>
        </tr>
    </table>
</div>

{{-- PAIEMENTS + PIED DE PAGE
     Gardés ensemble : quand le contenu remplit exactement la première page, le pied de
     page passait seul sur une seconde page presque blanche. Ensemble, c'est l'historique
     des paiements ET le pied de page qui passent à la page suivante, ou aucun. --}}
<div class="closing">
<div class="section">
    <h2 class="section-title">Historique des paiements</h2>

    <table class="data">
        <thead>
        <tr>
            <th>Date</th>
            <th>Méthode</th>
            <th>Statut</th>
            <th class="num">Montant</th>
        </tr>
        </thead>

        <tbody>
        @forelse($order->paiements as $p)
            <tr>
                <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $paymentMethodLabels[$p->payment_method] ?? ucfirst($p->payment_method) }}</td>
                <td>{{ $statusLabels[$p->payment_status] ?? ucfirst($p->payment_status) }}</td>
                <td class="num">{{ number_format($p->amount, 2) }} {{ $currencySymbol }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="ctr">Aucun paiement pour le moment.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- PIED DE PAGE --}}
<div class="footer">
    <span class="brand">{{ config('app.name') }}</span><br>
    © {{ date('Y') }} — Tous droits réservés — {{ config('app.url') }}
</div>
</div>

</body>
</html>
