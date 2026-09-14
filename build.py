#!/usr/bin/env python3
"""Assemble the static site in both languages.

    src/pages/*.html      + src/partials/*      ->  ./*.html        (English)
    src/pages-ne/*.html   + src/partials-ne/*   ->  ./ne/*.html     (Nepali)

Run `python3 build.py` after editing anything under src/. The generated files
at the project root are what gets published; GitHub and Hostinger serve them
directly, so there is no other build step.

Placeholders substituted per language:
    {{BASE}}  '' for English, '../' for Nepali — prefixes shared assets
    {{ALT}}   link to the same page in the other language
    {{V}}     a hash of styles.css + main.js, appended as ?v= so browsers
              (which cache CSS and JS for 7 days) fetch the new files after
              every change — run this script after editing CSS or JS too
    {{PHOTO:slug:XY}}  the portrait assets/img/people/<slug>.jpg (or .jpeg,
              .png, .webp) if that file exists, otherwise the initials XY —
              so adding a photo is: drop the file in, run this script

Every page also gets a canonical link and English/Nepali hreflang links as
absolute URLs on SITE, so search engines list each page under one address;
the English home page gets src/partials/structured-data.html; and
sitemap.xml is rewritten from the page list.
"""
import hashlib
import pathlib
import re
from xml.sax.saxutils import escape

ROOT = pathlib.Path(__file__).parent
PEOPLE = ROOT / 'assets' / 'img' / 'people'

# The site's one public address. www and Hostinger's temporary domain redirect
# here (see .htaccess); canonical and hreflang links and the sitemap use it.
SITE = 'https://kamalasciencecampus.edu.np/'

LANGS = {
    'en': {
        'pages':    ROOT / 'src' / 'pages',
        'partials': ROOT / 'src' / 'partials',
        'out':      ROOT,
        'base':     '',
        'code':     'en',
        # English page -> the Nepali counterpart
        'alt':      lambda name: f'ne/{name}',
    },
    'ne': {
        'pages':    ROOT / 'src' / 'pages-ne',
        'partials': ROOT / 'src' / 'partials-ne',
        'out':      ROOT / 'ne',
        'base':     '../',
        'code':     'ne',
        'alt':      lambda name: f'../{name}',
    },
}

TEMPLATE = """<!doctype html>
<html lang="{code}">
<head>
<meta charset="utf-8">
<title>{title}</title>
<meta name="description" content="{desc}">
<link rel="canonical" href="{canonical}">
<link rel="alternate" hreflang="en" href="{url_en}">
<link rel="alternate" hreflang="ne" href="{url_ne}">
<link rel="alternate" hreflang="x-default" href="{url_en}">
{head}{extra_head}
</head>
<body data-page="{page}"{body_class}>

{header}

<main id="main">
{body}
</main>

{footer}

</body>
</html>
"""


def photo(slug, initials, base):
    for ext in ('jpg', 'jpeg', 'png', 'webp'):
        if (PEOPLE / f'{slug}.{ext}').is_file():
            return (f'<img src="{base}assets/img/people/{slug}.{ext}" alt="" '
                    f'width="320" height="320" loading="lazy" decoding="async">')
    return f'<span class="person-initials" aria-hidden="true">{initials}</span>'


def page_url(lang, name):
    """Absolute public URL of a page. Home pages are listed as / and /ne/."""
    return SITE + ('ne/' if lang == 'ne' else '') + ('' if name == 'index.html' else name)


def write_sitemap(built):
    urls = [page_url(lang, name) for lang, names in built for name in names]
    # notices.php is outside the build but public, in both languages.
    urls += [SITE + 'notices.php', SITE + 'notices.php?lang=ne']
    entries = ''.join(f'  <url><loc>{escape(u)}</loc></url>\n' for u in urls)
    (ROOT / 'sitemap.xml').write_text(
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
        + entries + '</urlset>\n')
    return len(urls)


def asset_version():
    h = hashlib.sha1()
    for rel in ('assets/css/styles.css', 'assets/js/main.js'):
        h.update((ROOT / rel).read_bytes())
    return h.hexdigest()[:10]


def meta(src, key, default=''):
    m = re.search(r'<!--\s*%s:\s*(.*?)\s*-->' % key, src)
    return m.group(1) if m else default


def build(lang, spec):
    head = (ROOT / 'src' / 'partials' / 'head.html').read_text().strip()
    header = (spec['partials'] / 'header.html').read_text().strip()
    # Nepali reuses the English footer only if it has no translation of its own.
    footer_path = spec['partials'] / 'footer.html'
    footer = footer_path.read_text().strip()
    structured_data = '\n' + (ROOT / 'src' / 'partials' / 'structured-data.html').read_text().strip()

    spec['out'].mkdir(parents=True, exist_ok=True)
    built = []

    for path in sorted(spec['pages'].glob('*.html')):
        src = path.read_text()
        body = re.sub(r'<!--\s*(title|desc|page):.*?-->\s*', '', src, count=3).strip()
        html = TEMPLATE.format(
            code=spec['code'],
            canonical=page_url(lang, path.name),
            url_en=page_url('en', path.name),
            url_ne=page_url('ne', path.name),
            title=meta(src, 'title', 'Kamala Science Campus'),
            desc=meta(src, 'desc'),
            page=meta(src, 'page'),
            body_class=' class="lang-ne"' if lang == 'ne' else '',
            head=head,
            extra_head=structured_data if lang == 'en' and path.name == 'index.html' else '',
            header=header,
            body=body,
            footer=footer,
        )
        html = re.sub(r'\{\{PHOTO:([a-z0-9-]+):([^}]+)\}\}',
                      lambda m: photo(m.group(1), m.group(2), spec['base']), html)
        html = html.replace('{{V}}', asset_version())
        html = html.replace('{{BASE}}', spec['base'])
        html = html.replace('{{ALT}}', spec['alt'](path.name))
        (spec['out'] / path.name).write_text(html)
        built.append(path.name)

    return built


if __name__ == '__main__':
    built = []
    for lang, spec in LANGS.items():
        if not spec['pages'].is_dir():
            print(f'{lang}: no pages directory, skipped')
            continue
        names = build(lang, spec)
        built.append((lang, names))
        print(f'{lang}: {len(names)} pages -> {spec["out"].relative_to(ROOT) or "."}')
    print(f'sitemap.xml: {write_sitemap(built)} URLs')
