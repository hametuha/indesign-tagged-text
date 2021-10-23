# InDesign Tagged text

Text converter to create a tagged text for InDesign from UTF-8 text file. Markdown ready.

## Installation

Install with composer.

```
composer require hametuha/indesign-tagged-text
```

## Usage

### Integration

```
// Load composer.
require_once __DIR__ . '/vendor/autoload.php';
// Make Instance.
$converter = new Hametuha\InDesignTaggedText( 'Mac', 'UNICODE' );
// Load contents from string.
// 2nd argument is markdown or not.
$converter->convert( 'path/to/manuscript.md', true );
// Export as a string.
// If argument is set "true",
// the content will be converted to new text encoding(e.g. UTF-16Be)
$tagged_text = $converter->export( true );
```

### From CLI

W.I.P.

### Supported Styles

W.I.P.

## Licens

MIT.