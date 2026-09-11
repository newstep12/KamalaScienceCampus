#!/usr/bin/env python3
"""Assemble the static site: src/pages/*.html + src/partials -> ./*.html

Run `python3 build.py` after editing anything in src/. The generated .html
files at the project root are what gets published (GitHub Pages serves them
directly; no other tooling is required).
"""
import pathlib
import re

ROOT = pathlib.Path(__file__).parent
PARTIALS = ROOT / "src" / "partials"
PAGES = ROOT / "src" / "pages"

head = (PARTIALS / "head.html").read_text().strip()
header = (PARTIALS / "header.html").read_text().strip()
footer = (PARTIALS / "footer.html").read_text().strip()

TEMPLATE = """<!doctype html>
<html lang="en">
<head>
<title>{title}</title>
<meta name="description" content="{desc}">
{head}
</head>
<body data-page="{page}">

{header}

<main id="main">
{body}
</main>

{footer}

</body>
</html>
"""


def meta(src, key, default=""):
    m = re.search(r"<!--\s*%s:\s*(.*?)\s*-->" % key, src)
    return m.group(1) if m else default


built = []
for path in sorted(PAGES.glob("*.html")):
    src = path.read_text()
    body = re.sub(r"<!--\s*(title|desc|page):.*?-->\s*", "", src, count=3).strip()
    out = ROOT / path.name
    out.write_text(
        TEMPLATE.format(
            title=meta(src, "title", "Kamala Science Campus"),
            desc=meta(src, "desc"),
            page=meta(src, "page"),
            head=head,
            header=header,
            body=body,
            footer=footer,
        )
    )
    built.append(path.name)

print("built:", ", ".join(built))
