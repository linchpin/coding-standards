<?php
/**
 * Linchpin Coding Standards.
 *
 * @package Linchpin
 */

namespace Linchpin\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Sniff to check `use` class isn't prefixed with `\`
 */
class NoLeadingSlashOnUseSniff implements Sniff
{

    /**
     * Register the tokens to listen to.
     *
     * @return array
     */
    public function register()
    {
        return [ T_USE ];
    }

    /**
     * Process the tokens.
     *
     * @param  File $phpcsFile The file being scanned.
     * @param  int  $stackPtr  The position of the current token in the stack.
     * @return void
     */
    public function process( File $phpcsFile, $stackPtr )
    {
        $tokens   = $phpcsFile->getTokens();
        $look_for = [ T_STRING, T_NS_SEPARATOR ];
        $next     = $phpcsFile->findNext($look_for, $stackPtr);
        if ($tokens[ $next ]['code'] === T_NS_SEPARATOR ) {
            $name = '';
            do {
                ++$next;
                $name .= $tokens[ $next ]['content'];
            } while ( in_array($tokens[ $next + 1 ]['code'], $look_for, true) );

            $error = '`use` statement for class %s should not prefix with a backslash';
            $phpcsFile->addError($error, $stackPtr, 'LeadingSlash', [ $name ]);
        }
    }
}
