# Contributing

We welcome contributions to the Linchpin coding standards! These standards help maintain consistency across our WordPress projects and evolve as our practices improve.

## Guidelines for Rule Changes

**Bugfixes** are always welcome and can be released in minor or patch versions.

**New rules or major changes** require careful consideration. Our goal is to improve code quality without causing unnecessary disruption. Existing code should continue to pass unless we're intentionally making rules stricter. Breaking production code should be avoided unless there's a compelling reason.

**Relaxing rules** can typically be done in minor releases, but significant relaxations (like allowing different file naming conventions) should be major releases. Use your judgment to determine the appropriate version bump.

As long as there's team consensus on rule changes, they're good to go. If a rule is controversial, discuss it with the team before implementing.


## Testing

### Running Tests

To run tests, you'll need PHP CodeSniffer installed from source (not as a dist package).

First, install Composer dependencies:

```bash
composer install --prefer-source --dev
```

If you've already installed dependencies and need to switch PHP CodeSniffer to source:

```bash
rm -r vendor/squizlabs/php_codesniffer
composer install --prefer-source --dev
composer dump-autoload
```

### Writing Sniff Tests

Test files should mirror the directory structure of the sniffs they're testing. For example, to test `Linchpin/Sniffs/Layout/OrderSniff.php`, create:

```
Linchpin/Tests/Layout/OrderUnitTest.php # Unit test class
Linchpin/Tests/Layout/OrderUnitTest.inc # Test fixture code
```

The naming convention is simple: replace `Sniff.php` with `UnitTest.php`.

Here's a basic unit test template:

```php
<?php

namespace Linchpin\Tests\Layout;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Test class for OrderSniff.
 * 
 * Note: The class name must match the directory structure for autoloading.
 */
class OrderUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return [
			1  => 1, // line 1 expects 1 error.
		];
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return [];
	}

}
```


## Releasing

We use [release-please](https://github.com/googleapis/release-please) to automate our release process. Release-please monitors commits and automatically creates release PRs with updated version numbers and changelogs.

### Version Guidelines

- **Major releases**: Required when changes would break existing production code
- **Minor releases**: For new features, new rules, or making rules more lenient
- **Patch releases**: For small bugfixes that don't change behavior

For major releases, consider a phased rollout: publish the new version first, then give projects time (typically 2-4 sprints) to adapt before making it the default.

### Release Process

1. **Make your changes:**
   - Commit your changes with conventional commit messages (e.g., `feat(TASK-KEY):`, `fix(TASK-KEY):`)
   - Create a pull request for review
   - Merge to `main` branch

2. **Release-please creates a release PR:**
   - Release-please automatically opens a PR with version bumps and changelog updates
   - Review the PR to ensure the version bump and changelog are correct
   - The PR title indicates the type of release (e.g., "chore(TASK-KEY): Release-As: 1.2.0")

3. **Merge the release PR:**
   - Once merged, release-please automatically:
     - Creates a git tag with the new version
     - Creates a GitHub release with the changelog
     - Updates all configured files within the release-pelase-config.json
   - Packagist automatically detects the new tag and publishes the updated Composer package

4. **Verify the release:**
   - The GitHub Actions workflow runs tests against the new release
   - Check that Packagist has updated: https://packagist.org/packages/linchpin/coding-standards

### Conventional Commits

Example commit messages:
```
feat(TASK-KEY): add new sniff for array spacing
fix(TASK-KEY): correct false positive in escape output sniff
```

### Manual Release (if needed)

If you need to manually trigger a release:

1. Ensure `CHANGELOG.md` is up to date
2. Update the version in `composer.json`
3. Create and push a git tag:
   ```bash
   git tag v1.0.0  # Replace with your version number
   git push origin v1.0.0
   ```
4. Packagist will automatically detect the new tag

### Major Version Branches

When releasing a major version, create a maintenance branch for potential bugfix releases:
- `v1.0` for version 1.0.x
- `v2.0` for version 2.0.x
