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
    },
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
