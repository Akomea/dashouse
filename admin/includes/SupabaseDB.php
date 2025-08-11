<?php
require_once __DIR__ . '/../config/supabase.php';

class SupabaseDB {
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
        
        // Remove direct database connection attempt
        // $this->connectDirectDB();
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL error: " . $curlError);
            return false;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("Supabase API error: HTTP $httpCode - $response");
            return false;
        }
    }
    
    /**
     * Execute a query using REST API (improved SQL parsing for simpler cases)
     * Note: This method should be deprecated in favor of direct REST API calls
     */
    public function query($sql, $params = []) {
        error_log("SupabaseDB::query() called with SQL: " . $sql);
        error_log("SupabaseDB::query() called with params: " . json_encode($params));
        
        // Basic SQL parsing - this is limited and should be replaced with direct API calls
        $sql = trim($sql);
        
        // Simple SELECT queries
        if (preg_match('/^SELECT\s+.*FROM\s+(\w+)/i', $sql, $matches)) {
            $table = $matches[1];
            
            // Build query params for REST API
            $queryParams = [];
            
            // Handle WHERE clauses for simple cases
            if (preg_match('/WHERE\s+(\w+)\s*=\s*:(\w+)/i', $sql, $whereMatches)) {
                $column = $whereMatches[1];
                $paramName = $whereMatches[2];
                if (isset($params[$paramName])) {
                    $queryParams[$column] = 'eq.' . $params[$paramName];
                }
            }
            
            // Handle ORDER BY
            if (preg_match('/ORDER\s+BY\s+([\w\s,]+)/i', $sql, $orderMatches)) {
                $orderBy = trim($orderMatches[1]);
                $queryParams['order'] = str_replace(' ', '', $orderBy);
            }
            
            $result = $this->apiCall($table, 'GET', null, $queryParams);
            
            return new SupabaseStatementResult($result);
        }
        
        // For complex queries, log an error and return false
        error_log("Complex SQL query not supported, use direct API calls instead: " . $sql);
        return false;
    }

    /**
     * Fetch all rows from a query
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt && $stmt instanceof SupabaseStatementResult) {
            return $stmt->fetchAll();
        }
        return false;
    }
    
    /**
     * Fetch a single row
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt && $stmt instanceof SupabaseStatementResult) {
            return $stmt->fetch();
        }
        return false;
    }
    
    /**
     * Insert data into a table
     */
    public function insert($table, $data) {
        $result = $this->apiCall($table, 'POST', $data);
        if ($result && isset($result['id'])) {
            return $result['id'];
        }
        return $result ? true : false;
    }
    
    /**
     * Update data in a table
     */
    public function update($table, $data, $where, $whereParams = []) {
        // Extract ID from where clause for REST API
        $id = null;
        if (preg_match('/id\s*=\s*(\d+)/', $where, $matches)) {
            $id = $matches[1];
        } elseif (isset($whereParams['id'])) {
            $id = $whereParams['id'];
        }
        
        if ($id) {
            $result = $this->apiCall($table . '?id=eq.' . $id, 'PATCH', $data);
            return $result ? 1 : 0;
        }
        return 0;
    }
    
    /**
     * Delete from a table
     */
    public function delete($table, $where, $params = []) {
        $id = null;
        if (preg_match('/id\s*=\s*(\d+)/', $where, $matches)) {
            $id = $matches[1];
        } elseif (isset($params['id'])) {
            $id = $params['id'];
        }
        
        if ($id) {
            $result = $this->apiCall($table . '?id=eq.' . $id, 'DELETE');
            return $result ? 1 : 0;
        }
        return 0;
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $result = $this->apiCall($table . '?limit=1');
        return $result !== false;
    }
    
    /**
     * Get table structure (limited with REST API)
     */
    public function getTableStructure($table) {
        // REST API doesn't provide schema info easily, return basic structure
        return [
            ['column_name' => 'id', 'data_type' => 'integer'],
            ['column_name' => 'created_at', 'data_type' => 'timestamp'],
            ['column_name' => 'updated_at', 'data_type' => 'timestamp']
        ];
    }
    
    /**
     * Close database connection
     */
    public function close() {
        // No connection to close with REST API
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        // Not available with REST API
        return false;
    }
}

/**
 * Simple statement result wrapper for compatibility
 */
class SupabaseStatementResult {
    private $result;
    
    public function __construct($result) {
        $this->result = $result ?: [];
    }
    
    public function execute() {
        return $this->result !== false;
    }
    
    public function fetchAll() {
        return is_array($this->result) ? $this->result : [];
    }
    
    public function fetch() {
        if (is_array($this->result) && !empty($this->result)) {
            return $this->result[0];
        }
        return false;
    }
    
    public function rowCount() {
        return is_array($this->result) ? count($this->result) : 0;
    }
    
    public function errorInfo() {
        return ['', '', 'REST API call'];
    }
}
?>
