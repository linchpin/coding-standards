<?php
/**
 * Linchpin Coding Standards.
 *
 * @package Linchpin
 */

namespace Linchpin\Sniffs\Layout;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Sniff to check order of declarations (namespace, use, const, code).
 */
class OrderSniff implements Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [ T_OPEN_TAG ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the
     *                        stack passed in $tokens.
     *
     * @return void
     */
    public function process( File $phpcsFile, $stackPtr )
    {
        $tokens = $phpcsFile->getTokens();

        // Things we can look for.
        $look_for = [
        T_NAMESPACE    => 0,
        // Then.
        T_USE          => 1,
        // Then.
        T_CONST        => 2,
        // Then any of.
        T_REQUIRE      => 3,
        T_REQUIRE_ONCE => 3,
        T_INCLUDE      => 3,
        T_INCLUDE_ONCE => 3,
        ];

        // Which item are we looking for now?
        $current_score = 0;
        $current_token = [
        'content' => 'namespace',
        'code'    => T_NAMESPACE,
        'line'    => 0,
        ];

        // Start looking.
        $next_pos = 0;
        while ( true ) {
            $next_pos = $phpcsFile->findNext(array_keys($look_for), $next_pos + 1);
            if (empty($next_pos) ) {
                return;
            }

            $next_token = $tokens[ $next_pos ];

            // Ignore nested `use` eg. in lambda functions.
            if ($next_token['code'] === T_USE && $phpcsFile->findPrevious(T_CLOSURE, $next_pos, null, false, null, true) !== false ) {
                continue;
            }
            if ($next_token['code'] === T_USE && $phpcsFile->findPrevious(T_CLASS, $next_pos, null, false, null, true) !== false ) {
                continue;
            }

            // Must be current or higher.
            $next_type_score = $look_for[ $next_token['code'] ];
            if ($next_type_score < $current_score ) {
                // ERROR!
                $error  = '%s found on line %s, but %s was declared on line %s.';
                $error .= ' Statements should be ordered `namespace`, `use`, `const`, `require`, then code.';
                $data   = [ $next_token['content'], $next_token['line'], $current_token['content'], $current_token['line'] ];
                $phpcsFile->addError($error, $stackPtr, 'WrongOrder', $data);
                return;
            }

            // Adjust looking for.
            $current_score = $next_type_score;
            $current_token = $next_token;
        }
    }
}
