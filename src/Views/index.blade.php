@extends('log-viewer::layout')

@section('header')
    <div class="row g-3">
        <div class="col-md-3">
            <form action="{{ route('log-viewer.index') }}" method="GET" class="d-flex">
                <select name="file" class="form-select" onchange="this.form.submit()">
                    @foreach($files as $f)
                        <option value="{{ $f }}" {{ $currentFile === $f ? 'selected' : '' }}>
                            {{ $f }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="col-md-2">
            <form action="{{ route('log-viewer.index') }}" method="GET" class="d-flex">
                <input type="hidden" name="file" value="{{ $currentFile }}">
                <select name="level" class="form-select" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    @foreach($levels as $level)
                        <option value="{{ $level }}" {{ $currentLevel === $level ? 'selected' : '' }}>
                            {{ ucfirst($level) }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="col-md-5">
            <div class="input-group">
                <button class="btn btn-primary search-current" type="button" title="Search Current">
                    <i class="bi bi-search"></i>
                </button>
                <form action="{{ route('log-viewer.index') }}" method="GET" class="d-flex flex-grow-1">
                    <input type="hidden" name="file" value="{{ $currentFile }}">
                    <input type="hidden" name="level" value="{{ $currentLevel }}">
                    <input type="text" name="query" class="form-control" placeholder="Current File <- Search -> All Files" value="{{ $query }}">
                </form>
                <button class="btn btn-secondary search-all" type="button" title="Search All">
                    <i class="bi bi-search-heart"></i>
                </button>
            </div>
        </div>
        <div class="col-md-2">
            <a href="{{ route('log-viewer.index', ['file' => $currentFile]) }}" class="btn btn-outline-danger ms-2" title="Clear all filters">
                <i class="bi bi-x-circle"></i> Clear Filters
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="log-container">
        @if(count($logs) > 0)
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-secondary">{{ count($logs) }} logs</span>
                    @if(isset($hasMoreLogs) && $hasMoreLogs)
                        <span class="badge bg-info ms-2">Showing page {{ $currentPage }}</span>
                    @endif
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary expand-all-logs">Expand All</button>
                    <button class="btn btn-sm btn-outline-secondary collapse-all-logs">Collapse All</button>
                </div>
            </div>
            <table class="log-table">
                <thead>
                    <tr>
                        <th width="120">Level</th>
                        <th width="160">Date</th>
                        <th>Message</th>
                        <th width="80">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>
                                <span class="log-level log-level-{{ $log['level'] }}">
                                    {{ $log['level'] }}
                                </span>
                            </td>
                            <td class="log-date">{{ $log['date'] }}</td>
                            <td>
                                @if($log['is_long'])
                                    <div class="log-message-container">
                                        <div class="log-message log-message-preview {{ $log['has_search_match'] ? 'expanded' : '' }}">@if($query){!! Str::limit(explode("\n", $log['message'])[0], 100) !!}@else{{ Str::limit(explode("\n", $log['message'])[0], 100) }}@endif</div>
                                        <pre class="log-message-full {{ $log['has_search_match'] ? 'show' : '' }}">@if($query){!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="search-highlight">$1</span>', htmlspecialchars($log['message'])) !!}@else{{ $log['message'] }}@endif</pre>
                                    </div>
                                @else
                                    <div class="log-message">@if($query){!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="search-highlight">$1</span>', htmlspecialchars($log['message'])) !!}@else{{ $log['message'] }}@endif</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($log['is_long'])
                                    <button class="btn btn-sm btn-outline-secondary toggle-message" 
                                            data-action="{{ $log['has_search_match'] ? 'collapse' : 'expand' }}"
                                            title="{{ $log['has_search_match'] ? 'Collapse' : 'Expand' }} message">
                                        <i class="bi bi-{{ $log['has_search_match'] ? 'arrows-collapse' : 'arrows-expand' }}"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-sm btn-outline-secondary copy-log" 
                                            data-message="{{ $log['message'] }}"
                                            title="Copy message">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if(isset($hasMoreLogs) && $hasMoreLogs)
                <div class="pagination-container p-3 bg-light border-top d-flex justify-content-between align-items-center">
                    <div>
                        @if($currentPage > 1)
                            <a href="{{ route('log-viewer.index', ['file' => $currentFile, 'level' => $currentLevel, 'query' => $query, 'page' => $currentPage - 1]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Previous Page
                            </a>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('log-viewer.index', ['file' => $currentFile, 'level' => $currentLevel, 'query' => $query, 'page' => $currentPage + 1]) }}" class="btn btn-sm btn-outline-primary">
                            Next Page <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <h3>No logs found</h3>
                <p>No log entries were found matching your criteria.</p>
            </div>
        @endif
    </div>
@endsection