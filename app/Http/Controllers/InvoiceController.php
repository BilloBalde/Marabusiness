<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\URL;

class InvoiceController extends Controller
{
    /**
     * The route only requires being logged in; this is the actual check that the
     * signed-in user has a legitimate reason to see THIS order's invoice — its buyer,
     * the vendor who has to fulfill it, or an admin. Without it, any authenticated
     * account (or, before the route also gained ->middleware('auth'), literally
     * anyone) could read another customer's name, address, phone and full order
     * total just by changing the id in the URL.
     */
    private function authorizeOrder(Order $order): void
    {
        $user = auth()->user();

        $isOwner  = $order->user_id === $user->id;
        $isVendor = $user->vendor && $order->vendor_id === $user->vendor->id;
        $isAdmin  = $user->hasAnyRole(['admin', 'manager']);

        abort_unless($isOwner || $isVendor || $isAdmin, 403);
    }

    /**
     * simplesoftwareio/simple-qrcode's own PNG writer hard-requires the Imagick
     * extension, which isn't installed here. Its SVG writer works fine in a
     * browser, but the nested <g transform="scale(...)"><g transform="translate(...)">
     * groups it emits render as a blank box in dompdf — confirmed by isolating
     * the raw SVG in its own PDF; stripping the XML prolog alone didn't fix it
     * either. Drawing straight from BaconQrCode's own module matrix with GD
     * (already used elsewhere on this server) sidesteps both problems.
     */
    private function generateQrPngBase64(string $content): string
    {
        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M())->getMatrix();
        $moduleCount = $matrix->getWidth();

        $margin = 2;
        $pixelsPerModule = 10;
        $canvasSize = ($moduleCount + $margin * 2) * $pixelsPerModule;

        $image = imagecreatetruecolor($canvasSize, $canvasSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y)) {
                    imagefilledrectangle(
                        $image,
                        ($x + $margin) * $pixelsPerModule,
                        ($y + $margin) * $pixelsPerModule,
                        ($x + $margin + 1) * $pixelsPerModule - 1,
                        ($y + $margin + 1) * $pixelsPerModule - 1,
                        $black
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * The QR used to just encode the order number as plain text — scanning it
     * showed a phone the string "INV2026090019" and nothing else. What makes a
     * scan actually open the invoice is a URL, and since whoever scans it is
     * almost never logged into the site on that phone, the link can't go
     * through the normal auth+ownership-checked routes. A signed URL is the
     * standard way to hand out a no-login, resource-specific link: it 403s for
     * every id except the one it was minted for, without needing a session.
     */
    private function scanUrl(Order $order): string
    {
        return URL::signedRoute('orders.invoice.scan', ['order' => $order->id]);
    }

    public function preview(Order $order)
    {
        $this->authorizeOrder($order);

        return view('invoices.order', [
            'order' => $order,
            'qrBase64' => $this->generateQrPngBase64($this->scanUrl($order)),
            'payments' => $order->paiements,
        ]);
    }

    public function download(Order $order)
    {
        $this->authorizeOrder($order);

        $pdf = Pdf::loadView('invoices.order', [
            'order' => $order,
            'qrBase64' => $this->generateQrPngBase64($this->scanUrl($order)),
            'payments' => $order->paiements,
            'downloadMode' => true,
        ])->setPaper('a4');

        return $pdf->download("Facture-{$order->order_number}.pdf");
    }

    /**
     * What the QR code actually points to. The 'signed' middleware on this
     * route has already done the access check by the time we get here — it
     * either had the exact signature scanUrl() generated for this order, or
     * the request never reached this method.
     */
    public function scan(Order $order)
    {
        return view('invoices.order', [
            'order' => $order,
            'qrBase64' => $this->generateQrPngBase64($this->scanUrl($order)),
            'payments' => $order->paiements,
        ]);
    }
}
