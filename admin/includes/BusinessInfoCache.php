<?php
/**
 * Simple file-based cache for business info to reduce Supabase API calls
 */
class BusinessInfoCache {
    private $cacheFile;
    private $cacheExpiry;
    
    public function __construct($cacheDir = '/tmp', $expiryMinutes = 60) {
        $this->cacheFile = $cacheDir . '/business_info_cache.json';
        $this->cacheExpiry = $expiryMinutes * 60; // Convert to seconds - increased to 1 hour
    }
    
    /**
     * Get cached business info if valid
     */
    public function get() {
        if (!file_exists($this->cacheFile)) {
            error_log("BusinessInfoCache: Cache file not found");
            return null;
        }
        
        try {
            // Try to decompress cache data
            $compressed = file_get_contents($this->cacheFile);
            $decompressed = gzuncompress($compressed);
            
            if ($decompressed === false) {
                // Fallback for uncompressed cache
                $cacheData = json_decode($compressed, true);
            } else {
                $cacheData = json_decode($decompressed, true);
            }
            
            if (!$cacheData || !isset($cacheData['timestamp']) || !isset($cacheData['data'])) {
                error_log("BusinessInfoCache: Invalid cache data structure");
                return null;
            }
            
            // Check if cache is expired
            if (time() - $cacheData['timestamp'] > $this->cacheExpiry) {
                error_log("BusinessInfoCache: Cache expired");
                $this->clear();
                return null;
            }
            
            error_log("BusinessInfoCache: Cache hit - serving cached data");
            return $cacheData['data'];
        } catch (Exception $e) {
            error_log("BusinessInfoCache: Error reading cache - " . $e->getMessage());
            $this->clear();
            return null;
        }
    }
    
    /**
     * Store business info in cache with compression
     */
    public function set($data) {
        $cacheData = [
            'timestamp' => time(),
            'data' => $data,
            'version' => '1.1' // Cache version for invalidation
        ];
        
        // Compress cache data to save space
        $compressed = gzcompress(json_encode($cacheData), 6);
        file_put_contents($this->cacheFile, $compressed);
        
        error_log("BusinessInfoCache: Cached data (" . strlen($compressed) . " bytes compressed)");
    }
    
    /**
     * Clear the cache
     */
    public function clear() {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
    
    /**
     * Check if cache exists and is valid
     */
    public function isValid() {
        return $this->get() !== null;
    }
}
?>
