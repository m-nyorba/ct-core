# CT Core Plugin

Core blocks functionality for WordPress block themes.

## Features

- Custom Gutenberg blocks
- Block patterns support
- Extensible block architecture
- SCF (Smart Custom Fields) integration

## Installation

1. Upload the plugin files to `/wp-content/plugins/ct-core/`
2. Activate the plugin through the 'Plugins' menu in WordPress

## Blocks

### Content Display Block
Located in `blocks/content-display/` - displays custom content layouts.

## Premium Version

For premium features including:
- Additional premium blocks
- Theme-specific patterns, styles, templates
- Advanced customizer options
- Freemius licensing

Install **CT Core Pro** plugin.

## Development

### Adding a New Block

1. Create folder: `blocks/{block-name}/`
2. Add `block.json` with block configuration
3. Add block assets (JS, CSS)
4. Register block in loader

### File Structure

```
ct-core/
├── ct-core.php              # Main plugin file
├── class-ct-core-loader.php              # Core loader class
├── includes/               # Core classes
│   ├── class-ct-core-admin.php
│   └── class-ct-core-licensing.php
├── modules/                # Theme modules
│   ├── true-focus/
│   ├── focus-studio/
│   └── lensly/
├── admin/                  # Admin interface
│   ├── views/
│   └── assets/
└── languages/              # Translation files
```

## License

GPL-2.0-or-later

## Author

CT Core - [https://mnor.top](https://mnor.top)
