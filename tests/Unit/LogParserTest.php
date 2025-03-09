<?php

namespace Lapayrus\LogViewer\Tests\Unit;

use Illuminate\Support\Facades\File;
use Lapayrus\LogViewer\Http\Controllers\LogViewerController;
use PHPUnit\Framework\TestCase;

class LogParserTest extends TestCase
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
     * Test parsing of multi-line log entries
     */
    public function testParseMultiLineLogEntries()
    {
        // Create a test log file with multi-line log entries
        $logContent = <<<EOT
[2023-01-01 12:00:00] production.INFO: This is an info log message
[2023-01-01 12:01:00] production.ERROR: This is an error log message with
multiple lines
and a stack trace
[2023-01-01 12:02:00] production.DEBUG: This is a debug log message
EOT;
        
        $logFile = 'multiline-test.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method
        $logs = $method->invoke($this->controller, $logFile);
        
        // Assert that the logs are parsed correctly
        $this->assertCount(3, $logs);
        
        // Check the multi-line log entry
        $this->assertEquals('error', $logs[1]['level']);
        $this->assertEquals('2023-01-01 12:01:00', $logs[1]['date']);
        $this->assertEquals('production', $logs[1]['environment']);
        $this->assertEquals("This is an error log message with\nmultiple lines\nand a stack trace", $logs[1]['message']);
    }

    /**
     * Test parsing of malformed log entries
     */
    public function testParseMalformedLogEntries()
    {
        // Create a test log file with some malformed entries
        $logContent = <<<EOT
This line doesn't match the log format
[2023-01-01 12:00:00] production.INFO: This is a valid log entry
[2023-01-01 12:01:00 production.ERROR: This line has a missing bracket
[2023-01-01 12:02:00] production.DEBUG: This is another valid log entry
EOT;
        
        $logFile = 'malformed-test.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method
        $logs = $method->invoke($this->controller, $logFile);
        
        // Assert that only valid log entries are parsed
        $this->assertCount(2, $logs);
        
        // Check the first valid log entry
        $this->assertEquals('info', $logs[0]['level']);
        $this->assertEquals('This is a valid log entry', $logs[0]['message']);
        
        // Check the second valid log entry
        $this->assertEquals('debug', $logs[1]['level']);
        $this->assertEquals('This is another valid log entry', $logs[1]['message']);
    }

    /**
     * Test parsing of log entries with special characters
     */
    public function testParseLogEntriesWithSpecialCharacters()
    {
        // Create a test log file with special characters
        $logContent = <<<EOT
[2023-01-01 12:00:00] production.INFO: Log with HTML <div>content</div>
[2023-01-01 12:01:00] production.ERROR: Log with JSON {"key":"value"}
[2023-01-01 12:02:00] production.DEBUG: Log with special chars: !@#$%^&*()
EOT;
        
        $logFile = 'special-chars-test.log';
        $this->createTestLogFile($logFile, $logContent);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLogs');
        $method->setAccessible(true);
        
        // Call the method
        $logs = $method->invoke($this->controller, $logFile);
        
        // Assert that logs with special characters are parsed correctly
        $this->assertCount(3, $logs);
        
        // Check the HTML content log
        $this->assertEquals('info', $logs[0]['level']);
        $this->assertEquals('Log with HTML <div>content</div>', $logs[0]['message']);
        
        // Check the JSON content log
        $this->assertEquals('error', $logs[1]['level']);
        $this->assertEquals('Log with JSON {"key":"value"}', $logs[1]['message']);
        
        // Check the special characters log
        $this->assertEquals('debug', $logs[2]['level']);
        $this->assertEquals('Log with special chars: !@#$%^&*()', $logs[2]['message']);
    }
}