<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function preview(Order $order)
    {
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
        ->size(120)
        ->generate($order->order_number);

        // Convert SVG → PNG → base64 (GD only)
        return view('invoices.order', [
            'order' => $order,
            'qrBase64'   => $qrSvg,
            'payments' => $order->paiements,
        ]);
    }

    public function download(Order $order)
    {
        // Generate QR SVG
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(200)
            ->generate($order->order_number);

        // Convert SVG → PNG → base64 (GD only)

        $pdf = Pdf::loadView('invoices.order', [
            'order' => $order,
            'qrBase64'   => $qrSvg,
            'payments' => $order->paiements,
            'downloadMode' => true, 
        ])->setPaper('a4');

        return $pdf->download("Invoice-{$order->order_number}.pdf");
    }

    function svgToPngBase64($svgContent, $scale = 4)
{
    // Extract viewBox width & height
    preg_match('/viewBox="0 0 (\d+) (\d+)"/', $svgContent, $m);
    $size = $m ? (int)$m[1] : 120;

    $pngSize = $size * $scale;

    // Create GD canvas
    $img = imagecreatetruecolor($pngSize, $pngSize);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);

    // Load SVG as XML
    $svg = simplexml_load_string($svgContent);

    foreach ($svg->path as $path) {
        $d = (string)$path['d']; // example: M0 0h1v1H0zM1 0h1v1H1z

        preg_match_all('/M(\d+) (\d+)h(\d+)v(\d+)H(\d+)/', $d, $blocks, PREG_SET_ORDER);

        foreach ($blocks as $b) {
            $x = $b[1] * $scale;
            $y = $b[2] * $scale;
            $w = $b[3] * $scale;
            $h = $b[4] * $scale;

            imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $black);
        }
    }

    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();

    return 'data:image/png;base64,' . base64_encode($pngData);
}


}
