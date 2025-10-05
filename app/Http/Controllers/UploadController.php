<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{

    public function show($token)
    {
        $qr = QrCode::where('token', $token)->firstOrFail();
        // Could validate expires_at here
        return inertia('Upload/Show', ['token' => $token, 'qr' => $qr]);
    }

    public function uploadPhoto(Request $r, $token)
    {
        $qr = QrCode::where('token', $token)->firstOrFail();
        // Accept a blob sent as form-data file 'photo' OR base64 payload 'photoBase64'
        if ($r->hasFile('photo')) {
            $file = $r->file('photo');
            $path = $file->store("uploads/qrcodes/{$qr->id}", 'public');
        } else {
            $r->validate(['photoBase64' => 'required|string']);
            $data = $r->photoBase64;
            // data:image/png;base64,xxx
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = $type[1]; // png, jpeg, etc
                $data = base64_decode($data);
                $filename = time() . '.' . $type;
                $path = "uploads/qrcodes/{$qr->id}/{$filename}";
                Storage::disk('public')->put($path, $data);
            } else {
                return response()->json(['error' => 'Invalid image data'], 422);
            }
        }

        // Store a DB record if you have a Photo model (omitted here)
        // Optional: capture client metadata
        $meta = [
            'ip' => $r->ip(),
            'ua' => $r->userAgent(),
            'time' => now()->toDateTimeString(),
        ];
        $qr->update(['meta' => array_merge($qr->meta ?? [], ['last_upload' => $meta])]);

        return response()->json(['ok' => true, 'path' => Storage::url($path)]);
    }
}
