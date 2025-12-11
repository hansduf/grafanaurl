<?php
class ChannelModel {
    private $config;
    private $connection;
    private $dbDriver;

    public function __construct($config) {
        $this->config = $config;
        $this->dbDriver = getenv('DB_DRIVER') ?: 'mysql';
        
        if ($this->dbDriver === 'mysql') {
            $this->initMysql();
        } else {
            die('Database driver not supported: ' . $this->dbDriver);
        }
    }

    /**
     * Initialize MySQL connection
     */
    private function initMysql() {
        $host = getenv('MYSQL_HOST') ?: 'localhost';
        $port = getenv('MYSQL_PORT') ?: 3306;
        $database = getenv('MYSQL_DATABASE') ?: 'grafana';
        $username = getenv('MYSQL_USERNAME') ?: 'root';
        $password = getenv('MYSQL_PASSWORD') ?: '';

        $this->connection = new mysqli($host, $username, $password, $database, $port);
        
        if ($this->connection->connect_error) {
            die('Database connection failed: ' . $this->connection->connect_error);
        }
        
        // Set charset to UTF-8
        $this->connection->set_charset('utf8mb4');
    }

    /**
     * Execute query and return result
     */
    private function query($sql, $types = '', $params = []) {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            error_log('Query prepare error: ' . $this->connection->error . ' | SQL: ' . $sql);
            return null;
        }

        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log('Query execute error: ' . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    // ============ CHANNELS METHODS ============

    /**
     * Get all channels with their current media
     */
    public function getAllChannels() {
        $sql = "SELECT c.*, m.id as media_id, m.filename, m.mime_type 
                FROM channels c 
                LEFT JOIN media m ON c.current_media_id = m.id 
                ORDER BY c.created_at DESC";
        
        $result = $this->query($sql);
        if (!$result) return [];
        
        $channels = [];
        while ($row = $result->fetch_assoc()) {
            $channels[] = $row;
        }
        return $channels;
    }

    /**
     * Get single channel by name with media details
     */
    public function getChannel($name) {
        $sql = "SELECT c.*, m.id as media_id, m.filename, m.mime_type 
                FROM channels c 
                LEFT JOIN media m ON c.current_media_id = m.id 
                WHERE c.name = ?";
        
        $result = $this->query($sql, 's', [$name]);
        if (!$result) return null;
        
        $row = $result->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Create new channel
     */
    public function createChannel($name, $description = '') {
        // Check if exists
        if ($this->getChannel($name)) {
            error_log('Channel already exists: ' . $name);
            return false;
        }

        $sql = "INSERT INTO channels (name, description, current_media_id, created_at) 
                VALUES (?, ?, NULL, NOW())";
        
        $result = $this->query($sql, 'ss', [$name, $description]);
        return $result !== null;
    }

    /**
     * Update channel (description only)
     */
    public function updateChannel($name, $description) {
        $sql = "UPDATE channels SET description = ? WHERE name = ?";
        $result = $this->query($sql, 'ss', [$description, $name]);
        return $result !== null;
    }

    /**
     * Delete channel (hard delete)
     */
    public function deleteChannel($name) {
        $channel = $this->getChannel($name);
        if (!$channel) return false;

        $sql = "DELETE FROM channels WHERE name = ?";
        $result = $this->query($sql, 's', [$name]);
        return $result !== null;
    }

    /**
     * Set/link media to channel (set as current/active)
     */
    public function setChannelMedia($channelName, $mediaId) {
        $sql = "UPDATE channels SET current_media_id = ? WHERE name = ?";
        $result = $this->query($sql, 'is', [$mediaId, $channelName]);
        return $result !== null;
    }

    // ============ MEDIA METHODS ============

    /**
     * Get all media (library) with pagination
     */
    public function getAllMedia($limit = 50, $offset = 0) {
        $sql = "SELECT * FROM media ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) return [];
        
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $media = [];
        while ($row = $result->fetch_assoc()) {
            $media[] = $row;
        }
        $stmt->close();
        return $media;
    }
    
    /**
     * Get total media count
     */
    public function getMediaCount() {
        $sql = "SELECT COUNT(*) as count FROM media";
        $result = $this->query($sql);
        if (!$result) return 0;
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }

    /**
     * Get single media by ID
     */
    public function getMedia($id) {
        $sql = "SELECT * FROM media WHERE id = ?";
        
        $result = $this->query($sql, 'i', [$id]);
        if (!$result) return null;
        
        $row = $result->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Get media by filename
     */
    public function getMediaByFilename($filename) {
        $sql = "SELECT * FROM media WHERE filename = ?";
        
        $result = $this->query($sql, 's', [$filename]);
        if (!$result) return null;
        
        $row = $result->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Upload/create media record
     */
    public function uploadMedia($filename, $mimeType) {
        $sql = "INSERT INTO media (filename, mime_type, created_at) VALUES (?, ?, NOW())";
        $result = $this->query($sql, 'ss', [$filename, $mimeType]);
        
        if ($result !== null) {
            return $this->connection->insert_id;
        }
        return false;
    }

    /**
     * Delete media (hard delete)
     */
    public function deleteMedia($id) {
        $sql = "DELETE FROM media WHERE id = ?";
        $result = $this->query($sql, 'i', [$id]);
        return $result !== null;
    }

    /**
     * Get all channels using specific media
     */
    public function getChannelsUsingMedia($mediaId) {
        $sql = "SELECT * FROM channels WHERE current_media_id = ?";
        
        $result = $this->query($sql, 'i', [$mediaId]);
        if (!$result) return [];
        
        $channels = [];
        while ($row = $result->fetch_assoc()) {
            $channels[] = $row;
        }
        return $channels;
    }

    /**
     * Sanitize channel name (alphanumeric, underscore, hyphen)
     */
    public function sanitizeChannel($name) {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $name);
    }
}