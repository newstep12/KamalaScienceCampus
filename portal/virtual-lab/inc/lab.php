<?php if (!defined('VPL_LAB')) { http_response_code(403); exit; } ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex">
<?php echo $VPL_INJECT; ?>
<title>Kamala Virtual Physics Lab</title>
<link rel="stylesheet" href="assets/fonts/fonts.css">
<script>
window.MathJax = { tex: { inlineMath: [['\\(', '\\)']], displayMath: [['\\[', '\\]']] }, svg: { fontCache: 'global' }, startup: { typeset: false } };
</script>
<script src="assets/mathjax/tex-svg.js" async></script>
<style>
:root {
  /* Layout: instrument-bench workspace. Fixed experiment index on the left; each experiment opens on tabs, and the bench is a two-column grid (instrument | lab notebook) that stacks on phones. */
  --bg: #edf1f1;
  --panel: #ffffff;
  --panel2: #f5f8f8;
  --ink: #15212a;
  --muted: #55646d;
  --line: #d2dadd;
  --grid: #e4eaec;
  --accent: #1a5a80;
  --accent-soft: #dceaf2;
  --accent-ink: #ffffff;
  --sodium: #b87700;
  --sodium-soft: #fbefd5;
  --ok: #23744a;
  --ok-soft: #ddefe4;
  --warn: #a4521d;
  --warn-soft: #f8e6d9;
  --bad: #b0281f;
  --field: #05080a;
  --plot1: #1a5a80;
  --plot2: #c98400;
  --plot3: #6a4ea1;
  --plot4: #23744a;
  --shadow: 0 1px 2px rgba(20, 40, 50, .06), 0 6px 20px rgba(20, 40, 50, .06);
  --bench: #dde5e2;
  --benchgrid: rgba(20, 50, 40, .06);
  --wirehalo: rgba(0, 0, 0, .2);
  --f-display: "IBM Plex Sans Condensed", "Arial Narrow", "Roboto Condensed", sans-serif;
  --f-body: "IBM Plex Sans", "Segoe UI", system-ui, -apple-system, sans-serif;
  --f-mono: "IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #0f1519; --panel: #162026; --panel2: #1b272e; --ink: #e3eaed; --muted: #94a4ad; --line: #2a3841; --grid: #213039;
    --accent: #72b6df; --accent-soft: #1d3646; --accent-ink: #08141b; --sodium: #f0b13a; --sodium-soft: #3a2f17;
    --ok: #62c48f; --ok-soft: #173327; --warn: #ec9259; --warn-soft: #3a2518; --bad: #f06e64; --field: #000000;
    --plot1: #72b6df; --plot2: #f0b13a; --plot3: #b49ae8; --plot4: #62c48f;
    --shadow: 0 1px 2px rgba(0,0,0,.3), 0 6px 20px rgba(0,0,0,.25);
    --bench: #1a2328; --benchgrid: rgba(255,255,255,.035); --wirehalo: rgba(255,255,255,.3);
    color-scheme: dark;
  }
}
:root[data-theme="dark"] {
  --bg: #0f1519; --panel: #162026; --panel2: #1b272e; --ink: #e3eaed; --muted: #94a4ad; --line: #2a3841; --grid: #213039;
  --accent: #72b6df; --accent-soft: #1d3646; --accent-ink: #08141b; --sodium: #f0b13a; --sodium-soft: #3a2f17;
  --ok: #62c48f; --ok-soft: #173327; --warn: #ec9259; --warn-soft: #3a2518; --bad: #f06e64; --field: #000000;
  --plot1: #72b6df; --plot2: #f0b13a; --plot3: #b49ae8; --plot4: #62c48f;
  --shadow: 0 1px 2px rgba(0,0,0,.3), 0 6px 20px rgba(0,0,0,.25);
  --bench: #1a2328; --benchgrid: rgba(255,255,255,.035); --wirehalo: rgba(255,255,255,.3);
  color-scheme: dark;
}
* { box-sizing: border-box; }
html, body { background: var(--bg); color: var(--ink); }
body { font: 15px/1.55 var(--f-body); -webkit-font-smoothing: antialiased; }
a { color: var(--accent); }
button, input, select { font: inherit; color: inherit; }
:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
h1, h2, h3, h4 { font-family: var(--f-display); text-wrap: balance; line-height: 1.2; margin: 0; }
sub, sup { line-height: 0; }

/* ---------- top bar ---------- */
.top {
  position: sticky; top: env(safe-area-inset-top, 0px); z-index: 30;
  display: flex; align-items: center; gap: 12px; justify-content: space-between;
  padding: 10px 16px; background: var(--panel); border-bottom: 1px solid var(--line);
}
.brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--ink); min-width: 0; }
.brand-mark { width: 38px; height: 26px; border-radius: 4px; flex: none; background: var(--field); position: relative; overflow: hidden; }
.brand-mark i { position: absolute; top: 3px; bottom: 3px; width: 2px; border-radius: 1px; }
.brand b { display: block; font-family: var(--f-display); font-weight: 700; font-size: 17px; letter-spacing: .01em; }
.brand small { display: block; color: var(--muted); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-tools { display: flex; align-items: center; gap: 10px; flex: none; }
.sw { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); cursor: pointer; user-select: none; }
.sw input { appearance: none; width: 34px; height: 20px; border-radius: 10px; background: var(--line); position: relative; cursor: pointer; transition: background .15s; margin: 0; flex: none; }
.sw input::after { content: ""; position: absolute; top: 3px; left: 3px; width: 14px; height: 14px; border-radius: 50%; background: var(--panel); transition: left .15s; box-shadow: 0 1px 2px rgba(0,0,0,.25); }
.sw input:checked { background: var(--accent); }
.sw input:checked::after { left: 17px; }
.menu-btn { display: none; border: 1px solid var(--line); background: var(--panel2); border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer; }

/* ---------- layout ---------- */
.layout { display: grid; grid-template-columns: 268px minmax(0, 1fr); min-height: calc(100vh - 56px); }
.side {
  position: sticky; top: calc(env(safe-area-inset-top, 0px) + 53px); align-self: start;
  max-height: calc(100vh - 53px); overflow-y: auto; padding: 18px 12px 30px 16px; border-right: 1px solid var(--line);
}
.side h4 { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin: 18px 8px 6px; font-weight: 600; }
.side h4:first-of-type { margin-top: 6px; }
.side a { display: grid; grid-template-columns: 30px 1fr; gap: 8px; align-items: baseline; padding: 6px 8px; border-radius: 7px; text-decoration: none; color: var(--ink); font-size: 13.5px; line-height: 1.35; }
.side a:hover { background: var(--panel2); }
.side a.on { background: var(--accent-soft); color: var(--ink); }
.side a .n { font-family: var(--f-mono); font-size: 12px; color: var(--muted); }
.side a.on .n { color: var(--accent); font-weight: 600; }
.side a.home { grid-template-columns: 1fr; font-weight: 600; }
main { padding: 22px 16px 70px; min-width: 0; }
.wrap { max-width: 1380px; margin: 0 auto; }
@media (min-width: 760px) { main { padding: 26px 28px 80px; } }
@media (max-width: 979px) {
  .layout { grid-template-columns: minmax(0, 1fr); }
  .menu-btn { display: inline-block; }
  .side { position: fixed; z-index: 40; top: 0; bottom: 0; left: 0; width: min(300px, 86vw); max-height: none; background: var(--panel); transform: translateX(-102%); transition: transform .2s; box-shadow: var(--shadow); padding-top: calc(env(safe-area-inset-top, 0px) + 18px); }
  .side.open { transform: none; }
  .scrim { position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 35; }
  .sw span.long { display: none; }
}
@media (max-width: 520px) { .brand small { display: none; } .brand b { font-size: 15px; white-space: nowrap; } .sw > span { display: none; } .top { padding: 8px 12px; } }

/* ---------- experiment header & tabs ---------- */
.eyebrow { font-family: var(--f-mono); font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.eyebrow .no { color: var(--accent); font-weight: 600; }
.exp-head h1 { font-size: clamp(24px, 3.2vw, 34px); font-weight: 600; margin: 6px 0 4px; max-width: 30ch; }
.exp-head .aim { color: var(--muted); max-width: 75ch; margin: 0; }
.tabs { display: flex; gap: 4px; margin: 18px 0 20px; border-bottom: 1px solid var(--line); overflow-x: auto; scrollbar-width: none; }
.tabs button { border: 0; background: none; padding: 10px 14px; font-weight: 600; font-size: 14px; color: var(--muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; }
.tabs button[aria-selected="true"] { color: var(--ink); border-bottom-color: var(--accent); }
.tabs button:hover { color: var(--ink); }

/* ---------- prose ---------- */
.prose { max-width: 78ch; }
.prose h2 { font-size: 21px; margin: 26px 0 8px; font-weight: 600; }
.prose h2:first-child { margin-top: 0; }
.prose h3 { font-size: 16.5px; margin: 20px 0 6px; font-weight: 600; }
.prose p, .prose li { margin: 0 0 10px; }
.prose ul, .prose ol { padding-left: 22px; margin: 0 0 12px; }
.prose ol.steps { list-style: none; padding-left: 0; counter-reset: st; }
.prose ol.steps > li { counter-increment: st; position: relative; padding-left: 40px; margin-bottom: 12px; }
.prose ol.steps > li::before { content: counter(st); position: absolute; left: 0; top: 1px; width: 26px; height: 26px; border-radius: 50%; border: 1.5px solid var(--accent); color: var(--accent); font: 600 12.5px/23px var(--f-mono); text-align: center; }
.prose .eq { background: var(--panel); border: 1px solid var(--line); border-radius: 8px; padding: 10px 14px; margin: 10px 0 14px; overflow-x: auto; }
.prose .note { background: var(--sodium-soft); border-radius: 8px; padding: 10px 14px; margin: 12px 0; }
.prose .vnote { background: var(--accent-soft); border-radius: 8px; padding: 10px 14px; margin: 12px 0; }
.prose table { border-collapse: collapse; margin: 8px 0 14px; font-size: 14px; }
.prose th, .prose td { border: 1px solid var(--line); padding: 5px 10px; text-align: left; }
.prose th { background: var(--panel2); font-weight: 600; }
.tw { overflow-x: auto; max-width: 100%; }
.prose figure { margin: 10px 0 16px; }
.prose figcaption { font-size: 13px; color: var(--muted); margin-top: 4px; }
.kv { display: grid; grid-template-columns: max-content 1fr; gap: 4px 14px; font-size: 14px; }
.kv dt { color: var(--muted); }
.kv dd { margin: 0; }
mjx-container { max-width: 100%; overflow-x: auto; overflow-y: hidden; }
mjx-container[display="true"] { margin: .4em 0 !important; }

details.viva { border-bottom: 1px solid var(--line); padding: 10px 0; }
details.viva summary { cursor: pointer; font-weight: 600; list-style: none; display: grid; grid-template-columns: 30px 1fr; }
details.viva summary::-webkit-details-marker { display: none; }
details.viva summary .q { font-family: var(--f-mono); color: var(--accent); font-size: 13px; padding-top: 2px; }
details.viva .a { padding: 6px 0 2px 30px; color: var(--ink); }
details.viva[open] summary { color: var(--accent); }

/* ---------- bench ---------- */
.bench { display: grid; gap: 18px; grid-template-columns: minmax(0, 1fr); }
@media (min-width: 1240px) { .bench { grid-template-columns: minmax(0, 1.08fr) minmax(0, 1fr); align-items: start; } }
.card { background: var(--panel); border: 1px solid var(--line); border-radius: 10px; padding: 14px; min-width: 0; }
.card + .card { margin-top: 16px; }
.card-h { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
.card-h h3 { font-size: 15px; letter-spacing: .04em; text-transform: uppercase; font-weight: 600; }
.card-h .sub { font-size: 12.5px; color: var(--muted); }
.stack { display: grid; gap: 12px; min-width: 0; }
.cols2 { display: grid; gap: 12px; grid-template-columns: minmax(0, 1fr); }
@media (min-width: 620px) { .cols2 { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } .cols2.wide-l { grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); } }
.cv { width: 100%; position: relative; }
.cv canvas { display: block; width: 100%; border-radius: 8px; touch-action: none; }
.cv-label { font-size: 11.5px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin: 0 0 4px; font-weight: 600; }
.ctls { display: grid; gap: 10px 16px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
.ctl { min-width: 0; }
.ctl-top { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; font-size: 13px; }
.ctl-top label { color: var(--muted); font-weight: 500; }
.ctl-val { font-family: var(--f-mono); font-size: 13px; font-variant-numeric: tabular-nums; }
.ctl input[type=range] { width: 100%; accent-color: var(--accent); margin: 4px 0 2px; }
.fine { display: flex; gap: 4px; flex-wrap: wrap; }
.fbtn { border: 1px solid var(--line); background: var(--panel2); border-radius: 6px; padding: 3px 7px; font: 500 12px var(--f-mono); cursor: pointer; min-width: 38px; touch-action: manipulation; user-select: none; }
.fbtn:hover { border-color: var(--accent); color: var(--accent); }
.fbtn:active { background: var(--accent-soft); }
.opts { display: flex; flex-wrap: wrap; gap: 10px 14px; align-items: center; }
.fld { display: inline-flex; gap: 6px; align-items: center; font-size: 13px; }
.fld > span { color: var(--muted); }
select, input[type=number], input[type=text] { border: 1px solid var(--line); background: var(--panel); border-radius: 7px; padding: 5px 8px; font-size: 13.5px; max-width: 100%; }
input[type=number] { width: 96px; font-family: var(--f-mono); }
.seg { display: inline-flex; border: 1px solid var(--line); border-radius: 7px; overflow: hidden; }
.seg button { border: 0; background: var(--panel); padding: 5px 10px; font-size: 12.5px; cursor: pointer; border-right: 1px solid var(--line); }
.seg button:last-child { border-right: 0; }
.seg button[aria-pressed="true"] { background: var(--accent); color: var(--accent-ink); }
.chk { display: inline-flex; gap: 6px; align-items: center; font-size: 13px; cursor: pointer; }
.chk input { accent-color: var(--accent); width: 16px; height: 16px; }
.btn { border: 1px solid var(--line); background: var(--panel2); border-radius: 8px; padding: 7px 13px; font-weight: 600; font-size: 13.5px; cursor: pointer; }
.btn:hover { border-color: var(--accent); }
.btn.pri { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
.btn.pri:hover { filter: brightness(1.08); }
.btn.sm { padding: 4px 9px; font-size: 12.5px; font-weight: 500; }
.btn:disabled { opacity: .45; cursor: not-allowed; }
.recrow { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding-top: 4px; }
.recrow select { min-width: 0; flex: 1 1 200px; }
.status { font-size: 13px; border-radius: 7px; padding: 7px 10px; background: var(--panel2); color: var(--muted); min-height: 34px; }
.status.ok { background: var(--ok-soft); color: var(--ok); }
.status.warn { background: var(--warn-soft); color: var(--warn); }
.readouts { display: flex; flex-wrap: wrap; gap: 8px; }
.ro { background: var(--field); color: #ffd36b; border-radius: 7px; padding: 6px 10px; min-width: 112px; }
.ro small { display: block; font: 600 10px var(--f-body); letter-spacing: .08em; text-transform: uppercase; color: #9fb0b8; }
.ro b { font: 500 18px var(--f-mono); font-variant-numeric: tabular-nums; letter-spacing: .02em; }
.ro.hid b { color: #5a6a72; }
.lamp-note { font-size: 12.5px; color: var(--muted); }
.pill { display: inline-block; font: 600 11px var(--f-mono); letter-spacing: .05em; padding: 2px 7px; border-radius: 99px; background: var(--chip, var(--panel2)); border: 1px solid var(--line); color: var(--muted); }
.pill.warn { color: var(--warn); border-color: var(--warn); background: var(--warn-soft); }

/* observation tables */
.tblbar { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
.tblbar h4 { font-size: 14px; font-weight: 600; }
.tblbar .tools { display: flex; gap: 6px; }
.tscroll { overflow-x: auto; border: 1px solid var(--line); border-radius: 8px; }
table.obs { border-collapse: collapse; width: 100%; font-size: 12.5px; font-variant-numeric: tabular-nums; }
table.obs th { background: var(--panel2); font: 600 11.5px/1.3 var(--f-body); color: var(--muted); text-align: center; padding: 7px 6px; border-bottom: 1px solid var(--line); border-right: 1px solid var(--grid); vertical-align: bottom; }
table.obs td { font-family: var(--f-mono); text-align: center; padding: 5px 6px; border-bottom: 1px solid var(--grid); border-right: 1px solid var(--grid); white-space: nowrap; }
table.obs tr:last-child td { border-bottom: 0; }
table.obs th:last-child, table.obs td:last-child { border-right: 0; }
table.obs td.lbl { font-family: var(--f-body); text-align: left; }
table.obs td.empty { color: var(--line); }
table.obs td.calc { color: var(--accent); }
table.obs tr.flash td { animation: flash 1.2s ease-out; }
@keyframes flash { from { background: var(--sodium-soft); } to { background: transparent; } }
table.obs button.x { border: 0; background: none; color: var(--muted); cursor: pointer; font-size: 15px; padding: 0 4px; line-height: 1; }
table.obs button.x:hover { color: var(--bad); }
.result { border-radius: 8px; background: var(--panel2); padding: 12px 14px; font-size: 14px; }
.result h4 { font-size: 13px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; font-weight: 600; }
.result .big { font: 600 17px var(--f-body); }
.result .big b { font-family: var(--f-mono); }
.result p { margin: 4px 0; }
.calc-lines { font-family: var(--f-mono); font-size: 12.5px; line-height: 1.7; overflow-x: auto; }
.calc-lines div { white-space: nowrap; }
.muted { color: var(--muted); }
.small { font-size: 12.5px; }
.howto { font-size: 13px; color: var(--muted); border-left: 2px solid var(--sodium); padding: 2px 0 2px 10px; margin: 0; }

/* sheet for reading practice */
.sheet { position: fixed; z-index: 60; left: 50%; transform: translateX(-50%); bottom: 0; width: min(560px, 100%); background: var(--panel); border: 1px solid var(--line); border-bottom: 0; border-radius: 14px 14px 0 0; box-shadow: 0 -8px 30px rgba(0,0,0,.18); padding: 16px 16px calc(16px + env(safe-area-inset-bottom, 0px)); }
.sheet h4 { font-size: 16px; margin-bottom: 4px; }
.sheet p { margin: 4px 0 10px; font-size: 13.5px; color: var(--muted); }
.sheet .row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.sheet input { font-family: var(--f-mono); font-size: 16px; padding: 8px 10px; width: 170px; }
.sheet .fb { margin-top: 10px; font-size: 13.5px; }
.sheet .fb.ok { color: var(--ok); }
.sheet .fb.no { color: var(--warn); }

/* home */
.hero { display: grid; gap: 18px; margin-bottom: 26px; }
.hero h1 { font-size: clamp(30px, 4.6vw, 48px); font-weight: 700; letter-spacing: -.01em; max-width: 18ch; }
.hero p { color: var(--muted); max-width: 66ch; margin: 0; font-size: 16px; }
.spec-strip { border-radius: 10px; overflow: hidden; background: var(--field); }
.spec-cap { display: flex; justify-content: space-between; font: 12px var(--f-mono); color: var(--muted); margin-top: 6px; flex-wrap: wrap; gap: 6px; }
.howgrid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin: 6px 0 30px; }
.howgrid div { border-top: 2px solid var(--accent); padding-top: 10px; font-size: 14px; }
.howgrid b { display: block; font-family: var(--f-display); font-size: 16px; margin-bottom: 2px; }
.grp-title { font-size: 13px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin: 26px 0 10px; font-weight: 600; }
.cards { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); }
.xcard { display: grid; grid-template-columns: 42px 1fr; gap: 10px; text-decoration: none; color: var(--ink); background: var(--panel); border: 1px solid var(--line); border-radius: 10px; padding: 13px 14px; transition: border-color .15s, transform .15s; }
.xcard:hover { border-color: var(--accent); transform: translateY(-1px); }
.xcard .n { font: 600 20px var(--f-mono); color: var(--accent); }
.xcard b { display: block; font-family: var(--f-display); font-size: 16.5px; font-weight: 600; line-height: 1.25; margin-bottom: 3px; }
.xcard span { font-size: 13px; color: var(--muted); display: block; }
footer.foot { margin-top: 40px; padding-top: 14px; border-top: 1px solid var(--line); color: var(--muted); font-size: 13px; }

/* ---------- wiring workbench ---------- */
.cb-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, 1fr); margin-bottom: 18px; }
@media (min-width: 1180px) { .cb-grid { grid-template-columns: minmax(0, .66fr) minmax(0, 1.34fr); align-items: start; } .cb-diag { position: sticky; top: 70px; } }
.cb-diag .schem { width: 100%; height: auto; display: block; }
.schem .s-w { stroke: var(--ink); stroke-width: 1.6; fill: none; stroke-linecap: round; stroke-linejoin: round; }
.schem .s-thick { stroke-width: 3; }
.schem .s-f { fill: var(--ink); }
.schem .s-m { fill: var(--panel); stroke: var(--ink); stroke-width: 1.6; }
.schem .s-dot { fill: var(--ink); }
.schem .s-t { fill: var(--ink); font-family: var(--f-body); }
.cb-tray { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; min-height: 30px; align-items: center; }
.cb-tray-item { display: flex; flex-direction: column; align-items: center; gap: 3px; border: 1px dashed var(--muted); background: var(--panel2); border-radius: 8px; padding: 6px 8px; cursor: grab; font-size: 11.5px; touch-action: none; max-width: 150px; }
.cb-tray-item:hover { border-color: var(--accent); border-style: solid; }
.cb-tray-item span { text-align: center; line-height: 1.2; }
.cb-ghost { position: fixed; pointer-events: none; z-index: 80; opacity: .9; filter: drop-shadow(0 6px 10px rgba(0,0,0,.35)); }
.cb-ws { border-radius: 10px; position: relative; }
.cb-svg { display: block; width: 100%; height: auto; touch-action: none; user-select: none; -webkit-user-select: none; border-radius: 10px; }
.cb-bench { fill: var(--bench); }
.cb-gridline { stroke: var(--benchgrid); stroke-width: 1; }
.eq-ink { fill: var(--ink); font-family: var(--f-body); }
.cb-part { cursor: move; }
.cb-term { cursor: crosshair; }
.cb-term .hit { fill: transparent; }
.cb-term .ring { fill: #c9a227; stroke: #6b5510; stroke-width: 1.5; }
.cb-term.k-pos .ring { fill: #d6372a; stroke: #7a1810; }
.cb-term.k-neg .ring { fill: #2a2d30; stroke: #000; }
.cb-term.k-pin .ring { fill: #d4d9dc; stroke: #6b7378; }
.cb-term.k-lead .ring { fill: #b7bec2; stroke: #5d666b; }
.cb-term .core { fill: rgba(0,0,0,.45); }
.cb-term:hover .ring { stroke: var(--accent); stroke-width: 3.5; }
.cb-term.sel .ring { stroke: var(--sodium); stroke-width: 4; }
.cb-wire { cursor: pointer; }
.cb-wire .halo { stroke: var(--wirehalo); stroke-width: 7; fill: none; stroke-linecap: round; }
.cb-wire .core { stroke-width: 3.4; fill: none; stroke-linecap: round; }
.cb-wire:hover .core { stroke-width: 5.5; }
.cb-temp { stroke: var(--accent); stroke-width: 2.5; stroke-dasharray: 6 5; fill: none; pointer-events: none; }
.cb-pulse { fill: none; stroke: var(--sodium); stroke-width: 3; animation: cbpulse 1s ease-in-out infinite; pointer-events: none; }
@keyframes cbpulse { 50% { stroke-opacity: .15; } }
.cb-ghostwire { fill: none; stroke: var(--sodium); stroke-width: 3; stroke-dasharray: 7 6; pointer-events: none; }
.cb-spark line { stroke: #ffd54a; stroke-width: 3.5; stroke-linecap: round; animation: cbspark 1s ease-out forwards; }
@keyframes cbspark { from { opacity: 1; } to { opacity: 0; } }
.cb-ws.blown, .cb-ws.nope { animation: cbshake .42s; }
@keyframes cbshake { 20%, 60% { transform: translateX(-7px); } 40%, 80% { transform: translateX(7px); } }
.cb-ws.ok { animation: cbok 1.3s; }
@keyframes cbok { from { box-shadow: 0 0 0 5px var(--ok); } to { box-shadow: 0 0 0 0 transparent; } }
.cb-tools { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: 10px 0 8px; }
.cb-power { margin-left: auto; display: inline-flex; gap: 8px; align-items: center; border: 1px solid var(--line); background: var(--panel2); border-radius: 99px; padding: 6px 14px 6px 8px; font-weight: 600; font-size: 13px; cursor: pointer; }
.cb-power .lamp { width: 14px; height: 14px; border-radius: 50%; background: #6a2323; box-shadow: inset 0 0 0 2px rgba(0,0,0,.3); }
.cb-power[aria-pressed="true"] { border-color: var(--ok); }
.cb-power[aria-pressed="true"] .lamp { background: #36d36b; box-shadow: 0 0 10px #36d36b; }
.cb-stages { margin-bottom: 12px; }
.cb-stages .seg { flex-wrap: wrap; }
.cro-wrap { display: grid; gap: 12px; grid-template-columns: minmax(0, 1fr); }
@media (min-width: 760px) { .cro-wrap { grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); align-items: start; } }
.cro-panel { background: #3e4952; border-radius: 10px; padding: 12px; color: #e8eef1; display: grid; gap: 10px; }
.cro-panel .ctl-top label, .cro-panel .fld > span, .cro-panel .chk { color: #c8d3d9; }
.cro-panel select { background: #22292e; color: #e8eef1; border-color: #56636c; }
.cro-panel .seg button { background: #22292e; color: #e8eef1; border-color: #56636c; }
.cro-panel .seg button[aria-pressed="true"] { background: #7dd3fc; color: #0b1a22; }
.cro-panel .fbtn { background: #22292e; color: #e8eef1; border-color: #56636c; }
.cro-panel .ctl input[type=range] { accent-color: #7dd3fc; }
.cro-panel h5 { margin: 0; font: 600 11px var(--f-body); letter-spacing: .1em; text-transform: uppercase; color: #9fb0b8; }
.readbox { display: flex; flex-wrap: wrap; gap: 8px; align-items: end; }
.readbox label { display: grid; gap: 3px; font-size: 12.5px; color: var(--muted); }
.truth { border-collapse: collapse; font: 13px var(--f-mono); }
.truth td, .truth th { border: 1px solid var(--line); padding: 4px 10px; text-align: center; }
.truth th { background: var(--panel2); font-family: var(--f-body); font-size: 12px; }
.badge-ok { color: var(--ok); font-weight: 600; }
.badge-no { color: var(--warn); font-weight: 600; }
/* record file and quiz */
.rec-diag { display: flex; flex-wrap: wrap; gap: 12px; }
.rec-diag svg { width: 100%; max-width: 440px; height: auto; display: block; border: 1px solid var(--line); border-radius: 8px; background: var(--panel); }
.rec { max-width: 82ch; }
.rec section { border-top: 1px solid var(--line); padding: 14px 0 6px; }
.rec section h3 { font-size: 15px; display: flex; gap: 10px; align-items: baseline; flex-wrap: wrap; }
.rec section h3 .hint { font: 500 12px var(--f-body); color: var(--muted); }
.rec .tip { font-size: 13px; color: var(--muted); background: var(--panel2); border-radius: 7px; padding: 6px 10px; margin: 6px 0; }
.marks { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin: 10px 0 16px; }
.marks div { background: var(--panel); border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; }
.marks b { display: block; font: 600 22px var(--f-mono); color: var(--accent); }
.marks span { font-size: 12.5px; color: var(--muted); }
.quiz { max-width: 680px; }
.qcard { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 18px; min-height: 200px; display: grid; gap: 12px; align-content: start; }
.qcard .qq { font: 600 18px/1.4 var(--f-display); }
.qcard .qa { background: var(--panel2); border-left: 3px solid var(--accent); border-radius: 6px; padding: 10px 12px; }
.qbar { display: flex; gap: 4px; margin: 10px 0; flex-wrap: wrap; }
.qbar i { width: 18px; height: 6px; border-radius: 3px; background: var(--line); }
.qbar i.ok { background: var(--ok); } .qbar i.no { background: var(--warn); } .qbar i.cur { background: var(--accent); }
.qscore { display: flex; gap: 16px; font-size: 13px; color: var(--muted); flex-wrap: wrap; }
.qscore b { color: var(--ink); font-family: var(--f-mono); }
.stars { font-size: 30px; letter-spacing: 4px; color: var(--sodium); }

.pager { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 34px 0 6px; border-top: 1px solid var(--line); padding-top: 16px; }
.pager .pg { display: grid; gap: 2px; padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; text-decoration: none; color: var(--ink); background: var(--panel); }
.pager .pg:hover { border-color: var(--accent); }
.pager .pg small { color: var(--muted); font: 500 11px var(--f-mono); letter-spacing: .04em; }
.pager .next { text-align: right; }
.cb-wire .plug { stroke: rgba(0,0,0,.5); stroke-width: 1; }
.stack > *, .card > *, .bench > * { min-width: 0; }
/* ---------- record: student details, actions, print ---------- */
.stu-form { display: flex; flex-wrap: wrap; gap: 10px 16px; margin: 14px 0 4px; padding: 12px 14px; background: var(--panel2); border: 1px solid var(--line); border-radius: 10px; }
.stu-form label { display: grid; gap: 4px; font-size: 12.5px; color: var(--muted); font-weight: 600; flex: 1 1 160px; }
.stu-form input { border: 1px solid var(--line); border-radius: 7px; padding: 7px 9px; background: var(--panel); font-size: 14px; color: var(--ink); min-width: 0; }
#recNote { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--panel); color: var(--ink); font: 14px/1.5 var(--f-body); resize: vertical; }
.rec-note { white-space: pre-wrap; }
.rec-actions { display: flex; flex-wrap: wrap; gap: 8px; margin: 18px 0 6px; }
.print-only { display: none; }
.side-user { margin: 0 4px 12px; padding: 10px 10px 8px; border: 1px solid var(--line); border-radius: 10px; background: var(--panel); }
.side-user .su-name { font-weight: 600; font-size: 14px; }
.side-user .su-meta { font-size: 12px; color: var(--muted); margin-top: 1px; }
.side-user .su-links { display: flex; flex-wrap: wrap; gap: 4px 12px; margin-top: 6px; }
.side a.su-link { display: inline; padding: 0; font-size: 12.5px; font-weight: 600; color: var(--accent); background: none; }
.side a.su-link:hover { text-decoration: underline; background: none; }
.side .snd-side { display: none; margin: 22px 8px 0; }
@media (max-width: 979px) { .side .snd-side { display: inline-flex; } }
@media (max-width: 520px) { .top .sw.snd { display: none; } }
@media print {
  :root { color-scheme: light; }
  body { background: #fff !important; }
  .top, .side, .tabs, .pager, footer.foot, .scrim, #sheet, .no-print, .rec-guide, .rec .tip, .rec .hint, .exp-head .eyebrow, .howto { display: none !important; }
  .layout { display: block !important; }
  main { padding: 0 !important; }
  .rec { max-width: none; }
  .rec section { break-inside: avoid-page; }
  .rec #recNote { display: none; }
  .print-only { display: block; border: 1px solid #999; border-radius: 6px; padding: 8px 10px; min-height: 4em; }
  .tscroll { overflow: visible !important; }
  table.obs { font-size: 11px; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
</style>

<style>img{max-width:100%}[hidden]{display:none!important}body{margin:0;background:var(--bg)}</style>
</head>
<body>
<div class="app">
  <header class="top">
    <a class="brand" href="#home" aria-label="Virtual Physics Lab home">
      <span class="brand-mark" aria-hidden="true">
        <i style="left:5px;background:#7a3cff"></i><i style="left:10px;background:#3a5cff"></i><i style="left:17px;background:#3dff6a"></i><i style="left:23px;background:#ffe23a"></i><i style="left:25px;background:#ffd13a"></i><i style="left:31px;background:#ff4a3a;opacity:.6"></i>
      </span>
      <span><b>Virtual Physics Lab</b><small>B.Sc. Second Year · Kamala Science Campus, Sindhuli</small></span>
    </a>
    <div class="top-tools">
      <label class="sw" title="Hide digital readouts and read the vernier scales yourself"><input type="checkbox" id="practiceSw" aria-label="Read scales myself"><span>Read scales <span class="long">myself</span></span></label>
      <label class="sw snd" title="Switch clicks, plug sounds and a pop when a fuse blows"><input type="checkbox" id="soundSw" aria-label="Bench sounds"><span>Sound</span></label>
      <button class="menu-btn" id="menuBtn" type="button" aria-controls="side">Experiments</button>
    </div>
  </header>
  <div class="layout">
    <nav class="side" id="side" aria-label="Experiments"></nav>
    <main id="main"><div class="wrap" id="view"></div></main>
  </div>
</div>
<div class="sheet" id="sheet" hidden></div>
<script>
'use strict';
/* ================= core utilities ================= */
const EXPS = [];
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
function h(tag, a, ...kids) {
  const e = document.createElement(tag);
  if (a) for (const [k, v] of Object.entries(a)) {
    if (v == null || v === false) continue;
    if (k === 'class') e.className = v;
    else if (k === 'html') e.innerHTML = v;
    else if (k === 'text') e.textContent = v;
    else if (k.startsWith('on') && typeof v === 'function') e.addEventListener(k.slice(2), v);
    else e.setAttribute(k, v === true ? '' : v);
  }
  for (const k of kids.flat(3)) if (k != null && k !== false) e.append(k.nodeType ? k : String(k));
  return e;
}
/* when the lab is served from the campus portal, each signed-in user gets their own storage space,
   so two students sharing a lab computer do not see each other's readings */
const VPL_USER = window.VPL_USER || null, VPL_CFG = window.VPL_CFG || {};
const LSP = 'vpl2.' + (VPL_USER && VPL_USER.id != null ? 'u' + String(VPL_USER.id).replace(/[^\w-]/g, '_') + '.' : '');
const LS = {
  get(k, d) { try { const v = localStorage.getItem(LSP + k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } },
  set(k, v) { try { localStorage.setItem(LSP + k, JSON.stringify(v)); } catch (e) { } },
  del(k) { try { localStorage.removeItem(LSP + k); } catch (e) { } }
};
/* short synthesized bench sounds (switch clicks, plugs, a pop when a fuse blows) */
const sfx = (() => {
  let ac = null;
  const ctx = () => { if (!ac) { const C = window.AudioContext || window.webkitAudioContext; if (!C) return null; ac = new C(); } if (ac.state === 'suspended') ac.resume(); return ac; };
  function noise(a, dur, f, q, vol, type = 'bandpass') {
    const n = Math.ceil(a.sampleRate * dur), b = a.createBuffer(1, n, a.sampleRate), d = b.getChannelData(0);
    for (let i = 0; i < n; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / n, 3);
    const s = a.createBufferSource(); s.buffer = b; const fl = a.createBiquadFilter(); fl.type = type; fl.frequency.value = f; fl.Q.value = q;
    const g = a.createGain(); g.gain.value = vol; s.connect(fl); fl.connect(g); g.connect(a.destination); s.start();
  }
  function tone(a, f, dur, vol, type = 'square') {
    const o = a.createOscillator(), g = a.createGain(), t = a.currentTime; o.type = type; o.frequency.value = f;
    g.gain.setValueAtTime(vol, t); g.gain.exponentialRampToValueAtTime(1e-4, t + dur); o.connect(g); g.connect(a.destination); o.start(t); o.stop(t + dur + 0.02);
  }
  return function (kind) {
    if (!LS.get('sound', true)) return;
    try {
      const a = ctx(); if (!a) return;
      if (kind === 'plug') noise(a, 0.03, 3200, 1.2, 0.5);
      else if (kind === 'unplug') noise(a, 0.04, 1800, 1.5, 0.4);
      else if (kind === 'on') { noise(a, 0.025, 2400, 2, 0.6); setTimeout(() => { try { tone(a, 100, 0.18, 0.025, 'sawtooth'); } catch (e) { } }, 30); }
      else if (kind === 'off') noise(a, 0.025, 1600, 2, 0.5);
      else if (kind === 'bang') { noise(a, 0.35, 900, 0.6, 1.2, 'lowpass'); noise(a, 0.06, 4000, 1, 0.6); }
      else if (kind === 'buzz') tone(a, 140, 0.28, 0.05);
      else if (kind === 'tick') noise(a, 0.012, 5000, 3, 0.25);
    } catch (e) { }
  };
})();
const DEG = Math.PI / 180;
const SUBRE = /\b([A-Za-z]{1,3})_([A-Za-z0-9]{1,6})\b/g;
const subHTML = s => String(s).replace(SUBRE, '$1<sub>$2</sub>');
const subSVG = s => String(s).replace(SUBRE, '$1<tspan baseline-shift="sub" font-size="72%">$2</tspan>');
function fillSub(ctx, text, x, y, align) { // canvas text with simple subscripts (X_ab)
  const parts = []; let last = 0; String(text).replace(SUBRE, (m, a, b, i) => { parts.push([text.slice(last, i) + a, 0]); parts.push([b, 1]); last = i + m.length; return m; }); parts.push([text.slice(last), 0]);
  const f0 = ctx.font; const m = /(\d+(?:\.\d+)?)px/.exec(f0); const fs = m ? +m[1] : 12; const fsub = f0.replace(/\d+(?:\.\d+)?px/, (fs * 0.75).toFixed(1) + 'px');
  const w = parts.reduce((t, [s, sub]) => { ctx.font = sub ? fsub : f0; return t + ctx.measureText(s).width; }, 0);
  let cx = align === 'center' ? x - w / 2 : align === 'right' ? x - w : x; const ta = ctx.textAlign; ctx.textAlign = 'left';
  parts.forEach(([s, sub]) => { ctx.font = sub ? fsub : f0; ctx.fillText(s, cx, y + (sub ? fs * 0.28 : 0)); cx += ctx.measureText(s).width; });
  ctx.font = f0; ctx.textAlign = ta;
}
const clamp = (x, a, b) => Math.min(b, Math.max(a, x));
const rnd = (x, d) => { const f = 10 ** d; return Math.round(x * f) / f; };
const mean = a => a.length ? a.reduce((s, x) => s + x, 0) / a.length : NaN;
const norm360 = x => ((x % 360) + 360) % 360;
const angDiff = (a, b) => ((b - a + 540) % 360 + 360) % 360 - 180; // b - a in (-180,180]
const sinc = u => Math.abs(u) < 1e-9 ? 1 : Math.sin(u) / u;
function gauss() { let u = 0, v = 0; while (u === 0) u = Math.random(); while (v === 0) v = Math.random(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v); }
function poisson(m) {
  if (m <= 0) return 0;
  if (m < 40) { const L = Math.exp(-m); let k = 0, p = 1; do { k++; p *= Math.random(); } while (p > L); return k - 1; }
  return Math.max(0, Math.round(m + Math.sqrt(m) * gauss()));
}
function mulberry32(a) { return function () { a |= 0; a = a + 0x6D2B79F5 | 0; let t = Math.imul(a ^ a >>> 15, 1 | a); t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t; return ((t ^ t >>> 14) >>> 0) / 4294967296; }; }
function apparatus(id) {
  let s = LS.get('seed.' + id);
  if (s == null) { s = Math.floor(Math.random() * 2 ** 31); LS.set('seed.' + id, s); }
  const r = mulberry32(s);
  return { u: r, range: (a, b) => a + (b - a) * r(), pick: arr => arr[Math.floor(r() * arr.length)], gauss: () => { let u = r() || 1e-9, v = r(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v); } };
}
function resetApparatus(id) {
  try { Object.keys(localStorage).filter(k => k.startsWith(LSP) && (k.includes('.' + id + '.') || k.endsWith('.' + id))).forEach(k => localStorage.removeItem(k)); } catch (e) { }
}
/* formatting */
const fx = (v, d = 2) => (v == null || !isFinite(v)) ? '—' : (+v).toFixed(d);
function sci(v, d = 3) {
  if (v == null || !isFinite(v) || v === 0) return v === 0 ? '0' : '—';
  const e = Math.floor(Math.log10(Math.abs(v))); const m = v / 10 ** e;
  return `${m.toFixed(d)} × 10<sup>${e}</sup>`;
}
function dm(x) { // reading 0..360 to D° MM′
  x = norm360(x); let d = Math.floor(x), m = Math.round((x - d) * 60);
  if (m === 60) { d = (d + 1) % 360; m = 0; }
  return `${d}° ${String(m).padStart(2, '0')}′`;
}
function dmS(x) { // signed angle
  const s = x < 0 ? '−' : ''; x = Math.abs(x); let d = Math.floor(x), m = Math.round((x - d) * 60);
  if (m === 60) { d++; m = 0; }
  return `${s}${d}° ${String(m).padStart(2, '0')}′`;
}
function parseDM(s) {
  s = String(s).trim().replace(/[°º′'’"]/g, ' ').replace(/,/g, ' ').replace(/\s+/g, ' ').trim();
  if (!s) return NaN;
  const p = s.split(' ').map(Number);
  if (p.some(isNaN)) return NaN;
  if (p.length === 1) return p[0];
  return p[0] + p[1] / 60;
}
const roundMin = x => Math.round(x * 60) / 60;
function theme() {
  const cs = getComputedStyle(document.documentElement);
  const g = n => cs.getPropertyValue('--' + n).trim();
  return { bg: g('bg'), panel: g('panel'), panel2: g('panel2'), ink: g('ink'), muted: g('muted'), line: g('line'), grid: g('grid'), accent: g('accent'), sodium: g('sodium'), ok: g('ok'), warn: g('warn'), bad: g('bad'), field: g('field'), p1: g('plot1'), p2: g('plot2'), p3: g('plot3'), p4: g('plot4'), mono: '"IBM Plex Mono", ui-monospace, monospace', sans: '"IBM Plex Sans", system-ui, sans-serif' };
}
/* wavelength (nm) to rgb 0..1 */
function wl2rgb(l) {
  let r = 0, g = 0, b = 0;
  if (l >= 380 && l < 440) { r = -(l - 440) / 60; b = 1; }
  else if (l >= 440 && l < 490) { g = (l - 440) / 50; b = 1; }
  else if (l >= 490 && l < 510) { g = 1; b = -(l - 510) / 20; }
  else if (l >= 510 && l < 580) { r = (l - 510) / 70; g = 1; }
  else if (l >= 580 && l < 645) { r = 1; g = -(l - 645) / 65; }
  else if (l >= 645 && l <= 780) { r = 1; }
  const f = l < 420 ? 0.3 + 0.7 * (l - 380) / 40 : l > 700 ? 0.3 + 0.7 * (780 - l) / 80 : 1;
  return [r * f, g * f, b * f];
}
/* stats */
function linreg(pts) {
  const n = pts.length; if (n < 2) return null;
  let sx = 0, sy = 0, sxx = 0, sxy = 0, syy = 0;
  for (const [x, y] of pts) { sx += x; sy += y; sxx += x * x; sxy += x * y; syy += y * y; }
  const den = n * sxx - sx * sx; if (Math.abs(den) < 1e-300) return null;
  const m = (n * sxy - sx * sy) / den, c = (sy - m * sx) / n;
  let ss = 0; for (const [x, y] of pts) ss += (y - m * x - c) ** 2;
  const se = n > 2 ? Math.sqrt(ss / (n - 2)) : 0;
  const sm = se * Math.sqrt(n / den), sc = se * Math.sqrt(sxx / den);
  const r = (n * sxy - sx * sy) / Math.sqrt(den * (n * syy - sy * sy) || 1);
  return { m, c, sm, sc, r };
}
function linregOrigin(pts) {
  let sxx = 0, sxy = 0; for (const [x, y] of pts) { sxx += x * x; sxy += x * y; }
  if (!sxx) return null; const m = sxy / sxx; return { m, c: 0 };
}
/* regularized upper incomplete gamma Q(a,x) — for chi-square p-values */
function lgamma(z) { const g = 7, c = [0.99999999999980993, 676.5203681218851, -1259.1392167224028, 771.32342877765313, -176.61502916214059, 12.507343278686905, -0.13857109526572012, 9.9843695780195716e-6, 1.5056327351493116e-7]; if (z < 0.5) return Math.log(Math.PI / Math.abs(Math.sin(Math.PI * z))) - lgamma(1 - z); z -= 1; let x = c[0]; for (let i = 1; i < g + 2; i++) x += c[i] / (z + i); const t = z + g + 0.5; return 0.5 * Math.log(2 * Math.PI) + (z + 0.5) * Math.log(t) - t + Math.log(x); }
function gammaQ(a, x) {
  if (x <= 0) return 1;
  if (x < a + 1) { let sum = 1 / a, del = sum, ap = a; for (let n = 0; n < 500; n++) { ap++; del *= x / ap; sum += del; if (Math.abs(del) < Math.abs(sum) * 1e-14) break; } return 1 - sum * Math.exp(-x + a * Math.log(x) - lgamma(a)); }
  let b = x + 1 - a, c = 1e300, d = 1 / b, hh = d;
  for (let i = 1; i < 500; i++) { const an = -i * (i - a); b += 2; d = an * d + b; if (Math.abs(d) < 1e-300) d = 1e-300; c = b + an / c; if (Math.abs(c) < 1e-300) c = 1e-300; d = 1 / d; const del = d * c; hh *= del; if (Math.abs(del - 1) < 1e-14) break; }
  return Math.exp(-x + a * Math.log(x) - lgamma(a)) * hh;
}
const chi2P = (chi, dof) => gammaQ(dof / 2, chi / 2); // P(χ² ≥ chi)

/* ================= canvases ================= */
const LIVE = { canvases: new Set(), cleanups: [] };
function canvasView(parent, o) {
  const wrap = h('div', { class: 'cv' }); const c = h('canvas'); wrap.append(c); parent.append(wrap);
  if (o.label) wrap.prepend(h('div', { class: 'cv-label' }, o.label));
  if (o.role) c.setAttribute('role', 'img');
  if (o.alt) c.setAttribute('aria-label', o.alt);
  const ctx = c.getContext('2d'); let W = 0, H = 0, pending = false;
  const view = { canvas: c, wrap, ctx, get w() { return W; }, get h() { return H; }, redraw, now };
  function size() {
    const r = c.getBoundingClientRect(); W = Math.max(160, Math.round(r.width || wrap.clientWidth || 300));
    H = Math.round(o.height ? o.height(W) : W * (o.aspect || 0.6));
    if (o.maxH) H = Math.min(H, o.maxH);
    if (o.minH) H = Math.max(H, o.minH);
    const dpr = Math.min(2, window.devicePixelRatio || 1);
    c.width = Math.round(W * dpr); c.height = Math.round(H * dpr); c.style.height = H + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }
  function now() { if (!W) size(); o.draw(ctx, W, H, view); }
  function redraw() { if (pending) return; pending = true; requestAnimationFrame(() => { pending = false; now(); }); }
  const ro = new ResizeObserver(() => { const r = c.getBoundingClientRect(); if (Math.round(r.width) !== W) { size(); o.draw(ctx, W, H, view); } });
  ro.observe(wrap);
  view.destroy = () => ro.disconnect();
  view.resize = () => { size(); o.draw(ctx, W, H, view); };
  LIVE.canvases.add(view);
  requestAnimationFrame(() => { size(); o.draw(ctx, W, H, view); });
  return view;
}
function onCleanup(fn) { LIVE.cleanups.push(fn); }
function teardown() {
  LIVE.canvases.forEach(v => v.destroy()); LIVE.canvases.clear();
  LIVE.cleanups.splice(0).forEach(f => { try { f(); } catch (e) { } });
  closeSheet();
}
function loop(fn) { // animation loop with dt (s); returns stop fn
  let last = performance.now(), id = 0, alive = true;
  const step = t => { if (!alive) return; const dt = Math.min(0.25, (t - last) / 1000); last = t; fn(dt); id = requestAnimationFrame(step); };
  id = requestAnimationFrame(step);
  const stop = () => { alive = false; cancelAnimationFrame(id); };
  onCleanup(stop); return stop;
}
const redrawAll = () => LIVE.canvases.forEach(v => v.redraw());
try { matchMedia('(prefers-color-scheme: dark)').addEventListener('change', redrawAll); } catch (e) { }
new MutationObserver(redrawAll).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

/* ================= controls ================= */
let ctlSeq = 0;
function holdRepeat(btn, fn) {
  let t1 = 0, t2 = 0;
  const stop = () => { clearTimeout(t1); clearInterval(t2); };
  btn.addEventListener('pointerdown', e => { e.preventDefault(); fn(); t1 = setTimeout(() => { t2 = setInterval(fn, 70); }, 380); });
  ['pointerup', 'pointerleave', 'pointercancel'].forEach(ev => btn.addEventListener(ev, stop));
  btn.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fn(); } });
}
function slider(o) {
  const id = o.id || ('c' + (++ctlSeq));
  const val = h('output', { class: 'ctl-val', for: id });
  const toS = o.toS || (x => x), fromS = o.fromS || (x => x);
  const inp = h('input', { type: 'range', id, min: o.smin ?? o.min, max: o.smax ?? o.max, step: o.sstep ?? o.step ?? 'any', value: toS(o.value) });
  let v = o.value, ready = false;
  function set(x, src) {
    x = clamp(x, o.min, o.max); if (o.snap) x = Math.round(x / o.snap) * o.snap; x = +x.toFixed(9);
    v = x; if (src !== 'range') inp.value = toS(x);
    val.innerHTML = o.fmt ? o.fmt(x) : x;
    if (ready && o.onInput) o.onInput(x, src);
  }
  inp.addEventListener('input', () => set(fromS(parseFloat(inp.value)), 'range'));
  const fine = h('div', { class: 'fine' });
  (o.fine || []).forEach(([t, d]) => { const b = h('button', { type: 'button', class: 'fbtn', 'aria-label': `${o.label} ${t}` }, t); holdRepeat(b, () => set(v + d, 'fine')); fine.append(b); });
  const el = h('div', { class: 'ctl' }, h('div', { class: 'ctl-top' }, h('label', { for: id, html: subHTML(o.label) }), val), inp, o.fine ? fine : null);
  set(o.value, 'init'); ready = true;
  return { el, get: () => v, set, input: inp, show: s => { val.hidden = !s; } };
}
function selectBox(o) {
  const id = o.id || ('c' + (++ctlSeq));
  const s = h('select', { id });
  const add = (p, [v, t]) => p.append(h('option', { value: v }, t));
  (o.options || []).forEach(op => {
    if (op.group) { const g = h('optgroup', { label: op.group }); op.items.forEach(it => add(g, it)); s.append(g); }
    else add(s, op);
  });
  if (o.value != null) s.value = o.value;
  s.addEventListener('change', () => o.onChange && o.onChange(s.value));
  const el = o.label ? h('label', { class: 'fld', for: id }, h('span', { html: subHTML(o.label) }), s) : s;
  return { el, sel: s, get: () => s.value, set: v => { s.value = v; } };
}
function seg(o) {
  let v = o.value; const el = h('div', { class: 'seg', role: 'group', 'aria-label': o.label || '' });
  const btns = o.options.map(([val, t]) => { const b = h('button', { type: 'button', 'aria-pressed': String(val === v) }, t); b.addEventListener('click', () => set(val, true)); el.append(b); return [val, b]; });
  function set(x, fire) { v = x; btns.forEach(([val, b]) => b.setAttribute('aria-pressed', String(val === x))); if (fire && o.onChange) o.onChange(x); }
  const wrap = o.label ? h('div', { class: 'fld' }, h('span', { html: subHTML(o.label) }), el) : el;
  return { el: wrap, get: () => v, set };
}
function check(o) {
  const id = o.id || ('c' + (++ctlSeq));
  const i = h('input', { type: 'checkbox', id }); i.checked = !!o.value;
  i.addEventListener('change', () => o.onChange && o.onChange(i.checked));
  return { el: h('label', { class: 'chk', for: id }, i, o.label), get: () => i.checked, set: x => { i.checked = !!x; } };
}
function statusBox() {
  const el = h('div', { class: 'status', role: 'status', 'aria-live': 'polite' });
  return { el, say(msg, kind = '') { el.className = 'status ' + kind; el.innerHTML = subHTML(msg); } };
}
function readout(label) {
  const b = h('b', null, '—'); const el = h('div', { class: 'ro' }, h('small', { html: subHTML(label) }), b);
  return { el, set(txt, hide) { el.classList.toggle('hid', !!hide); b.innerHTML = hide ? '<span>hidden</span>' : txt; } };
}
function card(title, sub, ...kids) {
  return h('section', { class: 'card' }, h('div', { class: 'card-h' }, h('h3', { html: subHTML(title) }), sub ? h('span', { class: 'sub', html: subHTML(sub) }) : null), ...kids);
}

/* ================= practice mode & reading sheet ================= */
const Practice = {
  on: LS.get('practice', false),
  subs: new Set(),
  set(v) { this.on = v; LS.set('practice', v); this.subs.forEach(f => f(v)); }
};
let sheetResolve = null;
function closeSheet(val) { const s = $('#sheet'); if (s) { s.hidden = true; s.innerHTML = ''; } if (sheetResolve) { const r = sheetResolve; sheetResolve = null; r(val ?? null); } }
/* ask the student to read an instrument; resolves to the accepted value or null */
function askReading(o) {
  if (!Practice.on) return Promise.resolve(o.truth);
  closeSheet();
  return new Promise(res => {
    sheetResolve = res;
    const s = $('#sheet'); s.innerHTML = '';
    const inp = h('input', { type: 'text', inputmode: 'decimal', autocomplete: 'off', placeholder: o.placeholder || '', 'aria-label': o.title });
    const fb = h('div', { class: 'fb', role: 'status' });
    let fails = 0;
    const accept = v => { const r = sheetResolve; sheetResolve = null; s.hidden = true; s.innerHTML = ''; r && r(v); };
    const check = () => {
      const v = (o.parse || parseFloat)(inp.value);
      if (!isFinite(v)) { fb.className = 'fb no'; fb.textContent = 'Enter a number' + (o.unit ? ` in ${o.unit}` : '') + '.'; return; }
      const err = o.diff ? Math.abs(o.diff(v, o.truth)) : Math.abs(v - o.truth);
      if (err <= o.tol) { fb.className = 'fb ok'; fb.textContent = 'Correct reading. Recorded.'; setTimeout(() => accept(o.truth), 450); }
      else { fails++; fb.className = 'fb no'; fb.innerHTML = 'Not quite. ' + (fails >= 1 && o.hint ? o.hint : 'Look at the scale again.'); useBtn.hidden = false; }
    };
    const useBtn = h('button', { type: 'button', class: 'btn', onclick: () => accept(o.truth) }, 'Use the correct value');
    useBtn.hidden = true;
    s.append(h('h4', null, o.title), h('p', { html: o.prompt || '' }),
      h('form', { class: 'row', onsubmit: e => { e.preventDefault(); check(); } }, inp, o.unit ? h('span', { class: 'muted' }, o.unit) : null,
        h('button', { class: 'btn pri', type: 'submit' }, 'Check'), useBtn,
        h('button', { class: 'btn', type: 'button', onclick: () => closeSheet(null) }, 'Cancel')), fb);
    s.hidden = false; setTimeout(() => inp.focus(), 30);
  });
}
/* hint builders */
function vernierHint(val, msd, lc, unitFmt) {
  const ms = Math.floor(val / msd + 1e-9) * msd; const vs = Math.round((val - ms) / lc);
  return `Main scale reading = ${unitFmt(ms)}; vernier division ${vs} coincides, so reading = ${unitFmt(ms)} + ${vs} × L.C. = <b>${unitFmt(val)}</b>.`;
}

/* ================= vernier drawing ================= */
/* draws a magnified main scale + vernier; value = reading at vernier zero */
function drawVernier(ctx, W, H, value, c) {
  const T = theme();
  const msd = c.msd, n = c.n, span = c.span || (n + 6);
  ctx.clearRect(0, 0, W, H);
  ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
  const k = (W - 24) / (span * msd); // px per unit
  const x0 = 12 + 3 * msd * k; // screen x of vernier zero
  const mid = Math.round(H * 0.52);
  // main scale band
  ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, mid);
  ctx.strokeStyle = T.line; ctx.beginPath(); ctx.moveTo(0, mid + .5); ctx.lineTo(W, mid + .5); ctx.stroke();
  const first = Math.floor((value - (x0 / k)) / msd) - 1, last = first + span + 4;
  ctx.fillStyle = T.ink; ctx.strokeStyle = T.ink; ctx.lineWidth = 1;
  ctx.font = `11px ${T.mono}`; ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic';
  const minPx = c.labelPx || 30; let labEvery = c.major * 100;
  for (const m of [1, 2, 5, 10, 20, 50]) if (c.major * m * msd * k >= minPx) { labEvery = c.major * m; break; }
  const isMul = (i, m) => Math.abs(Math.round(i / m) * m - i) < 1e-6;
  for (let i = first; i <= last; i++) {
    const v = i * msd, x = x0 + (v - value) * k;
    if (x < 2 || x > W - 2) continue;
    const big = isMul(i, c.major);
    const medium = c.medium && isMul(i, c.medium);
    const len = big ? mid * 0.5 : medium ? mid * 0.36 : mid * 0.24;
    ctx.beginPath(); ctx.moveTo(Math.round(x) + .5, mid); ctx.lineTo(Math.round(x) + .5, mid - len); ctx.stroke();
    if (big && isMul(i, labEvery)) ctx.fillText(c.fmt(v), x, mid - len - 4);
  }
  // vernier
  ctx.fillStyle = T.accent;
  ctx.globalAlpha = 0.08; ctx.fillRect(x0 - 6, mid, (n - 1) * msd * k + 12, H - mid); ctx.globalAlpha = 1;
  const vstep = (n - 1) / n * msd * k;
  let best = -1, bestErr = 1e9;
  const lc = msd / n;
  const frac = (value - Math.floor(value / msd + 1e-9) * msd) / lc; best = Math.round(frac) % (n + 1); bestErr = 0;
  for (let j = 0; j <= n; j++) {
    const x = x0 + j * vstep; if (x > W - 2) break;
    const lab = j % (c.vlabel || 5) === 0;
    const len = lab ? (H - mid) * 0.36 : (H - mid) * 0.22;
    const hi = !Practice.on && j === best;
    ctx.strokeStyle = hi ? T.sodium : T.accent; ctx.lineWidth = hi ? 2 : 1;
    ctx.beginPath(); ctx.moveTo(Math.round(x) + .5, mid); ctx.lineTo(Math.round(x) + .5, mid + len); ctx.stroke();
    if (lab) { ctx.fillStyle = T.accent; ctx.textBaseline = 'top'; ctx.fillText(String(c.vfmt ? c.vfmt(j) : j), x, mid + len + 3); ctx.textBaseline = 'alphabetic'; }
  }
  ctx.lineWidth = 1;
}

/* ================= plotting ================= */
function niceStep(span, n) { const s0 = span / n; const mag = 10 ** Math.floor(Math.log10(s0)); const e = s0 / mag; return (e >= 7.5 ? 10 : e >= 3.5 ? 5 : e >= 1.5 ? 2 : 1) * mag; }
function decs(step) { return Math.max(0, -Math.floor(Math.log10(step) + 1e-9)); }
function drawPlot(ctx, W, H, o) {
  const T = theme();
  ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, H);
  const L = 60, R = 16, Tp = 14, B = 44, pw = W - L - R, ph = H - Tp - B;
  const xs = [], ys = [];
  (o.series || []).forEach(s => s.pts.forEach(p => { if (isFinite(p[0]) && isFinite(p[1])) { xs.push(p[0]); ys.push(p[1]); } }));
  (o.extraX || []).forEach(v => xs.push(v)); (o.extraY || []).forEach(v => ys.push(v));
  ctx.font = `11px ${T.mono}`;
  if (!xs.length || !(o.series || []).some(s => s.pts.length)) {
    ctx.strokeStyle = T.line; ctx.strokeRect(L + .5, Tp + .5, pw, ph);
    ctx.fillStyle = T.muted; ctx.textAlign = 'center'; ctx.font = `13px ${T.sans}`;
    ctx.fillText(o.empty || 'Record readings to see the graph', L + pw / 2, Tp + ph / 2);
    axisTitles(); return;
  }
  let x0 = Math.min(...xs), x1 = Math.max(...xs), y0 = Math.min(...ys), y1 = Math.max(...ys);
  if (o.zeroX) { x0 = Math.min(0, x0); x1 = Math.max(0, x1); }
  if (o.zeroY) { y0 = Math.min(0, y0); y1 = Math.max(0, y1); }
  if (x1 - x0 < 1e-12) { const d = Math.abs(x0) * 0.1 || 1; x0 -= d; x1 += d; }
  if (y1 - y0 < 1e-12) { const d = Math.abs(y0) * 0.1 || 1; y0 -= d; y1 += d; }
  const px = (x1 - x0) * 0.04, py = (y1 - y0) * 0.06;
  if (!(o.zeroX && x0 === 0)) x0 -= px; x1 += px;
  if (!(o.zeroY && y0 === 0)) y0 -= py; y1 += py;
  const sx = niceStep(x1 - x0, Math.max(3, Math.round(pw / 85))), sy = niceStep(y1 - y0, Math.max(3, Math.round(ph / 48)));
  x0 = Math.floor(x0 / sx + 1e-9) * sx; x1 = Math.ceil(x1 / sx - 1e-9) * sx; y0 = Math.floor(y0 / sy + 1e-9) * sy; y1 = Math.ceil(y1 / sy - 1e-9) * sy;
  const X = v => L + (v - x0) / (x1 - x0) * pw, Y = v => Tp + ph - (v - y0) / (y1 - y0) * ph;
  ctx.lineWidth = 1;
  ctx.strokeStyle = T.grid; ctx.fillStyle = T.muted; ctx.textAlign = 'center'; ctx.textBaseline = 'top';
  const dx = o.xDec ?? decs(sx), dy = o.yDec ?? decs(sy);
  for (let v = x0; v <= x1 + sx * 1e-6; v += sx) { const x = Math.round(X(v)) + .5; ctx.beginPath(); ctx.moveTo(x, Tp); ctx.lineTo(x, Tp + ph); ctx.stroke(); ctx.fillText((+v.toFixed(10)).toFixed(dx), x, Tp + ph + 5); }
  ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
  for (let v = y0; v <= y1 + sy * 1e-6; v += sy) { const y = Math.round(Y(v)) + .5; ctx.beginPath(); ctx.moveTo(L, y); ctx.lineTo(L + pw, y); ctx.stroke(); ctx.fillText((+v.toFixed(10)).toFixed(dy), L - 6, y); }
  ctx.strokeStyle = T.muted; ctx.strokeRect(L + .5, Tp + .5, pw, ph);
  ctx.save(); ctx.beginPath(); ctx.rect(L, Tp, pw, ph); ctx.clip();
  (o.hlines || []).forEach(l => { ctx.strokeStyle = l.color || T.muted; ctx.setLineDash([4, 4]); const y = Y(l.y); ctx.beginPath(); ctx.moveTo(L, y); ctx.lineTo(L + pw, y); ctx.stroke(); ctx.setLineDash([]); if (l.label) { ctx.fillStyle = l.color || T.muted; ctx.textAlign = 'right'; ctx.textBaseline = 'bottom'; ctx.font = `10.5px ${T.sans}`; fillSub(ctx, l.label, L + pw - 4, y - 2, 'right'); } });
  (o.vlines || []).forEach(l => { ctx.strokeStyle = l.color || T.muted; ctx.setLineDash([4, 4]); const x = X(l.x); ctx.beginPath(); ctx.moveTo(x, Tp); ctx.lineTo(x, Tp + ph); ctx.stroke(); ctx.setLineDash([]); if (l.label) { ctx.fillStyle = l.color || T.muted; ctx.textAlign = 'left'; ctx.textBaseline = 'top'; ctx.font = `10.5px ${T.sans}`; fillSub(ctx, l.label, x + 4, Tp + 4 + (l.dy || 0), 'left'); } });
  (o.series || []).forEach((s, si) => {
    const col = s.color || [T.p1, T.p2, T.p3, T.p4][si % 4];
    if (s.fit) { ctx.strokeStyle = col; ctx.lineWidth = 1.5; ctx.globalAlpha = .8; ctx.beginPath(); const fx0 = s.fitFrom ?? x0, fx1 = s.fitTo ?? x1; ctx.moveTo(X(fx0), Y(s.fit.m * fx0 + s.fit.c)); ctx.lineTo(X(fx1), Y(s.fit.m * fx1 + s.fit.c)); ctx.stroke(); ctx.globalAlpha = 1; }
    if (s.curve) { ctx.strokeStyle = col; ctx.lineWidth = 1.5; ctx.beginPath(); s.curve.forEach((p, i) => i ? ctx.lineTo(X(p[0]), Y(p[1])) : ctx.moveTo(X(p[0]), Y(p[1]))); ctx.stroke(); }
    if (s.join) { const p = [...s.pts].sort((a, b) => a[0] - b[0]); ctx.strokeStyle = col; ctx.lineWidth = 1.5; ctx.beginPath(); p.forEach((q, i) => i ? ctx.lineTo(X(q[0]), Y(q[1])) : ctx.moveTo(X(q[0]), Y(q[1]))); ctx.stroke(); }
    ctx.lineWidth = 1;
    s.pts.forEach(p => { ctx.fillStyle = col; ctx.strokeStyle = T.panel; ctx.beginPath(); ctx.arc(X(p[0]), Y(p[1]), s.r || 3.6, 0, 7); ctx.fill(); ctx.stroke(); });
  });
  ctx.restore();
  if ((o.series || []).filter(s => s.label).length > 1) { // legend
    ctx.font = `11px ${T.sans}`; ctx.textAlign = 'left'; ctx.textBaseline = 'middle'; let lx = L + 10, ly = Tp + 12;
    (o.series || []).forEach((s, si) => { if (!s.label) return; const col = s.color || [T.p1, T.p2, T.p3, T.p4][si % 4]; ctx.fillStyle = col; ctx.beginPath(); ctx.arc(lx + 4, ly, 4, 0, 7); ctx.fill(); ctx.fillStyle = T.ink; fillSub(ctx, s.label, lx + 12, ly, 'left'); ly += 16; });
  }
  axisTitles();
  function axisTitles() {
    ctx.fillStyle = T.ink; ctx.font = `12px ${T.sans}`; ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
    fillSub(ctx, o.xLabel || '', L + pw / 2, H - 6, 'center');
    ctx.save(); ctx.translate(13, Tp + ph / 2); ctx.rotate(-Math.PI / 2); ctx.textBaseline = 'middle'; fillSub(ctx, o.yLabel || '', 0, 0, 'center'); ctx.restore();
  }
}

/* ================= observation table ================= */
class ObsTable {
  constructor(o) {
    this.o = o; this.key = 'tbl.' + o.key;
    this.rows = LS.get(this.key, null) || (o.template ? o.template() : []);
    this.flash = -1;
    this.body = h('div', { class: 'tscroll' });
    let armed = 0;
    const clr = h('button', { type: 'button', class: 'btn sm' }, 'Clear');
    clr.addEventListener('click', () => {
      if (!armed) { armed = 1; clr.textContent = 'Click again to clear'; setTimeout(() => { armed = 0; clr.textContent = 'Clear'; }, 2600); return; }
      armed = 0; clr.textContent = 'Clear'; this.clear();
    });
    const cp = h('button', { type: 'button', class: 'btn sm' }, 'Copy');
    cp.addEventListener('click', () => {
      const txt = this.tsv();
      const done = ok => { cp.textContent = ok ? 'Copied' : 'Select & copy below'; setTimeout(() => cp.textContent = 'Copy', 1800); };
      try { navigator.clipboard.writeText(txt).then(() => done(true), () => { this.fallback(txt); done(false); }); } catch (e) { this.fallback(txt); done(false); }
    });
    this.fb = h('textarea', { readonly: true, rows: 4, style: 'width:100%;margin-top:6px;font:12px var(--f-mono)', 'aria-label': 'Table as text' }); this.fb.hidden = true;
    this.el = h('div', { class: 'obs-wrap' }, h('div', { class: 'tblbar' }, h('h4', { html: o.title || 'Observations' }), h('div', { class: 'tools' }, cp, clr)), this.body, this.fb);
    this.render();
  }
  fallback(t) { this.fb.hidden = false; this.fb.value = t; this.fb.focus(); this.fb.select(); }
  save() { LS.set(this.key, this.rows); }
  add(r) { this.rows.push(r); this.flash = this.rows.length - 1; this.changed(); return this.rows.length - 1; }
  update(i, patch) { Object.assign(this.rows[i], patch); this.flash = i; this.changed(); }
  remove(i) { this.rows.splice(i, 1); this.flash = -1; this.changed(); }
  clear() { this.rows = this.o.template ? this.o.template() : []; this.flash = -1; this.changed(); }
  replaceAll(rows) { this.rows = rows; this.changed(); }
  changed() { this.save(); this.render(); this.o.onChange && this.o.onChange(this.rows); }
  val(c, r, i) { return c.v ? c.v(r, i, this.rows) : r[c.k]; }
  render() {
    const cols = this.o.columns;
    const head = h('tr', null, cols.map(c => h('th', { html: c.label })), this.o.deletable ? h('th', { 'aria-label': 'Delete' }) : null);
    const order = this.o.sort ? this.rows.map((r, i) => i).sort((a, b) => this.o.sort(this.rows[a], this.rows[b])) : this.rows.map((r, i) => i);
    const trs = order.map(i => {
      const r = this.rows[i];
      const tds = cols.map(c => {
        const v = this.val(c, r, i);
        const empty = v == null || v === '' || (typeof v === 'number' && !isFinite(v));
        const txt = empty ? '·' : (c.fmt ? c.fmt(v, r) : v);
        return h('td', { class: (c.cls || '') + (empty ? ' empty' : '') + (c.calc ? ' calc' : ''), html: String(txt) });
      });
      const tr = h('tr', { class: i === this.flash ? 'flash' : null }, tds,
        this.o.deletable ? h('td', null, h('button', { type: 'button', class: 'x', 'aria-label': 'Delete row', title: 'Delete row', onclick: () => this.remove(i) }, '×')) : null);
      return tr;
    });
    if (!trs.length) trs.push(h('tr', null, h('td', { colspan: cols.length + (this.o.deletable ? 1 : 0), class: 'lbl muted', style: 'padding:14px;text-align:center' }, this.o.emptyText || 'No readings yet. Use the Record button on the bench.')));
    this.body.innerHTML = '';
    this.body.append(h('table', { class: 'obs' }, h('thead', null, head), h('tbody', null, trs)));
    this.flash = -1;
    try { LS.set('snap.' + this.o.key, { title: String(this.o.title || 'Observations').replace(/<[^>]+>/g, ''), tsv: this.tsv() }); } catch (e) { }
  }
  tsv() {
    const cols = this.o.columns; const strip = s => String(s).replace(/<sup>(.*?)<\/sup>/g, '^$1').replace(/<sub>(.*?)<\/sub>/g, '_$1').replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ');
    const lines = [cols.map(c => strip(c.label)).join('\t')];
    this.rows.forEach((r, i) => lines.push(cols.map(c => { const v = this.val(c, r, i); return (v == null || (typeof v === 'number' && !isFinite(v))) ? '' : strip(c.fmt ? c.fmt(v, r) : v); }).join('\t')));
    return lines.join('\n');
  }
}
function resultBox() {
  const el = h('div', { class: 'result' }); const exp = typeof CUR_EXP !== 'undefined' ? CUR_EXP : null;
  return { el, set(html) { el.innerHTML = html; if (exp && !/Record |record |Complete steps|Take at least|need at least/.test(html.slice(0, 400)) ) LS.set('snapres.' + exp, htmlToText(html)); } };
}
function pctErr(exp, std) { return Math.abs(exp - std) / Math.abs(std) * 100; }

/* ================= spectrometer engine (experiments 2–6) ================= */
const SOURCES = {
  hg: {
    name: 'Mercury vapour lamp', tint: '#cfd8ff', lines: [
      { id: 'violet', name: 'Violet', l: 404.66, I: 0.55 },
      { id: 'blue', name: 'Blue', l: 435.83, I: 0.9 },
      { id: 'bgreen', name: 'Bluish-green', l: 491.60, I: 0.22 },
      { id: 'green', name: 'Green', l: 546.07, I: 1.0 },
      { id: 'y1', name: 'Yellow-1', l: 576.96, I: 0.75 },
      { id: 'y2', name: 'Yellow-2', l: 579.07, I: 0.72 },
      { id: 'red', name: 'Red', l: 623.44, I: 0.2 }]
  },
  na: {
    name: 'Sodium vapour lamp', tint: '#ffcf5a', lines: [
      { id: 'd2', name: 'D₂', l: 588.995, I: 1.0 },
      { id: 'd1', name: 'D₁', l: 589.592, I: 0.8 }]
  }
};
const HG_MAIN = ['violet', 'blue', 'bgreen', 'green', 'y1', 'y2', 'red'];
const lineOf = (src, id) => SOURCES[src].lines.find(l => l.id === id);
const F_COLL = 178; // collimator focal length, mm
const FIELD = 1.25; // telescope half-field at ×1, degrees

function spectrometer(cfg) {
  const R = cfg.rng;
  const C0 = R.range(0, 360), C1 = R.range(0, 360), phiE = R.range(0, 360), eps = 1.5 / 60;
  const def = {
    tel: R.range(-30, 30), inc: cfg.kind === 'prism' ? R.range(30, 45) : R.range(9, 24) * (R.u() < 0.5 ? -1 : 1),
    tilt: R.range(-5, 5), pmode: 'refract', removed: false, source: cfg.sources[0], zoom: 1, slit: 0.10,
    ap: cfg.aperture ? cfg.aperture.init : null, trace: !!cfg.trace
  };
  const st = Object.assign(def, LS.get('spec.' + cfg.id, {}));
  if (!cfg.sources.includes(st.source)) st.source = cfg.sources[0];
  let saveT = 0;
  const save = () => { clearTimeout(saveT); saveT = setTimeout(() => LS.set('spec.' + cfg.id, st), 250); };
  const Wbeam = () => cfg.aperture ? st.ap : 20;
  const F = () => FIELD / st.zoom;
  const gEff = () => (((st.inc + 90) % 180) + 180) % 180 - 90;

  function muOf(l) { return cfg.prism.Ac + cfg.prism.B / (l * l); }
  function minDev(l) { const A = cfg.prism.A, mu = muOf(l); return 2 * Math.asin(mu * Math.sin(A / 2 * DEG)) / DEG - A; }

  function rays() {
    const src = SOURCES[st.source]; const out = [];
    if (st.removed) { src.lines.forEach(L => out.push({ dir: 0, l: L.l, I: L.I, line: L.id, kind: 'direct', Wout: Wbeam() })); return out; }
    if (cfg.kind === 'prism') {
      const A = cfg.prism.A;
      if (st.pmode === 'angle') {
        src.lines.forEach(L => {
          out.push({ dir: A + 2 * st.tilt, l: L.l, I: L.I * 0.45, line: L.id, kind: 'reflL', Wout: 10 });
          out.push({ dir: -A + 2 * st.tilt, l: L.l, I: L.I * 0.45, line: L.id, kind: 'reflR', Wout: 10 });
        });
        return out;
      }
      const i1 = st.inc;
      if (i1 <= 0.2 || i1 >= 89.8) return out;
      src.lines.forEach(L => out.push({ dir: 180 - 2 * i1, l: L.l, I: L.I * 0.12, line: L.id, kind: 'refl', Wout: 10 }));
      src.lines.forEach(L => {
        const mu = muOf(L.l);
        const r1 = Math.asin(Math.sin(i1 * DEG) / mu) / DEG, r2 = A - r1;
        const se = mu * Math.sin(r2 * DEG); if (Math.abs(se) >= 1) return;
        const e = Math.asin(se) / DEG, dev = i1 + e - A;
        out.push({ dir: -dev, l: L.l, I: L.I, line: L.id, kind: 'ref', dev, mu, e, Wout: Wbeam() * Math.cos(e * DEG) / Math.cos(i1 * DEG) });
      });
    } else {
      const d = 2.54e7 / cfg.grating.lpi, g = gEff();
      src.lines.forEach(L => {
        for (let m = -3; m <= 3; m++) {
          const s = Math.sin(g * DEG) + m * L.l / d; if (Math.abs(s) >= 1) continue;
          const th = Math.asin(s) / DEG;
          out.push({ dir: th - g, l: L.l, I: L.I * [0.6, 0.85, 0.32, 0.1][Math.abs(m)], line: L.id, kind: 'diff', m, Wout: Wbeam() * Math.cos(th * DEG) / Math.cos(g * DEG) });
        }
        out.push({ dir: 180 - 2 * g, l: L.l, I: L.I * 0.15, line: L.id, kind: 'refl', Wout: 10 });
      });
    }
    return out;
  }
  const inView = tol => rays().map(r => ({ ...r, off: angDiff(st.tel, r.dir) })).filter(r => Math.abs(r.off) <= tol).sort((a, b) => Math.abs(a.off) - Math.abs(b.off));
  function verniers() {
    const ecc = eps * Math.sin((st.tel + phiE) * DEG);
    return { v1: norm360(C0 + st.tel + ecc), v2: norm360(C0 + st.tel + 180 - ecc) };
  }
  const tableReading = () => norm360(C1 + (cfg.kind === 'grating' ? -st.inc : -st.inc));

  /* ---------- UI ---------- */
  const el = h('div', { class: 'stack' });
  const opts = h('div', { class: 'opts' });
  if (cfg.sources.length > 1) opts.append(selectBox({ label: 'Lamp', options: cfg.sources.map(s => [s, SOURCES[s].name]), value: st.source, onChange: v => { st.source = v; changed(); cfg.onSource && cfg.onSource(v); } }).el);
  else opts.append(h('span', { class: 'pill' }, SOURCES[st.source].name));
  if (cfg.kind === 'prism' && cfg.angleMode) opts.append(seg({ label: 'Prism position', value: st.pmode, options: [['angle', 'Apex to collimator (angle A)'], ['refract', 'For refraction']], onChange: v => { st.pmode = v; syncTbl(); changed(); } }).el);
  opts.append(check({ label: cfg.kind === 'prism' ? 'Remove prism (direct reading)' : 'Remove grating (direct reading)', value: st.removed, onChange: v => { st.removed = v; changed(); } }).el);
  el.append(opts);

  const top = canvasView(el, { label: 'Spectrometer, top view — drag the telescope arm or the table', aspect: 0.62, maxH: 430, draw: drawTop, alt: 'Top view of spectrometer' });
  const row = h('div', { class: 'cols2' });
  el.append(row);
  const eyeBox = h('div');
  const eye = canvasView(eyeBox, { label: 'Through the telescope', height: w => Math.min(w, 300) + (st.trace ? 70 : 0), draw: drawEye, alt: 'Telescope field of view' });
  const eyeOpts = h('div', { class: 'opts', style: 'margin-top:8px' });
  if (cfg.zooms) eyeOpts.append(seg({ label: 'Eyepiece', value: st.zoom, options: cfg.zooms, onChange: v => { st.zoom = v; changed(); } }).el);
  if (cfg.trace) eyeOpts.append(check({ label: 'Intensity trace', value: st.trace, onChange: v => { st.trace = v; eye.resize(); changed(); } }).el);
  eyeBox.append(eyeOpts);
  const eyeView = eye;
  const vBox = h('div', { class: 'stack' });
  const vc = { msd: 0.5, n: 30, major: 2, fmt: v => String(Math.round(norm360(v))), vlabel: 5, lcText: "1′", span: 36 };
  const vern1 = canvasView(vBox, { label: 'Vernier I · main scale 0.5°, 30 vernier div, L.C. 1′', aspect: 0.24, minH: 84, maxH: 96, draw: (c, w, hh) => drawVernier(c, w, hh, verniers().v1, Object.assign({ title: 'main scale (0.5°) / vernier (30 div)' }, vc)) });
  const vern2 = canvasView(vBox, { label: 'Vernier II', aspect: 0.24, minH: 84, maxH: 96, draw: (c, w, hh) => drawVernier(c, w, hh, verniers().v2, Object.assign({ title: 'main scale (0.5°) / vernier (30 div)' }, vc)) });
  const ro1 = readout('Vernier I'), ro2 = readout('Vernier II'), roT = readout('Prism-table');
  vBox.append(h('div', { class: 'readouts' }, ro1.el, ro2.el, cfg.kind === 'grating' ? roT.el : null));
  row.append(eyeBox, vBox);

  const ctls = h('div', { class: 'ctls' });
  const telS = slider({ label: 'Telescope (coarse / tangent screw)', min: -160, max: 160, step: 0.01, value: st.tel, fmt: () => '', fine: [['−1°', -1], ['−10′', -1 / 6], ['−1′', -1 / 60], ['−0.2′', -0.2 / 60], ['+0.2′', 0.2 / 60], ['+1′', 1 / 60], ['+10′', 1 / 6], ['+1°', 1]], onInput: v => { st.tel = v; changed(); } });
  const tblRange = cfg.kind === 'prism' ? [0, 90] : [-180, 180];
  const tblS = slider({ label: cfg.kind === 'prism' ? 'Prism table (rotate)' : 'Grating table (rotate)', min: tblRange[0], max: tblRange[1], step: 0.01, value: st.inc, fmt: () => '', fine: [['−1°', -1], ['−10′', -1 / 6], ['−1′', -1 / 60], ['+1′', 1 / 60], ['+10′', 1 / 6], ['+1°', 1]], onInput: (v, src) => { if (src === 'sync') return; if (st.pmode === 'angle' && cfg.kind === 'prism') st.tilt = clamp(v - 45, -15, 15); else st.inc = v; changed(); } });
  ctls.append(telS.el, tblS.el);
  const slitS = slider({ label: 'Collimator slit width', min: 0.005, max: 0.3, step: 0.005, value: st.slit, fmt: v => v.toFixed(3) + ' mm', onInput: v => { st.slit = v; changed(); } });
  ctls.append(slitS.el);
  let apS = null;
  if (cfg.aperture) {
    apS = slider({ label: 'Aperture slit (micrometer)', min: cfg.aperture.min, max: cfg.aperture.max, step: 0.01, value: st.ap, fmt: v => v.toFixed(2) + ' mm', fine: [['−1', -1], ['−0.1', -0.1], ['−0.01', -0.01], ['+0.01', 0.01], ['+0.1', 0.1], ['+1', 1]], onInput: v => { st.ap = v; changed(); } });
    ctls.append(apS.el);
  }
  el.append(ctls);
  const syncTbl = () => { if (cfg.kind === 'prism' && st.pmode === 'angle') tblS.set(45 + st.tilt, 'sync'); else tblS.set(st.inc, 'sync'); };
  syncTbl();

  function updateReadouts() {
    const v = verniers();
    ro1.set(dm(roundMin(v.v1)), Practice.on); ro2.set(dm(roundMin(v.v2)), Practice.on);
    roT.set(dm(roundMin(tableReading())), false);
  }
  function changed() { save(); top.redraw(); eyeView.redraw(); vern1.redraw(); vern2.redraw(); updateReadouts(); cfg.onChange && cfg.onChange(); }
  Practice.subs.add(changed); onCleanup(() => Practice.subs.delete(changed));
  updateReadouts();

  /* ---------- drawing: top view ---------- */
  function drawTop(ctx, W, H) {
    const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
    const cx = W * 0.55, cy = H / 2, Rs = Math.min(W * 0.42, H * 0.47);
    const P = (a, r) => [cx + r * Math.cos(a * DEG), cy - r * Math.sin(a * DEG)];
    // circular scale
    ctx.strokeStyle = T.line; ctx.lineWidth = 1; ctx.beginPath(); ctx.arc(cx, cy, Rs, 0, 7); ctx.stroke();
    ctx.fillStyle = T.muted; ctx.font = `9.5px ${T.mono}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    for (let k = 0; k < 72; k++) {
      const a = k * 5 - C0; const big = k % 6 === 0; const [x1, y1] = P(a, Rs), [x2, y2] = P(a, Rs - (big ? 9 : 5));
      ctx.strokeStyle = T.muted; ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
      if (big) { const [tx, ty] = P(a, Rs - 18); ctx.fillText(String(k * 5), tx, ty); }
    }
    const src = SOURCES[st.source];
    // lamp + collimator
    const [lx, ly] = P(180, Rs * 1.12);
    const glow = ctx.createRadialGradient(lx, ly, 2, lx, ly, 22); glow.addColorStop(0, src.tint); glow.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = glow; ctx.beginPath(); ctx.arc(lx, ly, 22, 0, 7); ctx.fill();
    drawTube(180, Rs * 0.32, Rs * 0.95, 13, T.ink, 'collimator');
    // beam in
    ctx.strokeStyle = src.tint; ctx.globalAlpha = .9; ctx.lineWidth = 3; ctx.beginPath(); const [bx, by] = P(180, Rs * 0.32); ctx.moveTo(bx, by); ctx.lineTo(cx, cy); ctx.stroke(); ctx.globalAlpha = 1; ctx.lineWidth = 1;
    // rays out
    const rs = rays();
    rs.forEach(r => {
      const [rr, gg, bb] = r.kind === 'reflL' || r.kind === 'reflR' || r.kind === 'refl' || (r.kind === 'diff' && r.m === 0) || r.kind === 'direct' ? (st.source === 'na' ? wl2rgb(589) : [0.85, 0.85, 1]) : wl2rgb(r.l);
      ctx.strokeStyle = `rgba(${rr * 255 | 0},${gg * 255 | 0},${bb * 255 | 0},${clamp(0.25 + r.I * 0.6, 0, 0.9)})`;
      ctx.lineWidth = 1.6; ctx.beginPath(); ctx.moveTo(cx, cy); const [x, y] = P(r.dir, Rs * 0.97); ctx.lineTo(x, y); ctx.stroke();
    });
    ctx.lineWidth = 1;
    // table
    ctx.fillStyle = T.panel; ctx.strokeStyle = T.muted; ctx.beginPath(); ctx.arc(cx, cy, Rs * 0.23, 0, 7); ctx.fill(); ctx.stroke();
    if (!st.removed) {
      if (cfg.kind === 'prism') {
        const A = cfg.prism.A, Ls = Rs * 0.3, hgt = Ls * Math.cos(A / 2 * DEG), b2 = Ls * Math.sin(A / 2 * DEG);
        const pts = [[0, hgt * 2 / 3], [-b2, -hgt / 3], [b2, -hgt / 3]];
        const rot = (st.pmode === 'angle' ? 90 + st.tilt : A / 2 - st.inc) * DEG;
        ctx.fillStyle = T.accent; ctx.globalAlpha = .22; ctx.strokeStyle = T.accent; ctx.lineWidth = 1.5;
        ctx.beginPath(); pts.forEach(([x, y], i) => { const X = cx + x * Math.cos(rot) - y * Math.sin(rot), Y = cy - (x * Math.sin(rot) + y * Math.cos(rot)); i ? ctx.lineTo(X, Y) : ctx.moveTo(X, Y); }); ctx.closePath(); ctx.fill(); ctx.globalAlpha = 1; ctx.stroke(); ctx.lineWidth = 1;
      } else {
        const g = st.inc, a = (90 - g) * DEG, Lg = Rs * 0.2;
        ctx.strokeStyle = T.accent; ctx.lineWidth = 4; ctx.beginPath(); ctx.moveTo(cx - Lg * Math.cos(a), cy + Lg * Math.sin(a)); ctx.lineTo(cx + Lg * Math.cos(a), cy - Lg * Math.sin(a)); ctx.stroke(); ctx.lineWidth = 1;
        // normal indicator
        ctx.setLineDash([3, 3]); ctx.strokeStyle = T.muted; ctx.beginPath(); ctx.moveTo(cx, cy); const [nx, ny] = P(-g, Rs * 0.3); ctx.lineTo(nx, ny); ctx.stroke(); ctx.setLineDash([]);
      }
    }
    ctx.fillStyle = T.muted; ctx.font = `10px ${T.sans}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    { const [lx1, ly1] = P(22, Rs * 1.08); if (ly1 > 8) ctx.fillText('left side', lx1, ly1); const [lx2, ly2] = P(-22, Rs * 1.08); if (ly2 < H - 8) ctx.fillText('right side', lx2, ly2); }
    // telescope
    const vis = inView(F());
    const tcol = vis.length ? T.sodium : T.ink;
    drawTube(st.tel, Rs * 0.3, Rs * 0.93, 15, tcol, 'telescope');
    function drawTube(a, r1, r2, w, col, lab) {
      const [x1, y1] = P(a, r1), [x2, y2] = P(a, r2);
      ctx.save(); ctx.strokeStyle = col; ctx.lineCap = 'round'; ctx.lineWidth = w; ctx.globalAlpha = .18; ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
      ctx.globalAlpha = 1; ctx.lineWidth = 1.5; ctx.lineCap = 'butt';
      const nx = -Math.sin(a * DEG) * w / 2, ny = -Math.cos(a * DEG) * w / 2;
      ctx.beginPath(); ctx.moveTo(x1 + nx, y1 + ny); ctx.lineTo(x2 + nx, y2 + ny); ctx.moveTo(x1 - nx, y1 - ny); ctx.lineTo(x2 - nx, y2 - ny); ctx.stroke();
      ctx.fillStyle = col; ctx.font = `10.5px ${T.sans}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      const [tx, ty] = P(a, r2 + 16); if (tx > 20 && tx < W - 20 && ty > 8 && ty < H - 8) ctx.fillText(lab, tx, ty);
      ctx.restore();
    }
  }
  /* ---------- drawing: eyepiece ---------- */
  function drawEye(ctx, W, H) {
    const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, H);
    const fh = st.trace ? H - 70 : H; const cx = W / 2, cy = fh / 2, Rr = Math.min(W, fh) / 2 - 3;
    const Fd = F(), pixDeg = Fd / Rr, pixRad = pixDeg * DEG;
    const list = inView(Fd * 1.25 + 0.02);
    const ws = st.slit / F_COLL; const bright = 0.45 + 0.55 * Math.min(1, st.slit / 0.08);
    const cols = Math.ceil(2 * Rr); const prof = new Float32Array(cols * 3); const lum = new Float32Array(cols);
    list.forEach(r => {
      const wd = r.l * 1e-6 / Math.max(0.01, r.Wout), wde = Math.max(wd, 1.15 * pixRad);
      const nS = clamp(Math.ceil(ws / (0.45 * wde)) + 1, 1, 80); const gain = Math.max(1, ws / wde) / nS;
      const isWhite = r.kind === 'reflL' || r.kind === 'reflR' || r.kind === 'refl' || r.kind === 'direct' || (r.kind === 'diff' && r.m === 0);
      const rgb = isWhite && st.source === 'hg' ? [0.8, 0.82, 1] : wl2rgb(r.l);
      const w8 = r.I * bright;
      for (let i = 0; i < cols; i++) {
        const dx = ((i - Rr) * pixDeg - r.off) * DEG;
        if (Math.abs(dx) > ws + 40 * wde) continue;
        let s = 0; for (let j = 0; j < nS; j++) { const o = nS === 1 ? 0 : (j / (nS - 1) - 0.5) * ws; const u = Math.PI * (dx - o) / wde; const sc = sinc(u); s += sc * sc; }
        const v = s * gain * w8; prof[i * 3] += v * rgb[0]; prof[i * 3 + 1] += v * rgb[1]; prof[i * 3 + 2] += v * rgb[2]; lum[i] += v;
      }
    });
    ctx.save(); ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.fillStyle = '#020304'; ctx.fill(); ctx.clip();
    const top = cy - Rr * 0.72, hh = Rr * 1.44;
    for (let i = 0; i < cols; i++) {
      const r = prof[i * 3], g = prof[i * 3 + 1], b = prof[i * 3 + 2]; if (r + g + b < 0.004) continue;
      const tm = v => Math.round(255 * (1 - Math.exp(-2.4 * v)));
      ctx.fillStyle = `rgb(${tm(r)},${tm(g)},${tm(b)})`; ctx.fillRect(cx - Rr + i, top, 1.2, hh);
    }
    // fade ends of slit image
    const gtop = ctx.createLinearGradient(0, top, 0, top + 18); gtop.addColorStop(0, 'rgba(2,3,4,1)'); gtop.addColorStop(1, 'rgba(2,3,4,0)'); ctx.fillStyle = gtop; ctx.fillRect(cx - Rr, top, 2 * Rr, 18);
    const gb = ctx.createLinearGradient(0, top + hh - 18, 0, top + hh); gb.addColorStop(0, 'rgba(2,3,4,0)'); gb.addColorStop(1, 'rgba(2,3,4,1)'); ctx.fillStyle = gb; ctx.fillRect(cx - Rr, top + hh - 18, 2 * Rr, 18);
    // crosswire
    ctx.strokeStyle = 'rgba(235,235,235,.75)'; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(cx + .5, cy - Rr); ctx.lineTo(cx + .5, cy + Rr); ctx.moveTo(cx - Rr, cy + .5); ctx.lineTo(cx + Rr, cy + .5); ctx.stroke();
    ctx.restore();
    ctx.strokeStyle = T.muted; ctx.lineWidth = 2; ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.stroke(); ctx.lineWidth = 1;
    ctx.fillStyle = T.muted; ctx.font = `10.5px ${T.mono}`; ctx.textAlign = 'left'; ctx.textBaseline = 'top';
    ctx.fillText(`field ±${Fd >= 0.1 ? (Fd).toFixed(2) + '°' : (Fd * 3600).toFixed(0) + '″'}`, 4, 4);
    if (!list.length) { ctx.fillStyle = '#7d8a91'; ctx.textAlign = 'center'; ctx.font = `12px ${T.sans}`; ctx.fillText('no light in the field', cx, cy + Rr * 0.82); }
    if (st.trace) {
      const ty = fh + 8, th = 56; ctx.fillStyle = T.panel2; ctx.fillRect(cx - Rr, ty, 2 * Rr, th);
      let mx = 0; for (const v of lum) mx = Math.max(mx, v);
      if (mx > 0) { ctx.strokeStyle = T.sodium; ctx.lineWidth = 1.5; ctx.beginPath(); for (let i = 0; i < cols; i++) { const y = ty + th - 4 - (lum[i] / mx) * (th - 10); i ? ctx.lineTo(cx - Rr + i, y) : ctx.moveTo(cx - Rr + i, y); } ctx.stroke(); ctx.lineWidth = 1; }
      ctx.fillStyle = T.muted; ctx.font = `10px ${T.sans}`; ctx.textAlign = 'left'; ctx.textBaseline = 'top'; ctx.fillText('intensity across the field', cx - Rr + 4, ty + 2);
    }
  }
  /* ---------- dragging on the top view ---------- */
  let drag = null;
  top.canvas.addEventListener('pointerdown', e => {
    const r = top.canvas.getBoundingClientRect(); const W = top.w, H = top.h; const cx = W * 0.55, cy = H / 2, Rs = Math.min(W * 0.42, H * 0.47);
    const x = e.clientX - r.left - cx, y = cy - (e.clientY - r.top); const rad = Math.hypot(x, y), a = Math.atan2(y, x) / DEG;
    if (rad > Rs * 0.28) drag = { kind: 'tel' }; else drag = { kind: 'tbl', a0: a };
    top.canvas.setPointerCapture(e.pointerId); move(e);
  });
  top.canvas.addEventListener('pointermove', move);
  top.canvas.addEventListener('pointerup', () => drag = null);
  top.canvas.addEventListener('pointercancel', () => drag = null);
  function move(e) {
    if (!drag) return; const r = top.canvas.getBoundingClientRect(); const W = top.w, H = top.h; const cx = W * 0.55, cy = H / 2;
    const x = e.clientX - r.left - cx, y = cy - (e.clientY - r.top); const a = Math.atan2(y, x) / DEG;
    if (drag.kind === 'tel') { if (Math.abs(a) <= 160) telS.set(a, 'drag'); }
    else {
      const da = angDiff(drag.a0, a); drag.a0 = a;
      if (cfg.kind === 'prism' && st.pmode === 'angle') { st.tilt = clamp(st.tilt + da, -15, 15); tblS.set(45 + st.tilt, 'sync'); changed(); }
      else tblS.set(st.inc - da, 'drag');
    }
  }
  /* ---------- recording helpers ---------- */
  async function readTel() {
    const v = verniers(); const t1 = roundMin(v.v1), t2 = roundMin(v.v2);
    const fmt = x => dm(x);
    const a = await askReading({ title: 'Read Vernier I', prompt: 'Read the main scale (0.5° divisions) and the coinciding vernier division (1′ each). Type it like <b>147 23</b> for 147° 23′.', truth: t1, tol: 0.55 / 60, parse: parseDM, diff: (x, y) => angDiff(x, y), hint: vernierHint(t1, 0.5, 1 / 60, fmt), placeholder: 'deg min' });
    if (a == null) return null;
    const b = await askReading({ title: 'Read Vernier II', prompt: 'Now read the second vernier (about 180° away).', truth: t2, tol: 0.55 / 60, parse: parseDM, diff: (x, y) => angDiff(x, y), hint: vernierHint(t2, 0.5, 1 / 60, fmt), placeholder: 'deg min' });
    if (b == null) return null;
    return { v1: t1, v2: t2 };
  }
  return { el, st, rays, inView, verniers, readTel, minDev, muOf, gEff, F, changed, redraw: changed, setSource: v => { st.source = v; changed(); } };
}

/* ================= experiments 2–6: spectrometer based ================= */
function benchCols(root) {
  const instr = h('div', { class: 'stack' }), notes = h('div', { class: 'stack' });
  root.append(h('div', { class: 'bench' }, instr, notes));
  return { instr, notes };
}
function plotCard(title, opts) {
  const c = card(title, opts.sub || '');
  const v = canvasView(c, { aspect: opts.aspect || 0.62, maxH: 340, minH: 220, draw: (ctx, w, hh) => drawPlot(ctx, w, hh, opts.get()) });
  return { el: c, view: v };
}
const NA_DL = 0.597, NA_L = 589.29; // nm
const SPEC_COMMON_PRECAUTIONS = String.raw`
<li>Level the spectrometer and the prism table with a spirit level before starting.</li>
<li>Focus the eyepiece on the cross-wires first, then focus the telescope and the collimator for parallel rays (Schuster’s method or a distant object). Do not disturb the focusing afterwards.</li>
<li>Keep the slit vertical and narrow; a wide slit gives a broad image and a poor setting of the cross-wire.</li>
<li>Read <b>both</b> verniers for every setting. Taking the mean of the two eliminates the error due to eccentricity of the circular scale.</li>
<li>Clamp the telescope (or the table) before using the tangent screw; set the cross-wire on the line with the tangent screw only.</li>
<li>Remove parallax between the cross-wire and the image. Never touch the optical surfaces of the prism or grating with fingers.</li>`;

/* ---------- shared prism tables ---------- */
function angleTable(key, onChange) {
  const tA = new ObsTable({
    key, title: 'Table 1. Angle of the prism (A)', onChange,
    template: () => [{ L1: null, L2: null, R1: null, R2: null }],
    columns: [
      { label: 'Image from face 1<br>V₁', k: 'L1', fmt: dm }, { label: '<br>V₂', k: 'L2', fmt: dm },
      { label: 'Image from face 2<br>V₁', k: 'R1', fmt: dm }, { label: '<br>V₂', k: 'R2', fmt: dm },
      { label: '2A<br>(V₁)', v: r => r.L1 != null && r.R1 != null ? Math.abs(angDiff(r.L1, r.R1)) : null, fmt: dmS, calc: 1 },
      { label: '2A<br>(V₂)', v: r => r.L2 != null && r.R2 != null ? Math.abs(angDiff(r.L2, r.R2)) : null, fmt: dmS, calc: 1 },
      { label: 'A<br>(mean)', v: r => angleOf(r), fmt: dmS, calc: 1 }]
  });
  return tA;
}
function angleOf(r) {
  if (!r) return null; const a = [];
  if (r.L1 != null && r.R1 != null) a.push(Math.abs(angDiff(r.L1, r.R1)) / 2);
  if (r.L2 != null && r.R2 != null) a.push(Math.abs(angDiff(r.L2, r.R2)) / 2);
  return a.length ? mean(a) : null;
}
function devTable(key, lineIds, getA, onChange, title) {
  return new ObsTable({
    key, title: title || 'Table 2. Minimum deviation', onChange,
    template: () => [{ id: 'direct', name: 'Direct reading' }].concat(lineIds.map(id => ({ id, name: lineOf('hg', id).name }))),
    columns: [
      { label: 'Position / colour', k: 'name', cls: 'lbl' },
      { label: 'V₁', k: 'v1', fmt: dm }, { label: 'V₂', k: 'v2', fmt: dm },
      { label: 'δ<sub>m</sub> (V₁)', v: (r, i, rows) => devOf(r, rows, 'v1'), fmt: dmS, calc: 1 },
      { label: 'δ<sub>m</sub> (V₂)', v: (r, i, rows) => devOf(r, rows, 'v2'), fmt: dmS, calc: 1 },
      { label: 'δ<sub>m</sub> mean', v: (r, i, rows) => devMean(r, rows), fmt: dmS, calc: 1 },
      { label: 'μ', v: (r, i, rows) => muFrom(getA(), devMean(r, rows)), fmt: v => v.toFixed(4), calc: 1 }]
  });
}
function devOf(r, rows, k) { if (r.id === 'direct') return null; const d = rows.find(x => x.id === 'direct'); if (!d || d[k] == null || r[k] == null) return null; return Math.abs(angDiff(d[k], r[k])); }
function devMean(r, rows) { const a = [devOf(r, rows, 'v1'), devOf(r, rows, 'v2')].filter(x => x != null); return a.length ? mean(a) : null; }
function muFrom(A, dm_) { if (A == null || dm_ == null) return null; return Math.sin((A + dm_) / 2 * DEG) / Math.sin(A / 2 * DEG); }

async function prismRecord(spec, slot, tA, tB, say) {
  const st = spec.st, near = 1 / 60;
  if (slot === 'A|1' || slot === 'A|2') {
    if (st.removed) return say('Put the prism back on the table first.', 'warn');
    if (st.pmode !== 'angle') return say('For angle A, place the prism with its refracting edge (apex) facing the collimator. Choose “Apex to collimator”.', 'warn');
    const want = slot === 'A|1' ? 'reflL' : 'reflR';
    const v = spec.inView(near).find(r => r.kind === want);
    if (!v) { const other = spec.inView(near).find(r => r.kind === 'reflL' || r.kind === 'reflR'); return say(other ? 'The cross-wire is on the image reflected from the <b>other</b> face. Choose the matching row.' : 'No reflected image of the slit is on the cross-wire. Turn the telescope until the reflected image is seen, then set the cross-wire on it with the tangent screw.', 'warn'); }
    const r = await spec.readTel(); if (!r) return;
    tA.update(0, slot === 'A|1' ? { L1: r.v1, L2: r.v2 } : { R1: r.v1, R2: r.v2 });
    return say(`Recorded: V₁ = ${dm(r.v1)}, V₂ = ${dm(r.v2)}.`, 'ok');
  }
  if (slot === 'direct') {
    if (!st.removed) return say('Remove the prism (tick “Remove prism”) and turn the telescope in line with the collimator to see the slit directly.', 'warn');
    if (!spec.inView(near).length) return say('The direct image of the slit is not on the cross-wire.', 'warn');
    const r = await spec.readTel(); if (!r) return;
    const i = tB.rows.findIndex(x => x.id === 'direct'); tB.update(i, { v1: r.v1, v2: r.v2 });
    return say(`Direct reading recorded: V₁ = ${dm(r.v1)}, V₂ = ${dm(r.v2)}.`, 'ok');
  }
  if (slot.startsWith('md|')) {
    const id = slot.slice(3);
    if (st.removed) return say('Put the prism back on the table.', 'warn');
    if (st.pmode !== 'refract') return say('Place the prism for refraction (Prism position → For refraction).', 'warn');
    if (st.source !== 'hg') return say('Use the mercury lamp for these lines.', 'warn');
    const vis = spec.inView(near).filter(r => r.kind === 'ref');
    const v = vis.find(r => r.line === id);
    if (!v) return say(vis.length ? `The cross-wire is on the <b>${lineOf('hg', vis[0].line).name}</b> line, not on ${lineOf('hg', id).name}.` : `The ${lineOf('hg', id).name} line is not on the cross-wire.`, 'warn');
    const excess = v.dev - spec.minDev(v.l);
    if (excess > 2 / 60) return say(`This is not the minimum deviation position yet (the line can still move ${excess > 0.5 ? 'a lot' : 'a little'} closer to the direct position). Rotate the prism table slowly and follow the line with the telescope until it stops and turns back.`, 'warn');
    const r = await spec.readTel(); if (!r) return;
    const i = tB.rows.findIndex(x => x.id === id); tB.update(i, { v1: r.v1, v2: r.v2 });
    return say(`Minimum deviation reading for ${lineOf('hg', id).name}: V₁ = ${dm(r.v1)}, V₂ = ${dm(r.v2)}.`, 'ok');
  }
}
function recorder(slots, onRec) {
  const sb = statusBox();
  const sel = selectBox({ options: slots, label: null });
  sel.sel.setAttribute('aria-label', 'Record this reading as');
  const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading');
  const api = { sel, say: sb.say };
  btn.addEventListener('click', async () => { btn.disabled = true; try { await onRec(api.sel.get(), sb.say); } finally { btn.disabled = false; } });
  sb.say('Choose what you are measuring, set the cross-wire, then press Record.');
  api.el = h('div', { class: 'stack' }, h('div', { class: 'recrow' }, h('span', { class: 'small muted' }, 'Record as'), sel.sel, btn), sb.el);
  return api;
}
function prismParams(R) {
  return { A: +(60 + R.range(-0.45, 0.45)).toFixed(3), Ac: +R.range(1.585, 1.625).toFixed(4), B: Math.round(R.range(9500, 12500)) };
}

/* ======================== EXP 2 ======================== */
EXPS.push({
  no: 2, id: 'exp2', group: 'Optics', short: 'Wavelength by plane diffraction grating',
  title: 'Wavelength of light using a plane diffraction grating',
  aim: 'To determine the wavelength of given source of light using a plane diffraction grating.',
  apparatus: ['Spectrometer', 'Plane transmission diffraction grating (15000 lines per inch)', 'Mercury vapour lamp (or sodium lamp)', 'Spirit level', 'Reading lens', 'Grating holder'],
  theory: String.raw`
<h2>Plane transmission grating</h2>
<p>A plane transmission grating is a glass plate on which a large number of equidistant parallel lines are ruled. The ruled lines are opaque (width \(b\)) and the spaces between them are transparent (width \(a\)). The distance \((a+b)\) is the <b>grating element</b>. If \(N\) lines are ruled per inch,</p>
<div class="eq">\[(a+b)=\frac{2.54}{N}\ \text{cm}\]</div>
<p>When a parallel beam of monochromatic light falls <b>normally</b> on the grating, the secondary waves from corresponding points of successive slits reach the telescope with a path difference \((a+b)\sin\theta\). Bright principal maxima are formed when this path difference is a whole number of wavelengths:</p>
<div class="eq">\[(a+b)\sin\theta = n\lambda,\qquad n = 0,\ 1,\ 2,\ \dots\]</div>
<p>so that</p>
<div class="eq">\[\lambda = \frac{(a+b)\sin\theta}{n} = \frac{2.54\,\sin\theta}{N\,n}\ \text{cm}\]</div>
<p>where \(\theta\) is the angle of diffraction of the \(n\)-th order maximum. White (zero-order) light goes straight; each colour of a mixed source appears on both sides at angles that increase with wavelength. The angle \(\theta\) is measured as half of the angle between the positions of the same line on the left and right sides, which removes any small error in setting the telescope.</p>
<h3>Why normal incidence?</h3>
<p>The simple formula holds only for normal incidence. For an angle of incidence \(i\) it becomes \((a+b)(\sin\theta - \sin i) = n\lambda\), and the left and right diffraction angles are no longer equal. The grating is therefore set exactly normal to the incident beam by the 45° method described in the procedure.</p>
<h3>Lines of the mercury spectrum</h3>
<p>The mercury lamp gives a line spectrum: violet, blue, a weak bluish-green, a bright green, two close yellow lines and a faint red. In the virtual lab you identify them by colour and calculate each wavelength. The sodium lamp gives the yellow D lines (unresolved in this experiment).</p>`,
  procedure: String.raw`
<h2>A. Preliminary adjustments of the spectrometer</h2>
<ol class="steps">
<li><b>Levelling.</b> Level the base, the prism table, the telescope and the collimator with the spirit level and the levelling screws.</li>
<li><b>Eyepiece.</b> Turn the telescope towards a white wall and move the eyepiece until the cross-wires are seen sharply.</li>
<li><b>Telescope for parallel rays.</b> Focus the telescope on a distant object (or by Schuster’s method) so that parallel rays form a sharp image on the cross-wires.</li>
<li><b>Collimator.</b> Illuminate the slit with the lamp. Bring the telescope in line with the collimator and adjust the collimator until a sharp, narrow, vertical image of the slit is seen. The beam from the collimator is now parallel.</li>
</ol>
<h2>B. Setting the grating normal to the incident light</h2>
<ol class="steps">
<li>With the grating removed, set the cross-wire on the direct image of the slit and note the reading of one vernier.</li>
<li>Rotate the telescope through exactly <b>90°</b> from this position (add 90° to the reading) and clamp it.</li>
<li>Mount the grating in its holder on the prism table with its ruled surface vertical. Rotate the prism table until the image of the slit <b>reflected</b> from the grating surface falls on the cross-wire. The grating now makes 45° with the incident beam.</li>
<li>Note the prism-table vernier reading and rotate the table through exactly <b>45°</b> in the direction that turns the grating face towards the collimator. Clamp the table. The grating is now normal to the beam.</li>
</ol>
<h2>C. Measuring the diffraction angles</h2>
<ol class="steps">
<li>Release the telescope and turn it to the <b>left</b> to see the first-order spectrum. Set the cross-wire on each line (violet → red) with the tangent screw and note the readings of both verniers.</li>
<li>Turn the telescope to the <b>right</b> side and repeat for the same lines of the first order.</li>
<li>Repeat steps 1–2 for the second-order spectrum.</li>
<li>For each line, \(2\theta\) = difference between the left and right readings of the same vernier. Take the mean of both verniers, find \(\theta\) and calculate \(\lambda = (a+b)\sin\theta/n\).</li>
</ol>
<div class="vnote"><b>On the virtual bench:</b> drag the telescope arm in the top view (or use the coarse slider) to find a line, then centre it on the cross-wire with the tangent-screw buttons (±1′, ±0.2′). Tick “Remove grating” for the direct reading. The <i>Prism-table</i> readout plays the role of the table vernier for the 45° rotation. Choose the row in “Record as” and press Record. Switch on <i>Read scales myself</i> (top bar) to practise reading the verniers.</div>`,
  precautions: SPEC_COMMON_PRECAUTIONS + String.raw`<li>The grating must be exactly normal to the incident beam; check that the left and right diffraction angles of the same line are equal.</li><li>Hold the grating by its edges; the ruled surface should face the telescope.</li>`,
  errors: String.raw`<li>Error in setting the grating normal to the beam.</li><li>Error in setting the cross-wire on the centre of a broad line (too wide a slit).</li><li>Eccentricity of the circular scale (removed by reading both verniers).</li>`,
  viva: [
    ['What is a diffraction grating?', 'A large number of equidistant, parallel, equally wide slits (rulings) on a glass plate. A transmission grating has about 15000 lines per inch.'],
    ['What is the grating element?', 'The distance (a + b) between corresponding points of two adjacent slits. For N lines per inch, a + b = 2.54/N cm.'],
    ['Why must the light fall normally on the grating?', 'The relation (a+b) sin θ = nλ is derived for normal incidence. For oblique incidence the angles on the two sides differ and the simple formula gives a wrong λ.'],
    ['What is the maximum order you can observe?', 'Since sin θ ≤ 1, n_max = (a+b)/λ. For 15000 LPI and λ = 546 nm, (a+b)/λ = 1693/546 ≈ 3.1, so the third order is just possible for green but not for red.'],
    ['Why are the spectral lines in the second order more widely spaced?', 'The dispersive power dθ/dλ = n/[(a+b) cos θ] increases with the order n.'],
    ['How does a grating spectrum differ from a prism spectrum?', 'A grating gives several orders, deviates red the most, and its dispersion is almost uniform (normal spectrum). A prism gives one spectrum, deviates violet the most and its dispersion is not uniform.'],
    ['Why is the central (zero-order) image white?', 'For n = 0 the path difference is zero for every wavelength, so all colours reinforce in the same direction.'],
    ['Why do we read both verniers?', 'To eliminate the error caused by eccentricity of the axis of rotation of the telescope relative to the circular scale.'],
    ['What happens if the number of lines per inch is increased?', 'The grating element decreases, so the diffraction angles and the dispersion increase, but fewer orders are visible.']],
  bench(root) {
    const R = apparatus('exp2'); const lpi = 15000; const d = 2.54e7 / lpi;
    const { instr, notes } = benchCols(root);
    let src = LS.get('exp2.src', 'hg');
    const spec = spectrometer({ id: 'exp2', rng: R, kind: 'grating', grating: { lpi }, sources: ['hg', 'na'], zooms: [[1, '×1'], [4, '×4']], onSource: v => { src = v; LS.set('exp2.src', v); buildTable(); rec.sel.sel.replaceWith(newSel()); } });
    spec.st.source = src; spec.changed();
    const lineSet = () => src === 'hg' ? HG_MAIN.map(id => ({ id, name: lineOf('hg', id).name, l: lineOf('hg', id).l })) : [{ id: 'na', name: 'Sodium yellow (D)', l: NA_L }];
    const slotOpts = () => [1, 2].flatMap(n => ['L', 'R'].map(s => ({ group: `Order ${n} — ${s === 'L' ? 'left' : 'right'} side`, items: lineSet().map(L => [`${L.id}|${n}|${s}`, `${L.name}, order ${n}, ${s === 'L' ? 'left' : 'right'}`]) })));
    function newSel() { const s = selectBox({ options: slotOpts() }); rec.sel = s; s.sel.setAttribute('aria-label', 'Record this reading as'); return s.sel; }
    const rec = recorder(slotOpts(), async (slot, say) => {
      const [id, n, side] = slot.split('|'); const N = +n;
      if (spec.st.removed) return say('Put the grating back on the table.', 'warn');
      const vis = spec.inView(1 / 60).filter(r => r.kind === 'diff' && r.m !== 0);
      const match = vis.find(r => (id === 'na' ? true : r.line === id) && Math.abs(r.m) === N && Math.sign(r.dir) === (side === 'L' ? 1 : -1));
      if (!match) {
        if (vis.length) { const v = vis[0]; return say(`The cross-wire is on the <b>${src === 'hg' ? lineOf('hg', v.line).name : 'sodium'}</b> line of order ${Math.abs(v.m)} on the ${v.dir > 0 ? 'left' : 'right'} side. Choose the matching row or move the telescope.`, 'warn'); }
        return say('No diffracted line is on the cross-wire. Find the line, then centre it with the tangent screw (±1′, ±0.2′).', 'warn');
      }
      const r = await spec.readTel(); if (!r) return;
      const i = tbl.rows.findIndex(x => x.id === id && x.n === N);
      tbl.update(i, side === 'L' ? { L1: r.v1, L2: r.v2 } : { R1: r.v1, R2: r.v2 });
      const g = spec.gEff();
      say(`Recorded V₁ = ${dm(r.v1)}, V₂ = ${dm(r.v2)}.` + (Math.abs(g) > 0.3 ? ' <br>Note: the grating is not exactly normal to the beam, so left and right angles will differ.' : ''), Math.abs(g) > 0.3 ? 'warn' : 'ok');
    });
    instr.append(card('Spectrometer', `grating: ${lpi} lines per inch`, spec.el), card('Record', '', rec.el));
    let tbl; const tblHost = h('div'); const res = resultBox();
    function buildTable() {
      tbl = new ObsTable({
        key: 'exp2.' + src, title: 'Table. Diffraction angles', onChange: analyse,
        template: () => [1, 2].flatMap(n => lineSet().map(L => ({ id: L.id, name: L.name, n }))),
        columns: [
          { label: 'Line', k: 'name', cls: 'lbl' }, { label: 'n', k: 'n' },
          { label: 'Left<br>V₁', k: 'L1', fmt: dm }, { label: 'Left<br>V₂', k: 'L2', fmt: dm },
          { label: 'Right<br>V₁', k: 'R1', fmt: dm }, { label: 'Right<br>V₂', k: 'R2', fmt: dm },
          { label: '2θ<br>(V₁)', v: r => r.L1 != null && r.R1 != null ? Math.abs(angDiff(r.L1, r.R1)) : null, fmt: dmS, calc: 1 },
          { label: '2θ<br>(V₂)', v: r => r.L2 != null && r.R2 != null ? Math.abs(angDiff(r.L2, r.R2)) : null, fmt: dmS, calc: 1 },
          { label: 'θ<br>(mean)', v: thetaOf, fmt: dmS, calc: 1 },
          { label: 'λ (nm)', v: r => { const t = thetaOf(r); return t == null ? null : d * Math.sin(t * DEG) / r.n; }, fmt: v => v.toFixed(1), calc: 1 }]
      });
      tblHost.innerHTML = ''; tblHost.append(tbl.el); analyse();
    }
    function thetaOf(r) { const a = []; if (r.L1 != null && r.R1 != null) a.push(Math.abs(angDiff(r.L1, r.R1)) / 2); if (r.L2 != null && r.R2 != null) a.push(Math.abs(angDiff(r.L2, r.R2)) / 2); return a.length ? mean(a) : null; }
    function analyse() {
      const by = {};
      tbl.rows.forEach(r => { const t = thetaOf(r); if (t != null) (by[r.id] = by[r.id] || []).push(d * Math.sin(t * DEG) / r.n); });
      const ids = Object.keys(by);
      if (!ids.length) { res.set(`<h4>Calculation</h4><p class="muted">Grating element (a + b) = 2.54/${lpi} cm = ${(2.54 / lpi).toExponential(4)} cm = ${d.toFixed(1)} nm.<br>Record the left and right readings of a line to calculate its wavelength.</p>`); return; }
      const rows = ids.map(id => { const L = lineSet().find(x => x.id === id); const v = mean(by[id]); return `<tr><td class="lbl">${L.name}</td><td>${v.toFixed(1)}</td><td>${L.l.toFixed(1)}</td><td>${pctErr(v, L.l).toFixed(2)}%</td></tr>`; }).join('');
      res.set(`<h4>Calculation &amp; result</h4><p class="small">(a + b) = 2.54/N = 2.54/${lpi} cm = <b>${(2.54 / lpi * 1e4).toFixed(3)} µm</b>; &nbsp; λ = (a + b) sin θ / n</p>
      <div class="tscroll" style="margin-top:8px"><table class="obs"><thead><tr><th>Line</th><th>λ measured (nm)</th><th>Standard (nm)</th><th>Error</th></tr></thead><tbody>${rows}</tbody></table></div>
      <p class="small muted" style="margin-top:8px">Result: the wavelengths of the ${src === 'hg' ? 'mercury lines' : 'sodium light'} are as shown (mean of the orders measured).</p>`);
    }
    buildTable();
    notes.append(card('Lab notebook', 'readings are saved in this browser', tblHost), card('Result', '', res.el));
  }
});

/* ======================== EXP 3 ======================== */
EXPS.push({
  no: 3, id: 'exp3', group: 'Optics', short: 'Resolving power of a prism',
  title: 'Resolving power of a prism',
  aim: 'To determine the resolving power of a prism.',
  apparatus: ['Spectrometer', 'Equilateral glass prism (base 5.0 cm)', 'Mercury vapour lamp', 'Sodium vapour lamp', 'Adjustable rectangular slit (aperture) with micrometer', 'Spirit level, reading lens'],
  theory: String.raw`
<h2>Resolving power</h2>
<p>The <b>resolving power</b> of an optical instrument that forms a spectrum is its ability to show two close spectral lines as separate. If \(\lambda\) and \(\lambda+d\lambda\) are just resolved,</p>
<div class="eq">\[\text{Resolving power} = \frac{\lambda}{d\lambda}\]</div>
<p><b>Rayleigh’s criterion:</b> two lines of equal intensity are just resolved when the principal maximum of one falls on the first minimum of the other. The intensity at the middle then dips to about 81% of the peaks.</p>
<h2>Resolving power of a prism</h2>
<p>Let a parallel beam of width \(a\) pass through the prism at minimum deviation. The extreme rays of the beam travel through glass paths that differ by \(t\), the <b>effective base</b> of the prism. Applying Rayleigh’s criterion to the emergent wavefront gives</p>
<div class="eq">\[\frac{\lambda}{d\lambda} = t\,\frac{d\mu}{d\lambda}\]</div>
<p>For the whole prism (beam filling the face) \(t\) equals the base length \(b\) of the prism. Using Cauchy’s formula \(\mu = A + B/\lambda^2\),</p>
<div class="eq">\[\frac{d\mu}{d\lambda} = -\frac{2B}{\lambda^{3}}\qquad\Rightarrow\qquad \text{R.P.} = \frac{2Bt}{\lambda^{3}}\]</div>
<h3>What we measure</h3>
<ol>
<li>The refracting angle \(A\) and the refractive index \(\mu\) for several mercury lines from \(\mu = \dfrac{\sin\frac{A+\delta_m}{2}}{\sin\frac{A}{2}}\). From two or more lines, Cauchy’s constant \(B\) and hence \(d\mu/d\lambda\) at the sodium wavelength (589.3 nm) are found.</li>
<li>The theoretical resolving power of the prism, \(b\,|d\mu/d\lambda|\), with the base \(b\) = 5.0 cm.</li>
<li>An experimental check: an adjustable rectangular slit of width \(a\) is put in the path of the beam. Its width is reduced until the sodium D lines (588.995 nm and 589.592 nm, \(d\lambda\) = 0.597 nm) are <b>just resolved</b>. The effective base is then</li>
</ol>
<div class="eq">\[t = \frac{2a\sin(A/2)}{\cos i},\qquad \sin i = \mu\sin\frac{A}{2}\]</div>
<p>where \(i\) is the angle of incidence at minimum deviation. At this setting \(t\,|d\mu/d\lambda|\) should equal \(\lambda/d\lambda = 589.3/0.597 \approx 987\).</p>`,
  procedure: String.raw`
<h2>A. Preliminary adjustments</h2>
<ol class="steps"><li>Level the spectrometer, focus the eyepiece on the cross-wires, focus the telescope for parallel rays and adjust the collimator so that a sharp vertical image of a narrow slit is seen.</li></ol>
<h2>B. Angle of the prism (A)</h2>
<ol class="steps">
<li>Place the prism on the table with its refracting edge (apex) facing the collimator, near the centre of the table.</li>
<li>Turn the telescope to one side and find the image of the slit reflected from one face. Set the cross-wire on it and read both verniers.</li>
<li>Turn the telescope to the other side, find the image reflected from the second face and read both verniers.</li>
<li>The difference between the two readings of a vernier is \(2A\).</li></ol>
<h2>C. Refractive index of mercury lines</h2>
<ol class="steps">
<li>Remove the prism and take the direct reading of the slit (both verniers).</li>
<li>Replace the prism for refraction. Find a spectral line through the prism. Rotate the prism table slowly in one direction while following the line with the telescope. At one position the line stops and turns back: this is <b>minimum deviation</b>. Set the cross-wire on it there and read both verniers.</li>
<li>Repeat for at least two lines (e.g. green and yellow; more lines give a better value of B).</li></ol>
<h2>D. Resolution of the sodium D lines</h2>
<ol class="steps">
<li>Replace the mercury lamp by the sodium lamp and set the prism at minimum deviation for the yellow light. Make the collimator slit as narrow as possible and use the high-power eyepiece.</li>
<li>Place the adjustable rectangular slit on the table in the path of the incident beam with its length parallel to the refracting edge.</li>
<li>Starting with a wide aperture (D lines clearly separate), reduce the width slowly until the two lines just merge into one. Note the width \(a\) when they are <b>just resolved</b>. Repeat three times.</li>
<li>Calculate \(t\), \(d\mu/d\lambda\) and the resolving power.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> choose <i>Apex to collimator</i> for part B and <i>For refraction</i> for parts C and D. Drag inside the table circle to rotate the prism. For part D choose the sodium lamp, select the ×40 eyepiece, narrow the collimator slit (0.005–0.01 mm) and use the aperture micrometer. Switch on the <i>Intensity trace</i> to see the dip between the lines.</div>`,
  precautions: SPEC_COMMON_PRECAUTIONS + String.raw`<li>The prism must be exactly at minimum deviation for the light being studied.</li><li>The collimator slit should be very narrow in part D; otherwise the D lines cannot be resolved even with a wide aperture.</li><li>Reduce the aperture slowly; the judgement of “just resolved” should be repeated and averaged.</li>`,
  errors: String.raw`<li>Judgement of the just-resolved position is subjective.</li><li>A small error in A or δ<sub>m</sub> gives a larger relative error in dμ/dλ, which is found from a small difference in μ.</li>`,
  viva: [
    ['What is meant by resolving power of a prism?', 'Its ability to separate two close wavelengths, measured as λ/dλ for two lines that are just resolved.'],
    ['State Rayleigh’s criterion.', 'Two equally intense lines are just resolved when the central maximum of one falls on the first minimum of the other.'],
    ['On what does the resolving power of a prism depend?', 'On the base length t (effective width of the beam) and on the dispersion dμ/dλ of the glass. It does not depend on the angle of the prism directly.'],
    ['Why does R.P. of a prism increase towards the violet?', 'Because dμ/dλ = −2B/λ³ increases rapidly as λ decreases.'],
    ['What is the minimum R.P. needed to resolve the sodium D lines?', 'λ/dλ = 589.3/0.597 ≈ 987.'],
    ['Distinguish between dispersive power and resolving power.', 'Dispersive power gives the angular separation of two wavelengths; resolving power tells whether the two lines can be seen separately, which also depends on the width of each line.'],
    ['Why is the slit kept very narrow?', 'The width of the line image adds to the diffraction width. A wide slit makes each D line broad so they overlap.'],
    ['Why is the prism set at minimum deviation?', 'At minimum deviation the beam passes symmetrically, the formula for μ is valid and the image is sharpest and brightest.']],
  bench(root) {
    const R = apparatus('exp3'); const P = prismParams(R); const base = 50; // mm
    const { instr, notes } = benchCols(root);
    const spec = spectrometer({ id: 'exp3', rng: R, kind: 'prism', prism: P, sources: ['hg', 'na'], angleMode: true, zooms: [[1, '×1'], [10, '×10'], [40, '×40']], aperture: { min: 0.5, max: 20, init: 20 }, trace: true });
    const lines = ['blue', 'green', 'y1', 'y2'];
    const rec = recorder([
      { group: 'Part B — angle of prism', items: [['A|1', 'Image reflected from face 1'], ['A|2', 'Image reflected from face 2']] },
      { group: 'Part C — mercury lines', items: [['direct', 'Direct reading (prism removed)']].concat(lines.map(id => ['md|' + id, `${lineOf('hg', id).name} — minimum deviation`])) },
      { group: 'Part D — sodium D lines', items: [['ap', 'Aperture width when D lines are just resolved']] }],
      async (slot, say) => {
        if (slot !== 'ap') return prismRecord(spec, slot, tA, tB, say);
        const st = spec.st;
        if (st.source !== 'na' || st.pmode !== 'refract' || st.removed) return say('Use the sodium lamp with the prism in the refraction position.', 'warn');
        const vis = spec.inView(spec.F()).filter(r => r.kind === 'ref');
        if (vis.length < 2) return say('The D lines are not in the field of view. Find the yellow line, set the prism at minimum deviation and use the ×40 eyepiece.', 'warn');
        if (vis[0].dev - spec.minDev(vis[0].l) > 0.25) return say('Set the prism at minimum deviation for the sodium light first.', 'warn');
        const [a, b] = vis; const sep = Math.abs(a.dir - b.dir) * DEG, wd = a.l * 1e-6 / a.Wout, ws = st.slit / F_COLL;
        const ratio = sep / Math.hypot(wd, ws * 0.9);
        tC.add({ a: st.ap });
        say(`Recorded aperture width a = ${st.ap.toFixed(2)} mm.` + (ratio > 1.35 ? ' The lines still look clearly separated at this width; the just-resolved width is smaller.' : ratio < 0.8 ? ' At this width the lines are not resolved; the just-resolved width is larger.' : ''), ratio > 1.35 || ratio < 0.8 ? 'warn' : 'ok');
      });
    instr.append(card('Spectrometer', 'equilateral prism, base 5.0 cm', spec.el), card('Record', '', rec.el));
    const tA = angleTable('exp3.A', () => { tB.render(); analyse(); });
    const tB = devTable('exp3.B', lines, () => angleOf(tA.rows[0]), analyse, 'Table 2. Refractive index for mercury lines');
    const tC = new ObsTable({ key: 'exp3.C', title: 'Table 3. Aperture width at just resolution (sodium D lines)', onChange: analyse, deletable: true, columns: [{ label: 'Trial', v: (r, i) => i + 1 }, { label: 'Aperture width a (mm)', k: 'a', fmt: v => v.toFixed(2) }] });
    const res = resultBox();
    function analyse() {
      const A = angleOf(tA.rows[0]);
      const pts = tB.rows.filter(r => r.id !== 'direct').map(r => { const mu = muFrom(A, devMean(r, tB.rows)); return mu == null ? null : [1 / (lineOf('hg', r.id).l ** 2), mu, r]; }).filter(Boolean);
      let html = '<h4>Calculation</h4><div class="calc-lines">';
      if (A == null) { res.set(html + '<div>Record both reflected images to find A.</div></div>'); return; }
      html += `<div>A = ${dmS(A)}</div>`;
      if (pts.length < 2) { res.set(html + '<div>Record the direct reading and the minimum deviation of at least two mercury lines.</div></div>'); return; }
      const f = linreg(pts.map(p => [p[0], p[1]])); const B = f.m, Ac = f.c; // B in nm²
      const dmu = 2 * B / NA_L ** 3; // per nm
      const muNa = Ac + B / NA_L ** 2; const i = Math.asin(clamp(muNa * Math.sin(A / 2 * DEG), -1, 1)) / DEG;
      html += `<div>Cauchy fit: μ = ${Ac.toFixed(4)} + ${B.toFixed(0)} nm² / λ²</div><div>|dμ/dλ| at 589.3 nm = 2B/λ³ = ${(dmu * 1e7).toFixed(1)} cm⁻¹ (${dmu.toExponential(3)} nm⁻¹)</div>`;
      html += `<div>Theoretical R.P. of prism = b |dμ/dλ| = 5.0 cm × ${(dmu * 1e7).toFixed(1)} cm⁻¹ = <b>${(base * 1e6 * dmu).toFixed(0)}</b></div>`;
      if (!tC.rows.length) { res.set(html + '<div>Now find the aperture width at which the sodium D lines are just resolved.</div></div>'); return; }
      const a = mean(tC.rows.map(r => r.a)); const t = 2 * a * Math.sin(A / 2 * DEG) / Math.cos(i * DEG);
      const rp = t * 1e6 * dmu; const need = NA_L / NA_DL;
      html += `<div>μ(589.3) = ${muNa.toFixed(4)}, &nbsp;i = sin⁻¹(μ sin A/2) = ${dmS(i)}</div><div>mean a = ${a.toFixed(2)} mm → t = 2a sin(A/2)/cos i = ${t.toFixed(2)} mm</div><div>R.P. at just resolution = t |dμ/dλ| = <b>${rp.toFixed(0)}</b></div><div>Required λ/dλ = 589.29/0.597 = ${need.toFixed(0)} &nbsp;(difference ${pctErr(rp, need).toFixed(1)}%)</div></div>`;
      html += `<p class="big" style="margin-top:10px">Resolving power of the prism (full base) ≈ <b>${(base * 1e6 * dmu).toFixed(0)}</b></p><p class="small muted">${(() => { const d = Math.abs(pctErr(rp, need)); return d <= 12 ? `The experimental value at just-resolution (${rp.toFixed(0)}) agrees with λ/dλ for the D lines (${need.toFixed(0)}) within ${d.toFixed(0)}%, verifying R.P. = t dμ/dλ.` : d <= 30 ? `The value at just-resolution (${rp.toFixed(0)}) is of the same order as λ/dλ (${need.toFixed(0)}) but differs by ${d.toFixed(0)}%. Narrow the aperture slowly and judge the just-resolved position more carefully (the dip between the lines should be about 80% of the peaks).` : `The value at just-resolution (${rp.toFixed(0)}) differs from λ/dλ (${need.toFixed(0)}) by ${d.toFixed(0)}%. The aperture readings were probably not taken at the just-resolved position; repeat part C.`; })()}</p>`;
      res.set(html);
    }
    notes.append(card('Lab notebook', 'readings are saved in this browser', h('div', { class: 'stack' }, tA.el, tB.el, tC.el)), card('Result', '', res.el));
    analyse();
  }
});

/* ======================== EXP 4 ======================== */
EXPS.push({
  no: 4, id: 'exp4', group: 'Optics', short: 'Resolving power of a grating',
  title: 'Resolving power of a plane transmission grating',
  aim: 'To determine the resolving power of a plane transmission diffraction grating.',
  apparatus: ['Spectrometer', 'Plane transmission grating (15000 lines per inch)', 'Sodium vapour lamp', 'Adjustable rectangular slit with micrometer', 'Spirit level, reading lens'],
  theory: String.raw`
<h2>Resolving power of a grating</h2>
<p>Two wavelengths \(\lambda\) and \(\lambda + d\lambda\) are just resolved (Rayleigh’s criterion) when the \(n\)-th order principal maximum of \(\lambda+d\lambda\) falls on the first minimum adjacent to the \(n\)-th order maximum of \(\lambda\). For a grating with \(N\) lines exposed to the beam, the first minimum next to the \(n\)-th order maximum lies at a path difference \(n\lambda + \lambda/N\) between the extreme rays. Equating:</p>
<div class="eq">\[N(a+b)\sin\theta = N n\lambda + \lambda = Nn(\lambda + d\lambda)\]</div>
<div class="eq">\[\Rightarrow\qquad \frac{\lambda}{d\lambda} = nN\]</div>
<p>The resolving power is the product of the order and the <b>total number of lines exposed</b> to the incident beam. It does not depend on the grating element.</p>
<h3>Experimental method</h3>
<p>The sodium D lines (\(\lambda_1\) = 588.995 nm, \(\lambda_2\) = 589.592 nm) are observed in the first order. An adjustable rectangular slit placed before the grating limits the width of the beam. When the slit width is \(w\) and the D lines are <b>just resolved</b>, the number of lines used is</p>
<div class="eq">\[N = w \times (\text{lines per cm}) = w\,\frac{N_0}{2.54}\]</div>
<p>where \(N_0\) = 15000 lines per inch. Then the experimental resolving power is \(nN\), which should equal the theoretical value</p>
<div class="eq">\[\frac{\lambda}{d\lambda} = \frac{589.29}{0.597} \approx 987\]</div>
<p>The resolving power of the grating itself, with the full beam width \(W\) = 2.0 cm, is \(n \times W \times N_0/2.54\).</p>`,
  procedure: String.raw`
<h2>Procedure</h2>
<ol class="steps">
<li>Make the preliminary adjustments of the spectrometer (levelling, eyepiece, telescope and collimator for parallel light).</li>
<li>Set the grating normal to the incident beam by the 45° method (as in the wavelength experiment).</li>
<li>Illuminate the slit with the sodium lamp. Make the collimator slit very narrow and observe the first-order yellow line with the high-power eyepiece; the two D lines are seen separately.</li>
<li>Place the adjustable rectangular slit between the collimator and the grating with its length parallel to the grating lines.</li>
<li>Reduce its width slowly until the two D lines just merge (just resolved). Note the width \(w\) from the micrometer. Repeat three times, approaching from wider widths.</li>
<li>Repeat for the second order.</li>
<li>Calculate \(N = w N_0/2.54\) and the resolving power \(nN\). Compare with \(\lambda/d\lambda\).</li></ol>
<div class="vnote"><b>On the virtual bench:</b> first set normal incidence (telescope at direct reading + 90°, rotate the grating table until the reflected image is on the cross-wire, then turn the table back 45° using the Prism-table readout). Then find the first-order yellow line, choose the ×40 eyepiece, narrow the collimator slit and turn on the intensity trace. Reduce the aperture micrometer until the dip between the two lines disappears.</div>`,
  precautions: SPEC_COMMON_PRECAUTIONS + String.raw`<li>The rectangular slit must be parallel to the grating lines and centred on the beam.</li><li>Use a very narrow collimator slit; a wide slit blurs the lines and the result is too large.</li>`,
  errors: String.raw`<li>Subjective judgement of the just-resolved position.</li><li>Error in reading the small slit width.</li>`,
  viva: [
    ['Define resolving power of a grating.', 'λ/dλ for two lines that are just resolved; it equals nN, the order times the number of lines exposed.'],
    ['Does the resolving power depend on the grating element?', 'No. It depends only on the order and the total number of lines illuminated.'],
    ['Why does the resolving power increase in the second order?', 'R.P. = nN, so for the same width the second order gives twice the resolving power; a smaller width is needed to just resolve the lines.'],
    ['What is the difference between dispersive power and resolving power?', 'Dispersive power dθ/dλ = n/[(a+b)cos θ] gives the angular separation; resolving power nN tells whether the lines can be seen separately.'],
    ['What is the minimum number of lines needed to resolve the D lines in the first order?', 'N = λ/(n dλ) ≈ 987 lines.'],
    ['Why is a high-power eyepiece used?', 'The angular separation of the D lines in the first order is only about 1′; magnification helps to judge the resolution.']],
  bench(root) {
    const R = apparatus('exp4'); const lpi = 15000; const perMM = lpi / 25.4;
    const { instr, notes } = benchCols(root);
    const spec = spectrometer({ id: 'exp4', rng: R, kind: 'grating', grating: { lpi }, sources: ['na'], zooms: [[1, '×1'], [10, '×10'], [40, '×40']], aperture: { min: 0.2, max: 20, init: 20 }, trace: true });
    const rec = recorder([{ group: 'Just-resolved aperture width', items: [['1', 'First order'], ['2', 'Second order']] }], async (slot, say) => {
      const n = +slot; const st = spec.st;
      if (st.removed) return say('Put the grating back.', 'warn');
      const vis = spec.inView(spec.F()).filter(r => r.kind === 'diff' && Math.abs(r.m) === n);
      if (vis.length < 2) return say(`The order-${n} yellow lines are not in the field of view. Find them and use a high-power eyepiece.`, 'warn');
      const [a, b] = vis; const sep = Math.abs(a.dir - b.dir) * DEG, wd = a.l * 1e-6 / a.Wout, ws = st.slit / F_COLL;
      const ratio = sep / Math.hypot(wd, ws * 0.9);
      tbl.add({ n, w: st.ap });
      say(`Recorded w = ${st.ap.toFixed(2)} mm for order ${n}.` + (ratio > 1.35 ? ' The lines still look clearly separated; the just-resolved width is smaller.' : ratio < 0.8 ? ' At this width they are not resolved; the just-resolved width is larger.' : '') + (Math.abs(spec.gEff()) > 1 ? ' (The grating is not normal to the beam.)' : ''), ratio > 1.35 || ratio < 0.8 ? 'warn' : 'ok');
    });
    instr.append(card('Spectrometer', `grating: ${lpi} lines per inch`, spec.el), card('Record', '', rec.el));
    const need = NA_L / NA_DL;
    const tbl = new ObsTable({
      key: 'exp4.t', title: 'Table. Width of aperture at just resolution', onChange: analyse, deletable: true, sort: (a, b) => a.n - b.n,
      columns: [{ label: 'Order n', k: 'n' }, { label: 'Width w (mm)', k: 'w', fmt: v => v.toFixed(2) },
        { label: 'N = w × lines/mm', v: r => r.w * perMM, fmt: v => v.toFixed(0), calc: 1 },
        { label: 'R.P. = nN', v: r => r.n * r.w * perMM, fmt: v => v.toFixed(0), calc: 1 },
        { label: 'λ/dλ', v: () => need, fmt: v => v.toFixed(0) },
        { label: 'Difference', v: r => pctErr(r.n * r.w * perMM, need), fmt: v => v.toFixed(1) + '%', calc: 1 }]
    });
    const res = resultBox();
    function analyse() {
      let html = `<h4>Calculation</h4><div class="calc-lines"><div>Lines per mm = ${lpi}/25.4 = ${perMM.toFixed(1)}</div><div>λ/dλ (D lines) = 589.29 / 0.597 = ${need.toFixed(0)}</div>`;
      [1, 2].forEach(n => { const ws = tbl.rows.filter(r => r.n === n).map(r => r.w); if (ws.length) html += `<div>Order ${n}: mean w = ${mean(ws).toFixed(2)} mm → N = ${(mean(ws) * perMM).toFixed(0)} → R.P. = ${(n * mean(ws) * perMM).toFixed(0)}</div>`; });
      html += `<div>Full beam W = 20.0 mm → R.P. of grating = n × ${(20 * perMM).toFixed(0)} = ${(20 * perMM).toFixed(0)} (n = 1), ${(40 * perMM).toFixed(0)} (n = 2)</div></div>`;
      const all = tbl.rows.map(r => r.n * r.w * perMM);
      if (all.length) html += `<p class="big" style="margin-top:10px">Experimental resolving power = <b>${mean(all).toFixed(0)}</b> (theoretical ${need.toFixed(0)})</p>`;
      else html += `<p class="muted small" style="margin-top:8px">Record the just-resolved aperture width to complete the result.</p>`;
      res.set(html);
    }
    notes.append(card('Lab notebook', '', tbl.el), card('Result', '', res.el)); analyse();
  }
});

/* ======================== EXP 5 & 6 ======================== */
function prismMuBench(root, id, mode) {
  const R = apparatus(id); const P = prismParams(R);
  const { instr, notes } = benchCols(root);
  const spec = spectrometer({ id, rng: R, kind: 'prism', prism: P, sources: ['hg'], angleMode: true, zooms: [[1, '×1'], [4, '×4']] });
  const lines = HG_MAIN;
  const rec = recorder([
    { group: 'Angle of the prism', items: [['A|1', 'Image reflected from face 1'], ['A|2', 'Image reflected from face 2']] },
    { group: 'Minimum deviation', items: [['direct', 'Direct reading (prism removed)']].concat(lines.map(i => ['md|' + i, `${lineOf('hg', i).name} — minimum deviation`])) }],
    (slot, say) => prismRecord(spec, slot, tA, tB, say));
  instr.append(card('Spectrometer', 'equilateral prism, mercury lamp', spec.el), card('Record', '', rec.el));
  const tA = angleTable(id + '.A', () => { tB.render(); analyse(); });
  const tB = devTable(id + '.B', lines, () => angleOf(tA.rows[0]), analyse);
  const res = resultBox();
  const pts = () => { const A = angleOf(tA.rows[0]); return tB.rows.filter(r => r.id !== 'direct').map(r => { const mu = muFrom(A, devMean(r, tB.rows)); return mu == null ? null : { id: r.id, l: lineOf('hg', r.id).l, mu }; }).filter(Boolean); };
  const plot = mode === 'mu'
    ? plotCard('Graph: μ against λ (dispersion curve)', { get: () => ({ xLabel: 'Wavelength λ (nm)', yLabel: 'Refractive index μ', series: [{ pts: pts().map(p => [p.l, p.mu]), join: true }], yDec: 3 }) })
    : plotCard('Graph: μ against 1/λ²', { get: () => { const p = pts().map(q => [1e6 / q.l ** 2, q.mu]); const f = p.length >= 2 ? linreg(p) : null; return { xLabel: '1/λ² (µm⁻²)', yLabel: 'Refractive index μ', series: [{ pts: p, fit: f }], yDec: 3, xDec: 2 }; } });
  function analyse() {
    plot.view.redraw();
    const A = angleOf(tA.rows[0]); const p = pts();
    let html = '<h4>Calculation</h4><div class="calc-lines">';
    if (A == null) { res.set(html + '<div>Record both reflected images to find the angle A of the prism.</div></div>'); return; }
    html += `<div>Angle of prism A = ${dmS(A)}</div><div>μ = sin[(A + δm)/2] / sin(A/2)</div>`;
    if (!p.length) { res.set(html + '<div>Record the direct reading and the minimum-deviation readings of the lines.</div></div>'); return; }
    html += '</div>';
    if (mode === 'mu') {
      html += `<div class="tscroll" style="margin-top:8px"><table class="obs"><thead><tr><th>Line</th><th>λ (nm)</th><th>μ</th></tr></thead><tbody>${p.sort((a, b) => a.l - b.l).map(q => `<tr><td class="lbl">${lineOf('hg', q.id).name}</td><td>${q.l.toFixed(1)}</td><td>${q.mu.toFixed(4)}</td></tr>`).join('')}</tbody></table></div>`;
      const v = p.find(q => q.id === 'violet'), r = p.find(q => q.id === 'red') || p.find(q => q.id === 'y2'), y = p.find(q => q.id === 'y1') || p.find(q => q.id === 'green');
      if (v && r && y) html += `<p class="small" style="margin-top:8px">Dispersive power (violet–${lineOf('hg', r.id).name.toLowerCase()}): ω = (μ<sub>v</sub> − μ<sub>r</sub>)/(μ<sub>y</sub> − 1) = ${((v.mu - r.mu) / (y.mu - 1)).toFixed(4)}</p>`;
      html += `<p class="small muted">Result: the refractive index of the prism decreases with increasing wavelength (normal dispersion), as shown by the graph.</p>`;
    } else {
      if (p.length < 2) { res.set(html + '<p class="small muted">Record at least two lines (more lines give a better straight line).</p>'); return; }
      const f = linreg(p.map(q => [1e6 / q.l ** 2, q.mu]));
      html += `<div class="calc-lines" style="margin-top:6px"><div>Straight-line fit of μ vs 1/λ² (${p.length} lines, r = ${f.r.toFixed(4)})</div><div>Intercept A = ${f.c.toFixed(4)} ± ${f.sc.toFixed(4)}</div><div>Slope B = ${f.m.toFixed(4)} µm² = ${(f.m * 1e-8).toExponential(3)} cm²</div></div>`;
      html += `<p class="big" style="margin-top:10px">Cauchy’s constants: A = <b>${f.c.toFixed(4)}</b>, B = <b>${(f.m * 1e-8).toExponential(2)} cm²</b></p>`;
      html += `<p class="small muted">Manufacturer’s data for this prism: A = ${P.Ac.toFixed(4)}, B = ${(P.B * 1e-14).toExponential(2)} cm². Using your constants, μ for sodium light (589.3 nm) = ${(f.c + f.m / 0.58929 ** 2).toFixed(4)}.</p>`;
    }
    res.set(html);
  }
  notes.append(card('Lab notebook', 'readings are saved in this browser', h('div', { class: 'stack' }, tA.el, tB.el)), plot.el, card('Result', '', res.el));
  analyse();
}
const PRISM_PROC = String.raw`
<h2>A. Preliminary adjustments</h2>
<ol class="steps">
<li>Level the spectrometer and the prism table with a spirit level.</li>
<li>Focus the eyepiece on the cross-wires; focus the telescope for parallel rays (distant object or Schuster’s method).</li>
<li>Illuminate the slit with the mercury lamp, bring the telescope in line with the collimator and adjust the collimator for a sharp, narrow, vertical image of the slit.</li></ol>
<h2>B. Angle of the prism (A)</h2>
<ol class="steps">
<li>Place the prism at the centre of the table with its refracting edge (apex) towards the collimator so that light falls on both faces.</li>
<li>Find the image of the slit reflected from one face; set the cross-wire on it and read both verniers.</li>
<li>Find the image reflected from the other face and read both verniers.</li>
<li>The difference of the two readings of the same vernier is 2A. Take the mean of the two verniers.</li></ol>
<h2>C. Angle of minimum deviation</h2>
<ol class="steps">
<li>Remove the prism and take the direct reading of the slit image (both verniers).</li>
<li>Place the prism so that light enters one face and emerges from the other. Look for the spectrum with the naked eye, then with the telescope.</li>
<li>Rotate the prism table slowly towards the direct position while following a line with the telescope. The line moves, stops and then turns back. Set the table where the line turns back (<b>minimum deviation</b>) and put the cross-wire on the line with the tangent screw. Read both verniers.</li>
<li>Repeat for each line of the mercury spectrum (violet, blue, bluish-green, green, yellow-1, yellow-2, red).</li>
<li>δ<sub>m</sub> = difference between the minimum deviation reading and the direct reading. Calculate μ for each line.</li></ol>`;
const PRISM_VNOTE = String.raw`<div class="vnote"><b>On the virtual bench:</b> choose <i>Apex to collimator</i> for the angle of the prism and <i>For refraction</i> for minimum deviation. Drag inside the table circle (or use the table slider) to rotate the prism, and drag the telescope arm to follow the line. If the line is not at minimum deviation, the Record button tells you so.</div>`;
EXPS.push({
  no: 5, id: 'exp5', group: 'Optics', short: 'Refractive index for different wavelengths',
  title: 'Refractive index of a prism for different wavelengths',
  aim: 'To determine the refractive index of the material of a prism for light of different wavelengths.',
  apparatus: ['Spectrometer', 'Equilateral glass prism', 'Mercury vapour lamp', 'Spirit level', 'Reading lens'],
  theory: String.raw`
<h2>Refractive index from minimum deviation</h2>
<p>When a ray passes through a prism of refracting angle \(A\), the deviation \(\delta\) depends on the angle of incidence. The deviation is least (\(\delta_m\)) when the ray passes <b>symmetrically</b> through the prism: the angle of incidence equals the angle of emergence and the ray inside is parallel to the base. Then \(r_1 = r_2 = A/2\) and \(i = (A+\delta_m)/2\), so</p>
<div class="eq">\[\mu = \frac{\sin\left(\dfrac{A+\delta_m}{2}\right)}{\sin\left(\dfrac{A}{2}\right)}\]</div>
<h3>Angle of the prism</h3>
<p>When the apex faces the collimator, the parallel beam is reflected from both faces. The angle between the two reflected beams is \(2A\), so \(A\) is half the difference between the two telescope readings.</p>
<h3>Dispersion</h3>
<p>The refractive index of glass depends on wavelength: it is larger for violet than for red (<b>normal dispersion</b>). Hence each line of the mercury spectrum has its own minimum deviation and its own \(\mu\). The graph of \(\mu\) against \(\lambda\) is the dispersion curve of the glass. The dispersive power between two colours is</p>
<div class="eq">\[\omega = \frac{\mu_v - \mu_r}{\mu_y - 1}\]</div>
<table><tr><th>Line (Hg)</th><th>Violet</th><th>Blue</th><th>Bluish-green</th><th>Green</th><th>Yellow-1</th><th>Yellow-2</th><th>Red</th></tr><tr><td>λ (nm)</td><td>404.7</td><td>435.8</td><td>491.6</td><td>546.1</td><td>577.0</td><td>579.1</td><td>623.4</td></tr></table>`,
  procedure: PRISM_PROC + String.raw`<h2>D. Graph</h2><ol class="steps"><li>Plot μ (y-axis) against λ (x-axis) and draw a smooth curve.</li></ol>` + PRISM_VNOTE,
  precautions: SPEC_COMMON_PRECAUTIONS + String.raw`<li>Set the minimum deviation separately for each line.</li><li>The prism must stay in the same place on the table during part C.</li>`,
  errors: String.raw`<li>Error in locating the exact minimum deviation position (the line moves very little near minimum deviation, which also makes the reading insensitive to this error).</li><li>Error in the angle A affects every value of μ.</li>`,
  viva: [
    ['What is angle of minimum deviation?', 'The smallest angle between the incident and emergent rays, obtained when the ray passes symmetrically through the prism.'],
    ['Why do we measure the angle of minimum deviation and not any deviation?', 'At minimum deviation r₁ = r₂ = A/2, so μ can be calculated from A and δm alone. Also the image is sharpest.'],
    ['Why does the refractive index depend on wavelength?', 'The speed of light in glass depends on frequency because of the interaction of light with the bound electrons of the medium (dispersion).'],
    ['Which colour is deviated most by a prism?', 'Violet, because μ is largest for violet.'],
    ['What is dispersive power?', 'ω = (μv − μr)/(μ − 1); it measures the ability of the material to spread out the colours relative to its mean deviation.'],
    ['Why is the reflected image method used for the angle A?', 'The angle between the beams reflected from the two faces is exactly 2A, independent of the small rotation of the prism.'],
    ['What is the effect of increasing A?', 'The deviation increases; if A is too large total internal reflection occurs at the second face and no light emerges.']],
  bench(root) { prismMuBench(root, 'exp5', 'mu'); }
});
EXPS.push({
  no: 6, id: 'exp6', group: 'Optics', short: 'Cauchy’s constants of a prism',
  title: 'Cauchy’s constants for the material of a prism',
  aim: 'To determine the value of Cauchy’s constants for the material of the given prism using a spectrometer.',
  apparatus: ['Spectrometer', 'Equilateral glass prism', 'Mercury vapour lamp', 'Spirit level', 'Reading lens', 'Graph paper'],
  theory: String.raw`
<h2>Cauchy’s dispersion formula</h2>
<p>For transparent media in the visible region (away from absorption bands) the refractive index varies with wavelength according to Cauchy’s formula</p>
<div class="eq">\[\mu = A + \frac{B}{\lambda^{2}} + \frac{C}{\lambda^{4}} + \cdots\]</div>
<p>The third term is very small, so to a good approximation</p>
<div class="eq">\[\mu = A + \frac{B}{\lambda^{2}}\]</div>
<p>where \(A\) and \(B\) are <b>Cauchy’s constants</b> of the material. \(A\) is dimensionless and \(B\) has the dimension of (length)². A graph of \(\mu\) against \(1/\lambda^{2}\) is a straight line with <b>slope B</b> and <b>intercept A</b> on the μ-axis.</p>
<h3>Measuring μ</h3>
<p>The refractive index for each line of the mercury spectrum is found by the minimum deviation method:</p>
<div class="eq">\[\mu = \frac{\sin\left(\dfrac{A_p+\delta_m}{2}\right)}{\sin\left(\dfrac{A_p}{2}\right)}\]</div>
<p>where \(A_p\) is the angle of the prism (not to be confused with Cauchy’s constant \(A\)). The constants can also be found from any two lines:</p>
<div class="eq">\[B = \frac{\mu_1-\mu_2}{\dfrac{1}{\lambda_1^2}-\dfrac{1}{\lambda_2^2}},\qquad A = \mu_1 - \frac{B}{\lambda_1^2}\]</div>
<p>Typical values for dense flint glass: \(A \approx 1.60\), \(B \approx 1\times10^{-10}\ \text{cm}^2\) (= 10⁴ nm²).</p>`,
  procedure: PRISM_PROC + String.raw`<h2>D. Graph and calculation</h2><ol class="steps"><li>Calculate 1/λ² for each line (use λ in µm so that 1/λ² is in µm⁻²).</li><li>Plot μ against 1/λ². Draw the best straight line. The intercept on the μ-axis is A; the slope is B (in µm²; 1 µm² = 10⁻⁸ cm²).</li></ol>` + PRISM_VNOTE,
  precautions: SPEC_COMMON_PRECAUTIONS + String.raw`<li>Use as many lines as possible, spread over the whole spectrum, for a reliable straight line.</li>`,
  errors: String.raw`<li>Small errors in δm give scatter of the points about the straight line.</li><li>Cauchy’s formula is approximate; the neglected C/λ⁴ term causes a slight curvature at short wavelengths.</li>`,
  viva: [
    ['State Cauchy’s formula.', 'μ = A + B/λ² + C/λ⁴ + …; usually μ = A + B/λ².'],
    ['What are the units of A and B?', 'A has no unit; B has the unit of (length)², e.g. cm² or m².'],
    ['What is the physical meaning of A?', 'It is the refractive index for infinitely long wavelength (1/λ² → 0).'],
    ['Is Cauchy’s formula valid near an absorption band?', 'No. Near absorption bands anomalous dispersion occurs and the formula fails.'],
    ['How do you get dμ/dλ from Cauchy’s constants?', 'dμ/dλ = −2B/λ³.'],
    ['Why is the graph of μ against λ a curve but μ against 1/λ² a straight line?', 'Because μ is linear in 1/λ² according to Cauchy’s formula.']],
  bench(root) { prismMuBench(root, 'exp6', 'cauchy'); }
});

/* ================= experiments 1 and 7 ================= */
function labelText(ctx, T, t, x, y, al = 'center', col) { ctx.fillStyle = col || T.muted; ctx.font = `11px ${T.sans}`; ctx.textAlign = al; ctx.textBaseline = 'middle'; ctx.fillText(t, x, y); }

/* ======================== EXP 1: Newton's rings ======================== */
EXPS.push({
  no: 1, id: 'exp1', group: 'Optics', short: 'Wavelength by Newton’s rings',
  title: 'Wavelength of light by Newton’s rings',
  aim: 'To determine the wavelength of given source of light by Newton’s ring method.',
  apparatus: ['Newton’s rings apparatus (plano-convex lens of large radius of curvature, optically plane glass plate, glass plate at 45°)', 'Sodium vapour lamp', 'Convex lens (to make the beam parallel)', 'Travelling microscope', 'Spherometer', 'Reading lens'],
  theory: String.raw`
<h2>Formation of the rings</h2>
<p>When a plano-convex lens of large radius of curvature \(R\) is placed with its convex surface on a plane glass plate, a thin film of air is enclosed between them. Its thickness is zero at the point of contact and increases outwards. When monochromatic light falls normally on this film, the light reflected from the upper and lower surfaces of the air film interferes. Points of equal thickness lie on circles about the point of contact, so the fringes are concentric circles: <b>Newton’s rings</b>.</p>
<h3>Thickness of the film</h3>
<p>If \(t\) is the thickness of the film at a distance \(r\) from the point of contact, from the geometry of the circle \(r^2 = (2R - t)\,t \approx 2Rt\) because \(t \ll R\). Hence</p>
<div class="eq">\[t = \frac{r^{2}}{2R}\]</div>
<h3>Condition for dark rings</h3>
<p>For normal incidence on an air film (\(\mu = 1\)), the path difference between the two reflected rays is \(2t\). The ray reflected from the glass plate (denser medium) suffers an extra phase change of \(\pi\), i.e. an extra path of \(\lambda/2\). The rings are dark when</p>
<div class="eq">\[2t = n\lambda \quad\Rightarrow\quad \frac{r_n^{2}}{R} = n\lambda \quad\Rightarrow\quad D_n^{2} = 4n\lambda R\]</div>
<p>where \(D_n\) is the diameter of the \(n\)-th dark ring. The centre (\(t = 0\)) is dark. The diameters of the dark rings are proportional to \(\sqrt{n}\), so the rings get closer as \(n\) increases.</p>
<h3>Working formula</h3>
<p>For the \((n+p)\)-th ring \(D_{n+p}^{2} = 4(n+p)\lambda R\). Subtracting,</p>
<div class="eq">\[\lambda = \frac{D_{n+p}^{2}-D_{n}^{2}}{4pR}\]</div>
<p>Using the difference removes the error due to a dust particle or imperfect contact at the centre (which shifts all \(D^2\) by the same amount). Equivalently, the graph of \(D_n^2\) against \(n\) is a straight line of slope \(4\lambda R\):</p>
<div class="eq">\[\lambda = \frac{\text{slope}}{4R}\]</div>
<h3>Radius of curvature</h3>
<p>\(R\) is measured with a spherometer: \(R = \dfrac{l^2}{6h} + \dfrac{h}{2}\), where \(l\) is the mean distance between the legs and \(h\) the height of the curved surface. In the virtual lab, \(R\) is given on the bench.</p>`,
  procedure: String.raw`
<h2>Setting up</h2>
<ol class="steps">
<li>Clean the plano-convex lens and the glass plate. Place the lens, convex side down, on the plane glass plate in the Newton’s rings box. Fix the glass plate at 45° above it.</li>
<li>Place the sodium lamp so that its light is made parallel by the convex lens and falls on the 45° plate. The plate reflects it vertically down on to the lens.</li>
<li>Place the travelling microscope above the arrangement. Focus it on the air film until the rings are seen clearly. Move the microscope so that the central dark spot is at the cross-wire and one cross-wire moves along a diameter.</li></ol>
<h2>Measuring the diameters</h2>
<ol class="steps">
<li>Move the microscope to the left from the centre, counting the dark rings, until the cross-wire is beyond the 20th dark ring (22nd, say).</li>
<li>Now move it back (to the right) and set the cross-wire on the <b>20th</b> dark ring. Note the reading of the microscope (main scale + vernier).</li>
<li>Continue moving <b>in the same direction</b> and note the readings for rings 18, 16, 14, … , 2 on the left.</li>
<li>Keep moving in the same direction across the centre. Note the readings for rings 2, 4, … , 20 on the right side.</li>
<li>For each ring, diameter \(D\) = difference between the right and left readings. Calculate \(D^2\).</li>
<li>Plot \(D^2\) (y-axis) against ring number \(n\) (x-axis). Find the slope and calculate \(\lambda = \text{slope}/4R\). Also calculate \(\lambda\) from pairs of rings using \(\lambda = (D^2_{n+p}-D^2_n)/4pR\).</li></ol>
<div class="vnote"><b>On the virtual bench:</b> move the microscope with the slider (coarse) and the buttons (fine, 0.001 cm). Put the vertical cross-wire on the centre of a dark ring and press <i>Record</i>. In a real lab you count the rings as you move; here the bench tells you the ring number when you record. The virtual microscope has a little backlash, just like a real one: keep turning the screw in one direction while taking readings.</div>`,
  precautions: String.raw`<li>Clean the lens and the plate; dust at the point of contact makes the centre bright or grey.</li><li>The light falling on the film should be parallel and normal (glass plate exactly at 45°).</li><li>Move the microscope screw in one direction only during a set of readings to avoid backlash error.</li><li>Set the cross-wire tangentially or on the middle of the dark ring, the same way for every ring.</li><li>Count the rings carefully; do not use rings very close to the centre, which are broad.</li>`,
  errors: String.raw`<li>Error in counting the rings or in setting the cross-wire.</li><li>Backlash of the microscope screw.</li><li>Error in the measurement of R by the spherometer.</li>`,
  viva: [
    ['Why are Newton’s rings circular?', 'The air film has constant thickness along circles centred at the point of contact, and each ring is a locus of constant thickness (fringe of equal thickness).'],
    ['Why is the centre of the ring system dark?', 'At the point of contact the thickness is zero; the only path difference is λ/2 due to reflection at the denser glass plate, so the two waves cancel.'],
    ['Why do the rings get closer as their order increases?', 'Because Dₙ ∝ √n, so the difference between successive diameters decreases with n.'],
    ['Why do we measure diameters and not radii?', 'The exact centre of the ring system is hard to locate; the difference of two readings on a diameter avoids this.'],
    ['Why is the formula with D²ₙ₊ₚ − D²ₙ used?', 'It cancels the constant error in D² due to dust or imperfect contact at the centre.'],
    ['What happens if a liquid of refractive index μ is put between the lens and plate?', 'D²ₙ = 4nλR/μ, so the rings contract. This is used to find μ of the liquid.'],
    ['What happens if white light is used?', 'Only a few coloured rings are seen near the centre because different wavelengths form rings at different places and they overlap.'],
    ['Why is a glass plate at 45° used?', 'It turns the horizontal beam vertically down so that light falls normally on the film, and lets the reflected light pass up to the microscope.'],
    ['What will you see in transmitted light?', 'A complementary ring system with a bright centre; the contrast is poor.'],
    ['What is backlash error?', 'The lag between the turning of the screw and the movement of the microscope when the direction of rotation is reversed; avoided by moving in one direction only.']],
  bench(root) {
    const R = apparatus('exp1');
    const P = { R: +R.range(85, 135).toFixed(1), xc: +R.range(4.35, 4.65).toFixed(4), y0: R.range(-0.04, 0.04), d0: R.range(0, 0.12) * 589.3e-6, lam: 589.3e-6, back: 0.003 };
    const Rmm = P.R * 10;
    const st = Object.assign({ s: +(P.xc + R.range(-0.08, 0.08)).toFixed(3), c: null, lastRecDir: 0, dir: 1 }, LS.get('exp1.st', {}));
    if (st.c == null) st.c = st.s;
    const { instr, notes } = benchCols(root);
    const setup = canvasView(h('div'), { aspect: 0.5, maxH: 220, draw: drawSetup, alt: 'Newton’s rings arrangement' });
    const row = h('div', { class: 'cols2' });
    const eyeBox = h('div'), sideBox = h('div', { class: 'stack' });
    row.append(eyeBox, sideBox);
    const eye = canvasView(eyeBox, { label: 'Through the travelling microscope', height: w => Math.min(w, 320), draw: drawEye, alt: 'Rings in microscope' });
    const vern = canvasView(sideBox, { label: 'Microscope scale · L.C. 0.001 cm', aspect: 0.26, minH: 88, maxH: 100, draw: (c, w, hh) => drawVernier(c, w, hh, st.s, { msd: 0.05, n: 50, major: 2, medium: 0, fmt: v => v.toFixed(1), vlabel: 10, span: 56, lcText: '0.001 cm', labelPx: 28 }) });
    const ro = readout('Microscope reading');
    sideBox.append(h('div', { class: 'readouts' }, ro.el, h('div', { class: 'ro' }, h('small', null, 'Radius of lens R'), h('b', null, P.R.toFixed(1) + ' cm'))));
    sideBox.append(h('p', { class: 'howto' }, 'R was measured with a spherometer. Least count of the microscope = 0.05 cm / 50 = 0.001 cm.'));
    const mic = slider({ label: 'Microscope screw', min: +(P.xc - 0.55).toFixed(3), max: +(P.xc + 0.55).toFixed(3), step: 0.001, snap: 0.0005, value: st.s, fmt: () => '', fine: [['−0.1', -0.1], ['−0.01', -0.01], ['−0.001', -0.001], ['+0.001', 0.001], ['+0.01', 0.01], ['+0.1', 0.1]], onInput: v => move(v) });
    const sb = statusBox(); sb.say('Move the cross-wire on to the centre of a dark ring, then press Record.');
    const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading');
    instr.append(card('Newton’s rings apparatus', 'sodium light, λ unknown', setup.wrap, row, mic.el, h('div', { class: 'recrow' }, btn, h('span', { class: 'small muted' }, 'The ring number and side are detected automatically.')), sb.el));
    function move(v) {
      const ds = v - st.s; if (ds > 0) st.dir = 1; else if (ds < 0) st.dir = -1;
      st.s = v; st.c = clamp(st.c, st.s, st.s + P.back); // carriage lags by up to the backlash
      LS.set('exp1.st', st); eye.redraw(); vern.redraw(); upd();
    }
    function upd() { ro.set(st.s.toFixed(3) + ' cm', Practice.on); }
    upd(); Practice.subs.add(upd); onCleanup(() => Practice.subs.delete(upd));
    const off = () => (st.c - P.xc) * 10; // mm, optical axis position of cross-wire relative to ring centre
    function drawSetup(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
      const by = H * 0.8, cx = W * 0.6;
      ctx.strokeStyle = T.muted; ctx.lineWidth = 1.2;
      ctx.fillStyle = T.grid; ctx.fillRect(cx - 90, by, 180, 10); ctx.strokeRect(cx - 90, by, 180, 10);
      ctx.beginPath(); ctx.moveTo(cx - 70, by); ctx.quadraticCurveTo(cx, by - 26, cx + 70, by); ctx.lineTo(cx + 70, by - 18); ctx.lineTo(cx - 70, by - 18); ctx.closePath(); ctx.fillStyle = T.accent; ctx.globalAlpha = .18; ctx.fill(); ctx.globalAlpha = 1; ctx.stroke();
      const py = H * 0.42; ctx.strokeStyle = T.accent; ctx.lineWidth = 3; ctx.beginPath(); ctx.moveTo(cx - 34, py + 34); ctx.lineTo(cx + 34, py - 34); ctx.stroke(); ctx.lineWidth = 1;
      ctx.fillStyle = T.ink; ctx.globalAlpha = .12; ctx.fillRect(cx - 14, 6, 28, py - 46); ctx.globalAlpha = 1; ctx.strokeStyle = T.ink; ctx.strokeRect(cx - 14, 6, 28, py - 46);
      const lx = W * 0.1; const g = ctx.createRadialGradient(lx, py, 2, lx, py, 24); g.addColorStop(0, '#ffd257'); g.addColorStop(1, 'rgba(255,210,87,0)'); ctx.fillStyle = g; ctx.beginPath(); ctx.arc(lx, py, 24, 0, 7); ctx.fill();
      const llx = W * 0.27; ctx.strokeStyle = T.muted; ctx.beginPath(); ctx.ellipse(llx, py, 5, 26, 0, 0, 7); ctx.stroke();
      ctx.strokeStyle = T.sodium; ctx.globalAlpha = .8; ctx.lineWidth = 1.5;
      [-10, 10].forEach(d => { ctx.beginPath(); ctx.moveTo(llx, py + d); ctx.lineTo(cx + d, py + d); ctx.lineTo(cx + d, by - 14); ctx.stroke(); });
      ctx.globalAlpha = 1; ctx.lineWidth = 1;
      labelText(ctx, T, 'sodium lamp', lx, py + 36); labelText(ctx, T, 'convex lens', llx, py + 38); labelText(ctx, T, 'glass plate 45°', cx + 42, py - 30, 'left');
      labelText(ctx, T, 'travelling microscope', cx + 22, 20, 'left'); labelText(ctx, T, 'plano-convex lens on glass plate', cx, by + 22);
    }
    const off2 = document.createElement('canvas'); const N = 260; off2.width = off2.height = N; const octx = off2.getContext('2d'); const img = octx.createImageData(N, N);
    function drawEye(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, H);
      const cx = W / 2, cy = H / 2, Rr = Math.min(W, H) / 2 - 3, F = 0.62; // mm half-field
      const x0 = off(); const d = img.data;
      for (let j = 0; j < N; j++) for (let i = 0; i < N; i++) {
        const u = (i - N / 2) / (N / 2), v = (j - N / 2) / (N / 2); const k = (j * N + i) * 4;
        const rr = u * u + v * v; if (rr > 1.02) { d[k + 3] = 0; continue; }
        const x = x0 + u * F, y = v * F - P.y0; const r2 = x * x + y * y;
        const t = r2 / (2 * Rmm) + P.d0;
        const I = Math.sin(2 * Math.PI * t / P.lam) ** 2; const vig = 1 - 0.35 * rr;
        const b = (0.07 + 0.88 * I) * vig;
        d[k] = 255 * b; d[k + 1] = 196 * b; d[k + 2] = 52 * b; d[k + 3] = 255;
      }
      octx.putImageData(img, 0, 0);
      ctx.save(); ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.clip(); ctx.imageSmoothingEnabled = true; ctx.drawImage(off2, cx - Rr, cy - Rr, 2 * Rr, 2 * Rr);
      ctx.strokeStyle = 'rgba(20,20,20,.85)'; ctx.lineWidth = 1.2; ctx.beginPath(); ctx.moveTo(cx, cy - Rr); ctx.lineTo(cx, cy + Rr); ctx.moveTo(cx - Rr, cy); ctx.lineTo(cx + Rr, cy); ctx.stroke(); ctx.restore();
      ctx.strokeStyle = T.muted; ctx.lineWidth = 2; ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.stroke(); ctx.lineWidth = 1;
    }
    btn.addEventListener('click', async () => {
      const x = off(); const r2 = x * x + P.y0 * P.y0; const mf = (r2 / Rmm + 2 * P.d0) / P.lam; const m = Math.round(mf);
      if (m < 1) return sb.say('The cross-wire is at the central dark spot. Move outwards to the rings.', 'warn');
      if (Math.abs(mf - m) > 0.2) return sb.say('The cross-wire is not on the middle of a dark ring. Use the fine buttons to set it on the dark ring.', 'warn');
      if (m > 30) return sb.say('Use rings up to about the 25th; outer rings are too close together.', 'warn');
      const side = x < 0 ? 'L' : 'R';
      const truth = rnd(st.s, 3);
      const v = await askReading({ title: 'Read the microscope', prompt: 'Main scale reading (0.05 cm divisions) + coinciding vernier division × 0.001 cm. Enter the reading in cm, e.g. 4.523.', truth, tol: 0.00051, unit: 'cm', hint: vernierHint(truth, 0.05, 0.001, z => z.toFixed(3) + ' cm') });
      if (v == null) return;
      const i = tbl.rows.findIndex(r => r.m === m);
      const patch = side === 'L' ? { L: truth } : { R: truth };
      if (i < 0) tbl.add(Object.assign({ m }, patch)); else tbl.update(i, patch);
      let note = '';
      if (st.lastRecDir && st.dir !== st.lastRecDir) note = ' You reversed the direction of the screw since the last reading; a backlash error may enter.';
      st.lastRecDir = st.dir; LS.set('exp1.st', st);
      sb.say(`Ring ${m} (${side === 'L' ? 'left' : 'right'} side): ${truth.toFixed(3)} cm recorded.` + note, note ? 'warn' : 'ok');
    });
    const D = r => r.L != null && r.R != null ? Math.abs(r.R - r.L) : null;
    const tbl = new ObsTable({
      key: 'exp1.t', title: 'Table. Diameters of dark rings', onChange: analyse, deletable: true, sort: (a, b) => b.m - a.m,
      columns: [{ label: 'Ring no.<br>n', k: 'm' }, { label: 'Left reading<br>(cm)', k: 'L', fmt: v => v.toFixed(3) }, { label: 'Right reading<br>(cm)', k: 'R', fmt: v => v.toFixed(3) },
        { label: 'Diameter D<br>(cm)', v: D, fmt: v => v.toFixed(3), calc: 1 }, { label: 'D²<br>(cm²)', v: r => D(r) == null ? null : D(r) ** 2, fmt: v => v.toFixed(4), calc: 1 }]
    });
    const plot = plotCard('Graph: D² against ring number n', { get: () => { const p = tbl.rows.filter(r => D(r) != null).map(r => [r.m, D(r) ** 2]); return { xLabel: 'Ring number n', yLabel: 'D² (cm²)', series: [{ pts: p, fit: p.length >= 2 ? linreg(p) : null }], zeroX: true, zeroY: true, xDec: 0 }; } });
    const res = resultBox();
    function analyse() {
      plot.view.redraw();
      const pts = tbl.rows.filter(r => D(r) != null).map(r => [r.m, D(r) ** 2]).sort((a, b) => a[0] - b[0]);
      let html = '<h4>Calculation</h4>';
      if (pts.length < 2) { res.set(html + `<p class="muted small">Record left and right readings of at least two rings (preferably 8–10 rings, e.g. n = 2, 4, …, 20). R = ${P.R.toFixed(1)} cm.</p>`); return; }
      const f = linreg(pts); const lamG = f.m / (4 * P.R) * 1e7; // nm
      const map = new Map(pts); let pairs = [], p = 0;
      for (const pp of [10, 8, 6, 5, 4, 3, 2, 1]) { const ps = pts.filter(([n]) => map.has(n + pp)).map(([n]) => [n, pp, (map.get(n + pp) - map.get(n)) / (4 * pp * P.R) * 1e7]); if (ps.length >= Math.min(3, pts.length - 1)) { pairs = ps; p = pp; break; } }
      html += `<div class="calc-lines"><div>Slope of D² vs n = ${f.m.toFixed(5)} cm² &nbsp;(${pts.length} rings)</div><div>λ = slope / 4R = ${f.m.toFixed(5)} / (4 × ${P.R.toFixed(1)}) = ${(f.m / (4 * P.R)).toExponential(4)} cm = <b>${lamG.toFixed(1)} nm</b></div>`;
      if (pairs.length) { html += `<div>Pairs with p = ${p}: ` + pairs.slice(0, 8).map(([n, pp, l]) => `n=${n}: ${l.toFixed(1)}`).join('; ') + (pairs.length > 8 ? '; …' : '') + ` nm</div><div>Mean λ (pairs) = <b>${mean(pairs.map(q => q[2])).toFixed(1)} nm</b></div>`; }
      html += `</div><p class="big" style="margin-top:10px">Wavelength of sodium light λ = <b>${lamG.toFixed(1)} nm</b> (${(lamG * 10).toFixed(0)} Å)</p><p class="small muted">Standard value 589.3 nm; percentage error ${pctErr(lamG, 589.3).toFixed(2)}%.</p>`;
      res.set(html);
    }
    notes.append(card('Lab notebook', 'readings are saved in this browser', tbl.el), plot.el, card('Result', '', res.el));
    analyse();
  }
});

/* ======================== EXP 7: Laurent half-shade polarimeter ======================== */
EXPS.push({
  no: 7, id: 'exp7', group: 'Optics', short: 'Specific rotation of sugar (polarimeter)',
  title: 'Specific rotation of sugar solution by Laurent’s half-shade polarimeter',
  aim: 'To determine the specific rotation of sugar solution using Laurent half-shade polarimeter.',
  apparatus: ['Laurent’s half-shade polarimeter with a 20 cm (2 dm) tube', 'Sodium vapour lamp', 'Cane sugar', 'Physical balance and weight box', 'Measuring flask (100 cc), beakers, distilled water'],
  theory: String.raw`
<h2>Optical activity</h2>
<p>When plane-polarised light passes through certain substances (quartz, sugar solution, turpentine), the plane of vibration is rotated. Such substances are <b>optically active</b>. If the rotation is clockwise as seen by the observer facing the light, the substance is <b>dextro-rotatory</b> (cane sugar); otherwise it is <b>laevo-rotatory</b> (fruit sugar).</p>
<p>For a solution, the angle of rotation \(\theta\) is proportional to the length \(l\) of the solution traversed and to its concentration \(c\) (Biot’s laws). The <b>specific rotation</b> \(S\) at a given temperature and wavelength is the rotation produced by a 1 dm length of solution containing 1 g of active substance per cc:</p>
<div class="eq">\[S_{t}^{\lambda} = \frac{\theta}{l\,c}\]</div>
<p>with \(\theta\) in degrees, \(l\) in decimetres and \(c\) in g/cc. Its unit is degree dm⁻¹ (g/cc)⁻¹. For cane sugar with sodium light at 20 °C, \(S \approx 66.5\).</p>
<h2>Laurent’s half-shade device</h2>
<p>Finding the position of complete darkness with a single analyser is not precise, because the eye cannot judge the exact minimum. In the half-shade polarimeter, half of the field is covered by a thin quartz <b>half-wave plate</b> cut with its optic axis parallel to the faces, the other half by plain glass. If the vibration from the polariser makes an angle \(\alpha\) with the optic axis, the light through the quartz half emerges with its vibration turned to \(-\alpha\). The two halves therefore carry vibrations at an angle \(2\alpha\) to each other.</p>
<p>When the analyser is perpendicular to the bisector of these two directions, the two halves are <b>equally dark</b> (dim). A very small rotation of the analyser makes one half brighter and the other darker, so this position can be set very sensitively. (The position 90° away gives equal <b>brightness</b>, which is much less sensitive.)</p>
<h3>Working formula</h3>
<p>Let the analyser reading for equal darkness be \(\theta_1\) with water in the tube and \(\theta_2\) with the solution. Then \(\theta = \theta_2 - \theta_1\) and</p>
<div class="eq">\[S = \frac{\theta}{l\,c},\qquad c = \frac{W}{V}\ \text{g/cc}\]</div>
<p>A graph of \(\theta\) against \(c\) is a straight line through the origin with slope \(Sl\). An unknown concentration can be read from this graph.</p>`,
  procedure: String.raw`
<h2>Preparing the solutions</h2>
<ol class="steps">
<li>Weigh 25 g of cane sugar, dissolve it in distilled water and make the volume exactly 100 cc in a measuring flask: \(c = 0.25\) g/cc.</li>
<li>Prepare 0.20, 0.15, 0.10 and 0.05 g/cc solutions by dilution (or by weighing 20, 15, 10, 5 g and making each up to 100 cc).</li></ol>
<h2>Zero reading (water)</h2>
<ol class="steps">
<li>Clean the polarimeter tube, fill it with distilled water so that no air bubble remains, and close it. Place it in the polarimeter.</li>
<li>Illuminate the polariser with the sodium lamp. Focus the eyepiece until the vertical dividing line of the half-shade is sharp.</li>
<li>Rotate the analyser until both halves of the field are <b>equally dark</b>. Note the reading of the circular scale and vernier. Take three readings, approaching the position alternately from both sides, and find the mean \(\theta_1\).</li></ol>
<h2>Solutions</h2>
<ol class="steps">
<li>Fill the tube with the 0.05 g/cc solution. The field is no longer equally dark. Rotate the analyser to restore equal darkness and note three readings; mean \(\theta_2\).</li>
<li>Repeat for each concentration. Rinse the tube with the next solution before filling.</li>
<li>Calculate \(\theta = \theta_2 - \theta_1\) and \(S = \theta/(lc)\) for each. Plot \(\theta\) against \(c\).</li>
<li>Fill the tube with the unknown solution, find its rotation and read its concentration from the graph.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> choose what is in the tube, then rotate the analyser with the slider and the fine buttons (±1°, ±0.1°). The left half of the field is seen through the quartz plate. Press <i>Record</i> when both halves look equally dark. Three readings per solution are kept.</div>`,
  precautions: String.raw`<li>There should be no air bubble in the tube; it disturbs the field.</li><li>Use the equal-darkness position, not the equal-brightness position.</li><li>Clean and rinse the tube with the solution to be used next.</li><li>Use monochromatic (sodium) light; specific rotation depends on wavelength.</li><li>The temperature should remain constant; S changes slightly with temperature.</li>`,
  errors: String.raw`<li>Error in judging equal darkness.</li><li>Error in preparing the concentration (weighing, volume).</li><li>Temperature variations.</li>`,
  viva: [
    ['What is specific rotation?', 'The rotation of the plane of polarisation produced by a 1 dm length of solution containing 1 g of the active substance per cc, at a given temperature and wavelength.'],
    ['What is the function of the half-shade plate?', 'It divides the field into two halves with vibrations inclined to each other, so that the position of the analyser can be set precisely by matching the two halves.'],
    ['Why is the equal-darkness position preferred?', 'At low intensity the eye detects small differences in brightness more easily, so the setting is more sensitive.'],
    ['What is the thickness of the half-wave plate?', 't = λ / [2(μe − μo)] for sodium light; it introduces a path difference of λ/2 between the e- and o-rays.'],
    ['Why is sodium light used?', 'Specific rotation depends on wavelength (approximately ∝ 1/λ²); with white light each colour would be rotated differently and the halves cannot be matched.'],
    ['What are dextro- and laevo-rotatory substances?', 'Dextro: rotates the plane clockwise as seen facing the light (cane sugar). Laevo: anticlockwise (fruit sugar).'],
    ['On what factors does the rotation depend?', 'Length of solution, concentration, wavelength, temperature and the nature of the substance.'],
    ['What is a saccharimeter?', 'A polarimeter calibrated to give the percentage of sugar directly.'],
    ['Why do we take a reading with water first?', 'Water is optically inactive; its reading gives the zero for the analyser so that the rotation due to sugar alone is found.']],
  bench(root) {
    const R = apparatus('exp7');
    const P = { pol: +R.range(18, 62).toFixed(2), eps: 3.2, S: 66.5 * (1 + R.range(-0.006, 0.006)), l: 2.0 };
    const samples = [{ id: 'w', name: 'Distilled water', c: 0 }].concat([5, 10, 15, 20, 25].map((g, k) => ({ id: 's' + g, name: `Solution ${k + 1} (${g} g / 100 cc)`, c: g / 100, ca: g / 100 * (1 + R.range(-0.008, 0.008)) })), [{ id: 'x', name: 'Unknown solution X', c: null, ca: +R.range(0.07, 0.22).toFixed(3) }]);
    samples[0].ca = 0;
    const st = Object.assign({ a: +R.range(0, 360).toFixed(1), sample: 'w' }, LS.get('exp7.st', {}));
    const theta = () => { const s = samples.find(x => x.id === st.sample); return P.S * P.l * s.ca; };
    const { instr, notes } = benchCols(root);
    const scheme = canvasView(h('div'), { aspect: 0.22, minH: 96, maxH: 130, draw: drawScheme, alt: 'Polarimeter arrangement' });
    const row = h('div', { class: 'cols2' }); const eyeBox = h('div'), side = h('div', { class: 'stack' }); row.append(eyeBox, side);
    const eye = canvasView(eyeBox, { label: 'Field of view (half-shade)', height: w => Math.min(w, 300), draw: drawEye, alt: 'Half-shade field' });
    const vern = canvasView(side, { label: 'Analyser scale · L.C. 0.1°', aspect: 0.26, minH: 88, maxH: 100, draw: (c, w, hh) => drawVernier(c, w, hh, norm360(st.a), { msd: 1, n: 10, major: 5, fmt: v => String(Math.round(norm360(v))), vlabel: 5, span: 30, lcText: '0.1°', title: 'main scale 1° / vernier 10 div' }) });
    const ro = readout('Analyser reading');
    side.append(h('div', { class: 'readouts' }, ro.el, h('div', { class: 'ro' }, h('small', null, 'Tube length'), h('b', null, '2.0 dm'))));
    const sampleSel = selectBox({ label: 'In the tube', options: samples.map(s => [s.id, s.name]), value: st.sample, onChange: v => { st.sample = v; save(); eye.redraw(); } });
    side.append(sampleSel.el);
    const an = slider({ label: 'Analyser (rotate)', min: 0, max: 360, step: 0.05, value: st.a, fmt: () => '', fine: [['−10°', -10], ['−1°', -1], ['−0.1°', -0.1], ['+0.1°', 0.1], ['+1°', 1], ['+10°', 10]], onInput: v => { st.a = norm360(v); save(); eye.redraw(); vern.redraw(); upd(); } });
    const sb = statusBox(); sb.say('Rotate the analyser until both halves are equally dark, then press Record.');
    const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading');
    instr.append(card('Laurent’s half-shade polarimeter', 'sodium light', scheme.wrap, row, an.el, h('div', { class: 'recrow' }, btn), sb.el));
    function save() { LS.set('exp7.st', st); }
    function upd() { ro.set(norm360(st.a).toFixed(1) + '°', Practice.on); }
    upd(); Practice.subs.add(upd); onCleanup(() => Practice.subs.delete(upd));
    const halves = () => { const th = theta(); const p1 = P.pol - 90 - P.eps + th, p2 = P.pol - 90 + P.eps + th; return [Math.cos((st.a - p1) * DEG) ** 2, Math.cos((st.a - p2) * DEG) ** 2]; };
    function drawEye(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, H);
      const cx = W / 2, cy = H / 2, Rr = Math.min(W, H) / 2 - 3; const G = 0.7 / Math.sin(P.eps * DEG) ** 2;
      const [I1, I2] = halves(); const map = I => 1 - Math.exp(-I * G);
      const col = b => `rgb(${Math.round(255 * b)},${Math.round(205 * b)},${Math.round(70 * b)})`;
      ctx.save(); ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.clip();
      ctx.fillStyle = col(map(I1)); ctx.fillRect(cx - Rr, cy - Rr, Rr, 2 * Rr);
      ctx.fillStyle = col(map(I2)); ctx.fillRect(cx, cy - Rr, Rr, 2 * Rr);
      const vg = ctx.createRadialGradient(cx, cy, Rr * 0.5, cx, cy, Rr); vg.addColorStop(0, 'rgba(0,0,0,0)'); vg.addColorStop(1, 'rgba(0,0,0,.45)'); ctx.fillStyle = vg; ctx.fillRect(cx - Rr, cy - Rr, 2 * Rr, 2 * Rr);
      ctx.strokeStyle = 'rgba(0,0,0,.6)'; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.moveTo(cx, cy - Rr); ctx.lineTo(cx, cy + Rr); ctx.stroke(); ctx.restore();
      ctx.strokeStyle = T.muted; ctx.lineWidth = 2; ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.stroke(); ctx.lineWidth = 1;
    }
    function drawScheme(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
      const y = H * 0.45; const parts = [['lamp', 0.06], ['polariser', 0.2], ['half-shade', 0.32], ['tube (2 dm)', 0.56], ['analyser', 0.8], ['eye', 0.93]];
      ctx.strokeStyle = T.sodium; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(W * 0.06, y); ctx.lineTo(W * 0.92, y); ctx.stroke(); ctx.lineWidth = 1;
      const g = ctx.createRadialGradient(W * 0.06, y, 2, W * 0.06, y, 20); g.addColorStop(0, '#ffd257'); g.addColorStop(1, 'rgba(255,210,87,0)'); ctx.fillStyle = g; ctx.beginPath(); ctx.arc(W * 0.06, y, 20, 0, 7); ctx.fill();
      ctx.fillStyle = T.accent; ctx.globalAlpha = .2; ctx.fillRect(W * 0.42, y - 12, W * 0.28, 24); ctx.globalAlpha = 1; ctx.strokeStyle = T.accent; ctx.strokeRect(W * 0.42, y - 12, W * 0.28, 24);
      [0.2, 0.8].forEach(f => { ctx.strokeStyle = T.ink; ctx.strokeRect(W * f - 7, y - 18, 14, 36); });
      ctx.fillStyle = T.ink; ctx.globalAlpha = .25; ctx.fillRect(W * 0.32 - 3, y - 18, 6, 18); ctx.globalAlpha = 1; ctx.strokeStyle = T.ink; ctx.strokeRect(W * 0.32 - 3, y - 18, 6, 36);
      parts.forEach(([t, f]) => labelText(ctx, T, t, W * f, y + 32));
    }
    btn.addEventListener('click', async () => {
      const th = theta(); const dark = P.pol + th; const dd = Math.abs(((st.a - dark) % 180 + 270) % 180 - 90); // distance to equal darkness (mod 180)
      const bright = Math.abs(((st.a - dark - 90) % 180 + 270) % 180 - 90);
      if (bright < 6) return sb.say('Both halves are equally <b>bright</b>. This position is not sensitive. Turn the analyser about 90° to the equal-darkness position.', 'warn');
      if (dd > 0.5) return sb.say('The two halves are not equally dark yet. Turn the analyser slowly (±0.1°) until they match.', 'warn');
      const truth = rnd(norm360(st.a), 1);
      const v = await askReading({ title: 'Read the analyser scale', prompt: 'Main scale in degrees + coinciding vernier division × 0.1°.', truth, tol: 0.051, unit: '°', hint: vernierHint(truth, 1, 0.1, z => z.toFixed(1) + '°') });
      if (v == null) return;
      const i = tbl.rows.findIndex(r => r.id === st.sample); const rr = (tbl.rows[i].r || []).slice(); rr.push(truth); while (rr.length > 3) rr.shift();
      tbl.update(i, { r: rr });
      sb.say(`Recorded ${truth.toFixed(1)}° for ${samples.find(s => s.id === st.sample).name}.` + (rr.length >= 3 ? ' (Three readings taken; another reading replaces the oldest.)' : ''), 'ok');
    });
    const m180 = (x, ref) => ref + (((x - ref) % 180 + 270) % 180 - 90);
    const meanR = r => { if (!r.r || !r.r.length) return null; const ref = r.r[0]; return mean(r.r.map(x => m180(x, ref))); };
    const rot = (r, rows) => { if (r.id === 'w') return null; const w = rows.find(x => x.id === 'w'); const a = meanR(w), b = meanR(r); if (a == null || b == null) return null; return ((b - a) % 180 + 270) % 180 - 90; };
    const tbl = new ObsTable({
      key: 'exp7.t', title: 'Table. Analyser readings for equal darkness', onChange: analyse,
      template: () => samples.map(s => ({ id: s.id, name: s.name.replace(/ \(.*/, ''), c: s.c, r: [] })),
      columns: [{ label: 'Tube contains', k: 'name', cls: 'lbl' }, { label: 'c<br>(g/cc)', k: 'c', fmt: v => v.toFixed(2) },
        ...[0, 1, 2].map(k => ({ label: `R<sub>${k + 1}</sub>`, v: r => r.r && r.r[k] != null ? r.r[k] : null, fmt: v => v.toFixed(1) + '°' })),
        { label: 'Mean', v: meanR, fmt: v => norm360(v).toFixed(2) + '°', calc: 1 }, { label: 'θ = mean − θ<sub>water</sub>', v: (r, i, rows) => rot(r, rows), fmt: v => v.toFixed(2) + '°', calc: 1 },
        { label: 'S = θ/(lc)', v: (r, i, rows) => { const t = rot(r, rows); return t == null || !r.c ? null : t / (P.l * r.c); }, fmt: v => v.toFixed(1), calc: 1 }]
    });
    const plot = plotCard('Graph: rotation θ against concentration c', { get: () => { const p = tbl.rows.filter(r => r.c && rot(r, tbl.rows) != null).map(r => [r.c, rot(r, tbl.rows)]); const f = p.length ? linregOrigin(p) : null; const x = tbl.rows.find(r => r.id === 'x'); const tx = x ? rot(x, tbl.rows) : null; return { xLabel: 'Concentration c (g/cc)', yLabel: 'Rotation θ (degree)', series: [{ pts: p, fit: f }], zeroX: true, zeroY: true, hlines: tx != null ? [{ y: tx, label: 'unknown X' }] : [], xDec: 2 }; } });
    const res = resultBox();
    function analyse() {
      plot.view.redraw();
      const p = tbl.rows.filter(r => r.c && rot(r, tbl.rows) != null).map(r => [r.c, rot(r, tbl.rows)]);
      let html = '<h4>Calculation</h4>';
      if (meanR(tbl.rows[0]) == null) { res.set(html + '<p class="small muted">Start with distilled water in the tube to get the zero reading.</p>'); return; }
      if (!p.length) { res.set(html + `<p class="small muted">Zero reading (water) = ${norm360(meanR(tbl.rows[0])).toFixed(2)}°. Now fill the tube with the solutions.</p>`); return; }
      const f = linregOrigin(p); const S = f.m / P.l;
      html += `<div class="calc-lines"><div>Zero reading (water) θ₁ = ${norm360(meanR(tbl.rows[0])).toFixed(2)}°</div><div>Slope of θ vs c (line through origin) = ${f.m.toFixed(2)}° per g/cc</div><div>S = slope / l = ${f.m.toFixed(2)} / 2.0 = <b>${S.toFixed(1)}</b> degree dm⁻¹ (g/cc)⁻¹</div><div>Mean of individual values of S = ${mean(p.map(q => q[1] / (P.l * q[0]))).toFixed(1)}</div>`;
      const x = tbl.rows.find(r => r.id === 'x'); const tx = x ? rot(x, tbl.rows) : null;
      if (tx != null) html += `<div>Unknown X: θ = ${tx.toFixed(2)}° → c = θ/(S l) = <b>${(tx / (S * P.l)).toFixed(3)} g/cc</b></div>`;
      html += `</div><p class="big" style="margin-top:10px">Specific rotation of cane sugar S = <b>${S.toFixed(1)}°</b> dm⁻¹ (g/cc)⁻¹</p><p class="small muted">Standard value at 20 °C for sodium light: 66.5; error ${pctErr(S, 66.5).toFixed(1)}%.</p>`;
      res.set(html);
    }
    notes.append(card('Lab notebook', 'l = 2.0 dm', tbl.el), plot.el, card('Result', '', res.el)); analyse();
  }
});

/* ================= experiments 8–10 ================= */
const E_CH = 1.602177e-19, EM = 1.75882e11, MU0 = 4e-7 * Math.PI;

/* ======================== EXP 8: Millikan ======================== */
EXPS.push({
  no: 8, id: 'exp8', group: 'Modern physics', short: 'Charge of electron (Millikan)',
  title: 'Charge of an electron by Millikan’s oil-drop method',
  aim: 'To determine the charge of an electron by Millikan’s method.',
  apparatus: ['Millikan’s oil-drop apparatus (two horizontal parallel plates, d = 5.00 mm, with a small hole in the upper plate)', 'Atomiser with non-volatile oil (ρ = 886 kg m⁻³)', 'Viewing microscope with graticule', 'Variable DC high-voltage supply (0–600 V) with voltmeter and reversing switch', 'Light source, stopwatch', 'Ionising source (X-ray / radioactive) to change the charge'],
  theory: String.raw`
<h2>Principle</h2>
<p>Tiny oil drops sprayed from an atomiser become charged by friction. A drop of radius \(a\) falling through air quickly reaches a constant <b>terminal velocity</b> \(v\), when its effective weight is balanced by the viscous drag given by Stokes’ law:</p>
<div class="eq">\[\frac{4}{3}\pi a^{3}(\rho-\sigma)g = 6\pi\eta a v \qquad\Rightarrow\qquad a = \sqrt{\frac{9\eta v}{2(\rho-\sigma)g}}\]</div>
<p>where \(\rho\) is the density of oil, \(\sigma\) that of air and \(\eta\) the viscosity of air. The velocity is found by timing the fall through a known distance \(s\) (between two graticule lines): \(v = s/t\).</p>
<h3>Balancing method</h3>
<p>If a potential difference \(V\) is applied between the plates (separation \(d\)), the drop experiences a force \(qE = qV/d\). The voltage is adjusted until the drop stays still. Then</p>
<div class="eq">\[q\,\frac{V}{d} = \frac{4}{3}\pi a^{3}(\rho-\sigma)g\qquad\Rightarrow\qquad q = \frac{4\pi a^{3}(\rho-\sigma)g\,d}{3V}\]</div>
<p>Combining with the expression for \(a\):</p>
<div class="eq">\[q = \frac{18\pi d}{V}\sqrt{\frac{\eta^{3}v^{3}}{2(\rho-\sigma)g}}\]</div>
<h3>Correction to Stokes’ law</h3>
<p>For drops of radius about 1 µm, comparable to the mean free path of air molecules, Stokes’ law over-estimates the drag. Cunningham’s correction replaces \(\eta\) by</p>
<div class="eq">\[\eta' = \frac{\eta}{1 + \dfrac{b}{p\,a}}\qquad (b = 8.2\times10^{-3}\ \text{Pa m},\ p = \text{air pressure})\]</div>
<p>The radius is first found with \(\eta\), then recalculated with \(\eta'\) (two or three iterations).</p>
<h3>Quantisation of charge</h3>
<p>The charges \(q\) found on different drops (or on the same drop after ionising the air) are always whole-number multiples of a smallest charge \(e\): \(q = ne\). Dividing each \(q\) by the nearest integer \(n\) gives \(e\). Millikan’s result: \(e = 1.602\times10^{-19}\) C.</p>
<table><tr><th>Constant</th><th>Value used</th></tr><tr><td>Plate separation d</td><td>5.00 mm</td></tr><tr><td>Distance between timing lines s</td><td>1.00 mm</td></tr><tr><td>Viscosity of air η (23 °C)</td><td>1.832 × 10⁻⁵ N s m⁻²</td></tr><tr><td>Density of oil ρ</td><td>886 kg m⁻³</td></tr><tr><td>Density of air σ</td><td>1.2 kg m⁻³</td></tr><tr><td>g</td><td>9.78 m s⁻²</td></tr><tr><td>Pressure p</td><td>1.013 × 10⁵ Pa</td></tr></table>`,
  procedure: String.raw`
<h2>Adjustments</h2>
<ol class="steps">
<li>Level the plates with the levelling screws and a spirit level so that the electric field is vertical.</li>
<li>Insert a thin wire through the hole in the upper plate and focus the microscope on it. Remove the wire. Switch on the lamp; the drops will appear as bright points of light on a dark background.</li></ol>
<h2>Observations</h2>
<ol class="steps">
<li>Spray oil with the atomiser above the upper plate. Some drops pass through the hole. Select a drop that falls slowly (a small drop).</li>
<li>Switch on the field and adjust the voltage until the drop is held stationary. If it moves the wrong way, reverse the polarity. Note the balancing voltage \(V\). Take it three times.</li>
<li>Bring the drop above the upper timing line with the field. Switch the field off and start the stopwatch as the drop crosses the upper line; stop it as it crosses the lower line (s = 1.00 mm). Repeat the timing 3–5 times, lifting the drop back each time with the field.</li>
<li>Repeat for several drops (at least 5). The charge on a drop can also be changed by ionising the air (X-ray), giving a new balancing voltage for the same drop.</li>
<li>Calculate \(v\), \(a\) (with Cunningham’s correction), \(q\) and \(n = q/e\) rounded to the nearest integer, and finally \(e = q/n\).</li></ol>
<div class="vnote"><b>On the virtual bench:</b> the microscope image is shown erect for convenience (a real microscope shows the drop moving upward while it falls). Press the space bar or the Start/Stop button to use the stopwatch. Use the speed setting (×2, ×4) to save time; the stopwatch shows the true (simulated) time. The drop jitters slightly: this is Brownian motion, a real source of error.</div>`,
  precautions: String.raw`<li>The plates must be horizontal and the field uniform.</li><li>Choose small, slowly moving drops; fast drops give large timing errors.</li><li>Time each drop several times and take the mean.</li><li>Do not touch the plates; high voltage. Switch off the supply when not in use.</li><li>Avoid air currents (close the chamber) and keep the temperature constant.</li>`,
  errors: String.raw`<li>Brownian motion makes individual fall times scatter.</li><li>Reaction time in starting/stopping the watch.</li><li>Uncertainty in the viscosity of air and the correction to Stokes’ law.</li>`,
  viva: [
    ['Why is oil used and not water?', 'Oil has a very low vapour pressure, so the drop does not evaporate and its mass stays constant during the measurements.'],
    ['How do the drops get charged?', 'By friction in the nozzle of the atomiser. The charge can be changed by ionising the air with X-rays or a radioactive source.'],
    ['What is terminal velocity?', 'The constant velocity reached when the net downward force (weight − upthrust) equals the viscous drag.'],
    ['Why do we measure the free fall?', 'To find the radius (hence mass) of the drop using Stokes’ law.'],
    ['What does Millikan’s experiment prove?', 'That electric charge is quantised: every charge is an integral multiple of e = 1.6 × 10⁻¹⁹ C.'],
    ['Why is Cunningham’s correction needed?', 'For very small drops the air cannot be treated as a continuous medium; Stokes’ law over-estimates the drag.'],
    ['Why should the drop be small?', 'Small drops carry few charges, so the integer n can be identified clearly, and they move slowly so timing is accurate.'],
    ['What is Brownian motion and how does it affect the result?', 'The random motion of a small particle due to collisions with air molecules. It makes the fall time fluctuate, so several timings are averaged.'],
    ['What would happen if the plates were not horizontal?', 'The field would not be vertical; the drop would drift sideways out of view and the force balance would be wrong.']],
  bench(root) {
    const K = { eta: 1.832e-5, rho: 886, sig: 1.2, g: 9.78, d: 5e-3, s: 1e-3, b: 8.2e-3, p: 1.013e5, kT: 1.381e-23 * 296 };
    const etaC = a => K.eta / (1 + K.b / (K.p * a));
    const mgOf = a => 4 / 3 * Math.PI * a ** 3 * (K.rho - K.sig) * K.g;
    const st = Object.assign({ V: 300, on: false, up: true, speed: 1, cur: -1, corr: true }, LS.get('exp8.st', {}));
    let drop = null, watch = { t: 0, run: false }, bg = [];
    const save = () => LS.set('exp8.st', st);
    function makeDrop(a, n) {
      if (!a) { for (let k = 0; k < 50; k++) { a = (0.9 + Math.random() * 0.45) * 1e-6; const v1 = mgOf(a) * K.d / E_CH; const lo = Math.ceil(v1 / 560), hi = Math.floor(v1 / 140); if (hi >= lo) { n = lo + Math.floor(Math.random() * (hi - lo + 1)); break; } } }
      return { a, n, y: -1.25, x: (Math.random() - 0.5) * 0.6, lost: false };
    }
    for (let k = 0; k < 9; k++) bg.push({ x: (Math.random() - 0.5) * 3, y: (Math.random() - 0.5) * 3, v: 0.02 + Math.random() * 0.12, b: 0.25 + Math.random() * 0.4 });
    const { instr, notes } = benchCols(root);
    const view = canvasView(h('div'), { label: 'Microscope field (graticule lines every 0.5 mm)', height: w => Math.min(w, 360), draw: drawField, alt: 'Oil drop in microscope' });
    const swRo = readout('Stopwatch'), vRo = readout('Plate voltage');
    const plates = check({ label: 'Field ON', value: st.on, onChange: v => { st.on = v; save(); } });
    const pol = seg({ label: 'Upper plate', value: st.up ? '+' : '−', options: [['+', '+'], ['−', '−']], onChange: v => { st.up = v === '+'; save(); } });
    const vs = slider({ label: 'Voltage between plates', min: 0, max: 600, step: 1, value: st.V, fmt: v => v.toFixed(0) + ' V', fine: [['−10', -10], ['−1', -1], ['+1', 1], ['+10', 10]], onInput: v => { st.V = v; save(); vRo.set(v.toFixed(0) + ' V'); } });
    vRo.set(st.V.toFixed(0) + ' V');
    const spd = seg({ label: 'Speed', value: st.speed, options: [[1, '×1'], [2, '×2'], [4, '×4']], onChange: v => { st.speed = v; save(); } });
    const swBtn = h('button', { type: 'button', class: 'btn pri' }, 'Start / Stop');
    const swReset = h('button', { type: 'button', class: 'btn' }, 'Reset watch');
    const sprayBtn = h('button', { type: 'button', class: 'btn' }, 'Spray a new drop');
    const ionBtn = h('button', { type: 'button', class: 'btn' }, 'Ionise (change charge)');
    const recT = h('button', { type: 'button', class: 'btn pri' }, 'Record fall time');
    const recV = h('button', { type: 'button', class: 'btn pri' }, 'Record balancing voltage');
    const sb = statusBox(); sb.say('Spray a drop, balance it with the field, then time its free fall between lines A and B.');
    instr.append(card('Millikan oil-drop apparatus', 'd = 5.00 mm, s = 1.00 mm', view.wrap,
      h('div', { class: 'readouts' }, swRo.el, vRo.el),
      h('div', { class: 'opts' }, plates.el, pol.el, spd.el), vs.el,
      h('div', { class: 'recrow' }, swBtn, swReset, sprayBtn, ionBtn),
      h('div', { class: 'recrow' }, recT, recV), sb.el));
    swBtn.addEventListener('click', () => { watch.run = !watch.run; });
    swReset.addEventListener('click', () => { watch.run = false; watch.t = 0; });
    const key = e => { if (e.code === 'Space' && !/INPUT|SELECT|TEXTAREA|BUTTON/.test(document.activeElement?.tagName || '')) { e.preventDefault(); watch.run = !watch.run; } };
    document.addEventListener('keydown', key); onCleanup(() => document.removeEventListener('keydown', key));
    function newRow(label, t) { const i = tbl.add({ label, V: [], t: t || [] }); st.cur = i; save(); }
    sprayBtn.addEventListener('click', () => { drop = makeDrop(); st.dropA = drop.a; st.dropN = drop.n; { const c = tbl.rows[st.cur]; const empty = c && !c.V.length && !c.t.length && !/\(/.test(c.label); if (!empty) newRow('Drop ' + (tbl.rows.length + 1)); } sb.say('New drop selected. Balance it first: switch the field on and adjust the voltage.'); });
    ionBtn.addEventListener('click', () => {
      if (!drop || drop.lost) return sb.say('There is no drop in view.', 'warn');
      const v1 = mgOf(drop.a) * K.d / E_CH; const opts = []; for (let n = 1; n < 60; n++) { const V = v1 / n; if (V >= 90 && V <= 590 && n !== drop.n) opts.push(n); }
      if (!opts.length) return sb.say('Ionising did not change the charge this time.', 'warn');
      opts.sort((a, b) => Math.abs(a - drop.n) - Math.abs(b - drop.n)); drop.n = opts[Math.floor(Math.random() * Math.min(3, opts.length))]; st.dropN = drop.n;
      const prev = tbl.rows[st.cur]; newRow((prev ? prev.label.replace(/ \(.*\)$/, '') : 'Drop') + ' (new charge)', prev ? prev.t.slice() : []);
      sb.say('The charge on the drop has changed. Its fall time is unchanged (copied); find the new balancing voltage.', 'ok');
    });
    recT.addEventListener('click', () => {
      if (st.cur < 0 || !tbl.rows[st.cur]) return sb.say('Spray a drop first.', 'warn');
      if (watch.run) return sb.say('Stop the watch first.', 'warn');
      if (watch.t < 0.5) return sb.say('Time the free fall of the drop between lines A and B (field OFF).', 'warn');
      const r = tbl.rows[st.cur]; const t = r.t.concat([rnd(watch.t, 2)]).slice(-5); tbl.update(st.cur, { t }); sb.say(`Fall time ${watch.t.toFixed(2)} s recorded for ${r.label}.`, 'ok'); watch.t = 0;
    });
    recV.addEventListener('click', () => {
      if (!drop || drop.lost) return sb.say('There is no drop in view.', 'warn');
      if (!st.on) return sb.say('Switch the field on and adjust the voltage until the drop stays still.', 'warn');
      const F = (st.up ? 1 : -1) * drop.n * E_CH * st.V / K.d, mg = mgOf(drop.a);
      if (Math.abs(F / mg - 1) > 0.02) return sb.say(`The drop is not balanced; it is still moving ${F < mg ? 'down' : 'up'}. Adjust the voltage.`, 'warn');
      const r = tbl.rows[st.cur]; const V = r.V.concat([st.V]).slice(-3); tbl.update(st.cur, { V }); sb.say(`Balancing voltage ${st.V} V recorded for ${r.label}.`, 'ok');
    });
    if (st.dropA) { drop = makeDrop(st.dropA, st.dropN); drop.y = 0; } else { drop = makeDrop(); st.dropA = drop.a; st.dropN = drop.n; }
    loop(dt => {
      const T = dt * st.speed;
      if (watch.run) watch.t += T;
      swRo.set(watch.t.toFixed(2) + ' s');
      if (drop && !drop.lost) {
        const n = 4, h1 = T / n;
        for (let k = 0; k < n; k++) {
          const eC = etaC(drop.a), F = st.on ? (st.up ? 1 : -1) * drop.n * E_CH * st.V / K.d : 0;
          const v = (mgOf(drop.a) - F) / (6 * Math.PI * eC * drop.a); const D = K.kT / (6 * Math.PI * eC * drop.a);
          drop.y += v * h1 * 1e3 + Math.sqrt(2 * D * h1) * gauss() * 1e3; drop.x += Math.sqrt(2 * D * h1) * gauss() * 1e3;
          drop.x = clamp(drop.x, -1.4, 1.4);
        }
        if (Math.abs(drop.y) > 2.5) { drop.lost = true; sb.say('The drop has been lost (it reached a plate). Spray a new drop.', 'warn'); }
      }
      bg.forEach(b => { b.y += b.v * T; if (b.y > 1.6) { b.y = -1.6; b.x = (Math.random() - 0.5) * 3; } });
      view.now();
    });
    function drawField(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, H);
      const cx = W / 2, cy = H / 2, Rr = Math.min(W, H) / 2 - 3, k = Rr / 1.55; // px per mm
      ctx.save(); ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.fillStyle = '#04070a'; ctx.fill(); ctx.clip();
      ctx.font = `10px ${T.mono}`; ctx.textAlign = 'left'; ctx.textBaseline = 'bottom';
      for (let y = -1.5; y <= 1.5001; y += 0.5) { const yy = cy + y * k; const timing = Math.abs(Math.abs(y) - 0.5) < 1e-6; ctx.strokeStyle = timing ? 'rgba(255,214,110,.75)' : 'rgba(150,170,180,.35)'; ctx.lineWidth = timing ? 1.4 : 1; ctx.beginPath(); ctx.moveTo(cx - Rr, yy); ctx.lineTo(cx + Rr, yy); ctx.stroke(); if (timing) { ctx.fillStyle = 'rgba(255,214,110,.9)'; ctx.fillText(y < 0 ? 'A' : 'B', cx - Rr * 0.85, yy - 2); } }
      ctx.strokeStyle = 'rgba(150,170,180,.25)'; ctx.beginPath(); ctx.moveTo(cx, cy - Rr); ctx.lineTo(cx, cy + Rr); ctx.stroke(); ctx.lineWidth = 1;
      bg.forEach(b => { ctx.fillStyle = `rgba(220,230,255,${b.b * 0.5})`; ctx.beginPath(); ctx.arc(cx + b.x * k, cy + b.y * k, 1.3, 0, 7); ctx.fill(); });
      if (drop && !drop.lost && Math.abs(drop.y) < 1.62) {
        const x = cx + drop.x * k, y = cy + drop.y * k; const g = ctx.createRadialGradient(x, y, 0, x, y, 7); g.addColorStop(0, 'rgba(255,255,240,1)'); g.addColorStop(0.35, 'rgba(255,240,200,.7)'); g.addColorStop(1, 'rgba(255,230,180,0)');
        ctx.fillStyle = g; ctx.beginPath(); ctx.arc(x, y, 7, 0, 7); ctx.fill();
      }
      ctx.restore();
      ctx.strokeStyle = T.muted; ctx.lineWidth = 2; ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.stroke(); ctx.lineWidth = 1;
      if (drop && !drop.lost && Math.abs(drop.y) >= 1.62) { ctx.fillStyle = T.sodium; ctx.font = `12px ${T.sans}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(drop.y < 0 ? '▲ drop above the field' : '▼ drop below the field', cx, drop.y < 0 ? cy - Rr + 16 : cy + Rr - 16); }
      ctx.fillStyle = T.muted; ctx.font = `10.5px ${T.sans}`; ctx.textAlign = 'right'; ctx.textBaseline = 'top'; ctx.fillText(st.on ? `field ON, ${st.V} V` : 'field OFF', W - 6, 6);
    }
    const tm = r => r.t && r.t.length ? mean(r.t) : null, Vm = r => r.V && r.V.length ? mean(r.V) : null;
    function calc(r) {
      const t = tm(r), V = Vm(r); if (t == null || V == null) return null;
      const v = K.s / t; let a = Math.sqrt(9 * K.eta * v / (2 * (K.rho - K.sig) * K.g));
      if (st.corr) for (let k = 0; k < 6; k++) a = Math.sqrt(9 * etaC(a) * v / (2 * (K.rho - K.sig) * K.g));
      const q = mgOf(a) * K.d / V; const n = Math.max(1, Math.round(q / E_CH));
      return { v, a, q, n, e: q / n };
    }
    const tbl = new ObsTable({
      key: 'exp8.t', title: 'Table. Balancing voltage and fall time', onChange: analyse, deletable: true,
      columns: [{ label: 'Drop', k: 'label', cls: 'lbl' }, { label: 'V (volt)<br>readings', v: r => r.V && r.V.length ? r.V.join(', ') : null }, { label: 'V̄<br>(V)', v: Vm, fmt: v => v.toFixed(1), calc: 1 },
        { label: 'Fall times t<br>(s)', v: r => r.t && r.t.length ? r.t.map(x => x.toFixed(2)).join(', ') : null }, { label: 't̄<br>(s)', v: tm, fmt: v => v.toFixed(2), calc: 1 },
        { label: 'v<br>(10⁻⁴ m/s)', v: r => tm(r) ? K.s / tm(r) * 1e4 : null, fmt: v => v.toFixed(3), calc: 1 },
        { label: 'a<br>(µm)', v: r => calc(r)?.a * 1e6, fmt: v => v.toFixed(3), calc: 1 },
        { label: 'q<br>(10⁻¹⁹ C)', v: r => calc(r)?.q * 1e19, fmt: v => v.toFixed(2), calc: 1 },
        { label: 'n', v: r => calc(r)?.n, calc: 1 }, { label: 'e = q/n<br>(10⁻¹⁹ C)', v: r => calc(r)?.e * 1e19, fmt: v => v.toFixed(3), calc: 1 }]
    });
    const corr = check({ label: 'Apply Cunningham’s correction', value: st.corr, onChange: v => { st.corr = v; save(); tbl.render(); analyse(); } });
    const plot = plotCard('Graph: charge on each drop', { get: () => { const p = tbl.rows.map((r, i) => [i + 1, calc(r)?.q * 1e19]).filter(p => isFinite(p[1])); const mx = Math.max(3, ...p.map(q => q[1])); const hl = []; for (let n = 1; n * 1.602 <= mx * 1.08; n++) hl.push({ y: n * 1.602, label: n <= 12 ? `${n}e` : '' }); return { xLabel: 'Drop number', yLabel: 'q (10⁻¹⁹ C)', series: [{ pts: p }], hlines: hl, zeroY: true, xDec: 0 }; } });
    const res = resultBox();
    function analyse() {
      plot.view.redraw();
      const c = tbl.rows.map(calc).filter(Boolean);
      if (!c.length) { res.set('<h4>Calculation</h4><p class="small muted">For each drop record at least one balancing voltage and fall times (3 or more). The charge is then calculated from q = (4/3)πa³(ρ − σ)g d / V.</p>'); return; }
      const e = mean(c.map(x => x.e));
      res.set(`<h4>Calculation</h4><div class="calc-lines"><div>a = √(9ηv / 2(ρ−σ)g) ${st.corr ? 'with η′ = η/(1 + b/pa)' : '(uncorrected η)'}</div><div>q = (4/3)πa³(ρ−σ) g d / V</div><div>Charges (10⁻¹⁹ C): ${c.map(x => x.q * 1e19).map(x => x.toFixed(2)).join(', ')}</div><div>n (nearest integer of q/1.6×10⁻¹⁹): ${c.map(x => x.n).join(', ')}</div></div>
      <p class="big" style="margin-top:10px">Charge of electron e = <b>${(e * 1e19).toFixed(3)} × 10⁻¹⁹ C</b></p><p class="small muted">Standard value 1.602 × 10⁻¹⁹ C; error ${pctErr(e, E_CH).toFixed(1)}%. All charges are close to whole multiples of e, showing that charge is quantised.</p>`);
    }
    if (st.cur < 0 || !tbl.rows[st.cur]) { newRow('Drop ' + (tbl.rows.length + 1)); }
    notes.append(card('Lab notebook', 'readings are saved in this browser', h('div', { class: 'stack' }, corr.el, tbl.el)), plot.el, card('Result', '', res.el)); analyse();
  }
});

/* ======================== EXP 9: e/m by magnetron ======================== */
EXPS.push({
  no: 9, id: 'exp9', group: 'Modern physics', short: 'e/m by magnetron valve',
  title: 'Specific charge of an electron (e/m) by the magnetron method',
  aim: 'To determine the specific charge of an electron (e/m) by magnetron tube method.',
  apparatus: ['Magnetron valve (cylindrical diode; anode radius a = 4.00 mm)', 'Solenoid (N = 900 turns, length L = 15.0 cm, mean diameter D = 6.0 cm)', 'DC supply for filament and variable anode supply (0–12 V)', 'DC supply for solenoid (0–2 A) with ammeter and rheostat', 'Milliammeter (anode current), voltmeter, connecting wires'],
  theory: String.raw`
<h2>Principle</h2>
<p>In a cylindrical diode the electrons emitted by the axial filament (cathode) are accelerated radially towards the coaxial cylindrical anode of radius \(a\) by the anode potential \(V\). A solenoid around the valve produces a uniform magnetic field \(B\) along the axis, perpendicular to the electron velocity. The magnetic force bends the paths into curves. As \(B\) increases, the curves become tighter, and at a <b>critical field</b> \(B_c\) the electrons just graze the anode and return. Beyond \(B_c\) the anode current falls sharply.</p>
<h3>Critical field</h3>
<p>Energy gained by an electron reaching radius \(r\): \(\frac{1}{2}mv^{2} = eV(r)\). The torque about the axis due to the magnetic force gives the angular momentum relation (electron starting from the axis with negligible velocity)</p>
<div class="eq">\[m r^{2}\dot\phi = \frac{1}{2}eBr^{2}\qquad\Rightarrow\qquad v_\phi = \frac{eBr}{2m}\]</div>
<p>At the critical condition the electron reaches the anode (\(r = a\), potential \(V\)) moving tangentially, so \(v = v_\phi\):</p>
<div class="eq">\[\frac{1}{2}m\left(\frac{eB_c a}{2m}\right)^{2} = eV\qquad\Rightarrow\qquad \frac{e}{m} = \frac{8V}{B_c^{2}a^{2}}\]</div>
<h3>Field of the solenoid</h3>
<p>At the centre of a solenoid of \(N\) turns, length \(L\) and diameter \(D\) carrying current \(I\):</p>
<div class="eq">\[B = \frac{\mu_0 N I}{\sqrt{L^{2}+D^{2}}}\qquad\Rightarrow\qquad \frac{e}{m} = \frac{8V\,(L^{2}+D^{2})}{\mu_0^{2}N^{2}I_c^{2}a^{2}}\]</div>
<p>where \(I_c\) is the critical solenoid current. Because the electrons leave the filament with a spread of thermal velocities and the field is not perfectly uniform, the anode current does not drop to zero suddenly. \(I_c\) is taken at the middle of the steep fall of the \(I_a\)–\(I_s\) curve (where the anode current has fallen to half way between its initial and final values).</p>
<p>Since \(I_c^{2} \propto V\), repeating for several anode voltages gives a check.</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Place the magnetron valve at the centre of the solenoid with its axis along the solenoid axis.</li>
<li>Connect the filament supply, the anode circuit (anode supply, voltmeter, milliammeter) and the solenoid circuit (DC supply, ammeter, rheostat, key) as in the circuit diagram.</li>
<li>Switch on the filament and allow the valve to warm up for a few minutes.</li>
<li>Set the anode voltage to 6 V. With zero solenoid current, note the anode current \(I_a\).</li>
<li>Increase the solenoid current \(I_s\) in steps of 0.05 A, noting \(I_a\) each time. Take smaller steps (0.02 A) where \(I_a\) begins to fall. Continue until \(I_a\) becomes nearly constant again (small).</li>
<li>Repeat for anode voltages of 8 V and 10 V.</li>
<li>Plot \(I_a\) against \(I_s\) for each voltage. Find the critical current \(I_c\) at the middle of the steep fall.</li>
<li>Calculate \(B_c\) and \(e/m\) for each voltage and take the mean.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> set the anode voltage, then raise the solenoid current with the slider and fine buttons. Press <i>Record</i> to note the meter readings. The cross-section view shows the electron paths bending as the field increases.</div>`,
  precautions: String.raw`<li>The valve should be at the centre of the solenoid and coaxial with it.</li><li>Allow the filament to heat up and the anode current to become steady before readings.</li><li>Do not exceed the rated currents; switch off the solenoid current when not in use to avoid heating.</li><li>Take closely spaced readings near the critical current.</li>`,
  errors: String.raw`<li>Thermal velocity spread and non-uniform field make the cut-off gradual.</li><li>The filament may not be exactly on the axis.</li><li>Error in the value of the field from the solenoid formula.</li>`,
  viva: [
    ['What is a magnetron?', 'A cylindrical diode placed in an axial magnetic field; the field controls the anode current by bending the electron paths.'],
    ['What is meant by specific charge?', 'The charge per unit mass of a particle, e/m. For the electron e/m = 1.759 × 10¹¹ C kg⁻¹.'],
    ['What is the critical magnetic field?', 'The field for which electrons leaving the cathode just graze the anode, so that the anode current falls sharply.'],
    ['Why does the anode current not fall to zero suddenly?', 'Electrons are emitted with different thermal velocities, the field is not perfectly uniform and the filament is not exactly along the axis.'],
    ['How does the critical current depend on anode voltage?', 'I_c² ∝ V, i.e. I_c ∝ √V.'],
    ['Why is the magnetic field kept parallel to the axis?', 'So that it is perpendicular to the radial electron velocity and bends the paths in the plane perpendicular to the axis.'],
    ['Does the result depend on the direction of the solenoid current?', 'No. Reversing the field only reverses the direction in which the paths curve.']],
  bench(root) {
    const R = apparatus('exp9');
    const P = { a: 4.0e-3, rk: 0.1e-3, N: 900, L: 0.15, D: 0.06, k: 0.12 * (1 + R.range(-0.1, 0.1)), w: 0.055, f: 0.03, kB: R.range(0.965, 1.0) }; // kB: actual field at the valve relative to the formula
    const BperA = MU0 * P.N / Math.hypot(P.L, P.D);
    const st = Object.assign({ V: 6, I: 0 }, LS.get('exp9.st', {}));
    const save = () => LS.set('exp9.st', st);
    const Bc = V => Math.sqrt(8 * V / (EM * P.a ** 2)) / (1 - (P.rk / P.a) ** 2);
    const noise = (x, s) => { const z = Math.sin(x * 9137.1 + s * 31.7) * 43758.5453; return (z - Math.floor(z)) - 0.5; };
    const Ia = () => { const B = P.kB * BperA * st.I, b = Bc(st.V); const I0 = P.k * st.V ** 1.5; return I0 * (P.f + (1 - P.f) / (1 + Math.exp((B - b) / (P.w * b)))) * (1 + 0.004 * noise(st.I, st.V)); };
    const { instr, notes } = benchCols(root);
    const row = h('div', { class: 'cols2' }); const left = h('div'), right = h('div', { class: 'stack' }); row.append(left, right);
    const tube = canvasView(left, { label: 'Cross-section of the valve (electron paths)', height: w => Math.min(w, 300), draw: drawTube, alt: 'Electron paths in magnetron' });
    const roV = readout('Anode voltage'), roIa = readout('Anode current'), roIs = readout('Solenoid current');
    right.append(h('div', { class: 'readouts' }, roV.el, roIa.el, roIs.el));
    const circ = canvasView(right, { label: 'Circuit', aspect: 0.55, maxH: 200, draw: drawCircuit, alt: 'Circuit diagram' });
    const vS = slider({ label: 'Anode voltage V', min: 2, max: 12, step: 0.1, value: st.V, fmt: v => v.toFixed(1) + ' V', fine: [['−1', -1], ['−0.1', -0.1], ['+0.1', 0.1], ['+1', 1]], onInput: v => { st.V = v; changed(); } });
    const iS = slider({ label: 'Solenoid current Iₛ', min: 0, max: 2, step: 0.01, value: st.I, fmt: v => v.toFixed(2) + ' A', fine: [['−0.1', -0.1], ['−0.05', -0.05], ['−0.01', -0.01], ['+0.01', 0.01], ['+0.05', 0.05], ['+0.1', 0.1]], onInput: v => { st.I = v; changed(); } });
    const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading'); const sb = statusBox(); sb.say('Set the anode voltage, then record Iₐ for increasing solenoid current.');
    instr.append(card('Magnetron valve in a solenoid', 'a = 4.00 mm; N = 900, L = 15.0 cm, D = 6.0 cm', row, h('div', { class: 'ctls' }, vS.el, iS.el), h('div', { class: 'recrow' }, btn), sb.el));
    function changed() { save(); tube.redraw(); upd(); }
    function upd() { roV.set(st.V.toFixed(1) + ' V'); roIa.set(Ia().toFixed(2) + ' mA'); roIs.set(st.I.toFixed(2) + ' A'); }
    upd();
    btn.addEventListener('click', () => {
      const Vr = rnd(st.V, 1), ia = rnd(Ia(), 2), is = rnd(st.I, 2);
      const dup = tbl.rows.findIndex(r => r.V === Vr && r.Is === is);
      if (dup >= 0) tbl.update(dup, { Ia: ia }); else tbl.add({ V: Vr, Is: is, Ia: ia });
      sb.say(`Recorded: V = ${Vr.toFixed(1)} V, Iₛ = ${is.toFixed(2)} A, Iₐ = ${ia.toFixed(2)} mA.`, 'ok');
    });
    function drawTube(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, H);
      const cx = W / 2, cy = H / 2, Ra = Math.min(W, H) / 2 - 14, k = Ra / P.a;
      ctx.fillStyle = T.panel2; ctx.beginPath(); ctx.arc(cx, cy, Ra, 0, 7); ctx.fill();
      ctx.strokeStyle = T.ink; ctx.lineWidth = 4; ctx.beginPath(); ctx.arc(cx, cy, Ra, 0, 7); ctx.stroke(); ctx.lineWidth = 1;
      ctx.fillStyle = T.sodium; ctx.beginPath(); ctx.arc(cx, cy, 3.5, 0, 7); ctx.fill();
      // B into page symbols
      ctx.fillStyle = T.muted; ctx.font = `11px ${T.sans}`; ctx.textAlign = 'left'; ctx.textBaseline = 'top'; ctx.fillText(`B = ${(BperA * st.I * 1e3).toFixed(2)} mT (axial)`, 6, 6);
      const B = P.kB * BperA * st.I, V = st.V, lnr = Math.log(P.a / P.rk);
      const acc = (x, y, vx, vy) => { const r = Math.hypot(x, y); const E = V / (r * lnr); return [EM * (E * x / r - vy * B), EM * (E * y / r + vx * B)]; };
      ctx.strokeStyle = T.accent; ctx.lineWidth = 1.4;
      const nE = 12; let hit = 0;
      for (let j = 0; j < nE; j++) {
        const ang = j / nE * 2 * Math.PI + 0.13; let x = P.rk * Math.cos(ang), y = P.rk * Math.sin(ang);
        const v0 = 2.5e5 * (0.6 + 0.4 * Math.sin(j * 2.1)); let vx = v0 * Math.cos(ang), vy = v0 * Math.sin(ang);
        const dt = 4e-12; ctx.beginPath(); ctx.moveTo(cx + x * k, cy - y * k); let ok = false;
        for (let s = 0; s < 4000; s++) {
          const [ax1, ay1] = acc(x, y, vx, vy);
          const xm = x + vx * dt / 2, ym = y + vy * dt / 2, vxm = vx + ax1 * dt / 2, vym = vy + ay1 * dt / 2;
          const [ax2, ay2] = acc(xm, ym, vxm, vym);
          x += vxm * dt; y += vym * dt; vx += ax2 * dt; vy += ay2 * dt;
          const r = Math.hypot(x, y);
          if (s % 3 === 0) ctx.lineTo(cx + x * k, cy - y * k);
          if (r >= P.a) { ok = true; break; }
          if (r < P.rk * 0.95) break;
        }
        ctx.stroke(); if (ok) hit++;
      }
      ctx.lineWidth = 1;
      ctx.fillStyle = T.muted; ctx.textAlign = 'left'; ctx.textBaseline = 'bottom'; ctx.fillText(hit === nE ? 'electrons reach the anode' : hit === 0 ? 'electrons return to the cathode' : 'near the critical field', 6, H - 4);
    }
    function drawCircuit(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
      ctx.strokeStyle = T.ink; ctx.fillStyle = T.ink; ctx.lineWidth = 1.2; ctx.font = `10.5px ${T.sans}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      const cx = W * 0.32, cy = H * 0.5, r = Math.min(H * 0.3, W * 0.14);
      for (let i = 0; i < 7; i++) { ctx.beginPath(); ctx.ellipse(cx - r * 1.2 + i * r * 0.4, cy, r * 0.18, r * 1.25, 0, 0, 7); ctx.stroke(); }
      ctx.strokeRect(cx - r * 0.5, cy - r * 0.8, r, r * 1.6); ctx.fillText('valve', cx, cy);
      const box = (x, y, t) => { ctx.beginPath(); ctx.arc(x, y, 12, 0, 7); ctx.fillStyle = T.panel; ctx.fill(); ctx.stroke(); ctx.fillStyle = T.ink; ctx.fillText(t, x, y); };
      ctx.beginPath(); ctx.moveTo(cx, cy - r * 0.8); ctx.lineTo(cx, 12); ctx.lineTo(W * 0.85, 12); ctx.lineTo(W * 0.85, H - 12); ctx.lineTo(cx, H - 12); ctx.lineTo(cx, cy + r * 0.8); ctx.stroke();
      box(W * 0.62, 12, 'mA'); box(W * 0.85, H * 0.5, 'V');
      ctx.fillText('anode supply', W * 0.72, H - 22);
      ctx.beginPath(); ctx.moveTo(cx - r * 1.3, cy + r * 1.3); ctx.lineTo(cx - r * 1.3, H - 30); ctx.lineTo(W * 0.06, H - 30); ctx.lineTo(W * 0.06, 30); ctx.lineTo(cx - r * 1.3, 30); ctx.lineTo(cx - r * 1.3, cy - r * 1.3); ctx.stroke();
      box(W * 0.06, H * 0.5, 'A'); ctx.fillText('solenoid supply', W * 0.13, H - 18);
    }
    const tbl = new ObsTable({
      key: 'exp9.t', title: 'Table. Anode current against solenoid current', onChange: analyse, deletable: true, sort: (a, b) => a.V - b.V || a.Is - b.Is,
      columns: [{ label: 'Anode voltage V<br>(V)', k: 'V', fmt: v => v.toFixed(1) }, { label: 'Solenoid current Iₛ<br>(A)', k: 'Is', fmt: v => v.toFixed(2) }, { label: 'Anode current Iₐ<br>(mA)', k: 'Ia', fmt: v => v.toFixed(2) }, { label: 'B = µ₀NIₛ/√(L²+D²)<br>(mT)', v: r => BperA * r.Is * 1e3, fmt: v => v.toFixed(3), calc: 1 }]
    });
    function groups() { const g = {}; tbl.rows.forEach(r => (g[r.V.toFixed(1)] = g[r.V.toFixed(1)] || []).push(r)); return Object.entries(g).map(([V, rs]) => ({ V: +V, rs: rs.sort((a, b) => a.Is - b.Is) })); }
    function critical(rs) {
      if (rs.length < 4) return null; const mx = Math.max(...rs.map(r => r.Ia)), mn = Math.min(...rs.map(r => r.Ia)); if (mx - mn < 0.2 * mx) return null;
      const half = (mx + mn) / 2; for (let i = 1; i < rs.length; i++) { const a = rs[i - 1], b = rs[i]; if (a.Ia >= half && b.Ia < half) return a.Is + (a.Ia - half) / (a.Ia - b.Ia) * (b.Is - a.Is); } return null;
    }
    const plot = plotCard('Graph: Iₐ against Iₛ', { get: () => { const gs = groups(); return { xLabel: 'Solenoid current Iₛ (A)', yLabel: 'Anode current Iₐ (mA)', series: gs.map(g => ({ pts: g.rs.map(r => [r.Is, r.Ia]), join: true, label: `V = ${g.V.toFixed(1)} V` })), vlines: gs.map((g, i) => { const c = critical(g.rs); return c ? { x: c, label: `I꜀ = ${c.toFixed(3)} A`, dy: i * 14, color: [theme().p1, theme().p2, theme().p3, theme().p4][i % 4] } : null; }).filter(Boolean), zeroX: true, zeroY: true }; } });
    const res = resultBox();
    function analyse() {
      plot.view.redraw();
      const out = groups().map(g => { const c = critical(g.rs); if (!c) return null; const B = BperA * c; return { V: g.V, c, B, em: 8 * g.V / (B * B * P.a * P.a) }; }).filter(Boolean);
      if (!out.length) { res.set('<h4>Calculation</h4><p class="small muted">Record Iₐ from Iₛ = 0 up to beyond the sharp fall (about 8–15 readings for one anode voltage). The critical current is found from the middle of the fall.</p>'); return; }
      const em = mean(out.map(o => o.em));
      res.set(`<h4>Calculation</h4><div class="calc-lines"><div>B = µ₀NI/√(L²+D²) = ${(BperA * 1e3).toFixed(3)} mT per ampere</div>${out.map(o => `<div>V = ${o.V.toFixed(1)} V: I꜀ = ${o.c.toFixed(3)} A, B꜀ = ${(o.B * 1e3).toFixed(3)} mT, e/m = 8V/(B꜀²a²) = ${(o.em / 1e11).toFixed(3)} × 10¹¹ C/kg, I꜀²/V = ${(o.c * o.c / o.V).toFixed(4)}</div>`).join('')}</div>
      <p class="big" style="margin-top:10px">e/m = <b>${(em / 1e11).toFixed(3)} × 10¹¹ C kg⁻¹</b></p><p class="small muted">Standard value 1.759 × 10¹¹ C kg⁻¹; error ${pctErr(em, EM).toFixed(1)}%.</p>`);
    }
    notes.append(card('Lab notebook', 'readings are saved in this browser', tbl.el), plot.el, card('Result', '', res.el)); analyse();
  }
});

/* ======================== EXP 10: e/m by Thomson ======================== */
EXPS.push({
  no: 10, id: 'exp10', group: 'Modern physics', short: 'e/m by Thomson’s method',
  title: 'Specific charge of an electron (e/m) by Thomson’s method',
  aim: 'To determine the specific charge of an electron (e/m) by Thomson’s method.',
  apparatus: ['Cathode ray tube with deflecting plates, on a wooden stand, with power supply', 'Pair of bar magnets on a magnetometer stand', 'Deflection magnetometer', 'Low-voltage DC supply for the deflecting plates with voltmeter', 'Plate length l = 4.0 cm, separation d = 0.40 cm, distance from centre of plates to screen L = 20.0 cm'],
  theory: String.raw`
<h2>Principle (crossed fields)</h2>
<p>Electrons from the gun move along the axis of the tube with velocity \(v\). Between the deflecting plates (length \(l\), separation \(d\), potential difference \(V\)) the electric field \(E = V/d\) gives each electron a transverse acceleration \(eE/m\) for a time \(l/v\). The spot on the screen at distance \(L\) from the centre of the plates is displaced by</p>
<div class="eq">\[y = \frac{eE}{m}\,\frac{lL}{v^{2}}\]</div>
<p>A magnetic field \(B\) perpendicular to both \(E\) and the beam is now applied (bar magnets) and adjusted until the spot returns to its undeflected position. The electric and magnetic forces are then equal:</p>
<div class="eq">\[eE = evB\qquad\Rightarrow\qquad v = \frac{E}{B}\]</div>
<p>Substituting for \(v\):</p>
<div class="eq">\[\frac{e}{m} = \frac{yE}{B^{2}lL} = \frac{y\,V}{B^{2}\,l\,L\,d}\]</div>
<h3>Measurement of B</h3>
<p>The cathode ray tube is removed and a deflection magnetometer is placed at the same place, with the magnets at the same distance. The needle sets along the resultant of \(B\) and the earth’s horizontal field \(B_H\) (which are perpendicular), so</p>
<div class="eq">\[B = B_H\tan\theta\]</div>
<p>\(\theta\) is the mean of four readings (both ends of the pointer, and with the magnets reversed). \(B_H\) is taken as 3.7 × 10⁻⁵ T (approximate value for Sindhuli; use your laboratory value if known).</p>
<p><b>Note:</b> the tube is placed along the magnetic meridian so that the earth’s field is parallel to the beam and does not deflect it.</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Place the CRT on its stand with its axis along the magnetic meridian (N–S), using a compass. Switch on and adjust brightness and focus to get a small sharp spot. Note its zero position on the scale.</li>
<li>Apply a small voltage \(V\) (about 1–3 V) across the deflecting plates. Note the displacement \(y\) of the spot.</li>
<li>Place the two bar magnets on the arms of the stand on either side of the tube, at equal distances, with opposite poles facing the tube, so that their field is perpendicular to the beam and to E. Move them symmetrically until the spot comes back to zero. Note their distance.</li>
<li>Switch off and remove the CRT without disturbing the magnets. Place a deflection magnetometer at the position of the plates. Read both ends of the pointer (θ₁, θ₂). Reverse both magnets (keeping distances) and read again (θ₃, θ₄). Find the mean θ.</li>
<li>Calculate \(B = B_H \tan\theta\) and e/m.</li>
<li>Repeat for two more plate voltages.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> Step 1 — set the plate voltage with no magnets and record y. Step 2 — tick “Bar magnets placed” and move them until the spot is back at 0; if the spot moves further away, reverse the magnets. Record the distance. Step 3 — switch the view to the magnetometer, record θ, reverse the magnets and record again.</div>`,
  precautions: String.raw`<li>Set the tube along the magnetic meridian; keep other magnetic materials away.</li><li>Use a small, sharp spot; read its position without parallax.</li><li>Keep the magnets symmetric and at the same distance when the magnetometer replaces the tube.</li><li>Read both ends of the pointer and reverse the magnets to remove errors.</li>`,
  errors: String.raw`<li>The magnetic field is not confined to the plate region and is not uniform.</li><li>The small deflection y and the angle θ have relatively large reading errors (B enters as B²).</li><li>Uncertainty in B<sub>H</sub>.</li>`,
  viva: [
    ['What is the principle of Thomson’s method?', 'Crossed electric and magnetic fields are balanced so that the electrons go undeflected; this gives v = E/B. The electric deflection alone then gives e/m.'],
    ['Why must E and B be perpendicular?', 'Only then are the electric and magnetic forces along the same line in opposite directions and can cancel.'],
    ['Why is the tube placed along the magnetic meridian?', 'So that the earth’s horizontal field is parallel to the beam and exerts no force on the electrons.'],
    ['What is the use of the deflection magnetometer?', 'To measure the field of the bar magnets: B = B_H tan θ.'],
    ['Does e/m depend on the accelerating voltage?', 'No. e/m is a constant; the accelerating voltage only changes v and hence the required B.'],
    ['Why was Thomson’s experiment important?', 'It showed that cathode rays are particles with a definite charge-to-mass ratio, the same for all cathode materials — the discovery of the electron (1897).'],
    ['What is the value of e/m?', '1.759 × 10¹¹ C kg⁻¹.']],
  bench(root) {
    const R = apparatus('exp10');
    const P = { l: 0.04, d: 0.004, L: 0.20, Vacc: 250, BH: 3.7e-5, K: 5.7e-7 * (1 + R.range(-0.12, 0.12)), ep: R.range(0.3, 0.8), good: R.u() < 0.5 ? 'N' : 'S' };
    const v = Math.sqrt(2 * EM * P.Vacc);
    const st = Object.assign({ Vp: 0, pol: 1, mag: false, r: 40, orient: 'N', mode: 'crt', cur: -1 }, LS.get('exp10.st', {}));
    const save = () => LS.set('exp10.st', st);
    const Bm = () => st.mag ? P.K / (st.r / 100) ** 3 : 0;
    const sgn = () => st.orient === P.good ? 1 : -1;
    const spotY = () => (st.pol * st.Vp / P.d - sgn() * v * Bm()) * P.l * P.L / (2 * P.Vacc) * 100; // cm
    const theta = () => Math.atan2(sgn() * Bm(), P.BH) / DEG;
    const { instr, notes } = benchCols(root);
    const side = canvasView(h('div'), { label: 'Cathode ray tube (side view)', aspect: 0.32, minH: 120, maxH: 190, draw: drawSide, alt: 'CRT side view' });
    const row = h('div', { class: 'cols2' }); const lb = h('div'), rb = h('div', { class: 'stack' }); row.append(lb, rb);
    const scr = canvasView(lb, { label: '', height: w => Math.min(w, 300), draw: drawScreen, alt: 'Screen or magnetometer' });
    const roY = readout('Spot position y'), roVp = readout('Plate voltage'), roR = readout('Magnet distance');
    rb.append(h('div', { class: 'readouts' }, roY.el, roVp.el, roR.el));
    const modeSeg = seg({ label: 'At the tube position', value: st.mode, options: [['crt', 'CRT'], ['mag', 'Magnetometer']], onChange: x => { st.mode = x; changed(); } });
    const polSeg = seg({ label: 'Plate polarity', value: st.pol, options: [[1, 'Normal'], [-1, 'Reversed']], onChange: x => { st.pol = x; changed(); } });
    const magChk = check({ label: 'Bar magnets placed', value: st.mag, onChange: x => { st.mag = x; changed(); } });
    const oriSeg = seg({ label: 'Magnets', value: st.orient, options: [['N', 'N poles in'], ['S', 'S poles in']], onChange: x => { st.orient = x; changed(); } });
    rb.append(h('div', { class: 'opts' }, modeSeg.el), h('div', { class: 'opts' }, polSeg.el), h('div', { class: 'opts' }, magChk.el, oriSeg.el));
    const vS = slider({ label: 'Plate voltage V', min: 0, max: 4, step: 0.05, value: st.Vp, fmt: x => x.toFixed(2) + ' V', fine: [['−0.5', -0.5], ['−0.05', -0.05], ['+0.05', 0.05], ['+0.5', 0.5]], onInput: x => { st.Vp = x; changed(); } });
    const rS = slider({ label: 'Distance of each magnet from tube', min: 10, max: 50, step: 0.1, value: st.r, fmt: x => x.toFixed(1) + ' cm', fine: [['−1', -1], ['−0.1', -0.1], ['+0.1', 0.1], ['+1', 1]], onInput: x => { st.r = x; changed(); } });
    const b1 = h('button', { type: 'button', class: 'btn pri' }, '1 · Record deflection y');
    const b2 = h('button', { type: 'button', class: 'btn pri' }, '2 · Record magnet distance');
    const b3 = h('button', { type: 'button', class: 'btn pri' }, '3 · Record magnetometer θ');
    const sb = statusBox(); sb.say('Step 1: apply a plate voltage (no magnets) and record the deflection.');
    instr.append(card('Thomson’s e/m apparatus', 'l = 4.0 cm, d = 0.40 cm, L = 20.0 cm', side.wrap, row, h('div', { class: 'ctls' }, vS.el, rS.el), h('div', { class: 'recrow' }, b1, b2, b3), sb.el));
    function changed() { save(); side.redraw(); scr.redraw(); upd(); }
    function upd() {
      const y = spotY(); roY.set(st.mode !== 'crt' ? '—' : Math.abs(y) > 4 ? 'off screen' : rnd(y, 2).toFixed(2) + ' cm', Practice.on && st.mode === 'crt');
      roVp.set(st.Vp.toFixed(2) + ' V'); roR.set(st.mag ? st.r.toFixed(1) + ' cm' : 'removed');
    }
    upd(); Practice.subs.add(upd); onCleanup(() => Practice.subs.delete(upd));
    function drawSide(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
      const y0 = H / 2, x0 = W * 0.05, xs = W * 0.93;
      ctx.strokeStyle = T.ink; ctx.lineWidth = 1.2; ctx.beginPath(); ctx.moveTo(x0, y0 - 10); ctx.lineTo(W * 0.4, y0 - 10); ctx.lineTo(xs, y0 - H * 0.4); ctx.lineTo(xs, y0 + H * 0.4); ctx.lineTo(W * 0.4, y0 + 10); ctx.lineTo(x0, y0 + 10); ctx.closePath(); ctx.stroke();
      if (st.mode === 'mag') { ctx.fillStyle = T.panel2; ctx.globalAlpha = .7; ctx.fillRect(0, 0, W, H); ctx.globalAlpha = 1; labelText(ctx, T, 'tube removed — magnetometer in its place', W / 2, y0, 'center', T.ink); return; }
      const pl = W * 0.22, pr = W * 0.32; ctx.lineWidth = 3; ctx.strokeStyle = T.accent; ctx.beginPath(); ctx.moveTo(pl, y0 - 7); ctx.lineTo(pr, y0 - 7); ctx.moveTo(pl, y0 + 7); ctx.lineTo(pr, y0 + 7); ctx.stroke(); ctx.lineWidth = 1;
      const y = spotY(); const yscr = clamp(-y / 4 * H * 0.38, -H * 0.42, H * 0.42);
      ctx.strokeStyle = '#3fd07a'; ctx.lineWidth = 1.6; ctx.beginPath(); ctx.moveTo(x0 + 6, y0); ctx.lineTo(pl, y0); ctx.quadraticCurveTo((pl + pr) / 2, y0, pr, y0 + yscr * 0.12); ctx.lineTo(xs, y0 + yscr); ctx.stroke(); ctx.lineWidth = 1;
      if (st.mag) { const off = clamp(H * 0.12 + (st.r - 10) / 40 * H * 0.3, 18, H * 0.45); [-1, 1].forEach(s => { ctx.fillStyle = T.bad; ctx.globalAlpha = .7; ctx.fillRect((pl + pr) / 2 - 20, y0 + s * off - 5, 40, 10); ctx.globalAlpha = 1; }); }
      labelText(ctx, T, 'gun', x0 + 18, y0 + 20); labelText(ctx, T, 'plates', (pl + pr) / 2, y0 + 24); labelText(ctx, T, 'screen', xs - 18, H - 8);
      if (st.mag) labelText(ctx, T, 'magnets (field into/out of page)', (pl + pr) / 2 + 60, 10, 'left');
    }
    function drawScreen(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel; ctx.fillRect(0, 0, W, H);
      const cx = W / 2, cy = H / 2, Rr = Math.min(W, H) / 2 - 3;
      if (st.mode === 'crt') {
        const k = Rr / 4.2; ctx.save(); ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.fillStyle = '#0a1410'; ctx.fill(); ctx.clip();
        for (let mm = -42; mm <= 42; mm++) { const p = mm / 10 * k; const major = mm % 10 === 0, half = mm % 5 === 0; ctx.strokeStyle = major ? 'rgba(120,200,150,.45)' : half ? 'rgba(120,200,150,.22)' : 'rgba(120,200,150,.1)'; ctx.beginPath(); ctx.moveTo(cx - Rr, cy + p); ctx.lineTo(cx + Rr, cy + p); ctx.moveTo(cx + p, cy - Rr); ctx.lineTo(cx + p, cy + Rr); ctx.stroke(); }
        ctx.fillStyle = 'rgba(140,220,170,.8)'; ctx.font = `10px ${T.mono}`; ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
        for (let c = -4; c <= 4; c++) if (c) ctx.fillText(String(c), cx + 3, cy - c * k);
        const y = spotY(); if (Math.abs(y) <= 4.2) { const sy = cy - y * k; const g = ctx.createRadialGradient(cx, sy, 0, cx, sy, 9); g.addColorStop(0, 'rgba(200,255,210,1)'); g.addColorStop(.4, 'rgba(90,240,140,.8)'); g.addColorStop(1, 'rgba(60,220,120,0)'); ctx.fillStyle = g; ctx.beginPath(); ctx.arc(cx, sy, 9, 0, 7); ctx.fill(); }
        ctx.restore(); ctx.strokeStyle = T.muted; ctx.lineWidth = 2; ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.stroke(); ctx.lineWidth = 1;
        ctx.fillStyle = T.muted; ctx.font = `10.5px ${T.sans}`; ctx.textAlign = 'left'; ctx.textBaseline = 'top'; ctx.fillText('screen, scale in cm', 4, 4);
      } else {
        ctx.fillStyle = T.panel2; ctx.beginPath(); ctx.arc(cx, cy, Rr, 0, 7); ctx.fill(); ctx.strokeStyle = T.muted; ctx.lineWidth = 2; ctx.stroke(); ctx.lineWidth = 1;
        ctx.strokeStyle = T.ink; ctx.fillStyle = T.ink; ctx.font = `10px ${T.mono}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        for (let a = 0; a < 360; a++) { const ang = (a - 90) * DEG; const big = a % 10 === 0; const r1 = Rr - 4, r2 = Rr - (big ? 14 : a % 5 === 0 ? 10 : 7); ctx.beginPath(); ctx.moveTo(cx + r1 * Math.cos(ang), cy + r1 * Math.sin(ang)); ctx.lineTo(cx + r2 * Math.cos(ang), cy + r2 * Math.sin(ang)); ctx.stroke(); if (big && a % 30 === 0) { const lab = a <= 90 ? a : a <= 180 ? 180 - a : a <= 270 ? a - 180 : 360 - a; ctx.fillText(String(lab), cx + (Rr - 26) * Math.cos(ang), cy + (Rr - 26) * Math.sin(ang)); } }
        const th = theta(); const a1 = (th + P.ep - 90) * DEG, a2 = (th - P.ep + 90) * DEG;
        ctx.strokeStyle = T.bad; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.moveTo(cx + (Rr - 6) * Math.cos(a1), cy + (Rr - 6) * Math.sin(a1)); ctx.lineTo(cx + (Rr - 6) * Math.cos(a2), cy + (Rr - 6) * Math.sin(a2)); ctx.stroke(); ctx.lineWidth = 1;
        ctx.fillStyle = T.ink; ctx.beginPath(); ctx.arc(cx, cy, 4, 0, 7); ctx.fill();
        ctx.fillStyle = T.muted; ctx.font = `10.5px ${T.sans}`; ctx.textAlign = 'left'; ctx.textBaseline = 'top'; ctx.fillText('deflection magnetometer (1° divisions)', 4, 4);
      }
    }
    b1.addEventListener('click', async () => {
      if (st.mode !== 'crt') return sb.say('Put the CRT back (At the tube position → CRT).', 'warn');
      if (st.mag) return sb.say('Remove the magnets for this step (untick “Bar magnets placed”).', 'warn');
      const y = spotY(); if (st.Vp < 0.5) return sb.say('Apply a plate voltage of at least 0.5 V.', 'warn'); if (Math.abs(y) > 4) return sb.say('The spot is off the screen; reduce the voltage.', 'warn');
      const truth = rnd(Math.abs(y), 2);
      const val = await askReading({ title: 'Read the spot deflection', prompt: 'Read the distance of the spot from the centre on the cm scale (estimate to 0.05 cm).', truth, tol: 0.05, unit: 'cm', hint: `The spot is at <b>${truth.toFixed(2)} cm</b> from the centre.` });
      if (val == null) return;
      const i = tbl.add({ Vp: st.Vp, y: truth, r: null, th: [null, null, null, null] }); st.cur = i; save();
      sb.say(`Recorded V = ${st.Vp.toFixed(2)} V, y = ${truth.toFixed(2)} cm. Step 2: place the magnets and bring the spot back to 0.`, 'ok');
    });
    b2.addEventListener('click', () => {
      const r = tbl.rows[st.cur]; if (!r) return sb.say('Do step 1 first.', 'warn');
      if (st.mode !== 'crt') return sb.say('The CRT must be in place for this step.', 'warn');
      if (Math.abs(st.Vp - r.Vp) > 1e-6) return sb.say(`Keep the plate voltage at ${r.Vp.toFixed(2)} V (as in step 1).`, 'warn');
      if (!st.mag) return sb.say('Place the bar magnets.', 'warn');
      const y = spotY(); if (Math.abs(y) > 0.02) return sb.say(Math.abs(y) > Math.abs(r.y) ? 'The spot has moved further away: reverse the magnets.' : 'The spot is not yet back at zero. Move the magnets.', 'warn');
      tbl.update(st.cur, { r: rnd(st.r, 1), good: st.orient }); sb.say(`Distance ${st.r.toFixed(1)} cm recorded. Step 3: switch to the magnetometer without moving the magnets.`, 'ok');
    });
    b3.addEventListener('click', async () => {
      const r = tbl.rows[st.cur]; if (!r || r.r == null) return sb.say('Do steps 1 and 2 first.', 'warn');
      if (st.mode !== 'mag') return sb.say('Replace the CRT by the magnetometer (At the tube position → Magnetometer).', 'warn');
      if (!st.mag || Math.abs(st.r - r.r) > 0.05) return sb.say(`Keep the magnets at ${r.r.toFixed(1)} cm, as in step 2.`, 'warn');
      const th = theta(); const t1 = Math.round(Math.abs(th + P.ep)), t2 = Math.round(Math.abs(th - P.ep));
      const a = await askReading({ title: 'Read the magnetometer (one end)', prompt: 'Read the deflection of one end of the pointer to the nearest degree.', truth: t1, tol: 0.6, unit: '°', hint: `This end of the pointer shows <b>${t1}°</b>.` }); if (a == null) return;
      const b = await askReading({ title: 'Read the other end', prompt: 'Now read the other end of the pointer.', truth: t2, tol: 0.6, unit: '°', hint: `The other end shows <b>${t2}°</b>.` }); if (b == null) return;
      const th4 = r.th.slice(); const idx = st.orient === r.good ? 0 : 2; th4[idx] = t1; th4[idx + 1] = t2;
      tbl.update(st.cur, { th: th4 });
      sb.say(`θ = ${t1}°, ${t2}° recorded.` + (th4[2] == null ? ' Now reverse the magnets and record again.' : ' Set complete. Repeat with another plate voltage.'), 'ok');
    });
    const thm = r => { const a = (r.th || []).filter(x => x != null); return a.length ? mean(a) : null; };
    const emOf = r => { const t = thm(r); if (t == null || r.y == null) return null; const B = P.BH * Math.tan(t * DEG); return (r.y / 100) * (r.Vp / P.d) / (B * B * P.l * P.L); };
    const tbl = new ObsTable({
      key: 'exp10.t', title: 'Table. Deflection, balancing and magnetometer readings', onChange: analyse, deletable: true,
      columns: [{ label: 'V<br>(volt)', k: 'Vp', fmt: x => x.toFixed(2) }, { label: 'y<br>(cm)', k: 'y', fmt: x => x.toFixed(2) }, { label: 'Magnet dist.<br>(cm)', k: 'r', fmt: x => x.toFixed(1) },
        ...[0, 1, 2, 3].map(k => ({ label: `θ<sub>${k + 1}</sub>`, v: r => r.th ? r.th[k] : null, fmt: x => x + '°' })),
        { label: 'θ̄', v: thm, fmt: x => x.toFixed(1) + '°', calc: 1 }, { label: 'B = B<sub>H</sub> tan θ<br>(10⁻⁵ T)', v: r => thm(r) == null ? null : P.BH * Math.tan(thm(r) * DEG) * 1e5, fmt: x => x.toFixed(3), calc: 1 },
        { label: 'e/m<br>(10¹¹ C/kg)', v: r => emOf(r) == null ? null : emOf(r) / 1e11, fmt: x => x.toFixed(3), calc: 1 }]
    });
    const res = resultBox();
    function analyse() {
      const v = tbl.rows.map(emOf).filter(x => x != null);
      if (!v.length) { res.set('<h4>Calculation</h4><p class="small muted">Complete the three steps for a plate voltage: deflection y, magnet distance for zero deflection, and the four magnetometer readings. e/m = yV/(B² l L d).</p>'); return; }
      const em = mean(v);
      res.set(`<h4>Calculation</h4><div class="calc-lines"><div>e/m = y V / (B² l L d), &nbsp;B = B<sub>H</sub> tan θ, B<sub>H</sub> = 3.7 × 10⁻⁵ T</div><div>l = 0.040 m, L = 0.200 m, d = 0.0040 m</div><div>Values: ${v.map(x => (x / 1e11).toFixed(3)).join(', ')} × 10¹¹ C/kg</div></div><p class="big" style="margin-top:10px">e/m = <b>${(em / 1e11).toFixed(3)} × 10¹¹ C kg⁻¹</b></p><p class="small muted">Standard value 1.759 × 10¹¹ C kg⁻¹; error ${pctErr(em, EM).toFixed(1)}%. Errors of 5–10% are normal for this method because y and θ are small readings and B enters squared.</p>`);
    }
    notes.append(card('Lab notebook', 'readings are saved in this browser', tbl.el), card('Result', '', res.el)); analyse();
  }
});

/* ================= experiments 11–12: G.M. counter ================= */
function gmModel(R, R0) {
  const Vs = Math.round(R.range(355, 395)), Vt = Vs + Math.round(R.range(35, 50)), Ve = Vt + Math.round(R.range(230, 260));
  const slope = R.range(0.022, 0.042);
  const shape = V => {
    if (V < Vs) return 0;
    if (V < Vt) { const u = (V - Vs) / (Vt - Vs); return u * u * (3 - 2 * u); }
    const p = 1 + slope * (V - Vt) / 100;
    if (V <= Ve) return p;
    return (1 + slope * (Ve - Vt) / 100) * Math.exp((V - Ve) / 17);
  };
  return { Vs, Vt, Ve, slope, shape, R0, bg: 0.42 };
}
const ALU = [0.10, 0.10, 0.20, 0.25, 0.50, 1.00];
function gmBench(root, which) {
  const id = which === 11 ? 'exp11' : 'exp12';
  const R = apparatus(id);
  const M = gmModel(R, which === 11 ? R.range(38, 48) : R.range(70, 82));
  const mu = R.range(16.5, 19.0); // cm^-1 in Al
  const tau = 2.5e-4;
  const st = Object.assign({ V: 300, src: true, preset: 60, speed: 5, foils: [false, false, false, false, false, false], sound: false }, LS.get(id + '.st', {}));
  const save = () => LS.set(id + '.st', st);
  const thick = () => ALU.reduce((s, t, i) => s + (st.foils[i] ? t : 0), 0); // mm
  function trueRate() {
    const sh = M.shape(st.V); let r = M.bg * sh;
    if (st.src) { let s = M.R0 * sh; if (which === 12) s *= Math.exp(-mu * thick() / 10) + 0.006; r += s; }
    return r / (1 + r * tau);
  }
  const run = { on: false, t: 0, N: 0, done: false, recorded: false };
  const { instr, notes } = benchCols(root);
  const setup = canvasView(h('div'), { aspect: 0.5, maxH: 220, draw: drawSetup, alt: 'G.M. counter set-up' });
  const roN = readout('Counts'), roT = readout('Time'), roV = readout('High voltage');
  roN.el.style.minWidth = '150px';
  const hv = slider({ label: 'High voltage (EHT)', min: 250, max: 720, step: 10, value: st.V, fmt: v => v.toFixed(0) + ' V', fine: [['−50', -50], ['−10', -10], ['+10', 10], ['+50', 50]], onInput: (v, src) => { if (src === 'sync') return; if (run.on) { hv.set(st.V, 'sync'); sb.say('Stop the count before changing the voltage.', 'warn'); return; } st.V = v; save(); upd(); setup.redraw(); warnHV(); } });
  const preset = seg({ label: 'Counting time', value: st.preset, options: [[10, '10 s'], [30, '30 s'], [60, '60 s'], [100, '100 s']], onChange: v => { st.preset = v; save(); reset(); } });
  const speed = seg({ label: 'Speed', value: st.speed, options: [[1, '×1'], [5, '×5'], [20, '×20']], onChange: v => { st.speed = v; save(); } });
  const srcChk = check({ label: 'Source in holder', value: st.src, onChange: v => { if (run.on) { srcChk.set(st.src); return; } st.src = v; save(); setup.redraw(); reset(); } });
  const sndChk = check({ label: 'Click sound', value: st.sound, onChange: v => { st.sound = v; save(); } });
  const foilBox = h('div', { class: 'opts' });
  if (which === 12) {
    foilBox.append(h('span', { class: 'small muted' }, 'Aluminium absorbers:'));
    ALU.forEach((t, i) => { const c = check({ label: t.toFixed(2) + ' mm', value: st.foils[i], onChange: v => { if (run.on) { c.set(st.foils[i]); return; } st.foils[i] = v; save(); setup.redraw(); upd(); reset(); } }); foilBox.append(c.el); });
  }
  const bStart = h('button', { type: 'button', class: 'btn pri' }, 'Start count'), bStop = h('button', { type: 'button', class: 'btn' }, 'Stop'), bReset = h('button', { type: 'button', class: 'btn' }, 'Reset');
  const targets = which === 11 ? [['char', 'Characteristic table (count at this voltage)'], ['rel', 'Reliability table (repeated count)'], ['bg', 'Background (source removed)']] : [['abs', 'Absorber table (count with this thickness)'], ['bg', 'Background (source removed)']];
  const tgt = selectBox({ options: targets }); tgt.sel.setAttribute('aria-label', 'Record the count into');
  const bRec = h('button', { type: 'button', class: 'btn pri' }, 'Record count');
  const sb = statusBox();
  sb.say(which === 11 ? 'Raise the voltage until counts begin, then count at steps of 20 V.' : 'Set the voltage to the operating voltage (middle of the plateau), take a background count, then count with increasing absorber thickness.');
  instr.append(card('G.M. counter and scaler', which === 12 ? 'source: Sr-90 (β), absorbers: aluminium' : 'source: Sr-90 (β)', setup.wrap,
    h('div', { class: 'readouts' }, roN.el, roT.el, roV.el), hv.el, h('div', { class: 'opts' }, preset.el, speed.el), h('div', { class: 'opts' }, srcChk.el, sndChk.el), which === 12 ? foilBox : null,
    h('div', { class: 'recrow' }, bStart, bStop, bReset), h('div', { class: 'recrow' }, h('span', { class: 'small muted' }, 'Record into'), tgt.sel, bRec), sb.el));
  function upd() { roN.set(String(run.N).padStart(6, ' ')); roT.set(run.t.toFixed(1) + ' / ' + st.preset + ' s'); roV.set(st.V + ' V'); }
  function reset() { run.on = false; run.t = 0; run.N = 0; run.done = false; run.recorded = false; upd(); }
  function warnHV() { if (st.V > M.Ve + 15) sb.say('Warning: the count rate is rising steeply. The tube is entering the continuous-discharge region. Reduce the voltage at once to avoid damaging the tube.', 'warn'); }
  bStart.addEventListener('click', () => { if (run.done || run.on) reset(); run.on = true; sb.say('Counting…'); });
  bStop.addEventListener('click', () => { run.on = false; });
  bReset.addEventListener('click', reset);
  upd();
  let actx = null;
  function click() { try { actx = actx || new (window.AudioContext || window.webkitAudioContext)(); const o = actx.createOscillator(), g = actx.createGain(); o.type = 'square'; o.frequency.value = 1800 + Math.random() * 400; g.gain.setValueAtTime(0.06, actx.currentTime); g.gain.exponentialRampToValueAtTime(0.0001, actx.currentTime + 0.012); o.connect(g).connect(actx.destination); o.start(); o.stop(actx.currentTime + 0.015); } catch (e) { } }
  loop(dt => {
    if (!run.on) return;
    let d = dt * st.speed; if (run.t + d >= st.preset) { d = st.preset - run.t; }
    const n = poisson(trueRate() * d); run.N += n; run.t += d;
    if (st.sound && st.speed === 1) for (let k = 0; k < Math.min(n, 3); k++) setTimeout(click, Math.random() * dt * 1000);
    if (run.t >= st.preset - 1e-9) { run.on = false; run.done = true; const hot = st.V > M.Ve + 15; sb.say(`Counting complete: ${run.N} counts in ${st.preset} s. Press “Record count”.` + (hot ? ' <br>Warning: the tube is in the continuous-discharge region. Reduce the voltage at once.' : ''), hot ? 'warn' : 'ok'); }
    upd();
  });
  bRec.addEventListener('click', () => {
    if (!run.done) return sb.say('Complete a full count (press Start and wait for the preset time) before recording.', 'warn');
    if (run.recorded) return sb.say('This count is already recorded. Start a new count.', 'warn');
    const t = tgt.get(); const row = { V: st.V, N: run.N, t: st.preset };
    if (t === 'bg') { if (st.src) return sb.say('For background, remove the source first (untick “Source in holder”).', 'warn'); tBg.add(row); }
    else {
      if (!st.src) return sb.say('The source is not in the holder. Put it back, or record this count as background.', 'warn');
      if (t === 'char') tA.add(row);
      else if (t === 'rel') { if (tB.rows.length && tB.rows[0].V !== st.V) return sb.say(`Keep the same voltage (${tB.rows[0].V} V) and time for all repeated counts.`, 'warn'); if (tB.rows.length && tB.rows[0].t !== st.preset) return sb.say(`Use the same counting time (${tB.rows[0].t} s) for all repeated counts.`, 'warn'); tB.add(row); }
      else if (t === 'abs') tA.add(Object.assign(row, { x: +thick().toFixed(2) }));
    }
    run.recorded = true; sb.say('Recorded.', 'ok');
  });
  function drawSetup(ctx, W, H) {
    const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
    const cx = W * 0.32, base = H - 14, cw = Math.min(150, W * 0.36);
    ctx.fillStyle = T.muted; ctx.globalAlpha = .22; ctx.fillRect(cx - cw / 2, 18, cw, base - 18); ctx.globalAlpha = 1; ctx.strokeStyle = T.muted; ctx.strokeRect(cx - cw / 2, 18, cw, base - 18);
    ctx.fillStyle = T.panel; ctx.fillRect(cx - cw / 2 + 14, 26, cw - 28, base - 34);
    // GM tube
    ctx.fillStyle = T.accent; ctx.globalAlpha = .3; ctx.fillRect(cx - 16, 6, 32, H * 0.36); ctx.globalAlpha = 1; ctx.strokeStyle = T.accent; ctx.strokeRect(cx - 16, 6, 32, H * 0.36);
    ctx.strokeStyle = T.ink; ctx.beginPath(); ctx.moveTo(cx, 10); ctx.lineTo(cx, 6 + H * 0.36 - 4); ctx.stroke();
    // shelves
    const shelves = 5; for (let k = 0; k < shelves; k++) { const y = 6 + H * 0.36 + 14 + k * ((base - (6 + H * 0.36 + 14)) / shelves); ctx.strokeStyle = T.line; ctx.beginPath(); ctx.moveTo(cx - cw / 2 + 14, y); ctx.lineTo(cx + cw / 2 - 14, y); ctx.stroke(); }
    const yAbs = 6 + H * 0.36 + 8; const tk = thick();
    if (which === 12 && tk > 0) { const hh = Math.max(3, tk * 7); ctx.fillStyle = '#9aa6ad'; ctx.fillRect(cx - 34, yAbs, 68, hh); ctx.strokeStyle = T.ink; ctx.strokeRect(cx - 34, yAbs, 68, hh); }
    const ySrc = base - 40;
    if (st.src) { ctx.fillStyle = T.bad; ctx.beginPath(); ctx.arc(cx, ySrc, 7, 0, 7); ctx.fill(); ctx.strokeStyle = T.bad; ctx.globalAlpha = .5; for (let k = 0; k < 5; k++) { const a = -Math.PI / 2 + (k - 2) * 0.18; ctx.beginPath(); ctx.moveTo(cx, ySrc - 8); ctx.lineTo(cx + Math.cos(a) * 40, ySrc - 8 + Math.sin(a) * 40); ctx.stroke(); } ctx.globalAlpha = 1; }
    labelText(ctx, T, 'G.M. tube', cx + 22, 18, 'left'); labelText(ctx, T, 'lead castle', cx, base + 6);
    if (st.src) labelText(ctx, T, 'β source', cx + 14, ySrc, 'left');
    if (which === 12) labelText(ctx, T, `absorber ${tk.toFixed(2)} mm`, cx + 40, yAbs + 4, 'left');
    // scaler box
    const sx = W * 0.62, sw = W * 0.34; ctx.fillStyle = T.ink; ctx.globalAlpha = .9; ctx.fillRect(sx, H * 0.22, sw, H * 0.5); ctx.globalAlpha = 1;
    ctx.fillStyle = '#ff6a4d'; ctx.font = `600 ${Math.min(22, sw / 7)}px ${T.mono}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(String(run.N).padStart(6, '0'), sx + sw / 2, H * 0.4);
    ctx.fillStyle = '#9fb0b8'; ctx.font = `10px ${T.sans}`; ctx.fillText(`scaler · EHT ${st.V} V`, sx + sw / 2, H * 0.62);
    ctx.strokeStyle = T.muted; ctx.beginPath(); ctx.moveTo(cx + 16, 14); ctx.bezierCurveTo(W * 0.5, 4, W * 0.5, H * 0.4, sx, H * 0.45); ctx.stroke();
  }
  loop(() => setup.now());
  const rate = r => r.N / r.t * 60;
  const bgRate = () => tBg.rows.length ? mean(tBg.rows.map(rate)) : 0;
  const bgCols = [{ label: 'V (volt)', k: 'V' }, { label: 'Counts N', k: 'N' }, { label: 't (s)', k: 't' }, { label: 'Rate (counts/min)', v: rate, fmt: v => v.toFixed(1), calc: 1 }];
  const tBg = new ObsTable({ key: id + '.bg', title: 'Background counts', columns: bgCols, deletable: true, onChange: analyse });
  let tA, tB = null, plot, res = resultBox();
  if (which === 11) {
    tA = new ObsTable({ key: id + '.A', title: 'Table 1. Count rate against voltage (characteristic)', deletable: true, sort: (a, b) => a.V - b.V, onChange: analyse, columns: [{ label: 'Voltage V (volt)', k: 'V' }, { label: 'Counts N', k: 'N' }, { label: 'Time t (s)', k: 't' }, { label: 'Count rate (counts/min)', v: rate, fmt: v => v.toFixed(0), calc: 1 }] });
    tB = new ObsTable({ key: id + '.B', title: 'Table 2. Repeated counts at the operating voltage (reliability)', deletable: true, onChange: analyse, columns: [{ label: 'Trial', v: (r, i) => i + 1 }, { label: 'V (volt)', k: 'V' }, { label: 't (s)', k: 't' }, { label: 'Counts N', k: 'N' }, { label: 'N − N̄', v: (r, i, rows) => r.N - mean(rows.map(x => x.N)), fmt: v => v.toFixed(1), calc: 1 }, { label: '(N − N̄)²', v: (r, i, rows) => (r.N - mean(rows.map(x => x.N))) ** 2, fmt: v => v.toFixed(1), calc: 1 }] });
    plot = plotCard('Graph: count rate against voltage', { get: () => { const p = tA.rows.map(r => [r.V, rate(r)]); const pl = plateau(); return { xLabel: 'Applied voltage (V)', yLabel: 'Count rate (counts/min)', series: [{ pts: p, join: true }], vlines: pl ? [{ x: pl.V1, label: 'V₁' }, { x: pl.V2, label: 'V₂' }, { x: pl.Vop, label: 'operating', dy: 14 }] : [], zeroY: true, xDec: 0, yDec: 0 }; } });
  } else {
    const corr = r => rate(r) - bgRate();
    tA = new ObsTable({ key: id + '.A', title: 'Table. Count rate against absorber thickness', deletable: true, sort: (a, b) => a.x - b.x, onChange: analyse, columns: [{ label: 'Thickness x (mm)', k: 'x', fmt: v => v.toFixed(2) }, { label: 'V (volt)', k: 'V' }, { label: 'Counts N', k: 'N' }, { label: 't (s)', k: 't' }, { label: 'R (counts/min)', v: rate, fmt: v => v.toFixed(1), calc: 1 }, { label: 'R − R<sub>b</sub>', v: corr, fmt: v => v.toFixed(1), calc: 1 }, { label: 'ln(R − R<sub>b</sub>)', v: r => corr(r) > 0 ? Math.log(corr(r)) : null, fmt: v => v.toFixed(3), calc: 1 }] });
    plot = plotCard('Graph: ln(R − R_b) against thickness', { get: () => { const p = tA.rows.filter(r => corr(r) > 0).map(r => [r.x, Math.log(corr(r))]); return { xLabel: 'Absorber thickness x (mm)', yLabel: 'ln (corrected count rate)', series: [{ pts: p, fit: p.length >= 2 ? linreg(p) : null }], zeroX: true }; } });
  }
  const inV1 = h('input', { type: 'number', step: 10, id: id + '-v1', 'aria-label': 'Plateau start V1' }), inV2 = h('input', { type: 'number', step: 10, id: id + '-v2', 'aria-label': 'Plateau end V2' });
  const plIn = which === 11 ? h('div', { class: 'opts' }, h('label', { class: 'fld', for: id + '-v1' }, h('span', null, 'Plateau from V₁ ='), inV1), h('label', { class: 'fld', for: id + '-v2' }, h('span', null, 'to V₂ ='), inV2), h('span', { class: 'small muted' }, 'Suggested from your data; change them after reading your graph.')) : null;
  let userPl = LS.get(id + '.pl', null);
  if (plIn) { [inV1, inV2].forEach(i => i.addEventListener('change', () => { userPl = { V1: +inV1.value, V2: +inV2.value }; LS.set(id + '.pl', userPl); analyse(); })); }
  const interp = (pts, x) => { if (!pts.length) return null; if (x <= pts[0][0]) return pts[0][1]; for (let i = 1; i < pts.length; i++) if (x <= pts[i][0]) { const [x0, y0] = pts[i - 1], [x1, y1] = pts[i]; return y0 + (y1 - y0) * (x - x0) / (x1 - x0); } return pts[pts.length - 1][1]; };
  function autoPlateau() {
    const p = tA.rows.map(r => [r.V, rate(r)]).sort((a, b) => a[0] - b[0]); if (p.length < 5) return null;
    let best = null;
    for (let i = 0; i < p.length; i++) for (let j = i + 2; j < p.length; j++) {
      const seg_ = p.slice(i, j + 1); const f = linreg(seg_); if (!f) continue; const r1 = f.m * p[i][0] + f.c; if (r1 <= 0) continue;
      const sl = f.m / r1 * 100 * 100; const ok = sl >= -2 && sl < 9 && seg_.every(q => Math.abs(q[1] - (f.m * q[0] + f.c)) < 4 * Math.sqrt(q[1] / 1) + 0.04 * q[1]);
      if (ok && (!best || p[j][0] - p[i][0] > best.V2 - best.V1)) best = { V1: p[i][0], V2: p[j][0] };
    }
    return best;
  }
  function plateau() {
    const p = tA.rows.map(r => [r.V, rate(r)]).sort((a, b) => a[0] - b[0]);
    const pl = userPl && userPl.V2 > userPl.V1 ? userPl : autoPlateau(); if (!pl) return null;
    const inPl = p.filter(q => q[0] >= pl.V1 - 1e-6 && q[0] <= pl.V2 + 1e-6); const lf = inPl.length >= 3 ? linreg(inPl) : null;
    const R1 = lf ? lf.m * pl.V1 + lf.c : interp(p, pl.V1), R2 = lf ? lf.m * pl.V2 + lf.c : interp(p, pl.V2); if (R1 == null || !(R1 > 0)) return null;
    return { V1: pl.V1, V2: pl.V2, R1, R2, slope: (R2 - R1) / R1 * 100 / (pl.V2 - pl.V1) * 100, Vop: Math.round((pl.V1 + pl.V2) / 2 / 10) * 10 };
  }
  function analyse() {
    plot.view.redraw();
    if (which === 11) {
      const p = tA.rows.map(r => [r.V, rate(r)]).sort((a, b) => a[0] - b[0]);
      const pl = plateau(); let html = '<h4>Characteristic</h4>';
      if (pl && document.activeElement !== inV1 && document.activeElement !== inV2) { inV1.value = pl.V1; inV2.value = pl.V2; }
      const start = p.find(q => q[1] > 0);
      if (!pl) html += `<p class="small muted">Record counts from the starting voltage up to the end of the plateau (about 15–20 voltages, 20 V apart).${start ? ` Counting started at about ${start[0]} V.` : ''}</p>`;
      else html += `<div class="calc-lines">${start ? `<div>Starting potential ≈ ${start[0]} V</div>` : ''}<div>Plateau: V₁ = ${pl.V1} V (R₁ = ${pl.R1.toFixed(0)} cpm) to V₂ = ${pl.V2} V (R₂ = ${pl.R2.toFixed(0)} cpm)</div><div>Plateau length = ${pl.V2 - pl.V1} V</div><div class="small muted">R₁ and R₂ are read from the best straight line through the plateau points, so a single noisy count does not change the slope much.</div><div>Slope = (R₂ − R₁)/R₁ × 100/(V₂ − V₁) × 100% = <b>${pl.slope.toFixed(2)}% per 100 V</b></div><div>Operating voltage (middle of plateau) ≈ <b>${pl.Vop} V</b></div></div>`;
      const n = tB.rows.length; html += '<h4 style="margin-top:12px">Reliability (statistics of counting)</h4>';
      if (n < 5) html += `<p class="small muted">Take at least 10 repeated counts of equal time at the operating voltage (${n} so far).</p>`;
      else {
        const Ns = tB.rows.map(r => r.N), Nb = mean(Ns), sd = Math.sqrt(Ns.reduce((s, x) => s + (x - Nb) ** 2, 0) / (n - 1)), chi = Ns.reduce((s, x) => s + (x - Nb) ** 2, 0) / Nb, P = chi2P(chi, n - 1);
        const ok = P >= 0.05 && P <= 0.95;
        html += `<div class="calc-lines"><div>Mean N̄ = ${Nb.toFixed(1)}, &nbsp;√N̄ = ${Math.sqrt(Nb).toFixed(2)}</div><div>Standard deviation σ = √[Σ(N − N̄)²/(n − 1)] = ${sd.toFixed(2)}</div><div>χ² = Σ(N − N̄)²/N̄ = ${chi.toFixed(2)} with ${n - 1} degrees of freedom → P = ${P.toFixed(3)}</div></div>`;
        html += `<p class="big" style="margin-top:8px">${ok ? 'The counter is <b>reliable</b>' : 'The counter is <b>not reliable</b>'}</p><p class="small muted">${ok ? `σ ≈ √N̄ and 0.05 &lt; P &lt; 0.95, so the counts follow Poisson statistics: the counter is working properly.` : 'P lies outside 0.05–0.95; the fluctuations are not consistent with random (Poisson) counting. Check the voltage and take more counts.'}</p>`;
      }
      if (tBg.rows.length) html += `<p class="small muted">Background rate = ${bgRate().toFixed(1)} counts/min.</p>`;
      res.set(html);
    } else {
      const Rb = bgRate(); const p = tA.rows.filter(r => rate(r) - Rb > 0).map(r => [r.x, Math.log(rate(r) - Rb)]);
      let html = '<h4>Calculation</h4>';
      if (!tBg.rows.length) html += '<p class="small muted">Take a background count (source removed) at the same voltage.</p>';
      if (p.length < 3) { res.set(html + '<p class="small muted">Record counts for at least 5–6 thicknesses from 0 to about 2 mm.</p>'); return; }
      const f = linreg(p); const muMM = -f.m, muCM = muMM * 10, mum = muCM / 2.70, half = 0.693 / muMM, E = (17 / mum) ** (1 / 1.14);
      html += `<div class="calc-lines"><div>Background R<sub>b</sub> = ${Rb.toFixed(1)} counts/min</div><div>Slope of ln(R − R<sub>b</sub>) vs x = ${f.m.toFixed(3)} mm⁻¹</div><div>µ = − slope = ${muMM.toFixed(3)} mm⁻¹ = <b>${muCM.toFixed(2)} cm⁻¹</b></div><div>Mass absorption coefficient µ/ρ = ${muCM.toFixed(2)}/2.70 = ${mum.toFixed(2)} cm² g⁻¹</div><div>Half-value thickness x½ = 0.693/µ = ${half.toFixed(3)} mm</div><div>Estimated E<sub>max</sub> from µ/ρ = 17 E<sup>−1.14</sup>: ${E.toFixed(2)} MeV</div></div>`;
      html += `<p class="big" style="margin-top:10px">Linear absorption coefficient of β-particles in aluminium µ = <b>${muCM.toFixed(1)} cm⁻¹</b></p><p class="small muted">For Sr-90/Y-90 β-rays (E<sub>max</sub> = 2.28 MeV) the expected µ/ρ in aluminium is about 6.5 cm² g⁻¹, i.e. µ ≈ 17.5 cm⁻¹.</p>`;
      res.set(html);
    }
  }
  const tables = h('div', { class: 'stack' }, tA.el, tB ? plIn : null, tB ? tB.el : null, tBg.el);
  if (which === 11) { tables.innerHTML = ''; tables.append(tA.el, tB.el, tBg.el); }
  notes.append(card('Lab notebook', 'readings are saved in this browser', tables), plot.el, which === 11 ? card('Plateau', '', plIn) : null, card('Result', '', res.el));
  analyse();
}
const GM_THEORY_COMMON = String.raw`
<h2>Geiger–Müller counter</h2>
<p>A G.M. tube is a metal (or graphite-coated glass) cylinder acting as the <b>cathode</b>, with a thin tungsten wire along its axis as the <b>anode</b>. It is filled with an inert gas (argon or neon) at low pressure, with a small amount of a quenching gas (alcohol vapour or a halogen). A thin mica end-window lets β-particles enter. A high voltage of a few hundred volts is applied between anode and cathode through a large resistance.</p>
<p>An ionising particle produces a few ion pairs. The electrons are accelerated towards the thin anode, where the field \(E = V/[r\ln(b/a)]\) is very strong. They gain enough energy to ionise further, giving an <b>avalanche</b>. Ultraviolet photons from the avalanche spread the discharge along the whole length of the wire (Geiger discharge). A large voltage pulse is produced whose size does not depend on the energy of the incident particle. The quenching gas stops the discharge, and the tube is ready for the next particle after the <b>dead time</b> (about 100–300 µs).</p>`;

EXPS.push({
  no: 11, id: 'exp11', group: 'Nuclear physics', short: 'Characteristics of a G.M. counter',
  title: 'Characteristics of a Geiger–Müller counter and its reliability',
  aim: 'To study the characteristics of Geiger Muller (G.M.) counter and its reliability.',
  apparatus: ['G.M. tube with end window in a lead castle', 'Scaler (counter) with variable EHT supply (0–1000 V) and timer', 'β-source (Sr-90) and holder', 'Tongs for handling the source'],
  theory: GM_THEORY_COMMON + String.raw`
<h2>Characteristic curve</h2>
<p>If the count rate is measured for a source at fixed position while the voltage is increased:</p>
<ul>
<li>Below the <b>starting potential</b> the pulses are too small to be counted.</li>
<li>Above it the count rate rises rapidly (proportional region) up to the <b>threshold</b> voltage \(V_1\).</li>
<li>From \(V_1\) to \(V_2\) the count rate is nearly constant: the <b>plateau</b>. Every particle entering the tube now produces a full Geiger discharge.</li>
<li>Beyond \(V_2\) the count rate increases steeply because of spurious and continuous discharge, which damages the tube.</li></ul>
<p>The quality of a tube is judged by the plateau length (100–300 V) and its <b>slope</b>, which should be small (a few % per 100 V):</p>
<div class="eq">\[\text{Slope} = \frac{R_2 - R_1}{R_1}\times\frac{100}{V_2 - V_1}\times 100\ \%\ \text{per 100 V}\]</div>
<p>The <b>operating voltage</b> is chosen near the middle of the plateau, where small changes in voltage hardly change the count rate.</p>
<h2>Reliability: statistics of counting</h2>
<p>Radioactive decay is random, so repeated counts \(N\) of equal duration fluctuate about the mean \(\bar N\) according to the <b>Poisson distribution</b>, whose standard deviation is</p>
<div class="eq">\[\sigma = \sqrt{\bar N}\]</div>
<p>A counter is reliable if its fluctuations are only those expected from Poisson statistics. This is tested with the <b>chi-square test</b>:</p>
<div class="eq">\[\chi^{2} = \sum_{i=1}^{n}\frac{(N_i-\bar N)^{2}}{\bar N}\]</div>
<p>with \(n-1\) degrees of freedom. If the probability \(P\) of getting a value of χ² at least this large lies between about 0.05 and 0.95 (some books use 0.1–0.9), the counter is working properly. Very small P means extra fluctuations (faulty counter); very large P means the counts are too regular.</p>`,
  procedure: String.raw`
<h2>A. Characteristic curve</h2>
<ol class="steps">
<li>Connect the G.M. tube to the scaler. Make sure the EHT control is at minimum. Switch on and let the scaler warm up.</li>
<li>Place the β-source in a fixed shelf below the window (handle it with tongs).</li>
<li>Set the counting time (e.g. 60 s). Increase the EHT slowly until counts are just obtained: this is the <b>starting potential</b>.</li>
<li>Increase the voltage in steps of 20 V, counting for the same time at each step. Record V and N.</li>
<li>Continue until the count rate begins to rise steeply (about 50 V beyond the end of the plateau). <b>Do not go further</b>; reduce the voltage immediately.</li>
<li>Plot count rate against voltage. Find the threshold V₁, the end of the plateau V₂, the plateau length, its slope, and the operating voltage.</li></ol>
<h2>B. Background and reliability</h2>
<ol class="steps">
<li>Set the operating voltage. Remove the source and count for 100 s or more to find the background rate.</li>
<li>Replace the source. Take at least 10 (preferably 20) counts of the same duration (e.g. 30 s).</li>
<li>Calculate \(\bar N\), the standard deviation, \(\sqrt{\bar N}\) and χ². Find P from the χ² table and decide whether the counter is reliable.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> set the voltage, press <i>Start count</i>, wait for the preset time, then press <i>Record count</i> after choosing the table. Use the speed setting (×5, ×20) to save time; the counting statistics are the same as in real time.</div>`,
  precautions: String.raw`<li>Never exceed the end of the plateau; continuous discharge damages the tube.</li><li>Handle the radioactive source with tongs; keep it in its container when not in use and away from the body.</li><li>Keep the source position fixed throughout part A.</li><li>Raise the voltage slowly and allow the counter to stabilise.</li><li>Do not touch the thin end window.</li>`,
  errors: String.raw`<li>Statistical fluctuation (∝ √N); count long enough for large N.</li><li>Dead time losses at high count rates.</li><li>Background radiation.</li>`,
  viva: [
    ['What is the plateau of a G.M. counter?', 'The range of voltage over which the count rate is nearly independent of the applied voltage.'],
    ['Why is the operating voltage chosen at the middle of the plateau?', 'Small changes in the supply voltage then produce almost no change in the count rate.'],
    ['Why does the plateau have a small slope?', 'Because of spurious (after-) pulses and a slight increase in the sensitive volume with voltage.'],
    ['What is the function of the quenching gas?', 'It absorbs the UV photons and the energy of the positive ions so that the discharge stops and secondary discharges are prevented.'],
    ['What is dead time?', 'The time after a discharge during which the tube cannot respond to another particle (≈ 100–300 µs), because the positive ion sheath reduces the field near the anode.'],
    ['Can a G.M. counter distinguish between α, β and γ?', 'No. The pulse size is the same for all particles; it only counts them.'],
    ['What is the standard deviation of a count N?', '√N, according to Poisson statistics.'],
    ['What does the χ² test tell you?', 'Whether the observed fluctuations are consistent with random (Poisson) counting, i.e. whether the counter is reliable.'],
    ['Why is a thin mica window used?', 'β-particles have a short range in solids; a thin window lets them enter the tube.']],
  bench(root) { gmBench(root, 11); }
});
EXPS.push({
  no: 12, id: 'exp12', group: 'Nuclear physics', short: 'Absorption coefficient of β-particles',
  title: 'Linear absorption coefficient of β-particles using a G.M. counter',
  aim: 'To determine the linear absorption coefficient of β-particles in a matter using a G.M. counter.',
  apparatus: ['G.M. tube in a lead castle with shelves', 'Scaler with EHT supply and timer', 'β-source (Sr-90)', 'Set of aluminium absorber sheets (0.10–1.00 mm)', 'Screw gauge, tongs'],
  theory: GM_THEORY_COMMON + String.raw`
<h2>Absorption of β-particles</h2>
<p>β-particles are emitted with a continuous spectrum of energies up to a maximum \(E_{max}\). When they pass through matter they lose energy by ionisation and are scattered. Because of the continuous energy spectrum, the intensity transmitted through an absorber of thickness \(x\) decreases very nearly <b>exponentially</b> (for thicknesses well below the range):</p>
<div class="eq">\[I = I_0\,e^{-\mu x}\qquad\Rightarrow\qquad \ln I = \ln I_0 - \mu x\]</div>
<p>\(\mu\) is the <b>linear absorption coefficient</b> (unit cm⁻¹). A graph of \(\ln(R-R_b)\) against \(x\), where \(R\) is the count rate and \(R_b\) the background rate, is a straight line of slope \(-\mu\).</p>
<p>Related quantities:</p>
<div class="eq">\[\text{Mass absorption coefficient } \mu_m = \frac{\mu}{\rho}\ (\text{cm}^2\,\text{g}^{-1}),\qquad \text{half thickness } x_{1/2} = \frac{\ln 2}{\mu} = \frac{0.693}{\mu}\]</div>
<p>For aluminium an empirical relation is \(\mu_m \approx 17\,E_{max}^{-1.14}\) with \(E_{max}\) in MeV. At large thicknesses the count rate levels off at a small value because of bremsstrahlung (X-rays) and background.</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Set up the G.M. counter and set the EHT to the operating voltage found from the characteristic curve.</li>
<li>Remove all sources from the neighbourhood and take a background count for 100 s.</li>
<li>Place the β-source on a lower shelf. Count for a fixed time without any absorber.</li>
<li>Measure the thickness of each aluminium sheet with a screw gauge. Place absorbers on the shelf between the source and the window, increasing the total thickness step by step (0.10, 0.20, 0.30 mm …). Count for the same time at each thickness. Do not move the source.</li>
<li>Correct each count rate for background. Plot ln(R − R<sub>b</sub>) against x and find μ from the slope. Calculate μ/ρ (ρ = 2.70 g cm⁻³ for Al) and the half-value thickness.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> tick the aluminium sheets to build up a thickness (the total is shown in the drawing). First find the plateau (raise the EHT until the count rate stops increasing); the operating voltage is near the middle of the plateau, about 100–150 V above the voltage where counting starts.</div>`,
  precautions: String.raw`<li>Operate the tube at the middle of its plateau.</li><li>Keep the distance between source and window fixed; place absorbers close to the tube window.</li><li>Count for long enough that N is large (statistical error 1/√N).</li><li>Handle the source with tongs.</li>`,
  errors: String.raw`<li>Statistical fluctuations, especially for thick absorbers (small counts).</li><li>Scattering of β-particles into the window from the edges of the absorber.</li><li>Error in the thickness of the sheets.</li>`,
  viva: [
    ['Why do β-particles show approximately exponential absorption?', 'Because they are emitted with a continuous range of energies, and lower energy particles are stopped at smaller thicknesses; the combined effect is close to exponential.'],
    ['What is the half-value thickness?', 'The thickness of absorber that reduces the intensity to half: x½ = 0.693/µ.'],
    ['Why is the mass absorption coefficient used?', 'µ/ρ is nearly independent of the absorbing material for β-particles, so it characterises the radiation.'],
    ['Why is background count subtracted?', 'The counter also records cosmic rays and natural radioactivity; only the counts due to the source obey the absorption law.'],
    ['Why does the count rate not fall to zero for thick absorbers?', 'Because of background and of bremsstrahlung X-rays produced by the β-particles in the absorber, which are more penetrating.'],
    ['How can you find the maximum energy of β-particles?', 'From the range (Feather’s method) or from µ/ρ using the empirical relation µ/ρ = 17 E_max^−1.14.']],
  bench(root) { gmBench(root, 12); }
});

/* ================= experiment 13: series LCR resonance ================= */
EXPS.push({
  no: 13, id: 'exp13', group: 'Electricity & CRO', short: 'Series LCR resonance and Q',
  title: 'Resonant frequency and quality factor of a series LCR circuit',
  aim: 'To determine the resonant frequency and quality factor of series LCR circuit.',
  apparatus: ['Audio-frequency signal generator (sine wave)', 'Inductance coil (with its resistance marked)', 'Capacitors', 'Resistance box', 'AC milliammeter (or AC voltmeter / CRO across R)', 'AC voltmeter, connecting wires'],
  theory: String.raw`
<h2>Series LCR circuit</h2>
<p>A resistance \(R\), an inductance \(L\) (of resistance \(r\)) and a capacitance \(C\) are connected in series with an AC source of rms voltage \(V\) and frequency \(f\) (\(\omega = 2\pi f\)). The reactances are \(X_L = \omega L\) and \(X_C = 1/(\omega C)\). The impedance and current are</p>
<div class="eq">\[Z = \sqrt{R_T^{2} + \left(\omega L - \frac{1}{\omega C}\right)^{2}},\qquad I = \frac{V}{Z},\qquad \tan\phi = \frac{X_L - X_C}{R_T}\]</div>
<p>where \(R_T = R + r\) is the total resistance.</p>
<h3>Resonance</h3>
<p>At one frequency \(X_L = X_C\). Then \(Z = R_T\) is minimum, the current is maximum (\(I_0 = V/R_T\)) and in phase with the voltage. This is <b>series resonance</b>:</p>
<div class="eq">\[\omega_0 L = \frac{1}{\omega_0 C}\qquad\Rightarrow\qquad f_0 = \frac{1}{2\pi\sqrt{LC}}\]</div>
<p>Below \(f_0\) the circuit is capacitive (current leads), above it inductive (current lags).</p>
<h3>Quality factor and bandwidth</h3>
<p>The sharpness of resonance is measured by the <b>quality factor</b></p>
<div class="eq">\[Q = \frac{\omega_0 L}{R_T} = \frac{1}{R_T}\sqrt{\frac{L}{C}}\]</div>
<p>The <b>half-power frequencies</b> \(f_1\) and \(f_2\) are where the power is half its maximum, i.e. \(I = I_0/\sqrt 2\). The bandwidth is \(f_2 - f_1\) and</p>
<div class="eq">\[Q = \frac{f_0}{f_2 - f_1}\]</div>
<p>At resonance the voltage across L (or C) is Q times the applied voltage (voltage magnification): \(V_C = V_L \approx QV\). A smaller resistance gives a sharper resonance and a larger Q.</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Connect the signal generator, resistance box R, inductor L, capacitor C and the AC milliammeter in series (see the circuit diagram).</li>
<li>Note the values of L (and its resistance r), C and R. Calculate the expected \(f_0\) to choose the frequency range.</li>
<li>Set the generator output to a convenient voltage (e.g. 2 V rms) and keep it constant throughout (check with the voltmeter at each frequency).</li>
<li>Starting well below \(f_0\), increase the frequency in steps and note the current at each step. Take small steps near resonance, where the current changes rapidly. Continue well above \(f_0\).</li>
<li>Plot I against f. The frequency of maximum current is \(f_0\).</li>
<li>Draw a horizontal line at \(I_0/\sqrt2 = 0.707\,I_0\). Its intersections with the curve give \(f_1\) and \(f_2\). Calculate \(Q = f_0/(f_2-f_1)\).</li>
<li>Repeat with a different value of R and compare the sharpness of the curves.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> choose L, C and R, then change the frequency with the slider (coarse) and the buttons (fine). Press <i>Record</i> to note the frequency and current. Each combination of components keeps its own table. The phasor diagram shows how V<sub>L</sub> and V<sub>C</sub> cancel at resonance.</div>`,
  precautions: String.raw`<li>Keep the source voltage constant at every frequency.</li><li>Take closely spaced readings near the resonant frequency.</li><li>Use an AC meter suitable for the frequency range.</li><li>Include the resistance of the coil in R<sub>T</sub> when calculating the theoretical Q.</li>`,
  errors: String.raw`<li>Tolerance of the marked values of L and C (typically ±5%).</li><li>Output impedance of the generator and variation of its output voltage with frequency.</li><li>Losses in the coil core at higher frequency.</li>`,
  viva: [
    ['What is resonance in a series LCR circuit?', 'The condition X_L = X_C, at which the impedance is minimum (= R), the current is maximum and in phase with the voltage.'],
    ['Why is a series resonant circuit called an acceptor circuit?', 'Because it offers minimum impedance and accepts (passes) maximum current at the resonant frequency.'],
    ['What is the quality factor?', 'Q = ω₀L/R = f₀/(f₂ − f₁); it measures the sharpness of resonance and the voltage magnification at resonance.'],
    ['What are half-power frequencies?', 'The frequencies at which the current is I₀/√2, so that the power I²R is half its maximum value.'],
    ['How does R affect the resonance curve?', 'f₀ does not depend on R, but a larger R lowers the peak current and broadens the curve (smaller Q).'],
    ['What is the phase between V and I below and above resonance?', 'Below f₀ the circuit is capacitive and I leads V; above f₀ it is inductive and I lags V.'],
    ['Can the voltage across C be larger than the supply voltage?', 'Yes. At resonance V_C = QV, which can be many times V.'],
    ['Where is series resonance used?', 'In tuning circuits of radio receivers and in filters.']],
  bench(root) {
    const R = apparatus('exp13');
    const Ls = [[10, 12], [25, 18], [50, 28]], Cs = [0.1, 0.22, 0.47], Rs = [22, 47, 100, 220];
    const tol = { L: Ls.map(() => 1 + R.range(-0.05, 0.05)), C: Cs.map(() => 1 + R.range(-0.05, 0.05)) };
    const st = Object.assign({ Li: 1, Ci: 1, Ri: 1, f: 1200, V: 2.0, vm: 'C' }, LS.get('exp13.st', {}));
    const save = () => LS.set('exp13.st', st);
    const comp = () => { const [L, r] = Ls[st.Li]; return { L: L * 1e-3 * tol.L[st.Li], Ln: L * 1e-3, r, C: Cs[st.Ci] * 1e-6 * tol.C[st.Ci], Cn: Cs[st.Ci] * 1e-6, R: Rs[st.Ri] }; };
    const circ = () => { const c = comp(), w = 2 * Math.PI * st.f, XL = w * c.L, XC = 1 / (w * c.C), RT = c.R + c.r, Z = Math.hypot(RT, XL - XC), I = st.V / Z; return { ...c, XL, XC, RT, Z, I, phi: Math.atan2(XL - XC, RT) / DEG, VR: I * c.R, VL: I * Math.hypot(c.r, XL), VC: I * XC }; };
    const comboKey = () => `${Ls[st.Li][0]}mH-${Cs[st.Ci]}uF-${Rs[st.Ri]}R`;
    const { instr, notes } = benchCols(root);
    const sch = canvasView(h('div'), { label: 'Circuit', aspect: 0.42, minH: 150, maxH: 230, draw: drawCircuit, alt: 'Series LCR circuit' });
    const row = h('div', { class: 'cols2' }); const a = h('div', { class: 'stack' }), b = h('div'); row.append(a, b);
    const roF = readout('Generator frequency'), roI = readout('AC milliammeter'), roVm = readout('AC voltmeter');
    a.append(h('div', { class: 'readouts' }, roF.el, roI.el, roVm.el));
    const vmSel = seg({ label: 'Voltmeter across', value: st.vm, options: [['S', 'source'], ['R', 'R'], ['L', 'L'], ['C', 'C']], onChange: v => { st.vm = v; changed(); } });
    a.append(vmSel.el);
    const selL = selectBox({ label: 'L', options: Ls.map(([l, r], i) => [i, `${l} mH (r = ${r} Ω)`]), value: st.Li, onChange: v => { st.Li = +v; changed(true); } });
    const selC = selectBox({ label: 'C', options: Cs.map((c, i) => [i, `${c} µF`]), value: st.Ci, onChange: v => { st.Ci = +v; changed(true); } });
    const selR = selectBox({ label: 'R', options: Rs.map((r, i) => [i, `${r} Ω`]), value: st.Ri, onChange: v => { st.Ri = +v; changed(true); } });
    a.append(h('div', { class: 'opts' }, selL.el, selC.el, selR.el));
    const ph = canvasView(b, { label: 'Phasor diagram', height: w => Math.min(w * 0.9, 250), draw: drawPhasor, alt: 'Phasor diagram' });
    const fmin = 100, fmax = 12000, toS = f => Math.log(f / fmin) / Math.log(fmax / fmin) * 1000, fromS = s => fmin * (fmax / fmin) ** (s / 1000);
    const fS = slider({ label: 'Frequency', min: fmin, max: fmax, smin: 0, smax: 1000, sstep: 1, toS, fromS, value: st.f, fmt: v => v.toFixed(0) + ' Hz', fine: [['−100', -100], ['−10', -10], ['−1', -1], ['+1', 1], ['+10', 10], ['+100', 100]], onInput: v => { st.f = Math.round(v); changed(); } });
    const vS = slider({ label: 'Generator output (kept constant)', min: 0.5, max: 5, step: 0.1, value: st.V, fmt: v => v.toFixed(1) + ' V rms', onInput: v => { st.V = v; changed(); } });
    const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading'); const sb = statusBox(); sb.say('Record the current at frequencies below, near and above resonance.');
    instr.append(card('Series LCR circuit', 'sine-wave signal generator', sch.wrap, row, h('div', { class: 'ctls' }, fS.el, vS.el), h('div', { class: 'recrow' }, btn), sb.el));
    let tbl; const tblHost = h('div'); const res = resultBox();
    function changed(comb) { save(); sch.redraw(); ph.redraw(); upd(); if (comb) buildTable(); }
    function upd() { const c = circ(); roF.set(st.f.toFixed(0) + ' Hz'); roI.set((c.I * 1e3).toFixed(2) + ' mA'); roVm.set(({ S: st.V, R: c.VR, L: c.VL, C: c.VC })[st.vm].toFixed(3) + ' V'); }
    btn.addEventListener('click', () => {
      const c = circ(); const I = rnd(c.I * 1e3, 2);
      const i = tbl.rows.findIndex(r => r.f === st.f); if (i >= 0) tbl.update(i, { I }); else tbl.add({ f: st.f, I, V: st.V });
      if (tbl.rows.some(r => Math.abs(r.V - st.V) > 1e-6)) sb.say('The generator output has changed between readings. Keep it constant (use the same voltage for the whole table).', 'warn');
      else sb.say(`f = ${st.f} Hz, I = ${I.toFixed(2)} mA recorded.`, 'ok');
    });
    function drawCircuit(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
      const x0 = W * 0.1, x1 = W * 0.9, y0 = H * 0.22, y1 = H * 0.8; const c = comp();
      ctx.strokeStyle = T.ink; ctx.lineWidth = 1.4;
      const seg_ = [[x0, y0, x1, y0], [x1, y0, x1, y1], [x1, y1, x0, y1], [x0, y1, x0, y0]];
      ctx.beginPath(); ctx.moveTo(x0, y0); ctx.lineTo(x1, y0); ctx.lineTo(x1, y1); ctx.lineTo(x0, y1); ctx.closePath(); ctx.stroke();
      const blank = (x, y, w, hh) => { ctx.fillStyle = T.panel2; ctx.fillRect(x - w / 2, y - hh / 2, w, hh); };
      // resistor
      const rx = W * 0.3; blank(rx, y0, 54, 20); ctx.beginPath(); for (let i = 0; i <= 8; i++) { const x = rx - 24 + i * 6, y = y0 + (i === 0 || i === 8 ? 0 : (i % 2 ? -7 : 7)); i ? ctx.lineTo(x, y) : ctx.moveTo(x, y); } ctx.stroke();
      // inductor
      const lx = W * 0.52; blank(lx, y0, 60, 22); ctx.beginPath(); ctx.moveTo(lx - 30, y0); for (let i = 0; i < 4; i++) ctx.arc(lx - 22.5 + i * 15, y0, 7.5, Math.PI, 0); ctx.lineTo(lx + 30, y0); ctx.stroke();
      // capacitor
      const cx = W * 0.74; blank(cx, y0, 14, 30); ctx.beginPath(); ctx.moveTo(cx - 5, y0 - 13); ctx.lineTo(cx - 5, y0 + 13); ctx.moveTo(cx + 5, y0 - 13); ctx.lineTo(cx + 5, y0 + 13); ctx.stroke();
      // source
      const sx = W * 0.5; blank(sx, y1, 36, 36); ctx.beginPath(); ctx.arc(sx, y1, 15, 0, 7); ctx.stroke(); ctx.beginPath(); for (let i = 0; i <= 20; i++) { const x = sx - 9 + i * 0.9, y = y1 - 5 * Math.sin(i / 20 * 2 * Math.PI); i ? ctx.lineTo(x, y) : ctx.moveTo(x, y); } ctx.stroke();
      // ammeter
      const ax = x1, ay = (y0 + y1) / 2; blank(ax, ay, 30, 30); ctx.beginPath(); ctx.arc(ax, ay, 13, 0, 7); ctx.stroke();
      ctx.lineWidth = 1; labelText(ctx, T, 'mA', ax, ay, 'center', T.ink);
      labelText(ctx, T, `R = ${c.R} Ω`, rx, y0 - 16); labelText(ctx, T, `L = ${(c.Ln * 1e3).toFixed(0)} mH`, lx, y0 - 18); labelText(ctx, T, `C = ${(c.Cn * 1e6).toFixed(2)} µF`, cx, y0 - 22);
      labelText(ctx, T, `${st.V.toFixed(1)} V, ${st.f} Hz`, sx, y1 + 26);
      const vx = { S: sx, R: rx, L: lx, C: cx }[st.vm]; const vy = st.vm === 'S' ? y1 - 32 : y0 + 30;
      ctx.strokeStyle = T.accent; ctx.setLineDash([3, 3]); ctx.beginPath(); ctx.moveTo(vx - 24, st.vm === 'S' ? y1 : y0); ctx.lineTo(vx - 24, vy); ctx.lineTo(vx + 24, vy); ctx.lineTo(vx + 24, st.vm === 'S' ? y1 : y0); ctx.stroke(); ctx.setLineDash([]);
      ctx.fillStyle = T.panel; ctx.beginPath(); ctx.arc(vx, vy, 10, 0, 7); ctx.fill(); ctx.strokeStyle = T.accent; ctx.stroke(); labelText(ctx, T, 'V', vx, vy, 'center', T.accent);
    }
    function drawPhasor(ctx, W, H) {
      const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = T.panel2; ctx.fillRect(0, 0, W, H);
      const c = circ(); const vr = c.I * c.RT, vl = c.I * c.XL, vc = c.I * c.XC; const mx = Math.max(vr, vl, vc, st.V, 1e-9);
      const ox = W * 0.22, oy = H / 2, k = Math.min(W * 0.6, H * 0.42) / mx;
      const arrow = (x, y, col, lab) => { ctx.strokeStyle = col; ctx.fillStyle = col; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(ox, oy); ctx.lineTo(ox + x, oy - y); ctx.stroke(); const a = Math.atan2(-y, x); ctx.beginPath(); ctx.moveTo(ox + x, oy - y); ctx.lineTo(ox + x - 8 * Math.cos(a - 0.4), oy - y - 8 * Math.sin(a - 0.4)); ctx.lineTo(ox + x - 8 * Math.cos(a + 0.4), oy - y - 8 * Math.sin(a + 0.4)); ctx.fill(); ctx.lineWidth = 1; ctx.font = `11px ${T.sans}`; ctx.textAlign = 'left'; ctx.textBaseline = 'middle'; ctx.fillText(lab, ox + x + 6, oy - y + (Math.abs(y) < 4 ? 10 : 0)); };
      ctx.strokeStyle = T.line; ctx.beginPath(); ctx.moveTo(8, oy); ctx.lineTo(W - 8, oy); ctx.moveTo(ox, 8); ctx.lineTo(ox, H - 8); ctx.stroke();
      arrow(vr * k, 0, T.p1, 'I·R'); arrow(0, vl * k, T.p4, 'V_L'); arrow(0, -vc * k, T.p3, 'V_C');
      arrow(vr * k, (vl - vc) * k, T.sodium, `V (φ = ${c.phi.toFixed(1)}°)`);
      ctx.fillStyle = T.muted; ctx.font = `10.5px ${T.sans}`; ctx.textAlign = 'left'; ctx.textBaseline = 'top';
      ctx.fillText(c.phi > 1 ? 'inductive: current lags' : c.phi < -1 ? 'capacitive: current leads' : 'near resonance: V and I in phase', 8, 6);
    }
    function buildTable() {
      tbl = new ObsTable({
        key: 'exp13.' + comboKey(), title: `Table. Current against frequency (L = ${Ls[st.Li][0]} mH, C = ${Cs[st.Ci]} µF, R = ${Rs[st.Ri]} Ω)`, onChange: analyse, deletable: true, sort: (a, b) => a.f - b.f,
        columns: [{ label: 'Frequency f (Hz)', k: 'f' }, { label: 'Current I (mA)', k: 'I', fmt: v => v.toFixed(2) }, { label: 'Source V (V rms)', k: 'V', fmt: v => v.toFixed(1) }]
      });
      tblHost.innerHTML = ''; tblHost.append(tbl.el); analyse();
    }
    function result() {
      const p = tbl.rows.map(r => [r.f, r.I]).sort((a, b) => a[0] - b[0]); if (p.length < 5) return null;
      let k = 0; p.forEach((q, i) => { if (q[1] > p[k][1]) k = i; }); if (k === 0 || k === p.length - 1) return { p, edge: true };
      let f0 = p[k][0], I0 = p[k][1];
      const [x1, y1] = p[k - 1], [x2, y2] = p[k], [x3, y3] = p[k + 1]; const d = (x1 - x2) * (x1 - x3) * (x2 - x3);
      if (d) { const A = (x3 * (y2 - y1) + x2 * (y1 - y3) + x1 * (y3 - y2)) / d, B = (x3 * x3 * (y1 - y2) + x2 * x2 * (y3 - y1) + x1 * x1 * (y2 - y3)) / d, C = (x2 * x3 * (x2 - x3) * y1 + x3 * x1 * (x3 - x1) * y2 + x1 * x2 * (x1 - x2) * y3) / d; if (A < 0) { const fv = -B / (2 * A); if (fv > x1 && fv < x3) { f0 = fv; I0 = Math.max(I0, A * fv * fv + B * fv + C); } } }
      const hp = I0 / Math.SQRT2; let f1 = null, f2 = null;
      for (let i = k; i > 0; i--) if (p[i - 1][1] < hp && p[i][1] >= hp) { f1 = p[i - 1][0] + (hp - p[i - 1][1]) / (p[i][1] - p[i - 1][1]) * (p[i][0] - p[i - 1][0]); break; }
      for (let i = k; i < p.length - 1; i++) if (p[i][1] >= hp && p[i + 1][1] < hp) { f2 = p[i][0] + (p[i][1] - hp) / (p[i][1] - p[i + 1][1]) * (p[i + 1][0] - p[i][0]); break; }
      return { p, f0, I0, hp, f1, f2 };
    }
    const plot = plotCard('Graph: resonance curve (I against f)', { get: () => { const r = tbl ? result() : null; const p = tbl ? tbl.rows.map(x => [x.f, x.I]) : []; const vl = []; if (r && !r.edge) { vl.push({ x: r.f0, label: 'f₀' }); if (r.f1) vl.push({ x: r.f1, label: 'f₁', dy: 14 }); if (r.f2) vl.push({ x: r.f2, label: 'f₂', dy: 14 }); } return { xLabel: 'Frequency f (Hz)', yLabel: 'Current I (mA)', series: [{ pts: p, join: true }], vlines: vl, hlines: r && !r.edge ? [{ y: r.hp, label: 'I₀/√2' }] : [], zeroY: true, xDec: 0 }; } });
    function analyse() {
      plot.view.redraw();
      const c = comp(); const RT = c.R + c.r; const f0t = 1 / (2 * Math.PI * Math.sqrt(c.Ln * c.Cn)); const Qt = Math.sqrt(c.Ln / c.Cn) / RT;
      let html = `<h4>Calculation</h4><div class="calc-lines"><div>Theoretical f₀ = 1/(2π√LC) = ${f0t.toFixed(0)} Hz (marked values)</div><div>Theoretical Q = (1/R<sub>T</sub>)√(L/C) = ${Qt.toFixed(2)}, with R<sub>T</sub> = R + r = ${RT} Ω</div>`;
      const r = result();
      if (!r) { res.set(html + '<div>Record at least 8–10 readings across the resonance.</div></div>'); return; }
      if (r.edge) { res.set(html + '<div>The largest current is at the end of your range; extend the frequency range on both sides of the peak.</div></div>'); return; }
      html += `<div>From the graph: f₀ = ${r.f0.toFixed(0)} Hz, I₀ = ${r.I0.toFixed(2)} mA, I₀/√2 = ${r.hp.toFixed(2)} mA</div>`;
      if (!r.f1 || !r.f2) { res.set(html + '<div>Take readings on both sides until the current falls below I₀/√2 to find f₁ and f₂.</div></div>'); return; }
      const Q = r.f0 / (r.f2 - r.f1);
      html += `<div>f₁ = ${r.f1.toFixed(0)} Hz, f₂ = ${r.f2.toFixed(0)} Hz, bandwidth = ${(r.f2 - r.f1).toFixed(0)} Hz</div><div>Q = f₀/(f₂ − f₁) = ${Q.toFixed(2)}</div></div>`;
      html += `<p class="big" style="margin-top:10px">f₀ = <b>${r.f0.toFixed(0)} Hz</b>, &nbsp;Q = <b>${Q.toFixed(2)}</b></p><p class="small muted">Theoretical: f₀ = ${f0t.toFixed(0)} Hz (difference ${pctErr(r.f0, f0t).toFixed(1)}%), Q = ${Qt.toFixed(2)} (difference ${pctErr(Q, Qt).toFixed(1)}%). Differences of a few per cent come from the tolerance of the marked values of L and C.</p>`;
      res.set(html);
    }
    upd(); buildTable();
    notes.append(card('Lab notebook', 'each set of components has its own table', tblHost), plot.el, card('Result', '', res.el));
  }
});

/* ================= wiring workbench: equipment, wires, checking ================= */
const EQ = { body: '#36424b', body2: '#283138', edge: '#1c2328', lcd: '#c2d1ba', lcdInk: '#1d2820', txt: '#e8eef1', dim: '#9fb0b8', red: '#d6372a', black: '#232629', brass: '#c9a227', pcb: '#1d6a46', wood: '#93633a', yellow: '#f1c232' };
const mono = '"IBM Plex Mono", ui-monospace, monospace', sans = '"IBM Plex Sans", system-ui, sans-serif';
const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
function lcdBox(x, y, w, hh, txt, opt = {}) {
  return `<rect x="${x}" y="${y}" width="${w}" height="${hh}" rx="3" fill="${opt.bg || EQ.lcd}" stroke="#0e1410" stroke-width="1"/>
  <text x="${x + w - 6}" y="${y + hh * 0.74}" text-anchor="end" font-family='${mono}' font-size="${opt.fs || hh * 0.62}" font-weight="600" fill="${opt.ink || EQ.lcdInk}">${esc(txt)}</text>`;
}
function knob(cx, cy, r, frac) {
  const a = (-135 + 270 * clamp(frac || 0, 0, 1)) * DEG;
  return `<circle cx="${cx}" cy="${cy}" r="${r}" fill="#1b2125" stroke="#56636c" stroke-width="2"/><circle cx="${cx}" cy="${cy}" r="${r * 0.62}" fill="#2c353b"/>
  <line x1="${cx}" y1="${cy}" x2="${cx + Math.sin(a) * r * 0.9}" y2="${cy - Math.cos(a) * r * 0.9}" stroke="#f3f6f7" stroke-width="2.4" stroke-linecap="round"/>`;
}
const T_ = (x, y, s, o = {}) => `<text x="${x}" y="${y}" text-anchor="${o.a || 'middle'}" font-family='${o.m ? mono : sans}' font-size="${o.fs || 11}" font-weight="${o.fw || 500}" fill="${o.c || EQ.txt}"${o.cls ? ` class="${o.cls}"` : ''}>${o.sub ? subSVG(esc(s)) : esc(s)}</text>`;
const INK = { cls: 'eq-ink' }; // text that sits on the bench (theme colour)
/* each part: w,h, terms {id:[x,y,kind,label]}, render(p, st) -> svg, polar: [a,b], swap: true for non-polar 2-terminal parts */
const PARTS = {
  supply: {
    w: 200, h: 124, terms: { '+': [128, 104, 'pos', '+'], '-': [170, 104, 'neg', '−'] }, polar: ['+', '-'],
    render: (p, st) => `<rect x="0" y="0" width="200" height="124" rx="9" fill="${EQ.body}" stroke="${EQ.edge}"/>
      <rect x="6" y="6" width="188" height="16" rx="3" fill="${EQ.body2}"/>${T_(100, 18, `${p.name || ''} DC SUPPLY 0–${p.max || 30} V`, { fs: 10, fw: 600 })}
      ${lcdBox(12, 30, 104, 38, st.on ? (st.v ?? 0).toFixed(p.dp ?? 2) + ' V' : (st.fuse ? 'SHORT' : '– – –'), { fs: 20 })}
      <g data-knob="v" style="cursor:ns-resize"><title>Drag up or down to turn</title><circle cx="158" cy="50" r="26" fill="transparent"/>${knob(158, 50, 20, (st.v || 0) / (p.max || 30))}</g>${T_(158, 84, 'VOLTAGE', { fs: 8, c: EQ.dim })}
      <circle cx="26" cy="98" r="6" fill="${st.on ? '#41d36b' : '#4a1d1d'}" stroke="#111"/>${T_(26, 116, 'POWER', { fs: 7.5, c: EQ.dim })}
      ${T_(128, 92, '+', { fs: 14, fw: 700, c: '#ff8a80' })}${T_(170, 92, '−', { fs: 14, fw: 700 })}`
  },
  dmm: {
    w: 124, h: 128, terms: { '+': [36, 112, 'pos', '+'], '-': [88, 112, 'neg', 'COM'] }, polar: ['+', '-'],
    render: (p, st) => `<rect x="0" y="0" width="124" height="128" rx="11" fill="${EQ.yellow}" stroke="#8a6d10"/><rect x="7" y="7" width="110" height="114" rx="7" fill="#2a3036"/>
      ${lcdBox(14, 14, 96, 36, st.fuse ? 'FUSE' : st.on ? (st.rev && !String(st.val ?? '0').startsWith('-') ? '-' : '') + (st.val ?? '0.00') : '', { fs: 19 })}
      ${T_(62, 66, p.unit || 'V', { fs: 15, fw: 700, c: '#ffe08a' })}${T_(62, 80, p.mode || 'DC', { fs: 9, c: EQ.dim })}
      ${T_(36, 98, '+', { fs: 12, fw: 700, c: '#ff8a80' })}${T_(88, 98, 'COM', { fs: 9 })}`
  },
  res: {
    w: 124, h: 50, swap: true, terms: { '1': [8, 34, 'lead', '1'], '2': [116, 34, 'lead', '2'] },
    render: (p) => { const b = bands(p.ohm || 1000); return `<line x1="8" y1="34" x2="116" y2="34" stroke="#9aa3a8" stroke-width="2.4"/>
      <rect x="34" y="24" width="56" height="20" rx="9" fill="#e3c99a" stroke="#8b6d3e"/>${b.map((c, i) => `<rect x="${42 + i * 10}" y="24" width="5" height="20" fill="${c}"/>`).join('')}
      ${T_(62, 14, p.text || '', { ...INK, fs: 11, fw: 600 })}`; }
  },
  rbox: {
    w: 176, h: 104, swap: true, terms: { '1': [24, 92, 'post', '1'], '2': [152, 92, 'post', '2'] },
    render: (p, st) => `<rect x="0" y="0" width="176" height="104" rx="6" fill="${EQ.wood}" stroke="#5d3c1d"/><rect x="8" y="8" width="160" height="70" rx="4" fill="#1f2326"/>
      ${T_(88, 22, p.name || 'Resistance box', { fs: 10, fw: 600, sub: 1 })}${lcdBox(24, 30, 128, 32, st.val || '', { fs: 17, bg: '#d9c9a6' })}
      ${T_(88, 74, 'Ω (resistance box)', { fs: 8.5, c: EQ.dim })}`
  },
  cap: {
    w: 70, h: 78, swap: true, terms: { '1': [24, 70, 'lead', '1'], '2': [46, 70, 'lead', '2'] },
    render: (p) => `<line x1="24" y1="70" x2="30" y2="40" stroke="#9aa3a8" stroke-width="2.2"/><line x1="46" y1="70" x2="40" y2="40" stroke="#9aa3a8" stroke-width="2.2"/>
      <ellipse cx="35" cy="30" rx="18" ry="16" fill="#e0782f" stroke="#8a3f10"/>${T_(35, 34, p.code || '334', { fs: 9, c: '#2b1606' })}${T_(35, 8, p.text || '', { ...INK, fs: 10, fw: 600 })}`
  },
  diode: {
    w: 124, h: 52, terms: { 'A': [8, 36, 'lead', 'A'], 'K': [116, 36, 'lead', 'K'] }, polar: ['A', 'K'],
    render: (p) => `<line x1="8" y1="36" x2="116" y2="36" stroke="#9aa3a8" stroke-width="2.4"/>
      <rect x="38" y="27" width="48" height="18" rx="4" fill="${p.zener ? '#e8a23a' : '#26292c'}" stroke="#111" opacity="${p.zener ? 0.9 : 1}"/><rect x="74" y="27" width="6" height="18" fill="${p.zener ? '#222' : '#cfd5d9'}"/>
      ${T_(62, 14, p.text || '', { ...INK, fs: 11, fw: 600 })}${T_(14, 50, 'A', { ...INK, fs: 9 })}${T_(110, 50, 'K', { ...INK, fs: 9 })}`
  },
  bjt: {
    w: 100, h: 124, terms: { 'C': [22, 114, 'lead', 'C'], 'B': [50, 114, 'lead', 'B'], 'E': [78, 114, 'lead', 'E'] },
    render: (p) => `<line x1="40" y1="56" x2="22" y2="110" stroke="#aab2b6" stroke-width="2.4"/><line x1="50" y1="56" x2="50" y2="110" stroke="#aab2b6" stroke-width="2.4"/><line x1="60" y1="56" x2="78" y2="110" stroke="#aab2b6" stroke-width="2.4"/>
      <path d="M26 58 L26 26 A24 24 0 0 1 74 26 L74 58 Z" fill="#1d2023" stroke="#000"/>${T_(50, 38, p.part || 'BC547', { fs: 9.5, fw: 600 })}${T_(50, 50, p.pnp ? 'PNP' : 'NPN', { fs: 8, c: EQ.dim })}
      ${T_(50, 12, p.text || '', { ...INK, fs: 10.5, fw: 600 })}${T_(22, 100, 'C', { ...INK, fs: 9, a: 'end' })}${T_(78, 100, 'E', { ...INK, fs: 9, a: 'start' })}`
  },
  reg: {
    w: 104, h: 134, terms: { 'IN': [30, 124, 'lead', 'IN'], 'GND': [52, 124, 'lead', 'GND'], 'OUT': [74, 124, 'lead', 'OUT'] },
    render: (p) => `<rect x="22" y="6" width="60" height="34" rx="2" fill="#b9c0c4" stroke="#6b7378"/><circle cx="52" cy="22" r="7" fill="#7b8388"/>
      <rect x="22" y="38" width="60" height="44" fill="#1d2023" stroke="#000"/>${T_(52, 58, p.part || 'LM7805', { fs: 10, fw: 600 })}${T_(52, 72, '+5 V', { fs: 8.5, c: EQ.dim })}
      ${[30, 52, 74].map(x => `<line x1="${x}" y1="82" x2="${x}" y2="122" stroke="#aab2b6" stroke-width="3"/>`).join('')}
      ${T_(30, 79, 'IN', { fs: 7, c: EQ.dim })}${T_(52, 79, 'GND', { fs: 7, c: EQ.dim })}${T_(74, 79, 'OUT', { fs: 7, c: EQ.dim })}`
  },
  fgen: {
    w: 224, h: 128, terms: { 'OUT': [158, 110, 'pos', 'OUT'], 'GND': [196, 110, 'neg', 'GND'] }, polar: ['OUT', 'GND'],
    render: (p, st) => `<rect x="0" y="0" width="224" height="128" rx="9" fill="#3b4652" stroke="${EQ.edge}"/>${T_(112, 17, p.name || 'FUNCTION GENERATOR', { fs: 10, fw: 600 })}
      ${lcdBox(12, 26, 138, 34, st.on ? st.val || '' : '', { fs: 17, bg: '#0f2a1d', ink: '#5cff9a' })}
      <g data-knob="f" style="cursor:ns-resize"><title>Drag up or down to turn</title><circle cx="184" cy="44" r="24" fill="transparent"/>${knob(184, 44, 18, st.k1 || 0)}</g>${T_(184, 76, 'FREQ', { fs: 8, c: EQ.dim })}
      <g data-knob="a" style="cursor:ns-resize"><title>Drag up or down to turn</title><circle cx="40" cy="92" r="20" fill="transparent"/>${knob(40, 92, 14, st.k2 || 0)}</g>${T_(40, 117, 'AMPL', { fs: 8, c: EQ.dim })}
      <path d="M78 96 q8 -16 16 0 t16 0" stroke="#9fe6b8" fill="none" stroke-width="2"/>${T_(158, 96, 'OUT', { fs: 8.5 })}${T_(196, 96, 'GND', { fs: 8.5 })}`
  },
  osc: {
    w: 176, h: 112, terms: { 'OUT': [118, 96, 'pos', 'OUT'], 'GND': [152, 96, 'neg', 'GND'] }, polar: ['OUT', 'GND'],
    render: (p, st) => `<rect x="0" y="0" width="176" height="112" rx="9" fill="#5a3b52" stroke="${EQ.edge}"/>${T_(88, 18, p.name || 'UNKNOWN SOURCE', { fs: 10, fw: 600 })}
      ${lcdBox(14, 28, 148, 30, st.on ? 'f = ? Hz' : '', { fs: 15, bg: '#2a1730', ink: '#ff9ad5' })}
      <circle cx="34" cy="86" r="6" fill="${st.on ? '#41d36b' : '#3a2030'}" stroke="#111"/>${T_(118, 80, 'OUT', { fs: 8.5 })}${T_(152, 80, 'GND', { fs: 8.5 })}`
  },
  cro: {
    w: 250, h: 168, terms: { 'CH1': [56, 150, 'post', 'CH1 (Y)'], 'CH2': [126, 150, 'post', 'CH2 (X)'], 'GND': [196, 150, 'neg', 'GND'] },
    render: (p, st) => `<rect x="0" y="0" width="250" height="168" rx="10" fill="#4a5560" stroke="${EQ.edge}"/>${T_(125, 16, 'CATHODE RAY OSCILLOSCOPE', { fs: 10, fw: 600 })}
      <rect x="14" y="24" width="140" height="96" rx="6" fill="#0b1712" stroke="#000"/>${Array.from({ length: 9 }, (_, i) => `<line x1="${14 + i * 17.5}" y1="24" x2="${14 + i * 17.5}" y2="120" stroke="#1d3a2b"/>`).join('')}
      ${st.on ? `<path d="M14 72 q8.75 -${30} 17.5 0 t17.5 0 t17.5 0 t17.5 0 t17.5 0 t17.5 0 t17.5 0 t17.5 0" stroke="#7dff9b" fill="none" stroke-width="1.6" opacity=".9"/>` : ''}
      ${[0, 1, 2].map(i => knob(186 + (i % 2) * 34, 40 + Math.floor(i / 2) * 40, 11, 0.3 + i * 0.2)).join('')}
      ${T_(56, 136, 'CH1 (Y)', { fs: 8.5 })}${T_(126, 136, 'CH2 (X)', { fs: 8.5 })}${T_(196, 136, 'GND', { fs: 8.5 })}`
  },
  trainer: {
    w: 344, h: 204, terms: { '+5V': [40, 40, 'pos', '+5 V'], 'GND': [96, 40, 'neg', 'GND'], 'A': [64, 112, 'post', 'Switch A'], 'B': [144, 112, 'post', 'Switch B'], 'L1': [262, 112, 'post', 'LED input'] },
    render: (p, st) => { const sw = (x, on, n) => `<g data-sw="${n}" style="cursor:pointer"><rect x="${x - 18}" y="138" width="36" height="52" rx="6" fill="#141a1f" stroke="#59656d"/><rect x="${x - 12}" y="${on ? 144 : 164}" width="24" height="20" rx="3" fill="${on ? '#f2f5f7' : '#8e989e'}"/>${T_(x, 199, n + (on ? ' = 1' : ' = 0'), { fs: 8.5, c: '#d7e6f5' })}</g>`;
      const led = st.on && st.led; return `<rect x="0" y="0" width="344" height="204" rx="10" fill="#1f4f80" stroke="#0f2a45"/>${T_(220, 22, 'DIGITAL TRAINER KIT', { fs: 10, fw: 600 })}
      ${T_(40, 24, '+5V', { fs: 9 })}${T_(96, 24, 'GND', { fs: 9 })}${T_(64, 96, 'A', { fs: 10, fw: 700 })}${T_(144, 96, 'B', { fs: 10, fw: 700 })}${T_(262, 96, 'LED', { fs: 9, fw: 700 })}
      ${sw(64, st.A, 'A')}${sw(144, st.B, 'B')}
      <circle cx="262" cy="56" r="13" fill="${led ? '#ff4433' : '#5a1f1a'}" stroke="#2a0b08"/>${led ? '<circle cx="262" cy="56" r="22" fill="#ff4433" opacity=".28"/>' : ''}
      ${lcdBox(206, 146, 112, 28, st.on ? (st.vout ?? 0).toFixed(2) + ' V' : '', { fs: 15 })}${T_(262, 190, 'output voltage', { fs: 8, c: '#d7e6f5' })}
      <circle cx="320" cy="30" r="5" fill="${st.on ? '#41d36b' : '#18301e'}"/>`; }
  },
  ic: {
    w: 244, h: 104, terms: Object.fromEntries(Array.from({ length: 14 }, (_, i) => { const n = i + 1; const x = n <= 7 ? 38 + (n - 1) * 28 : 38 + (14 - n) * 28; const y = n <= 7 ? 96 : 8; return [String(n), [x, y, 'pin', 'pin ' + n]]; })),
    render: (p) => `${Array.from({ length: 7 }, (_, i) => `<rect x="${32 + i * 28}" y="78" width="12" height="16" fill="#b7bec2"/><rect x="${32 + i * 28}" y="10" width="12" height="16" fill="#b7bec2"/>`).join('')}
      <rect x="20" y="24" width="204" height="56" rx="4" fill="#1b1e21" stroke="#000"/><path d="M20 42 a10 10 0 0 1 0 20" fill="#3a3f44"/>
      ${T_(122, 50, p.part || '74LS08', { fs: 13, fw: 600 })}${T_(122, 67, p.fn || '', { fs: 9, c: EQ.dim })}
      ${Array.from({ length: 14 }, (_, i) => { const n = i + 1; const x = n <= 7 ? 38 + (n - 1) * 28 : 38 + (14 - n) * 28; const y = n <= 7 ? 76 : 36; return T_(x, y, String(n), { fs: 7.5, c: '#8f9aa0' }); }).join('')}`
  },
  network: {
    w: 330, h: 186, terms: { 'IN+': [26, 46, 'pos', 'IN +'], 'IN-': [26, 150, 'neg', 'IN −'], 'A': [304, 46, 'post', 'A'], 'B': [304, 150, 'post', 'B'] },
    render: (p) => `<rect x="0" y="0" width="330" height="186" rx="8" fill="${EQ.pcb}" stroke="#0c3a25"/>${T_(165, 18, 'NETWORK BOARD', { fs: 10, fw: 600 })}
      <path d="M26 46 H86 M140 46 H186 M240 46 H304 M163 46 V78 M163 124 V150 M26 150 H304" stroke="#e7c76a" stroke-width="3" fill="none"/>
      ${[[86, 46, 'R1'], [186, 46, 'R3']].map(([x, y, n]) => `<rect x="${x}" y="${y - 9}" width="54" height="18" rx="7" fill="#e3c99a" stroke="#8b6d3e"/>${T_(x + 27, y - 14, `${n} = ${p[n]}`, { fs: 9 })}`).join('')}
      <rect x="154" y="78" width="18" height="46" rx="7" fill="#e3c99a" stroke="#8b6d3e"/>${T_(196, 104, `R2 = ${p.R2}`, { fs: 9, a: 'start' })}
      ${T_(26, 30, 'IN+', { fs: 9 })}${T_(26, 174, 'IN−', { fs: 9 })}${T_(304, 30, 'A', { fs: 10, fw: 700 })}${T_(304, 174, 'B', { fs: 10, fw: 700 })}`
  }
};
function bands(ohm) { // colour code for resistor bodies
  const C = ['#000', '#7b4a12', '#d32f2f', '#f57c00', '#fbc02d', '#388e3c', '#1976d2', '#7b1fa2', '#9e9e9e', '#fff'];
  const e = Math.floor(Math.log10(ohm)) - 1, d = Math.round(ohm / 10 ** e); const a = Math.floor(d / 10) % 10, b = d % 10;
  return [C[a], C[b], C[clamp(e, 0, 9)], '#c9a227'];
}
const fmtOhm = r => r >= 1e6 ? (r / 1e6) + ' MΩ' : r >= 1000 ? (r / 1000) + ' kΩ' : r + ' Ω';

/* ================= schematic drawing helpers ================= */
const SC = {
  w: (...p) => `<polyline class="s-w" points="${p.map(q => q.join(',')).join(' ')}"/>`,
  dot: (x, y) => `<circle class="s-dot" cx="${x}" cy="${y}" r="3"/>`,
  t: (x, y, s, a = 'middle', fs = 11) => `<text class="s-t" x="${x}" y="${y}" text-anchor="${a}" font-size="${fs}">${subSVG(s)}</text>`,
  // resistor between two points (horizontal or vertical); arrow = variable
  R(x1, y1, x2, y2, lab, arrow) {
    const hz = y1 === y2, L = hz ? x2 - x1 : y2 - y1, mid = L / 2, bl = 36, s = Math.sign(L);
    const pts = []; const n = 6; for (let i = 0; i <= n; i++) { const u = mid - s * bl / 2 + s * bl * i / n, v = i === 0 || i === n ? 0 : (i % 2 ? -6 : 6); pts.push(hz ? [x1 + u, y1 + v] : [x1 + v, y1 + u]); }
    let o = SC.w([x1, y1], pts[0]) + SC.w(...pts) + SC.w(pts[n], [x2, y2]);
    if (arrow) { const cx = (x1 + x2) / 2, cy = (y1 + y2) / 2; o += `<line class="s-w" x1="${cx - 14}" y1="${cy + 14}" x2="${cx + 14}" y2="${cy - 14}"/><path class="s-f" d="M${cx + 14} ${cy - 14} l-8 1 l7 7 z"/>`; }
    if (lab) o += hz ? SC.t((x1 + x2) / 2, y1 - 12, lab) : SC.t(x1 + 12, (y1 + y2) / 2 + 4, lab, 'start');
    return o;
  },
  // cell / DC source between two points; plus at first point
  B(x1, y1, x2, y2, lab, variable) {
    const hz = y1 === y2, mx = (x1 + x2) / 2, my = (y1 + y2) / 2; let o;
    if (hz) { o = SC.w([x1, y1], [mx - 4, my]) + SC.w([mx + 4, my], [x2, y2]) + `<line class="s-w" x1="${mx - 4}" y1="${my - 14}" x2="${mx - 4}" y2="${my + 14}"/><line class="s-w s-thick" x1="${mx + 4}" y1="${my - 7}" x2="${mx + 4}" y2="${my + 7}"/>`; o += SC.t(mx - 12, my - 16, '+') + (lab ? SC.t(mx, my + 30, lab) : ''); }
    else { const s = Math.sign(y2 - y1); o = SC.w([x1, y1], [mx, my - 4 * s]) + SC.w([mx, my + 4 * s], [x2, y2]) + `<line class="s-w" x1="${mx - 14}" y1="${my - 4 * s}" x2="${mx + 14}" y2="${my - 4 * s}"/><line class="s-w s-thick" x1="${mx - 7}" y1="${my + 4 * s}" x2="${mx + 7}" y2="${my + 4 * s}"/>`; o += SC.t(mx + 18, my - 6 * s, '+', 'start') + (lab ? SC.t(mx - 20, my + 4, lab, 'end') : ''); }
    if (variable) o += `<line class="s-w" x1="${mx - 16}" y1="${my + 16}" x2="${mx + 16}" y2="${my - 16}"/><path class="s-f" d="M${mx + 16} ${my - 16} l-8 1 l7 7 z"/>`;
    return o;
  },
  // meter circle on a line between two points; plus mark near first point
  M(x1, y1, x2, y2, txt, plusFirst = true) {
    const mx = (x1 + x2) / 2, my = (y1 + y2) / 2, hz = y1 === y2, r = 15;
    let o = hz ? SC.w([x1, y1], [mx - r, my]) + SC.w([mx + r, my], [x2, y2]) : SC.w([x1, y1], [mx, my - Math.sign(y2 - y1) * r]) + SC.w([mx, my + Math.sign(y2 - y1) * r], [x2, y2]);
    o += `<circle class="s-m" cx="${mx}" cy="${my}" r="${r}"/>${SC.t(mx, my + 4, txt, 'middle', txt.length > 2 ? 9 : 11)}`;
    const px = hz ? (plusFirst ? mx - r - 6 : mx + r + 6) : mx + 10, py = hz ? my - 8 : (plusFirst ? my - Math.sign(y2 - y1) * (r + 4) : my + Math.sign(y2 - y1) * (r + 10));
    o += SC.t(px, py, '+', 'middle', 10);
    return o;
  },
  // diode between two points, anode at first point; zener adds bent bar
  D(x1, y1, x2, y2, zener, lab) {
    const hz = y1 === y2, mx = (x1 + x2) / 2, my = (y1 + y2) / 2, s = hz ? Math.sign(x2 - x1) : Math.sign(y2 - y1);
    let o;
    if (hz) { o = SC.w([x1, y1], [mx - 9 * s, my]) + SC.w([mx + 9 * s, my], [x2, y2]) + `<path class="s-f" d="M${mx - 9 * s} ${my - 9} L${mx + 9 * s} ${my} L${mx - 9 * s} ${my + 9} Z"/><line class="s-w" x1="${mx + 9 * s}" y1="${my - 10}" x2="${mx + 9 * s}" y2="${my + 10}"/>`; if (zener) o += `<polyline class="s-w" points="${mx + 9 * s - 5},${my - 14} ${mx + 9 * s},${my - 10}"/><polyline class="s-w" points="${mx + 9 * s},${my + 10} ${mx + 9 * s + 5},${my + 14}"/>`; if (lab) o += SC.t(mx, my - 16, lab); }
    else { o = SC.w([x1, y1], [mx, my - 9 * s]) + SC.w([mx, my + 9 * s], [x2, y2]) + `<path class="s-f" d="M${mx - 9} ${my - 9 * s} L${mx} ${my + 9 * s} L${mx + 9} ${my - 9 * s} Z"/><line class="s-w" x1="${mx - 10}" y1="${my + 9 * s}" x2="${mx + 10}" y2="${my + 9 * s}"/>`; if (zener) o += `<polyline class="s-w" points="${mx - 14},${my + 9 * s - 5} ${mx - 10},${my + 9 * s}"/><polyline class="s-w" points="${mx + 10},${my + 9 * s} ${mx + 14},${my + 9 * s + 5}"/>`; if (lab) o += SC.t(mx + 16, my + 4, lab, 'start'); }
    return o;
  },
  // transistor: base at (x,y); returns svg; collector up-right, emitter down-right (flip=true: collector down)
  Q(x, y, pnp, lab, flip) {
    const bx = x + 16, s = flip ? -1 : 1; const cY = y - 26 * s, eY = y + 26 * s;
    let o = `<circle class="s-m" cx="${bx + 8}" cy="${y}" r="20"/>` + SC.w([x, y], [bx, y]) + `<line class="s-w s-thick" x1="${bx}" y1="${y - 12}" x2="${bx}" y2="${y + 12}"/>`;
    o += SC.w([bx, y - 6 * s], [bx + 16, cY + 6 * s], [bx + 16, cY]) + SC.w([bx, y + 6 * s], [bx + 16, eY - 6 * s], [bx + 16, eY]);
    const ax = bx + 13, ay = y + 13 * s; // arrow on emitter
    const ang = Math.atan2((eY - 6 * s) - (y + 6 * s), 16);
    const tip = pnp ? [bx + 4, y + 8 * s] : [ax, ay]; const dir = pnp ? ang + Math.PI : ang;
    o += `<path class="s-f" d="M${tip[0]} ${tip[1]} l${-8 * Math.cos(dir - 0.45)} ${-8 * Math.sin(dir - 0.45)} l${8 * Math.cos(dir - 0.45) - 8 * Math.cos(dir + 0.45)} ${8 * Math.sin(dir - 0.45) - 8 * Math.sin(dir + 0.45)} z"/>`;
    o += SC.t(bx + 22, cY + 6 * s + (flip ? 10 : -2), 'C', 'start', 9) + SC.t(bx + 22, eY - 6 * s + (flip ? -2 : 10), 'E', 'start', 9) + SC.t(x - 2, y - 6, 'B', 'end', 9);
    if (lab) o += SC.t(bx + 34, y + 4, lab, 'start', 10);
    return { svg: o, C: [bx + 16, cY], E: [bx + 16, eY], B: [x, y] };
  },
  gnd: (x, y) => `<line class="s-w" x1="${x}" y1="${y}" x2="${x}" y2="${y + 8}"/><line class="s-w" x1="${x - 10}" y1="${y + 8}" x2="${x + 10}" y2="${y + 8}"/><line class="s-w" x1="${x - 6}" y1="${y + 12}" x2="${x + 6}" y2="${y + 12}"/><line class="s-w" x1="${x - 2}" y1="${y + 16}" x2="${x + 2}" y2="${y + 16}"/>`,
  box: (x, y, w, hh, lab, sub) => `<rect class="s-m" x="${x}" y="${y}" width="${w}" height="${hh}" rx="4"/>${SC.t(x + w / 2, y + hh / 2 + (sub ? -2 : 4), lab, 'middle', 11)}${sub ? SC.t(x + w / 2, y + hh / 2 + 12, sub, 'middle', 9) : ''}`,
  AC(x1, y1, x2, y2, lab) { const mx = (x1 + x2) / 2, my = (y1 + y2) / 2, hz = y1 === y2, r = 16; return (hz ? SC.w([x1, y1], [mx - r, my]) + SC.w([mx + r, my], [x2, y2]) : SC.w([x1, y1], [mx, my - r]) + SC.w([mx, my + r], [x2, y2])) + `<circle class="s-m" cx="${mx}" cy="${my}" r="${r}"/><path class="s-w" d="M${mx - 9} ${my} q4.5 -9 9 0 t9 0"/>` + (lab ? SC.t(hz ? mx : mx - r - 6, hz ? my + r + 14 : my + 4, lab, hz ? 'middle' : 'end') : ''); },
  C(x1, y1, x2, y2, lab) { const hz = y1 === y2, mx = (x1 + x2) / 2, my = (y1 + y2) / 2; return hz ? SC.w([x1, y1], [mx - 4, my]) + SC.w([mx + 4, my], [x2, y2]) + `<line class="s-w s-thick" x1="${mx - 4}" y1="${my - 11}" x2="${mx - 4}" y2="${my + 11}"/><line class="s-w s-thick" x1="${mx + 4}" y1="${my - 11}" x2="${mx + 4}" y2="${my + 11}"/>` + (lab ? SC.t(mx, my - 15, lab) : '') : SC.w([x1, y1], [mx, my - 4]) + SC.w([mx, my + 4], [x2, y2]) + `<line class="s-w s-thick" x1="${mx - 11}" y1="${my - 4}" x2="${mx + 11}" y2="${my - 4}"/><line class="s-w s-thick" x1="${mx - 11}" y1="${my + 4}" x2="${mx + 11}" y2="${my + 4}"/>` + (lab ? SC.t(mx + 15, my + 4, lab, 'start') : ''); },
  // logic gate symbol; inputs at left (y-10,y+10) or one input (y); output at right; returns {svg, in:[...], out}
  G(type, x, y, pins) {
    const not = type === 'NOT', bub = /N(AND|OR)|NOT/.test(type); let body;
    if (not) body = `<path class="s-m" d="M${x} ${y - 16} L${x + 32} ${y} L${x} ${y + 16} Z"/>`;
    else if (/AND/.test(type)) body = `<path class="s-m" d="M${x} ${y - 18} H${x + 20} A18 18 0 0 1 ${x + 20} ${y + 18} H${x} Z"/>`;
    else body = `<path class="s-m" d="M${x - 4} ${y - 18} Q${x + 24} ${y - 18} ${x + 40} ${y} Q${x + 24} ${y + 18} ${x - 4} ${y + 18} Q${x + 8} ${y} ${x - 4} ${y - 18} Z"/>`;
    const ox = not ? x + 32 : (/AND/.test(type) ? x + 38 : x + 40);
    let o = body + (bub ? `<circle class="s-m" cx="${ox + 4}" cy="${y}" r="4"/>` : '');
    const outX = ox + (bub ? 8 : 0);
    const ins = not ? [[x, y]] : [[x + (/OR/.test(type) ? 2 : 0), y - 10], [x + (/OR/.test(type) ? 2 : 0), y + 10]];
    if (pins) { ins.forEach((p, i) => o += SC.t(p[0] - 4, p[1] - 3, pins[i], 'end', 8)); o += SC.t(outX + 4, y - 4, pins[ins.length], 'start', 8); }
    return { svg: o, in: ins, out: [outX, y] };
  },
  svg: (w, hh, body) => `<svg class="schem" viewBox="0 0 ${w} ${hh}" role="img" aria-label="Circuit diagram">${body}</svg>`
};

/* ================= the workbench ================= */
const WIRE_COLS = ['#1f6fd1', '#2e9e4f', '#e0a100', '#8e44ad', '#e67e22', '#16a2b8', '#c2185b'];
function circuitBench(root, cfg) {
  const NS = 'http://www.w3.org/2000/svg';
  const stages = cfg.stages || [{ id: 'main', name: '', parts: cfg.parts.map(p => p.id), spec: cfg.spec }];
  const partDef = Object.fromEntries(cfg.parts.map(p => [p.id, p]));
  const disp = Object.fromEntries(cfg.parts.map(p => [p.id, Object.assign({ on: false }, p.st || {})]));
  let stage = stages.find(s => s.id === LS.get('cb.' + cfg.key + '.stage', stages[0].id)) || stages[0];
  let S = null, powered = false, fuse = false, pending = null, undo = null, reversed = [], knobs = {};
  const W = cfg.W || 900, H = cfg.H || 560;
  const loadStage = st => {
    const sv = LS.get('cb.' + cfg.key + '.' + st.id, null);
    if (sv) return sv;
    if (st.carry) { const prevIdx = stages.indexOf(st) - 1; if (prevIdx >= 0) { const pv = LS.get('cb.' + cfg.key + '.' + stages[prevIdx].id, null); if (pv) return JSON.parse(JSON.stringify(pv)); } }
    return { pos: {}, placed: {}, wires: [] };
  };
  const save = () => LS.set('cb.' + cfg.key + '.' + stage.id, S);
  const stageParts = () => stage.parts.map(id => partDef[id]);
  const posOf = pid => S.pos[pid] || (stage.pos && stage.pos[pid]) || [partDef[pid].x, partDef[pid].y];
  const termXY = t => { const [pid, tid] = splitT(t); const d = PARTS[partDef[pid].type]; const [x, y] = posOf(pid); const tt = d.terms[tid]; return [x + tt[0], y + tt[1]]; };
  const tName = t => { const [pid, tid] = splitT(t); const p = partDef[pid]; const d = PARTS[p.type]; return `${p.label} (${d.terms[tid][3]})`; };

  /* ---------- DOM ---------- */
  const el = h('div', { class: 'cb' });
  const stageBar = h('div', { class: 'opts cb-stages' });
  if (stages.length > 1) {
    const sg = seg({ label: cfg.stageLabel || 'Circuit', value: stage.id, options: stages.map((s, i) => [s.id, s.name]), onChange: v => switchStage(v) });
    stageBar.append(sg.el);
  }
  const tray = h('div', { class: 'cb-tray', 'aria-label': 'Equipment not yet on the bench' });
  const svg = document.createElementNS(NS, 'svg'); svg.setAttribute('viewBox', `0 0 ${W} ${H}`); svg.setAttribute('class', 'cb-svg'); svg.setAttribute('role', 'application'); svg.setAttribute('aria-label', 'Workbench: drag equipment here and connect terminals with wires');
  svg.innerHTML = `<defs><pattern id="bgrid-${cfg.key}" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M40 0H0V40" fill="none" class="cb-gridline"/></pattern></defs><rect class="cb-bench" x="0" y="0" width="${W}" height="${H}" rx="10"/><rect x="0" y="0" width="${W}" height="${H}" fill="url(#bgrid-${cfg.key})"/><g class="cb-parts"></g><g class="cb-wires"></g><g class="cb-terms"></g><path class="cb-temp" d=""/><g class="cb-fx"></g>`;
  const gParts = svg.querySelector('.cb-parts'), gWires = svg.querySelector('.cb-wires'), gTerms = svg.querySelector('.cb-terms'), temp = svg.querySelector('.cb-temp'), gFx = svg.querySelector('.cb-fx');
  const ws = h('div', { class: 'cb-ws' }); ws.append(svg);
  const sb = statusBox();
  const btn = (t, f, cls) => { const b = h('button', { type: 'button', class: 'btn sm ' + (cls || '') }, t); b.addEventListener('click', f); return b; };
  const powerBtn = h('button', { type: 'button', class: 'cb-power', 'aria-pressed': 'false' }, h('span', { class: 'lamp' }), h('span', { class: 'lbl' }, 'Power OFF'));
  powerBtn.addEventListener('click', () => setPower(!powered));
  const tools = h('div', { class: 'cb-tools' },
    btn('Place all equipment', () => { stageParts().forEach(p => S.placed[p.id] = true); save(); render(); }),
    btn('Check circuit', () => check(true)), btn('Hint', hint), btn('Show me (demo wiring)', demo), btn('Clear wires', () => { if (S.wires.length) { undo = S.wires.slice(); S.wires = []; afterWire(); sb.say('All wires removed. <a href="#" class="cb-undo">Undo</a>'); } }),
    powerBtn);
  const schemBox = h('div', { class: 'cb-schem' });
  el.append(stageBar, h('div', { class: 'cb-grid' }, h('div', { class: 'card cb-diag' }, h('div', { class: 'card-h' }, h('h3', null, 'Circuit diagram'), h('span', { class: 'sub' }, 'wire the bench to match this')), schemBox),
    h('div', { class: 'card cb-main' }, h('div', { class: 'card-h' }, h('h3', null, 'Workbench'), h('span', { class: 'sub' }, 'drag equipment from the tray, then drag from terminal to terminal to connect a wire · tap a wire to remove it')), tray, ws, tools, sb.el)));
  root.append(el);
  sb.el.addEventListener('click', e => { if (e.target.classList.contains('cb-undo')) { e.preventDefault(); if (undo) { S.wires = undo; undo = null; afterWire(); sb.say('Restored.'); } } });

  /* ---------- rendering ---------- */
  function partSVG(p) { return PARTS[p.type].render(p.p || {}, disp[p.id]); }
  function render() {
    gParts.innerHTML = ''; gTerms.innerHTML = ''; tray.innerHTML = '';
    stageParts().forEach(p => {
      if (!S.placed[p.id]) { trayItem(p); return; }
      const [x, y] = posOf(p.id);
      const g = document.createElementNS(NS, 'g'); g.setAttribute('transform', `translate(${x},${y})`); g.dataset.p = p.id; g.setAttribute('class', 'cb-part');
      g.innerHTML = `<g class="body">${partSVG(p)}</g><text class="eq-ink cb-plabel" x="${PARTS[p.type].w / 2}" y="${p.labelBelow ? PARTS[p.type].h + 16 : -6}" text-anchor="middle" font-size="13" font-weight="600">${subSVG(esc(p.label))}</text>`;
      gParts.append(g);
      Object.entries(PARTS[p.type].terms).forEach(([tid, [tx, ty, kind, lab]]) => {
        const t = p.id + '.' + tid; const tg = document.createElementNS(NS, 'g'); tg.dataset.t = t; tg.setAttribute('class', 'cb-term k-' + kind);
        tg.setAttribute('transform', `translate(${x + tx},${y + ty})`);
        tg.innerHTML = `<circle class="hit" r="15"/><circle class="ring" r="${kind === 'pin' ? 5 : 7}"/><circle class="core" r="${kind === 'pin' ? 2.2 : 3}"/><title>${esc(tName(t))}</title>`;
        gTerms.append(tg);
      });
    });
    if (!tray.children.length) tray.append(h('span', { class: 'small muted' }, 'All equipment is on the bench.'));
    renderWires();
    schemBox.innerHTML = cfg.schematic ? cfg.schematic(stage.id) : '';
  }
  function refreshPart(pid) { const g = gParts.querySelector(`[data-p="${pid}"] .body`); if (g) g.innerHTML = partSVG(partDef[pid]); }
  function trayItem(p) {
    const d = PARTS[p.type]; const sc = Math.min(1, 74 / d.h, 120 / d.w);
    const it = h('button', { type: 'button', class: 'cb-tray-item', title: 'Drag to the bench (or click to place)' });
    it.innerHTML = `<svg viewBox="-4 -4 ${d.w + 8} ${d.h + 8}" width="${(d.w + 8) * sc}" height="${(d.h + 8) * sc}">${partSVG(p)}</svg><span>${subHTML(esc(p.label))}</span>`;
    let ghost = null, moved = false;
    it.addEventListener('pointerdown', e => {
      e.preventDefault(); moved = false; it.setPointerCapture(e.pointerId);
      const mv = ev => { if (!moved && Math.hypot(ev.clientX - e.clientX, ev.clientY - e.clientY) > 6) { moved = true; ghost = it.firstElementChild.cloneNode(true); ghost.classList.add('cb-ghost'); document.body.append(ghost); } if (ghost) { ghost.style.left = ev.clientX - 30 + 'px'; ghost.style.top = ev.clientY - 30 + 'px'; } };
      const up = ev => {
        it.removeEventListener('pointermove', mv); it.removeEventListener('pointerup', up); it.removeEventListener('pointercancel', up);
        if (ghost) { ghost.remove(); ghost = null; }
        if (!moved) { place(p.id); return; }
        const r = svg.getBoundingClientRect(); if (ev.clientX < r.left || ev.clientX > r.right || ev.clientY < r.top || ev.clientY > r.bottom) return;
        const pt = toSVG(ev); place(p.id, [clamp(pt.x - d.w / 2, 0, W - d.w), clamp(pt.y - d.h / 2, 0, H - d.h)]);
      };
      it.addEventListener('pointermove', mv); it.addEventListener('pointerup', up); it.addEventListener('pointercancel', up);
    });
    tray.append(it);
  }
  function place(pid, xy) { S.placed[pid] = true; if (xy) S.pos[pid] = xy.map(Math.round); save(); render(); sb.say(`${partDef[pid].label} placed on the bench.`); }
  function wireColor(w, i) {
    const k = t => { const [pid, tid] = splitT(t); return PARTS[partDef[pid].type].terms[tid][2]; };
    if (w[2]) return w[2]; const a = k(w[0]), b = k(w[1]);
    if (a === 'pos' || b === 'pos') return '#d32f2f'; if (a === 'neg' || b === 'neg') return '#26292c'; return WIRE_COLS[i % WIRE_COLS.length];
  }
  const wirePath = ([x1, y1], [x2, y2]) => { const d = Math.hypot(x2 - x1, y2 - y1); const sag = Math.min(70, d * 0.28) + 12; return `M${x1} ${y1} C${x1} ${y1 + sag} ${x2} ${y2 + sag} ${x2} ${y2}`; };
  function renderWires() {
    gWires.innerHTML = S.wires.map((w, i) => { if (!S.placed[splitT(w[0])[0]] || !S.placed[splitT(w[1])[0]]) return ''; const d = wirePath(termXY(w[0]), termXY(w[1])); const c = wireColor(w, i); const plug = ([x, y]) => `<rect class="plug" x="${x - 4.5}" y="${y + 3}" width="9" height="15" rx="3" fill="${c}"/>`; return `<g class="cb-wire" data-w="${i}"><path class="halo" d="${d}"/><path class="core" d="${d}" stroke="${c}"/>${plug(termXY(w[0]))}${plug(termXY(w[1]))}<title>${esc(tName(w[0]))} — ${esc(tName(w[1]))} (click to remove)</title></g>`; }).join('');
  }
  /* ---------- pointer interaction ---------- */
  function toSVG(e) { const pt = svg.createSVGPoint(); pt.x = e.clientX; pt.y = e.clientY; return pt.matrixTransform(svg.getScreenCTM().inverse()); }
  let drag = null;
  svg.addEventListener('pointerdown', e => {
    const sw = e.target.closest('[data-sw]');
    if (sw) { const pid = sw.closest('[data-p]').dataset.p; cfg.onSwitch && cfg.onSwitch(pid, sw.dataset.sw); return; }
    const kn = e.target.closest('[data-knob]');
    if (kn) { const pid = kn.closest('[data-p]').dataset.p, which = kn.dataset.knob, ctl = knobs[pid] && knobs[pid][which]; if (ctl) { e.preventDefault(); drag = { kind: 'knob', ctl, y0: e.clientY, s0: +ctl.input.value }; svg.setPointerCapture(e.pointerId); return; } }
    const t = e.target.closest('[data-t]');
    if (t) { e.preventDefault(); drag = { kind: 'wire', from: t.dataset.t, x: e.clientX, y: e.clientY }; svg.setPointerCapture(e.pointerId); return; }
    const w = e.target.closest('[data-w]');
    if (w) { e.preventDefault(); const i = +w.dataset.w; undo = S.wires.slice(); const rem = S.wires.splice(i, 1)[0]; afterWire(); sfx('unplug'); sb.say(`Wire removed: ${tName(rem[0])} — ${tName(rem[1])}. <a href="#" class="cb-undo">Undo</a>`); return; }
    const p = e.target.closest('[data-p]');
    if (p) { e.preventDefault(); const pt = toSVG(e); const [x, y] = posOf(p.dataset.p); drag = { kind: 'move', pid: p.dataset.p, dx: pt.x - x, dy: pt.y - y }; svg.setPointerCapture(e.pointerId); }
  });
  svg.addEventListener('pointermove', e => {
    if (!drag) return;
    if (drag.kind === 'knob') { const c = drag.ctl, inp = c.input; const lo = +inp.min, hi = +inp.max; const v = clamp(drag.s0 + (drag.y0 - e.clientY) / 220 * (hi - lo), lo, hi); inp.value = v; inp.dispatchEvent(new Event('input')); return; }
    const pt = toSVG(e);
    if (drag.kind === 'wire') { const [x1, y1] = termXY(drag.from); temp.setAttribute('d', `M${x1} ${y1} L${pt.x} ${pt.y}`); }
    else { const d = PARTS[partDef[drag.pid].type]; S.pos[drag.pid] = [Math.round(clamp(pt.x - drag.dx, 0, W - d.w)), Math.round(clamp(pt.y - drag.dy, 14, H - d.h))]; const g = gParts.querySelector(`[data-p="${drag.pid}"]`); g.setAttribute('transform', `translate(${S.pos[drag.pid][0]},${S.pos[drag.pid][1]})`); $$(`[data-t^="${drag.pid}."]`, gTerms).forEach(tg => { const [x, y] = termXY(tg.dataset.t); tg.setAttribute('transform', `translate(${x},${y})`); }); renderWires(); }
  });
  const endDrag = e => {
    if (!drag) return; const d = drag; drag = null; temp.setAttribute('d', '');
    if (d.kind === 'move' || d.kind === 'knob') { if (d.kind === 'move') save(); return; }
    const tgtEl = document.elementFromPoint(e.clientX, e.clientY); const tg = tgtEl && tgtEl.closest && tgtEl.closest('[data-t]');
    const moved = Math.hypot(e.clientX - d.x, e.clientY - d.y) > 6;
    if (tg && tg.dataset.t !== d.from) { addWire(d.from, tg.dataset.t); setPending(null); return; }
    if (!moved) { if (pending && pending !== d.from) { addWire(pending, d.from); setPending(null); } else setPending(pending === d.from ? null : d.from); }
  };
  svg.addEventListener('pointerup', endDrag); svg.addEventListener('pointercancel', () => { drag = null; temp.setAttribute('d', ''); });
  function setPending(t) { pending = t; $$('.cb-term', gTerms).forEach(g => g.classList.toggle('sel', g.dataset.t === t)); if (t) sb.say(`Selected ${tName(t)}. Now tap the terminal to connect it to.`); }
  function addWire(a, b) {
    if (S.wires.some(w => (w[0] === a && w[1] === b) || (w[0] === b && w[1] === a))) { sb.say('These two terminals are already connected.'); return; }
    S.wires.push([a, b]); afterWire(); sfx('plug'); sb.say(`Wire connected: ${tName(a)} — ${tName(b)}.`);
  }
  function afterWire() { save(); renderWires(); if (powered) { setPower(false); sb.say('The wiring changed, so the power was switched off.', 'warn'); } cfg.onWiring && cfg.onWiring(); }

  /* ---------- checking ---------- */
  function uf(wires) { const par = {}; const f = x => { if (par[x] === undefined) par[x] = x; return par[x] === x ? x : (par[x] = f(par[x])); }; wires.forEach(([a, b]) => { par[f(a)] = f(b); }); return f; }
  function combos(spec) {
    const sw = stage.parts.filter(id => PARTS[partDef[id].type].swap && spec.nets.some(n => n.some(t => t.startsWith(id + '.'))));
    const groups = (spec.same || []); const out = [];
    const perms = arr => arr.length <= 1 ? [arr] : arr.flatMap((x, i) => perms([...arr.slice(0, i), ...arr.slice(i + 1)]).map(r => [x, ...r]));
    let gp = [{}]; groups.forEach(g => { const ps = perms(g); gp = gp.flatMap(m => ps.map(pm => { const mm = { ...m }; g.forEach((id, i) => mm[id] = pm[i]); return mm; })); });
    const n = Math.min(sw.length, 10);
    for (let mask = 0; mask < (1 << n); mask++) gp.forEach(pm => out.push({ flip: new Set(sw.filter((_, i) => mask >> i & 1)), pm }));
    return out;
  }
  function mapT(t, c) { let [pid, tid] = splitT(t); if (c.pm[pid]) pid = c.pm[pid]; if (c.flip.has(pid)) tid = tid === '1' ? '2' : tid === '2' ? '1' : tid; return pid + '.' + tid; }
  function analyse(wires = S.wires) {
    const spec = stage.spec; const f = uf(wires);
    const unplaced = stageParts().filter(p => !S.placed[p.id]);
    if (spec.fn) { const r = spec.fn({ f, wires, tName, stage: stage.id }); r.unplaced = unplaced; return r; }
    let best = null;
    for (const c of combos(spec)) {
      const nets = spec.nets.map(n => n.map(t => mapT(t, c)));
      const inNet = new Set(nets.flat());
      let missing = 0, merged = 0, extra = 0; const rootNets = {};
      nets.forEach((n, i) => { const rs = new Set(n.map(f)); missing += rs.size - 1; rs.forEach(r => (rootNets[r] = rootNets[r] || new Set()).add(i)); });
      Object.values(rootNets).forEach(s => merged += s.size - 1);
      const extraT = new Set(); wires.forEach(w => w.forEach(t => { if (!inNet.has(t)) extraT.add(t); })); extra = extraT.size;
      const score = missing + 3 * merged + 2 * extra;
      if (!best || score < best.score) best = { score, nets, c, missing, merged, extraT: [...extraT], rootNets };
      if (score === 0) break;
    }
    const r = { ok: best.score === 0 && !unplaced.length, unplaced, msgs: [], links: [], short: false, best };
    // diagnose
    const nets = best.nets, netOf = {}; nets.forEach((n, i) => n.forEach(t => netOf[t] = i));
    const supNets = stage.parts.filter(id => ['supply', 'fgen', 'osc'].includes(partDef[id].type)).map(id => { const d = PARTS[partDef[id].type]; return [id, netOf[id + '.' + d.polar[0]], netOf[id + '.' + d.polar[1]]]; }).filter(x => x[1] !== undefined && x[2] !== undefined);
    // reversed polar parts
    stage.parts.forEach(id => {
      const d = PARTS[partDef[id].type]; if (!d.polar) return; const a = id + '.' + d.polar[0], b = id + '.' + d.polar[1];
      if (netOf[a] === undefined || netOf[b] === undefined) return;
      const na = nets[netOf[a]].filter(t => t !== a), nb = nets[netOf[b]].filter(t => t !== b);
      if (na.length && nb.length && na.every(t => f(t) === f(b)) && nb.every(t => f(t) === f(a))) r.msgs.push({ kind: 'polar', text: polarText(id), pid: id, rev: partDef[id].type === 'dmm' });
    });
    // two meters interchanged (e.g. ammeter where the voltmeter should be)
    const meters = stage.parts.filter(id => partDef[id].type === 'dmm');
    for (let i = 0; i < meters.length; i++) for (let j = i + 1; j < meters.length; j++) {
      const [p, q] = [meters[i], meters[j]]; const sw = t => t.startsWith(p + '.') ? q + t.slice(p.length) : t.startsWith(q + '.') ? p + t.slice(q.length) : t;
      let ok = false, used = false;
      for (const c of combos(spec)) { const cn = spec.nets.map(n => n.map(t => mapT(t, c))); const rel = cn.filter(n => n.some(t => t.startsWith(p + '.') || t.startsWith(q + '.'))); used = rel.length > 0; if (used && rel.every(n => { const m = n.map(sw); return m.every(t => f(t) === f(m[0])); }) && rel.some(n => !n.every(t => f(t) === f(n[0])))) { ok = true; break; } }
      if (ok && used && partDef[p].p.unit !== partDef[q].p.unit) r.msgs.unshift({ kind: 'polar', text: `${partDef[p].label} and ${partDef[q].label} are interchanged. An ammeter must be in series with the current it measures; a voltmeter goes in parallel, across the two points.` });
    }
    // transistor E/C swap
    stage.parts.filter(id => partDef[id].type === 'bjt').forEach(id => {
      const e = id + '.E', c = id + '.C'; if (netOf[e] === undefined || netOf[c] === undefined) return;
      const ne = nets[netOf[e]].filter(t => t !== e), nc = nets[netOf[c]].filter(t => t !== c);
      if (ne.length && nc.length && ne.every(t => f(t) === f(c)) && nc.every(t => f(t) === f(e))) r.msgs.push({ kind: 'polar', text: `The emitter and collector leads of the transistor are interchanged. For ${partDef[id].p.part || 'BC547'} with its flat face towards you, the leads are C, B, E from left to right.` });
    });
    // shorts across sources
    supNets.forEach(([id, i1, i2]) => { if (f(nets[i1][0]) === f(nets[i2][0]) || nets[i1].some(t => nets[i2].some(u => f(t) === f(u)))) { r.short = true; r.msgs.unshift({ kind: 'short', text: `Short circuit! The two terminals of ${partDef[id].label} are joined directly. This would blow the fuse.` }); } });
    // ammeter connected straight across a source (no other component in between)
    const AMM = ['mA', 'µA', 'A'];
    stage.parts.filter(id => partDef[id].type === 'dmm' && AMM.includes(partDef[id].p.unit)).forEach(m => {
      const a = f(m + '.+'), b = f(m + '.-'); if (a === b) return;
      stage.parts.filter(id => ['supply', 'fgen', 'osc'].includes(partDef[id].type)).forEach(sid => { const d = PARTS[partDef[sid].type]; const x = f(sid + '.' + d.polar[0]), y = f(sid + '.' + d.polar[1]); if ((a === x && b === y) || (a === y && b === x)) { r.short = true; r.msgs.unshift({ kind: 'short', pid: m, text: `${partDef[m].label} is connected directly across ${partDef[sid].label}. An ammeter has almost no resistance, so this is a short circuit: its fuse would blow. An ammeter must always be in series with a component.` }); } });
    });
    // merged nets (wrong connections)
    Object.values(best.rootNets).forEach(s => { if (s.size > 1 && !r.short) { const [i, j] = [...s]; if (!r.msgs.some(m => m.kind === 'polar')) r.msgs.push({ kind: 'merge', text: `Wrong connection: ${tName(nets[i][0])} is joined to ${tName(nets[j][0])}. These points must not be connected together.` }); } });
    best.extraT.forEach(t => r.msgs.push({ kind: 'extra', text: `${tName(t)} should not be connected in this circuit.` }));
    // missing links
    nets.forEach(n => { const groups = {}; n.forEach(t => (groups[f(t)] = groups[f(t)] || []).push(t)); const gs = Object.values(groups); for (let k = 1; k < gs.length; k++) r.links.push([gs[0][0], gs[k][0]]); });
    return r;
  }
  function polarText(id) {
    const t = partDef[id].type;
    if (t === 'dmm') return `${partDef[id].label} is connected with reversed polarity. Current must enter at its + (red) terminal; for a voltmeter, + goes to the higher potential.`;
    if (t === 'supply') return `The + and − terminals of ${partDef[id].label} are interchanged. Check the polarity in the circuit diagram${/pnp/i.test(stage.id) ? ' (for a PNP transistor the supplies are reversed compared with NPN)' : ''}.`;
    if (t === 'reg') return 'The IN and OUT pins of the regulator are interchanged. With the printed face towards you and legs down: pin 1 = IN, pin 2 = GND, pin 3 = OUT.';
    if (t === 'diode') return partDef[id].p.zener ? `The Zener diode is connected the wrong way round. It must be reverse biased: its cathode (band, K) goes towards the positive side.` : `Diode ${partDef[id].label} is reversed. Check the anode (A) and the cathode (K, band) in the diagram.`;
    return `${partDef[id].label} is connected the wrong way round.`;
  }
  function check(say) {
    const r = analyse();
    if (!say) return r;
    if (r.unplaced.length) { sb.say(`First place all the equipment on the bench: ${r.unplaced.map(p => p.label).join(', ')}.`, 'warn'); return r; }
    if (r.ok) { sb.say('Circuit is correct. Switch the power ON and start taking readings.', 'ok'); flashOK(); return r; }
    const first = r.msgs[0];
    const miss = r.links.length;
    let html = first ? first.text : '';
    if (miss && !(first && first.kind === 'polar')) html += (html ? '<br>' : '') + (miss === 1 ? 'One connection is still missing.' : `${miss} connections are still missing.`) + ' Press <b>Hint</b> to see the next one.';
    if (r.msgs.length > 1) html += `<br><span class="small">(${r.msgs.length - 1} more problem${r.msgs.length > 2 ? 's' : ''} after you fix this.)</span>`;
    sb.say(html || 'The circuit does not match the diagram yet.', 'warn');
    if (r.msgs[0] && r.msgs[0].hl) highlight(r.msgs[0].hl);
    return r;
  }
  function highlight(ts, ghost) {
    gFx.innerHTML = ts.map(t => { const [x, y] = termXY(t); return `<circle class="cb-pulse" cx="${x}" cy="${y}" r="14"/>`; }).join('') + (ghost ? `<path class="cb-ghostwire" d="${wirePath(termXY(ts[0]), termXY(ts[1]))}"/>` : '');
    clearTimeout(highlight.t); highlight.t = setTimeout(() => gFx.innerHTML = '', 3200);
  }
  function hint() {
    const r = analyse();
    if (r.unplaced.length) return sb.say(`Drag ${r.unplaced[0].label} from the tray on to the bench.`, 'warn');
    if (r.ok) return sb.say('The circuit is already correct. Switch the power ON.', 'ok');
    const blocking = r.msgs.find(m => m.kind !== 'extra' && m.kind !== 'logic');
    if (blocking && !(r.links.length && /^Pin (14|7)/.test(blocking.text))) { sb.say('Fix this first: ' + blocking.text, 'warn'); return; }
    const extra = r.msgs.find(m => m.kind === 'extra'); if (extra && !r.links.length) { sb.say(extra.text + ' Tap that wire to remove it.', 'warn'); return; }
    const l = r.links[0]; if (!l) return sb.say('Check the diagram again.', 'warn');
    const [a, b] = l; sb.say(`Next connection: <b>${tName(a)}</b> to <b>${tName(b)}</b>.`, 'warn'); highlight([a, b], true);
  }
  function flashOK() { ws.classList.remove('ok'); void ws.offsetWidth; ws.classList.add('ok'); }
  let demoRun = 0;
  async function demo() {
    const my = ++demoRun; setPower(false);
    const list = stage.spec.demo || stage.spec.nets.flatMap(n => n.slice(1).map((t, i) => [n[i], t]));
    stageParts().forEach(p => { S.placed[p.id] = true; delete S.pos[p.id]; });
    S.wires = []; save(); render();
    sb.say('Watch: the equipment is placed and wired one connection at a time, following the circuit diagram.');
    for (const [a, b] of list) {
      await new Promise(r => setTimeout(r, 520)); if (my !== demoRun || !el.isConnected) return;
      S.wires.push([a, b]); save(); renderWires(); highlight([a, b]); sfx('plug'); sb.say(`Connecting <b>${tName(a)}</b> to <b>${tName(b)}</b>.`);
    }
    await new Promise(r => setTimeout(r, 400)); if (my !== demoRun) return;
    sb.say('Demo wiring complete. Switch the power ON and take readings, or press “Clear wires” and try wiring it yourself.', 'ok'); flashOK(); cfg.onWiring && cfg.onWiring();
  }
  function setPower(on) {
    if (on) {
      const r = analyse();
      if (r.unplaced.length) { sb.say('Place and connect all the equipment before switching on.', 'warn'); return; }
      if (r.short) { blow(r); return; }
      reversed = [];
      if (!r.ok) { const rev = r.msgs.filter(m => m.rev).map(m => m.pid); if (rev.length) { const sw = t => { const [pid, tid] = splitT(t); return rev.includes(pid) ? pid + '.' + (tid === '+' ? '-' : '+') : t; }; const r2 = analyse(S.wires.map(w => [sw(w[0]), sw(w[1])])); if (r2.ok) { reversed = rev; r.ok = true; } } }
      if (!r.ok) { sfx('buzz'); const m = r.msgs[0]; sb.say('The circuit is not correct, so it is not safe to switch on. ' + (m ? m.text : `${r.links.length} connection(s) are missing.`) + ' Use <b>Check circuit</b> or <b>Hint</b>.', 'warn'); ws.classList.remove('nope'); void ws.offsetWidth; ws.classList.add('nope'); return; }
      fuse = false;
    }
    powered = on; powerBtn.setAttribute('aria-pressed', String(on)); powerBtn.querySelector('.lbl').textContent = on ? 'Power ON' : 'Power OFF';
    if (!on) reversed = [];
    stage.parts.forEach(id => { disp[id].on = on; disp[id].fuse = false; disp[id].rev = reversed.includes(id); }); stage.parts.forEach(refreshPart);
    sfx(on ? 'on' : 'off');
    if (on && reversed.length) sb.say(`Power is ON, but ${reversed.map(id => partDef[id].label).join(' and ')} ${reversed.length > 1 ? 'show' : 'shows'} a <b>negative</b> reading: its leads are reversed. Switch off, interchange the leads (+ to the higher potential) and switch on again before recording.`, 'warn');
    else if (on) sb.say(stage.onMsg || 'Power is ON. Adjust the controls below and record your readings.', 'ok');
    cfg.onPower && cfg.onPower(on, stage.id);
  }
  function blow(r) {
    fuse = true; powered = false; ws.classList.remove('blown'); void ws.offsetWidth; ws.classList.add('blown'); sfx('bang');
    const m = (r || analyse()).msgs.find(x => x.kind === 'short');
    const at = m && m.pid ? m.pid : stage.parts.find(id => partDef[id].type === 'supply');
    if (at) { disp[at].fuse = true; disp[at].on = !!(m && m.pid); refreshPart(at); const [x, y] = posOf(at); const d = PARTS[partDef[at].type]; gFx.innerHTML = `<g class="cb-spark" transform="translate(${x + d.w * 0.6},${y + d.h * 0.75})">${Array.from({ length: 10 }, (_, i) => `<line x1="0" y1="0" x2="${Math.cos(i * 0.63) * 34}" y2="${Math.sin(i * 0.63) * 34}"/>`).join('')}</g>`; setTimeout(() => { gFx.innerHTML = ''; if (m && m.pid) { disp[at].fuse = false; disp[at].on = false; refreshPart(at); } }, 1800); }
    sb.say('<b>Bang! The fuse has blown.</b> ' + (m ? m.text : '') + ' Remove the wrong wire, then switch on again (the fuse has been replaced).', 'warn');
  }
  function switchStage(id) {
    setPower(false); stage = stages.find(s => s.id === id); LS.set('cb.' + cfg.key + '.stage', id); S = loadStage(stage); save(); render();
    sb.say(stage.intro || 'Wire the circuit for this step.'); cfg.onStage && cfg.onStage(id);
  }
  Practice.subs.add(render); onCleanup(() => { Practice.subs.delete(render); demoRun++; });
  S = loadStage(stage); render(); sb.say(stage.intro || cfg.intro || 'Place the equipment on the bench and connect it as shown in the circuit diagram. Stuck? Press Hint, or Show me for a demo.');
  return {
    el, sb, say: sb.say, get on() { return powered; }, get stage() { return stage.id; }, wires: () => S.wires,
    show(pid, patch) { Object.assign(disp[pid], patch); refreshPart(pid); },
    get ok() { return powered && !reversed.length; },
    ready(say) { if (!powered) { say('Switch the power ON first (the circuit must be correct).', 'warn'); return false; } if (reversed.length) { say(`${reversed.map(id => partDef[id].label).join(' and ')} reads negative because its leads are reversed. Switch off, interchange the leads and switch on again.`, 'warn'); return false; } return true; },
    bindKnob(pid, which, ctl) { (knobs[pid] = knobs[pid] || {})[which] = ctl; },
    st: pid => disp[pid], check, setPower, analyse, switchStage
  };
}
function splitT(t) { const i = t.indexOf('.'); return [t.slice(0, i), t.slice(i + 1)]; }

/* ================= experiments 14–15: CRO ================= */
function circuitPage(root) {
  const top = h('div'); const { instr, notes } = (() => { const i = h('div', { class: 'stack' }), n = h('div', { class: 'stack' }); return { instr: i, notes: n }; })();
  root.append(top, h('div', { class: 'bench' }, instr, notes));
  return { top, instr, notes };
}
const VDIV = [0.01, 0.02, 0.05, 0.1, 0.2, 0.5, 1, 2, 5, 10], TDIV = [1e-5, 2e-5, 5e-5, 1e-4, 2e-4, 5e-4, 1e-3, 2e-3, 5e-3, 1e-2, 2e-2];
const fmtVd = v => v < 1 ? Math.round(v * 1000) + ' mV' : v + ' V', fmtTd = t => t < 1e-3 ? Math.round(t * 1e6) + ' µs' : Math.round(t * 1e3 * 100) / 100 + ' ms';
function croUnit(parent, o) {
  const R = o.rng;
  const e1 = VDIV.map(() => R.range(-0.035, 0.035)), e2 = VDIV.map(() => R.range(-0.035, 0.035)), et = TDIV.map(() => R.range(-0.025, 0.025));
  const st = Object.assign({ mode: 'YT', v1: 6, v2: 6, td: 6, yp: 0, xp: 0, gnd: false, trig: true, inten: 0.8, focus: 0.75, guide: false }, LS.get(o.key + '.cro', {}), o.force || {});
  const save = () => LS.set(o.key + '.cro', st);
  const wrap = h('div', { class: 'cro-wrap' });
  const scrBox = h('div');
  const view = canvasView(scrBox, { label: 'CRO screen (10 × 8 divisions)', height: w => Math.round(w * 0.8), maxH: 420, draw, alt: 'Oscilloscope screen' });
  const panel = h('div', { class: 'cro-panel' });
  const sel = (label, list, k, fmt) => selectBox({ label, options: list.map((v, i) => [i, fmt(v)]), value: st[k], onChange: v => { st[k] = +v; save(); } });
  const modeSeg = seg({ label: 'Mode', value: st.mode, options: [['YT', 'Y–T (time base)'], ['XY', 'X–Y']], onChange: v => { st.mode = v; save(); o.onMode && o.onMode(v); } });
  const v1 = sel('CH1 VOLTS/DIV', VDIV, 'v1', fmtVd), v2 = sel('CH2 VOLTS/DIV', VDIV, 'v2', fmtVd), td = sel('TIME/DIV', TDIV, 'td', fmtTd);
  const yp = slider({ label: 'Y POSITION', min: -4, max: 4, step: 0.01, value: st.yp, fmt: v => v.toFixed(2) + ' div', fine: [['−0.1', -0.1], ['−0.02', -0.02], ['+0.02', 0.02], ['+0.1', 0.1]], onInput: v => { st.yp = v; save(); } });
  const xp = slider({ label: 'X POSITION', min: -5, max: 5, step: 0.01, value: st.xp, fmt: v => v.toFixed(2) + ' div', fine: [['−0.1', -0.1], ['−0.02', -0.02], ['+0.02', 0.02], ['+0.1', 0.1]], onInput: v => { st.xp = v; save(); } });
  const inten = slider({ label: 'INTENSITY', min: 0.2, max: 1, step: 0.01, value: st.inten, fmt: () => '', onInput: v => { st.inten = v; save(); } });
  const focus = slider({ label: 'FOCUS', min: 0, max: 1, step: 0.01, value: st.focus, fmt: () => '', onInput: v => { st.focus = v; save(); } });
  const gnd = check({ label: 'Input coupling GND (zero line)', value: st.gnd, onChange: v => { st.gnd = v; save(); } });
  const trig = check({ label: 'Trigger AUTO (stable trace)', value: st.trig, onChange: v => { st.trig = v; save(); } });
  const guide = check({ label: 'Show measuring guides', value: st.guide, onChange: v => { st.guide = v; save(); } });
  panel.append(h('h5', null, 'Oscilloscope controls'), modeSeg.el, h('div', { class: 'opts' }, v1.el, v2.el, td.el), yp.el, xp.el, h('div', { class: 'ctls' }, inten.el, focus.el), h('div', { class: 'opts' }, gnd.el, trig.el)); if (o.guides !== false) panel.append(guide.el);
  wrap.append(scrBox, panel); parent.append(wrap);
  loop(() => view.now());
  const ampDiv = V => V / (VDIV[st.v1] * (1 + e1[st.v1]));
  function draw(ctx, W, H) {
    const T = theme(); ctx.clearRect(0, 0, W, H); ctx.fillStyle = '#2b333a'; ctx.fillRect(0, 0, W, H);
    const pad = 14, sw = W - 2 * pad, sh = H - 2 * pad; const dx = sw / 10, dy = sh / 8, cx = pad + sw / 2, cy = pad + sh / 2;
    ctx.fillStyle = '#06130c'; ctx.beginPath(); ctx.roundRect ? ctx.roundRect(pad - 4, pad - 4, sw + 8, sh + 8, 10) : ctx.rect(pad - 4, pad - 4, sw + 8, sh + 8); ctx.fill();
    ctx.strokeStyle = 'rgba(120,200,150,.2)'; ctx.lineWidth = 1;
    for (let i = 0; i <= 10; i++) { const x = Math.round(pad + i * dx) + .5; ctx.beginPath(); ctx.moveTo(x, pad); ctx.lineTo(x, pad + sh); ctx.stroke(); }
    for (let j = 0; j <= 8; j++) { const y = Math.round(pad + j * dy) + .5; ctx.beginPath(); ctx.moveTo(pad, y); ctx.lineTo(pad + sw, y); ctx.stroke(); }
    ctx.strokeStyle = 'rgba(120,200,150,.38)';
    for (let i = 0; i <= 50; i++) { const x = pad + i * dx / 5; ctx.beginPath(); ctx.moveTo(x, cy - 3); ctx.lineTo(x, cy + 3); ctx.stroke(); }
    for (let j = 0; j <= 40; j++) { const y = pad + j * dy / 5; ctx.beginPath(); ctx.moveTo(cx - 3, y); ctx.lineTo(cx + 3, y); ctx.stroke(); }
    const sg = o.signals ? o.signals() : null; const live = sg && sg.live;
    ctx.save(); ctx.beginPath(); ctx.rect(pad, pad, sw, sh); ctx.clip();
    const blur = 1.2 + (1 - st.focus) * 5;
    ctx.strokeStyle = `rgba(140,255,170,${0.35 + 0.65 * st.inten})`; ctx.lineWidth = blur; ctx.shadowColor = '#58ff8b'; ctx.shadowBlur = 6 * st.inten; ctx.lineJoin = 'round';
    ctx.beginPath();
    const now = performance.now() / 1000;
    if (st.mode === 'YT') {
      const tdv = TDIV[st.td] * (1 + et[st.td]); const N = 700; const f1 = live && !st.gnd ? sg.y1 : null;
      const t0 = st.trig ? (sg && sg.trig0 ? sg.trig0() : 0) : (now * 0.731) % 1;
      for (let i = 0; i <= N; i++) { const xd = -5 + 10 * i / N; const t = t0 + (xd + 5 - st.xp) * tdv; const yv = f1 ? ampDiv(f1(t)) : 0; const X = cx + xd * dx, Y = cy - (yv + st.yp) * dy; i ? ctx.lineTo(X, Y) : ctx.moveTo(X, Y); }
    } else {
      if (live && sg.y1 && sg.y2 && !st.gnd) {
        const span = sg.window || 0.01, N = 1400;
        for (let i = 0; i <= N; i++) { const t = now + span * i / N; const xv = sg.y2(t) / (VDIV[st.v2] * (1 + e2[st.v2])), yv = ampDiv(sg.y1(t)); const X = cx + (xv + st.xp) * dx, Y = cy - (yv + st.yp) * dy; i ? ctx.lineTo(X, Y) : ctx.moveTo(X, Y); }
      } else { const X = cx + st.xp * dx, Y = cy - st.yp * dy; ctx.moveTo(X - 1, Y); ctx.arc(X, Y, 2, 0, 7); }
    }
    ctx.stroke(); ctx.shadowBlur = 0;
    if (st.guide && live && st.mode === 'YT' && sg.vpk && !st.gnd) {
      const a = ampDiv(sg.vpk); ctx.setLineDash([5, 5]); ctx.strokeStyle = 'rgba(255,214,110,.75)'; ctx.lineWidth = 1;
      [a, -a].forEach(v => { const Y = cy - (v + st.yp) * dy; ctx.beginPath(); ctx.moveTo(pad, Y); ctx.lineTo(pad + sw, Y); ctx.stroke(); });
      ctx.setLineDash([]);
    }
    ctx.restore();
    ctx.fillStyle = '#9fb0b8'; ctx.font = `10.5px ${T.mono}`; ctx.textAlign = 'left'; ctx.textBaseline = 'bottom';
    ctx.fillText(st.mode === 'YT' ? `CH1 ${fmtVd(VDIV[st.v1])}/div   ${fmtTd(TDIV[st.td])}/div` : `X: ${fmtVd(VDIV[st.v2])}/div   Y: ${fmtVd(VDIV[st.v1])}/div`, pad + 4, H - 2);
    if (!live) { ctx.fillStyle = '#7f9a8a'; ctx.textAlign = 'center'; ctx.font = `12px ${T.sans}`; ctx.fillText(o.offText ? o.offText() : 'No signal: wire the circuit and switch the power ON', cx, pad + 22); }
  }
  return { st, view, heightDiv: Vpp => Vpp / (VDIV[st.v1] * (1 + e1[st.v1])), lenDiv: (n, f) => n / (f * TDIV[st.td] * (1 + et[st.td])), vdiv: () => VDIV[st.v1], tdiv: () => TDIV[st.td] };
}
const CRO_KNOW = String.raw`
<h3>Know your CRO: the main controls</h3>
<table><tr><th>Control</th><th>What it does</th></tr>
<tr><td>INTENSITY</td><td>Brightness of the spot/trace (keep low to protect the screen).</td></tr>
<tr><td>FOCUS</td><td>Makes the trace thin and sharp.</td></tr>
<tr><td>X and Y POSITION</td><td>Move the trace horizontally / vertically, to bring a peak on a grid line for reading.</td></tr>
<tr><td>VOLTS/DIV (vertical attenuator)</td><td>Volts represented by one vertical division. Choose it so that the trace is as tall as possible without going off the screen.</td></tr>
<tr><td>TIME/DIV (time base)</td><td>Time represented by one horizontal division; sets the speed of the horizontal sweep.</td></tr>
<tr><td>TRIGGER</td><td>Starts each sweep at the same point of the signal, so the trace stands still.</td></tr>
<tr><td>AC / GND coupling</td><td>GND disconnects the input and shows the zero line; use it to set the reference.</td></tr>
<tr><td>X–Y mode</td><td>Time base off; CH2 drives the horizontal plates and CH1 the vertical plates (used for Lissajous figures).</td></tr></table>`;

const SCH14 = () => SC.svg(420, 230, SC.AC(90, 40, 90, 190, 'generator') + SC.w([90, 40], [200, 40], [330, 40]) + SC.w([90, 190], [200, 190], [330, 190]) + SC.M(200, 40, 200, 190, 'V') + SC.t(222, 120, 'AC voltmeter', 'start', 10) + SC.box(330, 20, 80, 190, 'CRO', 'CH1 (Y)') + SC.dot(200, 40) + SC.dot(200, 190) + SC.t(318, 36, 'CH1', 'end', 9) + SC.t(318, 186, 'GND', 'end', 9));
const SCH15 = () => SC.svg(420, 240, SC.AC(90, 40, 90, 120, 'known f_x') + SC.AC(180, 110, 180, 190, 'unknown f_y') + SC.box(290, 20, 110, 200, 'CRO', 'X–Y mode') + SC.w([90, 40], [290, 40]) + SC.t(286, 36, 'X (CH2)', 'end', 9) + SC.w([180, 110], [180, 90], [290, 90]) + SC.t(286, 86, 'Y (CH1)', 'end', 9) + SC.w([90, 120], [90, 210], [290, 210]) + SC.w([180, 190], [180, 210]) + SC.dot(180, 210) + SC.t(286, 206, 'GND', 'end', 9));
/* ======================== EXP 14 ======================== */
EXPS.push({
  no: 14, id: 'exp14', group: 'Electricity & CRO', short: 'Study and calibration of CRO',
  title: 'Study of the oscilloscope and its calibration for voltage and frequency',
  aim: 'To study oscilloscope and calibrate it for the measurement of voltage and frequency.',
  apparatus: ['Cathode ray oscilloscope (CRO)', 'Audio-frequency function generator (sine wave, with digital frequency display)', 'AC voltmeter (rms reading)', 'BNC / connecting leads'],
  theory: String.raw`
<h2>The cathode ray oscilloscope</h2>
<p>A CRO consists of a cathode ray tube (electron gun, vertical and horizontal deflecting plates, fluorescent screen), a vertical amplifier with a calibrated attenuator (VOLTS/DIV), a time-base (sweep) generator with a calibrated TIME/DIV switch, and a trigger circuit. The time base applies a saw-tooth voltage to the X-plates so the spot moves from left to right at a constant speed and flies back. The signal applied to the Y-plates moves the spot vertically, so a graph of voltage against time is traced on the screen.</p>
` + CRO_KNOW + String.raw`
<h2>Measurement of voltage</h2>
<p>If the peak-to-peak height of the trace is \(h\) divisions and the vertical sensitivity is \(S_V\) volt/div,</p>
<div class="eq">\[V_{pp} = h\times S_V,\qquad V_{0}=\frac{V_{pp}}{2},\qquad V_{rms} = \frac{V_0}{\sqrt 2} = \frac{V_{pp}}{2\sqrt 2}\]</div>
<h2>Measurement of frequency</h2>
<p>If \(n\) complete cycles occupy a horizontal length of \(L\) divisions and the time base is \(S_T\) second/div,</p>
<div class="eq">\[T = \frac{L\times S_T}{n},\qquad f = \frac{1}{T}\]</div>
<h2>Calibration</h2>
<p>The calibrated settings of a CRO have small errors. To calibrate, a known voltage (measured with an AC voltmeter) and a known frequency (from the generator) are applied, and the CRO readings are compared with them. The <b>calibration factor</b> is</p>
<div class="eq">\[K_V = \frac{V_{rms}\ (\text{voltmeter})}{V_{rms}\ (\text{CRO})},\qquad K_f = \frac{f\ (\text{generator})}{f\ (\text{CRO})}\]</div>
<p>A graph of the CRO value against the standard value is a straight line; its slope shows the calibration of the CRO.</p>`,
  procedure: String.raw`
<h2>Part A. Getting a trace</h2>
<ol class="steps">
<li>Switch on the CRO and wait for the trace. Adjust INTENSITY and FOCUS to get a thin, sharp line.</li>
<li>Set the coupling to GND and bring the line to the centre with the Y POSITION control. Set coupling back to AC.</li>
<li>Connect the generator output to CH1 (Y input) and the generator ground to the CRO ground. Connect the AC voltmeter across the generator output.</li></ol>
<h2>Part B. Calibration for voltage</h2>
<ol class="steps">
<li>Set the generator to about 1 kHz. Adjust the amplitude and note the AC voltmeter reading \(V_{rms}\).</li>
<li>Select VOLTS/DIV so that the trace is as large as possible within the screen. Use Y POSITION to put the lower peak on a horizontal grid line and count the peak-to-peak height \(h\) (to 0.2 div using the small marks on the central axis).</li>
<li>Calculate \(V_{pp} = h S_V\) and \(V_{rms} = V_{pp}/2\sqrt2\). Repeat for 5–6 amplitudes.</li></ol>
<h2>Part C. Calibration for frequency</h2>
<ol class="steps">
<li>Keep a convenient amplitude. Set a frequency on the generator.</li>
<li>Select TIME/DIV so that 1–4 complete cycles fill the screen. With X POSITION put the start of a cycle on a vertical grid line and measure the length \(L\) of \(n\) cycles.</li>
<li>Calculate \(T = L S_T/n\) and \(f = 1/T\). Repeat for 5–6 frequencies (e.g. 100 Hz – 10 kHz).</li>
<li>Plot CRO readings against the voltmeter / generator readings and find the calibration factors.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> wire the generator, CRO and AC voltmeter (or press <i>Show me</i>), switch ON, then set the controls. Read the screen yourself and type the number of divisions; the bench checks your reading and records it.</div>`,
  precautions: String.raw`<li>Keep the intensity low; a bright stationary spot burns the screen.</li><li>Use the full screen height/width for better accuracy.</li><li>Read the trace from the centre of its thickness and avoid parallax.</li><li>Keep the variable (uncalibrated) knobs of VOLTS/DIV and TIME/DIV at the CAL position.</li><li>Connect the ground leads of all instruments together.</li>`,
  errors: String.raw`<li>Thickness of the trace and the limited resolution of the screen (about 0.1 div).</li><li>Calibration errors of the attenuator and time base (this is what we measure).</li>`,
  viva: [
    ['What is the function of the time base?', 'It generates a saw-tooth voltage that sweeps the spot horizontally at a uniform speed and then returns it quickly, so that the signal is displayed against time.'],
    ['Why is triggering necessary?', 'Each sweep must start at the same point of the signal; otherwise successive traces fall at different places and the picture moves or blurs.'],
    ['How do you measure rms voltage with a CRO?', 'Measure the peak-to-peak height h in divisions; V_pp = h × volts/div and V_rms = V_pp / (2√2) for a sine wave.'],
    ['Why does the AC voltmeter read the rms value?', 'It is calibrated in rms values for sinusoidal signals because rms gives the equivalent heating (DC) value.'],
    ['What is the use of X–Y mode?', 'To plot one signal against another, e.g. Lissajous figures, phase measurement and component characteristics.'],
    ['What is the advantage of a CRO over a voltmeter?', 'It has very high input impedance, shows the waveform, can measure peak values, frequency and phase, and works at high frequencies.'],
    ['What materials are used for the screen?', 'Phosphors such as zinc sulphide or zinc orthosilicate that glow when struck by electrons.'],
    ['What is meant by deflection sensitivity?', 'The deflection of the spot on the screen per volt applied to the deflecting plates (mm/V).']],
  bench(root) {
    const R = apparatus('exp14');
    const pg = circuitPage(root);
    const gst = Object.assign({ rng: 2, dial: 1.0, vpp: 4.0 }, LS.get('exp14.gen', {}));
    const RANGES = [10, 100, 1000, 10000];
    const freq = () => +(gst.dial * RANGES[gst.rng]).toPrecision(5);
    const cb = circuitBench(pg.top, {
      key: 'exp14', W: 900, H: 470,
      parts: [{ id: 'fg', type: 'fgen', label: 'Function generator', x: 40, y: 60 }, { id: 'cro', type: 'cro', label: 'CRO', x: 620, y: 40 }, { id: 'vm', type: 'dmm', label: 'AC voltmeter', x: 380, y: 260, p: { unit: 'V', mode: 'AC rms' } }],
      spec: { nets: [['fg.OUT', 'cro.CH1', 'vm.+'], ['fg.GND', 'cro.GND', 'vm.-']] },
      schematic: SCH14,
      onPower: on => upd()
    });
    const cro = croUnit(pg.instr, { rng: R, key: 'exp14', force: { mode: 'YT' }, signals: () => cb.on ? { live: true, y1: t => gst.vpp / 2 * Math.sin(2 * Math.PI * freq() * t), vpk: gst.vpp / 2 } : { live: false } });
    const genCard = card('Function generator', 'sine wave');
    const rngSeg = seg({ label: 'Range', value: gst.rng, options: RANGES.map((r, i) => [i, `×${r >= 1000 ? r / 1000 + 'k' : r}`]), onChange: v => { gst.rng = v; sv(); } });
    const dial = slider({ label: 'Frequency dial', min: 1, max: 10, step: 0.01, value: gst.dial, fmt: v => v.toFixed(2), fine: [['−0.1', -0.1], ['−0.01', -0.01], ['+0.01', 0.01], ['+0.1', 0.1]], onInput: v => { gst.dial = v; sv(); } });
    const amp = slider({ label: 'Amplitude (peak-to-peak)', min: 0, max: 10, step: 0.05, value: gst.vpp, fmt: v => v.toFixed(2) + ' V', onInput: v => { gst.vpp = v; sv(); } });
    cb.bindKnob('fg', 'f', dial); cb.bindKnob('fg', 'a', amp);
    genCard.append(rngSeg.el, dial.el, amp.el);
    function sv() { LS.set('exp14.gen', gst); upd(); }
    function upd() { cb.show('fg', { val: freq().toFixed(freq() < 100 ? 2 : 1) + ' Hz', k1: (gst.dial - 1) / 9, k2: gst.vpp / 10 }); cb.show('vm', { val: (gst.vpp / (2 * Math.SQRT2)).toFixed(3) }); }
    upd();
    // reading entry
    const hIn = h('input', { type: 'number', step: 0.1, min: 0, id: 'e14h' }), nIn = h('input', { type: 'number', step: 1, min: 1, value: 2, id: 'e14n' }), lIn = h('input', { type: 'number', step: 0.1, min: 0, id: 'e14l' });
    const sb = statusBox(); sb.say('Read the screen, type the divisions, then record.');
    const bV = h('button', { type: 'button', class: 'btn pri' }, 'Record voltage reading'), bF = h('button', { type: 'button', class: 'btn pri' }, 'Record frequency reading');
    const readCard = card('Read the screen', 'type what you count on the graticule',
      h('div', { class: 'readbox' }, h('label', { for: 'e14h' }, 'Peak-to-peak height h (div)', hIn), bV),
      h('div', { class: 'readbox', style: 'margin-top:10px' }, h('label', { for: 'e14n' }, 'Number of cycles n', nIn), h('label', { for: 'e14l' }, 'Horizontal length L (div)', lIn), bF), sb.el);
    pg.instr.append(genCard, readCard);
    const ready = () => { if (!cb.ready(sb.say)) return false; if (cro.st.mode !== 'YT') { sb.say('Use the Y–T mode for these measurements.', 'warn'); return false; } if (cro.st.gnd) { sb.say('Set the input coupling back from GND.', 'warn'); return false; } return true; };
    bV.addEventListener('click', () => {
      if (!ready()) return; const hv = parseFloat(hIn.value); const tr = cro.heightDiv(gst.vpp);
      if (tr > 8.05) return sb.say('The trace is taller than the screen. Choose a larger VOLTS/DIV.', 'warn');
      if (tr < 1.5) return sb.say('The trace is too small for an accurate reading. Choose a smaller VOLTS/DIV.', 'warn');
      if (!isFinite(hv)) return sb.say('Type the peak-to-peak height in divisions.', 'warn');
      if (Math.abs(hv - tr) > 0.12) return sb.say(`Count again. Put the lower peak on a grid line with Y POSITION and count to the top peak (each small mark = 0.2 div). ${Math.abs(hv - tr) > 0.5 ? '' : 'You are close.'}`, 'warn');
      tA.add({ vm: rnd(gst.vpp / (2 * Math.SQRT2), 3), vd: cro.vdiv(), h: rnd(hv, 2) }); sb.say(`Recorded h = ${hv} div at ${fmtVd(cro.vdiv())}/div.`, 'ok');
    });
    bF.addEventListener('click', () => {
      if (!ready()) return; const n = parseInt(nIn.value), L = parseFloat(lIn.value); if (!(n >= 1) || !isFinite(L)) return sb.say('Type n and L.', 'warn');
      const tr = cro.lenDiv(n, freq()); if (tr > 10.05) return sb.say(`${n} cycles do not fit on the screen at this TIME/DIV. Use fewer cycles or a larger TIME/DIV.`, 'warn');
      if (tr < 2) return sb.say('The cycles are too short for an accurate reading. Use a smaller TIME/DIV (or more cycles).', 'warn');
      if (Math.abs(L - tr) > 0.12) return sb.say(`Measure again: put the start of a cycle on a vertical grid line with X POSITION and count to the end of cycle ${n}. ${Math.abs(L - tr) > 0.5 ? '' : 'You are close.'}`, 'warn');
      tB.add({ f: freq(), td: cro.tdiv(), n, L: rnd(L, 2) }); sb.say(`Recorded ${n} cycles in ${L} div at ${fmtTd(cro.tdiv())}/div.`, 'ok');
    });
    const vcro = r => r.h * r.vd / (2 * Math.SQRT2), fcro = r => r.n / (r.L * r.td);
    const tA = new ObsTable({ key: 'exp14.A', title: 'Table 1. Calibration for voltage', deletable: true, onChange: analyse, columns: [{ label: 'V<sub>rms</sub> voltmeter (V)', k: 'vm', fmt: v => v.toFixed(3) }, { label: 'VOLTS/DIV', k: 'vd', fmt: fmtVd }, { label: 'h (div)', k: 'h', fmt: v => v.toFixed(1) }, { label: 'V<sub>pp</sub> = h × S<sub>V</sub> (V)', v: r => r.h * r.vd, fmt: v => v.toFixed(3), calc: 1 }, { label: 'V<sub>rms</sub> CRO (V)', v: vcro, fmt: v => v.toFixed(3), calc: 1 }, { label: 'K<sub>V</sub>', v: r => r.vm / vcro(r), fmt: v => v.toFixed(3), calc: 1 }] });
    const tB = new ObsTable({ key: 'exp14.B', title: 'Table 2. Calibration for frequency', deletable: true, onChange: analyse, columns: [{ label: 'f generator (Hz)', k: 'f' }, { label: 'TIME/DIV', k: 'td', fmt: fmtTd }, { label: 'n', k: 'n' }, { label: 'L (div)', k: 'L', fmt: v => v.toFixed(1) }, { label: 'T = L S<sub>T</sub>/n (ms)', v: r => r.L * r.td / r.n * 1e3, fmt: v => v.toFixed(4), calc: 1 }, { label: 'f CRO (Hz)', v: fcro, fmt: v => v.toFixed(1), calc: 1 }, { label: 'K<sub>f</sub>', v: r => r.f / fcro(r), fmt: v => v.toFixed(3), calc: 1 }] });
    const pV = plotCard('Graph: V_rms (CRO) against V_rms (voltmeter)', { get: () => { const p = tA.rows.map(r => [r.vm, vcro(r)]); return { xLabel: 'V_rms from voltmeter (V)', yLabel: 'V_rms from CRO (V)', series: [{ pts: p, fit: p.length > 1 ? linregOrigin(p) : null }], zeroX: true, zeroY: true }; } });
    const pF = plotCard('Graph: f (CRO) against f (generator)', { get: () => { const p = tB.rows.map(r => [r.f / 1000, fcro(r) / 1000]); return { xLabel: 'f generator (kHz)', yLabel: 'f from CRO (kHz)', series: [{ pts: p, fit: p.length > 1 ? linregOrigin(p) : null }], zeroX: true, zeroY: true }; } });
    const res = resultBox();
    function analyse() {
      pV.view.redraw(); pF.view.redraw();
      const kv = tA.rows.map(r => r.vm / vcro(r)), kf = tB.rows.map(r => r.f / fcro(r));
      let html = '<h4>Calculation &amp; result</h4>';
      if (!kv.length && !kf.length) { res.set(html + '<p class="small muted">Record at least 4–5 voltage readings and 4–5 frequency readings.</p>'); return; }
      if (kv.length) html += `<p>Voltage: mean calibration factor K<sub>V</sub> = <b>${mean(kv).toFixed(3)}</b> (the CRO reads ${Math.abs((1 / mean(kv) - 1) * 100).toFixed(1)}% ${mean(kv) < 1 ? 'high' : 'low'}).</p>`;
      if (kf.length) html += `<p>Frequency: mean calibration factor K<sub>f</sub> = <b>${mean(kf).toFixed(3)}</b> (time base error ${((mean(kf) - 1) * 100).toFixed(1)}%).</p>`;
      html += '<p class="small muted">Multiply future CRO readings by these factors to correct them. Factors differ slightly between VOLTS/DIV and TIME/DIV ranges because each range has its own small error.</p>';
      res.set(html);
    }
    pg.notes.append(card('Lab notebook', 'readings are saved in this browser', h('div', { class: 'stack' }, tA.el, tB.el)), pV.el, pF.el, card('Result', '', res.el)); analyse();
  }
});

/* ======================== EXP 15 ======================== */
EXPS.push({
  no: 15, id: 'exp15', group: 'Electricity & CRO', short: 'Unknown frequency by Lissajous figures',
  title: 'Unknown frequency of a source using Lissajous figures',
  aim: 'Determine the unknown frequency of a given source using Lissajous figure.',
  apparatus: ['Cathode ray oscilloscope with X–Y mode', 'Audio oscillator / function generator of known (calibrated) frequency', 'Source of unknown frequency', 'Connecting leads'],
  theory: String.raw`
<h2>Lissajous figures</h2>
<p>When two sinusoidal voltages are applied simultaneously to the X and Y deflecting plates of a CRO (time base off, X–Y mode), the spot moves under the combined action of two simple harmonic motions at right angles:</p>
<div class="eq">\[x = A\sin(2\pi f_x t),\qquad y = B\sin(2\pi f_y t + \phi)\]</div>
<p>If the ratio \(f_y/f_x\) is a ratio of small whole numbers, the path closes on itself and a stationary pattern is seen: a <b>Lissajous figure</b>. If the ratio is not exact, the figure slowly changes its shape (it appears to rotate); the frequency of the known source is adjusted until the figure stands still.</p>
<h3>Rule of tangencies</h3>
<p>Draw (imagine) a horizontal line touching the top of the figure and a vertical line touching its side. Count the number of points of tangency \(n_h\) (on the horizontal line) and \(n_v\) (on the vertical line). Then</p>
<div class="eq">\[\frac{f_y}{f_x} = \frac{n_h}{n_v}\qquad\Rightarrow\qquad f_y = f_x\,\frac{n_h}{n_v}\]</div>
<p>With the unknown on the Y input and the known generator on the X input, the unknown frequency is \(f_y\).</p>
<table><tr><th>f<sub>y</sub> : f<sub>x</sub></th><th>1 : 1</th><th>1 : 2</th><th>2 : 1</th><th>3 : 2</th><th>1 : 3</th></tr><tr><td>n<sub>h</sub> (top side)</td><td>1</td><td>1</td><td>2</td><td>3</td><td>1</td></tr><tr><td>n<sub>v</sub> (vertical side)</td><td>1</td><td>2</td><td>1</td><td>2</td><td>3</td></tr><tr><td>Shape</td><td>ellipse / circle</td><td>upright “8”</td><td>lying “∞”</td><td>three loops across</td><td>three loops up</td></tr></table>
<p>For a 1 : 1 ratio the shape depends on the phase difference: a straight line (0° or 180°), an ellipse, or a circle (90° with equal amplitudes).</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Switch the CRO to X–Y mode. Connect the unknown source to the Y input (CH1) and the known generator to the X input (CH2). Connect all grounds together.</li>
<li>Switch on. Adjust VOLTS/DIV of both channels and the amplitudes so that the figure fills about two-thirds of the screen.</li>
<li>Vary the frequency of the known generator slowly until a stationary figure is obtained. Note the generator frequency \(f_x\).</li>
<li>Count the points of tangency on a horizontal side (\(n_h\)) and on a vertical side (\(n_v\)) of the figure. Calculate \(f_y = f_x n_h / n_v\).</li>
<li>Obtain stationary figures for several ratios (1:1, 1:2, 2:1, 2:3, 3:2, 1:3 …). Take the mean of the values of the unknown frequency.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> the generator has fine buttons down to 0.1 Hz. When the frequency is close to a simple ratio the figure turns slowly; make it stand still before counting. If the figure is a closed line for a 1:1 ratio, wait a moment for it to open out: the phase keeps changing until the frequencies are exactly equal.</div>`,
  precautions: String.raw`<li>Switch off the time base (X–Y mode).</li><li>Keep the amplitudes moderate so that the whole figure is on the screen.</li><li>Change the known frequency very slowly near a stationary figure.</li><li>Count tangencies only on an open figure (avoid the degenerate line or overlapping loops).</li>`,
  errors: String.raw`<li>The figure is never perfectly still when the frequencies drift.</li><li>Error in the calibration of the known generator.</li>`,
  viva: [
    ['What are Lissajous figures?', 'Patterns traced by a spot under two simple harmonic motions at right angles, obtained on a CRO by applying sinusoidal voltages to the X and Y plates.'],
    ['How is the frequency ratio found?', 'f_y/f_x = number of tangencies on a horizontal line / number of tangencies on a vertical line.'],
    ['Why does the figure rotate?', 'When the frequency ratio is not exactly a ratio of integers, the phase difference changes continuously, so the shape changes periodically.'],
    ['What figure is obtained for equal frequencies with a 90° phase difference and equal amplitudes?', 'A circle.'],
    ['How can phase difference be measured with Lissajous figures?', 'For equal frequencies an ellipse is obtained; sin φ = (intercept on Y-axis)/(maximum Y deflection).'],
    ['Why must the time base be switched off?', 'Because the X-plates must be driven by the second signal, not by the saw-tooth sweep.']],
  bench(root) {
    const R = apparatus('exp15');
    const fu = +R.range(150, 820).toFixed(1), phu = R.range(0, 6.28);
    const pg = circuitPage(root);
    const gst = Object.assign({ f: 300 }, LS.get('exp15.gen', {}));
    const cb = circuitBench(pg.top, {
      key: 'exp15', W: 900, H: 470,
      parts: [{ id: 'fg', type: 'fgen', label: 'Known generator', x: 30, y: 40, p: { name: 'AUDIO OSCILLATOR' } }, { id: 'src', type: 'osc', label: 'Unknown source', x: 30, y: 290 }, { id: 'cro', type: 'cro', label: 'CRO (X–Y mode)', x: 620, y: 60 }],
      spec: { nets: [['fg.OUT', 'cro.CH2'], ['src.OUT', 'cro.CH1'], ['fg.GND', 'src.GND', 'cro.GND']] },
      schematic: SCH15,
      onPower: () => upd()
    });
    const cro = croUnit(pg.instr, {
      rng: R, key: 'exp15', guides: false, onMode: () => { }, force: { v1: 6, v2: 6 },
      signals: () => cb.on ? { live: true, y1: t => 2 * Math.sin(2 * Math.PI * fu * t + phu), y2: t => 2 * Math.sin(2 * Math.PI * gst.f * t), window: windowFor(), trig0: () => 0 } : { live: false },
      offText: () => 'No signal: wire the circuit and switch the power ON'
    });
    function bestRatio() { let best = null; for (let q = 1; q <= 4; q++) for (let p = 1; p <= 4; p++) { const err = Math.abs(fu * q - gst.f * p); if (!best || err < best.err) best = { p, q, err }; } return best; } // fu/f ≈ p/q
    function windowFor() { const b = bestRatio(); return Math.min(0.05, b.q / gst.f * 1.02); }
    const fS = slider({ label: 'Known frequency f_x', min: 20, max: 5000, smin: 0, smax: 1000, sstep: 1, toS: f => Math.log(f / 20) / Math.log(250) * 1000, fromS: s => 20 * 250 ** (s / 1000), value: gst.f, fmt: v => v.toFixed(1) + ' Hz', fine: [['−10', -10], ['−1', -1], ['−0.1', -0.1], ['+0.1', 0.1], ['+1', 1], ['+10', 10]], onInput: v => { gst.f = Math.round(v * 10) / 10; LS.set('exp15.gen', gst); upd(); } });
    cb.bindKnob('fg', 'f', fS);
    function upd() { cb.show('fg', { val: gst.f.toFixed(1) + ' Hz', k1: Math.log(gst.f / 20) / Math.log(250), k2: 0.4 }); }
    upd();
    const nh = h('input', { type: 'number', min: 1, max: 6, step: 1, id: 'e15h' }), nv = h('input', { type: 'number', min: 1, max: 6, step: 1, id: 'e15v' });
    const sb = statusBox(); sb.say('Make the figure stand still, count the tangencies, then record.');
    const bR = h('button', { type: 'button', class: 'btn pri' }, 'Record figure');
    pg.instr.append(card('Known generator', 'X input', fS.el), card('Count the tangencies', '', h('div', { class: 'readbox' }, h('label', { for: 'e15h' }, 'n_h (touching a horizontal side)', nh), h('label', { for: 'e15v' }, 'n_v (touching a vertical side)', nv), bR), sb.el));
    bR.addEventListener('click', () => {
      if (!cb.ready(sb.say)) return;
      if (cro.st.mode !== 'XY') return sb.say('Switch the CRO to X–Y mode: in Y–T mode you see only the time-base trace.', 'warn');
      const a = parseInt(nh.value), b = parseInt(nv.value); if (!(a >= 1 && b >= 1)) return sb.say('Type both counts.', 'warn');
      const drift = Math.abs(fu * b - gst.f * a);
      const br = bestRatio();
      if (Math.abs(a / b - br.p / br.q) > 1e-6 || br.err > 3 * Math.max(br.p, br.q)) return sb.say(br.err > 3 * Math.max(br.p, br.q) ? 'There is no stationary figure at this frequency. Change the known frequency slowly until the pattern stops turning.' : 'Count again. Count the points where the figure touches a horizontal side, then a vertical side.', 'warn');
      if (drift > 0.25) return sb.say('The ratio is right, but the figure is still turning. Adjust the known frequency in 0.1 Hz steps until it stands still.', 'warn');
      tbl.add({ nh: a, nv: b, fx: gst.f }); sb.say(`Recorded ${a} : ${b} at f_x = ${gst.f} Hz.`, 'ok');
    });
    const fy = r => r.fx * r.nh / r.nv;
    const tbl = new ObsTable({ key: 'exp15.t', title: 'Table. Stationary Lissajous figures', deletable: true, onChange: analyse, columns: [{ label: 'n<sub>h</sub>', k: 'nh' }, { label: 'n<sub>v</sub>', k: 'nv' }, { label: 'f<sub>y</sub> : f<sub>x</sub>', v: r => `${r.nh} : ${r.nv}` }, { label: 'f<sub>x</sub> known (Hz)', k: 'fx', fmt: v => v.toFixed(1) }, { label: 'f<sub>y</sub> = f<sub>x</sub> n<sub>h</sub>/n<sub>v</sub> (Hz)', v: fy, fmt: v => v.toFixed(1), calc: 1 }] });
    const res = resultBox();
    function analyse() {
      const v = tbl.rows.map(fy);
      if (!v.length) { res.set('<h4>Result</h4><p class="small muted">Record stationary figures for at least four different ratios.</p>'); return; }
      const m = mean(v), sd = v.length > 1 ? Math.sqrt(v.reduce((s, x) => s + (x - m) ** 2, 0) / (v.length - 1)) : 0;
      res.set(`<h4>Result</h4><p class="big">Unknown frequency f = <b>${m.toFixed(1)} Hz</b>${v.length > 1 ? ` ± ${sd.toFixed(1)} Hz` : ''}</p><p class="small muted">Mean of ${v.length} figures. ${v.length > 1 ? `Spread ${(sd / m * 100).toFixed(2)}%.` : ''}</p>`);
    }
    pg.notes.append(card('Lab notebook', '', tbl.el), card('Result', '', res.el)); analyse();
  }
});

/* ================= experiments 16–17: DC network theorems ================= */
const E_RL = [10, 22, 33, 47, 68, 82, 100, 120, 150, 180, 220, 270, 330, 390, 470, 560, 680, 820, 1000, 1500, 2200, 3300];
function stepSelect(o) {
  const s = selectBox({ label: o.label, options: o.values.map((v, i) => [i, o.fmt(v)]), value: o.values.indexOf(o.value), onChange: v => o.onChange(o.values[+v]) });
  const mv = d => { const i = clamp(+s.get() + d, 0, o.values.length - 1); s.set(i); o.onChange(o.values[i]); };
  const prev = h('button', { type: 'button', class: 'fbtn', 'aria-label': o.label + ' smaller' }, '◀'), next = h('button', { type: 'button', class: 'fbtn', 'aria-label': o.label + ' larger' }, '▶');
  prev.addEventListener('click', () => mv(-1)); next.addEventListener('click', () => mv(1));
  return { el: h('div', { class: 'opts' }, s.el, prev, next), set: v => s.set(o.values.indexOf(v)) };
}

const SCH16 = () => SC.svg(460, 250, SC.B(40, 60, 40, 200, 'E', true) + SC.R(40, 40, 170, 40, 'R_s') + SC.w([40, 60], [40, 40]) + SC.M(170, 40, 280, 40, 'mA') + SC.w([280, 40], [330, 40]) + SC.R(330, 40, 330, 210, 'R_L', true) + SC.w([330, 40], [410, 40]) + SC.M(410, 40, 410, 210, 'V') + SC.w([410, 210], [40, 210], [40, 200]) + SC.dot(330, 40) + SC.dot(330, 210));
/* ======================== EXP 16 ======================== */
EXPS.push({
  no: 16, id: 'exp16', group: 'Electricity & CRO', short: 'Maximum power transfer theorem',
  title: 'Verification of the maximum power transfer theorem',
  aim: 'To verify the maximum power transfer theorem.',
  apparatus: ['DC power supply (0–12 V)', 'Resistance box for the source (internal) resistance R<sub>s</sub>', 'Resistance box for the load R<sub>L</sub>', 'Milliammeter (0–100 mA)', 'Voltmeter (0–10 V)', 'Connecting wires'],
  theory: String.raw`
<h2>Statement</h2>
<p>A source of emf \(E\) and internal resistance \(R_s\) delivers <b>maximum power</b> to a load when the load resistance equals the internal resistance of the source: \(R_L = R_s\).</p>
<h2>Proof</h2>
<p>The current in the circuit is \(I = \dfrac{E}{R_s + R_L}\) and the power in the load is</p>
<div class="eq">\[P = I^2 R_L = \frac{E^{2}R_L}{(R_s+R_L)^{2}}\]</div>
<p>Setting \(dP/dR_L = 0\):</p>
<div class="eq">\[\frac{(R_s+R_L)^2 - 2R_L(R_s+R_L)}{(R_s+R_L)^4}=0\ \Rightarrow\ R_L = R_s,\qquad P_{max} = \frac{E^{2}}{4R_s}\]</div>
<p>At maximum power transfer the efficiency \(\eta = \dfrac{R_L}{R_s+R_L}\) is only 50%: half the power is lost in the source. For any network, \(R_s\) is replaced by the Thevenin resistance seen from the load terminals.</p>
<p>In the experiment a regulated DC supply (internal resistance nearly zero) with a resistance \(R_s\) in series acts as a source with internal resistance \(R_s\). The load resistance is varied, and \(V\) and \(I\) of the load are measured: \(P = VI\). The graph of \(P\) against \(R_L\) has a maximum at \(R_L = R_s\).</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Connect the circuit as in the diagram: supply → R<sub>s</sub> → milliammeter → load R<sub>L</sub> → back to the supply; the voltmeter across R<sub>L</sub>.</li>
<li>Set the supply to a fixed voltage (e.g. 6 V) and R<sub>s</sub> to a fixed value (e.g. 100 Ω).</li>
<li>Starting with a small R<sub>L</sub>, note the current I and the voltage V across the load.</li>
<li>Increase R<sub>L</sub> in steps (take more steps near R<sub>L</sub> = R<sub>s</sub>) and note V and I each time.</li>
<li>Calculate P = VI for each R<sub>L</sub>. Plot P against R<sub>L</sub> and find R<sub>L</sub> at maximum power. Compare it with R<sub>s</sub>.</li>
<li>Repeat with another value of R<sub>s</sub>.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> drag the equipment from the tray, wire it as in the diagram (or press <i>Show me</i>), press <i>Check circuit</i>, then switch the power ON. Change R<sub>L</sub> with the ◀ ▶ buttons and record.</div>`,
  precautions: String.raw`<li>Keep the supply voltage constant throughout the experiment.</li><li>Connect the ammeter in series and the voltmeter in parallel with correct polarity.</li><li>Do not allow large currents for long; switch off between readings if resistors heat up.</li><li>Take closely spaced readings near R<sub>L</sub> = R<sub>s</sub>.</li>`,
  errors: String.raw`<li>Resistance of the milliammeter and of the connecting wires adds to R<sub>s</sub>.</li><li>Tolerance of the resistance boxes.</li><li>The maximum of the curve is flat, so R<sub>L</sub> at maximum is hard to locate exactly.</li>`,
  viva: [
    ['State the maximum power transfer theorem.', 'A source delivers maximum power to the load when the load resistance equals the internal (Thevenin) resistance of the source.'],
    ['What is the efficiency at maximum power transfer?', '50%, because equal power is dissipated in the internal resistance.'],
    ['Where is maximum power transfer used?', 'In communication and electronic circuits (impedance matching of amplifiers to loudspeakers, antennas to receivers), where power is small and transfer is more important than efficiency.'],
    ['Why is it not used in power transmission?', 'Because 50% efficiency would waste half of the generated power; power systems are designed for high efficiency instead.'],
    ['What is the maximum power?', 'P_max = E²/(4R_s).'],
    ['How is the theorem applied to an AC circuit?', 'Maximum power is transferred when the load impedance is the complex conjugate of the source impedance.']],
  bench(root) {
    const R = apparatus('exp16');
    const st = Object.assign({ E: 6, Rs: 100, RL: 47 }, LS.get('exp16.st', {}));
    const rA = 1.2 + R.range(0, 0.6), tolS = 1 + R.range(-0.01, 0.01), tolL = 1 + R.range(-0.01, 0.01);
    const pg = circuitPage(root);
    const cb = circuitBench(pg.top, {
      key: 'exp16', W: 900, H: 480,
      parts: [{ id: 'ps', type: 'supply', label: 'DC supply E', x: 30, y: 40, p: { max: 12 } }, { id: 'rs', type: 'rbox', label: 'R_s (source resistance)', x: 270, y: 40, p: { name: 'R_s' } }, { id: 'ma', type: 'dmm', label: 'Milliammeter', x: 500, y: 30, p: { unit: 'mA' } }, { id: 'rl', type: 'rbox', label: 'R_L (load)', x: 690, y: 40, p: { name: 'R_L' } }, { id: 'vm', type: 'dmm', label: 'Voltmeter', x: 740, y: 250, p: { unit: 'V' } }],
      spec: { nets: [['ps.+', 'rs.1'], ['rs.2', 'ma.+'], ['ma.-', 'rl.1', 'vm.+'], ['rl.2', 'vm.-', 'ps.-']] },
      schematic: SCH16,
      onPower: () => upd()
    });
    const meas = () => { const I = st.E / (st.Rs * tolS + st.RL * tolL + rA); return { I, V: I * st.RL * tolL }; };
    function upd() { const m = meas(); cb.show('ps', { v: st.E }); cb.show('rs', { val: st.Rs + ' Ω' }); cb.show('rl', { val: st.RL + ' Ω' }); cb.show('ma', { val: (m.I * 1e3).toFixed(2) }); cb.show('vm', { val: m.V.toFixed(3) }); LS.set('exp16.st', st); }
    const eS = slider({ label: 'Supply voltage E', min: 0, max: 12, step: 0.1, value: st.E, fmt: v => v.toFixed(1) + ' V', fine: [['−0.1', -0.1], ['+0.1', 0.1]], onInput: v => { st.E = v; upd(); } });
    cb.bindKnob('ps', 'v', eS);
    const rsS = stepSelect({ label: 'R_s', values: [47, 68, 100, 150, 220, 330, 470], value: st.Rs, fmt: fmtOhm, onChange: v => { st.Rs = v; upd(); } });
    const rlS = stepSelect({ label: 'R_L', values: E_RL, value: st.RL, fmt: fmtOhm, onChange: v => { st.RL = v; upd(); } });
    const sb = statusBox(); const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading');
    pg.instr.append(card('Controls', 'supply and resistance boxes', eS.el, rsS.el, rlS.el, h('div', { class: 'recrow' }, btn), sb.el));
    upd(); sb.say('Wire the circuit and switch ON, then record V and I for each R_L.');
    btn.addEventListener('click', () => {
      if (!cb.ready(sb.say)) return;
      const m = meas(); const row = { E: st.E, Rs: st.Rs, RL: st.RL, I: rnd(m.I * 1e3, 2), V: rnd(m.V, 3) };
      const i = tbl.rows.findIndex(r => r.Rs === row.Rs && r.RL === row.RL); if (i >= 0) tbl.update(i, row); else tbl.add(row);
      if (tbl.rows.some(r => r.Rs === st.Rs && Math.abs(r.E - st.E) > 1e-6)) sb.say('Keep E the same for all readings with this R_s.', 'warn'); else sb.say(`Recorded R_L = ${st.RL} Ω: V = ${row.V} V, I = ${row.I} mA.`, 'ok');
    });
    const P = r => r.V * r.I; // mW
    const tbl = new ObsTable({ key: 'exp16.t', title: 'Table. Power in the load', deletable: true, onChange: analyse, sort: (a, b) => a.Rs - b.Rs || a.RL - b.RL, columns: [{ label: 'R<sub>s</sub> (Ω)', k: 'Rs' }, { label: 'R<sub>L</sub> (Ω)', k: 'RL' }, { label: 'V (V)', k: 'V', fmt: v => v.toFixed(3) }, { label: 'I (mA)', k: 'I', fmt: v => v.toFixed(2) }, { label: 'P = VI (mW)', v: P, fmt: v => v.toFixed(2), calc: 1 }, { label: 'η = R<sub>L</sub>/(R<sub>s</sub>+R<sub>L</sub>)', v: r => r.RL / (r.Rs + r.RL) * 100, fmt: v => v.toFixed(0) + '%', calc: 1 }] });
    const groups = () => { const g = {}; tbl.rows.forEach(r => (g[r.Rs] = g[r.Rs] || []).push(r)); return Object.entries(g).map(([k, rs]) => ({ Rs: +k, rs: rs.sort((a, b) => a.RL - b.RL) })); };
    function peak(rs) { if (rs.length < 3) return null; let k = 0; rs.forEach((r, i) => { if (P(r) > P(rs[k])) k = i; }); if (k === 0 || k === rs.length - 1) return { edge: true, RL: rs[k].RL, P: P(rs[k]) }; const x = [k - 1, k, k + 1].map(i => Math.log(rs[i].RL)), y = [k - 1, k, k + 1].map(i => P(rs[i])); const d = (x[0] - x[1]) * (x[0] - x[2]) * (x[1] - x[2]); const A = (x[2] * (y[1] - y[0]) + x[1] * (y[0] - y[2]) + x[0] * (y[2] - y[1])) / d, B = (x[2] ** 2 * (y[0] - y[1]) + x[1] ** 2 * (y[2] - y[0]) + x[0] ** 2 * (y[1] - y[2])) / d; const xv = -B / (2 * A); return A < 0 && xv > x[0] && xv < x[2] ? { RL: Math.exp(xv), P: Math.max(...y) } : { RL: rs[k].RL, P: P(rs[k]) }; }
    const plot = plotCard('Graph: power P against load resistance R_L', { get: () => ({ xLabel: 'Load resistance R_L (Ω)', yLabel: 'Power P (mW)', series: groups().map(g => ({ pts: g.rs.map(r => [r.RL, P(r)]), join: true, label: `R_s = ${g.Rs} Ω` })), vlines: groups().map(g => ({ x: g.Rs, label: `R_s = ${g.Rs} Ω` })), zeroY: true, zeroX: true, xDec: 0 }) });
    const res = resultBox();
    function analyse() {
      plot.view.redraw();
      const gs = groups(); if (!gs.length) { res.set('<h4>Result</h4><p class="small muted">Record about 10–15 readings of R<sub>L</sub> from well below to well above R<sub>s</sub>.</p>'); return; }
      let html = '<h4>Calculation &amp; result</h4><div class="calc-lines">';
      gs.forEach(g => { const pk = peak(g.rs); const E = g.rs[0].E; html += `<div>R<sub>s</sub> = ${g.Rs} Ω: ${pk ? (pk.edge ? `largest P at the end of your range (${pk.RL} Ω); extend R<sub>L</sub>` : `maximum P = ${pk.P.toFixed(2)} mW at R<sub>L</sub> ≈ <b>${pk.RL.toFixed(0)} Ω</b>; theoretical P<sub>max</sub> = E²/4R<sub>s</sub> = ${(E * E / (4 * g.Rs) * 1e3).toFixed(2)} mW`) : 'need at least 3 readings'}</div>`; });
      html += '</div>';
      const ok = gs.map(g => ({ g, pk: peak(g.rs) })).filter(x => x.pk && !x.pk.edge);
      if (ok.length) html += `<p class="big" style="margin-top:10px">Maximum power is delivered when R<sub>L</sub> ≈ R<sub>s</sub>: theorem verified.</p><p class="small muted">The peak lies slightly above R<sub>s</sub> because the milliammeter (≈ ${rA.toFixed(1)} Ω) and wires add to the source resistance.</p>`;
      res.set(html);
    }
    pg.notes.append(card('Lab notebook', '', tbl.el), plot.el, card('Result', '', res.el)); analyse();
  }
});

/* ======================== EXP 17 ======================== */
function netSchem(stage) {
  let o = SC.B(30, 70, 30, 200, 'E') + SC.w([30, 70], [30, 40]) + SC.R(30, 40, 150, 40, 'R1') + SC.R(150, 40, 150, 210, 'R2') + SC.R(150, 40, 280, 40, 'R3') + SC.w([30, 200], [30, 210], [280, 210]) + SC.dot(150, 40) + SC.dot(150, 210);
  if (stage === 's3') o = SC.w([30, 40], [30, 210]) + SC.t(22, 130, 'short', 'end', 10) + SC.R(30, 40, 150, 40, 'R1') + SC.R(150, 40, 150, 210, 'R2') + SC.R(150, 40, 280, 40, 'R3') + SC.w([30, 210], [280, 210]) + SC.dot(150, 40) + SC.dot(150, 210);
  o += `<circle class="s-m" cx="280" cy="40" r="4"/><circle class="s-m" cx="280" cy="210" r="4"/>` + SC.t(280, 28, 'A') + SC.t(280, 232, 'B');
  if (stage === 's1') o += SC.w([284, 40], [360, 40]) + SC.M(360, 40, 360, 120, 'mA') + SC.R(360, 120, 360, 210, 'R_L') + SC.w([360, 210], [284, 210]);
  if (stage === 's2') o += SC.w([284, 40], [360, 40]) + SC.M(360, 40, 360, 210, 'V') + SC.w([360, 210], [284, 210]) + SC.t(400, 130, 'V_Th', 'start');
  if (stage === 's3') o += SC.w([284, 40], [360, 40]) + SC.M(360, 40, 360, 210, 'Ω') + SC.w([360, 210], [284, 210]) + SC.t(400, 130, 'R_Th', 'start');
  if (stage === 's4') o += SC.w([284, 40], [360, 40]) + SC.M(360, 40, 360, 210, 'mA') + SC.w([360, 210], [284, 210]) + SC.t(400, 130, 'I_N', 'start');
  return SC.svg(460, 250, o);
}
EXPS.push({
  no: 17, id: 'exp17', group: 'Electricity & CRO', short: 'Thevenin’s and Norton’s theorems',
  title: 'Verification of Thevenin’s theorem and Norton’s theorem',
  aim: 'To verify the network theorems: Thevenin’s theorem and Norton’s theorem.',
  apparatus: ['DC power supply (0–12 V)', 'Network board with resistors R1, R2, R3', 'Resistance box (load R<sub>L</sub>)', 'Milliammeter, voltmeter', 'Multimeter (ohmmeter range)', 'Connecting wires'],
  theory: String.raw`
<h2>Thevenin’s theorem</h2>
<p>Any linear two-terminal network of sources and resistances can be replaced, as seen from its terminals A and B, by a single voltage source \(V_{Th}\) in series with a resistance \(R_{Th}\):</p>
<ul><li>\(V_{Th}\) = open-circuit voltage across A and B (load removed);</li>
<li>\(R_{Th}\) = resistance between A and B with all sources replaced by their internal resistances (an ideal voltage source is replaced by a short circuit).</li></ul>
<p>The load current is then</p>
<div class="eq">\[I_L = \frac{V_{Th}}{R_{Th} + R_L}\]</div>
<h2>Norton’s theorem</h2>
<p>The same network can be replaced by a current source \(I_N\) in parallel with a resistance \(R_N\):</p>
<ul><li>\(I_N\) = current through a short circuit placed across A and B;</li><li>\(R_N = R_{Th}\).</li></ul>
<div class="eq">\[I_L = I_N\,\frac{R_N}{R_N + R_L},\qquad V_{Th} = I_N R_{Th}\]</div>
<h2>The network used</h2>
<p>A supply E feeds R1 in series; R2 is across the line; R3 leads to terminal A (B is the common line). Theoretically</p>
<div class="eq">\[V_{Th} = E\,\frac{R_2}{R_1 + R_2},\qquad R_{Th} = R_3 + \frac{R_1R_2}{R_1+R_2},\qquad I_N = \frac{V_{Th}}{R_{Th}}\]</div>`,
  procedure: String.raw`
<ol class="steps">
<li><b>Load current.</b> Connect the supply to IN+ and IN− of the network board, and the load R<sub>L</sub> with the milliammeter in series across A and B. Set E (e.g. 10 V). Note I<sub>L</sub> for several values of R<sub>L</sub>.</li>
<li><b>V<sub>Th</sub>.</b> Remove the load and milliammeter. Connect the voltmeter across A and B and note the open-circuit voltage V<sub>Th</sub>.</li>
<li><b>R<sub>Th</sub>.</b> Disconnect the supply and join IN+ to IN− with a wire (replace the source by a short). Measure the resistance between A and B with the ohmmeter: this is R<sub>Th</sub>.</li>
<li><b>I<sub>N</sub>.</b> Remove the short and reconnect the supply. Connect only the milliammeter across A and B (short circuit) and note I<sub>N</sub>.</li>
<li>Calculate I<sub>L</sub> = V<sub>Th</sub>/(R<sub>Th</sub> + R<sub>L</sub>) and I<sub>L</sub> = I<sub>N</sub>R<sub>N</sub>/(R<sub>N</sub> + R<sub>L</sub>) for each R<sub>L</sub> and compare with the measured values.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> the four steps are four wiring stages (buttons above the workbench). Each stage has its own circuit diagram; rewire the bench for each step. Remember to set E to the same value in steps 1, 2 and 4.</div>`,
  precautions: String.raw`<li>Never connect the ohmmeter to a circuit that is powered: disconnect the supply before measuring R<sub>Th</sub>.</li><li>Keep E constant in all steps.</li><li>Use the milliammeter in series and the voltmeter in parallel, with correct polarity.</li><li>Measure the short-circuit current quickly; a short circuit draws a large current.</li>`,
  errors: String.raw`<li>Meter resistances (the milliammeter adds a little resistance in the short-circuit test).</li><li>Tolerance of resistors.</li>`,
  viva: [
    ['State Thevenin’s theorem.', 'A linear two-terminal network can be replaced by an equivalent voltage source V_Th (open-circuit voltage) in series with R_Th (resistance seen at the terminals with sources replaced by internal resistances).'],
    ['State Norton’s theorem.', 'A linear two-terminal network can be replaced by a current source I_N (short-circuit current) in parallel with R_N = R_Th.'],
    ['How are Thevenin and Norton equivalents related?', 'R_N = R_Th and V_Th = I_N R_Th; one can be converted into the other (source transformation).'],
    ['How is R_Th measured practically?', 'Replace the voltage source by a short circuit (current sources by open circuits) and measure the resistance across the terminals with an ohmmeter, or use R_Th = V_Th/I_N.'],
    ['Are the theorems valid for non-linear circuits?', 'No, only for linear bilateral networks.'],
    ['What is the use of these theorems?', 'They simplify analysis when the load is changed, because the rest of the network needs to be solved only once.']],
  bench(root) {
    const R = apparatus('exp17');
    const N = { R1: 470 * (1 + R.range(-0.02, 0.02)), R2: 1000 * (1 + R.range(-0.02, 0.02)), R3: 330 * (1 + R.range(-0.02, 0.02)) };
    const rA = 1.5;
    const st = Object.assign({ E: 10, RL: 470 }, LS.get('exp17.st', {}));
    const pg = circuitPage(root);
    const parts = [{ id: 'ps', type: 'supply', label: 'DC supply E', x: 30, y: 60, p: { max: 12 } }, { id: 'nb', type: 'network', label: 'Network board', x: 300, y: 40, p: { R1: '470 Ω', R2: '1 kΩ', R3: '330 Ω' } },
      { id: 'ma', type: 'dmm', label: 'Milliammeter', x: 700, y: 30, p: { unit: 'mA' } }, { id: 'rl', type: 'rbox', label: 'Load R_L', x: 680, y: 280, p: { name: 'R_L' } },
      { id: 'vm', type: 'dmm', label: 'Voltmeter', x: 700, y: 60, p: { unit: 'V' } }, { id: 'om', type: 'dmm', label: 'Ohmmeter (multimeter)', x: 700, y: 60, p: { unit: 'kΩ', mode: 'OHM' } }];
    const sup = [['ps.+', 'nb.IN+'], ['ps.-', 'nb.IN-']];
    const cb = circuitBench(pg.top, {
      key: 'exp17', W: 900, H: 480, parts, stageLabel: 'Step',
      stages: [
        { id: 's1', name: '1 · Load current', parts: ['ps', 'nb', 'ma', 'rl'], spec: { nets: [...sup, ['nb.A', 'ma.+'], ['ma.-', 'rl.1'], ['rl.2', 'nb.B']] }, intro: 'Step 1: connect the supply to the network and the load R_L with the milliammeter across A–B.' },
        { id: 's2', name: '2 · V_Th', parts: ['ps', 'nb', 'vm'], spec: { nets: [...sup, ['nb.A', 'vm.+'], ['nb.B', 'vm.-']] }, intro: 'Step 2: remove the load; connect the voltmeter across A–B to read the open-circuit voltage.' },
        { id: 's3', name: '3 · R_Th', parts: ['nb', 'om'], spec: { nets: [['nb.IN+', 'nb.IN-'], ['nb.A', 'om.+'], ['nb.B', 'om.-']] }, intro: 'Step 3: the supply is removed. Join IN+ to IN− with a wire (source replaced by a short) and connect the ohmmeter across A–B.', onMsg: 'Ohmmeter ON. Record R_Th.' },
        { id: 's4', name: '4 · I_N', parts: ['ps', 'nb', 'ma'], spec: { nets: [...sup, ['nb.A', 'ma.+'], ['ma.-', 'nb.B']] }, intro: 'Step 4: reconnect the supply and short A–B through the milliammeter to read the short-circuit current.' }],
      schematic: id => netSchem(id),
      onPower: () => upd(), onStage: () => upd()
    });
    const par = (a, b) => a * b / (a + b);
    function meas() {
      const E = st.E; const Vth = E * N.R2 / (N.R1 + N.R2), Rth = N.R3 + par(N.R1, N.R2);
      return { IL: Vth / (Rth + st.RL + rA), Vth, Rth, IN: Vth / (Rth + rA) };
    }
    function upd() {
      const m = meas(); LS.set('exp17.st', st);
      cb.show('ps', { v: st.E }); cb.show('rl', { val: st.RL + ' Ω' });
      const s = cb.stage; cb.show('ma', { val: ((s === 's1' ? m.IL : m.IN) * 1e3).toFixed(2) }); cb.show('vm', { val: m.Vth.toFixed(3) }); cb.show('om', { val: (m.Rth / 1000).toFixed(3) });
    }
    const eS = slider({ label: 'Supply voltage E', min: 0, max: 12, step: 0.1, value: st.E, fmt: v => v.toFixed(1) + ' V', fine: [['−0.1', -0.1], ['+0.1', 0.1]], onInput: v => { st.E = v; upd(); } });
    cb.bindKnob('ps', 'v', eS);
    const rlS = stepSelect({ label: 'R_L', values: E_RL, value: st.RL, fmt: fmtOhm, onChange: v => { st.RL = v; upd(); } });
    const sb = statusBox(); const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading for this step');
    pg.instr.append(card('Controls', '', eS.el, rlS.el, h('div', { class: 'recrow' }, btn), sb.el)); upd();
    sb.say('Do the four steps in order. Each step records its own reading.');
    const rec = LS.get('exp17.rec', {}); const saveRec = () => LS.set('exp17.rec', rec);
    btn.addEventListener('click', () => {
      if (!cb.ready(sb.say)) return;
      const m = meas(); const s = cb.stage;
      if (s === 's1') { const row = { RL: st.RL, E: st.E, IL: rnd(m.IL * 1e3, 2) }; const i = tL.rows.findIndex(r => r.RL === st.RL); if (i >= 0) tL.update(i, row); else tL.add(row); sb.say(`I_L = ${row.IL} mA for R_L = ${st.RL} Ω recorded.`, 'ok'); }
      if (s === 's2') { rec.Vth = rnd(m.Vth, 3); rec.E2 = st.E; saveRec(); analyse(); tL.render(); sb.say(`V_Th = ${rec.Vth} V recorded.`, 'ok'); }
      if (s === 's3') { rec.Rth = rnd(m.Rth, 0); saveRec(); analyse(); tL.render(); sb.say(`R_Th = ${rec.Rth} Ω recorded.`, 'ok'); }
      if (s === 's4') { rec.IN = rnd(m.IN * 1e3, 2); rec.E4 = st.E; saveRec(); analyse(); tL.render(); sb.say(`I_N = ${rec.IN} mA recorded.`, 'ok'); }
    });
    const ith = r => rec.Vth != null && rec.Rth != null ? rec.Vth / (rec.Rth + r.RL) * 1e3 : null, inor = r => rec.IN != null && rec.Rth != null ? rec.IN * rec.Rth / (rec.Rth + r.RL) : null;
    const tL = new ObsTable({ key: 'exp17.t', title: 'Table. Load current: measured and from the equivalent circuits', deletable: true, onChange: analyse, sort: (a, b) => a.RL - b.RL, columns: [{ label: 'R<sub>L</sub> (Ω)', k: 'RL' }, { label: 'I<sub>L</sub> measured (mA)', k: 'IL', fmt: v => v.toFixed(2) }, { label: 'I<sub>L</sub> = V<sub>Th</sub>/(R<sub>Th</sub>+R<sub>L</sub>) (mA)', v: ith, fmt: v => v.toFixed(2), calc: 1 }, { label: 'I<sub>L</sub> = I<sub>N</sub>R<sub>N</sub>/(R<sub>N</sub>+R<sub>L</sub>) (mA)', v: inor, fmt: v => v.toFixed(2), calc: 1 }, { label: 'Difference (Thevenin)', v: r => ith(r) == null ? null : pctErr(ith(r), r.IL), fmt: v => v.toFixed(1) + '%', calc: 1 }] });
    const res = resultBox();
    function analyse() {
      const E = st.E, th = { Vth: E * 1000 / 1470, Rth: 330 + par(470, 1000) };
      let html = `<h4>Equivalent circuit</h4><div class="calc-lines"><div>V<sub>Th</sub> (measured) = ${rec.Vth != null ? rec.Vth + ' V' : '—'} &nbsp; (theory with marked values: ${th.Vth.toFixed(2)} V for E = ${E} V)</div><div>R<sub>Th</sub> (measured) = ${rec.Rth != null ? rec.Rth + ' Ω' : '—'} &nbsp; (theory: ${th.Rth.toFixed(0)} Ω)</div><div>I<sub>N</sub> (measured) = ${rec.IN != null ? rec.IN + ' mA' : '—'}</div>${rec.Vth != null && rec.Rth != null && rec.IN != null ? `<div>Check: V<sub>Th</sub>/R<sub>Th</sub> = ${(rec.Vth / rec.Rth * 1e3).toFixed(2)} mA ≈ I<sub>N</sub></div>` : ''}</div>`;
      const rows = tL.rows.filter(r => ith(r) != null && inor(r) != null);
      if (rows.length) { const d = mean(rows.map(r => pctErr(ith(r), r.IL))), d2 = mean(rows.map(r => pctErr(inor(r), r.IL))); html += `<p class="big" style="margin-top:10px">Measured load currents agree with Thevenin’s (mean difference ${d.toFixed(1)}%) and Norton’s (${d2.toFixed(1)}%) equivalent circuits: theorems verified.</p>`; }
      else html += '<p class="small muted" style="margin-top:8px">Complete steps 1–4 to compare the load currents.</p>';
      if ((rec.E2 != null && rec.E2 !== E) || (rec.E4 != null && rec.E4 !== E)) html += '<p class="small" style="color:var(--warn)">E was different in some steps. Keep E the same and repeat those steps.</p>';
      res.set(html);
    }
    pg.notes.append(card('Lab notebook', '', tL.el), card('Result', '', res.el)); analyse();
  }
});

/* ================= experiments 18–20: transistor characteristics ================= */
const VT_ = 0.02585;
function bjtModel(R, pnp) {
  const P = { Is: (pnp ? 3e-14 : 2e-14) * (1 + R.range(-0.3, 0.3)), bf: (pnp ? 170 : 220) * (1 + R.range(-0.18, 0.18)), br: 3, VA: pnp ? 55 : 75 };
  const ex = v => Math.exp(Math.min(v / VT_, 60));
  const iC = (vbe, vbc) => P.Is * (ex(vbe) - ex(vbc)) * (1 + Math.max(0, vbe - vbc) / P.VA) - P.Is / P.br * (ex(vbc) - 1);
  const iB = (vbe, vbc) => P.Is / P.bf * (ex(vbe) - 1) + P.Is / P.br * (ex(vbc) - 1);
  const bis = (f, a, b) => { let fa = f(a); for (let k = 0; k < 70; k++) { const m = (a + b) / 2, fm = f(m); if ((fm > 0) === (fa > 0)) { a = m; fa = fm; } else b = m; } return (a + b) / 2; };
  const RB = 100e3, RE = 1000, rU = 100, rM = 1.0;
  return {
    P,
    CE(VBB, VCC) { let vce = VCC, vbe = 0; for (let k = 0; k < 4; k++) { vbe = bis(v => VBB - v - (RB + rU) * iB(v, v - vce), -0.5, Math.max(0.01, VBB)); vce = VCC - Math.max(0, iC(vbe, vbe - vce)) * rM; } const ib = iB(vbe, vbe - vce), ic = iC(vbe, vbe - vce); return { IB: Math.max(0, ib), IC: Math.max(0, ic), VBE: vbe, VCE: vce, IE: ib + ic }; },
    CB(VEE, VCC) { let vcb = VCC, vbe = 0; for (let k = 0; k < 4; k++) { vbe = bis(v => VEE - v - (RE + rM) * (iB(v, -vcb) + iC(v, -vcb)), -0.5, Math.max(0.01, VEE)); vcb = VCC - Math.max(0, iC(vbe, -vcb)) * rM; } const ib = iB(vbe, -vcb), ic = iC(vbe, -vcb); return { IE: Math.max(0, ib + ic), IC: Math.max(0, ic), VEB: vbe, VCB: vcb }; },
    CC(VBB, VCC) { const r = this.CE(VBB, VCC); return { IB: r.IB, IE: Math.max(0, r.IE), VCB: r.VCE - r.VBE, VCE: r.VCE, VBE: r.VBE }; }
  };
}
/* ----- schematics ----- */
function bjtSchem(cfg, pnp) {
  const n = !pnp; let o = '';
  if (cfg === 'CE') {
    o += SC.B(...(n ? [40, 80, 40, 220] : [40, 220, 40, 80]), 'V_BB', true) + SC.w([40, 80], [40, 60]) + SC.w([40, 220], [40, 250]);
    o += SC.R(40, 60, 140, 60, 'R_B') + SC.M(140, 60, 230, 60, 'µA', n) + SC.w([230, 60], [262, 60], [262, 150]);
    const q = SC.Q(262, 150, pnp, pnp ? 'BC557' : 'BC547'); o += q.svg;
    o += SC.w([262, 150], [215, 150]) + SC.M(215, 150, 215, 250, 'V', n) + SC.t(196, 204, 'V_BE', 'end', 9) + SC.dot(262, 150);
    o += SC.w(q.E, [q.E[0], 250]) + SC.w(q.C, [q.C[0], 60]) + SC.M(q.C[0], 60, 430, 60, 'mA', !n) + SC.w([430, 60], [480, 60]);
    o += SC.B(...(n ? [480, 80, 480, 220] : [480, 220, 480, 80]), '', true) + SC.t(504, 154, 'V_CC', 'start') + SC.w([480, 60], [480, 80]) + SC.w([480, 220], [480, 250]);
    o += SC.w([q.C[0], 96], [380, 96]) + SC.M(380, 96, 380, 250, 'V', n) + SC.t(398, 180, 'V_CE', 'start', 9) + SC.dot(q.C[0], 96);
    o += SC.w([40, 250], [480, 250]) + SC.dot(215, 250) + SC.dot(q.E[0], 250) + SC.dot(380, 250);
    return SC.svg(560, 280, o);
  }
  if (cfg === 'CB') {
    o += SC.B(...(n ? [40, 230, 40, 90] : [40, 90, 40, 230]), 'V_EE', true) + SC.w([40, 90], [40, 60]) + SC.w([40, 230], [40, 270]);
    o += SC.R(40, 60, 140, 60, 'R_E') + SC.M(140, 60, 230, 60, 'mA', !n) + SC.t(185, 34, 'I_E', 'middle', 9);
    const q = SC.Q(282, 150, pnp, pnp ? 'BC557' : 'BC547', true); o += q.svg;
    o += SC.w([230, 60], [q.E[0], 60], q.E) + SC.dot(250, 60) + SC.M(250, 60, 250, 270, 'V', !n) + SC.t(232, 200, 'V_EB', 'end', 9);
    o += SC.w([282, 150], [270, 150], [270, 270]) + SC.dot(270, 270);
    o += SC.w(q.C, [q.C[0], 210], [360, 210]) + SC.M(360, 210, 440, 210, 'mA', !n) + SC.t(400, 190, 'I_C', 'middle', 9) + SC.w([440, 210], [480, 210]);
    o += SC.B(...(n ? [480, 210, 480, 270] : [480, 270, 480, 210]), '', false) + SC.t(500, 244, 'V_CC', 'start');
    o += SC.dot(q.C[0], 210) + SC.M(q.C[0], 210, q.C[0], 270, 'V', n) + SC.t(q.C[0] - 18, 254, 'V_CB', 'end', 9);
    o += SC.w([40, 270], [480, 270]) + SC.dot(250, 270) + SC.dot(q.C[0], 270);
    return SC.svg(560, 300, o);
  }
  // CC
  o += SC.B(...(n ? [40, 140, 40, 270] : [40, 270, 40, 140]), 'V_BB', true) + SC.w([40, 140], [40, 120]) + SC.w([40, 270], [40, 300]);
  o += SC.R(40, 120, 130, 120, 'R_B') + SC.M(130, 120, 215, 120, 'µA', n) + SC.w([215, 120], [262, 120], [262, 160]);
  const q = SC.Q(262, 160, pnp, pnp ? 'BC557' : 'BC547'); o += q.svg;
  o += SC.w(q.C, [q.C[0], 40], [480, 40]) + SC.M(240, 40, 240, 120, 'V', n) + SC.w([240, 40], [q.C[0], 40]) + SC.t(222, 84, 'V_CB', 'end', 9) + SC.dot(240, 120) + SC.dot(q.C[0], 40);
  o += SC.w(q.E, [q.E[0], 210]) + SC.M(q.E[0], 210, q.E[0], 300, 'mA', n) + SC.t(q.E[0] + 20, 262, 'I_E', 'start', 9);
  o += SC.w([q.E[0], 200], [380, 200]) + SC.dot(q.E[0], 200) + SC.M(380, 40, 380, 200, 'V', n) + SC.t(398, 124, 'V_CE', 'start', 9) + SC.dot(380, 40);
  o += SC.B(...(n ? [480, 60, 480, 280] : [480, 280, 480, 60]), '', true) + SC.t(502, 170, 'V_CC', 'start') + SC.w([480, 40], [480, 60]) + SC.w([480, 280], [480, 300]);
  o += SC.w([40, 300], [480, 300]) + SC.dot(q.E[0], 300);
  return SC.svg(560, 320, o);
}
/* ----- nets ----- */
function bjtNets(cfg, pnp) {
  const q = pnp ? 'qp' : 'qn'; const P = pnp ? ['-', '+'] : ['+', '-']; const [a, b] = P; // a = '+' for NPN
  if (cfg === 'CE') return [[`vbb.${a}`, 'rb.1'], ['rb.2', `ua.${a}`], [`ua.${b}`, `${q}.B`, `v1.${a}`], [`${q}.E`, `vbb.${b}`, `vcc.${b}`, `v1.${b}`, `v2.${b}`], [`${q}.C`, `ma.${b}`, `v2.${a}`], [`ma.${a}`, `vcc.${a}`]];
  if (cfg === 'CB') return [[`vee.${b}`, 're.1'], ['re.2', `mae.${b}`], [`mae.${a}`, `${q}.E`, `v1.${b}`], [`vee.${a}`, `${q}.B`, `vcc.${b}`, `v1.${a}`, `v2.${b}`], [`${q}.C`, `mac.${b}`, `v2.${a}`], [`mac.${a}`, `vcc.${a}`]];
  return [[`vbb.${a}`, 'rb.1'], ['rb.2', `ua.${a}`], [`ua.${b}`, `${q}.B`, `v1.${b}`], [`${q}.C`, `vcc.${a}`, `v1.${a}`, `v2.${a}`], [`${q}.E`, `mae.${a}`, `v2.${b}`], [`mae.${b}`, `vbb.${b}`, `vcc.${b}`]];
}
const BJT_INFO = {
  CE: { inName: 'V_BE', inCur: 'I_B', outName: 'V_CE', outCur: 'I_C', inUnit: 'µA', outUnit: 'mA', s1: 'vbb', s2: 'vcc' },
  CB: { inName: 'V_EB', inCur: 'I_E', outName: 'V_CB', outCur: 'I_C', inUnit: 'mA', outUnit: 'mA', s1: 'vee', s2: 'vcc' },
  CC: { inName: 'V_CB', inCur: 'I_B', outName: 'V_CE', outCur: 'I_E', inUnit: 'µA', outUnit: 'mA', s1: 'vbb', s2: 'vcc' }
};
function bjtBench(root, cfg, id) {
  const R = apparatus(id); const models = { npn: bjtModel(R, false), pnp: bjtModel(R, true) };
  const I = BJT_INFO[cfg];
  const st = Object.assign({ s1: 0, s2: 0, mode: 'in' }, LS.get(id + '.st', {}));
  const pg = circuitPage(root);
  const lay = { s1: [10, 40], r: [232, 26], m1: [250, 110], q: [420, 230], m2: [560, 40], s2: [690, 200], v1: [270, 400], v2: [560, 400] };
  const parts = [
    { id: I.s1, type: 'supply', label: cfg === 'CB' ? 'V_EE (emitter supply)' : 'V_BB (base supply)', x: lay.s1[0], y: lay.s1[1], p: { max: cfg === 'CB' ? 5 : 10, name: cfg === 'CB' ? 'VEE' : 'VBB', dp: cfg === 'CB' ? 3 : 2 } },
    { id: 'vcc', type: 'supply', label: 'V_CC (collector supply)', x: lay.s2[0], y: lay.s2[1], p: { max: 20, name: 'VCC' } },
    cfg === 'CB' ? { id: 're', type: 'res', label: 'R_E', x: lay.r[0], y: lay.r[1], p: { ohm: 1000, text: '1 kΩ' } } : { id: 'rb', type: 'res', label: 'R_B', x: lay.r[0], y: lay.r[1], p: { ohm: 100000, text: '100 kΩ' } },
    cfg === 'CB' ? { id: 'mae', type: 'dmm', label: 'Milliammeter (I_E)', x: lay.m1[0], y: lay.m1[1], p: { unit: 'mA' } } : { id: 'ua', type: 'dmm', label: 'Microammeter (I_B)', x: lay.m1[0], y: lay.m1[1], p: { unit: 'µA' } },
    cfg === 'CC' ? { id: 'mae', type: 'dmm', label: 'Milliammeter (I_E)', x: lay.m2[0], y: lay.m2[1], p: { unit: 'mA' } } : { id: cfg === 'CB' ? 'mac' : 'ma', type: 'dmm', label: 'Milliammeter (I_C)', x: lay.m2[0], y: lay.m2[1], p: { unit: 'mA' } },
    { id: 'qn', type: 'bjt', label: 'Transistor', x: lay.q[0], y: lay.q[1], p: { part: 'BC547', pnp: false } },
    { id: 'qp', type: 'bjt', label: 'Transistor', x: lay.q[0], y: lay.q[1], p: { part: 'BC557', pnp: true } },
    { id: 'v1', type: 'dmm', label: `Voltmeter (${I.inName})`, x: lay.v1[0], y: lay.v1[1], p: { unit: 'V' } },
    { id: 'v2', type: 'dmm', label: `Voltmeter (${I.outName})`, x: lay.v2[0], y: lay.v2[1], p: { unit: 'V' } }];
  const common = parts.filter(p => !/^q[np]$/.test(p.id)).map(p => p.id);
  const cb = circuitBench(pg.top, {
    key: id, W: 900, H: 560, parts, stageLabel: 'Transistor',
    stages: [
      { id: 'npn', name: 'NPN (BC547)', parts: [...common, 'qn'], spec: { nets: bjtNets(cfg, false) }, intro: 'NPN transistor: wire the circuit as in the diagram. The legs of BC547 are C, B, E (flat face towards you).' },
      { id: 'pnp', name: 'PNP (BC557)', parts: [...common, 'qp'], spec: { nets: bjtNets(cfg, true) }, intro: 'PNP transistor: all supply and meter polarities are reversed compared with NPN. Wire it as in the diagram.' }],
    schematic: s => bjtSchem(cfg, s === 'pnp'),
    onPower: () => upd(), onStage: () => { buildTables(); upd(); analyse(); }
  });
  const meas = () => { const m = models[cb.stage]; return cfg === 'CB' ? m.CB(st.s1, st.s2) : cfg === 'CE' ? m.CE(st.s1, st.s2) : m.CC(st.s1, st.s2); };
  const rd = () => { const m = meas(); if (cfg === 'CE') return { vin: m.VBE, iin: m.IB * 1e6, vout: m.VCE, iout: m.IC * 1e3 }; if (cfg === 'CB') return { vin: m.VEB, iin: m.IE * 1e3, vout: m.VCB, iout: m.IC * 1e3 }; return { vin: m.VCB, iin: m.IB * 1e6, vout: m.VCE, iout: m.IE * 1e3 }; };
  function upd() {
    LS.set(id + '.st', st); cb.show(I.s1, { v: st.s1 }); cb.show('vcc', { v: st.s2 });
    const r = rd(); const inM = cfg === 'CB' ? 'mae' : 'ua', outM = cfg === 'CC' ? 'mae' : cfg === 'CB' ? 'mac' : 'ma';
    cb.show(inM, { val: I.inUnit === 'µA' ? r.iin.toFixed(1) : r.iin.toFixed(3) }); if (outM !== inM) cb.show(outM, { val: r.iout.toFixed(cfg === 'CB' ? 3 : 2) });
    cb.show('v1', { val: r.vin.toFixed(3) }); cb.show('v2', { val: r.vout.toFixed(2) });
  }
  const s1S = slider({ label: cfg === 'CB' ? 'V_EE (emitter supply)' : 'V_BB (base supply)', min: 0, max: cfg === 'CB' ? 5 : 10, step: cfg === 'CB' ? 0.005 : 0.01, value: st.s1, fmt: v => v.toFixed(cfg === 'CB' ? 3 : 2) + ' V', fine: cfg === 'CB' ? [['−0.1', -0.1], ['−0.005', -0.005], ['+0.005', 0.005], ['+0.1', 0.1]] : [['−0.5', -0.5], ['−0.02', -0.02], ['+0.02', 0.02], ['+0.5', 0.5]], onInput: v => { st.s1 = v; upd(); } });
  const s2S = slider({ label: 'V_CC (collector supply)', min: 0, max: 20, step: 0.05, value: st.s2, fmt: v => v.toFixed(2) + ' V', fine: [['−1', -1], ['−0.1', -0.1], ['+0.1', 0.1], ['+1', 1]], onInput: v => { st.s2 = v; upd(); } });
  cb.bindKnob(I.s1, 'v', s1S); cb.bindKnob('vcc', 'v', s2S);
  const modeSeg = seg({ label: 'Recording', value: st.mode, options: [['in', 'Input characteristic'], ['out', 'Output characteristic']], onChange: v => { st.mode = v; LS.set(id + '.st', st); guide(); } });
  const sb = statusBox(); const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading');
  const guideP = h('p', { class: 'howto' });
  pg.instr.append(card('Controls', 'supplies and what you are recording', modeSeg.el, guideP, s1S.el, s2S.el, h('div', { class: 'recrow' }, btn), sb.el));
  const G = {
    CE: { in: 'Keep V_CE constant (set V_CC, e.g. 0 V, then 2 V, then 5 V). Increase V_BB in small steps and record V_BE and I_B.', out: 'Set I_B (e.g. 20 µA) with V_BB. Increase V_CC in steps and record V_CE and I_C. Re-adjust V_BB to keep I_B constant. Repeat for 40 µA and 60 µA.' },
    CB: { in: 'Keep V_CB constant (0 V, 5 V, 10 V with V_CC). Increase V_EE slowly and record V_EB and I_E.', out: 'Set I_E (e.g. 1 mA) with V_EE. Increase V_CC and record V_CB and I_C, keeping I_E constant. Repeat for 2 mA and 3 mA.' },
    CC: { in: 'Keep V_CE constant (e.g. 3 V, then 6 V). Change V_BB and record V_CB and I_B.', out: 'Set I_B (e.g. 20 µA). Increase V_CC and record V_CE and I_E, keeping I_B constant. Repeat for 40 µA and 60 µA.' }
  };
  function guide() { guideP.textContent = G[cfg][st.mode]; }
  guide(); upd(); sb.say('Wire the circuit, check it and switch ON.');
  const key = () => id + '.' + cb.stage;
  let tIn, tOut; const tHost = h('div', { class: 'stack' });
  function grpIn(r) { return cfg === 'CB' ? Math.round(r.vout * 2) / 2 : Math.round(r.vout * 2) / 2; }
  function grpOut(r) { return cfg === 'CB' ? Math.round(r.iin * 2) / 2 : Math.round(r.iin / 5) * 5; }
  btn.addEventListener('click', () => {
    if (!cb.ready(sb.say)) return;
    const r = rd(); const row = { vin: rnd(r.vin, 3), iin: rnd(r.iin, cfg === 'CB' ? 3 : 1), vout: rnd(r.vout, 2), iout: rnd(r.iout, cfg === 'CB' ? 3 : 2) };
    if (st.mode === 'in') {
      const g = grpIn(row); const same = tIn.rows.filter(x => grpIn(x) === g);
      tIn.add(row); sb.say(`Input reading: ${I.inName} = ${row.vin} V, ${I.inCur} = ${row.iin} ${I.inUnit} at ${I.outName} ≈ ${g} V.`, 'ok');
    } else {
      const g = grpOut(row); const same = tOut.rows.filter(x => grpOut(x) === g);
      if (same.length && Math.abs(same[0].iin - row.iin) > (cfg === 'CB' ? 0.05 : 2)) sb.say(`${I.inCur} has drifted to ${row.iin} ${I.inUnit}. Re-adjust ${cfg === 'CB' ? 'V_EE' : 'V_BB'} to keep it constant before recording.`, 'warn');
      tOut.add(row); if (!(same.length && Math.abs(same[0].iin - row.iin) > (cfg === 'CB' ? 0.05 : 2))) sb.say(`Output reading: ${I.outName} = ${row.vout} V, ${I.outCur} = ${row.iout} mA at ${I.inCur} ≈ ${g} ${I.inUnit}.`, 'ok');
    }
  });
  const inPlot = plotCard('Input characteristics', { get: () => { const gs = groupBy(tIn ? tIn.rows : [], grpIn); return { xLabel: `${I.inName} (V)`, yLabel: `${I.inCur} (${I.inUnit})`, series: gs.map(g => ({ pts: g.rows.map(r => [r.vin, r.iin]), join: true, label: `${I.outName} = ${g.k} V` })), zeroY: true }; } });
  const outPlot = plotCard('Output characteristics', { get: () => { const gs = groupBy(tOut ? tOut.rows : [], grpOut); return { xLabel: `${I.outName} (V)`, yLabel: `${I.outCur} (mA)`, series: gs.map(g => ({ pts: g.rows.map(r => [r.vout, r.iout]), join: true, label: `${I.inCur} = ${g.k} ${I.inUnit}` })), zeroX: true, zeroY: true }; } });
  function groupBy(rows, f) { const m = {}; rows.forEach(r => { const k = f(r); (m[k] = m[k] || []).push(r); }); return Object.entries(m).map(([k, rs]) => ({ k: +k, rows: rs.slice() })).sort((a, b) => a.k - b.k); }
  function buildTables() {
    tIn = new ObsTable({ key: key() + '.in', title: `Table 1. Input characteristics (${cb.stage.toUpperCase()})`, deletable: true, onChange: analyse, sort: (a, b) => grpIn(a) - grpIn(b) || a.vin - b.vin, columns: [{ label: `${I.outName} (V)`, k: 'vout', fmt: v => v.toFixed(2) }, { label: `${I.inName} (V)`, k: 'vin', fmt: v => v.toFixed(3) }, { label: `${I.inCur} (${I.inUnit})`, k: 'iin', fmt: v => cfg === 'CB' ? v.toFixed(3) : v.toFixed(1) }] });
    tOut = new ObsTable({ key: key() + '.out', title: `Table 2. Output characteristics (${cb.stage.toUpperCase()})`, deletable: true, onChange: analyse, sort: (a, b) => grpOut(a) - grpOut(b) || a.vout - b.vout, columns: [{ label: `${I.inCur} (${I.inUnit})`, k: 'iin', fmt: v => cfg === 'CB' ? v.toFixed(3) : v.toFixed(1) }, { label: `${I.outName} (V)`, k: 'vout', fmt: v => v.toFixed(2) }, { label: `${I.outCur} (mA)`, k: 'iout', fmt: v => v.toFixed(cfg === 'CB' ? 3 : 2) }] });
    tHost.innerHTML = ''; tHost.append(tIn.el, tOut.el);
  }
  const res = resultBox();
  function interpAt(rows, x, kx, ky) { const p = rows.map(r => [r[kx], r[ky]]).sort((a, b) => a[0] - b[0]); for (let i = 1; i < p.length; i++) if (p[i - 1][0] <= x && x <= p[i][0] && p[i][0] > p[i - 1][0]) return p[i - 1][1] + (p[i][1] - p[i - 1][1]) * (x - p[i - 1][0]) / (p[i][0] - p[i - 1][0]); return null; }
  function analyse() {
    if (!tIn) return; inPlot.view.redraw(); outPlot.view.redraw();
    let html = '<h4>Calculation</h4><div class="calc-lines">'; let any = false;
    // input resistance
    const gi = groupBy(tIn.rows, grpIn).filter(g => g.rows.length >= 3);
    if (gi.length) { const g = gi[gi.length - 1]; const rows = g.rows.slice().sort((a, b) => a.iin - b.iin); const mx = rows[rows.length - 1].iin; let top = rows.filter(r => r.iin >= 0.15 * mx && r.iin <= 0.7 * mx); if (top.length < 2) top = rows.slice(-2); if (top.length >= 2) { const f = linreg(top.map(r => [r.iin, r.vin])); const ri = Math.abs(f.m) * (I.inUnit === 'µA' ? 1e6 : 1e3); html += `<div>Input resistance r<sub>i</sub> = Δ${I.inName}/Δ${I.inCur} (at ${I.outName} = ${g.k} V) = <b>${ri >= 1000 ? (ri / 1000).toFixed(2) + ' kΩ' : ri.toFixed(1) + ' Ω'}</b></div>`; any = true; } }
    const go = groupBy(tOut.rows, grpOut).filter(g => g.rows.length >= 3);
    if (go.length) {
      const g = go[go.length - 1]; const act = g.rows.filter(r => r.vout >= (cfg === 'CB' ? 0.5 : 1.5));
      if (act.length >= 2) { const f = linreg(act.map(r => [r.vout, r.iout])); const ro = f.m > 0 ? 1 / f.m : Infinity; html += `<div>Output resistance r<sub>o</sub> = Δ${I.outName}/Δ${I.outCur} (at ${I.inCur} = ${g.k} ${I.inUnit}) = <b>${isFinite(ro) ? (ro > 1000 ? (ro / 1000).toFixed(2) + ' MΩ' : ro.toFixed(1) + ' kΩ') : 'very large'}</b></div>`; any = true; }
      if (go.length >= 2) {
        const V0 = cfg === 'CB' ? 5 : 5; const a = go[0], b = go[go.length - 1];
        const ia = interpAt(a.rows, V0, 'vout', 'iout'), ib = interpAt(b.rows, V0, 'vout', 'iout'); const xa = mean(a.rows.map(r => r.iin)), xb = mean(b.rows.map(r => r.iin));
        if (ia != null && ib != null && xb !== xa) {
          const gain = (ib - ia) / ((xb - xa) * (I.inUnit === 'µA' ? 1e-3 : 1));
          const sym = cfg === 'CB' ? 'α' : cfg === 'CE' ? 'β' : 'γ';
          html += `<div>Current gain ${sym} = Δ${I.outCur}/Δ${I.inCur} at ${I.outName} = ${V0} V = (${ib.toFixed(cfg === 'CB' ? 3 : 2)} − ${ia.toFixed(cfg === 'CB' ? 3 : 2)}) mA / (${xb.toFixed(cfg === 'CB' ? 2 : 0)} − ${xa.toFixed(cfg === 'CB' ? 2 : 0)}) ${I.inUnit} = <b>${gain.toFixed(cfg === 'CB' ? 3 : 0)}</b></div>`;
          if (cfg === 'CB') html += `<div>β = α/(1 − α) = ${(gain / (1 - gain)).toFixed(0)}</div>`;
          if (cfg === 'CE') html += `<div>α = β/(1 + β) = ${(gain / (1 + gain)).toFixed(4)}</div>`;
          any = true;
        } else html += `<div>For the current gain, record output curves for two ${I.inCur} values that both include ${I.outName} = ${V0} V.</div>`;
      }
    }
    html += '</div>';
    if (!any) html = '<h4>Calculation</h4><p class="small muted">Record an input curve (8–10 readings) and output curves for two or three values of ' + I.inCur + ' (8–10 readings each).</p>';
    res.set(html);
  }
  buildTables();
  pg.notes.append(card('Lab notebook', 'NPN and PNP keep separate tables', tHost), inPlot.el, outPlot.el, card('Result', '', res.el)); analyse();
}
const BJT_COMMON_PRECAUTIONS = String.raw`<li>Check the transistor leads (C, B, E) before connecting; for BC547/BC557 with the flat face towards you they are C, B, E from left to right.</li><li>Observe the polarities: for NPN the collector and base are positive relative to the emitter; for PNP all polarities are reversed.</li><li>Start with both supplies at zero and increase them slowly. Do not exceed the maximum ratings (I<sub>C</sub> ≈ 100 mA, V<sub>CE</sub> ≈ 45 V for BC547).</li><li>Keep the input (or output) quantity constant while taking a set of readings.</li><li>Switch off the supplies between sets of readings to avoid heating of the transistor.</li>`;

/* ======================== EXP 18: CB ======================== */
EXPS.push({
  no: 18, id: 'exp18', group: 'Electronics', short: 'Transistor CB characteristics',
  title: 'Common-base (CB) characteristics of PNP and NPN transistors',
  aim: 'To study the CB characteristics of a PNP and NPN junction transistor.',
  apparatus: ['NPN transistor (BC547) and PNP transistor (BC557)', 'Two variable DC supplies (V<sub>EE</sub> 0–5 V, V<sub>CC</sub> 0–20 V)', 'Emitter resistance R<sub>E</sub> = 1 kΩ', 'Two milliammeters (I<sub>E</sub>, I<sub>C</sub>)', 'Two voltmeters (V<sub>EB</sub>, V<sub>CB</sub>)', 'Connecting wires'],
  theory: String.raw`
<h2>Common-base configuration</h2>
<p>In the CB configuration the base is common to the input (emitter–base) and the output (collector–base) circuits. For normal (active) operation the emitter–base junction is forward biased and the collector–base junction is reverse biased.</p>
<h3>Input characteristics</h3>
<p>Graph of emitter current \(I_E\) against emitter–base voltage \(V_{EB}\) at constant collector–base voltage \(V_{CB}\). It is like the forward characteristic of a diode: \(I_E\) is negligible until \(V_{EB}\) ≈ 0.6 V and then rises steeply. Increasing \(V_{CB}\) shifts the curve slightly to the left (Early effect: the base width decreases).</p>
<div class="eq">\[\text{Input resistance } r_i = \left(\frac{\Delta V_{EB}}{\Delta I_E}\right)_{V_{CB}}\quad(\text{a few tens of ohms})\]</div>
<h3>Output characteristics</h3>
<p>Graph of collector current \(I_C\) against \(V_{CB}\) at constant \(I_E\). In the active region \(I_C\) is almost equal to \(I_E\) and nearly independent of \(V_{CB}\); even at \(V_{CB}\) = 0 the collector collects almost all the carriers. \(I_C\) falls to zero only when the collector junction is forward biased.</p>
<div class="eq">\[r_o = \left(\frac{\Delta V_{CB}}{\Delta I_C}\right)_{I_E}\ (\text{very large}),\qquad \alpha = \left(\frac{\Delta I_C}{\Delta I_E}\right)_{V_{CB}}\ (0.95\text{–}0.995)\]</div>
<p>The current amplification factor \(\alpha\) is slightly less than 1. It is related to the CE gain by \(\beta = \alpha/(1-\alpha)\).</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Identify E, B and C of the transistor. Connect the circuit as in the diagram (NPN first). Keep both supplies at zero.</li>
<li><b>Input characteristics:</b> set V<sub>CB</sub> = 0 V (V<sub>CC</sub> = 0). Increase V<sub>EE</sub> in small steps and note V<sub>EB</sub> and I<sub>E</sub>. Repeat with V<sub>CB</sub> = 5 V and 10 V.</li>
<li><b>Output characteristics:</b> set I<sub>E</sub> = 1 mA with V<sub>EE</sub>. Increase V<sub>CC</sub> in steps and note V<sub>CB</sub> and I<sub>C</sub>, keeping I<sub>E</sub> constant. Repeat for I<sub>E</sub> = 2 mA and 3 mA.</li>
<li>Repeat the whole experiment with the PNP transistor, reversing all supply and meter polarities.</li>
<li>Plot the characteristics and find r<sub>i</sub>, r<sub>o</sub> and α.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> choose NPN or PNP above the workbench. Each type has its own wiring and tables. The Record button adds the present meter readings to the input or output table, according to the choice under Controls.</div>`,
  precautions: BJT_COMMON_PRECAUTIONS,
  errors: String.raw`<li>Variation of I<sub>E</sub> while recording the output curve.</li><li>Heating of the transistor changes the characteristics.</li><li>Meter loading.</li>`,
  viva: [
    ['Why is the emitter–base junction forward biased and the collector–base junction reverse biased?', 'For active-region operation: the forward-biased emitter injects carriers into the base, and the reverse-biased collector collects them.'],
    ['Why is α less than one?', 'A few carriers recombine in the base, so I_C is slightly less than I_E.'],
    ['Why is the output resistance in CB very high?', 'I_C hardly changes with V_CB because the reverse-biased collector collects nearly all the carriers whatever the voltage.'],
    ['Why does I_C flow even when V_CB = 0?', 'The built-in field of the collector–base junction sweeps the carriers that reach it into the collector.'],
    ['What is the Early effect?', 'The decrease of the effective base width as the collector reverse bias increases (base-width modulation); it slightly increases α and the output current.'],
    ['Where is the CB configuration used?', 'In high-frequency amplifiers and as a current buffer, because of its low input resistance and high output resistance.'],
    ['Relation between α and β?', 'β = α/(1 − α), α = β/(1 + β).']],
  bench(root) { bjtBench(root, 'CB', 'exp18'); }
});
/* ======================== EXP 19: CE ======================== */
EXPS.push({
  no: 19, id: 'exp19', group: 'Electronics', short: 'Transistor CE characteristics',
  title: 'Common-emitter (CE) characteristics of PNP and NPN transistors',
  aim: 'To study the CE characteristics of a PNP and NPN junction transistor.',
  apparatus: ['NPN transistor (BC547) and PNP transistor (BC557)', 'Two variable DC supplies (V<sub>BB</sub> 0–10 V, V<sub>CC</sub> 0–20 V)', 'Base resistance R<sub>B</sub> = 100 kΩ', 'Microammeter (I<sub>B</sub>), milliammeter (I<sub>C</sub>)', 'Two voltmeters (V<sub>BE</sub>, V<sub>CE</sub>)', 'Connecting wires'],
  theory: String.raw`
<h2>Common-emitter configuration</h2>
<p>The emitter is common to the input (base–emitter) and output (collector–emitter) circuits. This is the most widely used configuration because it gives both current and voltage gain.</p>
<h3>Input characteristics</h3>
<p>Graph of base current \(I_B\) (µA) against \(V_{BE}\) at constant \(V_{CE}\). It resembles a diode curve with a knee at about 0.6–0.7 V for silicon. With \(V_{CE}\) = 0 both junctions are forward biased and \(I_B\) is larger; as \(V_{CE}\) increases the curve shifts to the right.</p>
<div class="eq">\[r_i = \left(\frac{\Delta V_{BE}}{\Delta I_B}\right)_{V_{CE}}\quad(\text{about 0.5–5 k}\Omega)\]</div>
<h3>Output characteristics</h3>
<p>Graph of collector current \(I_C\) (mA) against \(V_{CE}\) at constant \(I_B\). \(I_C\) rises rapidly for \(V_{CE}\) below about 0.3–1 V (<b>saturation region</b>), then becomes nearly constant, increasing only slowly with \(V_{CE}\) (<b>active region</b>). Below the curve for \(I_B\) = 0 is the <b>cut-off region</b>.</p>
<div class="eq">\[r_o = \left(\frac{\Delta V_{CE}}{\Delta I_C}\right)_{I_B},\qquad \beta = \left(\frac{\Delta I_C}{\Delta I_B}\right)_{V_{CE}}\ (\text{about 100–400 for BC547})\]</div>
<p>Also \(\beta_{dc} = I_C/I_B\) and \(\alpha = \beta/(1+\beta)\).</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Identify the leads and connect the circuit as in the diagram (NPN first), with both supplies at zero.</li>
<li><b>Input characteristics:</b> keep V<sub>CE</sub> = 0 V. Increase V<sub>BB</sub> in small steps and note V<sub>BE</sub> and I<sub>B</sub>. Repeat with V<sub>CE</sub> = 2 V and 5 V (set with V<sub>CC</sub>).</li>
<li><b>Output characteristics:</b> set I<sub>B</sub> = 20 µA. Increase V<sub>CC</sub> from zero in small steps (0.1–0.2 V near the knee, larger later) and note V<sub>CE</sub> and I<sub>C</sub>. Keep I<sub>B</sub> constant by adjusting V<sub>BB</sub>. Repeat for I<sub>B</sub> = 40 µA and 60 µA.</li>
<li>Repeat with the PNP transistor (all polarities reversed).</li>
<li>Plot the characteristics. Find r<sub>i</sub>, r<sub>o</sub> and β (at V<sub>CE</sub> = 5 V).</li></ol>
<div class="vnote"><b>On the virtual bench:</b> choose NPN or PNP above the workbench. When recording the output curves, watch the microammeter: I<sub>B</sub> changes slightly as V<sub>CE</sub> changes, just as in a real transistor; correct it with V<sub>BB</sub>.</div>`,
  precautions: BJT_COMMON_PRECAUTIONS,
  errors: String.raw`<li>I<sub>B</sub> not held exactly constant for the output curves.</li><li>Heating of the transistor (β increases with temperature).</li><li>Meter resistances.</li>`,
  viva: [
    ['Why is CE the most popular configuration?', 'It gives high current gain (β), high voltage gain and high power gain, with moderate input and output resistances.'],
    ['What are the three regions of the output characteristics?', 'Saturation (both junctions forward biased), active (EB forward, CB reverse), and cut-off (both reverse biased).'],
    ['Why does I_C increase slightly with V_CE in the active region?', 'Because of the Early effect (base-width modulation).'],
    ['Why does the input curve shift to the right when V_CE increases?', 'With larger V_CE, the base width decreases, so fewer carriers recombine in the base and I_B is smaller for the same V_BE.'],
    ['What is the relation between β and α?', 'β = α/(1 − α).'],
    ['What is the knee voltage of silicon?', 'About 0.6–0.7 V.'],
    ['Why is a high resistance R_B used in the base circuit?', 'To limit the base current to microamperes and to make it easy to set.']],
  bench(root) { bjtBench(root, 'CE', 'exp19'); }
});
/* ======================== EXP 20: CC ======================== */
EXPS.push({
  no: 20, id: 'exp20', group: 'Electronics', short: 'Transistor CC characteristics',
  title: 'Common-collector (CC) characteristics of PNP and NPN transistors',
  aim: 'To study the CC characteristics of a PNP and NPN junction transistor.',
  apparatus: ['NPN transistor (BC547) and PNP transistor (BC557)', 'Two variable DC supplies (V<sub>BB</sub> 0–10 V, V<sub>CC</sub> 0–20 V)', 'Base resistance R<sub>B</sub> = 100 kΩ', 'Microammeter (I<sub>B</sub>), milliammeter (I<sub>E</sub>)', 'Two voltmeters (V<sub>CB</sub>, V<sub>CE</sub>)', 'Connecting wires'],
  theory: String.raw`
<h2>Common-collector configuration</h2>
<p>The collector is common to the input (base–collector) and output (emitter–collector) circuits. The output is taken from the emitter, so this circuit is also called an <b>emitter follower</b>: its voltage gain is about one, but it has a high input resistance and a low output resistance (used as a buffer).</p>
<h3>Input characteristics</h3>
<p>Graph of base current \(I_B\) against the collector–base voltage \(V_{CB}\) at constant \(V_{CE}\). Since \(V_{CB} = V_{CE} - V_{BE}\), the curve is very different from CE: as \(I_B\) increases, \(V_{BE}\) increases slightly and \(V_{CB}\) decreases. \(I_B\) falls sharply as \(V_{CB}\) approaches \(V_{CE} - 0.6\) V.</p>
<h3>Output characteristics</h3>
<p>Graph of emitter current \(I_E\) against \(V_{CE}\) at constant \(I_B\). They are almost the same as the CE output characteristics because \(I_E = I_C + I_B \approx I_C\).</p>
<div class="eq">\[\gamma = \left(\frac{\Delta I_E}{\Delta I_B}\right)_{V_{CE}} = 1+\beta,\qquad r_o = \left(\frac{\Delta V_{CE}}{\Delta I_E}\right)_{I_B}\]</div>
<p>In this experiment the collector is connected to the positive of V<sub>CC</sub> (common point of the output); the input voltage is measured between collector and base, and the output voltage between collector and emitter.</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Connect the circuit as in the diagram (NPN first), with both supplies at zero.</li>
<li><b>Input characteristics:</b> set V<sub>CE</sub> = 3 V with V<sub>CC</sub>. Vary V<sub>BB</sub> in steps and note V<sub>CB</sub> and I<sub>B</sub>. Repeat with V<sub>CE</sub> = 6 V.</li>
<li><b>Output characteristics:</b> set I<sub>B</sub> = 20 µA. Increase V<sub>CC</sub> in steps and note V<sub>CE</sub> and I<sub>E</sub>, keeping I<sub>B</sub> constant. Repeat for I<sub>B</sub> = 40 µA and 60 µA.</li>
<li>Repeat with the PNP transistor (all polarities reversed).</li>
<li>Plot the characteristics and find γ = ΔI<sub>E</sub>/ΔI<sub>B</sub> and r<sub>o</sub>.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> choose NPN or PNP above the workbench. The input curve is steep: small changes in V<sub>CB</sub> give large changes in I<sub>B</sub>, so take readings in small steps of V<sub>BB</sub>.</div>`,
  precautions: BJT_COMMON_PRECAUTIONS,
  errors: String.raw`<li>I<sub>B</sub> not constant during the output readings.</li><li>The input curve is steep, so small voltage reading errors give large errors in r<sub>i</sub>.</li>`,
  viva: [
    ['Why is the CC amplifier called an emitter follower?', 'The emitter voltage follows the base voltage (voltage gain ≈ 1, no phase reversal).'],
    ['What are the uses of a CC amplifier?', 'As a buffer or impedance-matching stage, because of its high input and low output resistance.'],
    ['What is the current gain in CC?', 'γ = I_E/I_B = 1 + β.'],
    ['Why are the output characteristics of CC and CE nearly the same?', 'Because I_E = I_C + I_B ≈ I_C.'],
    ['What is the voltage gain of a CC amplifier?', 'Slightly less than one.']],
  bench(root) { bjtBench(root, 'CC', 'exp20'); }
});

/* ================= experiments 21–22: regulated power supplies ================= */
PARTS.reg.polar = ['IN', 'OUT'];
function regSchem(zen) {
    let o = SC.B(30, 75, 30, 205, 'V_in', true) + SC.w([30, 75], [30, 50]) + SC.w([30, 205], [30, 240]) + SC.w([30, 50], [80, 50]) + SC.M(80, 50, 80, 240, 'V') + SC.dot(80, 50) + SC.dot(80, 240);
    if (zen) {
      o += SC.R(80, 50, 180, 50, 'R_s') + SC.w([180, 50], [260, 50]) + SC.M(205, 50, 205, 240, 'V') + SC.t(222, 160, 'V_o', 'start', 9) + SC.dot(205, 50) + SC.dot(205, 240);
      o += SC.D(260, 240, 260, 50, true, '') + SC.t(244, 130, 'Z', 'end', 10) + SC.dot(260, 50) + SC.dot(260, 240) + SC.M(260, 50, 370, 50, 'mA') + SC.w([370, 50], [420, 50]) + SC.R(420, 50, 420, 240, 'R_L', true) + SC.w([30, 240], [420, 240]);
      return SC.svg(480, 270, o);
    }
    o += SC.w([80, 50], [170, 50]) + SC.C(135, 50, 135, 240, 'C1') + SC.dot(135, 50) + SC.dot(135, 240) + SC.box(170, 28, 90, 50, '7805') + SC.t(176, 46, 'IN', 'start', 8) + SC.t(254, 46, 'OUT', 'end', 8) + SC.t(215, 70, 'GND', 'middle', 8);
    o += SC.w([215, 78], [215, 240]) + SC.dot(215, 240) + SC.w([260, 50], [350, 50]) + SC.C(300, 50, 300, 240, 'C2') + SC.dot(300, 50) + SC.dot(300, 240) + SC.M(350, 50, 350, 240, 'V') + SC.t(368, 150, 'V_o', 'start', 9) + SC.dot(350, 50) + SC.dot(350, 240);
    o += SC.M(350, 50, 450, 50, 'mA') + SC.w([450, 50], [480, 50]) + SC.R(480, 50, 480, 240, 'R_L', true) + SC.w([30, 240], [480, 240]);
    return SC.svg(530, 270, o);
  }

function regBench(root, kind, id) {
  const R = apparatus(id); const zen = kind === 'zener';
  const P = zen ? { Vz: 5.1 * (1 + R.range(-0.02, 0.02)), rz: 4.5 + R.range(0, 2.5), s: 0.05, Rs: 220 * (1 + R.range(-0.01, 0.01)) } : { Vn: 5.0 * (1 + R.range(-0.012, 0.012)), line: 0.0006 + R.range(0, 0.0006), load: 0.01 + R.range(0, 0.02) };
  const LOADS = zen ? ['open', 4700, 2200, 1000, 680, 470, 330, 270, 220, 180, 150, 120] : ['open', 1000, 470, 330, 220, 150, 100, 68, 47, 33];
  const st = Object.assign({ vin: 0, RL: zen ? 1000 : 470, mode: 'line' }, LS.get(id + '.st', {}));
  const pg = circuitPage(root);
  const parts = zen ? [
    { id: 'ps', type: 'supply', label: 'DC supply V_in', x: 10, y: 40, p: { max: 20 } }, { id: 'rs', type: 'res', label: 'R_s', x: 240, y: 24, p: { ohm: 220, text: '220 Ω' } },
    { id: 'z', type: 'diode', label: 'Zener diode', x: 300, y: 250, p: { zener: true, text: '5.1 V' } }, { id: 'ma', type: 'dmm', label: 'Milliammeter (I_L)', x: 500, y: 30, p: { unit: 'mA' } },
    { id: 'rl', type: 'rbox', label: 'Load R_L', x: 690, y: 40, p: { name: 'R_L' } }, { id: 'v1', type: 'dmm', label: 'Voltmeter (V_in)', x: 60, y: 300, p: { unit: 'V' } }, { id: 'v2', type: 'dmm', label: 'Voltmeter (V_out)', x: 560, y: 300, p: { unit: 'V' } }]
    : [
    { id: 'ps', type: 'supply', label: 'DC supply V_in', x: 10, y: 40, p: { max: 20 } }, { id: 'ic', type: 'reg', label: 'Regulator IC', x: 290, y: 150, p: { part: 'LM7805' } },
    { id: 'c1', type: 'cap', label: 'C1', x: 220, y: 330, p: { text: '0.33 µF', code: '334' } }, { id: 'c2', type: 'cap', label: 'C2', x: 420, y: 330, p: { text: '0.1 µF', code: '104' } },
    { id: 'ma', type: 'dmm', label: 'Milliammeter (I_L)', x: 500, y: 30, p: { unit: 'mA' } }, { id: 'rl', type: 'rbox', label: 'Load R_L', x: 690, y: 40, p: { name: 'R_L' } },
    { id: 'v1', type: 'dmm', label: 'Voltmeter (V_in)', x: 40, y: 300, p: { unit: 'V' } }, { id: 'v2', type: 'dmm', label: 'Voltmeter (V_out)', x: 600, y: 300, p: { unit: 'V' } }];
  const nets = zen ? [['ps.+', 'v1.+', 'rs.1'], ['rs.2', 'z.K', 'ma.+', 'v2.+'], ['ma.-', 'rl.1'], ['rl.2', 'z.A', 'ps.-', 'v1.-', 'v2.-']]
    : [['ps.+', 'v1.+', 'ic.IN', 'c1.1'], ['ic.OUT', 'c2.1', 'v2.+', 'ma.+'], ['ma.-', 'rl.1'], ['rl.2', 'ic.GND', 'c1.2', 'c2.2', 'ps.-', 'v1.-', 'v2.-']];
  const schem = () => regSchem(zen);
  const cb = circuitBench(pg.top, { key: id, W: 900, H: 470, parts, spec: { nets }, schematic: schem, onPower: () => upd() });
  function solve() {
    const RL = st.RL === 'open' ? Infinity : st.RL, Vin = st.vin;
    if (zen) {
      const Iz = V => P.s / P.rz * Math.log1p(Math.exp((V - P.Vz) / P.s)) + V * 2e-7;
      let a = 0, b = Math.max(0.001, Vin); for (let k = 0; k < 60; k++) { const m = (a + b) / 2; const f = (Vin - m) / P.Rs - Iz(m) - m / RL; if (f > 0) a = m; else b = m; }
      const V = (a + b) / 2; return { Vout: V, IL: isFinite(RL) ? V / RL : 0 };
    }
    let V = 0; for (let k = 0; k < 6; k++) { const I = isFinite(RL) ? V / RL : 0; const Vreg = P.Vn + P.line * (Vin - 10) - P.load * I; const lim = Math.max(0, Vin - (1.85 + 0.5 * I)); const kk = 0.06; V = -kk * Math.log(Math.exp(-Vreg / kk) + Math.exp(-lim / kk)); V = Math.max(0, V); }
    return { Vout: V, IL: isFinite(RL) ? V / RL : 0 };
  }
  function upd() { LS.set(id + '.st', st); const r = solve(); cb.show('ps', { v: st.vin }); cb.show('rl', { val: st.RL === 'open' ? 'OPEN' : st.RL + ' Ω' }); cb.show('v1', { val: st.vin.toFixed(2) }); cb.show('v2', { val: r.Vout.toFixed(3) }); cb.show('ma', { val: (r.IL * 1e3).toFixed(2) }); }
  const vS = slider({ label: 'Input voltage V_in', min: 0, max: 20, step: 0.1, value: st.vin, fmt: v => v.toFixed(1) + ' V', fine: [['−1', -1], ['−0.1', -0.1], ['+0.1', 0.1], ['+1', 1]], onInput: v => { st.vin = v; upd(); } });
  cb.bindKnob('ps', 'v', vS);
  const rlS = stepSelect({ label: 'Load R_L', values: LOADS, value: st.RL, fmt: v => v === 'open' ? 'open (no load)' : fmtOhm(v), onChange: v => { st.RL = v; upd(); } });
  const modeSeg = seg({ label: 'Recording', value: st.mode, options: [['line', 'Line regulation (vary V_in)'], ['load', 'Load regulation (vary R_L)']], onChange: v => { st.mode = v; LS.set(id + '.st', st); guide(); } });
  const gp = h('p', { class: 'howto' }); const guide = () => gp.textContent = st.mode === 'line' ? `Keep R_L fixed (e.g. ${zen ? '1 kΩ' : '470 Ω'}). Increase V_in from 0 to 20 V in steps (small steps near the knee) and record V_out.` : `Keep V_in fixed (e.g. ${zen ? '15' : '12'} V). Start with no load (open) and reduce R_L step by step, recording I_L and V_out.`;
  guide();
  const sb = statusBox(); const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record reading');
  pg.instr.append(card('Controls', '', modeSeg.el, gp, vS.el, rlS.el, h('div', { class: 'recrow' }, btn), sb.el)); upd();
  sb.say('Wire the circuit, check it and switch ON.');
  btn.addEventListener('click', () => {
    if (!cb.ready(sb.say)) return;
    const r = solve(); const row = { vin: rnd(st.vin, 2), RL: st.RL, vout: rnd(r.Vout, 3), IL: rnd(r.IL * 1e3, 2) };
    if (st.mode === 'line') { if (tLine.rows.length && tLine.rows[0].RL !== st.RL) return sb.say(`Keep R_L the same (${tLine.rows[0].RL}) for the whole line-regulation table.`, 'warn'); const i = tLine.rows.findIndex(x => x.vin === row.vin); if (i >= 0) tLine.update(i, row); else tLine.add(row); }
    else { if (tLoad.rows.length && tLoad.rows[0].vin !== row.vin) return sb.say(`Keep V_in the same (${tLoad.rows[0].vin} V) for the whole load-regulation table.`, 'warn'); const i = tLoad.rows.findIndex(x => x.RL === row.RL); if (i >= 0) tLoad.update(i, row); else tLoad.add(row); }
    sb.say(`Recorded V_in = ${row.vin} V, R_L = ${row.RL === 'open' ? 'open' : row.RL + ' Ω'}, I_L = ${row.IL} mA, V_out = ${row.vout} V.`, 'ok');
  });
  const tLine = new ObsTable({ key: id + '.line', title: 'Table 1. Line regulation (R<sub>L</sub> constant)', deletable: true, onChange: analyse, sort: (a, b) => a.vin - b.vin, columns: [{ label: 'R<sub>L</sub>', k: 'RL', fmt: v => v === 'open' ? 'open' : v + ' Ω' }, { label: 'V<sub>in</sub> (V)', k: 'vin', fmt: v => v.toFixed(2) }, { label: 'V<sub>out</sub> (V)', k: 'vout', fmt: v => v.toFixed(3) }] });
  const tLoad = new ObsTable({ key: id + '.load', title: 'Table 2. Load regulation (V<sub>in</sub> constant)', deletable: true, onChange: analyse, sort: (a, b) => a.IL - b.IL, columns: [{ label: 'V<sub>in</sub> (V)', k: 'vin', fmt: v => v.toFixed(2) }, { label: 'R<sub>L</sub>', k: 'RL', fmt: v => v === 'open' ? 'open' : v + ' Ω' }, { label: 'I<sub>L</sub> (mA)', k: 'IL', fmt: v => v.toFixed(2) }, { label: 'V<sub>out</sub> (V)', k: 'vout', fmt: v => v.toFixed(3) }] });
  const p1 = plotCard('Graph: V_out against V_in (line regulation)', { get: () => ({ xLabel: 'Input voltage V_in (V)', yLabel: 'Output voltage V_out (V)', series: [{ pts: tLine.rows.map(r => [r.vin, r.vout]), join: true }], zeroX: true, zeroY: true }) });
  const p2 = plotCard('Graph: V_out against I_L (load regulation)', { get: () => ({ xLabel: 'Load current I_L (mA)', yLabel: 'Output voltage V_out (V)', series: [{ pts: tLoad.rows.map(r => [r.IL, r.vout]), join: true }], zeroX: true, yDec: 2 }) });
  const res = resultBox();
  function analyse() {
    p1.view.redraw(); p2.view.redraw(); let html = '<h4>Calculation</h4><div class="calc-lines">'; let any = false;
    const L = tLine.rows.slice().sort((a, b) => a.vin - b.vin);
    if (L.length >= 4) {
      let k0 = -1; for (let i = 0; i < L.length - 1; i++) { const ok = L.slice(i).every((r, j, a) => j === 0 || (a[j].vout - a[j - 1].vout) / Math.max(1e-6, a[j].vin - a[j - 1].vin) < 0.15); if (ok && L[i].vout > 1) { k0 = i; break; } }
      const reg = k0 >= 0 ? L.slice(k0) : [];
      if (reg.length >= 2) { const a = reg[0], b = reg[reg.length - 1]; html += `<div>Regulation starts at V<sub>in</sub> ≈ ${a.vin} V; ${zen ? `Zener (breakdown) voltage V<sub>Z</sub> ≈ ${a.vout.toFixed(2)} V` : `regulated output ≈ ${mean(reg.map(r => r.vout)).toFixed(3)} V`}</div><div>Line regulation = ΔV<sub>out</sub>/ΔV<sub>in</sub> × 100 = (${b.vout.toFixed(3)} − ${a.vout.toFixed(3)})/(${b.vin} − ${a.vin}) × 100 = <b>${((b.vout - a.vout) / (b.vin - a.vin) * 100).toFixed(2)}%</b></div>`; if (!zen) html += `<div>Dropout voltage ≈ V<sub>in</sub> − V<sub>out</sub> at the start of regulation = ${(a.vin - a.vout).toFixed(2)} V</div>`; any = true; }
    }
    const D = tLoad.rows.slice().sort((a, b) => a.IL - b.IL);
    if (D.length >= 3) {
      const nl = D.find(r => r.RL === 'open') || D[0]; const inReg = D.filter(r => r.vout >= nl.vout * 0.95); const fl = inReg[inReg.length - 1];
      const reg = (nl.vout - fl.vout) / fl.vout * 100;
      html += `<div>V<sub>NL</sub> = ${nl.vout.toFixed(3)} V (${nl.RL === 'open' ? 'no load' : 'lightest load'}), V<sub>FL</sub> = ${fl.vout.toFixed(3)} V at I<sub>L</sub> = ${fl.IL} mA (largest load that is still regulated)</div><div>Load regulation = (V<sub>NL</sub> − V<sub>FL</sub>)/V<sub>FL</sub> × 100 = <b>${reg.toFixed(2)}%</b></div>`;
      if (inReg.length >= 2) { const f = linreg(inReg.map(r => [r.IL / 1000, r.vout])); html += `<div>Output resistance R<sub>o</sub> = −ΔV<sub>out</sub>/ΔI<sub>L</sub> ≈ ${(-f.m).toFixed(2)} Ω</div>`; }
      if (inReg.length < D.length) html += `<div style="color:var(--warn)">Beyond I<sub>L</sub> ≈ ${fl.IL} mA the output falls: ${zen ? 'the Zener current has dropped below its knee value' : 'the regulator has reached its limit'}. Maximum load current for regulation ≈ ${fl.IL} mA.</div>`;
      any = true;
    }
    html += '</div>';
    if (!any) html = '<h4>Calculation</h4><p class="small muted">Record the line-regulation table (V<sub>in</sub> from 0 to 20 V) and the load-regulation table (no load to the smallest R<sub>L</sub>).</p>';
    else html += `<p class="small muted" style="margin-top:8px">Smaller percentages mean better regulation. ${zen ? 'A Zener regulator holds V_out near V_Z only while the Zener current stays above its knee current.' : 'An IC regulator gives much better line and load regulation than a simple Zener circuit.'}</p>`;
    res.set(html);
  }
  pg.notes.append(card('Lab notebook', '', h('div', { class: 'stack' }, tLine.el, tLoad.el)), p1.el, p2.el, card('Result', '', res.el)); analyse();
}
const REG_PREC = String.raw`<li>Start with V<sub>in</sub> = 0 and increase it slowly.</li><li>Do not exceed the power rating of the Zener (or the current rating of the regulator).</li><li>Connect meters with correct polarity: ammeter in series, voltmeters in parallel.</li><li>Keep R<sub>L</sub> constant for the line-regulation table and V<sub>in</sub> constant for the load-regulation table.</li>`;
EXPS.push({
  no: 21, id: 'exp21', group: 'Electronics', short: 'Zener regulated power supply',
  title: 'Characteristics of a regulated power supply using a Zener diode',
  aim: 'To study the characteristics of regulated power supply using Zener diode.',
  apparatus: ['Variable DC supply (0–20 V)', 'Zener diode (5.1 V, 0.5 W)', 'Series resistance R<sub>s</sub> = 220 Ω', 'Load resistance box', 'Milliammeter, two voltmeters', 'Connecting wires'],
  theory: String.raw`
<h2>Zener diode</h2>
<p>A Zener diode is a heavily doped silicon p–n junction designed to work in <b>reverse breakdown</b>. Once the reverse voltage reaches the breakdown (Zener) voltage \(V_Z\), the voltage across it remains almost constant over a wide range of current. It is therefore connected <b>reverse biased</b>: cathode (band) towards the positive side.</p>
<h2>Zener voltage regulator</h2>
<p>The unregulated input \(V_{in}\) is applied through a series resistance \(R_s\); the Zener and the load \(R_L\) are in parallel. When \(V_{in}\) is above \(V_Z\),</p>
<div class="eq">\[I = \frac{V_{in}-V_Z}{R_s} = I_Z + I_L,\qquad V_{out} = V_Z\]</div>
<p>If \(V_{in}\) rises, the extra current flows through the Zener and \(V_{out}\) hardly changes. If the load draws more current, \(I_Z\) decreases by the same amount. Regulation is lost when \(I_Z\) falls to the knee current.</p>
<h3>Measures of regulation</h3>
<div class="eq">\[\text{Line regulation} = \frac{\Delta V_{out}}{\Delta V_{in}}\times100\%\ (R_L\ \text{constant}),\qquad \text{Load regulation} = \frac{V_{NL}-V_{FL}}{V_{FL}}\times 100\%\]</div>
<p>where \(V_{NL}\) is the output at no load and \(V_{FL}\) at full load. Smaller values mean better regulation.</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Connect the circuit as in the diagram: supply → R<sub>s</sub> → Zener (reverse biased) in parallel with the load (through the milliammeter); voltmeters across the input and the output.</li>
<li><b>Line regulation:</b> keep R<sub>L</sub> = 1 kΩ. Increase V<sub>in</sub> from 0 to 20 V in steps of 1 V (0.2 V near 5–7 V) and note V<sub>out</sub>.</li>
<li><b>Load regulation:</b> set V<sub>in</sub> = 15 V. Start with no load, then decrease R<sub>L</sub> step by step; note I<sub>L</sub> and V<sub>out</sub>.</li>
<li>Plot V<sub>out</sub> against V<sub>in</sub> and V<sub>out</sub> against I<sub>L</sub>. Calculate the line and load regulation.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> wire the circuit (watch the Zener’s band!) and press Check. If the Zener is the wrong way round, the bench tells you. Choose the table under Controls before recording.</div>`,
  precautions: REG_PREC + String.raw`<li>The Zener must be reverse biased (band towards positive).</li>`,
  errors: String.raw`<li>The Zener voltage changes with temperature and current (dynamic resistance r<sub>z</sub>).</li><li>Meter resolution: changes of V<sub>out</sub> are only a few millivolts.</li>`,
  viva: [
    ['What is a Zener diode?', 'A heavily doped p–n junction diode designed to operate in reverse breakdown with a sharp, stable breakdown voltage.'],
    ['Why is the Zener connected in reverse bias?', 'Only in reverse breakdown does its voltage stay constant; in forward bias it behaves like an ordinary diode (≈ 0.7 V).'],
    ['What is the function of the series resistor?', 'It limits the current and absorbs the difference between the input and Zener voltages.'],
    ['What is Zener breakdown and avalanche breakdown?', 'Zener breakdown (below about 5 V) is due to the strong field pulling electrons out of covalent bonds in a thin junction; avalanche breakdown (above about 6 V) is due to impact ionisation.'],
    ['What limits the regulation at heavy load?', 'When the load current approaches the total current, the Zener current falls below the knee current and the output voltage drops.'],
    ['Define line and load regulation.', 'Line: change of output per change of input at constant load. Load: change of output from no load to full load, as a percentage of full-load voltage.']],
  bench(root) { regBench(root, 'zener', 'exp21'); }
});
EXPS.push({
  no: 22, id: 'exp22', group: 'Electronics', short: 'IC regulated power supply (7805)',
  title: 'Characteristics of a regulated power supply using an integrated circuit',
  aim: 'To study the characteristics of regulated power supply by using integrated circuit (IC).',
  apparatus: ['Variable DC supply (0–20 V)', 'Three-terminal regulator IC LM7805', 'Capacitors 0.33 µF (input) and 0.1 µF (output)', 'Load resistance box', 'Milliammeter, two voltmeters', 'Connecting wires'],
  theory: String.raw`
<h2>Three-terminal IC regulators</h2>
<p>The 78xx series are fixed positive voltage regulators: 7805 gives +5 V, 7812 gives +12 V, etc. (79xx are negative regulators, LM317 is adjustable). Inside the IC a reference voltage, an error amplifier and a series pass transistor keep the output constant; the IC also has current limiting and thermal shutdown.</p>
<table><tr><th>Pin (7805, TO-220, front view)</th><th>1</th><th>2</th><th>3</th></tr><tr><td>Function</td><td>IN</td><td>GND</td><td>OUT</td></tr></table>
<p>A capacitor of 0.33 µF at the input and 0.1 µF at the output, close to the IC, keep it stable.</p>
<h3>Dropout voltage</h3>
<p>The regulator works only when the input is at least about 2 V above the output: \(V_{in} \ge V_{out} + V_{dropout}\) (≈ 7 V for the 7805). Below this, \(V_{out}\) follows \(V_{in} - V_{dropout}\).</p>
<h3>Regulation</h3>
<div class="eq">\[\text{Line regulation} = \frac{\Delta V_{out}}{\Delta V_{in}}\times 100\%,\qquad \text{Load regulation} = \frac{V_{NL}-V_{FL}}{V_{FL}}\times 100\%,\qquad R_o = -\frac{\Delta V_{out}}{\Delta I_L}\]</div>
<p>IC regulators give much better regulation than a simple Zener circuit, typically a few millivolts change over the whole input and load range.</p>`,
  procedure: String.raw`
<ol class="steps">
<li>Identify the pins of the 7805 (IN, GND, OUT). Connect the input capacitor between IN and GND and the output capacitor between OUT and GND.</li>
<li>Connect the variable supply to IN and GND with a voltmeter across it. Connect the load (through the milliammeter) and a voltmeter across OUT and GND.</li>
<li><b>Line regulation:</b> keep R<sub>L</sub> = 470 Ω. Increase V<sub>in</sub> from 0 to 20 V in steps (small steps between 5 and 8 V) and note V<sub>out</sub>.</li>
<li><b>Load regulation:</b> set V<sub>in</sub> = 12 V. Start with no load and decrease R<sub>L</sub> step by step; note I<sub>L</sub> and V<sub>out</sub>.</li>
<li>Plot the graphs and calculate the line regulation, load regulation, output resistance and dropout voltage.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> the most common mistake is to interchange the IN and OUT pins; the Check button catches it.</div>`,
  precautions: REG_PREC + String.raw`<li>Never interchange the IN and OUT pins of the regulator.</li><li>Keep the capacitors close to the IC.</li>`,
  errors: String.raw`<li>The output changes are only millivolts, close to the voltmeter resolution.</li><li>Heating of the IC at large load current changes the output slightly.</li>`,
  viva: [
    ['What is the output of a 7805?', '+5 V regulated (78 = positive series, 05 = 5 V).'],
    ['What is dropout voltage?', 'The minimum difference between input and output for which the regulator still regulates (about 2 V for 78xx).'],
    ['Why are capacitors connected at the input and output?', 'To prevent oscillation and to improve the transient response; the input capacitor also helps when the regulator is far from the filter capacitor.'],
    ['What protections are built into the IC?', 'Short-circuit current limiting, thermal shutdown and safe-operating-area protection of the pass transistor.'],
    ['How can you get a variable output?', 'Use an adjustable regulator such as LM317 with two resistors: V_out = 1.25(1 + R2/R1) V.'],
    ['Compare IC and Zener regulators.', 'IC regulators have much better line and load regulation, higher current capacity and built-in protection; Zener regulators are simple but waste power and regulate poorly at heavy loads.']],
  bench(root) { regBench(root, 'ic', 'exp22'); }
});

/* ================= experiments 23–25: logic gates ================= */
const IC74 = {
  '7400': { fn: 'NAND', name: 'quad 2-input NAND', g: [[1, 2, 3], [4, 5, 6], [9, 10, 8], [12, 13, 11]] },
  '7402': { fn: 'NOR', name: 'quad 2-input NOR', g: [[2, 3, 1], [5, 6, 4], [8, 9, 10], [11, 12, 13]] },
  '7404': { fn: 'NOT', name: 'hex inverter', g: [[1, 2], [3, 4], [5, 6], [9, 8], [11, 10], [13, 12]] },
  '7408': { fn: 'AND', name: 'quad 2-input AND', g: [[1, 2, 3], [4, 5, 6], [9, 10, 8], [12, 13, 11]] },
  '7432': { fn: 'OR', name: 'quad 2-input OR', g: [[1, 2, 3], [4, 5, 6], [9, 10, 8], [12, 13, 11]] }
};
const LOGIC = { AND: (a, b) => a & b, OR: (a, b) => a | b, NAND: (a, b) => 1 - (a & b), NOR: (a, b) => 1 - (a | b), NOT: a => 1 - a };
/* functional evaluation of a TTL circuit built on the trainer */
function ttlEval(f, icId, partNo, A, B) {
  const ic = IC74[partNo]; const P = n => f(`${icId}.${n}`);
  const vcc = f('t.+5V'), gnd = f('t.GND');
  const res = { problems: [], led: null, floating: false, short: false };
  if (vcc === gnd) { res.short = true; res.problems.push('+5 V is joined directly to GND: this is a short circuit across the trainer supply.'); return res; }
  const powered = P(14) === vcc && P(7) === gnd;
  if (P(14) === gnd && P(7) === vcc) res.problems.push('The IC is powered the wrong way round (pin 14 to GND, pin 7 to +5 V). This would destroy a real IC!');
  else { if (P(14) !== vcc) res.problems.push('Pin 14 (V<sub>CC</sub>) of the IC must be connected to +5 V.'); if (P(7) !== gnd) res.problems.push('Pin 7 (GND) of the IC must be connected to GND.'); }
  // drivers
  const drv = {}; const addD = (root, d) => (drv[root] = drv[root] || []).push(d);
  addD(vcc, { k: 'const', v: 1, n: '+5 V' }); addD(gnd, { k: 'const', v: 0, n: 'GND' }); addD(f('t.A'), { k: 'sw', v: A, n: 'switch A' }); addD(f('t.B'), { k: 'sw', v: B, n: 'switch B' });
  ic.g.forEach((g, gi) => { const out = g[g.length - 1]; addD(P(out), { k: 'gate', gi, n: `output pin ${out}` }); });
  for (const ds of Object.values(drv)) if (ds.length > 1) res.problems.push(`${ds.map(d => d.n).join(' and ')} are connected together. Two outputs (or an output and a supply line) must never be joined.`);
  const val = {}; Object.entries(drv).forEach(([r, ds]) => { if (ds.length === 1 && ds[0].k !== 'gate') val[r] = ds[0].v; });
  const gateOut = new Array(ic.g.length).fill(undefined);
  for (let pass = 0; pass < 10; pass++) {
    ic.g.forEach((g, gi) => {
      const ins = g.slice(0, -1).map(n => { const r = P(n); if (val[r] !== undefined) return val[r]; if (!drv[r]) { res.floatPins = (res.floatPins || new Set()).add(n); return 1; } return undefined; });
      if (!powered || ins.some(x => x === undefined)) return;
      const o = LOGIC[ic.fn](...ins); gateOut[gi] = o; const r = P(g[g.length - 1]); if (drv[r] && drv[r].length === 1) val[r] = o;
    });
  }
  const L = f('t.L1'); res.ledDriven = !!drv[L];
  if (res.floatPins) { const used = new Set(); let roots = new Set([L]); for (let k = 0; k < 6; k++) { ic.g.forEach((g, gi) => { if (roots.has(P(g[g.length - 1]))) { used.add(gi); g.slice(0, -1).forEach(n => roots.add(P(n))); } }); } const keep = new Set(); ic.g.forEach((g, gi) => { if (used.has(gi)) g.slice(0, -1).forEach(n => { if (res.floatPins.has(n)) keep.add(n); }); }); res.floatPins = keep; } res.led = val[L] !== undefined ? val[L] : (drv[L] ? 0 : 0);
  res.powered = powered;
  return res;
}
function ttlSpec(icId, partNo, target, demo) {
  return {
    demo,
    fn: ({ f }) => {
      const combos = target === 'NOT' ? [[0, 0], [1, 0]] : [[0, 0], [0, 1], [1, 0], [1, 1]];
      const r0 = ttlEval(f, icId, partNo, 0, 0);
      const out = { ok: false, msgs: [], links: [], short: r0.short };
      r0.problems.forEach(p => out.msgs.push({ kind: r0.short ? 'short' : 'merge', text: p }));
      if (!out.msgs.length && !r0.ledDriven) out.msgs.push({ kind: 'logic', text: 'The LED input is not connected to any gate output.' });
      if (!out.msgs.length) {
        for (const [a, b] of combos) { const r = ttlEval(f, icId, partNo, a, b); const want = LOGIC[target](a, b); if (r.led !== want) { out.msgs.push({ kind: 'logic', text: `Your circuit does not give ${target}: for A = ${a}${target === 'NOT' ? '' : `, B = ${b}`} the LED would be ${r.led ? 'ON' : 'OFF'} but it should be ${want ? 'ON' : 'OFF'}.` + (r.floatPins && r.floatPins.size ? ` (Unconnected input pins float HIGH in TTL: pin ${[...r.floatPins].join(', ')}.)` : '') }); break; } }
      }
      out.links = demo.filter(([a, b]) => f(a) !== f(b));
      if (!out.msgs.length) out.ok = true;
      else if (out.links.length && out.msgs[0].text.startsWith('Pin 14')) out.msgs[0].text += ' Then connect the gate inputs and output.';
      return out;
    }
  };
}
/* ---- schematic helpers for logic ---- */
const LS_ = {
  inp: (x, y, n) => `<circle class="s-m" cx="${x}" cy="${y}" r="5"/>${SC.t(x - 9, y + 4, n, 'end', 12)}`,
  led: (x, y) => SC.D(x, y, x, y + 56) + `<path class="s-w" d="M${x + 12} ${y + 22} l10 -8 M${x + 12} ${y + 32} l10 -8"/><path class="s-f" d="M${x + 22} ${y + 14} l-5 0 l3 4 z M${x + 22} ${y + 24} l-5 0 l3 4 z"/>` + SC.gnd(x, y + 56) + SC.t(x + 14, y + 52, 'LED', 'start', 10) + SC.t(x, y - 8, 'Y', 'middle', 12),
  vcc: (x, y) => `<line class="s-w" x1="${x - 10}" y1="${y}" x2="${x + 10}" y2="${y}"/>${SC.t(x, y - 6, '+5 V', 'middle', 10)}`,
  pwr: (n) => SC.t(10, 220, `IC ${n}: pin 14 → +5 V, pin 7 → GND`, 'start', 10)
};
function gateSchem(stage) {
  const s = stage; let o = '';
  const one = (type, pins, ic) => { const g = SC.G(type, 150, 100, pins); o += g.svg; if (type === 'NOT') o += LS_.inp(60, 100, 'A') + SC.w([65, 100], g.in[0]); else o += LS_.inp(60, 90, 'A') + LS_.inp(60, 110, 'B') + SC.w([65, 90], g.in[0]) + SC.w([65, 110], g.in[1]); o += SC.w(g.out, [320, g.out[1]]) + LS_.led(320, 100) + LS_.pwr(ic); };
  if (s === 'ttl-or') one('OR', ['1', '2', '3'], '7432'); if (s === 'ttl-and') one('AND', ['1', '2', '3'], '7408'); if (s === 'ttl-not') one('NOT', ['1', '2'], '7404');
  if (s === 'ttl-nor') one('NOR', ['2', '3', '1'], '7402'); if (s === 'ttl-nand') one('NAND', ['1', '2', '3'], '7400');
  if (s === 'u-nand-not') one('NAND', ['1', '2', '3'], '7400');
  if (s === 'u-nor-not') one('NOR', ['2', '3', '1'], '7402');
  if (s === 'u-nand-not' || s === 'u-nor-not') { o = o.replace(LS_.inp(60, 110, 'B'), ''); o += SC.w([65, 90], [80, 90], [80, 110], [140, 110]) + SC.dot(80, 90); }
  if (s === 'u-nand-and' || s === 'u-nor-or') { const ty = s === 'u-nand-and' ? 'NAND' : 'NOR'; const p1 = ty === 'NAND' ? ['1', '2', '3'] : ['2', '3', '1'], p2 = ty === 'NAND' ? ['4', '5', '6'] : ['5', '6', '4']; const g1 = SC.G(ty, 110, 100, p1), g2 = SC.G(ty, 230, 100, p2); o += g1.svg + g2.svg + LS_.inp(40, 90, 'A') + LS_.inp(40, 110, 'B') + SC.w([45, 90], g1.in[0]) + SC.w([45, 110], g1.in[1]) + SC.w(g1.out, [200, 100], [200, 90], g2.in[0]) + SC.w([200, 100], [200, 110], g2.in[1]) + SC.dot(200, 100) + SC.w(g2.out, [340, 100]) + LS_.led(340, 100) + LS_.pwr(ty === 'NAND' ? '7400' : '7402'); }
  if (s === 'u-nand-or' || s === 'u-nor-and') { const ty = s === 'u-nand-or' ? 'NAND' : 'NOR'; const P = ty === 'NAND' ? [['1', '2', '3'], ['4', '5', '6'], ['9', '10', '8']] : [['2', '3', '1'], ['5', '6', '4'], ['8', '9', '10']]; const g1 = SC.G(ty, 110, 60, P[0]), g2 = SC.G(ty, 110, 150, P[1]), g3 = SC.G(ty, 240, 105, P[2]); o += g1.svg + g2.svg + g3.svg + LS_.inp(30, 60, 'A') + LS_.inp(30, 150, 'B') + SC.w([35, 60], [80, 60], [80, 50], g1.in[0]) + SC.w([80, 60], [80, 70], g1.in[1]) + SC.dot(80, 60) + SC.w([35, 150], [80, 150], [80, 140], g2.in[0]) + SC.w([80, 150], [80, 160], g2.in[1]) + SC.dot(80, 150) + SC.w(g1.out, [200, 60], [200, 95], g3.in[0]) + SC.w(g2.out, [200, 150], [200, 115], g3.in[1]) + SC.w(g3.out, [350, 105]) + LS_.led(350, 105) + LS_.pwr(ty === 'NAND' ? '7400' : '7402'); }
  if (s.startsWith('dtl')) {
    const orIn = () => LS_.inp(40, 60, 'A') + LS_.inp(40, 140, 'B') + SC.D(45, 60, 140, 60, false, 'D1') + SC.D(45, 140, 140, 140, false, 'D2') + SC.w([140, 60], [170, 60], [170, 140], [140, 140]) + SC.dot(170, 100);
    const andIn = () => LS_.inp(40, 60, 'A') + LS_.inp(40, 140, 'B') + SC.D(140, 60, 45, 60, false, 'D1') + SC.D(140, 140, 45, 140, false, 'D2') + SC.w([140, 60], [170, 60], [170, 140], [140, 140]) + SC.dot(170, 100);
    if (s === 'dtl-or') o += orIn() + SC.w([170, 100], [330, 100]) + SC.R(230, 100, 230, 190, '1 kΩ') + SC.gnd(230, 190) + SC.dot(230, 100) + LS_.led(330, 100);
    if (s === 'dtl-and') o += andIn() + SC.w([170, 100], [330, 100]) + SC.R(230, 30, 230, 100, '4.7 kΩ') + LS_.vcc(230, 30) + SC.dot(230, 100) + LS_.led(330, 100);
    const inv = (bx, by, rbFrom, rbLab, rc) => { const q = SC.Q(bx, by, false, 'BC547'); return SC.R(rbFrom[0], rbFrom[1], bx, by, rbLab) + q.svg + SC.w(q.E, [q.E[0], 200]) + SC.gnd(q.E[0], 200) + SC.w(q.C, [q.C[0], 70]) + SC.R(q.C[0], 20, q.C[0], 70, rc) + LS_.vcc(q.C[0], 20) + SC.w([q.C[0], 70], [370, 70]) + SC.dot(q.C[0], 70) + LS_.led(370, 70); };
    if (s === 'dtl-not') o += LS_.inp(40, 120, 'A') + SC.w([45, 120], [80, 120]) + inv(200, 120, [80, 120], '10 kΩ', '1 kΩ');
    if (s === 'dtl-nor') o += orIn() + SC.w([170, 100], [200, 100], [200, 120]) + SC.R(170, 100, 170, 200, '') + SC.t(160, 170, '10 kΩ', 'end', 10) + SC.gnd(170, 200) + inv(280, 120, [200, 120], '4.7 kΩ', '1 kΩ');
    if (s === 'dtl-nand') o += andIn() + SC.R(170, 20, 170, 100, '4.7 kΩ') + LS_.vcc(170, 20) + SC.D(170, 100, 250, 100, false, 'D3') + SC.w([250, 100], [270, 100], [270, 120]) + SC.R(270, 120, 270, 200, '') + SC.t(262, 175, '10 kΩ', 'end', 10) + SC.gnd(270, 200) + (() => { const q = SC.Q(270, 120, false, ''); return q.svg + SC.w(q.E, [q.E[0], 200]) + SC.gnd(q.E[0], 200) + SC.w(q.C, [q.C[0], 70]) + SC.R(q.C[0], 20, q.C[0], 70, '2.2 kΩ') + LS_.vcc(q.C[0], 20) + SC.w([q.C[0], 70], [380, 70]) + SC.dot(q.C[0], 70) + LS_.led(380, 70); })();
  }
  return SC.svg(430, 240, o);
}
/* ---- DTL nets and analytic output ---- */
const DTL = {
  'dtl-or': { parts: ['d1', 'd2', 'r1k'], nets: [['t.A', 'd1.A'], ['t.B', 'd2.A'], ['d1.K', 'd2.K', 'r1k.1', 't.L1'], ['r1k.2', 't.GND']], same: [['d1', 'd2']], v: (a, b) => Math.max(a, b) ? 4.35 : 0.0, target: 'OR' },
  'dtl-and': { parts: ['d1', 'd2', 'r4k7'], nets: [['t.A', 'd1.K'], ['t.B', 'd2.K'], ['d1.A', 'd2.A', 'r4k7.1', 't.L1'], ['r4k7.2', 't.+5V']], same: [['d1', 'd2']], v: (a, b) => (a && b) ? 4.98 : 0.66, target: 'AND' },
  'dtl-not': { parts: ['r10k', 'r1k', 'q'], nets: [['t.A', 'r10k.1'], ['r10k.2', 'q.B'], ['q.E', 't.GND'], ['q.C', 'r1k.1', 't.L1'], ['r1k.2', 't.+5V']], v: a => a ? 0.12 : 4.97, target: 'NOT' },
  'dtl-nor': { parts: ['d1', 'd2', 'r10k', 'r4k7', 'r1k', 'q'], nets: [['t.A', 'd1.A'], ['t.B', 'd2.A'], ['d1.K', 'd2.K', 'r10k.1', 'r4k7.1'], ['r10k.2', 'q.E', 't.GND'], ['r4k7.2', 'q.B'], ['q.C', 'r1k.1', 't.L1'], ['r1k.2', 't.+5V']], same: [['d1', 'd2']], v: (a, b) => (a || b) ? 0.13 : 4.97, target: 'NOR' },
  'dtl-nand': { parts: ['d1', 'd2', 'd3', 'r4k7', 'r10k', 'r2k2', 'q'], nets: [['t.A', 'd1.K'], ['t.B', 'd2.K'], ['d1.A', 'd2.A', 'd3.A', 'r4k7.1'], ['r4k7.2', 'r2k2.2', 't.+5V'], ['d3.K', 'q.B', 'r10k.1'], ['r10k.2', 'q.E', 't.GND'], ['q.C', 'r2k2.1', 't.L1']], same: [['d1', 'd2', 'd3']], v: (a, b) => (a && b) ? 0.14 : 4.96, target: 'NAND' }
};
const DTL_POS = {
  'dtl-or': { d1: [400, 40], d2: [400, 130], r1k: [620, 120] },
  'dtl-and': { d1: [400, 40], d2: [400, 130], r4k7: [620, 60] },
  'dtl-not': { r10k: [420, 60], q: [620, 160], r1k: [700, 30] },
  'dtl-nor': { d1: [390, 20], d2: [390, 100], r10k: [400, 190], r4k7: [580, 40], q: [640, 190], r1k: [760, 40] },
  'dtl-nand': { d1: [390, 20], d2: [390, 100], d3: [560, 120], r4k7: [560, 20], r10k: [420, 200], q: [680, 220], r2k2: [740, 60] }
};
function logicBench(root, id, stageDefs) {
  const pg = circuitPage(root);
  const parts = [{ id: 't', type: 'trainer', label: 'Digital trainer', x: 20, y: 250, st: { A: 0, B: 0 } },
    { id: 'd1', type: 'diode', label: 'D1 (1N4148)', x: 400, y: 40, p: { text: '' } }, { id: 'd2', type: 'diode', label: 'D2 (1N4148)', x: 400, y: 130, p: { text: '' } }, { id: 'd3', type: 'diode', label: 'D3 (1N4148)', x: 560, y: 120, p: { text: '' } },
    { id: 'r1k', type: 'res', label: '1 kΩ', x: 620, y: 120, p: { ohm: 1000 } }, { id: 'r4k7', type: 'res', label: '4.7 kΩ', x: 620, y: 60, p: { ohm: 4700 } }, { id: 'r10k', type: 'res', label: '10 kΩ', x: 420, y: 60, p: { ohm: 10000 } }, { id: 'r2k2', type: 'res', label: '2.2 kΩ', x: 740, y: 60, p: { ohm: 2200 } },
    { id: 'q', type: 'bjt', label: 'Transistor', x: 620, y: 160, p: { part: 'BC547' } },
    ...Object.keys(IC74).map(n => ({ id: 'ic' + n, type: 'ic', label: 'IC ' + n, x: 450, y: 70, p: { part: '74LS' + n.slice(2), fn: IC74[n].name } }))];
  const stages = stageDefs.map(sd => {
    if (sd.dtl) { const D = DTL[sd.id]; return { id: sd.id, name: sd.name, parts: ['t', ...D.parts], pos: DTL_POS[sd.id], spec: { nets: D.nets, same: D.same }, intro: sd.intro || `Build the ${D.target} gate from diodes${/not|nor|nand/.test(sd.id) ? ', resistors and a transistor' : ' and a resistor'} on the bench, as in the diagram.`, target: D.target }; }
    return { id: sd.id, name: sd.name, parts: ['t', 'ic' + sd.ic], spec: ttlSpec('ic' + sd.ic, sd.ic, sd.target, sd.demo), intro: sd.intro || `Use IC ${sd.ic} (${IC74[sd.ic].name}). Remember pin 14 → +5 V and pin 7 → GND.`, target: sd.target, ic: sd.ic };
  });
  const cb = circuitBench(pg.top, {
    key: id, W: 900, H: 470, parts, stages, stageLabel: 'Circuit', schematic: gateSchem,
    onSwitch: (pid, sw) => { const s = cb.st('t'); cb.show('t', { [sw]: s[sw] ? 0 : 1 }); upd(); },
    onPower: () => upd(), onStage: () => { buildTable(); upd(); }
  });
  const cur = () => stages.find(s => s.id === cb.stage);
  function ttlOut() { const s = cur(), t = cb.st('t'); const f = ufOf(); const r = ttlEval(f, 'ic' + s.ic, s.ic, t.A ? 1 : 0, t.B ? 1 : 0); const led = r.led; return { led, v: led ? 3.42 + (t.A + t.B) * 0.01 : 0.16 }; }
  function ufOf() { const par = {}; const f = x => { if (par[x] === undefined) par[x] = x; return par[x] === x ? x : (par[x] = f(par[x])); }; cb.wires().forEach(([a, b]) => { par[f(a)] = f(b); }); return f; }
  function now() { const s = cur(); if (s.spec.nets) { const t = cb.st('t'); const v = DTL[s.id].v(t.A ? 1 : 0, t.B ? 1 : 0); return { v, led: v > 2 ? 1 : 0 }; } return ttlOut(); }
  function upd() { if (!cb.on) { cb.show('t', { led: 0, vout: 0 }); return; } const o = now(); cb.show('t', { led: o.led, vout: o.v }); }
  const sb = statusBox(); const btn = h('button', { type: 'button', class: 'btn pri' }, 'Record this row of the truth table');
  const how = h('p', { class: 'howto' }, 'Click the switches A and B on the trainer to set the inputs (1 = up = +5 V). Record the LED state and output voltage for every input combination.');
  pg.instr.append(card('Truth table', 'switch, observe, record', how, h('div', { class: 'recrow' }, btn), sb.el));
  sb.say('Wire the circuit, check it and switch ON.');
  let tbl; const host = h('div');
  function buildTable() {
    const s = cur(); const not = s.target === 'NOT';
    tbl = new ObsTable({ key: id + '.' + s.id, title: `Truth table: ${s.name}`, onChange: analyse,
      template: () => (not ? [[0, 0], [1, 0]] : [[0, 0], [0, 1], [1, 0], [1, 1]]).map(([a, b]) => ({ A: a, B: b })),
      columns: [{ label: 'A', k: 'A' }, ...(not ? [] : [{ label: 'B', k: 'B' }]), { label: 'LED', k: 'led', fmt: v => v ? 'ON (1)' : 'OFF (0)' }, { label: 'V<sub>out</sub> (V)', k: 'v', fmt: v => v.toFixed(2) }, { label: `Expected Y = ${not ? 'Ā' : s.target === 'AND' ? 'A·B' : s.target === 'OR' ? 'A + B' : s.target === 'NAND' ? '(A·B)′' : '(A + B)′'}`, v: r => LOGIC[s.target](r.A, r.B), calc: 1 }, { label: 'Check', v: r => r.led == null ? null : r.led === LOGIC[s.target](r.A, r.B) ? '✓' : '✗', calc: 1 }] });
    host.innerHTML = ''; host.append(tbl.el); analyse();
  }
  btn.addEventListener('click', () => {
    if (!cb.ready(sb.say)) return;
    const s = cur(), t = cb.st('t'); const A = t.A ? 1 : 0, B = s.target === 'NOT' ? 0 : (t.B ? 1 : 0); const o = now();
    const i = tbl.rows.findIndex(r => r.A === A && r.B === B); tbl.update(i, { led: o.led, v: rnd(o.v, 2) });
    const left = tbl.rows.filter(r => r.led == null).length;
    sb.say(`A = ${A}${s.target === 'NOT' ? '' : `, B = ${B}`} → LED ${o.led ? 'ON' : 'OFF'}, V<sub>out</sub> = ${o.v.toFixed(2)} V recorded.` + (left ? ` ${left} more input combination${left > 1 ? 's' : ''} to go: flip a switch.` : ' Truth table complete!'), 'ok');
  });
  const res = resultBox();
  function analyse() {
    const done = stages.map(s => { const rows = LS.get('tbl.' + id + '.' + s.id, null); if (!rows || rows.some(r => r.led == null)) return { s, st: rows && rows.some(r => r.led != null) ? 'partial' : 'todo' }; return { s, st: rows.every(r => r.led === LOGIC[s.target](r.A, r.B)) ? 'ok' : 'bad' }; });
    const n = done.filter(d => d.st === 'ok').length;
    res.set(`<h4>Result</h4><table class="truth"><thead><tr><th>Circuit</th><th>Status</th></tr></thead><tbody>${done.map(d => `<tr><td style="text-align:left">${d.s.name}</td><td>${d.st === 'ok' ? '<span class="badge-ok">verified ✓</span>' : d.st === 'bad' ? '<span class="badge-no">does not match</span>' : d.st === 'partial' ? 'in progress' : '—'}</td></tr>`).join('')}</tbody></table><p class="small muted" style="margin-top:8px">${n} of ${stages.length} truth tables verified. Logic 1 ≈ ${stages.some(s => s.spec.nets) ? '4.3–5 V (DTL) / ' : ''}3.4 V (TTL), logic 0 ≈ 0–0.7 V.</p>`);
  }
  buildTable(); upd();
  pg.notes.append(card('Lab notebook', 'each circuit has its own truth table', host), card('Result', '', res.el));
}
const TTL_THEORY = String.raw`
<h2>TTL integrated circuits</h2>
<p>Transistor–transistor logic (TTL) uses a multi-emitter input transistor and a totem-pole output. The 74LS series works from a +5 V supply: pin 14 is V<sub>CC</sub> and pin 7 is GND on all 14-pin gate ICs. Logic levels: input LOW ≤ 0.8 V, input HIGH ≥ 2.0 V; output LOW ≈ 0.2 V, output HIGH ≈ 3.4 V. An unconnected TTL input behaves as HIGH (but should never be left open in practice).</p>
<table><tr><th>IC</th><th>Gates</th><th>Pins of gate 1</th></tr>
<tr><td>7400</td><td>four 2-input NAND</td><td>inputs 1, 2 → output 3</td></tr>
<tr><td>7402</td><td>four 2-input NOR</td><td>inputs 2, 3 → output 1</td></tr>
<tr><td>7404</td><td>six NOT (inverters)</td><td>input 1 → output 2</td></tr>
<tr><td>7408</td><td>four 2-input AND</td><td>inputs 1, 2 → output 3</td></tr>
<tr><td>7432</td><td>four 2-input OR</td><td>inputs 1, 2 → output 3</td></tr></table>
<p>Gates 2–4 of the 7400/7408/7432 use pins 4, 5 → 6; 9, 10 → 8; 12, 13 → 11. Gates 2–4 of the 7402 use 5, 6 → 4; 8, 9 → 10; 11, 12 → 13.</p>`;
const LOGIC_PREC = String.raw`<li>Connect pin 14 to +5 V and pin 7 to GND before applying inputs; never reverse the supply to an IC.</li><li>Never connect two gate outputs together, or an output directly to +5 V or GND.</li><li>Identify pin 1 from the notch (or dot) on the IC.</li><li>Check diode orientation (band = cathode) and transistor leads in DTL circuits.</li><li>Switch off the trainer before changing connections.</li>`;

EXPS.push({
  no: 23, id: 'exp23', group: 'Digital electronics', short: 'OR, AND, NOT gates (DTL & TTL)',
  title: 'Logic gates OR, AND and NOT using DTL and TTL',
  aim: 'To study logic gates OR, AND and NOT by using DTL and TTL.',
  apparatus: ['Digital trainer kit (+5 V supply, logic switches, LED indicators)', 'Diodes 1N4148, resistors (1 kΩ, 4.7 kΩ, 10 kΩ), transistor BC547', 'ICs 74LS32 (OR), 74LS08 (AND), 74LS04 (NOT)', 'Connecting wires'],
  theory: String.raw`
<h2>Logic gates</h2>
<p>A logic gate has one or more inputs and one output; the output is a fixed logical function of the inputs. Using positive logic, HIGH (≈ +5 V) = 1 and LOW (≈ 0 V) = 0.</p>
<table><tr><th>A</th><th>B</th><th>OR: A + B</th><th>AND: A·B</th></tr><tr><td>0</td><td>0</td><td>0</td><td>0</td></tr><tr><td>0</td><td>1</td><td>1</td><td>0</td></tr><tr><td>1</td><td>0</td><td>1</td><td>0</td></tr><tr><td>1</td><td>1</td><td>1</td><td>1</td></tr></table>
<p>NOT gate: Y = Ā (output is the complement of the input).</p>
<h2>Diode logic (the D of DTL)</h2>
<p><b>OR gate:</b> the inputs are applied to the anodes of two diodes whose cathodes are joined and returned to ground through a resistor. If any input is HIGH, its diode conducts and the output is HIGH (≈ 5 − 0.7 = 4.3 V). <b>AND gate:</b> the cathodes go to the inputs and the joined anodes are pulled up to +5 V through a resistor. If any input is LOW, its diode conducts and pulls the output LOW (≈ 0.7 V); only when both inputs are HIGH is the output HIGH.</p>
<h2>Transistor inverter (the T of DTL)</h2>
<p><b>NOT gate:</b> the input drives the base of an NPN transistor through R<sub>B</sub>; the collector is connected to +5 V through R<sub>C</sub>. Input HIGH → transistor saturated → output ≈ 0.1 V (LOW). Input LOW → transistor cut off → output ≈ +5 V (HIGH).</p>` + TTL_THEORY,
  procedure: String.raw`
<ol class="steps">
<li>Check the trainer kit: switch on and verify the +5 V supply, the logic switches and the LEDs.</li>
<li><b>DTL OR gate:</b> build the diode OR circuit on the bench. Connect the inputs to switches A and B and the output to an LED. For all four input combinations note the LED state and measure the output voltage.</li>
<li><b>DTL AND gate:</b> build the diode AND circuit (diodes reversed, resistor to +5 V) and repeat.</li>
<li><b>DTL NOT gate:</b> build the transistor inverter and note the output for A = 0 and A = 1.</li>
<li><b>TTL:</b> insert IC 7432, connect pin 14 to +5 V and pin 7 to GND, connect A and B to pins 1 and 2 and pin 3 to an LED. Verify the OR truth table. Repeat with 7408 (AND) and 7404 (NOT: input pin 1, output pin 2).</li>
<li>Compare the observed truth tables with the theoretical ones.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> choose the circuit above the workbench. Each circuit has its own wiring and truth table. Click the switches on the trainer to change A and B. For TTL, you may use any gate of the IC; the bench checks that your circuit really behaves as the required gate.</div>`,
  precautions: LOGIC_PREC,
  errors: String.raw`<li>DTL output levels are not ideal (logic 0 of the diode AND gate is about 0.7 V; logic 1 of the OR gate is about 4.3 V).</li><li>Loose connections on the breadboard.</li>`,
  viva: [
    ['What is a logic gate?', 'A digital circuit whose output is a definite logical function (AND, OR, NOT …) of its inputs.'],
    ['What is positive logic?', 'HIGH voltage represents 1 and LOW voltage represents 0.'],
    ['Why is the output of a diode OR gate 4.3 V and not 5 V?', 'Because of the forward voltage drop (≈ 0.7 V) of the conducting silicon diode.'],
    ['Why does the transistor work as an inverter?', 'A HIGH input saturates it, pulling the collector (output) LOW; a LOW input cuts it off so the collector rises to V_CC.'],
    ['What does DTL stand for? TTL?', 'Diode–transistor logic; transistor–transistor logic.'],
    ['What is the supply voltage for TTL ICs?', '+5 V (4.75–5.25 V), pin 14 V_CC and pin 7 GND for 14-pin gate ICs.'],
    ['What happens if a TTL input is left open?', 'It behaves as logic HIGH, but it picks up noise; unused inputs should be tied to a definite level.'],
    ['Give the Boolean expressions of OR, AND and NOT.', 'Y = A + B, Y = A·B, Y = Ā.']],
  bench(root) {
    logicBench(root, 'exp23', [
      { id: 'dtl-or', name: 'OR (DTL)', dtl: true }, { id: 'dtl-and', name: 'AND (DTL)', dtl: true }, { id: 'dtl-not', name: 'NOT (DTL)', dtl: true },
      { id: 'ttl-or', name: 'OR (TTL 7432)', ic: '7432', target: 'OR', demo: [['t.+5V', 'ic7432.14'], ['t.GND', 'ic7432.7'], ['t.A', 'ic7432.1'], ['t.B', 'ic7432.2'], ['ic7432.3', 't.L1']] },
      { id: 'ttl-and', name: 'AND (TTL 7408)', ic: '7408', target: 'AND', demo: [['t.+5V', 'ic7408.14'], ['t.GND', 'ic7408.7'], ['t.A', 'ic7408.1'], ['t.B', 'ic7408.2'], ['ic7408.3', 't.L1']] },
      { id: 'ttl-not', name: 'NOT (TTL 7404)', ic: '7404', target: 'NOT', demo: [['t.+5V', 'ic7404.14'], ['t.GND', 'ic7404.7'], ['t.A', 'ic7404.1'], ['ic7404.2', 't.L1']] }]);
  }
});
EXPS.push({
  no: 24, id: 'exp24', group: 'Digital electronics', short: 'NOR and NAND gates (DTL & TTL)',
  title: 'Logic gates NOR and NAND using DTL and TTL',
  aim: 'To study logic gates NOR and NAND by using DTL and TTL.',
  apparatus: ['Digital trainer kit', 'Diodes 1N4148, resistors (1 kΩ, 2.2 kΩ, 4.7 kΩ, 10 kΩ), transistor BC547', 'ICs 74LS02 (NOR) and 74LS00 (NAND)', 'Connecting wires'],
  theory: String.raw`
<h2>NOR and NAND gates</h2>
<p>A NOR gate is an OR gate followed by a NOT gate: \(Y = \overline{A+B}\). A NAND gate is an AND gate followed by a NOT gate: \(Y = \overline{A\cdot B}\).</p>
<table><tr><th>A</th><th>B</th><th>NOR</th><th>NAND</th></tr><tr><td>0</td><td>0</td><td>1</td><td>1</td></tr><tr><td>0</td><td>1</td><td>0</td><td>1</td></tr><tr><td>1</td><td>0</td><td>0</td><td>1</td></tr><tr><td>1</td><td>1</td><td>0</td><td>0</td></tr></table>
<h2>DTL circuits</h2>
<p><b>DTL NOR:</b> a diode OR circuit (with a 10 kΩ resistor to ground) drives the base of a transistor inverter through a 4.7 kΩ resistor. If any input is HIGH the transistor saturates and Y is LOW.</p>
<p><b>DTL NAND:</b> a diode AND circuit (4.7 kΩ pull-up) drives the transistor base through a level-shifting diode D3 (base returned to ground through 10 kΩ). When any input is LOW, the AND point is at about 0.7 V; D3 and the base–emitter junction together need about 1.4 V, so the transistor stays off and Y is HIGH. Only when both inputs are HIGH does current flow through D3 into the base, saturating the transistor and making Y LOW. This is the classic DTL NAND gate.</p>` + TTL_THEORY,
  procedure: String.raw`
<ol class="steps">
<li><b>DTL NOR:</b> build the diode OR stage followed by the transistor inverter. Apply all four input combinations and note the LED and output voltage.</li>
<li><b>DTL NAND:</b> build the diode AND stage with the level-shifting diode and the transistor inverter. Verify its truth table.</li>
<li><b>TTL NOR:</b> insert 7402, power it (pin 14 → +5 V, pin 7 → GND), connect A and B to pins 2 and 3 and pin 1 to the LED. Verify the truth table.</li>
<li><b>TTL NAND:</b> insert 7400, connect A, B to pins 1, 2 and pin 3 to the LED. Verify the truth table.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> note the different pin arrangement of the 7402: the output of gate 1 is pin 1, not pin 3.</div>`,
  precautions: LOGIC_PREC,
  errors: String.raw`<li>Slow switching and non-ideal levels in DTL.</li><li>Wrong pin identification (especially 7402).</li>`,
  viva: [
    ['Why are NAND and NOR called universal gates?', 'Any Boolean function (and therefore NOT, AND, OR) can be built using only NAND gates or only NOR gates.'],
    ['Write the Boolean expressions for NOR and NAND.', 'Y = (A + B)′ and Y = (A·B)′.'],
    ['What is the function of D3 in the DTL NAND gate?', 'It shifts the voltage level so that the transistor stays off when the AND point is at about 0.7 V (logic 0).'],
    ['Which pins are the output of gate 1 in 7400 and 7402?', 'Pin 3 for 7400; pin 1 for 7402.'],
    ['State De Morgan’s theorems.', '(A + B)′ = A′·B′ and (A·B)′ = A′ + B′.'],
    ['Why is DTL replaced by TTL?', 'TTL is faster, has better noise margin and fan-out, and is available in compact ICs.']],
  bench(root) {
    logicBench(root, 'exp24', [
      { id: 'dtl-nor', name: 'NOR (DTL)', dtl: true }, { id: 'dtl-nand', name: 'NAND (DTL)', dtl: true },
      { id: 'ttl-nor', name: 'NOR (TTL 7402)', ic: '7402', target: 'NOR', demo: [['t.+5V', 'ic7402.14'], ['t.GND', 'ic7402.7'], ['t.A', 'ic7402.2'], ['t.B', 'ic7402.3'], ['ic7402.1', 't.L1']] },
      { id: 'ttl-nand', name: 'NAND (TTL 7400)', ic: '7400', target: 'NAND', demo: [['t.+5V', 'ic7400.14'], ['t.GND', 'ic7400.7'], ['t.A', 'ic7400.1'], ['t.B', 'ic7400.2'], ['ic7400.3', 't.L1']] }]);
  }
});
EXPS.push({
  no: 25, id: 'exp25', group: 'Digital electronics', short: 'NAND and NOR as universal gates',
  title: 'Verification of NAND and NOR gates as universal gates',
  aim: 'To verify NAND and NOR gates are universal gates.',
  apparatus: ['Digital trainer kit', 'IC 74LS00 (quad NAND)', 'IC 74LS02 (quad NOR)', 'Connecting wires'],
  theory: String.raw`
<h2>Universal gates</h2>
<p>A gate is called universal if every Boolean function (in particular NOT, AND and OR) can be realised using only that type of gate. NAND and NOR are universal.</p>
<h3>Using NAND only</h3>
<ul><li><b>NOT:</b> join both inputs: \(\overline{A\cdot A} = \bar A\).</li>
<li><b>AND:</b> NAND followed by a NAND used as NOT: \(\overline{\overline{A\cdot B}} = A\cdot B\).</li>
<li><b>OR:</b> invert each input with a NAND, then NAND them: \(\overline{\bar A\cdot\bar B} = A + B\) (De Morgan).</li></ul>
<h3>Using NOR only</h3>
<ul><li><b>NOT:</b> join both inputs: \(\overline{A+A} = \bar A\).</li>
<li><b>OR:</b> NOR followed by NOR used as NOT: \(\overline{\overline{A+B}} = A + B\).</li>
<li><b>AND:</b> invert each input with a NOR, then NOR them: \(\overline{\bar A+\bar B} = A\cdot B\).</li></ul>` + TTL_THEORY,
  procedure: String.raw`
<ol class="steps">
<li>Insert IC 7400 in the trainer and connect pin 14 to +5 V and pin 7 to GND.</li>
<li>Build NOT, AND and OR gates from NAND gates as in the diagrams, one at a time. For each, verify the truth table with the switches and the LED.</li>
<li>Replace the IC by 7402 and build NOT, OR and AND gates from NOR gates. Verify each truth table.</li>
<li>Conclude that NAND and NOR are universal gates.</li></ol>
<div class="vnote"><b>On the virtual bench:</b> you may use any gates of the IC: the bench checks the logic of your circuit, not the exact pins. Press <i>Show me</i> to see one correct wiring.</div>`,
  precautions: LOGIC_PREC,
  errors: String.raw`<li>Wrong pin connections; an output joined to another output.</li><li>Unconnected inputs float HIGH and give wrong results.</li>`,
  viva: [
    ['What is a universal gate?', 'A gate from which all other gates (NOT, AND, OR) can be built.'],
    ['How do you make NOT from NAND?', 'Join both inputs of the NAND gate together.'],
    ['How many NAND gates are needed for an OR gate?', 'Three: two as inverters and one NAND.'],
    ['How many NOR gates are needed for an AND gate?', 'Three: two as inverters and one NOR.'],
    ['Which theorem is used to make OR from NAND?', 'De Morgan’s theorem: (A′·B′)′ = A + B.'],
    ['Why are universal gates useful in industry?', 'Only one type of gate needs to be manufactured; any circuit can be built from it.']],
  bench(root) {
    const N = 'ic7400', R = 'ic7402', pw = ic => [['t.+5V', ic + '.14'], ['t.GND', ic + '.7']];
    logicBench(root, 'exp25', [
      { id: 'u-nand-not', name: 'NOT from NAND', ic: '7400', target: 'NOT', demo: [...pw(N), ['t.A', N + '.1'], [N + '.1', N + '.2'], [N + '.3', 't.L1']] },
      { id: 'u-nand-and', name: 'AND from NAND', ic: '7400', target: 'AND', demo: [...pw(N), ['t.A', N + '.1'], ['t.B', N + '.2'], [N + '.3', N + '.4'], [N + '.4', N + '.5'], [N + '.6', 't.L1']] },
      { id: 'u-nand-or', name: 'OR from NAND', ic: '7400', target: 'OR', demo: [...pw(N), ['t.A', N + '.1'], [N + '.1', N + '.2'], ['t.B', N + '.4'], [N + '.4', N + '.5'], [N + '.3', N + '.9'], [N + '.6', N + '.10'], [N + '.8', 't.L1']] },
      { id: 'u-nor-not', name: 'NOT from NOR', ic: '7402', target: 'NOT', demo: [...pw(R), ['t.A', R + '.2'], [R + '.2', R + '.3'], [R + '.1', 't.L1']] },
      { id: 'u-nor-or', name: 'OR from NOR', ic: '7402', target: 'OR', demo: [...pw(R), ['t.A', R + '.2'], ['t.B', R + '.3'], [R + '.1', R + '.5'], [R + '.5', R + '.6'], [R + '.4', 't.L1']] },
      { id: 'u-nor-and', name: 'AND from NOR', ic: '7402', target: 'AND', demo: [...pw(R), ['t.A', R + '.2'], [R + '.2', R + '.3'], ['t.B', R + '.5'], [R + '.5', R + '.6'], [R + '.1', R + '.8'], [R + '.4', R + '.9'], [R + '.10', 't.L1']] }]);
  }
});

/* ================= record file guide & viva quiz ================= */
let CUR_EXP = null;
const PCT = String.raw`Percentage error = \(\dfrac{|\text{experimental} - \text{standard}|}{\text{standard}}\times 100\%\)`;
const REC = {
  exp1: { theory: String.raw`A thin air film is formed between a plano-convex lens (radius \(R\)) and a plane glass plate. In reflected monochromatic light the \(n\)-th dark ring satisfies \(2t = n\lambda\) with \(t = r^2/2R\), so \(D_n^2 = 4n\lambda R\).`, formula: String.raw`\[\lambda = \frac{D_{n+p}^2 - D_n^2}{4pR} = \frac{\text{slope of } D^2\text{–}n\text{ graph}}{4R}\]`, diagram: 'Draw the Newton’s rings arrangement (sodium lamp, convex lens, glass plate at 45°, plano-convex lens on the plane glass plate, travelling microscope) and sketch the ring system with the cross-wire.', calc: 'D = (right reading − left reading); find D² for each ring. Plot D² against n and find the slope. λ = slope/4R. Also calculate λ from pairs of rings (n, n + p) and take the mean.', result: 'The wavelength of the given sodium light = ______ nm (standard value 589.3 nm).', error: String.raw`\[\frac{\delta\lambda}{\lambda} = \frac{2D_{n+p}\,\delta D + 2D_n\,\delta D}{D_{n+p}^2 - D_n^2} + \frac{\delta R}{R}\]with \(\delta D\) = 2 × L.C. = 0.002 cm (two readings per diameter).` },
  exp2: { theory: String.raw`For normal incidence on a plane transmission grating with grating element \(a+b = 2.54/N\) cm, the principal maxima of order \(n\) appear where \((a+b)\sin\theta = n\lambda\).`, formula: String.raw`\[\lambda = \frac{(a+b)\sin\theta}{n},\qquad \theta = \tfrac12(\text{left reading} \sim \text{right reading})\]`, diagram: 'Draw the top view of the spectrometer (collimator, grating on the prism table normal to the beam, telescope positions on the left and right), and the 45° method of setting normal incidence.', calc: 'For each line: 2θ = difference of left and right readings (each vernier), θ = mean/2. λ = (a + b) sin θ / n. Take the mean of the first and second orders.', result: 'The wavelengths of the mercury lines are: violet ___ nm, blue ___ nm, green ___ nm, yellow ___ nm (compare with the standard values).', error: String.raw`\[\frac{\delta\lambda}{\lambda} = \cot\theta\;\delta\theta,\qquad \delta\theta = 1' = 2.9\times10^{-4}\ \text{rad}\]` },
  exp3: { theory: String.raw`Two wavelengths are just resolved (Rayleigh) when the maximum of one falls on the first minimum of the other. For a prism with effective base \(t\): \(\lambda/d\lambda = t\,d\mu/d\lambda\); with Cauchy’s relation \(d\mu/d\lambda = -2B/\lambda^3\).`, formula: String.raw`\[\text{R.P.} = t\left|\frac{d\mu}{d\lambda}\right| = \frac{2Bt}{\lambda^3},\qquad t = \frac{2a\sin(A/2)}{\cos i}\]`, diagram: 'Draw the spectrometer with the prism at minimum deviation and the adjustable rectangular slit in the incident beam; sketch the D lines resolved and just resolved.', calc: 'Find A and μ for two or more lines; B = (μ₁ − μ₂)/(1/λ₁² − 1/λ₂²). dμ/dλ = 2B/λ³ at 589.3 nm. From the just-resolving width a, find t and R.P. = t dμ/dλ. Compare with λ/dλ = 987.', result: 'Resolving power of the prism = ______ ; at just resolution t dμ/dλ = ____, compared with λ/dλ = 987.', error: String.raw`\[\frac{\delta(\text{R.P.})}{\text{R.P.}} = \frac{\delta a}{a} + \frac{\delta B}{B}\]` },
  exp4: { theory: String.raw`For a grating with \(N\) lines exposed, two lines in the \(n\)-th order are just resolved when \(\lambda/d\lambda = nN\).`, formula: String.raw`\[\text{R.P.} = nN = n\,w\,\frac{N_0}{2.54},\qquad \frac{\lambda}{d\lambda} = \frac{589.3}{0.597}\approx 987\]`, diagram: 'Draw the spectrometer with the grating normal to the beam and the adjustable slit in front of it; sketch the D lines resolved / just resolved.', calc: 'For each order: N = w × lines per cm; R.P. = nN. Compare with λ/dλ.', result: 'The experimental resolving power of the grating = ______ (theoretical λ/dλ = 987). Resolving power with the full beam = ______.', error: String.raw`\[\frac{\delta(\text{R.P.})}{\text{R.P.}} = \frac{\delta w}{w}\]` },
  exp5: { theory: String.raw`At minimum deviation the ray passes symmetrically through the prism, so \(r = A/2\) and \(i = (A+\delta_m)/2\). The angle of the prism is half the angle between the beams reflected from its two faces.`, formula: String.raw`\[\mu = \frac{\sin\frac{A+\delta_m}{2}}{\sin\frac{A}{2}}\]`, diagram: 'Draw the spectrometer for (a) the angle of the prism by reflection and (b) minimum deviation (direct position and deviated position).', calc: 'A = ½ (difference of the two reflected-image readings). δm = (minimum deviation reading ∼ direct reading). Calculate μ for each line and plot μ against λ.', result: 'The refractive index of the prism material for violet ___, blue ___, green ___, yellow ___, red ___ ; μ decreases as λ increases.', error: String.raw`\[\delta\mu = \frac{\cos\frac{A+\delta_m}{2}}{2\sin\frac A2}\,\delta(\delta_m) + \frac{\sin\frac{\delta_m}{2}}{2\sin^2\frac A2}\,\delta A\]` },
  exp6: { theory: String.raw`Cauchy’s formula \(\mu = A + B/\lambda^2\) describes normal dispersion. μ for each line is found by the minimum deviation method; a graph of μ against \(1/\lambda^2\) is a straight line.`, formula: String.raw`\[\mu = A + \frac{B}{\lambda^2}:\quad A = \text{intercept},\ B = \text{slope}\]`, diagram: 'Draw the spectrometer for the angle of the prism and minimum deviation; draw the μ vs 1/λ² graph.', calc: 'Find μ for each line. Calculate 1/λ² (in µm⁻²). Plot μ vs 1/λ², draw the best line: intercept = A, slope = B (µm² → ×10⁻⁸ cm²).', result: 'Cauchy’s constants: A = ______ , B = ______ cm².', error: 'Find the standard errors of the slope and intercept from the scatter of points about the line (least-squares fit).' },
  exp7: { theory: String.raw`Optically active substances rotate the plane of polarisation. The rotation is proportional to the length \(l\) (dm) and the concentration \(c\) (g/cc). Laurent’s half-shade plate makes the setting of the analyser precise (equal darkness).`, formula: String.raw`\[S = \frac{\theta}{l\,c} = \frac{\text{slope of } \theta\text{–}c\text{ graph}}{l}\]`, diagram: 'Draw the polarimeter: sodium lamp, polariser, Laurent half-shade plate, tube with solution, analyser with circular scale, eyepiece; sketch the half-shade field.', calc: 'θ = (mean reading with solution) − (mean reading with water). S = θ/(lc) for each solution. Plot θ against c; S = slope/l. Read the unknown concentration from the graph.', result: 'Specific rotation of cane sugar = ______ ° dm⁻¹ (g/cc)⁻¹ (standard 66.5 at 20 °C). Concentration of the unknown solution = ____ g/cc.', error: String.raw`\[\frac{\delta S}{S} = \frac{\delta\theta}{\theta} + \frac{\delta l}{l} + \frac{\delta c}{c},\qquad \delta\theta = 2\times 0.1^\circ\]` },
  exp8: { theory: String.raw`A charged oil drop falling freely reaches terminal velocity \(v\) given by Stokes’ law, which gives its radius. When the electric force balances the weight, \(qV/d = \frac43\pi a^3(\rho-\sigma)g\).`, formula: String.raw`\[a = \sqrt{\frac{9\eta v}{2(\rho-\sigma)g}},\qquad q = \frac{4\pi a^3(\rho-\sigma)g\,d}{3V},\qquad e = \frac{q}{n}\]`, diagram: 'Draw the Millikan apparatus: parallel plates with hole, atomiser, light source, microscope with graticule, high-voltage supply with reversing switch.', calc: 'For each drop: v = s/t̄, a (with Cunningham correction), q. Divide each q by the nearest integer n; e = mean of q/n.', result: 'The charge of an electron e = ______ × 10⁻¹⁹ C (standard 1.602 × 10⁻¹⁹ C).', error: String.raw`\[\frac{\delta q}{q} = \frac32\,\frac{\delta t}{t} + \frac{\delta V}{V} + \frac32\,\frac{\delta \eta}{\eta}\]` },
  exp9: { theory: String.raw`Electrons moving radially in a cylindrical diode are bent by an axial magnetic field. At the critical field \(B_c\) they just graze the anode (radius \(a\)) and the anode current falls sharply.`, formula: String.raw`\[\frac em = \frac{8V}{B_c^2a^2},\qquad B_c = \frac{\mu_0 N I_c}{\sqrt{L^2 + D^2}}\]`, diagram: 'Draw the circuit: magnetron valve inside the solenoid; anode circuit (supply, voltmeter, milliammeter); solenoid circuit (supply, ammeter, rheostat).', calc: 'For each anode voltage plot Iₐ vs Iₛ; I_c at the middle of the fall. B_c = µ₀NI_c/√(L² + D²); e/m = 8V/(B_c²a²). Take the mean.', result: 'Specific charge of electron e/m = ______ × 10¹¹ C kg⁻¹ (standard 1.759 × 10¹¹ C kg⁻¹).', error: String.raw`\[\frac{\delta(e/m)}{e/m} = \frac{\delta V}{V} + 2\frac{\delta I_c}{I_c} + 2\frac{\delta a}{a}\]` },
  exp10: { theory: String.raw`In crossed electric and magnetic fields the electron beam is undeflected when \(eE = evB\), giving \(v = E/B\). The electric deflection alone is \(y = (eE/m)\,lL/v^2\).`, formula: String.raw`\[\frac em = \frac{yV}{B^2\,l\,L\,d},\qquad B = B_H\tan\theta\]`, diagram: 'Draw the CRT with deflecting plates, the bar magnets on either side, and the deflection magnetometer in the position of the tube.', calc: 'E = V/d; B = B_H tan θ̄ (θ̄ = mean of four readings); e/m = yE/(B² l L).', result: 'e/m = ______ × 10¹¹ C kg⁻¹ (standard 1.759 × 10¹¹ C kg⁻¹).', error: String.raw`\[\frac{\delta(e/m)}{e/m} = \frac{\delta y}{y} + \frac{\delta V}{V} + 2\frac{\delta\theta}{\sin\theta\cos\theta}\]` },
  exp11: { theory: String.raw`A G.M. counter counts every ionising particle with a pulse of the same size. The count rate is almost independent of voltage in the plateau region. Radioactive counts follow Poisson statistics: \(\sigma = \sqrt{\bar N}\).`, formula: String.raw`\[\text{Slope} = \frac{R_2-R_1}{R_1}\cdot\frac{100}{V_2-V_1}\times100\%,\qquad \chi^2 = \sum\frac{(N_i - \bar N)^2}{\bar N}\]`, diagram: 'Draw the block diagram: G.M. tube in lead castle with source, EHT supply, scaler/timer; draw the characteristic curve showing starting potential, threshold, plateau and discharge region.', calc: 'Plot count rate vs voltage; read V₁, V₂, R₁, R₂; calculate plateau length, slope and operating voltage. For reliability: N̄, σ, √N̄, χ² and P.', result: 'Starting potential ___ V, plateau from ___ V to ___ V, slope ___ % per 100 V, operating voltage ___ V. χ² = ___ (P = ___): the counter is reliable / not reliable.', error: String.raw`Statistical error of a count \(N\): \(\pm\sqrt N\), i.e. fractional error \(1/\sqrt N\).` },
  exp12: { theory: String.raw`β-particles are absorbed nearly exponentially by thin absorbers: \(I = I_0e^{-\mu x}\). The background count is subtracted before taking logarithms.`, formula: String.raw`\[\ln(R - R_b) = \ln R_0 - \mu x,\qquad \mu_m = \frac{\mu}{\rho},\qquad x_{1/2} = \frac{0.693}{\mu}\]`, diagram: 'Draw the G.M. counter set-up with the source, aluminium absorbers between source and window, EHT and scaler; draw the ln(R − R_b) vs x graph.', calc: 'Correct each count rate for background. Plot ln(R − R_b) vs x; μ = −slope. μ/ρ with ρ = 2.70 g cm⁻³; x½ = 0.693/μ.', result: 'Linear absorption coefficient of β-particles in aluminium μ = ______ cm⁻¹; mass absorption coefficient = ____ cm² g⁻¹; half thickness = ____ mm.', error: String.raw`Each count has error \(\sqrt N\); \(\delta\ln R = 1/\sqrt N\). The error in μ is the standard error of the slope.` },
  exp13: { theory: String.raw`In a series LCR circuit the current is maximum when \(X_L = X_C\) (resonance). The sharpness is measured by the quality factor, found from the half-power frequencies.`, formula: String.raw`\[f_0 = \frac{1}{2\pi\sqrt{LC}},\qquad Q = \frac{f_0}{f_2 - f_1} = \frac{1}{R}\sqrt{\frac LC}\]`, diagram: 'Draw the circuit: signal generator, R, L, C and AC milliammeter in series (AC voltmeter across the generator).', calc: 'Plot I vs f. f₀ at maximum current I₀. Draw a line at I₀/√2 to get f₁ and f₂. Q = f₀/(f₂ − f₁). Compare with theory.', result: 'Resonant frequency f₀ = ____ Hz (theoretical ____ Hz); quality factor Q = ____ (theoretical ____).', error: String.raw`\[\frac{\delta Q}{Q} = \frac{\delta f_0}{f_0} + \frac{\delta f_1 + \delta f_2}{f_2 - f_1}\]` },
  exp14: { theory: String.raw`The CRO displays voltage against time. Voltage: \(V_{pp} = h\times\) VOLTS/DIV; frequency: \(T = L\times\) TIME/DIV \(/n\). Comparing with a voltmeter and a generator calibrates the CRO.`, formula: String.raw`\[V_{rms} = \frac{h\,S_V}{2\sqrt2},\qquad f = \frac{n}{L\,S_T},\qquad K = \frac{\text{standard value}}{\text{CRO value}}\]`, diagram: () => SCH14(), calc: 'For each reading find V_rms(CRO) and K_V; for each frequency find f(CRO) and K_f. Plot CRO values against standard values.', result: 'Calibration factor for voltage K_V = ____; for frequency K_f = ____.', error: String.raw`\[\frac{\delta V}{V} = \frac{\delta h}{h},\qquad \frac{\delta f}{f} = \frac{\delta L}{L},\qquad \delta h = \delta L = 0.1\ \text{div}\]` },
  exp15: { theory: String.raw`Two sinusoidal voltages on the X and Y plates produce a Lissajous figure. When it is stationary, the frequency ratio equals the ratio of tangencies on a horizontal and a vertical side.`, formula: String.raw`\[\frac{f_y}{f_x} = \frac{n_h}{n_v}\quad\Rightarrow\quad f_y = f_x\frac{n_h}{n_v}\]`, diagram: () => SCH15(), calc: 'For each stationary figure note f_x, n_h, n_v; f_y = f_x n_h/n_v. Take the mean. Sketch each figure.', result: 'Frequency of the unknown source = ______ Hz.', error: String.raw`\[\frac{\delta f_y}{f_y} = \frac{\delta f_x}{f_x}\ \ (\delta f_x = \text{smallest change of } f_x \text{ that moves the figure})\]` },
  exp16: { theory: String.raw`A source of internal resistance \(R_s\) delivers maximum power to a load \(R_L = R_s\); then \(P_{max} = E^2/4R_s\) and the efficiency is 50%.`, formula: String.raw`\[P = VI = \frac{E^2R_L}{(R_s + R_L)^2},\qquad P_{max}\ \text{at}\ R_L = R_s\]`, diagram: () => SCH16(), calc: 'P = VI for each R_L. Plot P vs R_L; read R_L at maximum and compare with R_s. Calculate η = R_L/(R_s + R_L).', result: 'Maximum power ____ mW is obtained at R_L = ____ Ω ≈ R_s = ____ Ω. Hence the theorem is verified.', error: String.raw`\[\frac{\delta P}{P} = \frac{\delta V}{V} + \frac{\delta I}{I}\]` },
  exp17: { theory: String.raw`A linear network seen from terminals A–B equals \(V_{Th}\) in series with \(R_{Th}\) (Thevenin), or \(I_N\) in parallel with \(R_N = R_{Th}\) (Norton).`, formula: String.raw`\[I_L = \frac{V_{Th}}{R_{Th}+R_L} = I_N\frac{R_N}{R_N + R_L}\]`, diagram: () => netSchem('s1') + netSchem('s2') + netSchem('s3') + netSchem('s4'), calc: 'Measure V_Th (open circuit), R_Th (source shorted), I_N (A–B shorted). Calculate I_L from both equivalents for each R_L and compare with the measured I_L.', result: 'Measured load currents agree with the values from Thevenin’s and Norton’s equivalent circuits within ___ %. Hence both theorems are verified.', error: String.raw`\[\frac{\delta I_L}{I_L} = \frac{\delta V_{Th}}{V_{Th}} + \frac{\delta R_{Th} + \delta R_L}{R_{Th} + R_L}\]` },
  exp18: { theory: String.raw`In CB configuration the input is between emitter and base, the output between collector and base. \(I_C\approx\alpha I_E\) and is almost independent of \(V_{CB}\).`, formula: String.raw`\[r_i = \frac{\Delta V_{EB}}{\Delta I_E},\quad r_o = \frac{\Delta V_{CB}}{\Delta I_C},\quad \alpha = \frac{\Delta I_C}{\Delta I_E}\]`, diagram: () => bjtSchem('CB', false) + bjtSchem('CB', true), calc: 'Plot the input and output characteristics. Find r_i from the slope of the input curve, r_o and α from the output curves.', result: 'For NPN (PNP): r_i = ___ Ω, r_o = ___ , α = ____.', error: String.raw`\[\frac{\delta r}{r} = \frac{\delta(\Delta V)}{\Delta V} + \frac{\delta(\Delta I)}{\Delta I}\]` },
  exp19: { theory: String.raw`In CE configuration the input is between base and emitter, the output between collector and emitter. \(I_C = \beta I_B\) in the active region.`, formula: String.raw`\[r_i = \frac{\Delta V_{BE}}{\Delta I_B},\quad r_o = \frac{\Delta V_{CE}}{\Delta I_C},\quad \beta = \frac{\Delta I_C}{\Delta I_B}\]`, diagram: () => bjtSchem('CE', false) + bjtSchem('CE', true), calc: 'Plot the input and output characteristics; find r_i, r_o and β (at V_CE = 5 V).', result: 'For NPN (PNP): r_i = ___ kΩ, r_o = ___ kΩ, β = ___.', error: String.raw`\[\frac{\delta\beta}{\beta} = \frac{\delta(\Delta I_C)}{\Delta I_C} + \frac{\delta(\Delta I_B)}{\Delta I_B}\]` },
  exp20: { theory: String.raw`In CC configuration the collector is common; input between base and collector, output between emitter and collector. \(I_E = (1+\beta)I_B\).`, formula: String.raw`\[\gamma = \frac{\Delta I_E}{\Delta I_B} = 1 + \beta,\quad r_o = \frac{\Delta V_{CE}}{\Delta I_E}\]`, diagram: () => bjtSchem('CC', false) + bjtSchem('CC', true), calc: 'Plot I_B vs V_CB (input) and I_E vs V_CE (output); find γ and r_o.', result: 'For NPN (PNP): γ = ___, r_o = ___.', error: String.raw`\[\frac{\delta\gamma}{\gamma} = \frac{\delta(\Delta I_E)}{\Delta I_E} + \frac{\delta(\Delta I_B)}{\Delta I_B}\]` },
  exp21: { theory: String.raw`A reverse-biased Zener diode keeps its voltage nearly constant at \(V_Z\) beyond breakdown; with a series resistor it regulates the voltage across a load.`, formula: String.raw`\[\text{Line reg.} = \frac{\Delta V_{out}}{\Delta V_{in}}\times100\%,\qquad \text{Load reg.} = \frac{V_{NL}-V_{FL}}{V_{FL}}\times100\%\]`, diagram: () => regSchem(true), calc: 'Plot V_out vs V_in and V_out vs I_L. Calculate line and load regulation from the flat parts.', result: 'Zener voltage ____ V; line regulation ____ %; load regulation ____ %.', error: String.raw`Changes of \(V_{out}\) are a few mV; error \(\approx \pm\) 1 digit (1 mV) of the voltmeter: \(\delta(\text{reg})/\text{reg} \approx 2\,\delta V/\Delta V_{out}\).` },
  exp22: { theory: String.raw`A three-terminal regulator (7805) keeps its output at +5 V when the input exceeds the output by the dropout voltage (≈ 2 V).`, formula: String.raw`\[\text{Line reg.} = \frac{\Delta V_{out}}{\Delta V_{in}}\times100\%,\quad \text{Load reg.} = \frac{V_{NL}-V_{FL}}{V_{FL}}\times100\%,\quad R_o = -\frac{\Delta V_{out}}{\Delta I_L}\]`, diagram: () => regSchem(false), calc: 'Plot V_out vs V_in and V_out vs I_L; calculate line regulation, load regulation, output resistance and dropout voltage.', result: 'Regulated output ____ V; dropout ____ V; line regulation ____ %; load regulation ____ %.', error: String.raw`Changes are of the order of the voltmeter resolution (1 mV): \(\delta(\text{reg})/\text{reg} \approx 2\,\delta V/\Delta V_{out}\).` },
  exp23: { theory: String.raw`OR: Y = A + B; AND: Y = A·B; NOT: Y = Ā. DTL gates use diodes (OR, AND) and a transistor inverter (NOT); TTL gates are ICs 7432, 7408, 7404.`, formula: String.raw`\[Y_{OR} = A + B,\quad Y_{AND} = A\cdot B,\quad Y_{NOT} = \bar A\]`, diagram: () => ['dtl-or', 'dtl-and', 'dtl-not', 'ttl-or', 'ttl-and', 'ttl-not'].map(gateSchem).join(''), calc: 'Write the observed truth table (LED state and output voltage) for each gate and compare with the theoretical truth table.', result: 'The truth tables of OR, AND and NOT gates are verified using DTL and TTL.', error: 'Compare the measured output levels with the standard TTL levels (V_OH ≥ 2.4 V, V_OL ≤ 0.4 V) and note the non-ideal DTL levels (≈ 0.7 V for logic 0 of the diode AND, ≈ 4.3 V for logic 1 of the diode OR).' },
  exp24: { theory: String.raw`NOR = OR followed by NOT; NAND = AND followed by NOT. DTL versions use a diode gate driving a transistor inverter; TTL ICs are 7402 and 7400.`, formula: String.raw`\[Y_{NOR} = \overline{A+B},\qquad Y_{NAND} = \overline{A\cdot B}\]`, diagram: () => ['dtl-nor', 'dtl-nand', 'ttl-nor', 'ttl-nand'].map(gateSchem).join(''), calc: 'Record the truth tables (LED and V_out) and compare with theory.', result: 'The truth tables of NOR and NAND gates are verified using DTL and TTL.', error: 'Compare the logic levels with the TTL standard levels; note the DTL levels.' },
  exp25: { theory: String.raw`NAND and NOR are universal: NOT, AND and OR can each be built using only NAND gates or only NOR gates (De Morgan’s theorems).`, formula: String.raw`\[\bar A = \overline{A\cdot A},\ \ A\cdot B = \overline{\overline{A\cdot B}},\ \ A+B = \overline{\bar A\cdot\bar B};\qquad \bar A = \overline{A+A},\ \ A+B = \overline{\overline{A+B}},\ \ A\cdot B = \overline{\bar A+\bar B}\]`, diagram: () => ['u-nand-not', 'u-nand-and', 'u-nand-or', 'u-nor-not', 'u-nor-or', 'u-nor-and'].map(gateSchem).join(''), calc: 'Record the truth table of each circuit built from NAND (7400) and NOR (7402) gates.', result: 'NOT, AND and OR gates were realised using only NAND gates and only NOR gates; hence NAND and NOR are universal gates.', error: 'Not applicable (digital): verify every row of each truth table.' }
};
function htmlToText(s) { return String(s).replace(/<br\s*\/?>/g, '\n').replace(/<\/(p|div|h4|tr|li)>/g, '\n').replace(/<\/t[dh]>/g, '\t').replace(/<sup>(.*?)<\/sup>/g, '^$1').replace(/<sub>(.*?)<\/sub>/g, '_$1').replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/\n{3,}/g, '\n\n').trim(); }
function snapshots(id) {
  const out = []; try { Object.keys(localStorage).filter(k => k.startsWith(LSP + 'snap.' + id + '.')).sort().forEach(k => { const v = JSON.parse(localStorage.getItem(k)); if (v && v.tsv && v.tsv.split('\n').length > 1) out.push(v); }); } catch (e) { }
  return out;
}
function tsvTable(tsv) {
  const lines = tsv.split('\n').map(l => l.split('\t')); const head = lines.shift();
  return `<div class="tscroll"><table class="obs"><thead><tr>${head.map(c => `<th>${esc(c)}</th>`).join('')}</tr></thead><tbody>${lines.map(r => `<tr>${r.map(c => `<td>${esc(c)}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
}
function mathToTeX(root) { // tag each rendered formula with its TeX so copies of the record keep the maths
  try { window.MathJax.startup.document.getMathItemsWithin(root).forEach(it => { if (it.typesetRoot && it.typesetRoot.setAttribute) it.typesetRoot.setAttribute('data-tex', it.display ? '\\[' + it.math + '\\]' : '\\(' + it.math + '\\)'); }); } catch (e) { }
}
function recordClone(root) {
  mathToTeX(root);
  const c = root.cloneNode(true);
  c.querySelectorAll('mjx-container').forEach(m => m.replaceWith(document.createTextNode(m.getAttribute('data-tex') || m.getAttribute('aria-label') || '')));
  c.querySelectorAll('.tip, .hint, .no-print').forEach(n => n.remove());
  c.querySelectorAll('textarea').forEach(t => { const d = document.createElement('div'); d.className = 'rec-note'; d.textContent = t.value; t.replaceWith(d); });
  return c;
}
function recordTab(e, body) {
  const R = REC[e.id] || {};
  const sec = (n, title, hint, html) => `<section><h3>${n}. ${title} <span class="hint">${hint || ''}</span></h3>${html}</section>`;
  const tabs = snapshots(e.id); const res = LS.get('snapres.' + e.id, '');
  const stu = Object.assign({ name: '', roll: '' }, LS.get('student', {}));
  if (VPL_USER) { if (!stu.name && VPL_USER.name) stu.name = VPL_USER.name; if (!stu.roll && VPL_USER.roll) stu.roll = VPL_USER.roll; }
  const today = new Date(); const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  let date = LS.get('recdate.' + e.id, iso(today)); let note = LS.get('recnote.' + e.id, '');
  const canSubmit = !!(VPL_CFG.submit && VPL_USER && VPL_USER.role === 'student');
  const diag = typeof R.diagram === 'function' ? `<div class="rec-diag">${R.diagram()}</div><p class="tip">Copy this circuit diagram neatly in pencil, with + / − marks and meter ranges.</p>` : `<p>${R.diagram || ''}</p>`;
  const obs = tabs.length ? tabs.map(t => `<h4 style="margin:10px 0 6px;font-size:14px">${t.title}</h4>${tsvTable(t.tsv)}`).join('') : `<p class="tip">Your observation tables will appear here after you take readings on the Lab bench. Draw the tables in your file with the same headings, and write the least count of each instrument above the table.</p>`;
  const html = `<div class="rec prose">
    <div class="rec-guide"><p>The record file (practical copy) carries <b>20% of the marks</b> in the PHY202 practical examination, and error analysis another 10%. Write each experiment under these headings. Everything specific to this experiment is filled in below; copy it in your own handwriting and complete the blanks with your own readings.</p>
    <div class="marks"><div><b>20%</b><span>Record file</span></div><div><b>50%</b><span>Performing the experiment</span></div><div><b>10%</b><span>Error analysis</span></div><div><b>20%</b><span>Viva</span></div></div></div>
    <div class="stu-form no-print"><label>Your name<input id="stuName" autocomplete="name" value="${esc(stu.name)}"></label><label>Roll no.<input id="stuRoll" value="${esc(stu.roll)}"></label><label>Date of experiment<input id="stuDate" type="date" value="${esc(date)}"></label></div>
    <div class="rec-doc">
    ${sec(1, 'Heading', 'top of a new page', `<p><b>Experiment No. ${e.no}</b> &nbsp;·&nbsp; Date: <span data-f="date">__________</span><br><b>${e.title}</b><br><span class="small">Name: <span data-f="name">__________</span> &nbsp;·&nbsp; Roll no.: <span data-f="roll">______</span> &nbsp;·&nbsp; B.Sc. second year, Kamala Science Campus</span></p>`)}
    ${sec(2, 'Aim', '', `<p>${e.aim}</p>`)}
    ${sec(3, 'Apparatus required', '', `<ul>${e.apparatus.map(a => `<li>${a}</li>`).join('')}</ul>`)}
    ${sec(4, 'Theory', 'short: principle + working formula + symbols', `<p>${R.theory || ''}</p><div class="eq">${R.formula || ''}</div><p class="tip">Explain every symbol in the formula with its unit.</p>`)}
    ${sec(5, 'Diagram', 'left-hand (blank) page', diag)}
    ${sec(6, 'Observations', 'least counts first, then tables', obs)}
    ${sec(7, 'Calculations', 'show one full calculation, then tabulate', `<p>${R.calc || ''}</p>${res ? `<div class="result" style="white-space:pre-wrap">${esc(res)}</div>` : ''}`)}
    ${sec(8, 'Result', 'box it', `<p class="note">${R.result || ''}</p>`)}
    ${sec(9, 'Error analysis', '10% of the marks', `<p>Maximum probable (proportional) error:</p><div class="eq">${R.error || ''}</div><p>${PCT}</p><p class="tip">Put in your least counts and readings, calculate the maximum error, and compare it with the percentage error from the standard value. A good answer also names the largest source of error.</p>`)}
    ${sec(10, 'Precautions', '', `<ul>${e.precautions}</ul>`)}
    ${sec(11, 'Sources of error & conclusion', '', `<ul>${e.errors}</ul><p class="tip">End with one or two sentences: was the aim achieved, and how close is your result to the standard value?</p>`)}
    ${sec(12, 'Your error analysis and conclusion', 'in your own words', `<textarea id="recNote" rows="6" placeholder="Work out the maximum probable error with your own least counts and readings, then write your conclusion." aria-label="Your error analysis and conclusion"></textarea><div class="print-only rec-note" id="recNoteP"></div>`)}
    </div>
    <div class="rec-actions no-print">
      <button type="button" class="btn pri" id="copyRec">Copy the whole record as text</button>
      <button type="button" class="btn" id="printRec">Print / save as PDF</button>
      <button type="button" class="btn" id="dlRec">Download record (.html)</button>
      ${canSubmit ? '<button type="button" class="btn pri" id="subRec">Submit to my lecturer</button>' : ''}
      ${VPL_USER && VPL_USER.role === 'lecturer' && VPL_CFG.lecturer ? `<a class="btn" href="${esc(VPL_CFG.lecturer)}">Open student submissions</a>` : ''}
    </div>
    <p class="small muted no-print" id="copyMsg" role="status"></p>
    <textarea id="recTxt" class="no-print" hidden rows="8" style="width:100%;font:12px var(--f-mono)" aria-label="Record text"></textarea>
  </div>`;
  body.innerHTML = html; typeset(body);
  const msg = $('#copyMsg', body), noteEl = $('#recNote', body), noteP = $('#recNoteP', body);
  noteEl.value = note; noteP.textContent = note;
  const fmtDate = d => { const m = /^(\d{4})-(\d\d)-(\d\d)$/.exec(d || ''); return m ? `${m[3]}/${m[2]}/${m[1]}` : '__________'; };
  function fill() { body.querySelectorAll('[data-f]').forEach(n => { const k = n.dataset.f; n.textContent = k === 'date' ? fmtDate(date) : (stu[k] || (k === 'roll' ? '______' : '__________')); }); }
  fill();
  $('#stuName', body).addEventListener('input', ev => { stu.name = ev.target.value.trim(); LS.set('student', stu); fill(); });
  $('#stuRoll', body).addEventListener('input', ev => { stu.roll = ev.target.value.trim(); LS.set('student', stu); fill(); });
  $('#stuDate', body).addEventListener('input', ev => { date = ev.target.value; LS.set('recdate.' + e.id, date); fill(); });
  noteEl.addEventListener('input', () => { note = noteEl.value; noteP.textContent = note; LS.set('recnote.' + e.id, note); });
  const docEl = () => body.querySelector('.rec-doc');
  const asText = () => htmlToText(recordClone(docEl()).innerHTML.replace(/<svg[\s\S]*?<\/svg>/g, '[circuit diagram]'));
  $('#copyRec', body).addEventListener('click', () => {
    const txt = asText();
    const fallback = () => { const t = $('#recTxt', body); t.hidden = false; t.value = txt; t.select(); msg.textContent = 'Select all and copy.'; };
    try { navigator.clipboard.writeText(txt).then(() => msg.textContent = 'Copied.', fallback); } catch (err) { fallback(); }
  });
  $('#printRec', body).addEventListener('click', () => { document.body.classList.add('printing-record'); try { window.print(); if (!VPL_CFG.portal && location.protocol !== 'file:') msg.textContent = 'If no print window opened, use Download record and print that file.'; } catch (err) { msg.textContent = 'Printing is not available here. Use Download record, then print that file.'; } setTimeout(() => document.body.classList.remove('printing-record'), 600); });
  $('#dlRec', body).addEventListener('click', () => {
    const c = recordClone(docEl());
    const css = [...document.querySelectorAll('style')].map(x => x.outerHTML).join('');
    const title = `Record – Experiment ${e.no} – ${stu.name || 'student'}`;
    const doc = `<!doctype html><html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${esc(title)}</title>${css}<style>body{background:#fff;color:#15212a;margin:0}.rec{max-width:860px;margin:0 auto;padding:28px 20px}.rec-note{white-space:pre-wrap;border:1px solid #d2dadd;border-radius:6px;padding:8px 10px;min-height:3em}.print-only{display:none}</style><script>window.MathJax={tex:{inlineMath:[['\\\\(','\\\\)']],displayMath:[['\\\\[','\\\\]']]},svg:{fontCache:'local'}};<\/script><script src="https://cdn.jsdelivr.net/npm/mathjax@3.2.2/es5/tex-svg.js" async><\/script></head><body><div class="rec prose">${c.innerHTML}</div></body></html>`;
    try {
      const a = h('a', { href: URL.createObjectURL(new Blob([doc], { type: 'text/html' })), download: `record-exp${String(e.no).padStart(2, '0')}${stu.roll ? '-roll' + stu.roll.replace(/[^\w-]/g, '') : ''}.html` });
      document.body.append(a); a.click(); a.remove(); msg.textContent = 'Downloaded. Open the file in any browser to read or print it.';
    } catch (err) { msg.textContent = 'Download is not available here.'; }
  });
  if (canSubmit) {
    const sub = $('#subRec', body);
    const showStatus = j => { if (!j || !j.submitted_at) return; let t = `Submitted to your lecturer on ${new Date(j.submitted_at * 1000).toLocaleString()}.`; if (j.checked) t += ' Checked by your lecturer' + (j.total != null ? `: ${j.total}/100.` : '.') + (j.remark ? ` Remark: “${j.remark}”` : ''); msg.textContent = t; sub.textContent = 'Submit again (updates your record)'; };
    fetch(VPL_CFG.submit + (VPL_CFG.submit.includes('?') ? '&' : '?') + 'exp=' + e.id, { credentials: 'same-origin' }).then(r => r.ok ? r.json() : null).then(showStatus).catch(() => { });
    sub.addEventListener('click', async () => {
      if (!stu.name || !stu.roll) { msg.textContent = 'Write your name and roll number above before submitting.'; $('#stuName', body).focus(); return; }
      if (!snapshots(e.id).length) { msg.textContent = 'Take your readings on the Lab bench first; the record is submitted with your observation tables.'; return; }
      sub.disabled = true; msg.textContent = 'Submitting…';
      const payload = { csrf: VPL_CFG.csrf, exp: e.id, no: e.no, title: e.title, name: stu.name, roll: stu.roll, date, note, result: res, tables: snapshots(e.id).map(t => ({ title: t.title, tsv: t.tsv })), text: asText() };
      try {
        const r = await fetch(VPL_CFG.submit, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const j = await r.json().catch(() => ({}));
        if (r.ok && j.ok) { showStatus(j); } else msg.textContent = 'Could not submit: ' + (j.error || ('server replied ' + r.status)) + '.';
      } catch (err) { msg.textContent = 'Could not reach the server. Check your connection and try again.'; }
      sub.disabled = false;
    });
  }
}
/* ----- viva: quiz + list ----- */
function vivaTab(e, body) {
  let mode = LS.get('vivamode', 'quiz');
  const bar = seg({ label: 'Mode', value: mode, options: [['quiz', 'Quiz me'], ['list', 'All questions']], onChange: v => { mode = v; LS.set('vivamode', v); draw(); } });
  const host = h('div'); body.append(h('div', { class: 'opts', style: 'margin-bottom:14px' }, bar.el, h('span', { class: 'small muted' }, 'Viva carries 20% of the practical marks. Answer aloud before you look.')), host);
  let Q = null;
  function start(only) { const idx = only || e.viva.map((_, i) => i); Q = { order: idx.slice().sort(() => Math.random() - 0.5), i: 0, res: [], streak: 0, best: 0, shown: false }; drawQuiz(); }
  function draw() { host.innerHTML = ''; if (mode === 'list') { host.append(h('div', { class: 'prose' }, e.viva.map(([q, a], i) => h('details', { class: 'viva' }, h('summary', null, h('span', { class: 'q' }, 'Q' + (i + 1)), h('span', { html: subHTML(q) })), h('div', { class: 'a', html: subHTML(a) }))))); } else start(); }
  function drawQuiz() {
    host.innerHTML = ''; const n = Q.order.length;
    const barEl = h('div', { class: 'qbar', 'aria-hidden': 'true' }, Q.order.map((_, k) => h('i', { class: k < Q.res.length ? (Q.res[k] ? 'ok' : 'no') : k === Q.i ? 'cur' : '' })));
    const score = Q.res.filter(Boolean).length;
    const sc = h('div', { class: 'qscore' }, h('span', null, 'Score ', h('b', null, `${score}/${Q.res.length}`)), h('span', null, 'Streak ', h('b', null, String(Q.streak))), h('span', null, 'Best streak ', h('b', null, String(Q.best))));
    if (Q.i >= n) {
      const pct = score / n; const stars = pct >= 0.9 ? '★★★' : pct >= 0.6 ? '★★☆' : '★☆☆';
      const missed = Q.order.filter((_, k) => !Q.res[k]);
      host.append(h('div', { class: 'quiz' }, barEl, h('div', { class: 'qcard' }, h('div', { class: 'stars', 'aria-label': `${stars.split('★').length - 1} stars` }, stars), h('div', { class: 'qq' }, `You knew ${score} of ${n} answers.`), h('p', { class: 'muted', style: 'margin:0' }, pct >= 0.9 ? 'Excellent. You are ready for the viva on this experiment.' : pct >= 0.6 ? 'Good. Go through the ones you missed once more.' : 'Read the theory tab again, then try once more.'),
        h('div', { class: 'recrow' }, missed.length ? h('button', { type: 'button', class: 'btn pri', onclick: () => start(missed) }, `Practise the ${missed.length} I missed`) : null, h('button', { type: 'button', class: 'btn', onclick: () => start() }, 'Start again'))), sc));
      return;
    }
    const [q, a] = e.viva[Q.order[Q.i]];
    const ans = h('div', { class: 'qa', html: subHTML(a) }); ans.hidden = !Q.shown;
    const show = h('button', { type: 'button', class: 'btn pri', onclick: () => { Q.shown = true; drawQuiz(); } }, 'Show answer');
    const yes = h('button', { type: 'button', class: 'btn pri', onclick: () => next(true) }, 'I knew it ✓'), no = h('button', { type: 'button', class: 'btn', onclick: () => next(false) }, 'Not yet ✗');
    host.append(h('div', { class: 'quiz' }, barEl, h('div', { class: 'qcard' }, h('div', { class: 'small muted' }, `Question ${Q.i + 1} of ${n}`), h('div', { class: 'qq', html: subHTML(q) }), ans, h('div', { class: 'recrow' }, Q.shown ? [yes, no] : show)), sc));
    (Q.shown ? yes : show).focus({ preventScroll: true });
  }
  function next(ok) { Q.res.push(ok); Q.streak = ok ? Q.streak + 1 : 0; Q.best = Math.max(Q.best, Q.streak); Q.i++; Q.shown = false; drawQuiz(); }
  draw();
}

/* ================= app shell: navigation, pages ================= */
EXPS.sort((a, b) => a.no - b.no);
const GROUPS = ['Optics', 'Modern physics', 'Nuclear physics', 'Electricity & CRO', 'Electronics', 'Digital electronics'];
const pad2 = n => String(n).padStart(2, '0');
function typeset(el) {
  const go = () => { try { window.MathJax.typesetPromise([el]).catch(() => { }); } catch (e) { } };
  if (window.MathJax && window.MathJax.typesetPromise) go();
  else { const t = setInterval(() => { if (window.MathJax && window.MathJax.typesetPromise) { clearInterval(t); go(); } }, 300); setTimeout(() => clearInterval(t), 30000); }
}
function buildSide(active) {
  const side = $('#side'); side.innerHTML = '';
  if (VPL_USER || VPL_CFG.portal) {
    const links = h('div', { class: 'su-links' });
    if (VPL_CFG.portal) links.append(h('a', { href: VPL_CFG.portal, class: 'su-link' }, '← B.Sc. portal'));
    if (VPL_USER && VPL_USER.role === 'lecturer' && VPL_CFG.lecturer) links.append(h('a', { href: VPL_CFG.lecturer, class: 'su-link' }, 'Student submissions'));
    if (VPL_CFG.logout) links.append(h('a', { href: VPL_CFG.logout, class: 'su-link' }, 'Sign out'));
    side.append(h('div', { class: 'side-user' },
      VPL_USER ? h('div', { class: 'su-name' }, VPL_USER.name || 'Signed in') : null,
      VPL_USER ? h('div', { class: 'su-meta' }, [VPL_USER.label || (VPL_USER.role === 'lecturer' ? 'Lecturer' : 'B.Sc. second year'), VPL_USER.roll ? 'Roll no. ' + VPL_USER.roll : null].filter(Boolean).join(' · ')) : null, links));
  }
  side.append(h('a', { href: '#home', class: 'home' + (active === 'home' ? ' on' : '') }, 'All experiments'));
  GROUPS.forEach(g => {
    side.append(h('h4', null, g));
    EXPS.filter(e => e.group === g).forEach(e => side.append(h('a', { href: '#' + e.id, class: active === e.id ? 'on' : null, 'aria-current': active === e.id ? 'page' : null }, h('span', { class: 'n' }, pad2(e.no)), h('span', null, e.short))));
  });
  sideSound(side);
}
function sideSound(side) {
  const inp = h('input', { type: 'checkbox', 'aria-label': 'Bench sounds' }); inp.checked = LS.get('sound', true);
  inp.addEventListener('change', () => { LS.set('sound', inp.checked); const t = $('#soundSw'); if (t) t.checked = inp.checked; if (inp.checked) sfx('on'); });
  side.append(h('label', { class: 'sw snd-side' }, inp, h('span', null, 'Bench sounds')));
}
function closeMenu() { $('#side').classList.remove('open'); const s = $('.scrim'); if (s) s.remove(); }
$('#menuBtn').addEventListener('click', () => {
  const side = $('#side'); if (side.classList.contains('open')) return closeMenu();
  side.classList.add('open'); const scrim = h('div', { class: 'scrim', onclick: closeMenu }); document.body.append(scrim);
});
const psw = $('#practiceSw'); psw.checked = Practice.on;
psw.addEventListener('change', () => { Practice.set(psw.checked); redrawAll(); });
const ssw = $('#soundSw'); ssw.checked = LS.get('sound', true);
ssw.addEventListener('change', () => { LS.set('sound', ssw.checked); if (ssw.checked) sfx('on'); });

function home() {
  const v = $('#view'); v.innerHTML = '';
  const strip = h('div', { class: 'spec-strip' });
  const hero = h('section', { class: 'hero' },
    h('div', { class: 'eyebrow' }, h('span', { class: 'no' }, 'B.Sc. Physics · Second Year Lab Works (PHY202)'), h('span', null, 'TU four-year curriculum (2015) · 180 hours')),
    h('h1', null, 'Virtual physics laboratory'),
    h('p', null, 'All twenty-five practical experiments of the B.Sc. second-year curriculum (PHY202). Each has its theory and step-by-step procedure, a virtual bench where you set up the instruments or wire the circuit yourself, an observation table that calculates as you record, graphs, a ready guide for your record file and a viva quiz.'),
    strip);
  v.append(hero);
  canvasView(strip, { aspect: 0.09, minH: 54, maxH: 90, draw: drawSpectrum, alt: 'Emission spectrum of mercury with the sodium D lines' });
  hero.append(h('div', { class: 'spec-cap' }, h('span', null, 'Mercury lines (experiments 2, 5, 6) and the sodium D lines (experiments 1, 3, 4, 7)'), h('span', null, '400 nm → 650 nm')));
  v.append(h('div', { class: 'howgrid' },
    h('div', null, h('b', null, 'Read the theory'), 'Principle, derivation of the working formula and the apparatus for each experiment.'),
    h('div', null, h('b', null, 'Set up and wire'), 'Drag the equipment on to the bench and connect the wires terminal to terminal. Check circuit tells you exactly what is wrong; Show me wires it as a demo.'),
    h('div', null, h('b', null, 'Perform and record'), 'Switch on, adjust the controls and read real-looking instruments. Readings carry the same kinds of error as in the lab.'),
    h('div', null, h('b', null, 'Write the file, face the viva'), 'The Record file tab lays out your practical copy with your own tables; the viva tab quizzes you and keeps score.')));
  v.append(h('h2', { class: 'grp-title' }, 'PHY202 practical examination marks'), h('div', { class: 'marks' }, h('div', null, h('b', null, '20%'), h('span', null, 'Record file → Record file tab')), h('div', null, h('b', null, '50%'), h('span', null, 'Experiment → Lab bench')), h('div', null, h('b', null, '10%'), h('span', null, 'Error analysis → Record file, section 9')), h('div', null, h('b', null, '20%'), h('span', null, 'Viva → Viva quiz'))));
  v.append(h('p', { class: 'howto', style: 'margin:-12px 0 8px' }, 'Tip: switch on “Read scales myself” in the top bar to hide the digital readouts. You then read every vernier and meter yourself, and the bench checks your reading.'));
  GROUPS.forEach(g => {
    v.append(h('h2', { class: 'grp-title' }, g));
    v.append(h('div', { class: 'cards' }, EXPS.filter(e => e.group === g).map(e => h('a', { class: 'xcard', href: '#' + e.id }, h('span', { class: 'n' }, pad2(e.no)), h('span', null, h('b', null, e.short), h('span', null, e.aim))))));
  });
  v.append(footer());
}
function footer() {
  return h('footer', { class: 'foot' }, h('div', null, 'Department of Physics, Kamala Science Campus, Dhungrebas, Sindhuli · prepared by Mr. Manoj Devkota for B.Sc. second-year practical classes.'),
    h('div', { class: 'small', style: 'margin-top:4px' }, 'A virtual experiment is a preparation for, and a supplement to, work with real apparatus. Your readings are stored only in this browser.'));
}
function drawSpectrum(ctx, W, H) {
  ctx.fillStyle = '#020304'; ctx.fillRect(0, 0, W, H);
  const x = l => (l - 395) / (650 - 395) * W;
  const lines = SOURCES.hg.lines.concat([{ l: 588.995, I: 0.7 }, { l: 589.592, I: 0.55 }]);
  lines.forEach(L => { const [r, g, b] = wl2rgb(L.l); const xx = x(L.l); const gr = ctx.createLinearGradient(xx - 4, 0, xx + 4, 0); const c = a => `rgba(${r * 255 | 0},${g * 255 | 0},${b * 255 | 0},${a})`; gr.addColorStop(0, c(0)); gr.addColorStop(.5, c(Math.min(1, .35 + L.I))); gr.addColorStop(1, c(0)); ctx.fillStyle = gr; ctx.fillRect(xx - 4, 4, 8, H - 8); });
  ctx.fillStyle = 'rgba(200,210,215,.75)'; ctx.font = `10px "IBM Plex Mono", monospace`; ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
  ctx.textAlign = 'left';
  [[404.7, '404.7'], [435.8, '435.8'], [491.6, '491.6'], [546.1, '546.1'], [579.1, '577·579'], [589.6, 'Na D'], [623.4, '623.4']].forEach(([l, t], i) => { if (W > 560 || i % 2 === 0) ctx.fillText(t, x(l) + 7, H - 4); });
}
function expPage(e, tab) {
  const v = $('#view'); v.innerHTML = '';
  tab = tab || LS.get('tab.' + e.id, 'theory');
  const head = h('header', { class: 'exp-head' },
    h('div', { class: 'eyebrow' }, h('span', { class: 'no' }, 'Experiment ' + pad2(e.no)), h('span', null, e.group)),
    h('h1', null, e.title), h('p', { class: 'aim' }, h('b', null, 'Aim: '), e.aim));
  const tabs = [['theory', 'Theory'], ['procedure', 'Procedure'], ['bench', 'Lab bench'], ['record', 'Record file'], ['viva', 'Viva']];
  CUR_EXP = e.id;
  const bar = h('div', { class: 'tabs', role: 'tablist' });
  const body = h('div', { role: 'tabpanel' });
  tabs.forEach(([k, t]) => bar.append(h('button', { type: 'button', role: 'tab', 'aria-selected': String(k === tab), onclick: () => { teardown(); LS.set('tab.' + e.id, k); expPage(e, k); } }, t)));
  v.append(head, bar, body);
  if (tab === 'theory') {
    const p = h('div', { class: 'prose' }, h('h2', null, 'Apparatus'), h('ul', null, e.apparatus.map(a => h('li', null, a))), h('div', { html: e.theory }));
    body.append(p); typeset(p);
  } else if (tab === 'procedure') {
    const p = h('div', { class: 'prose', html: e.procedure + `<h2>Precautions</h2><ul>${e.precautions}</ul><h2>Sources of error</h2><ul>${e.errors}</ul>` });
    p.append(h('p', null, h('button', { type: 'button', class: 'btn pri', onclick: () => { teardown(); LS.set('tab.' + e.id, 'bench'); expPage(e, 'bench'); } }, 'Go to the lab bench')));
    body.append(p); typeset(p);
  } else if (tab === 'viva') {
    vivaTab(e, body);
  } else if (tab === 'record') {
    recordTab(e, body);
  } else {
    let armed = 0;
    const rb = h('button', { type: 'button', class: 'btn sm' }, 'New apparatus & clear readings');
    rb.addEventListener('click', () => { if (!armed) { armed = 1; rb.textContent = 'Click again to confirm'; setTimeout(() => { armed = 0; rb.textContent = 'New apparatus & clear readings'; }, 2800); return; } resetApparatus(e.id); teardown(); expPage(e, 'bench'); });
    body.append(h('div', { class: 'opts', style: 'justify-content:space-between;margin:-6px 0 14px' }, h('p', { class: 'howto' }, 'Your instrument has its own small errors, like a real one. Readings are saved in this browser; copy the tables into your practical copy.'), rb));
    try { e.bench(body); } catch (err) { console.error(err); body.append(h('p', { class: 'status warn' }, 'The bench could not be started: ' + err.message)); }
  }
  const ord = EXPS.slice().sort((a, b) => a.no - b.no), k = ord.indexOf(e), pv = ord[k - 1], nx = ord[k + 1];
  v.append(h('nav', { class: 'pager', 'aria-label': 'Other experiments' },
    pv ? h('a', { href: '#' + pv.id, class: 'pg prev' }, h('small', null, '← Experiment ' + pad2(pv.no)), h('span', null, pv.short)) : h('span'),
    nx ? h('a', { href: '#' + nx.id, class: 'pg next' }, h('small', null, 'Experiment ' + pad2(nx.no) + ' →'), h('span', null, nx.short)) : h('span')), footer());
}
function route() {
  teardown(); closeMenu();
  const id = (location.hash || '#home').slice(1);
  const e = EXPS.find(x => x.id === id);
  buildSide(e ? e.id : 'home');
  if (e) expPage(e); else home();
  window.scrollTo(0, 0);
}
window.addEventListener('hashchange', route);
route();

</script>

</body>
</html>
