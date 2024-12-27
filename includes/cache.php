<?php
class Cache {
    private $path;
    
    public function __construct($path = CACHE_PATH) {
        $this->path = $path;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
    
    public function set($key, $value, $ttl = CACHE_LIFETIME) {
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        $file = $this->getFilePath($key);
        return file_put_contents($file, serialize($data));
    }
    
    public function get($key) {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if (time() > $data['expires']) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    private function getFilePath($key) {
        return $this->path . '/' . md5($key) . '.cache';
    }
} 