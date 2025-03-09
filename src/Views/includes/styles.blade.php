<style>
    :root {
        --primary-color: #4f46e5;
        --primary-hover: #4338ca;
        --light-text: #6b7280;
        --border-color: #e5e7eb;
        --background-color: #f9fafb;
        --header-bg: white;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        color: #1f2937;
        background-color: var(--background-color);
        line-height: 1.5;
    }

    .header {
        background-color: var(--header-bg);
        border-bottom: 1px solid var(--border-color);
        padding: 1rem 0;
        margin-bottom: 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .log-container {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .log-table {
        width: 100%;
        border-collapse: collapse;
    }

    .log-table th {
        text-align: left;
        padding: 0.75rem 1rem;
        font-weight: 600;
        color: var(--light-text);
        background-color: var(--background-color);
        border-bottom: 1px solid var(--border-color);
    }

    .log-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: top;
    }

    .log-level {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: white;
    }

    .log-level-debug { background-color: #9ca3af; }
    .log-level-info { background-color: #3b82f6; }
    .log-level-notice { background-color: #8b5cf6; }
    .log-level-warning { background-color: #f59e0b; }
    .log-level-error { background-color: #ef4444; }
    .log-level-critical { background-color: #dc2626; }
    .log-level-alert { background-color: #b91c1c; }
    .log-level-emergency { background-color: #7f1d1d; }

    .log-date {
        font-family: 'Fira Code', 'Courier New', Courier, monospace;
        font-size: 0.875rem;
        color: var(--light-text);
    }

    .log-message {
        font-family: 'Fira Code', 'Courier New', Courier, monospace;
        white-space: pre-wrap;
        word-break: break-word;
        max-width: 100%;
        overflow-x: auto;
        padding: 0;
        margin: 0;
    }

    .log-message-container {
        position: relative;
        width: 100%;
    }

    .log-message-preview {
        display: block;
        font-family: 'Fira Code', 'Courier New', Courier, monospace;
        white-space: pre-wrap;
        word-break: break-word;
        max-width: 100%;
        overflow-x: auto;
        padding: 0;
        margin: 0;
    }

    .log-message-preview.expanded {
        display: none;
    }

    .log-message-full {
        display: none;
        white-space: pre-wrap;
        word-break: break-word;
        font-family: 'Fira Code', 'Courier New', Courier, monospace;
        max-width: 100%;
        overflow-x: auto;
        padding: 0.5rem;
        margin: 0;
        background-color: #f8fafc;
        border-radius: 0.25rem;
        line-height: 1.4;
    }

    .log-message-full.show {
        display: block;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
        border-color: var(--primary-hover);
    }

    .form-select, .form-control {
        border-color: var(--border-color);
        padding: 0.5rem 1rem;
    }

    .form-select:focus, .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
    }

    /* Pagination styles */
    .pagination-container {
        background-color: var(--background-color);
        border-top: 1px solid var(--border-color);
    }
    
    .load-more-row td {
        background-color: var(--background-color);
        border-top: 1px solid var(--border-color);
    }
    
    .load-more-logs {
        padding: 0.375rem 1rem;
        font-size: 0.875rem;
    }
    
    /* Empty state styling */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--light-text);
        background-color: white;
        border-radius: 0.5rem;
        margin: 0;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #9ca3af;
    }
    
    .empty-state h3 {
        margin-bottom: 1rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .empty-state p {
        color: var(--light-text);
        max-width: 500px;
        margin: 0 auto;
    }

    /* Search highlight */
    .search-highlight {
        background-color: #fef08a;
        padding: 0.125rem 0;
        border-radius: 0.125rem;
    }
</style>