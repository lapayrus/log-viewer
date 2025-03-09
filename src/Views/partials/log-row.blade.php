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
                <div class="log-message log-message-preview {{ $log['has_search_match'] ? 'expanded' : '' }}">{{ Str::limit(explode("\n", $log['message'])[0], 100) }}</div>
                <pre class="log-message-full {{ $log['has_search_match'] ? 'show' : '' }}">{{ $log['message'] }}</pre>
            </div>
        @else
            <div class="log-message">{{ $log['message'] }}</div>
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