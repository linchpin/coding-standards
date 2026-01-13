/**
 * Custom config for commitlint based on Linchpin needs/conventions.
 *
 * This config only slightly modifies the default config to allow for "improve" as a type
 * and adds support for ClickUp or Github issue numbers in the commit message.
 *
 * Format Example
 *
 * chore(MANTLE-123|NO-TASK|#123): Did a thing
 *
 * or if you have no issue number you can utilize  NO-TASK
 */
module.exports = {
	extends: ['@commitlint/config-conventional'],
	rules: {
		'type-enum': [2, 'always', ['improve', 'build','chore','ci','docs','feat','fix','perf','refactor','revert','style','test']],
		'subject-case': [1, 'always', ['sentence-case']],
	},
	"parserPreset": {
		"parserOpts": {
			headerPattern: /^(improve|build|ci|feat|fix|docs|style|revert|perf|refactor|test|chore)\(((?:[A-Z]+-\d+)|(?:NO-TASK)|(?:\#\d+))\):\s?([\w\d\s,\-]*)/,
			"headerCorrespondence": ["type", "scope", "subject"]
		}
	}
};