<table width="100%">
	<tr>
		<td align="left" width="70%">
			<strong>Linchpin Coding Standards</strong><br />
			WordPress coding standards, enhanced for modern development.
		</td>
		<td align="center" width="30%">
			<a href="https://packagist.org/packages/linchpin/coding-standards"><img src="https://img.shields.io/packagist/v/linchpin/coding-standards.svg" /></a>
		</td>
	</tr>
	<tr>
		<td>
			A <strong>Linchpin</strong> project.
		</td>
		<td align="center" width="30%">
		</td>
	</tr>
</table>

This is a codified version of WordPress coding standards with Linchpin customizations. We include phpcs rules for PHP code quality and consistency.

## Contributing

We welcome contributions to these standards and want to make the experience as seamless as possible. To learn more about contributing, please reference the [CONTRIBUTING.md](CONTRIBUTING.md) file.

## Setup

Install via Composer:

```bash
composer require --dev linchpin/coding-standards
```

## Using PHPCS

Run the following command to run the standards checks:

```
vendor/bin/phpcs --standard=vendor/linchpin/coding-standards .
```

We use the [DealerDirect phpcodesniffer-composer-installer](https://github.com/Dealerdirect/phpcodesniffer-composer-installer) package to handle `installed_paths` for PHPCS when first installing the Linchpin ruleset. If you an error such as `ERROR: Referenced sniff "WordPress-Core" does not exist`, delete the `composer.lock` file and `vendor` directories and re-install Composer dependencies.   

The final `.` here specifies the files you want to test; this is typically the current directory (`.`), but you can also selectively check files or directories by specifying them instead.

You can add this to your GitHub Actions workflow or CI configuration:

```yaml
- name: Run PHPCS
  run: vendor/bin/phpcs --standard=vendor/linchpin/coding-standards .
```

### Excluding Files

This standard includes special support for a `.phpcsignore` file (in the future, this should be [built into phpcs itself](https://github.com/squizlabs/PHP_CodeSniffer/issues/1884)). Simply place a `.phpcsignore` file in your root directory (wherever you're going to run `phpcs` from).

The format of this file is similar to `.gitignore` and similar files: one pattern per line, comment lines should start with a `#`, and whitespace-only lines are ignored:

```
# Exclude our tests directory.
tests/

# Exclude any file ending with ".inc"
*\.inc
```

Note that the patterns should match [the PHP_CodeSniffer style](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage#ignoring-files-and-folders): `*` is translated to `.*` for convenience, but all other characters work like a regular expression.

Patterns are relative to the directory that the `.phpcsignore` file lives in. On load, they are translated to absolute patterns: e.g. `*/tests/*` in `/your/dir/.phpcsignore` will become `/your/dir/.*/tests/.*` as a regular expression. **This differs from the regular PHP_CodeSniffer practice.**


### Advanced/Extending

If you want to add further rules (such as WordPress.com VIP-specific rules) or customize PHPCS defaults, you can create your own custom standard file (e.g. `phpcs.ruleset.xml`):

```xml
<?xml version="1.0"?>
<ruleset>
	<!-- Files or directories to check -->
	<file>.</file>

	<!-- Path to strip from the front of file paths inside reports (displays shorter paths) -->
	<arg name="basepath" value="." />

	<!-- Set a minimum PHP version for PHPCompatibility -->
	<config name="testVersion" value="7.2-" />

	<!-- Use Linchpin Coding Standards -->
	<rule ref="vendor/linchpin/coding-standards" />

	<!-- Add VIP-specific rules -->
	<rule ref="WordPress-VIP" />
</ruleset>
```

You can then reference this file when running phpcs:

```
vendor/bin/phpcs --standard=phpcs.ruleset.xml .
```


#### Excluding/Disabling Checks

You can also customise the rule to exclude elements if they aren't applicable to the project:

```xml
<rule ref="vendor/humanmade/coding-standards">
	<!-- Disable a specific rule -->
	<exclude name="Linchpin.Whitespace.MultipleEmptyLines" />
</rule>
```

Rules can also be disabled inline. [phpcs rules can be disabled](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage#ignoring-parts-of-a-file) with a `// @codingStandardsIgnoreLine` comment.

To find out what these codes are, specify `-s` when running `phpcs`, and the code will be output as well. You can specify a full code, or a partial one to disable groups of errors.


## Included Checks

The phpcs standard is based upon the `WordPress-VIP` standard from [WordPress Coding Standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards), with [customisation and additions](Linchpin/ruleset.xml) to match our style guide.
