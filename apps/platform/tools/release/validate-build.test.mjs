import {test} from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {validateBuild} from './validate-build.mjs';
import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';

function fixture() {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'umu-build-validator-test-'));
    fs.mkdirSync(path.join(dir, 'assets'));
    for (const file of ['app.js', 'app.css', 'shared.js', 'lazy.js', 'icon.svg']) {
        fs.writeFileSync(path.join(dir, 'assets', file), 'fixture-' + file);
    }
    const manifest = {
        'resources/js/app.jsx': {file: 'assets/app.js', isEntry: true, css: ['assets/app.css'], imports: ['shared'], dynamicImports: ['lazy'], assets: ['assets/icon.svg']},
        shared: {file: 'assets/shared.js'}, lazy: {file: 'assets/lazy.js', imports: ['shared']},
    };
    const save = () => fs.writeFileSync(path.join(dir, 'manifest.json'), JSON.stringify(manifest));
    save();
    return {dir, manifest, save};
}
const cases = [
    ['missing CSS', f => fs.unlinkSync(path.join(f.dir, 'assets/app.css'))],
    ['missing dynamic output', f => fs.unlinkSync(path.join(f.dir, 'assets/lazy.js'))],
    ['missing imported output', f => fs.unlinkSync(path.join(f.dir, 'assets/shared.js'))],
    ['missing asset', f => fs.unlinkSync(path.join(f.dir, 'assets/icon.svg'))],
    ['dangling static chunk', f => {f.manifest['resources/js/app.jsx'].imports = ['missing']; f.save();}],
    ['dangling dynamic chunk', f => {f.manifest['resources/js/app.jsx'].dynamicImports = ['missing']; f.save();}],
    ['parent traversal', f => {f.manifest.shared.file = '../outside.js'; f.save();}],
    ['absolute URL', f => {f.manifest.shared.file = 'https://example.test/a.js'; f.save();}],
    ['invalid list', f => {f.manifest.shared.css = 'assets/app.css'; f.save();}],
    ['empty output', f => fs.writeFileSync(path.join(f.dir, 'assets/app.js'), '')],
    ['missing app entry', f => {delete f.manifest['resources/js/app.jsx']; f.save();}],
    ['invalid JSON', f => fs.writeFileSync(path.join(f.dir, 'manifest.json'), '{')],
    ['symlinked output', f => {fs.unlinkSync(path.join(f.dir, 'assets/shared.js')); fs.symlinkSync('app.js', path.join(f.dir, 'assets/shared.js'));}],
];
test('accepts complete manifest and computes reproducible output identity', () => {
    const f = fixture();
    try {assert.deepEqual(validateBuild(f.dir), validateBuild(f.dir));}
    finally {fs.rmSync(f.dir, {recursive: true});}
});
for (const [name, mutate] of cases) {
    test('rejects ' + name, () => {
        const f = fixture();
        try {mutate(f); assert.throws(() => validateBuild(f.dir));}
        finally {fs.rmSync(f.dir, {recursive: true});}
    });
}

function identifiedFixture() {
    const f = fixture();
    fs.mkdirSync(path.join(f.dir, 'resources'));
    fs.writeFileSync(path.join(f.dir, 'resources', 'source.txt'), 'source-v1');
    for (const args of [['init', '--quiet'], ['add', '.'], ['-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.test', 'commit', '--quiet', '-m', 'fixture']]) {
        const result = spawnSync('git', args, {cwd: f.dir, encoding: 'utf8'});
        assert.equal(result.status, 0, 'Fixture Git setup failed');
    }
    const cli = fileURLToPath(new URL('./validate-build.mjs', import.meta.url));
    f.run = action => spawnSync(process.execPath, [cli, f.dir, action, '--local'], {cwd: f.dir, encoding: 'utf8'});
    assert.equal(f.run('--write-identity').status, 0, 'Fixture identity creation failed');
    return f;
}
for (const [name, mutate] of [
    ['unchanged identity', null],
    ['altered output bytes', f => fs.appendFileSync(path.join(f.dir, 'assets/app.js'), 'tamper')],
    ['changed source after build', f => fs.appendFileSync(path.join(f.dir, 'resources/source.txt'), 'changed')],
]) {
    test('identity check: ' + name, () => {
        const f = identifiedFixture();
        try {
            if (mutate) mutate(f);
            const result = f.run('--verify-identity');
            assert.equal(result.status, mutate ? 1 : 0, result.stderr);
        } finally {fs.rmSync(f.dir, {recursive: true});}
    });
}
