<?php
/**
 * Egress Optimizer for Supabase API calls
 * Reduces bandwidth usage through various optimization techniques
 */
class EgressOptimizer {
    private $requestLog = [];
    private $logFile;
    
    public function __construct() {
        $this->logFile = '/tmp/supabase_egress_log.json';
        $this->loadRequestLog();
    }
    
    /**
     * Check if we should make an API call based on recent requests
     */
    public function shouldMakeRequest($endpoint, $method, $params = []) {
        $requestKey = $this->generateRequestKey($endpoint, $method, $params);
        $now = time();
        
        // Check if we made this exact request recently (within 5 minutes)
        if (isset($this->requestLog[$requestKey])) {
            $lastRequest = $this->requestLog[$requestKey];
            if ($now - $lastRequest['timestamp'] < 300) { // 5 minutes
                error_log("EgressOptimizer: Skipping duplicate request - $requestKey");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Log a request to prevent duplicates
     */
    public function logRequest($endpoint, $method, $params = [], $responseSize = 0) {
        $requestKey = $this->generateRequestKey($endpoint, $method, $params);
        $this->requestLog[$requestKey] = [
            'timestamp' => time(),
            'response_size' => $responseSize,
            'endpoint' => $endpoint,
            'method' => $method
        ];
        
        // Clean old entries (older than 1 hour)
        $this->cleanOldEntries();
        $this->saveRequestLog();
    }
    
    /**
     * Optimize query parameters to reduce response size
     */
    public function optimizeQueryParams($params) {
        // Add limit if not specified to prevent large responses
        if (!isset($params['limit']) && !isset($params['select'])) {
            $params['limit'] = '100'; // Default limit
        }
        
        // Add compression hint
        $params['Accept-Encoding'] = 'gzip';
        
        return $params;
    }
    
    /**
     * Get egress usage statistics
     */
    public function getUsageStats() {
        $totalSize = 0;
        $requestCount = 0;
        $now = time();
        
        foreach ($this->requestLog as $request) {
            if ($now - $request['timestamp'] < 3600) { // Last hour
                $totalSize += $request['response_size'];
                $requestCount++;
            }
        }
        
        return [
            'total_bytes_last_hour' => $totalSize,
            'request_count_last_hour' => $requestCount,
            'average_response_size' => $requestCount > 0 ? round($totalSize / $requestCount) : 0
        ];
    }
    
    private function generateRequestKey($endpoint, $method, $params) {
        return md5($endpoint . $method . serialize($params));
    }
    
    private function loadRequestLog() {
        if (file_exists($this->logFile)) {
            $data = json_decode(file_get_contents($this->logFile), true);
            $this->requestLog = $data ?: [];
        }
    }
    
    private function saveRequestLog() {
        file_put_contents($this->logFile, json_encode($this->requestLog));
    }
    
    private function cleanOldEntries() {
        $now = time();
        foreach ($this->requestLog as $key => $request) {
            if ($now - $request['timestamp'] > 3600) { // Older than 1 hour
                unset($this->requestLog[$key]);
            }
        }
    }
}
?>
