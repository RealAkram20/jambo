// video-server.mjs — byte-range static server for the development video fixtures.
//
//   node tools/dev-catalogue/video-server.mjs
//   node tools/dev-catalogue/video-server.mjs --port=8097
//
// WHY THIS EXISTS, because a second server looks like over-engineering until
// you hit both of the reasons.
//
// 1. `php artisan serve` DOES NOT IMPLEMENT RANGE REQUESTS. Asked for
//    `Range: bytes=1000-2000` of a 4.3 MB fixture it answers `200` with all
//    4,372,373 bytes, not `206` with 1,001. Measured, not assumed. A player
//    seeking to the middle of a film would re-download the film, and the seek
//    bar this slice builds would look broken for a reason that is nothing to
//    do with the app.
//
// 2. `php artisan serve` is SINGLE-THREADED. Video bytes and API calls share
//    one process, so a stream in flight blocks the heartbeat behind it. The
//    posters already taught this project that lesson
//    (`tools/dev-catalogue/README.md`); a two-hour film would be the same
//    failure with a longer fuse. Serving video from its own process keeps
//    port 8095 free for the calls that must not wait.
//
// Apache is not the answer either: its vhost does not expose this path, and
// pointing it here would mean editing the web server that also serves the
// Precious project.
//
// Development only. It serves exactly one directory, refuses everything else,
// and is never deployed anywhere.
import { createReadStream, statSync, existsSync } from 'node:fs';
import { createServer } from 'node:http';
import { dirname, extname, join, normalize, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(HERE, '../../public/storage/dev-video');

const args = Object.fromEntries(
  process.argv.slice(2).map((a) => {
    const [k, v] = a.replace(/^--/, '').split('=');
    return [k, v ?? true];
  }),
);

const PORT = Number(args.port ?? 8097);

const TYPES = {
  '.mp4': 'video/mp4',
  '.m4v': 'video/x-m4v',
  '.webm': 'video/webm',
  '.ogv': 'video/ogg',
};

if (!existsSync(ROOT)) {
  console.error(
    `No fixture directory at ${ROOT}.\n` +
      'Run: php tools/dev-catalogue/9-playable-video.php --download',
  );
  process.exit(1);
}

/**
 * Resolve a request path inside ROOT, or null.
 *
 * The containment check is the whole security model of this file. It is a
 * development tool bound to 0.0.0.0 so an emulator can reach it, which means
 * anything on the local network can reach it too, and `../../../.env` is the
 * first thing anyone would try.
 */
function safePath(urlPath) {
  const decoded = decodeURIComponent(urlPath.split('?')[0]);
  const candidate = resolve(join(ROOT, normalize(decoded)));

  if (candidate !== ROOT && !candidate.startsWith(ROOT + sep)) {
    return null;
  }

  return candidate;
}

const server = createServer((req, res) => {
  const file = safePath(req.url ?? '/');

  if (file === null || !existsSync(file)) {
    res.writeHead(404, { 'content-type': 'text/plain' });
    res.end('Not found\n');
    console.log(`  404 ${req.method} ${req.url}`);
    return;
  }

  const stat = statSync(file);

  if (stat.isDirectory()) {
    res.writeHead(403, { 'content-type': 'text/plain' });
    res.end('No listing\n');
    return;
  }

  const type = TYPES[extname(file).toLowerCase()] ?? 'application/octet-stream';
  const range = req.headers.range;

  // A player asks for ranges constantly. Logging them is not noise: it is the
  // only direct evidence that the seek bar reached the network at all, and it
  // is how "the video did not move" gets separated from "the app never asked".
  const label = `${req.method} ${req.url}${range ? ` [${range}]` : ''}`;

  if (range !== undefined) {
    const match = /^bytes=(\d*)-(\d*)$/.exec(range.trim());

    if (match === null) {
      res.writeHead(416, { 'content-range': `bytes */${stat.size}` });
      res.end();
      console.log(`  416 ${label}`);
      return;
    }

    // An open start ("bytes=-500") means the LAST 500 bytes, which is how a
    // player reads an MP4 whose moov atom sits at the end of the file.
    const hasStart = match[1] !== '';
    const start = hasStart ? Number(match[1]) : Math.max(0, stat.size - Number(match[2] || 0));
    const end = hasStart
      ? match[2] === ''
        ? stat.size - 1
        : Math.min(Number(match[2]), stat.size - 1)
      : stat.size - 1;

    if (start > end || start >= stat.size) {
      res.writeHead(416, { 'content-range': `bytes */${stat.size}` });
      res.end();
      console.log(`  416 ${label}`);
      return;
    }

    res.writeHead(206, {
      'content-type': type,
      'content-length': end - start + 1,
      'content-range': `bytes ${start}-${end}/${stat.size}`,
      'accept-ranges': 'bytes',
      'cache-control': 'no-store',
    });

    console.log(`  206 ${label} -> ${start}-${end}/${stat.size}`);

    if (req.method === 'HEAD') {
      res.end();
      return;
    }

    createReadStream(file, { start, end }).pipe(res);
    return;
  }

  res.writeHead(200, {
    'content-type': type,
    'content-length': stat.size,
    'accept-ranges': 'bytes',
    'cache-control': 'no-store',
  });

  console.log(`  200 ${label} -> ${stat.size}`);

  if (req.method === 'HEAD') {
    res.end();
    return;
  }

  createReadStream(file).pipe(res);
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Jambo dev video on http://0.0.0.0:${PORT}  (emulator: http://10.0.2.2:${PORT})`);
  console.log(`Serving ${ROOT}`);
  console.log('Ranges supported. Ctrl-C to stop.\n');
});
