<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AudioService;
use App\Models\Transmissions;

class TransmissionController extends Controller
{
    protected $audioService;
    
    public function __construct(AudioService $audioService)
    {
        $this->audioService = $audioService;
    }

    public function sendTransmission(Request $request)
    {
        // Validate the request
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        // origin is also a value that might be supplied
        $origin = $request->input('origin', 'web');

        // Check if the request is a form upload or raw data
        $audioData = null;
        $contentType = 'audio/wav'; // default content type
        
        if ($request->hasFile('audio')) {
            // Handle form-data file upload (e.g., from Postman or web)
            $audioFile = $request->file('audio');
            $audioData = file_get_contents($audioFile->path());
            $contentType = $audioFile->getMimeType(); // Get the actual mime type
        } else {
            // Handle raw data (e.g., from iOS app)
            $audioData = $request->getContent();
        }
        
        // Check if audio data is empty
        if (empty($audioData)) {
            return response()->json(['error' => 'No audio data received'], 400);
        }

        // Store the audio file using the service, passing the content type
        $filename = $this->audioService->store($audioData, $request->user()->id, $origin, $contentType);
        
        if (!$filename) {
            return response()->json(['error' => 'Failed to store audio file'], 500);
        }

        // Create the transmission record
        $transmission = Transmissions::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'filename' => $filename,
            'status' => 'pending'
        ]);
        
        return response()->json([
            'message' => 'Transmission sent',
            'transmission' => $transmission
        ]);
    }

    public function getTransmissions(Request $request)
    {
        $transmissions = Transmissions::with('sender')
            ->where('receiver_id', $request->user()->id)
            ->where('status', '!=', 'listened')
            ->latest()
            ->get()
            ->map(function ($transmission) {
                $transmission->audio_url = asset('storage/audio/' . $transmission->filename);
                return $transmission;
            });
        
        return response()->json([
            'success' => true,
            'transmissions' => $transmissions
        ]);
    }

    public function listenToTransmission(Request $request)
    {
        // Find and validate the transmission
        $transmission = Transmissions::findOrFail($request->transmission_id);
        
        // Check if user is authorized to listen
        if ($transmission->receiver_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if the file exists
        $path = storage_path('app/public/audio/' . $transmission->filename);
        if (!file_exists($path)) {
            return response()->json(['error' => 'Audio file not found'], 404);
        }

        // Mark as listened <- Hide this for testing
        $transmission->status = 'listened';
        $transmission->save();

        // Determine content type based on file extension
        $contentType = str_ends_with($transmission->filename, '.mp3') ? 'audio/mpeg' : 'audio/wav';

        // Return the file as a stream with appropriate headers
        return response()->file($path, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . $transmission->filename . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }
}
