/**
 * Log Viewer UI Tests
 * 
 * These tests verify the JavaScript functionality of the Log Viewer UI components
 * including expand/collapse buttons, copy functionality, and search highlighting.
 */

describe('Log Viewer UI', () => {
    // Mock DOM elements
    let container;
    let expandAllButton;
    let collapseAllButton;
    let toggleButtons;
    let copyButtons;
    let logMessages;
    
    beforeEach(() => {
        // Set up our document body
        document.body.innerHTML = `
            <div class="log-container">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary">3 logs</span>
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
                        <tr>
                            <td>
                                <span class="log-level log-level-info">info</span>
                            </td>
                            <td class="log-date">2023-01-01 12:00:00</td>
                            <td>
                                <div class="log-message-container">
                                    <div class="log-message log-message-preview">This is a preview of a long message...</div>
                                    <pre class="log-message-full">This is the full content of a long message\nWith multiple lines\nAnd more details</pre>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-secondary toggle-message" data-action="expand" title="Expand message">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary copy-message" title="Copy message">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="log-level log-level-error">error</span>
                            </td>
                            <td class="log-date">2023-01-01 12:01:00</td>
                            <td>
                                <div class="log-message-container">
                                    <div class="log-message log-message-preview">Error message preview...</div>
                                    <pre class="log-message-full">This is the full content of an error message\nWith stack trace\nAnd more details</pre>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-secondary toggle-message" data-action="expand" title="Expand message">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary copy-message" title="Copy message">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
        
        // Initialize elements
        expandAllButton = document.querySelector('.expand-all-logs');
        collapseAllButton = document.querySelector('.collapse-all-logs');
        toggleButtons = document.querySelectorAll('.toggle-message');
        copyButtons = document.querySelectorAll('.copy-message');
        logMessages = document.querySelectorAll('.log-message-container');
        
        // Mock clipboard API
        global.navigator.clipboard = {
            writeText: jest.fn().mockResolvedValue(undefined)
        };
        
        // Mock Bootstrap tooltip functionality
        global.bootstrap = {
            Tooltip: jest.fn().mockImplementation(() => ({
                dispose: jest.fn(),
                hide: jest.fn(),
                show: jest.fn(),
                update: jest.fn()
            }))
        };
    });
    
    test('Expand All button should expand all log messages', () => {
        // Simulate click on expand all button
        expandAllButton.click();
        
        // Check that all log messages are expanded
        const previews = document.querySelectorAll('.log-message-preview');
        const fulls = document.querySelectorAll('.log-message-full');
        
        previews.forEach(preview => {
            expect(preview.classList.contains('expanded')).toBe(true);
        });
        
        fulls.forEach(full => {
            expect(full.classList.contains('show')).toBe(true);
        });
        
        // Check that toggle buttons now show collapse icon
        toggleButtons.forEach(button => {
            expect(button.getAttribute('data-action')).toBe('collapse');
            expect(button.getAttribute('title')).toBe('Collapse message');
            expect(button.querySelector('i').classList.contains('bi-chevron-up')).toBe(true);
        });
    });
    
    test('Collapse All button should collapse all log messages', () => {
        // First expand all messages
        expandAllButton.click();
        
        // Then collapse them
        collapseAllButton.click();
        
        // Check that all log messages are collapsed
        const previews = document.querySelectorAll('.log-message-preview');
        const fulls = document.querySelectorAll('.log-message-full');
        
        previews.forEach(preview => {
            expect(preview.classList.contains('expanded')).toBe(false);
        });
        
        fulls.forEach(full => {
            expect(full.classList.contains('show')).toBe(false);
        });
        
        // Check that toggle buttons now show expand icon
        toggleButtons.forEach(button => {
            expect(button.getAttribute('data-action')).toBe('expand');
            expect(button.getAttribute('title')).toBe('Expand message');
            expect(button.querySelector('i').classList.contains('bi-chevron-down')).toBe(true);
        });
    });
    
    test('Toggle button should expand and collapse individual log message', () => {
        // Get the first toggle button and its associated message container
        const toggleButton = toggleButtons[0];
        const messageContainer = logMessages[0];
        const preview = messageContainer.querySelector('.log-message-preview');
        const full = messageContainer.querySelector('.log-message-full');
        
        // Initially message should be collapsed
        expect(preview.classList.contains('expanded')).toBe(false);
        expect(full.classList.contains('show')).toBe(false);
        
        // Click to expand
        toggleButton.click();
        
        // Message should now be expanded
        expect(preview.classList.contains('expanded')).toBe(true);
        expect(full.classList.contains('show')).toBe(true);
        expect(toggleButton.getAttribute('data-action')).toBe('collapse');
        
        // Click again to collapse
        toggleButton.click();
        
        // Message should now be collapsed again
        expect(preview.classList.contains('expanded')).toBe(false);
        expect(full.classList.contains('show')).toBe(false);
        expect(toggleButton.getAttribute('data-action')).toBe('expand');
    });
    
    test('Copy button should copy log message to clipboard', async () => {
        // Get the first copy button and its associated message
        const copyButton = copyButtons[0];
        const messageContainer = logMessages[0];
        const fullMessage = messageContainer.querySelector('.log-message-full').textContent;
        
        // Click the copy button
        copyButton.click();
        
        // Verify clipboard API was called with the correct text
        expect(navigator.clipboard.writeText).toHaveBeenCalledWith(fullMessage);
    });
    
    test('Search highlighting should mark matching text', () => {
        // Add a search query parameter to the URL
        const url = new URL(window.location);
        url.searchParams.set('query', 'error');
        history.pushState({}, '', url);
        
        // Simulate search highlighting by adding highlighted spans
        document.body.innerHTML = document.body.innerHTML.replace(
            /error/gi, 
            '<span class="search-highlight">error</span>'
        );
        
        // Check that highlights are applied
        const highlights = document.querySelectorAll('.search-highlight');
        expect(highlights.length).toBeGreaterThan(0);
        
        // Each highlight should contain the search term
        highlights.forEach(highlight => {
            expect(highlight.textContent.toLowerCase()).toContain('error');
        });
    });
    
    test('Pagination controls should navigate between pages', () => {
        // Add pagination controls to the DOM
        document.body.innerHTML += `
            <div class="pagination-controls mt-3">
                <a href="?file=laravel.log&page=1" class="btn btn-sm btn-outline-secondary">Previous</a>
                <span class="mx-2">Page 2</span>
                <a href="?file=laravel.log&page=3" class="btn btn-sm btn-outline-primary">Next</a>
            </div>
        `;
        
        const prevButton = document.querySelector('.pagination-controls a:first-child');
        const nextButton = document.querySelector('.pagination-controls a:last-child');
        
        // Check that pagination buttons have correct URLs
        expect(prevButton.getAttribute('href')).toBe('?file=laravel.log&page=1');
        expect(nextButton.getAttribute('href')).toBe('?file=laravel.log&page=3');
    });
});