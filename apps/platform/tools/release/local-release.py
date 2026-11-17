#!/usr/bin/env python3
"""Local-only build/promote/smoke workflow. Never installs dependencies or starts containers."""
import argparse
import datetime
import fcntl
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
from urllib.parse import urlparse
from urllib.request import urlopen

app = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser()
parser.add_argument('--sso', choices=['enabled', 'disabled'], required=True)
parser.add_argument('--base-url', default='http://localhost:8000')
args = parser.parse_args()
url = urlparse(args.base_url)
if url.scheme != 'http' or url.hostname not in ('localhost', '127.0.0.1', '::1') or url.username or url.password or url.path not in ('', '/') or url.query or url.fragment:
    sys.exit('STOP: loopback HTTP only, no credentials/path/query/fragment.')
origin = args.base_url.rstrip('/')
validator = app/'tools/release/validate-build.mjs'
smoke = app/'tools/release-smoke'
root = Path.home()/'umu-ai-local-evidence'
root.mkdir(mode=0o700, exist_ok=True)
run = Path(tempfile.mkdtemp(prefix='frontend-release-', dir=root))
report = {'schema': 1, 'startedAt': datetime.datetime.now(datetime.timezone.utc).isoformat(),
          'status': 'STARTED', 'steps': [], 'localOnly': True,
          'ssoExpectation': args.sso, 'build': str(run/'new-build'), 'backup': str(run/'previous-build')}
installed = False
manifest_restore = app/'public/build/manifest.json'

def save():
    (run/'result.json').write_text(json.dumps(report, indent=2)+'\n')

def command(label, argv, cwd=app, env=None):
    print('\nSTEP: '+label, flush=True)
    result = subprocess.run([str(a) for a in argv], cwd=cwd, env=env)
    report['steps'].append({'step': label, 'exit': result.returncode})
    save()
    if result.returncode:
        raise RuntimeError(label+' failed; stop and review output.')

def fingerprint():
    script = "import {sourceIdentity} from './tools/release/validate-build.mjs'; console.log(JSON.stringify(sourceIdentity(process.cwd())))"
    return json.loads(subprocess.check_output(['node', '--input-type=module', '-e', script], cwd=app))

def replace_json(source, destination):
    temporary = destination.with_name(destination.name+'.release-tmp')
    if temporary.exists(): raise RuntimeError('Unexpected temporary manifest file exists.')
    shutil.copy2(source, temporary)
    os.replace(temporary, destination)

print('Private local evidence directory: '+str(run), flush=True)
save()
lock = None
try:
    os.chdir(app)
    lock = open(app/'storage/framework/frontend-release.lock', 'a')
    fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
    for required in [app/'node_modules/.bin/vite', smoke/'node_modules/.bin/playwright', app/'public/build/manifest.json']:
        if not required.exists(): raise RuntimeError('Missing prerequisite: '+str(required))
    if (app/'public/hot').exists() or (app/'public/hot').is_symlink():
        raise RuntimeError('public/hot exists; this workflow requires compiled assets.')
    php = "if (! app()->environment('local')) { throw new \\RuntimeException('Requires local environment'); } if (\\App\\Support\\GoogleSso::ready() !== "+('true' if args.sso == 'enabled' else 'false')+") { throw new \\RuntimeException('SSO readiness differs from declared expectation'); } echo 'Local readiness checked';"
    command('local container/readiness preflight', ['docker', 'compose', 'exec', '-T', 'laravel.test', 'php', 'artisan', 'tinker', '--execute='+php])
    with urlopen(origin+'/health', timeout=15) as response:
        if response.status != 200: raise RuntimeError('Local health is not 200.')
    before = fingerprint()
    command('validator negative tests', ['node', '--test', 'tools/release/validate-build.test.mjs'])
    command('typecheck', ['npm', 'run', 'typecheck'])
    command('frontend tests', ['npm', 'run', 'test'])
    new = run/'new-build'
    new.mkdir()
    command('build once in private staging directory', [app/'node_modules/.bin/vite', 'build', '--outDir', new])
    if fingerprint()['sourceSha256'] != before['sourceSha256']:
        raise RuntimeError('Source changed during build/tests; no promotion performed.')
    command('record local build identity', ['node', validator, new, '--write-identity', '--local'])
    command('verify candidate identity and source', ['node', validator, new, '--verify-identity'])
    build = app/'public/build'
    # Back up the exact existing manifest/build before any serving changes.
    shutil.copytree(build, run/'previous-build', symlinks=True)
    # Do not overwrite a supposedly immutable URL with different bytes.
    for source in new.rglob('*'):
        if not source.is_file() or source.name in ('manifest.json', 'release-identity.json'): continue
        target = build/source.relative_to(new)
        if target.exists() and (not target.is_file() or hashlib.sha256(target.read_bytes()).digest() != hashlib.sha256(source.read_bytes()).digest()):
            raise RuntimeError('Immutable asset filename collision; no manifest switch performed.')
    for source in new.rglob('*'):
        if not source.is_file() or source.name in ('manifest.json', 'release-identity.json'): continue
        target = build/source.relative_to(new)
        target.parent.mkdir(parents=True, exist_ok=True)
        if not target.exists(): shutil.copy2(source, target)
    # Start rollback coverage before either metadata file changes.
    installed = True
    replace_json(new/'release-identity.json', build/'release-identity.json')
    replace_json(new/'manifest.json', build/'manifest.json')
    command('verify installed identity', ['node', validator, build, '--verify-identity'])
    env = os.environ.copy()
    env.update(AUTH_BASE_URL=origin, AUTH_EXPECT_SSO=args.sso, AUTH_BUILD_IDENTITY=str(new/'release-identity.json'))
    command('browser smoke against installed candidate bytes', [smoke/'node_modules/.bin/playwright', 'test'], cwd=smoke, env=env)
    command('verify source/build unchanged after smoke', ['node', validator, build, '--verify-identity'])
    report.update(status='LOCAL_SMOKE_PASSED', identity=json.loads((new/'release-identity.json').read_text()))
except BaseException as error:
    report['status'] = 'FAILED'
    # Do not serialize command arguments, cookies, OAuth locations, or config values.
    report['failure'] = type(error).__name__
    print('\nSTOP: '+str(error), file=sys.stderr)
    if installed:
        try:
            replace_json(run/'previous-build/manifest.json', manifest_restore)
            old_identity = run/'previous-build/release-identity.json'
            active_identity = app/'public/build/release-identity.json'
            if old_identity.exists(): replace_json(old_identity, active_identity)
            elif active_identity.exists(): active_identity.unlink()
            report['rollback'] = 'previous manifest/identity restored; old assets retained'
            print('Previous frontend manifest restored. New unreferenced hashed assets retained.', file=sys.stderr)
        except BaseException:
            report['rollback'] = 'FAILED — restore previous-build manifest manually'
            print('ROLLBACK FAILED. Original build preserved at '+str(run/'previous-build'), file=sys.stderr)
    save()
    sys.exit(1)
finally:
    save()
    if lock: lock.close()

print('\nPASS: local build and browser smoke finished. Evidence: '+str(run/'result.json'))
print('This is NOT production acceptance, live Google login, or a commit/push.')
