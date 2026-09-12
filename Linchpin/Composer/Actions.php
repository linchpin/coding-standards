<?php
/**
 * Composer script handlers for scoping PHPCS to what a change actually touched.
 *
 * `linchpin/actions`' `php-checks.yml` runs `composer check-branch-cs`, so every repository
 * calling that shared workflow has to provide it. Until now each one carried its own copy of
 * this class under `dev/Composer/Actions.php` — mantle and block-alchemy both did, and the
 * two had already drifted apart. One copy here, in a package all of them already require as
 * a dev dependency, is one place to fix a bug in.
 *
 * **Scoped to changed lines, not changed files.** The per-repository copies sniffed each
 * changed file whole, which is fine on a clean tree and wrong on any other: editing one line
 * of a file carrying inherited findings reports all of them against the pull request that
 * touched it. The author did not cause them and cannot reasonably fix them in passing, so the
 * check fails for reasons nobody can act on — and a gate like that gets switched off rather
 * than fixed. On the repository this was written for, one such change reported 397 findings,
 * of which 5 were on a line it had touched.
 *
 * The trade is that a finding on an untouched line goes unreported until somebody edits that
 * line. That is the intent: debt is paid down by the changes passing through it.
 *
 * @package Linchpin
 */

namespace Linchpin\Composer;

use Composer\Script\Event;

/**
 * Runs PHPCS over the lines a change introduced.
 */
class Actions
{
    /**
     * Check the lines this branch changed against a git reference.
     *
     * @param  Event $event Composer event; the first argument is the base ref.
     * @return void
     */
    public static function check_branch_cs( Event $event )
    {
        $arguments = $event->getArguments();
        $compare   = empty($arguments) ? 'main' : (string) $arguments[0];

        exit(self::run([ escapeshellarg($compare) ]));
    }

    /**
     * Check the lines a commit is about to introduce.
     *
     * For a pre-commit hook. Diffs the working tree against HEAD rather than the index,
     * because lint-staged stashes unstaged work before running tasks and re-stages their
     * modifications only after every task finishes — so reading `--cached` reports the
     * errors an autofixer task fixed a moment earlier.
     *
     * @param  Event $event Composer event. Takes no arguments; the diff names the set.
     * @return void
     */
    public static function check_staged_cs( Event $event )
    {
        unset($event);

        exit(self::run([ 'HEAD' ]));
    }

    /**
     * Run PHPCS and report only what falls on the changed lines.
     *
     * Public so it can be exercised without constructing a Composer event.
     *
     * @param  array  $diff_arguments Arguments identifying the diff, already shell-escaped.
     * @param  string $standard       PHPCS standard to run against.
     * @return int Zero when nothing was reported on a changed line.
     */
    public static function run( array $diff_arguments, $standard = 'phpcs.xml.dist' )
    {
        $diff_spec = implode(' ', $diff_arguments);
        $files     = self::changed_files($diff_spec);

        if (empty($files) ) {
            echo 'No PHP files changed.' . PHP_EOL;

            return 0;
        }

        $changed_lines = self::changed_lines($diff_spec);
        $report        = self::phpcs_report($files, $standard);

        if (null === $report ) {
            echo 'PHPCS produced no parseable report.' . PHP_EOL;

            return 1;
        }

        $findings = self::findings_on_changed_lines($report, $changed_lines);

        if (empty($findings) ) {
            printf('PHPCS: no findings on the %d changed line(s).%s', self::count_lines($changed_lines), PHP_EOL);

            return 0;
        }

        self::print_findings($findings);

        // The checkstyle report is what `cs2pr` turns into inline annotations, and it is
        // written here rather than asked of PHPCS because PHPCS's own would carry every
        // inherited finding in each touched file — which is what the filtering removes.
        if (false !== getenv('CI') ) {
            self::write_checkstyle($findings, './phpcs-report.xml');
        }

        return 1;
    }

    /**
     * The PHP files in the diff that still exist.
     *
     * @param  string $diff_spec Escaped diff arguments.
     * @return array
     */
    protected static function changed_files( $diff_spec )
    {
        $files = [];

        // `--diff-filter=d` drops deletions: a removed file has nothing left to sniff.
        exec('git diff --name-only --diff-filter=d ' . $diff_spec, $files);

        $php_files = [];

        foreach ( $files as $file ) {
            if (substr($file, -4) === '.php' && file_exists($file) ) {
                $php_files[] = $file;
            }
        }

        return $php_files;
    }

    /**
     * The line numbers the diff adds or modifies, keyed by file.
     *
     * Read from a zero-context diff, so each hunk header names exactly the new lines rather
     * than those plus three either side.
     *
     * @param  string $diff_spec Escaped diff arguments.
     * @return array
     */
    protected static function changed_lines( $diff_spec )
    {
        $output = [];

        exec('git diff -U0 --diff-filter=d ' . $diff_spec, $output);

        $lines   = [];
        $current = '';

        foreach ( $output as $line ) {
            if (strpos($line, '+++ b/') === 0 ) {
                $current           = substr($line, 6);
                $lines[ $current ] = [];

                continue;
            }

            if ('' === $current || strpos($line, '@@') !== 0 ) {
                continue;
            }

            // @@ -old,count +new,count @@ — only the "+" side matters.
            if (! preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $matches) ) {
                continue;
            }

            $start = (int) $matches[1];
            $count = isset($matches[2]) ? (int) $matches[2] : 1;

            for ( $i = 0; $i < $count; $i++ ) {
                $lines[ $current ][ $start + $i ] = true;
            }
        }

        return $lines;
    }

    /**
     * PHPCS's JSON report for the given files.
     *
     * @param  array  $files    Files to sniff.
     * @param  string $standard PHPCS standard.
     * @return array|null Decoded report, or null when it could not be read.
     */
    protected static function phpcs_report( array $files, $standard )
    {
        $binary = file_exists('./vendor/bin/phpcs') ? './vendor/bin/phpcs' : 'phpcs';

        $command = sprintf(
            '%s --standard=%s --report=json %s 2>/dev/null',
            escapeshellarg($binary),
            escapeshellarg($standard),
            implode(' ', array_map('escapeshellarg', $files))
        );

        $output = shell_exec($command);

        if (! is_string($output) || '' === trim($output) ) {
            return null;
        }

        $decoded = json_decode($output, true);

        return is_array($decoded) && isset($decoded['files']) ? $decoded : null;
    }

    /**
     * Keep only the messages sitting on a line the diff touched.
     *
     * PHPCS reports absolute paths; the diff speaks in repository-relative ones.
     *
     * @param  array $report        Decoded PHPCS report.
     * @param  array $changed_lines Changed lines keyed by file.
     * @return array
     */
    protected static function findings_on_changed_lines( array $report, array $changed_lines )
    {
        $root     = rtrim((string) getcwd(), '/') . '/';
        $findings = [];

        foreach ( $report['files'] as $path => $data ) {
            $path     = (string) $path;
            $relative = strpos($path, $root) === 0 ? substr($path, strlen($root)) : $path;

            if (! isset($changed_lines[ $relative ]) ) {
                continue;
            }

            $messages = isset($data['messages']) ? $data['messages'] : [];

            foreach ( $messages as $message ) {
                $line = isset($message['line']) ? (int) $message['line'] : 0;

                if (isset($changed_lines[ $relative ][ $line ]) ) {
                    $findings[ $relative ][] = $message;
                }
            }
        }

        return $findings;
    }

    /**
     * Print the findings in something close to PHPCS's own full report.
     *
     * @param  array $findings Findings keyed by file.
     * @return void
     */
    protected static function print_findings( array $findings )
    {
        $total = 0;

        foreach ( $findings as $file => $messages ) {
            echo PHP_EOL . 'FILE: ' . $file . PHP_EOL;
            echo str_repeat('-', 80) . PHP_EOL;

            foreach ( $messages as $message ) {
                ++$total;

                printf(
                    '%4d | %-7s | %s (%s)%s',
                    isset($message['line']) ? (int) $message['line'] : 0,
                    isset($message['type']) ? (string) $message['type'] : 'ERROR',
                    isset($message['message']) ? (string) $message['message'] : '',
                    isset($message['source']) ? (string) $message['source'] : '',
                    PHP_EOL
                );
            }
        }

        echo PHP_EOL . sprintf('PHPCS: %d finding(s) on changed lines.', $total) . PHP_EOL;
    }

    /**
     * Write a checkstyle report holding only the filtered findings.
     *
     * @param  array  $findings Findings keyed by file.
     * @param  string $path     Where to write.
     * @return void
     */
    protected static function write_checkstyle( array $findings, $path )
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><checkstyle/>');

        foreach ( $findings as $file => $messages ) {
            $file_node = $xml->addChild('file');
            $file_node->addAttribute('name', $file);

            foreach ( $messages as $message ) {
                $type     = isset($message['type']) ? $message['type'] : '';
                $error    = $file_node->addChild('error');
                $error->addAttribute('line', (string) ( isset($message['line']) ? $message['line'] : 0 ));
                $error->addAttribute('column', (string) ( isset($message['column']) ? $message['column'] : 1 ));
                $error->addAttribute('severity', 'WARNING' === $type ? 'warning' : 'error');
                $error->addAttribute('message', (string) ( isset($message['message']) ? $message['message'] : '' ));
                $error->addAttribute('source', (string) ( isset($message['source']) ? $message['source'] : '' ));
            }
        }

        $xml->asXML($path);
    }

    /**
     * How many lines the diff touched, across every file.
     *
     * @param  array $changed_lines Changed lines keyed by file.
     * @return int
     */
    protected static function count_lines( array $changed_lines )
    {
        return array_sum(array_map('count', $changed_lines));
    }
}
