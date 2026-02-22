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
        
        // Set up error logging to file
        $logFile = __DIR__ . '/../../php-errors.log';
        ini_set('error_log', $logFile);
        ini_set('log_errors', 1);
    }
    
    /**
     * Make a REST API call to Supabase with egress optimization
     */
    public function apiCall($endpoint, $method = 'GET', $data = null, $queryParams = []) {
        $url = $this->supabase_url . '/rest/v1/' . $endpoint;
        
        // Add query parameters to URL
        if (!empty($queryParams)) {
            $queryString = http_build_query($queryParams);
            $url .= '?' . $queryString;
        }
        
        // Log API calls for monitoring egress usage
        error_log("Supabase API Call: $method $url");
        if ($data) {
            error_log("Request data: " . json_encode($data));
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Add compression to reduce egress
        $headers = array_merge($this->headers, ['Accept-Encoding: gzip, deflate']);
        
        // For PATCH/PUT/POST, request to return the updated representation
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $headers[] = 'Prefer: return=representation';
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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
        
        error_log("Supabase API Response Code: $httpCode");
        error_log("Supabase API Response: " . $response);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseSize = strlen($response);
            error_log("Supabase API Response Size: {$responseSize} bytes");
            
            // For empty responses (like 204 No Content), treat as success
            if (empty($response)) {
                return true;
            }
            
            return json_decode($response, true);
        } else {
            $errorMsg = "Supabase API error: HTTP $httpCode - $response";
            
            // Check for common Supabase limit errors
            if ($httpCode == 429) {
                $errorMsg .= " (Rate limit exceeded - reduce API call frequency)";
            } elseif ($httpCode == 402) {
                $errorMsg .= " (Payment required - egress limit exceeded)";
            } elseif ($httpCode == 403) {
                $errorMsg .= " (Forbidden - check API key or egress limits)";
            } elseif ($httpCode == 0 && $curlError) {
                $errorMsg .= " (Connection failed - possible network or limit issue)";
            }
            
            error_log($errorMsg);
            
            // For egress limit errors, don't retry immediately
            if ($httpCode == 402 || $httpCode == 403) {
                sleep(1);
            }
            
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
