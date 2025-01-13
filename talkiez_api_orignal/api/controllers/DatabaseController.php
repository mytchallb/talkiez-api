<?php
class DatabaseController {
    private $db;
    
    public function __construct() {
        // Create/connect to SQLite database
        $this->db = new SQLite3('../database.sqlite');
        
        // Create tables if they don't exist
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                created DATETIME DEFAULT CURRENT_TIMESTAMP,
                friends TEXT DEFAULT \'\'
            );
            
            CREATE TABLE IF NOT EXISTS audio (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                from_user_id INTEGER,
                to_user_id INTEGER,
                filename TEXT NOT NULL,
                created DATETIME DEFAULT CURRENT_TIMESTAMP,
                size INTEGER,
                can_delete INTEGER DEFAULT 0,
                duration FLOAT,
                FOREIGN KEY (from_user_id) REFERENCES users(id),
                FOREIGN KEY (to_user_id) REFERENCES users(id)
            );
        ');
    }

    // Example method to add a user
    public function register($username, $password) {
        $stmt = $this->db->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
        return $stmt->execute();
    }

    // Example method to save audio record
    public function saveAudioRecord($fromUserId, $toUserId, $filename, $size, $duration) {
        $stmt = $this->db->prepare('
            INSERT INTO audio (from_user_id, to_user_id, filename, size, duration) 
            VALUES (:from, :to, :filename, :size, :duration)
        ');
        $stmt->bindValue(':from', $fromUserId, SQLITE3_INTEGER);
        $stmt->bindValue(':to', $toUserId, SQLITE3_INTEGER);
        $stmt->bindValue(':filename', $filename, SQLITE3_TEXT);
        $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
        $stmt->bindValue(':duration', $duration, SQLITE3_FLOAT);
        return $stmt->execute();
    }

    // Get all audio records sent to a user
    public function getUserAudioReceived($userId) {
        $stmt = $this->db->prepare('
            SELECT a.*, u.username as from_username 
            FROM audio a
            JOIN users u ON a.from_user_id = u.id
            WHERE a.to_user_id = :userId
            ORDER BY a.created DESC
        ');
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $audio = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $audio[] = $row;
        }

        // Update can_delete to 1 since we've read the audio
        $stmt = $this->db->prepare('UPDATE audio SET can_delete = 1 WHERE to_user_id = :userId');
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->execute();

        return $audio;
    }

    // Add a friend to user's friend list
    public function addFriend($userId, $friendId) {
        // Get current friends list
        $stmt = $this->db->prepare('SELECT friends FROM users WHERE id = :userId');
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        // Convert current friends string to array, add new friend, remove duplicates
        $friends = $row['friends'] ? explode(',', $row['friends']) : [];
        $friends[] = $friendId;
        $friends = array_unique($friends);
        $newFriendsList = implode(',', $friends);
        
        // Update friends list
        $stmt = $this->db->prepare('UPDATE users SET friends = :friends WHERE id = :userId');
        $stmt->bindValue(':friends', $newFriendsList, SQLITE3_TEXT);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    // Remove a friend from user's friend list
    public function removeFriend($userId, $friendId) {
        // Get current friends list
        $stmt = $this->db->prepare('SELECT friends FROM users WHERE id = :userId');
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        // Convert to array, remove friend, and convert back to string
        $friends = $row['friends'] ? explode(',', $row['friends']) : [];
        $friends = array_diff($friends, [$friendId]);
        $newFriendsList = implode(',', $friends);
        
        // Update friends list
        $stmt = $this->db->prepare('UPDATE users SET friends = :friends WHERE id = :userId');
        $stmt->bindValue(':friends', $newFriendsList, SQLITE3_TEXT);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    public function getUsers() {
        $stmt = $this->db->prepare('SELECT * FROM users');
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        return $users;
    }
}




