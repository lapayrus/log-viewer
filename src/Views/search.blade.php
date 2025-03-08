@extends('log-viewer::layout')

@section('header')
    <div class="row g-3">
        <div class="col-md-3">
            <form action="{{ route('log-viewer.search') }}" method="GET" class="d-flex">
                <input type="hidden" name="query" value="{{ $query }}">
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
        <div class="col-md-9">
            <div class="input-group">
                <form action="{{ route('log-viewer.search') }}" method="GET" class="d-flex flex-grow-1">
                    <input type="hidden" name="level" value="{{ $currentLevel }}">
                    <input type="text" name="query" class="form-control" placeholder="Search across all logs..." value="{{ $query }}">
                </form>
                <button class="btn btn-secondary search-all" type="button" title="Search across all files">
                    <i class="bi bi-search-heart"></i>
                </button>
                <button class="btn btn-primary search-current" type="button" title="Search in first file">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('log-viewer.index') }}" class="btn btn-outline-danger ms-2" title="Clear all filters">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="mb-4">
        <h2 class="h5">Search Results for "{{ $query }}"</h2>
        <p class="text-muted">Found results in {{ count($results) }} log files</p>
    </div>

    @if(count($results) > 0)
        @foreach($results as $file => $logs)
            <div class="log-container mb-4">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0">
                        <a href="{{ route('log-viewer.show', ['file' => $file, 'query' => $query, 'level' => $currentLevel]) }}">
                            {{ $file }}
                        </a>
                        <span class="badge bg-secondary">{{ count($logs) }} results</span>
                    </h3>
                    <div>
                        <button class="btn btn-sm btn-outline-primary expand-all-logs" data-file="{{ $file }}">Expand All</button>
                        <button class="btn btn-sm btn-outline-secondary collapse-all-logs" data-file="{{ $file }}">Collapse All</button>
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
                                            <div class="log-message log-message-preview {{ $log['has_search_match'] ? 'expanded' : '' }}">
                                                @if($query)
                                                    {!! Str::limit(explode("\n", $log['message'])[0], 100) !!}
                                                @else
                                                    {{ Str::limit(explode("\n", $log['message'])[0], 100) }}
                                                @endif
                                            </div>
                                            <pre class="log-message-full {{ $log['has_search_match'] ? 'show' : '' }}">@if($query){!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="search-highlight">$1</span>', htmlspecialchars($log['message'])) !!}@else{{ $log['message'] }}@endif</pre>
                                            <button class="btn btn-sm btn-link toggle-message" data-action="{{ $log['has_search_match'] ? 'collapse' : 'expand' }}">
                                                {{ $log['has_search_match'] ? 'Collapse' : 'Expand' }}
                                            </button>
                                        </div>
                                    @else
                                        <div class="log-message">@if($query){!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="search-highlight">$1</span>', htmlspecialchars($log['message'])) !!}@else{{ $log['message'] }}@endif</div>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary copy-log" 
                                            data-message="{{ $log['message'] }}"
                                            title="Copy message">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="log-container">
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h3>No results found</h3>
                <p>
                    No logs matching "{{ $query }}" were found
                    @if($currentLevel)
                        with level "{{ $currentLevel }}"
                    @endif.
                </p>
            </div>
        </div>
    @endif
@endsection