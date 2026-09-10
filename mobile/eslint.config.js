const expoConfig = require('eslint-config-expo/flat');
const prettier = require('eslint-config-prettier');

/**
 * The rules below are the ones this app's failure modes call for rather than a
 * general style opinion.
 *
 * `no-floating-promises` earns its place first. Every request this app makes
 * is an `async` call over a mobile connection, and a dropped promise is a
 * request whose failure nobody ever sees — the screen simply stays on its
 * loading state, which is the exact outcome `~/.claude/skills/ui-performance`
 * calls the worst one.
 */
module.exports = [
  ...expoConfig,
  prettier,
  {
    ignores: ['node_modules/**', '.expo/**', 'dist/**', 'android/**', 'src/api/schema.d.ts'],
  },
  {
    files: ['**/*.ts', '**/*.tsx'],
    languageOptions: {
      parserOptions: {
        projectService: true,
      },
    },
    rules: {
      '@typescript-eslint/no-floating-promises': 'error',
      '@typescript-eslint/no-misused-promises': [
        'error',
        { checksVoidReturn: { attributes: false } },
      ],
      // `any` erases the contract in docs/api/openapi.yaml, which is the only
      // thing keeping a shipped APK and a live server in step.
      '@typescript-eslint/no-explicit-any': ['error', { ignoreRestArgs: false }],
      '@typescript-eslint/ban-ts-comment': [
        'error',
        { 'ts-expect-error': 'allow-with-description', 'ts-ignore': true },
      ],
      'no-console': ['warn', { allow: ['warn', 'error'] }],
      eqeqeq: ['error', 'always'],

      /*
       * One overlay design, enforced rather than agreed.
       *
       * Rio, 2026-09-10, on seeing an Android system dialog where a device
       * sign-out should have been: *"we need a universal design for our system
       * so that we don't use these default generic design and make sure all
       * the other agents use it when it's needed it should be enforced."*
       *
       * `Alert` is banned outright. A platform dialog is another product's
       * design — grey, teal, capitalised on Android and not on iOS — and it
       * cannot show a destructive action in this app's red or honour a single
       * token in `theme.ts`.
       *
       * `Modal` is banned because six screens each built their own sheet. The
       * tokens were shared, so they still looked alike; the markup was not, so
       * the Android back button, the safe-area padding and the scrim's press
       * target were solved five times and correctly a different number of
       * times. `ui/overlay.tsx` is the one place either may be imported.
       *
       * This is a lint error and not a convention, because a convention is
       * exactly what produced the six.
       */
      'no-restricted-imports': [
        'error',
        {
          paths: [
            {
              name: 'react-native',
              importNames: ['Alert'],
              message:
                "Use `useConfirm()` from `ui/overlay` instead of Alert. A platform dialog ignores every token in theme.ts and cannot draw a destructive action in the app's red.",
            },
            {
              name: 'react-native',
              importNames: ['Modal'],
              message:
                'Use `Sheet` from `ui/overlay` instead of Modal. Six hand-rolled sheets is what this rule exists to stop becoming seven.',
            },
          ],
        },
      ],
    },
  },
  {
    // The one file allowed to hold the platform primitives.
    files: ['src/ui/overlay.tsx'],
    rules: { 'no-restricted-imports': 'off' },
  },
  {
    /*
     * The six that predate the rule, allowed until each is converted.
     *
     * **A ratchet, not an exemption.** Nothing new can be added to this list
     * without a reviewer seeing it, and the list only shrinks. Converting them
     * is deliberate work in its own commit, per CLAUDE.md — restructuring
     * inside somebody else's active feature is the failure mode, not the fix —
     * and three of these belong to other sessions right now.
     *
     * Delete a line when its file moves to `Sheet`. When the array is empty,
     * delete the block.
     */
    files: [
      'src/features/referrals/EditCodeSheet.tsx',
      'src/features/wallet/WithdrawSheet.tsx',
      'src/features/watchlist/WatchlistScreen.tsx',
      'src/screens/ProfileMenuScreen.tsx',
      'src/ui/CountryPicker.tsx',
      'src/ui/player/PlayerMenu.tsx',
    ],
    rules: { 'no-restricted-imports': 'off' },
  },
  {
    files: ['**/*.test.ts', '**/*.test.tsx', 'jest.setup.ts'],
    rules: {
      // Test doubles stand in for native modules whose real shapes are wider
      // than anything a test needs.
      '@typescript-eslint/no-explicit-any': 'off',
    },
  },
];
