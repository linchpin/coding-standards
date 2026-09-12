<?php
/**
 * Linchpin Coding Standards.
 *
 * @package Linchpin
 */

namespace Linchpin\Sniffs\Performance;

use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractArrayAssignmentRestrictionsSniff;

/**
 * Flag slow orderby usage in WP queries.
 */
class SlowOrderBySniff extends AbstractArrayAssignmentRestrictionsSniff
{
    /**
     * Groups of variables to restrict.
     *
     * The message uses %2$s rather than %s on purpose. The parent emits with
     * `array( $key, $value )` as the replacements, so %s resolves to the array key
     * — always the literal "orderby" — and %2$s is the assigned value, which is the
     * part worth naming.
     *
     * @return array
     */
    public function getGroups()
    {
        return [
        'slow_order' => [
        'type'    => 'warning',
        'message' => 'Ordering query results by %2$s is not performant.',
        'keys'    => [
        'orderby',
        ],
        ],
        ];
    }

    /**
     * Callback to process each confirmed key, to check value.
     *
     * Returning true lets the parent emit the group message above. This sniff used
     * to call `$this->addMessage()` here and return false to suppress the built-in
     * message — the shape WPCS 2 offered on WordPressCS\WordPress\Sniff. WPCS 3
     * removed that method, so the call resolved to nothing on the class or its
     * parent and the sniff fatalled the moment it found something to report:
     *
     *   Uncaught Error: Call to undefined method
     *   Linchpin\Sniffs\Performance\SlowOrderBySniff::addMessage()
     *
     * Because it only ever ran on a query that actually ordered by one of these
     * values, it looked healthy on any codebase that never hit the pattern, and
     * consuming projects worked around it by excluding the sniff outright.
     *
     * Deferring to the parent rather than re-adding a shim also drops the
     * `$stackPtr` property and the `process_token()` override that existed only to
     * carry a token pointer to the manual call. The parent already reports against
     * the key's own token, which is the more accurate position.
     *
     * The quotes have to come off first. The parent builds the value from the raw
     * tokens, so a literal arrives here as `'rand'` — quotes included — and the
     * comparison against `rand` could never match. Combined with the fatal above,
     * the sniff had two independent reasons never to work: it matched nothing, and
     * anything it did match would have crashed.
     *
     * @param  string $key   Array index / key.
     * @param  mixed  $val   Assigned value.
     * @param  int    $line  Token line.
     * @param  array  $group Group definition.
     * @return mixed         FALSE if no match, TRUE if matches, STRING if matches
     *                       with custom error message passed to ->process().
     */
    public function callback( $key, $val, $line, $group )
    {
        switch ( TextStrings::stripQuotes( (string) $val ) ) {
        case 'rand':
        case 'meta_value':
        case 'meta_value_num':
            return true;

        default:
            // No match.
            return false;
        }
    }
}
