// check-generated.mjs — fails when a generated file no longer matches its source.
//
//   node scripts/check-generated.mjs api      src/api/schema.d.ts vs docs/api/openapi.yaml
//   node scripts/check-generated.mjs tokens   src/ui/tokens.json  vs design/tokens.json
//
// This is the app-side counterpart of tests/Feature/Api/V1/OpenApiSpecTest.php,
// and it exists for the same reason that test does: a shipped APK and a live
// server cannot be reconciled after the fact. The server test fails when a
// route drifts from the spec; this fails when the app's types drift from it.
//
// The tokens check is narrower on purpose. Re-running the real export needs a
// browser and the live site, which is not something to put in front of every
// commit — so what is checked here is that the copy the app ships still matches
// the record the export wrote. Re-exporting is a deliberate act
// (`npm run tokens:export`), and that is the point: a design token changes when
// the site changes, not when somebody adjusts a colour by eye.
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const APP = resolve(HERE, '..');
const REPO = resolve(APP, '..');

const which = process.argv[2];

function fail(message) {
  console.error(`\n${message}\n`);
  process.exit(1);
}

if (which === 'api') {
  const committed = readFileSync(resolve(APP, 'src/api/schema.d.ts'), 'utf8');

  /*
   * The generator is run through Node against its own entry point rather than
   * through `npx`. On Windows, spawning `npx.cmd` without a shell fails with
   * EINVAL — Node stopped executing `.cmd` files directly in 20.x — and
   * enabling the shell to work around that would put a repository path
   * through cmd's quoting rules for no benefit.
   */
  const cli = resolve(APP, 'node_modules/openapi-typescript/bin/cli.js');

  const regenerated = execFileSync(
    process.execPath,
    [cli, resolve(REPO, 'docs/api/openapi.yaml')],
    { cwd: APP, encoding: 'utf8', maxBuffer: 32 * 1024 * 1024 },
  );

  // The generator stamps nothing time-dependent, so a byte comparison is safe
  // once line endings are normalised — Windows checkouts would otherwise fail
  // this on nothing but CRLF.
  const normalise = (s) => s.replace(/\r\n/g, '\n').trimEnd();

  if (normalise(committed) !== normalise(regenerated)) {
    fail(
      'src/api/schema.d.ts is out of date with docs/api/openapi.yaml.\n' +
        'Run: npm run api:types  — and read the diff before committing it.\n' +
        'A change here is a change to the contract the app is built against.',
    );
  }

  console.log('api: src/api/schema.d.ts matches docs/api/openapi.yaml');
  process.exit(0);
}

if (which === 'tokens') {
  const record = JSON.parse(readFileSync(resolve(REPO, 'design/tokens.json'), 'utf8'));
  const committed = JSON.parse(readFileSync(resolve(APP, 'src/ui/tokens.json'), 'utf8'));

  const tree = {};
  for (const [path, entry] of Object.entries(record.tokens)) {
    const parts = path.split('.');
    let node = tree;
    for (const part of parts.slice(0, -1)) node = node[part] ??= {};
    node[parts.at(-1)] = entry.value;
  }

  if (JSON.stringify(committed) !== JSON.stringify(tree)) {
    fail(
      'src/ui/tokens.json does not match design/tokens.json.\n' +
        'Either it was hand-edited — a design value belongs in the site, then re-export —\n' +
        'or design/tokens.json moved on. Run: npm run tokens:export',
    );
  }

  console.log(
    `tokens: src/ui/tokens.json matches design/tokens.json (${Object.keys(record.tokens).length} tokens)`,
  );
  process.exit(0);
}

fail('Usage: node scripts/check-generated.mjs <api|tokens>');
