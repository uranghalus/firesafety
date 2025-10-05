<?php

namespace App\Http\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\QrCode as QrCodeModel;

class QrController extends Controller
{
    //
    public function index()
    {
        $items = QrCodeModel::latest()->paginate(20);
        return inertia('QRCodes/Index', compact('items'));
    }
    public function store(Request $request)
    {
        $request->validate(['title' => 'nullable|string']);
        $qr = QrCodeModel::create(['title' => $request->title, 'created_by' => auth()->id(),]);
        return redirect()->route('qrcodes.index')->with('success', 'QR generated');
    }
    public function showImage($id)
    {
        $qr = QrCodeModel::findOrFail($id);
        $uploadUrl = route('upload.show', ['token' => $qr->token]);
        $result = Builder::create()->writer(new PngWriter())->data($uploadUrl)->encoding(new Encoding('UTF-8'))->errorCorrectionLevel(new ErrorCorrectionLevelHigh())->size(300)->margin(10)->roundBlockSizeMode(new RoundBlockSizeModeMargin())->build();
        return response($result->getString())->header('Content-Type', $result->getMimeType());
    }
    public function pdf($id)
    {
        $qr = QrCodeModel::findOrFail($id);
        $uploadUrl = route('upload.show', ['token' => $qr->token]); // generate QR PNG as base64 string $result = Builder::create() ->writer(new PngWriter()) ->data($uploadUrl) ->encoding(new Encoding('UTF-8')) ->errorCorrectionLevel(new ErrorCorrectionLevelHigh()) ->size(300) ->margin(10) ->build(); $qrImage = 'data:'.$result->getMimeType().';base64,'.base64_encode($result->getString()); $view = view('qrcodes.print', compact('qr', 'qrImage'))->render(); $pdf = Pdf::loadHTML($view)->setPaper('A4', 'portrait'); return $pdf->download("qrcode-{$qr->token}.pdf"); }
    }
}
