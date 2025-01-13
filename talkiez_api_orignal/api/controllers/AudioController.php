<?php
class AudioController {
    private $upload_dir = '../audio/';
    private $log_file = 'audio_upload.log';
    
    public function __construct() {
        // Ensure upload directory exists
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    public function readAudioFile($filename) {
        $full_path = $this->upload_dir . $filename;
        if (file_exists($full_path)) {
            return file_get_contents($full_path);
        }
        return null;
    }

    public function sendAudio() {
        // Get the raw POST data
        $audio_data = file_get_contents('php://input');
        
        // Log request details
        $this->logMessage("Received request. Data size: " . strlen($audio_data) . " bytes");
        
        if (empty($audio_data)) {
            $this->sendError(400, 'No audio data received');
            return;
        }
        
        $filename = $this->createWavFile($audio_data);
        
        if ($filename === false) {
            $this->sendError(500, 'Failed to save audio file');
            return;
        }
        
        $this->logMessage("Success: Saved file $filename");
        echo json_encode([
            'success' => true,
            'filename' => $filename
        ]);
    }
    
    public function getAudioFileList() {
        $files = glob($this->upload_dir . '*.*');
        $file_list = array_map(function($file) {
            return [
                'name' => basename($file),
                'size' => filesize($file),
                'created' => filemtime($file),
                'type' => pathinfo($file, PATHINFO_EXTENSION)
            ];
        }, $files);
        
        echo json_encode([
            'success' => true,
            'files' => $file_list
        ]);
    }
    
    private function createWavFile($audio_data) {
        $data_size = strlen($audio_data);
        $header = $this->createWavHeader($data_size);
        $filename = 'recording_' . time() . '.wav';
        $full_path = $this->upload_dir . $filename;
        
        if (file_put_contents($full_path, $header . $audio_data) === false) {
            return false;
        }
        
        return $filename;
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
    
    private function logMessage($message) {
        $log_entry = date('[Y-m-d H:i:s] ') . $message . "\n";
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }
    
    private function sendError($code, $message) {
        http_response_code($code);
        $this->logMessage("Error: $message");
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
    }
}
?>