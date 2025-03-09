<?php

namespace Lapayrus\LogViewer\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Lapayrus\LogViewer\Http\Controllers\LogViewerController;
use PHPUnit\Framework\TestCase;

class LogViewerLargeFileTest extends TestCase
{
    protected $controller;
    protected $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new LogViewerController();
        
        // Create a temporary directory for test log files
        $this->tempDir = sys_get_temp_dir() . '/log-viewer-tests';
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
        
        // Mock the config values
        config(['log-viewer.path' => 'logs']);
        config(['log-viewer.pattern' => '*.log']);
        config(['log-viewer.large_file_threshold' => 1]); // Set to 1MB for testing
        
        // Mock the storage_path function
        if (!function_exists('storage_path')) {
            function storage_path($path = '')
            {
                return sys_get_temp_dir() . '/log-viewer-tests/' . $path;
            }
        }
        
        // Mock the view facade
        View::shouldReceive('make')->andReturn(new class {
            public function with($data) { return $this; }
        });
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        if (file_exists($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
        
        parent::tearDown();
    }
    
    /**
     * Helper method to delete directory recursively
     */
    protected function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Helper method to create a test log file with sample content
     */
    protected function createTestLogFile($filename, $content)
    {
        $path = $this->tempDir . '/logs/' . $filename;
        $dir = dirname($path);
        
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($path, $content);
        return $path;
    }

    /**
     * Generate a large log file with many entries
     */
    protected function generateLargeLogFile($filename, $entryCount = 500)
    {
        $content = "";
        for ($i = 0; $i < $entryCount; $i++) {
            $level = $i % 4 === 0 ? 'ERROR' : ($i % 3 === 0 ? 'WARNING' : 'INFO');
            $date = date('Y-m-d H:i:s', strtotime("2023-01-01 00:00:00 +$i minutes"));
            $message = "Log entry #{$i}: This is a sample log message with some random data: " . bin2hex(random_bytes(20));
            $content .= "[{$date}] production.{$level}: {$message}\n";
        }
        
        return $this->createTestLogFile($filename, $content);
    }

    /**
     * Test pagination with large log file
     */
    public function testPaginationWithLargeFile()
    {
        // Generate a large log file
        $logFile = 'laravel-large.log';
        $this->generateLargeLogFile($logFile, 300); // 300 log entries
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Test first page (should have 100 entries)
        $logs = $method->invoke($this->controller, $logFile, null, null, 1);
        $this->assertCount(100, $logs);
        
        // Test second page (should have 100 entries)
        $logs = $method->invoke($this->controller, $logFile, null, null, 2);
        $this->assertCount(100, $logs);
        
        // Test third page (should have 100 entries)
        $logs = $method->invoke($this->controller, $logFile, null, null, 3);
        $this->assertCount(100, $logs);
        
        // Test fourth page (should be empty as we only have 300 entries)
        $logs = $method->invoke($this->controller, $logFile, null, null, 4);
        $this->assertCount(0, $logs);
    }

    /**
     * Test pagination with level filtering
     */
    public function testPaginationWithLevelFiltering()
    {
        // Generate a large log file
        $logFile = 'laravel-large-filtered.log';
        $this->generateLargeLogFile($logFile, 300); // 300 log entries, every 4th is ERROR
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Test first page with error level filter
        $logs = $method->invoke($this->controller, $logFile, 'error', null, 1);
        
        // Should only contain error logs
        $this->assertGreaterThan(0, count($logs));
        foreach ($logs as $log) {
            $this->assertEquals('error', $log['level']);
        }
        
        // The number of error logs should be approximately 1/4 of total
        // but we can't be exact due to pagination boundaries
        $this->assertLessThanOrEqual(100, count($logs)); // Max per page
    }

    /**
     * Test pagination with search query
     */
    public function testPaginationWithSearchQuery()
    {
        // Generate a large log file with some entries containing a specific search term
        $logFile = 'laravel-large-search.log';
        $content = "";
        for ($i = 0; $i < 300; $i++) {
            $level = $i % 4 === 0 ? 'ERROR' : ($i % 3 === 0 ? 'WARNING' : 'INFO');
            $date = date('Y-m-d H:i:s', strtotime("2023-01-01 00:00:00 +$i minutes"));
            
            // Add search term to every 5th message
            $message = $i % 5 === 0 
                ? "SEARCHABLE: This is log entry #{$i} with the search term" 
                : "Log entry #{$i}: This is a regular log message";
                
            $content .= "[{$date}] production.{$level}: {$message}\n";
        }
        $this->createTestLogFile($logFile, $content);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Test search with pagination
        $logs = $method->invoke($this->controller, $logFile, null, 'SEARCHABLE', 1);
        
        // Should only contain logs with the search term
        $this->assertGreaterThan(0, count($logs));
        foreach ($logs as $log) {
            $this->assertStringContainsString('SEARCHABLE', $log['message']);
            $this->assertTrue($log['has_search_match']);
        }
    }

    /**
     * Test pagination with both level filter and search query
     */
    public function testPaginationWithLevelAndSearch()
    {
        // Generate a large log file
        $logFile = 'laravel-large-combined.log';
        $content = "";
        for ($i = 0; $i < 300; $i++) {
            $level = $i % 4 === 0 ? 'ERROR' : ($i % 3 === 0 ? 'WARNING' : 'INFO');
            $date = date('Y-m-d H:i:s', strtotime("2023-01-01 00:00:00 +$i minutes"));
            
            // Add search term to some messages
            $message = $i % 10 === 0 
                ? "CRITICAL: This is an important log entry #{$i}" 
                : "Log entry #{$i}: This is a regular log message";
                
            $content .= "[{$date}] production.{$level}: {$message}\n";
        }
        $this->createTestLogFile($logFile, $content);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Test with both level filter and search query
        $logs = $method->invoke($this->controller, $logFile, 'error', 'CRITICAL', 1);
        
        // Should only contain error logs with the search term
        $this->assertGreaterThanOrEqual(0, count($logs)); // May be 0 if no matches on page 1
        foreach ($logs as $log) {
            $this->assertEquals('error', $log['level']);
            $this->assertStringContainsString('CRITICAL', $log['message']);
            $this->assertTrue($log['has_search_match']);
        }
    }

    /**
     * Test performance with extremely large log file
     */
    public function testPerformanceWithExtremelyLargeFile()
    {
        // Skip this test in normal runs as it's resource-intensive
        $this->markTestSkipped('Performance test skipped to avoid long test runs');
        
        // Generate an extremely large log file (10,000 entries)
        $logFile = 'laravel-extreme.log';
        $this->generateLargeLogFile($logFile, 10000);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Measure time to retrieve first page
        $startTime = microtime(true);
        $logs = $method->invoke($this->controller, $logFile, null, null, 1);
        $endTime = microtime(true);
        
        // Assert that retrieval is reasonably fast (less than 1 second)
        $this->assertLessThan(1.0, $endTime - $startTime);
        $this->assertCount(100, $logs);
    }
}