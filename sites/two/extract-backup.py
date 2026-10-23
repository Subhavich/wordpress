"""
Turns the crawled backup of division2.immigration.go.th into an IMPORT PACK (read-only on the backup):

    pack/posts.json        one entry per article: nid, cid, category, title, date (Gregorian), views, body_html, images[], files[]
    pack/media/            images that were inline base64 in the HTML, written out as real files
    pack/files/            PDF / DOCX / XLSX that articles link to (copied from the backup)
    pack/REPORT.txt        what was found / what is missing

Usage (stdlib + bs4):   python -X utf8 sites/two/extract-backup.py <backup/division2.immigration.go.th> <output pack folder>
Then import it into WordPress with sites/two/import-real.php (see FOUNDRY.md).
"""
import base64, hashlib, json, os, re, shutil, sys
from bs4 import BeautifulSoup

src, out = sys.argv[1], sys.argv[2]
os.makedirs(f'{out}/media', exist_ok=True); os.makedirs(f'{out}/files', exist_ok=True)
MONTHS = {m: i + 1 for i, m in enumerate('มกราคม กุมภาพันธ์ มีนาคม เมษายน พฤษภาคม มิถุนายน กรกฎาคม สิงหาคม กันยายน ตุลาคม พฤศจิกายน ธันวาคม'.split())}
EXT = {'jpeg': 'jpg', 'jpg': 'jpg', 'png': 'png', 'gif': 'gif', 'webp': 'webp', 'svg+xml': 'svg'}
FILE_EXT = ('.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.zip')
report, posts, seen_media = [], [], {}


def thai_date(s):
    m = re.search(r'(\d{1,2})\s+(\S+)\s+(\d{4})', s or '')
    if not m or m.group(2) not in MONTHS: return None
    y = int(m.group(3)); y = y - 543 if y > 2400 else y
    return f'{y:04d}-{MONTHS[m.group(2)]:02d}-{int(m.group(1)):02d}'


def local_path(href):
    """map a (possibly rewritten) site-relative href to a file in the backup, or None"""
    if not href or re.match(r'^(https?:)?//|^(mailto|tel|data|#)', href):
        m = re.match(r'^https?://(?:www\.)?division2\.immigration\.go\.th/(.+)$', href or '')
        href = m.group(1) if m else None
    if not href: return None
    p = os.path.join(src, href.split('#')[0].replace('%20', ' '))
    return p if os.path.isfile(p) else None


names = sorted(f for f in os.listdir(src) if f.startswith('news_view'))
for fn in names:
    nid = int(re.search(r'nid=(\d+)', fn).group(1))
    soup = BeautifulSoup(open(os.path.join(src, fn), encoding='utf-8', errors='replace').read(), 'html.parser')
    title = soup.select_one('.s-content__header-title')
    body = soup.select_one('.s-content__main .font-content') or soup.select_one('.font-content')
    cat = soup.select_one('.s-content__header-meta .cat a')
    if not title or body is None:
        report.append(f'nid {nid}: no title/body, skipped'); continue
    cid = int(re.search(r'cid=(\d+)', cat['href']).group(1)) if cat and re.search(r'cid=(\d+)', cat['href']) else 0
    views = re.search(r'(\d+)\s*วิว', soup.get_text(' '))
    images, files = [], []
    for img in body.find_all('img'):
        s = img.get('src', '')
        m = re.match(r'data:image/([\w+.-]+);base64,(.*)', s, re.S)
        if m:
            raw = base64.b64decode(m.group(2)); h = hashlib.sha1(raw).hexdigest()[:10]
            name = f'n{nid}-{h}.{EXT.get(m.group(1), "bin")}'
            if h not in seen_media:
                open(f'{out}/media/{name}', 'wb').write(raw); seen_media[h] = name
            name = seen_media[h]; img['src'] = f'@media/{name}'; images.append(name)
        else:
            p = local_path(s)
            if p:
                name = f'n{nid}-{os.path.basename(p)}'; shutil.copy(p, f'{out}/media/{name}'); img['src'] = f'@media/{name}'; images.append(name)
            else:
                report.append(f'nid {nid}: image not in backup: {s[:80]}')
        for a in ('srcset', 'sizes', 'loading'): img.attrs.pop(a, None)
    for a in body.find_all('a', href=True):
        h = a['href']
        if h.lower().split('?')[0].endswith(FILE_EXT):
            p = local_path(h)
            if p:
                name = os.path.basename(p); shutil.copy(p, f'{out}/files/{name}'); a['href'] = f'@file/{name}'; files.append(name)
            else:
                report.append(f'nid {nid}: attachment not in backup: {h[:90]}')
    for a in body.find_all('a', href=True):          # crawler-rewritten internal links -> plain text link target removed later by the importer
        if re.search(r'(news_view|more_news)\.php', a['href']):
            m = re.search(r'(news_view|more_news)\.php(?:%3F|\?)?(?:nid|cid)=(\d+)', a['href'])
            if m: a['href'] = f'@{"post" if m.group(1) == "news_view" else "cat"}/{m.group(2)}'
    for t in body.find_all(['script', 'style']): t.decompose()
    html = body.decode_contents().strip()
    text = re.sub(r'\s+', ' ', body.get_text(' ', strip=True).replace('\xa0', ' ')).strip()
    d = thai_date(soup.select_one('.s-content__header-meta .date').get_text() if soup.select_one('.s-content__header-meta .date') else '')
    posts.append({'nid': nid, 'cid': cid, 'category': cat.get_text(strip=True) if cat else '', 'title': title.get_text(strip=True), 'date': d,
                  'views': int(views.group(1)) if views else 0, 'body_html': html, 'text_chars': len(text), 'images': images, 'files': files,
                  'iframes': len(body.find_all('iframe')), 'tables': len(body.find_all('table'))})
    if not d: report.append(f'nid {nid}: no date')

posts.sort(key=lambda p: (p['date'] or '', p['nid']))

# --- what becomes a PAGE and what becomes a POST, and the generated tags -----------------------------------------------
# The old CMS stored everything as an "article". About / contact / commanders / laws are not news: they become WordPress PAGES (no date, no tags).
STATIC_CIDS = {166, 172, 188, 189, 210}             # เกี่ยวกับเรา, ติดต่อเรา, โครงสร้าง/หน่วยงาน (admin site), ผู้บังคับบัญชา, กฏหมาย ระเบียบ
ITA_CIDS = {201, 229, 216, 214, 227, 198, 199, 226}   # 199/226 are the admin site's ITA indicator categories
TOPICS = [('พิธี', r'พิธี|พระราชกุศล|ถวายพระพร|ถวายเป็นพระ|บำเพ็ญกุศล|สตมวาร|ปัญญาสมวาร'), ('ประชุม', r'ประชุม'), ('สงกรานต์', r'สงกรานต์'),
          ('การจับกุม', r'จับกุม|ปราบปราม|ผลักดัน'), ('จิตอาสา', r'จิตอาสา'), ('แรงงานต่างด้าว', r'แรงงานต่างด้าว|ต่างด้าว 4 สัญชาติ'),
          ('ท่าอากาศยาน', r'ท่าอากาศยาน|สุวรรณภูมิ|ทอท\.'), ('TDAC', r'TDAC'), ('นโยบาย', r'นโยบาย|Anti|สินบน|NO GIFT|ของขวัญ'), ('สถิติ', r'สถิติ'),
          ('ร้องเรียน', r'ร้องเรียน'), ('จริยธรรม', r'จริยธรรม'), ('มอบ/ประดับยศ', r'ประดับเครื่องหมายยศ|มอบ'),
          ('ประกาศ', r'^\s*ประกาศ|ย้ายที่ทำการ'), ('ฝึกอบรม', r'ฝึกอบรม|อบรม'), ('ลงพื้นที่', r'ลงพื้นที่|ตรวจเยี่ยม'), ('ระบบดิจิทัล', r'แอปพลิเคชัน|โปรแกรม|Police Care')]


def make_tags(p):
    t, tags = p['title'], []
    be = int(p['date'][:4]) + 543 if p['date'] else None
    if be: tags.append(f'ปี {be}')
    if p['cid'] in ITA_CIDS or 'ITA' in t:
        m = re.search(r'(?:ประจำปี|ITA)\s*(?:งบประมาณ\s*)?(25\d\d)', t)
        tags.append(f'ITA {m.group(1) if m else be}')
        c = re.match(r'\s*([Oo]\d{1,2})\b', t) or re.match(r'\s*(0\d\d)\b', t)
        if c: tags.append(f'ITA {c.group(1).upper()}')
    for name, rx in TOPICS:
        if re.search(rx, t): tags.append(name)
    if p['files']: tags.append('มีเอกสารแนบ')
    return list(dict.fromkeys(tags))


for p in posts:
    p['kind'] = 'page' if p['cid'] in STATIC_CIDS else 'post'
    p['tags'] = [] if p['kind'] == 'page' else make_tags(p)
tagcount = {}
for p in posts:
    for t in p['tags']: tagcount.setdefault(t, []).append(p['nid'])
open(f'{out}/TAGS.txt', 'w', encoding='utf-8').write(
    f'{sum(1 for p in posts if p["kind"] == "page")} static articles become PAGES: ' + ', '.join(f'nid{p["nid"]} {p["title"][:30]}' for p in posts if p['kind'] == 'page') +
    f'\n{len(tagcount)} generated tags (name: posts)\n' + '\n'.join(f'{t}: {len(v)}' for t, v in sorted(tagcount.items(), key=lambda kv: -len(kv[1]))) +
    '\n\nposts without any topic/ITA tag: ' + ', '.join(f'nid{p["nid"]}' for p in posts if p['kind'] == 'post' and len(p['tags']) <= 1))
json.dump(posts, open(f'{out}/posts.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
empty = [p for p in posts if p['text_chars'] == 0 and not p['images'] and not p['files']]
lines = [f'articles parsed: {len(posts)} of {len(names)} news_view pages', f'media files written: {len(seen_media)}',
         f'attachments copied: {len(os.listdir(out + "/files"))}', f'with text: {sum(1 for p in posts if p["text_chars"])}, with images: {sum(1 for p in posts if p["images"])}, with files: {sum(1 for p in posts if p["files"])}',
         f'EMPTY (no text, image or file in the backup): {len(empty)} -> ' + ', '.join(f'nid{p["nid"]}' for p in empty)] + report
open(f'{out}/REPORT.txt', 'w', encoding='utf-8').write('\n'.join(lines))
print('\n'.join(lines))
