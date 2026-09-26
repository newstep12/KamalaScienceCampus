<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/idcard-sheet.php';

/**
 * Identity cards in bulk: every approved card the office asks for, laid out
 * for the printer in one run.
 *
 * Who is printed is the filters (students or staff, year of study, printed
 * or not) less anyone unticked in the list; how is the layout, the faces, the
 * orientation and the cutting guides. Everything is in the address, so a
 * reload, or a link sent to whoever does the printing, comes out the same.
 *
 * After a run the office marks the batch as printed, and the next time the
 * page opens it holds only the cards that are due: approved since, or changed
 * since they were printed (see card_fingerprint()).
 */

$admin    = require_role(ROLE_ADMIN);
$tracking = card_print_tracking();

/** The most cards one run lays out. A class at a time stays well inside it. */
const BULK_LIMIT = 200;

/** A query value that is a single string; a list (?layout[]=) is no value. */
$get = static fn (string $k): ?string => is_string($_GET[$k] ?? null) ? $_GET[$k] : null;

$who     = in_array($get('who'), ['students', 'staff', 'all'], true) ? $get('who') : 'students';
$year    = (int) $get('year');
$year    = $year >= 1 && $year <= 4 ? $year : 0;
$printed = $tracking && in_array($get('printed'), ['new', 'done', 'all'], true) ? $get('printed') : ($tracking ? 'new' : 'all');
$from    = max(0, (int) $get('from'));

$card    = id_card_settings();
$layout  = id_card_bulk_layout($get('layout'));
$sides   = id_card_sides($get('sides') ?? $card['sides']);
$orient  = id_card_orientation($get('orient') ?? $card['orientation']);
$guides  = id_card_bulk_guides($get('guides'));

/**
 * Who has been left out by hand. The list's ticks are sent with the ids that
 * were on screen, so a holder unticked is one that was shown and not sent;
 * ticking them again brings them back. Holders left out stay left out when
 * the filters change, until they are ticked again or the page is opened
 * afresh.
 */
$skip = array_filter(array_map('intval', explode(',', (string) $get('x'))));
if ($get('shown') !== null) {
    $shown = array_filter(array_map('intval', explode(',', (string) $get('shown'))));
    $pick  = array_map('intval', array_filter((array) ($_GET['pick'] ?? []), 'is_scalar'));
    $skip  = array_values(array_diff(array_unique(array_merge($skip, array_diff($shown, $pick))), $pick));
}
sort($skip);

/** The address of this page with these choices, and any changed. */
$here = static function (array $changes = []) use ($who, $year, $printed, $from, $layout, $sides, $orient, $guides, $skip): string {
    $q = array_merge([
        'who' => $who, 'year' => $year ?: null, 'printed' => $printed,
        'layout' => $layout, 'sides' => $sides, 'orient' => $orient, 'guides' => $guides,
        'x' => $skip ? implode(',', $skip) : null, 'from' => $from ?: null,
    ], $changes);
    return portal_url('/admin/id-cards.php?' . http_build_query(array_filter($q, static fn ($v) => $v !== null && $v !== '')));
};

// The ticks arrive as the whole list on screen; once they are folded into
// the left-out list, the page is asked for again at its short address, the
// one that is worth reloading or sending on.
if ($get('shown') !== null && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ' . $here());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($tracking && ($_POST['action'] ?? '') === 'mark_printed') {
        $n = mark_cards_printed(card_run_decode(is_string($_POST['cards'] ?? null) ? $_POST['cards'] : ''));
        log_activity((int) $admin['id'], 'mark_cards_printed', (string) $n);
        flash('ok', t('bulk_marked', localize_digits((string) $n)));
    }
    header('Location: ' . $here());
    exit;
}

$sql = "SELECT * FROM users WHERE status = 'active'";
$par = [];
if ($who === 'students') {
    $sql .= " AND role = 'student'";
} elseif ($who === 'staff') {
    $sql .= " AND role IN ('lecturer', 'admin')";
}
// A year of study is a set of students: choosing one leaves staff out.
if ($who !== 'staff' && $year) { $sql .= ' AND year_level = ?'; $par[] = $year; }
// Students by year and name, then staff by name.
$sql .= " ORDER BY (role = 'student') DESC, year_level, full_name, id";
$rows = all($sql, $par);

// Printed or due is a question of what each card says now against what it
// said when it was printed, so it is answered here rather than in SQL.
$prints = array_map('card_fingerprint', $rows);
$state  = array_map(static fn ($u, $fp) => $tracking ? card_print_state($u, $fp) : 'new', $rows, $prints);
if ($printed !== 'all') {
    // 'new' is every card that is due: never printed, or changed since.
    $keep   = array_filter($state, static fn ($st) => ($st === 'done') === ($printed === 'done'));
    $rows   = array_values(array_intersect_key($rows, $keep));
    $prints = array_values(array_intersect_key($prints, $keep));
    $state  = array_values($keep);
}

// A run is at most BULK_LIMIT cards; a longer list is taken a run at a time.
$total = count($rows);
$from  = $total ? min($from, intdiv($total - 1, BULK_LIMIT) * BULK_LIMIT) : 0;
$rows  = array_slice($rows, $from, BULK_LIMIT);
$state = array_slice($state, $from, BULK_LIMIT);
$prints = array_slice($prints, $from, BULK_LIMIT);

// The run itself: the listed holders less those left out.
$batch = array_values(array_filter(array_keys($rows), static fn ($k) => !in_array((int) $rows[$k]['id'], $skip, true)));

// The design, the signer and the campus are the same on every card and are
// asked for once; each holder's own half is added to a copy. The orientation
// is this run's.
$shared = id_card_shared_context($admin);
$shared['orientation'] = $orient;
$contexts = $missing = [];
foreach ($rows as $k => $u) {
    $contexts[$k] = $shared + id_card_holder_context($u, $admin);
    $missing[$k]  = id_card_missing($u, $contexts[$k]['holder_names']);
}

$holders = array_map(static fn ($k) => $rows[$k], $batch);
$ctxs    = array_map(static fn ($k) => $contexts[$k], $batch);
$count   = count($holders);
$incomplete = count(array_filter($batch, static fn ($k) => $missing[$k] !== []));

// A fold-over pair is a front and its back; with the fronts alone there is
// nothing to fold, and the sheet is the ordinary one.
$sheetLayout = ($layout === 'pairs' && $sides === 'front') ? 'duplex' : $layout;
$grid  = id_card_sheet_grid($sheetLayout, $orient);
$pages = $layout === 'card' ? [] : id_card_sheet_pages($count, $sheetLayout, $sides, $grid);

$sheets = $layout === 'card' ? $count : (int) ceil($count / max(1, $grid['per']));
$summary = $layout === 'card'
    ? t('bulk_sum_card', localize_digits((string) $sheets))
    : ($sheetLayout === 'duplex' && $sides === 'both'
        ? t('bulk_sum_duplex', localize_digits((string) $sheets))
        : t('bulk_sum_pages', localize_digits((string) $sheets)));

$howto = $layout === 'card' ? 'bulk_howto_card'
       : ($sheetLayout === 'pairs' ? 'bulk_howto_pairs' : ($sides === 'both' ? 'bulk_howto_duplex' : 'bulk_howto_fronts'));

$perDuplex = id_card_sheet_grid('duplex', $orient)['per'];
$perPairs  = id_card_sheet_grid('pairs', $orient)['per'];

layout_head(['title' => t('bulk_title'), 'active' => 'idcards', 'wide' => true]);
?>
<div class="p-page-head p-noprint">
  <h1><?= te('bulk_title') ?></h1>
  <p><?= te('bulk_intro') ?></p>
</div>

<form class="idcb-form p-noprint" method="get" id="bulk-form">
  <input type="hidden" name="x" value="<?= e(implode(',', $skip)) ?>">
  <input type="hidden" name="from" value="<?= $from ?: '' ?>">

  <div class="idcb-panel">
    <h2 class="idcb-step"><span>1</span><?= te('bulk_step_who') ?></h2>
    <div class="idcb-filters">
      <div class="p-field">
        <label for="who"><?= te('bulk_who') ?></label>
        <select id="who" name="who">
          <?php foreach (['students', 'staff', 'all'] as $v): ?>
            <option value="<?= $v ?>" <?= $who === $v ? 'selected' : '' ?>><?= te('bulk_who_' . $v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($who !== 'staff'): ?>
        <div class="p-field">
          <label for="year"><?= te('year_of_study') ?></label>
          <select id="year" name="year">
            <option value="0"><?= te('all_years') ?></option>
            <?php for ($y = 1; $y <= 4; $y++): ?>
              <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= e(year_label($y)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
      <?php endif; ?>
      <?php if ($tracking): ?>
        <div class="p-field">
          <label for="printed"><?= te('bulk_printed') ?></label>
          <select id="printed" name="printed">
            <?php foreach (['new', 'done', 'all'] as $v): ?>
              <option value="<?= $v ?>" <?= $printed === $v ? 'selected' : '' ?>><?= te('bulk_printed_' . $v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="idcb-panel">
    <h2 class="idcb-step"><span>2</span><?= te('bulk_step_how') ?></h2>
    <div class="idc-controls idcb-controls">
      <fieldset>
        <legend><?= te('bulk_layout') ?></legend>
        <div class="idc-choices idcb-choices-col">
          <?php foreach ([
              'duplex' => t('bulk_layout_duplex', localize_digits((string) $perDuplex)),
              'pairs'  => t('bulk_layout_pairs', localize_digits((string) $perPairs)),
              'card'   => t('bulk_layout_card'),
          ] as $v => $text): ?>
            <label class="idc-choice">
              <input type="radio" name="layout" value="<?= $v ?>" <?= $layout === $v ? 'checked' : '' ?>>
              <span><?= e($text) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset>
        <legend><?= te('id_card_sides') ?></legend>
        <div class="idc-choices">
          <?php foreach (['both' => 'id_card_sides_both', 'front' => 'id_card_sides_front'] as $v => $key): ?>
            <label class="idc-choice">
              <input type="radio" name="sides" value="<?= $v ?>" <?= $sides === $v ? 'checked' : '' ?>>
              <span><?= te($key) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset>
        <legend><?= te('idcard_orientation') ?></legend>
        <div class="idc-choices">
          <?php foreach (['portrait', 'landscape'] as $o): ?>
            <label class="idc-choice">
              <input type="radio" name="orient" value="<?= $o ?>" <?= $orient === $o ? 'checked' : '' ?>>
              <span><?= te('orientation_' . $o) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <?php if ($layout !== 'card'): ?>
        <fieldset>
          <legend><?= te('bulk_guides') ?></legend>
          <div class="idc-choices">
            <?php foreach (['marks', 'outline', 'none'] as $v): ?>
              <label class="idc-choice">
                <input type="radio" name="guides" value="<?= $v ?>" <?= $guides === $v ? 'checked' : '' ?>>
                <span><?= te('bulk_guides_' . $v) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      <?php else: ?>
        <input type="hidden" name="guides" value="<?= e($guides) ?>">
      <?php endif; ?>
    </div>
    <noscript><button class="p-btn p-btn-ghost" type="submit"><?= te('id_card_apply') ?></button></noscript>
  </div>

  <div class="idcb-panel">
    <h2 class="idcb-step"><span>3</span><?= te('bulk_step_check') ?></h2>
    <?php if (!$rows): ?>
      <div class="p-empty"><p><?= te($printed === 'new' ? 'bulk_none_new' : 'bulk_none') ?></p></div>
    <?php else: ?>
      <?php if ($total > BULK_LIMIT): ?>
        <div class="p-flash p-flash-info idcb-range">
          <?= e(t('bulk_range', localize_digits((string) ($from + 1)), localize_digits((string) ($from + count($rows))),
                  localize_digits((string) $total), localize_digits((string) BULK_LIMIT))) ?>
          <?php if ($from > 0): ?>
            <a href="<?= e($here(['from' => max(0, $from - BULK_LIMIT) ?: null])) ?>"><?= te('bulk_prev') ?></a>
          <?php endif; ?>
          <?php if ($from + BULK_LIMIT < $total): ?>
            <a href="<?= e($here(['from' => $from + BULK_LIMIT])) ?>"><?= te('bulk_next') ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if ($incomplete): ?>
        <div class="p-flash p-flash-info"><?= e(t('bulk_incomplete', localize_digits((string) $incomplete))) ?></div>
      <?php endif; ?>
      <input type="hidden" name="shown" value="<?= e(implode(',', array_map(static fn ($u) => (int) $u['id'], $rows))) ?>">
      <div class="p-table-wrap idcb-list">
        <table class="p-table">
          <thead>
            <tr>
              <th class="idcb-tick"><input type="checkbox" data-tick-all aria-label="<?= te('bulk_col_print') ?>" <?= $count === count($rows) ? 'checked' : '' ?>></th>
              <th><?= te('full_name') ?></th>
              <th><?= te('bulk_col_class') ?></th>
              <th><?= te('id_card_no') ?></th>
              <th><?= te('bulk_col_details') ?></th>
              <?php if ($tracking): ?><th><?= te('bulk_col_printed') ?></th><?php endif; ?>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $k => $u): $in = !in_array((int) $u['id'], $skip, true); ?>
              <tr class="<?= $in ? '' : 'idcb-off' ?>">
                <td class="idcb-tick">
                  <input type="checkbox" name="pick[]" value="<?= (int) $u['id'] ?>" <?= $in ? 'checked' : '' ?>
                         aria-label="<?= e(t('bulk_tick', $u['full_name'])) ?>">
                </td>
                <td>
                  <strong><?= e(display_name($u)) ?></strong>
                  <?php if ($contexts[$k]['holder_names']['deva'] && $contexts[$k]['holder_names']['latin']): ?>
                    <div class="idcb-sub" lang="ne"><?= e($contexts[$k]['holder_names']['deva']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($u['role'] === ROLE_STUDENT): ?>
                    <?= $u['year_level'] ? e(year_label((int) $u['year_level'])) : '—' ?>
                    <?php if (!empty($u['symbol_no'])): ?>
                      <div class="idcb-sub"><?= te('symbol_no') ?>: <?= e(localize_digits((string) $u['symbol_no'])) ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <?= e(id_card_role_line($u)) ?>
                  <?php endif; ?>
                </td>
                <td class="nowrap"><?= e(id_card_number($u)) ?></td>
                <td>
                  <?php if ($missing[$k]): ?>
                    <span class="p-tag pending"><?= e(t('bulk_missing', join_list($missing[$k]))) ?></span>
                  <?php else: ?>
                    <span class="p-tag ok"><?= te('bulk_complete') ?></span>
                  <?php endif; ?>
                </td>
                <?php if ($tracking): ?>
                  <td class="nowrap">
                    <?php if ($state[$k] === 'done'): ?>
                      <?= e(format_date($u['card_printed_at'])) ?>
                    <?php elseif ($state[$k] === 'changed'): ?>
                      <span class="p-tag pending"><?= e(t('bulk_changed', format_date($u['card_printed_at']))) ?></span>
                    <?php else: ?>
                      <span class="idcb-sub"><?= te('bulk_not_printed') ?></span>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
                <td class="nowrap">
                  <a class="p-btn p-btn-ghost p-btn-sm" href="<?= e(portal_url('/id-card.php?user=' . (int) $u['id'])) ?>"><?= te('print_id_card') ?></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <noscript><button class="p-btn p-btn-ghost" type="submit" style="margin-top:12px;"><?= te('id_card_apply') ?></button></noscript>
    <?php endif; ?>
  </div>
</form>

<?php if ($count): ?>
  <div class="idcb-panel idcb-go p-noprint">
    <h2 class="idcb-step"><span>4</span><?= te('bulk_step_print') ?></h2>
    <p class="idcb-summary"><strong><?= e(t('bulk_count', localize_digits((string) $count))) ?></strong> · <?= e($summary) ?></p>
    <div class="idcb-actions">
      <button class="p-btn p-btn-primary" type="button" data-print><?= te('bulk_print') ?></button>
      <?php if ($tracking && $printed !== 'done'): ?>
        <form method="post" action="<?= e($here()) ?>" data-confirm data-confirm-label="<?= te('confirm_again') ?>">
          <?= csrf_field() ?>
          <?php /* Each card with the fingerprint it has on this page — the one
                   that was printed — not the one it may have by the time the
                   button is pressed. */ ?>
          <input type="hidden" name="cards" value="<?= e(card_run_encode(array_combine(
              array_map(static fn ($k) => (int) $rows[$k]['id'], $batch),
              array_map(static fn ($k) => $prints[$k], $batch)
          ) ?: [])) ?>">
          <button class="p-btn p-btn-ghost" type="submit" name="action" value="mark_printed">
            <?= e(t('bulk_mark', localize_digits((string) $count))) ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <details class="idcb-howto" open>
      <summary><?= te('bulk_howto_title') ?></summary>
      <ol>
        <?php foreach (explode("\n", t($howto)) as $line): ?>
          <li><?= e(trim($line)) ?></li>
        <?php endforeach; ?>
      </ol>
      <p class="hint"><?= te('bulk_howto_pdf') ?></p>
    </details>
  </div>

  <?php if ($layout === 'card'): ?>
    <div class="idc-sheet idcb-cards" data-sides="<?= e($sides) ?>" data-print-target="card" data-orientation="<?= e($orient) ?>">
      <?php foreach ($holders as $i => $u): ?>
        <figure class="idc-holder">
          <?php id_card_face($u, $ctxs[$i], 'front', $ctxs[$i]['holder_names']); ?>
          <figcaption class="p-noprint"><?= e(display_name($u)) ?> · <?= te('id_card_front') ?></figcaption>
        </figure>
        <?php /* The backs are left out rather than hidden when only the fronts
                 print: a hidden last face would keep the page break after
                 the last front, and feed a card printer a blank. */ ?>
        <?php if ($sides === 'both'): ?>
          <figure class="idc-holder idc-holder-back">
            <?php id_card_face($u, $ctxs[$i], 'back'); ?>
            <figcaption class="p-noprint"><?= te('id_card_back') ?></figcaption>
          </figure>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="idcb-pages">
      <?php foreach ($pages as $page):
          $n = localize_digits((string) $page['sheet']);
          $label = $page['kind'] === 'front' ? t($sides === 'both' ? 'bulk_sheet_front' : 'bulk_page', $n)
                 : ($page['kind'] === 'back' ? t('bulk_sheet_back', $n) : t('bulk_page', $n));
          id_card_sheet_page($page, $holders, $ctxs, $grid, $guides, $label);
      endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<style id="idc-page-rule"><?= id_card_bulk_page_rule($layout, $orient) ?></style>

<script>
(function () {
  var form = document.getElementById('bulk-form');
  if (!form) return;

  // Every choice redraws the run: the layout is worked out on the server, to
  // the millimetre, so the page is simply asked again. Where the office was
  // on the page is kept across the reload.
  var KEY = 'idcb-scroll';
  try {
    var y = sessionStorage.getItem(KEY);
    if (y !== null) { sessionStorage.removeItem(KEY); window.scrollTo(0, parseInt(y, 10) || 0); }
  } catch (e) {}
  function send() {
    try { sessionStorage.setItem(KEY, String(window.scrollY)); } catch (e) {}
    form.submit();
  }
  // A new choice of whose cards starts again at the first run of them.
  var FILTERS = ['who', 'class', 'group', 'year', 'printed'];
  form.addEventListener('change', function (e) {
    if (FILTERS.indexOf(e.target.name) !== -1 && form.elements.from) {
      form.elements.from.value = '';
    }
    if (e.target.hasAttribute('data-tick-all')) {
      form.querySelectorAll('input[name="pick[]"]').forEach(function (b) { b.checked = e.target.checked; });
    }
    send();
  });

  // A run can be two hundred photographs and signatures, each fetched through
  // the portal. Printing before they have all arrived would put empty frames
  // on paper, so the button waits for every image to load (or fail) first.
  function imagesReady() {
    return Promise.all(Array.prototype.map.call(document.images, function (img) {
      if (img.complete) return null;
      return new Promise(function (done) {
        img.addEventListener('load', done, { once: true });
        img.addEventListener('error', done, { once: true });
      });
    }));
  }
  document.querySelectorAll('[data-print]').forEach(function (b) {
    b.addEventListener('click', function () {
      b.disabled = true;
      imagesReady().then(function () { b.disabled = false; window.print(); });
    });
  });
})();
</script>
<?php layout_foot(); ?>
