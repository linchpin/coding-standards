<?php
/**
 * Linchpin Coding Standards.
 *
 * @package Linchpin
 */

namespace Linchpin\Sniffs\PHP;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Sniff to check for isset() usage.
 */
class IssetSniff implements Sniff
{

    /**
     * Register the tokens to listen to.
     *
     * @return array
     */
    public function register()
    {
        return [ T_ISSET ];
    }

    /**
     * Process the tokens.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack.
     *
     * @throws RuntimeException If the stack pointer is invalid.
     *
     * @return void
     */
    public function process( File $phpcsFile, $stackPtr )
    {
        $tokens = $phpcsFile->getTokens();

        $open_parenthesis_token = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
        if ($open_parenthesis_token === false ) {
            throw new RuntimeException('$stackPtr was not a valid T_ISSET');
        }

        $comma_token = $phpcsFile->findNext(T_COMMA, $open_parenthesis_token + 1);
        if ($comma_token !== false && $comma_token < $tokens[ $open_parenthesis_token ]['parenthesis_closer'] ) {
            $phpcsFile->addWarning('Only one argument should be used per ISSET call', $stackPtr, 'MultipleArguments');
        }
    }
}
