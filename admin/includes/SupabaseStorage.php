<?php
/**
 * Supabase Storage Utility Class
 * Handles file uploads, downloads, and storage management
 */

class SupabaseStorage {
    private $url;
    private $anonKey;
    private $serviceRoleKey;
    private $bucketName;
    
    public function __construct($bucketName = 'dashouse-bucket') {
        $this->url = SUPABASE_URL;
        $this->anonKey = SUPABASE_ANON_KEY;
        $this->serviceRoleKey = SUPABASE_SERVICE_ROLE_KEY;
        $this->bucketName = $bucketName;
    }
    
    /**
     * Upload a file to Supabase storage
     */
    public function uploadFile($filePath, $fileName, $contentType = null) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File not found: $filePath");
            }
            
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                throw new Exception("Failed to read file: $filePath");
            }
            
            // Generate unique filename to avoid conflicts
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueName = uniqid() . '_' . time() . '.' . $extension;
            
            $url = $this->url . '/storage/v1/object/' . $this->bucketName . '/' . $uniqueName;
            
            // Use service role key for uploads if available, otherwise fall back to anon key
            $authKey = !empty($this->serviceRoleKey) && $this->serviceRoleKey !== '16384' ? $this->serviceRoleKey : $this->anonKey;
            
            $headers = [
                'Authorization: Bearer ' . $authKey,
                'Content-Type: ' . ($contentType ?: mime_content_type($filePath)),
                'Cache-Control: public, max-age=31536000'
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $fileContent,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['Key'])) {
                    return [
                        'success' => true,
                        'url' => $this->getPublicUrl($uniqueName),
                        'key' => $result['Key'],
                        'filename' => $uniqueName
                    ];
                }
            }
            
            throw new Exception("Upload failed with HTTP code: $httpCode, Response: $response");
            
        } catch (Exception $e) {
            error_log("Supabase storage upload error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a file from Supabase storage
     */
    public function deleteFile($fileName) {
        try {
            $url = $this->url . '/storage/v1/object/' . $this->bucketName . '/' . $fileName;
            
            // Use service role key for deletions if available, otherwise fall back to anon key
            $authKey = !empty($this->serviceRoleKey) && $this->serviceRoleKey !== '16384' ? $this->serviceRoleKey : $this->anonKey;
            
            $headers = [
                'Authorization: Bearer ' . $authKey
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
            
        } catch (Exception $e) {
            error_log("Supabase storage delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get public URL for a file
     */
    public function getPublicUrl($fileName) {
        return $this->url . '/storage/v1/object/public/' . $this->bucketName . '/' . $fileName;
    }
    
    /**
     * Check if bucket exists and is accessible
     */
    public function ensureBucketExists() {
        try {
            // Use service role key for bucket operations since anon key has limited access
            $authKey = !empty($this->serviceRoleKey) && $this->serviceRoleKey !== '16384' ? $this->serviceRoleKey : $this->anonKey;
            
            // Try to list objects in the bucket to check if it exists and is accessible
            $url = $this->url . '/storage/v1/object/' . $this->bucketName;
            
            $headers = [
                'Authorization: Bearer ' . $authKey,
                'apikey: ' . $authKey
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // If we get a 200 or 404 (bucket exists but no objects), the bucket is accessible
            if ($httpCode === 200 || $httpCode === 404) {
                echo "✓ Bucket '{$this->bucketName}' exists and is accessible\n";
                return true;
            } else {
                echo "✗ Bucket '{$this->bucketName}' not accessible (HTTP $httpCode)\n";
                echo "Response: $response\n";
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Supabase storage bucket check error: " . $e->getMessage());
            echo "✗ Error checking bucket: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Handle file upload from form
     */
    public function handleFormUpload($fileInput, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) {
        try {
            if (!isset($fileInput['tmp_name']) || !is_uploaded_file($fileInput['tmp_name'])) {
                throw new Exception("No file uploaded or upload failed");
            }
            
            $filePath = $fileInput['tmp_name'];
            $originalName = $fileInput['name'];
            $contentType = $fileInput['type'];
            $fileSize = $fileInput['size'];
            
            // Validate file type
            if (!in_array($contentType, $allowedTypes)) {
                throw new Exception("Invalid file type. Allowed: " . implode(', ', $allowedTypes));
            }
            
            // Validate file size (5MB limit)
            if ($fileSize > 5242880) {
                throw new Exception("File too large. Maximum size: 5MB");
            }
            
            // Ensure bucket exists
            if (!$this->ensureBucketExists()) {
                throw new Exception("Failed to ensure storage bucket exists");
            }
            
            // Upload file
            $result = $this->uploadFile($filePath, $originalName, $contentType);
            
            if ($result['success']) {
                return $result;
            } else {
                throw new Exception($result['error']);
            }
            
        } catch (Exception $e) {
            error_log("Form upload error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
