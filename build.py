#!/usr/bin/env python3
"""Assemble the static site in both languages.

    src/pages/*.html      + src/partials/*      ->  ./*.html        (English)
    src/pages-ne/*.html   + src/partials-ne/*   ->  ./ne/*.html     (Nepali)
    src/plus2/pages/*.html    + src/plus2/partials/*    -> ./plus2/*.html
    src/plus2/pages-ne/*.html + src/plus2/partials-ne/* -> ./plus2/ne/*.html
                          (+2 Science, Shree Kamala Secondary School; both
                          languages share src/plus2/partials/head.html)

Every page is the header (top bar), the side menu (sidenav.html), the page
itself and the footer, from that language's partials folder.

Run `python3 build.py` after editing anything under src/. The generated files
at the project root are what gets published; GitHub and Hostinger serve them
directly, so there is no other build step.

Placeholders substituted per language:
    {{BASE}}  the way back to the site root: '' for English, '../' for
              Nepali, '../' and '../../' on the +2 pages
    {{P2}}    on the +2 pages, the way back to plus2/ ('' or '../'), for the
              school's own assets and portal
    {{V2}}    on the +2 pages, a hash of plus2.css (as {{V}} below)
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
<body data-page="{page}"{body_attr}>

{header}

<div class="site-body">
{sidenav}
<div class="side-scrim" hidden></div>
<main id="main" class="site-main">
{body}
</main>
</div>

{footer}

</body>
</html>
"""


def photo(slug, initials, base, people=PEOPLE, url='assets/img/people/'):
    for ext in ('jpg', 'jpeg', 'png', 'webp'):
        if (people / f'{slug}.{ext}').is_file():
            return (f'<img src="{base}{url}{slug}.{ext}" alt="" '
                    f'width="320" height="320" loading="lazy" decoding="async">')
    return f'<span class="person-initials" aria-hidden="true">{initials}</span>'


def page_url(lang, name):
    """Absolute public URL of a page. Home pages are listed as / and /ne/."""
    return SITE + ('ne/' if lang == 'ne' else '') + ('' if name == 'index.html' else name)


def write_sitemap(built, plus2=()):
    urls = [page_url(lang, name) for lang, names in built for name in names]
    urls += [plus2_url(name, lang) for lang, names in plus2 for name in names]
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
    for rel in ('assets/css/styles.css', 'assets/js/main.js', 'assets/js/lang.js'):
        h.update((ROOT / rel).read_bytes())
    return h.hexdigest()[:10]


def body_attr(*classes):
    classes = [c for c in classes if c]
    return f' class="{" ".join(classes)}"' if classes else ''


def check_counterparts(en_dir, ne_dir, label):
    """Every page needs both languages: the language switch and the remembered-
    language redirect (assets/js/lang.js) send visitors to the counterpart, so
    a missing one would be a 404 for everyone who prefers that language."""
    names = lambda d: {p.name for p in d.glob('*.html')} if d.is_dir() else set()
    missing = sorted(names(en_dir) ^ names(ne_dir))
    if missing:
        raise SystemExit(f'{label}: these pages have no counterpart in the other '
                         f'language: {", ".join(missing)}. Add them to both '
                         f'{en_dir.relative_to(ROOT)} and {ne_dir.relative_to(ROOT)}.')


def meta(src, key, default=''):
    m = re.search(r'<!--\s*%s:\s*(.*?)\s*-->' % key, src)
    return m.group(1) if m else default


def build(lang, spec, version):
    head = (ROOT / 'src' / 'partials' / 'head.html').read_text().strip()
    header = (spec['partials'] / 'header.html').read_text().strip()
    # The page links, down the left beside the content (see styles.css).
    sidenav = (spec['partials'] / 'sidenav.html').read_text().strip()
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
            body_attr=body_attr('lang-ne' if lang == 'ne' else ''),
            head=head,
            extra_head=structured_data if lang == 'en' and path.name == 'index.html' else '',
            header=header,
            sidenav=sidenav,
            body=body,
            footer=footer,
        )
        html = re.sub(r'\{\{PHOTO:([a-z0-9-]+):([^}]+)\}\}',
                      lambda m: photo(m.group(1), m.group(2), spec['base']), html)
        html = html.replace('{{V}}', version)
        html = html.replace('{{BASE}}', spec['base'])
        html = html.replace('{{ALT}}', spec['alt'](path.name))
        (spec['out'] / path.name).write_text(html)
        built.append(path.name)

    return built


# The +2 Science section: Shree Kamala Secondary School's pages, served from
# /plus2/ (English) and /plus2/ne/ (Nepali) on this same domain. They have a
# header, side menu and footer of their own — the school's name and crest,
# not the campus's — and share the site's CSS and JS.
#
# Placeholders, on top of the campus ones:
#     {{P2}}   the prefix the +2 section's own files need — the crest, its
#              stylesheet, the portal: '' in English, '../' in Nepali
#     {{V2}}   a hash of plus2/assets/css/plus2.css, for its ?v=
PLUS2_OUT = ROOT / 'plus2'
PLUS2_PEOPLE = PLUS2_OUT / 'assets' / 'img' / 'people'
PLUS2_LANGS = {
    'en': {
        'pages':    ROOT / 'src' / 'plus2' / 'pages',
        'partials': ROOT / 'src' / 'plus2' / 'partials',
        'out':      PLUS2_OUT,
        'base':     '../',
        'p2':       '',
        'alt':      lambda name: f'ne/{name}',
    },
    'ne': {
        'pages':    ROOT / 'src' / 'plus2' / 'pages-ne',
        'partials': ROOT / 'src' / 'plus2' / 'partials-ne',
        'out':      PLUS2_OUT / 'ne',
        'base':     '../../',
        'p2':       '../',
        'alt':      lambda name: f'../{name}',
    },
}



def plus2_url(name, lang='en'):
    return SITE + 'plus2/' + ('ne/' if lang == 'ne' else '') + ('' if name == 'index.html' else name)


def build_plus2(version):
    """Both languages of the +2 section; returns [(lang, [names])]."""
    version2 = hashlib.sha1((PLUS2_OUT / 'assets' / 'css' / 'plus2.css').read_bytes()).hexdigest()[:10]
    head = (ROOT / 'src' / 'plus2' / 'partials' / 'head.html').read_text().strip()
    out = []
    for lang, spec in PLUS2_LANGS.items():
        if not spec['pages'].is_dir():
            continue
        header = (spec['partials'] / 'header.html').read_text().strip()
        sidenav = (spec['partials'] / 'sidenav.html').read_text().strip()
        footer = (spec['partials'] / 'footer.html').read_text().strip()
        spec['out'].mkdir(parents=True, exist_ok=True)
        built = []
        for path in sorted(spec['pages'].glob('*.html')):
            src = path.read_text()
            body = re.sub(r'<!--\s*(title|desc|page):.*?-->\s*', '', src, count=3).strip()
            html = TEMPLATE.format(
                code=lang,
                canonical=plus2_url(path.name, lang),
                url_en=plus2_url(path.name, 'en'),
                url_ne=plus2_url(path.name, 'ne'),
                title=meta(src, 'title', 'Shree Kamala Secondary School — +2 Science'),
                desc=meta(src, 'desc'),
                page=meta(src, 'page'),
                body_attr=body_attr('plus2', 'lang-ne' if lang == 'ne' else ''),
                head=head,
                extra_head='',
                header=header,
                sidenav=sidenav,
                body=body,
                footer=footer,
            )
            html = re.sub(r'\{\{PHOTO:([a-z0-9-]+):([^}]+)\}\}',
                          lambda m: photo(m.group(1), m.group(2), spec['p2'], PLUS2_PEOPLE, 'assets/img/people/'),
                          html)
            html = html.replace('{{V}}', version)
            # The +2 section's own stylesheet, versioned on its own, so editing
            # it never changes a campus page.
            html = html.replace('{{V2}}', version2)
            # The sources carry TODO notes for whoever fills the pages in; they
            # are for the repository, not for the live page's source.
            html = re.sub(r'[ \t]*<!--.*?-->[ \t]*\n?', '', html, flags=re.S)
            html = html.replace('{{P2}}', spec['p2'])
            html = html.replace('{{BASE}}', spec['base'])
            html = html.replace('{{ALT}}', spec['alt'](path.name))
            (spec['out'] / path.name).write_text(html)
            built.append(path.name)
        out.append((lang, built))
    return out


def write_notices_nav():
    """notices.php is PHP at the site root, outside the build. It prints these
    copies of the campus top bar and side menu, so its menu is always the one
    the built pages have. They sit in portal/inc/, which the web cannot read;
    notices.php fills in {{ALT}} itself, since its counterpart is a query
    string. A Nepali page link needs the ne/ that the built Nepali pages get
    from their folder."""
    for lang, spec in LANGS.items():
        html = ((spec['partials'] / 'header.html').read_text().strip()
                + '\n\n<div class="site-body">\n'
                + (spec['partials'] / 'sidenav.html').read_text().strip()
                + '\n<div class="side-scrim" hidden></div>\n')
        if lang == 'ne':
            html = re.sub(r'href="(?!\{\{|[a-z]+:|#)', 'href="ne/', html)
        html = html.replace('{{BASE}}', '')
        (ROOT / 'portal' / 'inc' / f'site-nav-{lang}.html').write_text(
            '<!-- Generated by build.py from src/partials' + ('-ne' if lang == 'ne' else '')
            + '/header.html and sidenav.html; edit those. -->\n' + html)


if __name__ == '__main__':
    check_counterparts(LANGS['en']['pages'], LANGS['ne']['pages'], 'Campus')
    check_counterparts(PLUS2_LANGS['en']['pages'], PLUS2_LANGS['ne']['pages'], '+2 Science')
    # Worked out once, not once a page.
    version = asset_version()
    built = []
    for lang, spec in LANGS.items():
        if not spec['pages'].is_dir():
            print(f'{lang}: no pages directory, skipped')
            continue
        names = build(lang, spec, version)
        built.append((lang, names))
        print(f'{lang}: {len(names)} pages -> {spec["out"].relative_to(ROOT) or "."}')
    plus2 = build_plus2(version)
    for lang, names in plus2:
        print(f'plus2 {lang}: {len(names)} pages -> {PLUS2_LANGS[lang]["out"].relative_to(ROOT)}')
    write_notices_nav()
    print('portal/inc/site-nav-{en,ne}.html: the menu for notices.php')
    print(f'sitemap.xml: {write_sitemap(built, plus2)} URLs')
