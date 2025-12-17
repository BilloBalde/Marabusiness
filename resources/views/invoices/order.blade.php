@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $isPaid = $order->payment_status === 'paid';

    // ✅ QR as SVG (NO IMAGICK)
    $qrSvg = QrCode::format('svg')
        ->size(150)
        ->generate($order->order_number);

    // ✅ LOGO as base64 (works in PDF)
    $logoPath = public_path('assets/images/logo.png');
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

    // ✅ Safe shipment
    $shipment = $order->shipments()->latest()->first();
@endphp


<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>

    <style>
        body { font-family: DejaVu Sans, sans-serif; margin:0; padding:0; }

        .header {
            background: #f5f6fa;
            padding: 25px 35px;
            border-bottom: 2px solid #ddd;
            display: flex; justify-content: space-between; align-items:center;
        }

        .logo img { height: 70px; }

        .qr img { height: 90px; }

        .section { padding: 30px 35px; }

        h2 { margin: 0 0 10px; font-size: 20px; text-transform: uppercase; }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        th { background: #fafafa; }

        .total-row td { font-size: 16px; font-weight: bold; }

        .watermark {
            position: fixed;
            top: 40%; left: 50%;
            font-size: 100px;
            color: {{ $isPaid ? 'rgba(0,180,0,0.12)' : 'rgba(255,0,0,0.12)' }};
            transform: translate(-50%, -50%) rotate(-25deg);
            z-index: -1;
            font-weight: 900;
        }

        .thumb { height: 45px; border-radius: 5px; }
        .footer { margin-top: 30px; text-align:center; font-size:12px; color:#777; }
    </style>
</head>

<body>

<div class="watermark">
    {{ strtoupper($order->payment_status) }}
</div>

{{-- HEADER --}}

<div class="header" style="padding:25px 35px; background:#f5f6fa; border-bottom:2px solid #ddd;">
    <table width="100%">
        <tr>
            <td width="25%" align="left">
                <img src="{{ $logoBase64 }}" style="height:80px;">
            </td>

            <td width="50%" align="center">
                <h2 style="margin:0;">Invoice #{{ $order->order_number }}</h2>
                <small>{{ $order->created_at->format('Y-m-d') }}</small>
            </td>

            <td width="25%" align="right">
                <div style="width:90px; height:90px;">
                    {!! $qrBase64 !!}
                </div>
            </td>

        </tr>
    </table>
</div>


{{-- CUSTOMER + VENDOR --}}
<div class="section">
    <table>
        <tr>
            <td width="50%">
                <h3>Vendor</h3>
                <strong>{{ $order->vendor->store_name }}</strong><br>
                {{ $order->vendor->address ?? '---' }}<br>
                Phone: {{ $order->vendor->phone ?? '---' }}
            </td>

            <td width="50%">
                <h3>Customer</h3>
                <strong>{{ $order->user->name }}</strong><br>
                {{ $order->user->address ?? '---' }}<br>
                Phone: {{ $order->user->phone ?? '---' }}
            </td>
        </tr>
    </table>
</div>

{{-- SHIPPING SUMMARY --}}
<div class="section">
    <h2>Shipping Summary</h2>

    <table>
        <tr style="text-align: center;">
            <th>Carrier</th>
            <th>Tracking</th>
            <th>Arrival</th>
            <th>Location</th>
            <th>Notes</th>
        </tr>
        <tr style="text-align: center;">
            <td><strong>{{ $order->shipping_carrier }}</strong></td>
            <td>{{ $shipment->tracking_number ?? '---' }}</td>
            <td>{{ $shipment->estimated_delivery_at ?? '---' }}</td>
            <td>{{ $shipment->current_location ?? '---' }}</td>
            <td>{{ $order->notes ?? '---' }}</td>
        </tr>
    </table>
</div>

{{-- ITEMS TABLE --}}
<div class="section">
    <h2>Order Items</h2>

    <table>
        <thead>
            <tr style="text-align: center;">
                <th>Item</th>
                <th>Thumbnail</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach($order->items as $item)
                @php
                    $img = $item->product?->images[0] ?? null;
                    $thumbPath = $img ? public_path('uploads/' . $img) : null;
                    $thumbBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($thumbPath));
                    //echo $thumbPath;
                @endphp

                <tr style="text-align: center;">
                    <td>{{ $item->product?->name }}</td>

                    <td>
                        @if ($thumbPath && file_exists($thumbPath))
                            <img src="{{ $thumbBase64 }}" class="thumb">
                        @else
                            —
                        @endif
                    </td>

                    <td>{{ number_format($item->unit_amount, 2) }}{{ $order->vendor->currency->symbol }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->total_amount, 2) }}{{ $order->vendor->currency->symbol }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Grand Total:</td>
                <td>{{ number_format($order->grand_total, 2) }}{{ $order->vendor->currency->symbol }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Total Paid:</td>
                <td>{{ number_format($order->total_paid, 2) }}{{ $order->vendor->currency->symbol }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Remaining Amount:</td>
                <td>{{ number_format($order->total_remaining, 2) }}{{ $order->vendor->currency->symbol }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- PAYMENT HISTORY --}}
<div class="section">
    <h2>Payment History</h2>

    <table>
        <thead>
        <tr style="text-align: center;">
            <th>Date</th>
            <th>Method</th>
            <th>Status</th>
            <th>Amount</th>
        </tr>
        </thead>

        <tbody>
        @forelse($order->paiements as $p)
            <tr style="text-align: center;">
                <td>{{ $p->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ ucfirst($p->payment_method) }}</td>
                <td>{{ ucfirst($p->payment_status) }}</td>
                <td>{{ number_format($p->amount, 2) }}{{ $order->vendor->currency->symbol }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center;">No payments yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- FOOTER --}}
<div class="footer">
    © {{ date('Y') }} MARA Business — All Rights Reserved<br>
    contact@mara-business.com — www.mara-business.com
</div>

</body>
</html>
