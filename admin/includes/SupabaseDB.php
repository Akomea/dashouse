<?php
require_once __DIR__ . '/../config/supabase.php';

class SupabaseDB {
    private $pdo;
    private $supabase_url;
    private $supabase_key;
    private $headers;
    
    public function __construct() {
        $this->supabase_url = SUPABASE_URL;
        $this->supabase_key = SUPABASE_ANON_KEY;
        $this->headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->supabase_key,
            'apikey: ' . $this->supabase_key
        ];
        
        $this->connectDirectDB();
    }
    
    /**
     * Connect directly to PostgreSQL database
     */
    private function connectDirectDB() {
        try {
            $dsn = "pgsql:host=" . SUPABASE_DB_HOST . ";dbname=" . SUPABASE_DB_NAME . ";port=5432";
            $this->pdo = new PDO($dsn, SUPABASE_DB_USER, SUPABASE_DB_PASSWORD);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->pdo = null;
        }
    }
    
    /**
     * Make a REST API call to Supabase
     */
    public function apiCall($endpoint, $method = 'GET', $data = null) {
        $url = $this->supabase_url . '/rest/v1/' . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("Supabase API error: HTTP $httpCode - $response");
            return false;
        }
    }
    
    /**
     * Execute a direct SQL query
     */
    public function query($sql, $params = []) {
        if (!$this->pdo) {
            error_log("No database connection available");
            return false;
        }
        
        try {
            error_log("=== QUERY DEBUG START ===");
            error_log("Original SQL: " . $sql);
            error_log("Original Params: " . json_encode($params));
            error_log("Original Param Types: " . json_encode(array_map('gettype', $params)));
            
            // Fix boolean parameters - convert empty strings and nulls to proper booleans
            $fixedParams = $this->fixBooleanParams($params);
            error_log("Fixed Params: " . json_encode($fixedParams));
            error_log("Fixed Param Types: " . json_encode(array_map('gettype', $fixedParams)));
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameters with explicit types to avoid PDO guessing
            foreach ($fixedParams as $i => $param) {
                $paramType = PDO::PARAM_STR; // Default to string
                
                if (is_bool($param)) {
                    $paramType = PDO::PARAM_BOOL;
                    error_log("Binding param {$i} as BOOLEAN: " . var_export($param, true));
                } elseif (is_int($param)) {
                    $paramType = PDO::PARAM_INT;
                    error_log("Binding param {$i} as INT: " . var_export($param, true));
                } elseif (is_float($param)) {
                    $paramType = PDO::PARAM_STR; // PDO doesn't have PARAM_FLOAT, use string
                    error_log("Binding param {$i} as FLOAT: " . var_export($param, true));
                } else {
                    error_log("Binding param {$i} as STRING: " . var_export($param, true));
                }
                
                $stmt->bindValue($i + 1, $param, $paramType);
            }
            
            error_log("Executing statement...");
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("SQL execution failed: " . json_encode($errorInfo));
                throw new PDOException("SQL execution failed: " . $errorInfo[2]);
            }
            
            error_log("=== QUERY DEBUG END ===");
            return $stmt;
        } catch (PDOException $e) {
            error_log("=== QUERY ERROR DEBUG ===");
            error_log("Database query error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Original Params: " . json_encode($params));
            error_log("Fixed Params: " . json_encode($fixedParams ?? $params));
            error_log("=== QUERY ERROR DEBUG END ===");

            // In development mode, throw the exception to see the real error
            if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Fix boolean parameters to prevent type conversion errors
     */
    private function fixBooleanParams($params) {
        error_log("fixBooleanParams called with: " . json_encode($params));
        
        $fixed = [];
        foreach ($params as $i => $param) {
            if ($param === '') {
                error_log("Converting empty string at position {$i} to null");
                $fixed[] = null; // Convert empty string to null
            } elseif ($param === 'true' || $param === 'false') {
                error_log("Converting string boolean '{$param}' at position {$i} to " . ($param === 'true' ? 'true' : 'false'));
                $fixed[] = ($param === 'true'); // Convert string booleans to actual booleans
            } else {
                $fixed[] = $param; // Keep other values as-is
            }
        }
        
        error_log("fixBooleanParams returning: " . json_encode($fixed));
        return $fixed;
    }
    
    /**
     * Fetch all rows from a query
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }
    
    /**
     * Fetch a single row
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Insert data into a table
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders) RETURNING id";
        $stmt = $this->query($sql, $data);
        
        if ($stmt) {
            $result = $stmt->fetch();
            return $result['id'] ?? true;
        }
        return false;
    }
    
    /**
     * Update data in a table
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = :$column";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
    
    /**
     * Delete from a table
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $sql = "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = :table
        )";
        
        $result = $this->fetch($sql, ['table' => $table]);
        return $result ? (bool)$result['exists'] : false;
    }
    
    /**
     * Get table structure
     */
    public function getTableStructure($table) {
        $sql = "SELECT column_name, data_type, is_nullable, column_default 
                FROM information_schema.columns 
                WHERE table_name = :table 
                ORDER BY ordinal_position";
        
        return $this->fetchAll($sql, ['table' => $table]);
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->pdo = null;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo ? $this->pdo->lastInsertId() : false;
    }
}
?>
