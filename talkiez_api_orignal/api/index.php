<?php
require_once 'controllers/DatabaseController.php';
require_once 'controllers/AudioController.php';
require_once 'controllers/AuthController.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Parse the request
$request_method = $_SERVER['REQUEST_METHOD'] ?? '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Initialize controller
$audioController = new AudioController();
$authController = new AuthController();
// Parameter-based routing
switch ($action) {
    case 'sendAudio':
        if ($request_method === 'POST') {
            $audioController->sendAudio();
        } else {
            sendMethodNotAllowed();
        }
        break;

    case 'register':
        if ($request_method === 'POST') {
            // if username then sanitize it
            $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
            $password = isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '';
            if ($username && $password) {
                $authController->register($username, $password);
            } else {
                sendMethodNotAllowed();
            }
        } else {
            sendMethodNotAllowed();
        }
        break;

    case 'login':
        if ($request_method === 'POST') {
            $authController->login();
        } else {
            sendMethodNotAllowed();
        }
        break;

    case 'getUsers':
        if ($request_method === 'GET') {
            $users = $authController->getUsers();
            echo json_encode($users);
        } else {
            sendMethodNotAllowed();
        }
        break;

    case 'getAudioFileList':
        if ($request_method === 'GET') {
            $audioController->getAudioFileList();
        } else {
            sendMethodNotAllowed();
        }
        break;
        
    default:
        http_response_code(200);
        echo json_encode([
            'message' => 'Response from server',
            'status' => 'active',
            'timestamp' => time()
        ]);
        break;
}

function sendMethodNotAllowed() {
    global $request_method, $action;
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed - '.$request_method . ': ' . $action]);
}
?>