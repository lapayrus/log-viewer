<?php

namespace Lapayrus\LogViewer\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Lapayrus\LogViewer\Http\Controllers\LogViewerController;
use PHPUnit\Framework\TestCase;

class LogViewerSearchTest extends TestCase
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
     * Test searching across multiple log files
     */
    public function testSearchAcrossMultipleFiles()
    {
        // Create test log files with different content
        $this->createTestLogFile('app-2023-01-01.log', <<<EOT
[2023-01-01 12:00:00] production.INFO: User login successful
[2023-01-01 12:01:00] production.ERROR: Database connection failed
EOT
        );
        
        $this->createTestLogFile('app-2023-01-02.log', <<<EOT
[2023-01-02 12:00:00] production.INFO: User logout
[2023-01-02 12:01:00] production.WARNING: Memory usage high
[2023-01-02 12:02:00] production.ERROR: Database query timeout
EOT
        );
        
        // Create a mock request with search query
        $request = new Request(['query' => 'database']);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('search');
        $method->setAccessible(true);
        
        // Call the search method
        $method->invoke($this->controller, $request);
        
        // Since we can't easily test the view directly, we'll test the underlying search functionality
        $getLogsMethod = $reflection->getMethod('getLogs');
        $getLogsMethod->setAccessible(true);
        
        // Check logs from first file
        $logs = $getLogsMethod->invoke($this->controller, 'app-2023-01-01.log', null, 'database');
        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertStringContainsString('Database connection failed', $logs[0]['message']);
        
        // Check logs from second file
        $logs = $getLogsMethod->invoke($this->controller, 'app-2023-01-02.log', null, 'database');
        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertStringContainsString('Database query timeout', $logs[0]['message']);
    }

    /**
     * Test searching with level filter
     */
    public function testSearchWithLevelFilter()
    {
        // Create test log file with different log levels
        $this->createTestLogFile('app-mixed.log', <<<EOT
[2023-01-01 12:00:00] production.INFO: User authentication successful
[2023-01-01 12:01:00] production.WARNING: Slow query detected
[2023-01-01 12:02:00] production.ERROR: Authentication failed
[2023-01-01 12:03:00] production.INFO: User authentication attempt
[2023-01-01 12:04:00] production.ERROR: Authentication service unavailable
EOT
        );
        
        // Create a mock request with search query and level filter
        $request = new Request([
            'query' => 'authentication',
            'level' => 'error'
        ]);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('search');
        $method->setAccessible(true);
        
        // Call the search method
        $method->invoke($this->controller, $request);
        
        // Test the underlying search functionality
        $getLogsMethod = $reflection->getMethod('getLogs');
        $getLogsMethod->setAccessible(true);
        
        // Check logs with both search query and level filter
        $logs = $getLogsMethod->invoke($this->controller, 'app-mixed.log', 'error', 'authentication');
        
        // Should only return error logs containing 'authentication'
        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertEquals('error', $log['level']);
            $this->assertStringContainsString('authentication', strtolower($log['message']));
        }
    }

    /**
     * Test search with no results
     */
    public function testSearchWithNoResults()
    {
        // Create test log file
        $this->createTestLogFile('app.log', <<<EOT
[2023-01-01 12:00:00] production.INFO: User login successful
[2023-01-01 12:01:00] production.ERROR: Database connection failed
EOT
        );
        
        // Create a mock request with search query that won't match anything
        $request = new Request(['query' => 'nonexistentterm']);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('search');
        $method->setAccessible(true);
        
        // Call the search method
        $method->invoke($this->controller, $request);
        
        // Test the underlying search functionality
        $getLogsMethod = $reflection->getMethod('getLogs');
        $getLogsMethod->setAccessible(true);
        
        // Check logs with search query
        $logs = $getLogsMethod->invoke($this->controller, 'app.log', null, 'nonexistentterm');
        
        // Should return no results
        $this->assertCount(0, $logs);
    }

    /**
     * Test search with date in query
     */
    public function testSearchWithDateInQuery()
    {
        // Create test log file with entries on different dates
        $this->createTestLogFile('app-dates.log', <<<EOT
[2023-01-01 12:00:00] production.INFO: System startup
[2023-01-02 12:00:00] production.INFO: Daily backup
[2023-01-03 12:00:00] production.INFO: System maintenance
EOT
        );
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $getLogsMethod = $reflection->getMethod('getLogs');
        $getLogsMethod->setAccessible(true);
        
        // Search for logs on a specific date
        $logs = $getLogsMethod->invoke($this->controller, 'app-dates.log', null, '2023-01-02');
        
        // Should only return logs from January 2nd
        $this->assertCount(1, $logs);
        $this->assertEquals('2023-01-02 12:00:00', $logs[0]['date']);
        $this->assertEquals('Daily backup', $logs[0]['message']);
    }

    /**
     * Test search with partial word matching
     */
    public function testSearchWithPartialWordMatching()
    {
        // Create test log file
        $this->createTestLogFile('app-partial.log', <<<EOT
[2023-01-01 12:00:00] production.INFO: User authentication successful
[2023-01-01 12:01:00] production.WARNING: Authentication token expired
[2023-01-01 12:02:00] production.ERROR: Auth service unavailable
EOT
        );
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $getLogsMethod = $reflection->getMethod('getLogs');
        $getLogsMethod->setAccessible(true);
        
        // Search for logs with partial word
        $logs = $getLogsMethod->invoke($this->controller, 'app-partial.log', null, 'auth');
        
        // Should return all logs containing 'auth' (case insensitive)
        $this->assertCount(3, $logs);
    }
}