<?php

class View
{
    
    private static $directories = null;
    
    
    /**
     * @param array $directories
     */
    public static function setDirectories($directories)
    {
        self::$directories = $directories;
    }
    
    
    /**
     * Include file with scoped variables
     * @param string $path
     * @param array $variables
     * @param bool $include_once
     * @param bool $die_on_error
     */
    public static function make($path, $variables = [], $include_once = false, $die_on_error = false)
    {
        $view_path = self::find($path, $die_on_error);
        
        if (Arr::iterable($variables)) {
            extract($variables);
        }
        
        ob_start();
        if ($include_once) {
            include_once $view_path;
        } else {
            include $view_path;
        }
        return ob_get_clean();
    }
    
    
    /**
     * Find matching view
     * @param string $path
     * @param bool $die_on_error
     * @return string
     */
    private static function find($path, $die_on_error = false)
    {
        $view_path = null;
        foreach (self::$directories as $directory) {
            $formatted_path = self::formatPath($directory . '/' . $path);
            if (self::isValidPath($formatted_path)) {
                $view_path = $formatted_path;
                break;
            }
        }
        
        if (empty($view_path)) {
            if ($die_on_error) {
                die('View not found: ' . $view_path);
            }
            return null;
        }
        
        return $view_path;
    }
    
    
    /**
     * Format path, removing consecutive slashes and adding file extension
     * @param string $path
     * @return string
     */
    private static function formatPath($path)
    {
        if (!preg_match('/\.php$/', $path)) {
            $path .= '.php';
        }
        $path = preg_replace('/\/{2,}/', '/', $path);
        return $path;
    }
    
    
    /**
     * Is the path to this view valid?
     * @param string $path
     */
    private static function isValidPath($path)
    {
        return file_exists($path);
    }
    
}
