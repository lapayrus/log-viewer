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
        $needsPagination = $fileSize > 5 * 1024 * 1024; // 5MB threshold
        
        // Get logs with pagination if needed
        if ($needsPagination) {
            $logs = $this->getLogsWithPagination($file, $level, $query, $page);
            $hasMoreLogs = count($logs) >= 100; // We're loading 100 logs per page
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
     * Load more logs via AJAX for infinite scrolling.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadMore(Request $request)
    {
        $file = $request->input('file');
        $level = $request->input('level');
        $query = $request->input('query');
        $page = $request->input('page', 1);
        
        $logs = $this->getLogsWithPagination($file, $level, $query, $page);
        $hasMoreLogs = count($logs) >= 100; // We're loading 100 logs per page
        
        $html = '';
        foreach ($logs as $log) {
            $html .= view('log-viewer::partials.log-row', [
                'log' => $log
            ])->render();
        }
        
        return response()->json([
            'html' => $html,
            'hasMoreLogs' => $hasMoreLogs
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
        
        // Open the file for reading
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }
        
        $currentEntry = null;
        $inEntry = false;
        
        // Read the file line by line
        while (($line = fgets($handle)) !== false) {
            // Check if this line starts a new log entry
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+):/', $line, $matches)) {
                // If we were processing an entry, finalize it
                if ($inEntry && $currentEntry) {
                    // Apply filters
                    $shouldInclude = true;
                    
                    if ($level && strtolower($currentEntry['level']) !== strtolower($level)) {
                        $shouldInclude = false;
                    }
                    
                    if ($query && stripos($currentEntry['message'], $query) === false && 
                        stripos($currentEntry['date'], $query) === false) {
                        $shouldInclude = false;
                    }
                    
                    if ($shouldInclude) {
                        $matchCount++;
                        
                        // Only include if it's in our page range
                        if ($matchCount > $offset && $matchCount <= $offset + $perPage) {
                            $logs[] = [
                                'level' => $currentEntry['level'],
                                'date' => $currentEntry['date'],
                                'environment' => $currentEntry['environment'],
                                'message' => $this->formatMessage($currentEntry['message']),
                                'is_long' => (substr_count($currentEntry['message'], "\n") > 1 || 
                                             strlen($currentEntry['message']) > 300),
                                'has_search_match' => $query && 
                                                   (stripos($currentEntry['message'], $query) !== false || 
                                                    stripos($currentEntry['date'], $query) !== false),
                            ];
                        }
                        
                        // If we've collected enough logs for this page, stop
                        if ($matchCount >= $offset + $perPage) {
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
                    'message' => trim(substr($line, strpos($line, ':') + 1)),
                ];
                
                $inEntry = true;
            } elseif ($inEntry && $currentEntry) {
                // Append to the current entry's message
                $currentEntry['message'] .= "\n" . $line;
            }
        }
        
        // Process the last entry
        if ($inEntry && $currentEntry) {
            $shouldInclude = true;
            
            if ($level && strtolower($currentEntry['level']) !== strtolower($level)) {
                $shouldInclude = false;
            }
            
            if ($query && stripos($currentEntry['message'], $query) === false && 
                stripos($currentEntry['date'], $query) === false) {
                $shouldInclude = false;
            }
            
            if ($shouldInclude) {
                $matchCount++;
                
                if ($matchCount > $offset && $matchCount <= $offset + $perPage) {
                    $logs[] = [
                        'level' => $currentEntry['level'],
                        'date' => $currentEntry['date'],
                        'environment' => $currentEntry['environment'],
                        'message' => $this->formatMessage($currentEntry['message']),
                        'is_long' => (substr_count($currentEntry['message'], "\n") > 1 || 
                                     strlen($currentEntry['message']) > 300),
                        'has_search_match' => $query && 
                                            (stripos($currentEntry['message'], $query) !== false || 
                                             stripos($currentEntry['date'], $query) !== false),
                    ];
                }
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
        // Trim the message to remove extra spaces
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
        
        return $message;
    }
}