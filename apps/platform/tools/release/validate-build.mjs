import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';

const hash = value => crypto.createHash('sha256').update(value).digest('hex');
const object = value => value !== null && typeof value === 'object' && !Array.isArray(value);
const own = (value, key) => Object.prototype.hasOwnProperty.call(value, key);
function fail(message) { throw new Error(message); }

export function validateBuild(directory) {
    const root = fs.realpathSync(directory);
    const inventory = new Map();
    function check(relative) {
        if (typeof relative !== 'string' || !relative || /[\\?#\x00-\x1f]/.test(relative) ||
            relative.startsWith('/') || relative.includes(':') || relative.split('/').some(p => !p || p === '.' || p === '..')) {
            fail('Unsafe or non-relative manifest output path');
        }
        const file = path.resolve(root, relative);
        if (!file.startsWith(root + path.sep)) fail('Manifest output escapes build root');
        if (fs.realpathSync(file) !== file) fail('Symlinked output is not accepted');
        const stat = fs.statSync(file);
        if (!stat.isFile() || stat.size === 0) fail('Missing, empty, or non-file output: ' + relative);
        inventory.set(relative, hash(fs.readFileSync(file)));
    }
    check('manifest.json');
    const manifestBytes = fs.readFileSync(path.join(root, 'manifest.json'));
    const manifest = JSON.parse(manifestBytes);
    if (!object(manifest) || Object.keys(manifest).length === 0) fail('Manifest must be a nonempty object');
    const entry = manifest['resources/js/app.jsx'];
    if (!object(entry) || entry.isEntry !== true || typeof entry.file !== 'string' || !entry.file.endsWith('.js')) {
        fail('Missing application JavaScript entry');
    }
    let hasCss = false;
    for (const [key, chunk] of Object.entries(manifest)) {
        if (!object(chunk)) fail('Invalid manifest chunk: ' + key);
        check(chunk.file);
        if (chunk.file.endsWith('.css')) hasCss = true;
        for (const field of ['css', 'assets', 'imports', 'dynamicImports']) {
            if (!own(chunk, field)) continue;
            if (!Array.isArray(chunk[field]) || chunk[field].some(value => typeof value !== 'string' || !value)) {
                fail('Invalid manifest list: ' + field);
            }
            for (const value of chunk[field]) {
                if (field === 'imports' || field === 'dynamicImports') {
                    if (!own(manifest, value)) fail('Dangling chunk reference: ' + value);
                } else {
                    check(value);
                    if (field === 'css') hasCss = true;
                }
            }
        }
    }
    if (!hasCss) fail('Application build has no CSS output');
    const outputs = Object.fromEntries([...inventory].sort(([a], [b]) => a.localeCompare(b)));
    return {schema: 1, manifestSha256: hash(manifestBytes), entry: entry.file, files: outputs,
        artifactSha256: hash(JSON.stringify(outputs))};
}

export function sourceIdentity(app) {
    const roots = ['app', 'bootstrap', 'config', 'resources', 'routes'];
    const inventory = [];
    function walk(relative) {
        const full = path.join(app, relative);
        const stat = fs.lstatSync(full);
        if (stat.isSymbolicLink()) fail('Source symlinks require explicit review');
        if (stat.isDirectory()) {
            for (const name of fs.readdirSync(full).sort()) {
                // Generated bootstrap cache is not source and can vary per runtime.
                if (relative === 'bootstrap' && name === 'cache') continue;
                walk(relative + '/' + name);
            }
        } else if (stat.isFile()) inventory.push([relative, hash(fs.readFileSync(full))]);
    }
    for (const name of roots) if (fs.existsSync(path.join(app, name))) walk(name);
    for (const name of ['package.json', 'package-lock.json', 'composer.json', 'composer.lock', 'vite.config.js']) {
        if (fs.existsSync(path.join(app, name))) walk(name);
    }
    const git = (...args) => execFileSync('git', args, {cwd: app, encoding: 'utf8'}).trim();
    return {baseCommit: git('rev-parse', 'HEAD'), worktreeClean: git('status', '--porcelain', '--untracked-files=normal') === '',
        sourceSha256: hash(JSON.stringify(inventory.sort(([a], [b]) => a.localeCompare(b))))};
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    try {
        const [directory, action = '--check', mode] = process.argv.slice(2);
        if (!directory || !['--check', '--write-identity', '--verify-identity'].includes(action)) {
            fail('Usage: node validate-build.mjs BUILD_DIR [--check|--write-identity|--verify-identity] [--local]');
        }
        const build = validateBuild(directory);
        const identityFile = path.join(directory, 'release-identity.json');
        if (action === '--write-identity') {
            const source = sourceIdentity(process.cwd());
            if (!source.worktreeClean && mode !== '--local') fail('Production identity requires a clean Git worktree');
            if (fs.existsSync(identityFile)) fail('Identity already exists; do not overwrite it');
            const identity = {...build, ...source, buildId: crypto.randomUUID(), builtAt: new Date().toISOString(),
                eligibility: mode === '--local' ? 'local-acceptance-only' : 'clean-source-candidate'};
            fs.writeFileSync(identityFile, JSON.stringify(identity, null, 2) + '\n', {flag: 'wx'});
        } else if (action === '--verify-identity') {
            const identity = JSON.parse(fs.readFileSync(identityFile, 'utf8'));
            if (JSON.stringify(identity.files) !== JSON.stringify(build.files) || identity.manifestSha256 !== build.manifestSha256 ||
                identity.artifactSha256 !== build.artifactSha256 || identity.entry !== build.entry) fail('Build content does not match recorded identity');
            if (identity.sourceSha256 !== sourceIdentity(process.cwd()).sourceSha256) fail('Source changed after build; rebuild/retest');
        }
        console.log('PASS: build references/integrity verified; entry=' + build.entry);
    } catch (error) {
        console.error('FAIL: ' + error.message);
        process.exitCode = 1;
    }
}
