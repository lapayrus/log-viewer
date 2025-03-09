# Log Viewer Testing Guide

This document outlines the testing approach for the Log Viewer package.

## Testing Setup

### Backend Testing

The package uses PHPUnit for backend testing. The tests are organized into:

- **Unit Tests**: Test individual components in isolation
- **Feature Tests**: Test complete features and workflows

### Frontend Testing

The package uses Jest for JavaScript testing.

## Running Tests

### Backend Tests

```bash
# Run all tests
phpunit

# Run specific test file
phpunit tests/Unit/LogViewerControllerTest.php

# Run specific test method
phpunit --filter testGetLogFiles
```

### Frontend Tests

```bash
# Run JavaScript tests
npm test
```

## Test Coverage

### Backend Test Coverage

#### Unit Tests

- **LogViewerControllerTest**: Tests the controller methods
  - `testGetLogFiles`: Verifies log files are retrieved correctly
  - `testGetLogs`: Verifies log parsing works correctly
  - `testGetLogsWithLevelFilter`: Tests log filtering by level
  - `testGetLogsWithSearchQuery`: Tests log searching functionality

#### Feature Tests

- **LogViewerLargeFileTest**: Tests handling of large log files
  - Tests pagination functionality
  - Tests performance with large files
  - Tests memory usage optimization

- **LogViewerSearchTest**: Tests search functionality across files
  - Tests searching in single files
  - Tests searching across multiple files
  - Tests combined search and level filtering

### Frontend Test Coverage

- **LogViewerUITest**: Tests UI interactions
  - Tests expand/collapse functionality
  - Tests copy to clipboard functionality
  - Tests search highlighting
  - Tests pagination controls

## Test Data

### Sample Log Files

Create test log files with various formats and sizes:

1. **Small log file**: A few entries for basic testing
2. **Medium log file**: Hundreds of entries for pagination testing
3. **Large log file**: Thousands of entries for performance testing
4. **Malformed log file**: Entries with incorrect format for error handling testing

### Log Entry Types

Include various log entry types in test files:

- Different log levels (info, error, warning, debug, etc.)
- Single-line and multi-line messages
- Messages with stack traces
- Messages with special characters

## Test Cases

### Backend Test Cases

1. **Log File Listing**
   - Verify all log files are listed
   - Verify files are sorted correctly (newest first)
   - Verify file size information is correct

2. **Log Parsing**
   - Verify log entries are parsed correctly
   - Verify log levels are extracted correctly
   - Verify timestamps are extracted correctly
   - Verify multi-line messages are handled correctly

3. **Filtering**
   - Verify filtering by log level works
   - Verify filtering preserves pagination state

4. **Searching**
   - Verify searching within a file works
   - Verify searching across files works
   - Verify search highlighting works
   - Verify search with level filtering works

5. **Pagination**
   - Verify pagination controls work correctly
   - Verify page navigation preserves search/filter state
   - Verify large files are paginated correctly

6. **Error Handling**
   - Verify handling of non-existent files
   - Verify handling of permission issues
   - Verify handling of malformed log entries

### Frontend Test Cases

1. **UI Controls**
   - Verify expand/collapse buttons work for individual entries
   - Verify expand all/collapse all buttons work
   - Verify copy button copies correct content

2. **Search Highlighting**
   - Verify search terms are highlighted in results
   - Verify highlighting works in expanded view

3. **Pagination Controls**
   - Verify pagination controls are displayed correctly
   - Verify page navigation works

4. **Responsive Design**
   - Verify UI adapts to different screen sizes
   - Verify mobile view is usable

## Edge Cases

1. **Empty Log Files**
   - Test behavior with empty log files

2. **Very Large Log Files**
   - Test performance with files > 100MB
   - Verify memory usage remains reasonable

3. **Special Characters**
   - Test with log entries containing special characters
   - Test with log entries containing HTML/JavaScript

4. **Concurrent Access**
   - Test multiple users viewing logs simultaneously

## Automated Testing Integration

1. **CI/CD Integration**
   - Configure GitHub Actions or other CI system to run tests automatically
   - Ensure tests run on multiple PHP versions

2. **Code Coverage**
   - Generate code coverage reports
   - Aim for >80% code coverage

## Manual Testing Checklist

- [ ] Verify all log files are displayed correctly
- [ ] Test search functionality with various queries
- [ ] Test filtering by different log levels
- [ ] Test pagination with large files
- [ ] Test expand/collapse functionality
- [ ] Test copy to clipboard functionality
- [ ] Test on different browsers (Chrome, Firefox, Safari)
- [ ] Test on mobile devices
- [ ] Test with screen readers for accessibility

## Performance Testing

- [ ] Measure load time with large log files
- [ ] Measure memory usage with large log files
- [ ] Measure search performance across multiple files
- [ ] Test with concurrent users