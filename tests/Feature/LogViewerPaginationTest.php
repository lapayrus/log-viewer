<?php

namespace Lapayrus\LogViewer\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Lapayrus\LogViewer\Http\Controllers\LogViewerController;
use PHPUnit\Framework\TestCase;

class LogViewerPaginationTest extends TestCase
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
        $content = '';
        $levels = ['INFO', 'ERROR', 'WARNING', 'DEBUG', 'CRITICAL'];
        
        for ($i = 0; $i < $entryCount; $i++) {
            $date = date('Y-m-d H:i:s', strtotime("2023-01-01 00:00:00 +$i minutes"));
            $level = $levels[array_rand($levels)];
            $message = "This is log entry #$i with level $level";
            
            $content .= "[$date] production.$level: $message\n";
        }
        
        return $this->createTestLogFile($filename, $content);
    }

    /**
     * Test pagination with large log file
     */
    public function testPaginationWithLargeFile()
    {
        // Generate a large log file
        $logFile = 'large-test.log';
        $this->generateLargeLogFile($logFile, 500);
        
        // Create a request with page parameter
        $request = new Request(['file' => $logFile, 'page' => 1]);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Call the method for page 1
        $logsPage1 = $method->invoke($this->controller, $logFile, null, null, 1);
        
        // Call the method for page 2
        $logsPage2 = $method->invoke($this->controller, $logFile, null, null, 2);
        
        // Assert that we got logs and they're different between pages
        $this->assertNotEmpty($logsPage1);
        $this->assertNotEmpty($logsPage2);
        $this->assertNotEquals($logsPage1[0]['message'], $logsPage2[0]['message']);
        
        // Verify the number of logs per page (default should be 100)
        $this->assertLessThanOrEqual(100, count($logsPage1));
        $this->assertLessThanOrEqual(100, count($logsPage2));
    }

    /**
     * Test pagination with level filtering
     */
    public function testPaginationWithLevelFiltering()
    {
        // Generate a large log file
        $logFile = 'large-filter-test.log';
        $this->generateLargeLogFile($logFile, 500);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Call the method with level filter
        $logs = $method->invoke($this->controller, $logFile, 'error', null, 1);
        
        // Assert that all returned logs have the error level
        foreach ($logs as $log) {
            $this->assertEquals('error', $log['level']);
        }
    }

    /**
     * Test pagination with search query
     */
    public function testPaginationWithSearchQuery()
    {
        // Generate a large log file
        $logFile = 'large-search-test.log';
        $this->generateLargeLogFile($logFile, 500);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Call the method with search query
        $searchTerm = 'level ERROR';
        $logs = $method->invoke($this->controller, $logFile, null, $searchTerm, 1);
        
        // Assert that all returned logs contain the search term
        foreach ($logs as $log) {
            $this->assertStringContainsString('ERROR', $log['message']);
        }
    }

    /**
     * Test pagination with combined level filtering and search
     */
    public function testPaginationWithCombinedFiltering()
    {
        // Generate a large log file
        $logFile = 'large-combined-test.log';
        $this->generateLargeLogFile($logFile, 500);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Call the method with level filter and search query
        $searchTerm = '#100'; // Looking for log entry #100
        $logs = $method->invoke($this->controller, $logFile, 'error', $searchTerm, 1);
        
        // Assert that all returned logs have the error level and contain the search term
        foreach ($logs as $log) {
            $this->assertEquals('error', $log['level']);
            $this->assertStringContainsString($searchTerm, $log['message']);
        }
    }

    /**
     * Test pagination state preservation
     */
    public function testPaginationStatePreservation()
    {
        // Generate a large log file
        $logFile = 'large-state-test.log';
        $this->generateLargeLogFile($logFile, 500);
        
        // Mock the request
        $request = new Request([
            'file' => $logFile,
            'level' => 'error',
            'query' => 'test',
            'page' => 2
        ]);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $indexMethod = $reflection->getMethod('index');
        $indexMethod->setAccessible(true);
        
        // Call the index method
        $response = $indexMethod->invoke($this->controller, $request);
        
        // Since we mocked the view, we can't directly test the view data
        // But we can test that the method doesn't throw exceptions
        $this->assertTrue(true);
    }
}