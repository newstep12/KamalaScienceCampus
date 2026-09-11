#!/usr/bin/env python3
"""Assemble the static site in both languages.

    src/pages/*.html      + src/partials/*      ->  ./*.html        (English)
    src/pages-ne/*.html   + src/partials-ne/*   ->  ./ne/*.html     (Nepali)

Run `python3 build.py` after editing anything under src/. The generated files
at the project root are what gets published; GitHub and Hostinger serve them
directly, so there is no other build step.

Two placeholders are substituted per language:
    {{BASE}}  '' for English, '../' for Nepali — prefixes shared assets
    {{ALT}}   link to the same page in the other language
"""
import pathlib
import re

ROOT = pathlib.Path(__file__).parent

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
<title>{title}</title>
<meta name="description" content="{desc}">
<link rel="alternate" hreflang="{alt_code}" href="{alt}">
{head}
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


def meta(src, key, default=''):
    m = re.search(r'<!--\s*%s:\s*(.*?)\s*-->' % key, src)
    return m.group(1) if m else default


def build(lang, spec):
    head = (ROOT / 'src' / 'partials' / 'head.html').read_text().strip()
    header = (spec['partials'] / 'header.html').read_text().strip()
    # Nepali reuses the English footer only if it has no translation of its own.
    footer_path = spec['partials'] / 'footer.html'
    footer = footer_path.read_text().strip()

    spec['out'].mkdir(parents=True, exist_ok=True)
    built = []

    for path in sorted(spec['pages'].glob('*.html')):
        src = path.read_text()
        body = re.sub(r'<!--\s*(title|desc|page):.*?-->\s*', '', src, count=3).strip()
        html = TEMPLATE.format(
            code=spec['code'],
            alt_code='ne' if lang == 'en' else 'en',
            alt=spec['alt'](path.name),
            title=meta(src, 'title', 'Kamala Science Campus'),
            desc=meta(src, 'desc'),
            page=meta(src, 'page'),
            body_class=' class="lang-ne"' if lang == 'ne' else '',
            head=head,
            header=header,
            body=body,
            footer=footer,
        )
        html = html.replace('{{BASE}}', spec['base'])
        html = html.replace('{{ALT}}', spec['alt'](path.name))
        (spec['out'] / path.name).write_text(html)
        built.append(path.name)

    return built


if __name__ == '__main__':
    for lang, spec in LANGS.items():
        if not spec['pages'].is_dir():
            print(f'{lang}: no pages directory, skipped')
            continue
        names = build(lang, spec)
        print(f'{lang}: {len(names)} pages -> {spec["out"].relative_to(ROOT) or "."}')
