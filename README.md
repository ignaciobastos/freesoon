# FreeSoon — Coming Soon & Maintenance

A free WordPress plugin by [Ignacio Bastos](https://ignaciobastos.com/) with a live visual editor for coming soon and maintenance pages. No paid feature gates or advertising.

## Requirements

- WordPress 6.4 or later
- PHP 7.4 or later

## Features

- Inline text editing synchronized with sidebar controls.
- Four layouts, image/video backgrounds, gradients, and WordPress font discovery.
- Site or custom logos, automatic contrast, and transparent-margin trimming.
- Independent countdown visibility and scheduled opening.
- Optional contact fields and local subscriber records with CSV export.
- Privacy-document discovery and configurable footer links.

See [readme.txt](readme.txt) for installation, translations, data handling, limitations, and changelog.

## Development and installation

The repository root is the plugin directory. Place it in `wp-content/plugins/freesoon/`, then activate FreeSoon in WordPress. No build step is required. Never commit the surrounding WordPress installation, its database, uploads, or credentials.

The original PHP, JavaScript, and CSS sources are included. Lucide is bundled locally; its source reference and license are documented in readme.txt and assets/LUCIDE-LICENSE. Translation sources and compiled catalogs are included in languages/.

Internal `obra_` identifiers are retained for compatibility with existing settings.

## GitHub upload

Extract this archive and upload the **contents** of the freesoon folder to the repository root, including the hidden `.gitignore` file. Do not upload the ZIP itself as the source repository.

For a WordPress installation ZIP, include the plugin files inside a single top-level `freesoon/` folder. GitHub-generated source archives may use a different folder name; use a dedicated installation ZIP for releases.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE). Third-party license notices must be retained.
