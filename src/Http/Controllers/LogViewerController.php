<?php

namespace Lapayrus\LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LogViewerController extends Controller
{
    /**
     * The log levels.
     *
     * @var array
     */
    protected $levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    /**
     * Show the log viewer dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $files = $this->getLogFiles();
        $file = $request->input('file', count($files) > 0 ? $files[0] : null);
        $level = $request->input('level');
        $query = $request->input('query');
        $page = $request->input('page', 1);
        
        // Get file size to determine if we need pagination
        $path = storage_path(config('log-viewer.path') . '/' . $file);
        $fileSize = File::exists($path) ? File::size($path) : 0;
        $largeFileThreshold = config('log-viewer.large_file_threshold', 5) * 1024 * 1024; // Get threshold from config
        $needsPagination = $fileSize > $largeFileThreshold;
        
        // Get logs with pagination if needed
        if ($needsPagination) {
            $logs = $this->getLogsWithPagination($file, $level, $query, $page);
            
            // Check if we need to show pagination controls
            // If we have a search query, we need to check if there are more matching logs
            // by trying to fetch one more log beyond our current page
            if (!empty($query) || !empty($level)) {
                // We're already using the improved getLogsWithPagination method that properly filters
                // If we got a full page of results (100 logs), there might be more
                $hasMoreLogs = count($logs) >= 100;
                
                // If we're on page > 1, we definitely have previous pages
                $hasMoreLogs = $hasMoreLogs || $page > 1;
            } else {
                // No search/filter, just check if we got a full page
                $hasMoreLogs = count($logs) >= 100;
            }
        } else {
            $logs = $this->getLogs($file, $level, $query);
            $hasMoreLogs = false;
        }
        
        return view('log-viewer::index', [
            'files' => $files,
            'currentFile' => $file,
            'levels' => $this->levels,
            'currentLevel' => $level,
            'logs' => $logs,
            'query' => $query,
            'hasMoreLogs' => $hasMoreLogs,
            'currentPage' => $page,
        ]);
    }

    /**
     * Show a specific log file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $file
     * @return \Illuminate\View\View
     */
    public function show(Request $request, $file)
    {
        $level = $request->input('level');
        $query = $request->input('query');
        $page = $request->input('page', 1);
        
        // Redirect to index with file parameter
        return redirect()->route('log-viewer.index', [
            'file' => $file,
            'level' => $level,
            'query' => $query,
            'page' => $page,
        ]);
    }

    /**
     * Search logs across all files.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $files = $this->getLogFiles();
        $query = $request->input('query');
        $level = $request->input('level');

        $results = [];

        if (!empty($query)) {
            foreach ($files as $file) {
                $logs = $this->getLogs($file, $level, $query);
                
                if (count($logs) > 0) {
                    $results[$file] = $logs;
                }
            }
        }

        return view('log-viewer::search', [
            'files' => $files,
            'levels' => $this->levels,
            'currentLevel' => $level,
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Get all log files.
     *
     * @return array
     */
    protected function getLogFiles()
    {
        $path = storage_path(config('log-viewer.path'));
        $pattern = config('log-viewer.pattern');

        $files = glob($path . '/' . $pattern);
        $files = array_map(function ($file) use ($path) {
            return str_replace($path . '/', '', $file);
        }, $files);

        return array_reverse($files);
    }
    
    /**
     * Get logs from a specific file.
     *
     * @param  string  $file
     * @param  string|null  $level
     * @param  string|null  $query
     * @return array
     */
    protected function getLogs($file, $level = null, $query = null)
    {
        if (!$file) {
            return [];
        }

        $path = storage_path(config('log-viewer.path') . '/' . $file);

        if (!File::exists($path)) {
            return [];
        }

        $logs = [];
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*?)(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \w+\.\w+:|$)/s';
        
        $content = File::get($path);
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $date = $match[1];
            $environment = $match[2];
            $logLevel = strtolower($match[3]);
            $message = trim($match[4]);
            
            // Filter by level if specified
            if ($level && $logLevel !== strtolower($level)) {
                continue;
            }
            
            // Filter by query if specified
            if ($query && stripos($message, $query) === false && stripos($date, $query) === false) {
                continue;
            }
            
            $logs[] = [
                'level' => $logLevel,
                'date' => $date,
                'environment' => $environment,
                'message' => $this->formatMessage($message),
                'is_long' => (substr_count($message, "\n") > 1 || strlen($message) > 300),
                'has_search_match' => $query && (stripos($message, $query) !== false || stripos($date, $query) !== false),
            ];
        }

        return $logs;
    }

    /**
     * Get logs from a specific file with pagination for large files.
     *
     * @param  string  $file
     * @param  string|null  $level
     * @param  string|null  $query
     * @param  int  $page
     * @return array
     */
    protected function getLogsWithPagination($file, $level = null, $query = null, $page = 1)
    {
        if (!$file) {
            return [];
        }
    
        $path = storage_path(config('log-viewer.path') . '/' . $file);
    
        if (!File::exists($path)) {
            return [];
        }
        
        $perPage = 100;
        $offset = ($page - 1) * $perPage;
        $logs = [];
        $matchCount = 0;
        $skipCount = 0;
        
        // Use a memory-efficient approach with file streaming
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }
        
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+):/'; 
        $currentEntry = null;
        $inEntry = false;
        $buffer = '';
        
        // If we're not on the first page, we need to count matching entries until we reach our offset
        if ($page > 1) {
            $targetSkipCount = $offset;
            
            // Read the file line by line
            while (($line = fgets($handle)) !== false) {
                // Check if this line starts a new log entry
                if (preg_match($pattern, $line, $matches)) {
                    // If we were processing an entry, finalize it
                    if ($inEntry && $currentEntry) {
                        // Extract message from buffer
                        $message = trim(substr($buffer, strpos($buffer, ':') + 1));
                        
                        // Apply filters
                        $shouldInclude = true;
                        
                        if ($level && strtolower($currentEntry['level']) !== strtolower($level)) {
                            $shouldInclude = false;
                        }
                        
                        if ($query && stripos($message, $query) === false && 
                            stripos($currentEntry['date'], $query) === false) {
                            $shouldInclude = false;
                        }
                        
                        if ($shouldInclude) {
                            $skipCount++;
                            if ($skipCount >= $targetSkipCount) {
                                // We've skipped enough entries, start collecting from here
                                // Reset the buffer and prepare to process this entry
                                $buffer = $line;
                                $date = $matches[1];
                                $environment = $matches[2];
                                $logLevel = strtolower($matches[3]);
                                
                                $currentEntry = [
                                    'level' => $logLevel,
                                    'date' => $date,
                                    'environment' => $environment,
                                ];
                                break;
                            }
                        }
                    }
                    
                    // Start a new entry
                    $date = $matches[1];
                    $environment = $matches[2];
                    $logLevel = strtolower($matches[3]);
                    
                    $currentEntry = [
                        'level' => $logLevel,
                        'date' => $date,
                        'environment' => $environment,
                    ];
                    
                    $buffer = $line;
                    $inEntry = true;
                } elseif ($inEntry) {
                    // Append to the buffer
                    $buffer .= $line;
                }
            }
            
            // If we couldn't skip to the right position, return empty
            if ($skipCount < $targetSkipCount) {
                fclose($handle);
                return [];
            }
        }
        
        // Now collect the logs for this page
        $buffer = $buffer ?: '';
        $inEntry = !empty($buffer);
        $foundMatchesCount = 0;
        
        while ($foundMatchesCount < $perPage) {
            if (!$inEntry) {
                // Try to start a new entry
                $line = fgets($handle);
                if ($line === false) {
                    break; // End of file
                }
                
                if (preg_match($pattern, $line, $matches)) {
                    $date = $matches[1];
                    $environment = $matches[2];
                    $logLevel = strtolower($matches[3]);
                    
                    $currentEntry = [
                        'level' => $logLevel,
                        'date' => $date,
                        'environment' => $environment,
                    ];
                    
                    $buffer = $line;
                    $inEntry = true;
                }
                continue;
            }
            
            // Read until we find the next log entry
            $line = fgets($handle);
            
            // If we've reached the end of the file or found a new entry
            if ($line === false || preg_match($pattern, $line, $matches)) {
                // Process the completed entry
                $message = trim(substr($buffer, strpos($buffer, ':') + 1));
                
                // Apply filters
                $shouldInclude = true;
                
                if ($level && strtolower($currentEntry['level']) !== strtolower($level)) {
                    $shouldInclude = false;
                }
                
                $hasSearchMatch = $query && 
                               (stripos($message, $query) !== false || 
                                stripos($currentEntry['date'], $query) !== false);
                                
                if ($query && !$hasSearchMatch) {
                    $shouldInclude = false;
                }
                
                if ($shouldInclude) {
                    $logs[] = [
                        'level' => $currentEntry['level'],
                        'date' => $currentEntry['date'],
                        'environment' => $currentEntry['environment'],
                        'message' => $this->formatMessage($message),
                        'is_long' => (substr_count($message, "\n") > 1 || strlen($message) > 300),
                        'has_search_match' => $hasSearchMatch,
                    ];
                    $foundMatchesCount++;
                }
                
                // If we've reached the end of the file, break
                if ($line === false) {
                    break;
                }
                
                // Start a new entry
                $date = $matches[1];
                $environment = $matches[2];
                $logLevel = strtolower($matches[3]);
                
                $currentEntry = [
                    'level' => $logLevel,
                    'date' => $date,
                    'environment' => $environment,
                ];
                
                $buffer = $line;
            } else {
                // Append to the current entry's buffer
                $buffer .= $line;
            }
        }
        
        fclose($handle);
        
        return $logs;
    }

    /**
     * Format message to properly display arrays and objects.
     *
     * @param  string  $message
     * @return string
     */
    protected function formatMessage($message)
    {
        // Trim the message to remove extra spaces at the beginning and end
        $message = trim($message);
        
        // Check if message contains array or object notation
        if (preg_match('/array\s*\(|\{.*\}|stdClass Object/i', $message)) {
            // Ensure we preserve the complete structure with proper indentation
            $lines = explode("\n", $message);
            $formattedLines = [];
            
            foreach ($lines as $line) {
                $formattedLines[] = trim($line); // Trim each line to remove extra spaces
            }
            
            return implode("\n", $formattedLines);
        }
        
        // Remove any extra spaces between words while preserving single spaces
        // But keep newlines intact for proper formatting
        $lines = explode("\n", $message);
        $formattedLines = [];
        
        foreach ($lines as $line) {
            $formattedLines[] = trim(preg_replace('/\s+/', ' ', $line));
        }
        
        return implode("\n", $formattedLines);
    }
}