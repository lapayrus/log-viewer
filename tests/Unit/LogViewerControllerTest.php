<?php

namespace Lapayrus\LogViewer\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Lapayrus\LogViewer\Http\Controllers\LogViewerController;
use PHPUnit\Framework\TestCase;

class LogViewerControllerTest extends TestCase
{
    protected $controller;
    protected $tempDir;
    protected $tempFile;

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
        config(['log-viewer.large_file_threshold' => 5]); // 5MB
        
        // Mock the storage_path function
        if (!function_exists('storage_path')) {
            function storage_path($path = '')
            {
                return sys_get_temp_dir() . '/log-viewer-tests/' . $path;
            }
        }
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
     * Test the getLogFiles method returns correct files
     */
    public function testGetLogFiles()
    {
        // Create test log files
        $this->createTestLogFile('laravel-2023-01-01.log', 'Test log content');
        $this->createTestLogFile('laravel-2023-01-02.log', 'Test log content');
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogFiles');
        $method->setAccessible(true);
        
        // Call the method
        $files = $method->invoke($this->controller);
        
        // Assert that the files are returned in reverse order (newest first)
        $this->assertCount(2, $files);
        $this->assertEquals('laravel-2023-01-02.log', $files[0]);
        $this->assertEquals('laravel-2023-01-01.log', $files[1]);
    }

    /**
     * Test the getLogs method parses log entries correctly
     */
    public function testGetLogs()
    {
        // Create a test log file with sample content
        $logContent = <<<EOT
[2023-01-01 12:00:00] production.INFO: This is an info log message
[2023-01-01 12:01:00] production.ERROR: This is an error log message
[2023-01-01 12:02:00] production.DEBUG: This is a debug log message
EOT;
        
        $logFile = 'laravel-test.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method
        $logs = $method->invoke($this->controller, $logFile);
        
        // Assert that the logs are parsed correctly
        $this->assertCount(3, $logs);
        
        // Check the first log entry
        $this->assertEquals('info', $logs[0]['level']);
        $this->assertEquals('2023-01-01 12:00:00', $logs[0]['date']);
        $this->assertEquals('production', $logs[0]['environment']);
        $this->assertEquals('This is an info log message', $logs[0]['message']);
        
        // Check the second log entry
        $this->assertEquals('error', $logs[1]['level']);
        $this->assertEquals('2023-01-01 12:01:00', $logs[1]['date']);
        $this->assertEquals('This is an error log message', $logs[1]['message']);
    }

    /**
     * Test the getLogs method with level filtering
     */
    public function testGetLogsWithLevelFilter()
    {
        // Create a test log file with sample content
        $logContent = <<<EOT
[2023-01-01 12:00:00] production.INFO: This is an info log message
[2023-01-01 12:01:00] production.ERROR: This is an error log message
[2023-01-01 12:02:00] production.DEBUG: This is a debug log message
EOT;
        
        $logFile = 'laravel-test.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method with level filter
        $logs = $method->invoke($this->controller, $logFile, 'error');
        
        // Assert that only error logs are returned
        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertEquals('This is an error log message', $logs[0]['message']);
    }

    /**
     * Test the getLogs method with search query
     */
    public function testGetLogsWithSearchQuery()
    {
        // Create a test log file with sample content
        $logContent = <<<EOT
[2023-01-01 12:00:00] production.INFO: This is an info log message
[2023-01-01 12:01:00] production.ERROR: This is an error log message
[2023-01-01 12:02:00] production.DEBUG: This is a debug log message
EOT;
        
        $logFile = 'laravel-test.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method with search query
        $logs = $method->invoke($this->controller, $logFile, null, 'error');
        
        // Assert that only logs containing 'error' are returned
        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertEquals('This is an error log message', $logs[0]['message']);
        $this->assertTrue($logs[0]['has_search_match']);
    }

    /**
     * Test the formatMessage method formats messages correctly
     */
    public function testFormatMessage()
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatMessage');
        $method->setAccessible(true);
        
        // Test formatting a simple message
        $message = "  This is a simple message with extra spaces  ";
        $formatted = $method->invoke($this->controller, $message);
        $this->assertEquals("This is a simple message with extra spaces", $formatted);
        
        // Test formatting a message with arrays
        $message = "Array data: array(
            'key1' => 'value1',
            'key2' => 'value2'
        )";
        $formatted = $method->invoke($this->controller, $message);
        $this->assertEquals("Array data: array(
'key1' => 'value1',
'key2' => 'value2')", $formatted);
    }

    /**
     * Test the getLogsWithPagination method for large files
     */
    public function testGetLogsWithPagination()
    {
        // Create a test log file with multiple entries
        $logContent = "";
        for ($i = 0; $i < 150; $i++) {
            $logContent .= "[2023-01-01 12:" . sprintf("%02d", $i) . ":00] production.INFO: This is log message $i\n";
        }
        
        $logFile = 'laravel-large.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Call the method for page 1
        $logs = $method->invoke($this->controller, $logFile, null, null, 1);
        
        // Assert that only 100 logs are returned (default per page)
        $this->assertCount(100, $logs);
        
        // Call the method for page 2
        $logs = $method->invoke($this->controller, $logFile, null, null, 2);
        
        // Assert that the remaining 50 logs are returned
        $this->assertCount(50, $logs);
    }

    /**
     * Test the getLogsWithPagination method with search query
     */
    public function testGetLogsWithPaginationAndSearch()
    {
        // Create a test log file with entries where only some match the search
        $logContent = "";
        for ($i = 0; $i < 150; $i++) {
            $message = ($i % 3 == 0) ? "This is a SEARCHABLE log message $i" : "This is log message $i";
            $logContent .= "[2023-01-01 12:" . sprintf("%02d", $i) . ":00] production.INFO: $message\n";
        }
        
        $logFile = 'laravel-search.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogsWithPagination');
        $method->setAccessible(true);
        
        // Call the method with search query
        $logs = $method->invoke($this->controller, $logFile, null, 'SEARCHABLE', 1);
        
        // Assert that only matching logs are returned
        foreach ($logs as $log) {
            $this->assertStringContainsString('SEARCHABLE', $log['message']);
            $this->assertTrue($log['has_search_match']);
        }
    }

    /**
     * Test handling of malformed log entries
     */
    public function testMalformedLogEntries()
    {
        // Create a test log file with some malformed entries
        $logContent = <<<EOT
This is not a valid log entry
[2023-01-01 12:00:00] production.INFO: This is a valid log entry
Another invalid entry
[2023-01-01 12:01:00] production.ERROR: This is another valid entry
EOT;
        
        $logFile = 'laravel-malformed.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method
        $logs = $method->invoke($this->controller, $logFile);
        
        // Assert that only valid log entries are parsed
        $this->assertCount(2, $logs);
        $this->assertEquals('This is a valid log entry', $logs[0]['message']);
        $this->assertEquals('This is another valid entry', $logs[1]['message']);
    }

    /**
     * Test handling of empty log files
     */
    public function testEmptyLogFile()
    {
        // Create an empty log file
        $logFile = 'laravel-empty.log';
        $this->createTestLogFile($logFile, '');
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method
        $logs = $method->invoke($this->controller, $logFile);
        
        // Assert that an empty array is returned
        $this->assertIsArray($logs);
        $this->assertEmpty($logs);
    }
}