<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class VideoController extends Controller
{
    /**
     * Stream the video in chunks for better performance
     */
    public function streamVideo(Request $request)
    {
        $videoPath = 'demo_fadogen.mp4';

        if (! Storage::exists($videoPath)) {
            abort(404, 'Video not found');
        }

        $mimeType = 'video/mp4';
        $size = Storage::size($videoPath);
        $stream = Storage::readStream($videoPath);

        $start = 0;
        $end = $size - 1;
        $status = 200;

        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $size,
            'Cache-Control' => 'public, max-age=31536000',
        ];

        // Handle range requests for better streaming
        if ($request->header('Range')) {
            $status = 206;
            $range = str_replace('bytes=', '', $request->header('Range'));
            [$start, $end] = explode('-', $range);

            if ($end === '') {
                $end = $size - 1;
            }

            $start = (int) $start;
            $end = (int) $end;

            $length = $end - $start + 1;

            $headers['Content-Length'] = $length;
            $headers['Content-Range'] = "bytes $start-$end/$size";

            // Seek to the requested position
            fseek($stream, $start);
        }

        return response()->stream(
            function () use ($stream, $end, $start) {
                $buffer = 8192;
                $currentPosition = $start;

                while (! feof($stream) && $currentPosition <= $end) {
                    $bytesToRead = min($buffer, $end - $currentPosition + 1);
                    echo fread($stream, $bytesToRead);
                    $currentPosition += $bytesToRead;
                    flush();
                }

                fclose($stream);
            },
            $status,
            $headers
        );
    }
}
