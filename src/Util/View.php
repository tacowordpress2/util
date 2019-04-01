<?php
namespace Taco\Util;

class View
{
    
    private static $directories = null;
    private static $report_errors = false;
    
    
    /**
     * Set views directories
     * @param array $directories
     */
    public static function setDirectories($directories)
    {
        if (!Arr::iterable($directories)) {
            $directories = [];
        }
        self::$directories = $directories;
    }
    
    
    /**
     * Set error reporting
     * @param bool $report_errors
     */
    public static function setErrorReporting($report_errors = false)
    {
        self::$report_errors = $report_errors;
    }
    
    
    /**
     * Include file with scoped variables
     * @param string $path
     * @param array $variables
     * @param bool $include_once
     */
    public static function make($path, $variables = [], $include_once = false)
    {
        $view_path = self::find($path);
        
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
     * @return string
     */
    private static function find($path)
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
            if (self::$report_errors) {
                die('View not found: ' . $path);
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
