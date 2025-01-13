<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AudioService
{
    public function store($audioData, $userId, $origin, $contentType = 'audio/wav'): ?string
    {
        if (empty($audioData)) {
            return null;
        }
        
        // Determine file extension based on origin and content type
        $extension = ($origin === 'web' && $contentType === 'audio/mpeg') ? 'mp3' : 'wav';
        $filename = 'recording_' . $userId . '_' . time() . '.' . $extension;
        
        // Handle web-based audio
        if ($origin === 'web') {
            Storage::disk('public')->put("audio/{$filename}", $audioData);
        }
        // Handle iOS raw audio data
        else if ($origin === 'ios') {
            $header = $this->createWavHeader(strlen($audioData));
            Storage::disk('public')->put(
                "audio/{$filename}", 
                $header . $audioData
            );
        }
        
        Log::info("Audio file saved: {$filename} from {$origin}");
        
        return $filename;
    }

    public function getFileList()
    {
        $files = Storage::disk('public')->files('audio');
        
        return collect($files)->map(function($file) {
            return [
                'name' => basename($file),
                'size' => Storage::disk('public')->size($file),
                'created' => Storage::disk('public')->lastModified($file),
                'type' => pathinfo($file, PATHINFO_EXTENSION)
            ];
        });
    }

    private function createWavHeader($data_size) {
      $header = "RIFF";
      $header .= pack('V', $data_size + 36);
      $header .= "WAVE";
      $header .= "fmt ";
      $header .= pack('V', 16);
      $header .= pack('v', 3);
      $header .= pack('v', 1);
      $header .= pack('V', 44100);
      $header .= pack('V', 44100 * 4);
      $header .= pack('v', 4);
      $header .= pack('v', 32);
      $header .= "data";
      $header .= pack('V', $data_size);
      return $header;
  }
}