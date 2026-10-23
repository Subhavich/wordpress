"""
Imports the REAL blog (the import pack made by extract-backup.py) into a LIVE WordPress over its REST API.
Runs on YOUR computer. The password is typed here and never stored. Python 3 standard library only.

  python -X utf8 sites/two/import-live.py --site https://division2.immigration.go.th --user <wp username> --pack <pack folder>

  Password: create an Application Password in WordPress (Users > Profile > Application Passwords) and type it when asked
            (or set the WP_APP_PASSWORD environment variable). Revoke it when you are done.
Options:
  --status draft|publish   default draft (review, then publish from Posts > All Posts)
  --dry-run                only print what would happen
  --limit N                import only the first N articles (try 3 first)

What it does: creates the categories (slug cat-<id>) and the generated tags, uploads the images and PDF/DOCX/XLSX files to the Media Library, creates
the 76 news/ITA articles as POSTS (real title, date, category, tags, body with the links rewritten, first image as featured image) and the 13 static
articles (about, contact, commanders, laws...) as PAGES, then re-links articles that point to each other.
Safe to re-run: posts/pages are found by their slug (news-<nid> / page-<nid>) and uploaded files are remembered in <pack>/import-manifest-<site>.json.
"""
import argparse, base64, getpass, json, mimetypes, os, re, sys, time, urllib.error, urllib.parse, urllib.request

ap = argparse.ArgumentParser()
ap.add_argument('--site', required=True); ap.add_argument('--user', required=True); ap.add_argument('--pack', required=True)
ap.add_argument('--status', default='draft', choices=['draft', 'publish', 'pending', 'private']); ap.add_argument('--dry-run', action='store_true'); ap.add_argument('--limit', type=int, default=0)
a = ap.parse_args()
site = a.site.rstrip('/')
pw = os.environ.get('WP_APP_PASSWORD') or getpass.getpass(f'Application password for {a.user} on {site}: ')
auth = 'Basic ' + base64.b64encode(f'{a.user}:{pw}'.encode()).decode()
mpath = os.path.join(a.pack, 'import-manifest-' + re.sub(r'\W+', '_', urllib.parse.urlparse(site).netloc + urllib.parse.urlparse(site).path.rstrip('/')) + '.json')   # one manifest per site
manifest = json.load(open(mpath, encoding='utf-8')) if os.path.exists(mpath) else {'media': {}, 'posts': {}}
save = lambda: json.dump(manifest, open(mpath, 'w', encoding='utf-8'), indent=1)
MIME = {'.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.png': 'image/png', '.gif': 'image/gif', '.webp': 'image/webp', '.pdf': 'application/pdf',
        '.doc': 'application/msword', '.docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '.xls': 'application/vnd.ms-excel',
        '.xlsx': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '.ppt': 'application/vnd.ms-powerpoint', '.pptx': 'application/vnd.openxmlformats-officedocument.presentationml.presentation'}


def api(method, route, query='', body=None, headers=None, tries=4):
    url = f'{site}/?rest_route={urllib.parse.quote(route)}' + (f'&{query}' if query else '')     # works even where /wp-json/ is blocked
    h = {'Authorization': auth, 'User-Agent': 'foundry-importer/1.0'}; h.update(headers or {})
    data = None if body is None else (body if isinstance(body, bytes) else json.dumps(body).encode())
    if isinstance(body, (dict, list)): h['Content-Type'] = 'application/json'
    for t in range(tries):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, data=data, headers=h, method=method), timeout=180) as r:
                return json.loads(r.read() or b'null'), r.headers
        except urllib.error.HTTPError as e:
            msg = e.read().decode('utf-8', 'replace')[:300]
            if e.code == 401: sys.exit(f'\nSTOP: {e.code} {msg}\n(check the username and the application password; the account must be an Administrator)')
            if e.code == 403 and 'Just a moment' in msg:                    # Cloudflare bot check: wait and try again, then give up on this request
                err = 'blocked by a Cloudflare challenge'; time.sleep(10 * (t + 1)); continue
            if e.code < 500 and e.code != 429: raise RuntimeError(f'{e.code} {msg}')
            err = f'{e.code}'
        except Exception as e:
            err = str(e)
        time.sleep(2 * (t + 1))
    raise RuntimeError(f'failed after retries: {route} ({err})')


posts = json.load(open(os.path.join(a.pack, 'posts.json'), encoding='utf-8'))
if a.limit: posts = posts[:a.limit]
me, _ = api('GET', '/wp/v2/users/me', '_fields=id,name')
print(f'Logged in as: {me["name"]}  ->  {site}\nArticles to import: {len(posts)}  status={a.status}' + ('  (DRY RUN)' if a.dry_run else ''))

# 1. categories and tags (posts only; static articles become pages and get neither) ---------------------------------------
def all_terms(route, key):
    out, page = {}, 1
    while True:
        rows, _ = api('GET', route, f'per_page=100&page={page}&_fields=id,slug,name')
        for c in rows: out[c[key]] = c['id']
        if len(rows) < 100: return out
        page += 1


cats, tags = all_terms('/wp/v2/categories', 'slug'), all_terms('/wp/v2/tags', 'name')
for p in posts:
    if p['kind'] != 'post': continue
    s = f'cat-{p["cid"]}'
    if p['cid'] and s not in cats:
        if a.dry_run: print('  would create category', p['category']); cats[s] = 0; continue
        c, _ = api('POST', '/wp/v2/categories', body={'name': p['category'], 'slug': s}); cats[s] = c['id']; print('  category', p['category'])
    for t in p['tags']:
        if t not in tags:
            if a.dry_run: tags[t] = 0; continue
            r, _ = api('POST', '/wp/v2/tags', body={'name': t}); tags[t] = r['id']


failed, blocked = set(), set()


# 2. media -----------------------------------------------------------------------------------------------------------
def upload(kind, name):
    key = f'{kind}/{name}'
    if key in manifest['media']: return manifest['media'][key]
    path = os.path.join(a.pack, 'media' if kind == 'media' else 'files', name)
    if not os.path.isfile(path): return None
    if a.dry_run: return {'id': 0, 'url': f'{site}/wp-content/uploads/{name}'}
    ext = os.path.splitext(name)[1].lower()
    stem = os.path.splitext(name)[0].lower()
    try:                                                          # already in the Media Library (uploaded by hand, or by an earlier run)?
        have, _ = api('GET', '/wp/v2/media', 'slug=' + urllib.parse.quote(re.sub(r'[^a-z0-9_-]+', '-', stem)) + '&_fields=id,source_url')
        have = [h for h in have if os.path.basename(h['source_url']).lower().startswith(stem[:20])]
        if have: manifest['media'][key] = {'id': have[0]['id'], 'url': have[0]['source_url']}; save(); return manifest['media'][key]
    except RuntimeError: pass
    if ext in blocked: failed.add(key); return None                # this file type was refused earlier in this run: do not hammer the server
    try:
        r, _ = api('POST', '/wp/v2/media', '_fields=id,source_url', open(path, 'rb').read(),
                   {'Content-Type': MIME.get(ext, mimetypes.guess_type(name)[0] or 'application/octet-stream'), 'Content-Disposition': f'attachment; filename="{name}"'})
    except RuntimeError as e:
        print(f'    ! could not upload {key}: {str(e)[:110]}'); failed.add(key); blocked.add(ext)
        print(f'    ! skipping further {ext} uploads in this run; the posts get a placeholder link, fixed on the next run'); return None
    manifest['media'][key] = {'id': r['id'], 'url': r['source_url']}; save(); time.sleep(0.5)       # be gentle with the server
    return manifest['media'][key]


# 3. posts and pages -------------------------------------------------------------------------------------------------
manifest.setdefault('kinds', {})
route = lambda kind: '/wp/v2/pages' if kind == 'page' else '/wp/v2/posts'
made = skipped = imgs = docs = 0
for i, p in enumerate(posts, 1):
    kind = p['kind']; slug = ('page-' if kind == 'page' else 'news-') + str(p['nid'])
    if str(p['nid']) in manifest['posts']: skipped += 1; continue
    found, _ = api('GET', route(kind), f'slug={slug}&status=any&_fields=id')
    if found: manifest['posts'][str(p['nid'])] = found[0]['id']; manifest['kinds'][str(p['nid'])] = kind; skipped += 1; continue
    def media(m):
        global imgs
        u = upload('media', m.group(1))
        if not u: return '#pending-media/' + m.group(1)
        imgs += 1; return u['url']
    def files(m):
        global docs
        u = upload('file', m.group(1))
        if not u: return '#pending-file/' + m.group(1)
        docs += 1; return u['url']
    body = re.sub(r'@media/([^"\')\s]+)', media, p['body_html']); body = re.sub(r'@file/([^"\')\s]+)', files, body)
    feat = upload('media', p['images'][0]) if p['images'] else None
    date = (p['date'] or '2024-01-01') + f'T08:{p["nid"] % 60:02d}:00'
    if a.dry_run: print(f'  [{i}/{len(posts)}] would create {kind} {slug}: {p["title"][:45]}  tags={p["tags"]}'); continue
    data = {'title': p['title'], 'content': body, 'status': a.status, 'date': date, 'slug': slug, 'featured_media': feat['id'] if feat else 0}
    if kind == 'post':
        data['categories'] = [cats[f'cat-{p["cid"]}']] if p['cid'] and cats.get(f'cat-{p["cid"]}') else []
        data['tags'] = [tags[t] for t in p['tags'] if tags.get(t)]
    r, _ = api('POST', route(kind), body=data)
    manifest['posts'][str(p['nid'])] = r['id']; manifest['kinds'][str(p['nid'])] = kind; made += 1
    if '#pending-' in body: manifest.setdefault('pending', {})[str(p['nid'])] = {'id': r['id'], 'kind': kind}
    save()
    print(f'  [{i}/{len(posts)}] {kind:4} {slug}  {p["title"][:45]}  ({len(p["images"])} img, {len(p["files"])} files, {len(p["tags"])} tags)')

# 3b. files that could not be uploaded earlier: try again, patch the posts that are waiting for them ------------------------
waiting = set()
if not a.dry_run:
    for nid, w in list(manifest.get('pending', {}).items()):
        cur, _ = api('GET', f'{route(w["kind"])}/{w["id"]}', 'context=edit&_fields=content')
        raw = cur['content']['raw']
        def again(m):
            u = upload('media' if m.group(1) == 'media' else 'file', m.group(2))
            return u['url'] if u else m.group(0)
        new = re.sub(r'#pending-(media|file)/([^"\')\s]+)', again, raw)
        if new != raw: api('POST', f'{route(w["kind"])}/{w["id"]}', body={'content': new})
        left = re.findall(r'#pending-(?:media|file)/([^"\')\s]+)', new)
        waiting.update(left)
        if not left: del manifest['pending'][nid]
        save()

# 4. links between articles ---------------------------------------------------------------------------------------------
firstpage = {}                                    # a category that became pages: link to its first page instead
for p in posts:
    if p['kind'] == 'page': firstpage.setdefault(p['cid'], manifest['posts'].get(str(p['nid'])))
relinked = 0
if not a.dry_run:
    for p in posts:
        pid = manifest['posts'].get(str(p['nid']))
        if not pid or not re.search(r'@(post|cat)/\d+', p['body_html']): continue
        cur, _ = api('GET', f'{route(p["kind"])}/{pid}', 'context=edit&_fields=content')
        raw = cur['content']['raw']
        def link(m):
            if m.group(1) == 'post':
                t = manifest['posts'].get(m.group(2)); return f'{site}/?p={t}' if t else '#'
            c = cats.get(f'cat-{m.group(2)}'); f = firstpage.get(int(m.group(2)))
            return f'{site}/?cat={c}' if c else (f'{site}/?p={f}' if f else '#')
        new = re.sub(r'@(post|cat)/(\d+)', link, raw)
        if new != raw: api('POST', f'{route(p["kind"])}/{pid}', body={'content': new}); relinked += 1

print(f'\nDONE: {made} created ({skipped} already there), {imgs} image refs + {docs} file refs, {relinked} re-linked. Status: {a.status}.')
if waiting:
    print(f'\n{len(waiting)} file(s) could NOT be uploaded (the server or Cloudflare refused them). Their articles exist, with a placeholder link in place of the file.')
    print('  Fix: upload those files by hand (Media > Add New, drag them in from <pack>\\files), then run this same command again: it finds them and fixes the links.')
    print('  Files: ' + ', '.join(sorted(waiting)[:12]) + (' ...' if len(waiting) > 12 else ''))
print('Next: review under Posts > All Posts and Pages > All Pages, then publish. Revoke the application password in your profile.')
