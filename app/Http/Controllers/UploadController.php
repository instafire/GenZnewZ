<?php

namespace App\Http\Controllers;

use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    /**
     * Proxy upload to PixelDrain
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400|mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,avi,pdf,doc,docx,txt',
        ]);

        $file = $request->file('file');
        $apiKey = env('PIXELDRAIN_API_KEY');

        if (!$apiKey) {
            Log::error('PixelDrain API key is not configured');
            return response()->json([
                'success' => false,
                'message' => 'Upload service is not configured.',
            ], 500);
        }

        $stream = fopen($file->getRealPath(), 'rb');
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientOriginalName()) ?: 'upload.bin';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode(':' . $apiKey),
            ])->timeout(120)
                ->withBody($stream, $file->getMimeType() ?: 'application/octet-stream')
                ->put('https://pixeldrain.com/api/file/' . urlencode($safeName));

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'id' => $data['id'],
                    'url' => 'https://pixeldrain.com/u/' . $data['id'],
                    'name' => $safeName,
                    'size' => $file->getSize(),
                ]);
            }

            Log::error('PixelDrain upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $response->body(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('PixelDrain upload exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Get upload page
     */
    public function index()
    {
        return Theme::scope('templates.upload')->render();
    }
}
