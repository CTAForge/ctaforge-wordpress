# CTAForge WordPress Plugin

Official WordPress plugin for [CTAForge](https://ctaforge.com) — connect your WordPress site to CTAForge and grow your email list.

[![CI](https://github.com/CTAForge/ctaforge-wordpress/actions/workflows/ci.yml/badge.svg)](https://github.com/CTAForge/ctaforge-wordpress/actions/workflows/ci.yml)
[![WordPress.org version](https://img.shields.io/wordpress/plugin/v/ctaforge)](https://wordpress.org/plugins/ctaforge/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)](LICENSE)

## Features

- **[ctaforge_form] shortcode** — drop signup forms anywhere on your site
- **Gutenberg block** — native block with full sidebar controls
- **WordPress user sync** — auto-subscribe new registrations to a list
- **WooCommerce integration** — sync customers and tag by purchase behaviour

## Installation (Development)

```bash
# Clone into your local WordPress install
cd wp-content/plugins
git clone https://github.com/CTAForge/ctaforge-wordpress.git ctaforge
cd ctaforge/trunk
composer install
```

Activate from **Plugins → Installed Plugins**, then go to **Settings → CTAForge**.

## Shortcode Usage

```
[ctaforge_form list_id="your-list-uuid"]
[ctaforge_form list_id="uuid" fields="first_name,last_name" button="Join the list" title="Weekly newsletter"]
```

## Release Process

1. Bump version in `trunk/ctaforge.php` (plugin header) and `trunk/readme.txt` (Stable tag)
2. Commit and push to `main`
3. Push a version tag: `git tag v1.0.1 && git push origin v1.0.1`
4. GitHub Actions automatically syncs `trunk/` → WordPress.org SVN

> **Requires:** `WP_SVN_USERNAME` and `WP_SVN_PASSWORD` stored as GitHub Actions secrets.

## WordPress.org SVN

The plugin slug is `ctaforge`. SVN structure is managed automatically by CI:

```
svn.wp-plugins.org/ctaforge/
├── trunk/         ← mirrors this repo's trunk/
├── tags/
│   └── 1.0.0/    ← created per release
└── assets/       ← screenshots and banner (mirrors assets/ in this repo)
```

## Contributing

Pull requests welcome. Please follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

```bash
# Check coding standards
cd trunk && composer lint

# Run tests
composer test
```

## License

GPL v2 or later — see [LICENSE](LICENSE).
