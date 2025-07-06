# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-07-07

### Added
- Comprehensive PHPUnit test suite
- ProcessFactory interface for dependency injection and improved testability
- DefaultProcessFactory implementation as default process creation strategy
- GitHub Actions CI workflow for automated testing
- Support for Symfony Process component ^5.4 and ^6.4

### Changed
- **BREAKING**: Minimum PHP version raised from 7.2 to 7.4
- **BREAKING**: Dropped PHPUnit ^8.5 support in favor of ^9.6
- **BREAKING**: Removed Symfony Process ^4.4 support
- InfoBuilder, JournalCtl and SystemCtl now accept optional ProcessFactory parameter for dependency injection

### Fixed
- Error message now correctly indicates 'restart' action in SystemCtl::restart()
- Variable naming in MemoryInfo to use bytes instead of kilobytes for accuracy
- Incorrect boolean return value in SystemCtl::showPropertyBool() parsing logic
- JournalCtl constructor now properly initializes outputFields and utc properties to prevent null return type issues

## [1.1.0] - 2023-05-08

### Added
- PHP 8 compatibility support

### Changed
- Updated Symfony Process component constraints to support versions 4.4 to <6.0

### Removed
- **BREAKING**: PHP 7.1 compatibility (minimum PHP version is now 7.2)

## [1.0.3] - 2019-08-22

### Added
- Service restart method to SystemCtl class

## [1.0.2] - 2019-08-22

### Fixed
- Code cleanup and improvements

## [1.0.1] - 2019-04-16

### Fixed
- Command line argument handling bugs
- Removed unused import statements

## [1.0.0] - 2019-04-02

### Added
- Initial release of PHP SystemD library
- SystemCtl class for interacting with systemctl commands
- JournalCtl class for interacting with journalctl commands
- InfoBuilder class for creating typed system information objects
- Support for service status, start, stop, enable/disable operations
- Journal log retrieval with configurable output formats
- System memory and uptime information retrieval

### Fixed
- Command line argument construction
- Better exception handling on parse failures