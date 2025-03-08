<script>
    // Highlight search terms with better performance
    function highlightText(text, query) {
        if (!query || typeof text !== 'string') return text;
        
        try {
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<span class="search-highlight">$1</span>');
        } catch (e) {
            // If regex fails, return original text
            return text;
        }
    }
    // Format array/object content for better display
    function formatArrayContent(text) {
        if (!text || typeof text !== 'string') return text;
        
        // Trim the text to remove extra spaces
        text = text.trim();
        
        // Check if text contains array or object notation
        if (text.includes('array (') || text.includes('stdClass Object') || 
            text.includes('{') || text.includes('[')) {
            // Wrap the content in a pre tag for better formatting
            return '<pre>' + text + '</pre>';
        }
        return text;
    }
    // Decode HTML entities in text
    function decodeHtmlEntities(text) {
        if (!text || typeof text !== 'string') return text;
        
        const textarea = document.createElement('textarea');
        textarea.innerHTML = text;
        return textarea.value;
    }
    // Initialize event handlers when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle message expansion/collapse
        document.querySelectorAll('.toggle-message').forEach(function(button) {
            button.addEventListener('click', function() {
                const container = this.closest('.log-message-container');
                const preview = container.querySelector('.log-message-preview');
                const full = container.querySelector('.log-message-full');
                
                if (this.dataset.action === 'expand') {
                    preview.classList.add('expanded');
                    full.classList.add('show');
                    this.dataset.action = 'collapse';
                    this.textContent = 'Collapse';
                } else {
                    preview.classList.remove('expanded');
                    full.classList.remove('show');
                    this.dataset.action = 'expand';
                    this.textContent = 'Expand';
                }
            });
        });
        
        // Copy log message to clipboard
        document.querySelectorAll('.copy-log').forEach(function(button) {
            button.addEventListener('click', function() {
                const message = decodeHtmlEntities(this.dataset.message);
                navigator.clipboard.writeText(message).then(function() {
                    // Show feedback
                    const originalTitle = button.getAttribute('title');
                    button.setAttribute('title', 'Copied!');
                    setTimeout(function() {
                        button.setAttribute('title', originalTitle);
                    }, 1500);
                });
            });
        });
        
        // Expand all logs
        const expandAllButton = document.querySelector('.expand-all-logs');
        if (expandAllButton) {
            expandAllButton.addEventListener('click', function() {
                document.querySelectorAll('.log-message-preview').forEach(function(preview) {
                    preview.classList.add('expanded');
                });
                document.querySelectorAll('.log-message-full').forEach(function(full) {
                    full.classList.add('show');
                });
                document.querySelectorAll('.toggle-message').forEach(function(button) {
                    button.dataset.action = 'collapse';
                    button.textContent = 'Collapse';
                });
            });
        }
        
        // Collapse all logs
        const collapseAllButton = document.querySelector('.collapse-all-logs');
        if (collapseAllButton) {
            collapseAllButton.addEventListener('click', function() {
                document.querySelectorAll('.log-message-preview').forEach(function(preview) {
                    preview.classList.remove('expanded');
                });
                document.querySelectorAll('.log-message-full').forEach(function(full) {
                    full.classList.remove('show');
                });
                document.querySelectorAll('.toggle-message').forEach(function(button) {
                    button.dataset.action = 'expand';
                    button.textContent = 'Expand';
                });
            });
        }
        
        // Search buttons functionality
        const searchCurrentButton = document.querySelector('.search-current');
        if (searchCurrentButton) {
            searchCurrentButton.addEventListener('click', function() {
                const form = document.querySelector('input[name="query"]').closest('form');
                form.submit();
            });
        }
        
        const searchAllButton = document.querySelector('.search-all');
        if (searchAllButton) {
            searchAllButton.addEventListener('click', function() {
                const query = document.querySelector('input[name="query"]').value;
                const level = document.querySelector('select[name="level"]')?.value || '';
                window.location.href = `${window.location.origin}/${window.location.pathname.split('/')[1]}/search?query=${encodeURIComponent(query)}&level=${encodeURIComponent(level)}`;
            });
        }
    });
</script>