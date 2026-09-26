<?php
declare(strict_types=1);

require_once __DIR__ . '/idcard-view.php';

/**
 * Printing many cards at once: the layouts the bulk page offers, and the
 * sheets they come out on.
 *
 * Every card is drawn at its real size — CR80, 85.6 × 53.98 mm — by the same
 * id_card_face() the single card page uses, so a card printed in a batch is
 * the card its holder sees. What this file adds is where each one goes:
 *
 *   duplex — A4, both sides. Nine portrait or ten landscape cards to a sheet;
 *            the fronts on one page, then their backs on the next, laid out
 *            mirror-wise so that a printer turning the sheet on its long edge
 *            puts every back behind its own front.
 *   pairs  — A4, one side, for a printer that cannot print on both. Each
 *            card's back sits beside its front, sharing an edge; the pair is
 *            cut out, folded on that edge and laminated, and the fold does the
 *            lining up a duplex printer would otherwise have to get right.
 *   card   — a card printer: every face on a page of its own the size of a
 *            CR80 blank, front then back.
 *
 * Positions are worked out here in millimetres and written onto the page as
 * absolute offsets, not left to a CSS grid, because the backs of a duplex
 * sheet have to land within a fraction of a millimetre of where the fronts
 * were, and the only way to promise that is to put both at numbers.
 */

const CARD_LONG_MM  = 85.6;
const CARD_SHORT_MM = 53.98;
const SHEET_W_MM    = 210.0;    // A4
const SHEET_H_MM    = 297.0;

function id_card_bulk_layout(?string $key): string
{
    return in_array($key, ['duplex', 'pairs', 'card'], true) ? $key : 'duplex';
}

function id_card_bulk_guides(?string $key): string
{
    return in_array($key, ['marks', 'outline', 'none'], true) ? $key : 'marks';
}

/**
 * The grid a sheet is laid out on. Everything in millimetres; 'left' and
 * 'top' place the full grid in the middle of the sheet, and every page of a
 * run uses the same place, a short last page included, so a front and its
 * back always share coordinates.
 *
 * The gaps are what a paper trimmer needs between two cuts. The landscape
 * sheet's rows sit closer than the rest because five of them have to fit down
 * an A4 page with room left in the margin for the crop marks.
 */
function id_card_sheet_grid(string $layout, string $orientation): array
{
    $portrait = $orientation !== 'landscape';
    $g = $layout === 'pairs'
        // A front and its back, touching: the shared edge is the fold.
        ? ['cols' => 2, 'rows' => $portrait ? 3 : 5, 'gx' => 0.0, 'gy' => $portrait ? 6.0 : 2.5]
        : ($portrait
            ? ['cols' => 3, 'rows' => 3, 'gx' => 5.0, 'gy' => 4.0]
            : ['cols' => 2, 'rows' => 5, 'gx' => 6.0, 'gy' => 2.5]);

    $g['w']      = $portrait ? CARD_SHORT_MM : CARD_LONG_MM;
    $g['h']      = $portrait ? CARD_LONG_MM : CARD_SHORT_MM;
    $g['width']  = $g['cols'] * $g['w'] + ($g['cols'] - 1) * $g['gx'];
    $g['height'] = $g['rows'] * $g['h'] + ($g['rows'] - 1) * $g['gy'];
    $g['left']   = (SHEET_W_MM - $g['width']) / 2;
    $g['top']    = (SHEET_H_MM - $g['height']) / 2;
    // Holders to a page: a pair is one holder across both columns.
    $g['per']    = $layout === 'pairs' ? $g['rows'] : $g['cols'] * $g['rows'];
    return $g;
}

/**
 * The pages of a run, in print order. Each page is
 *   ['kind' => 'front'|'back'|'pairs', 'sheet' => n, 'cells' => [[holder index, face, row, col], ...]]
 * where the holder index points into the caller's list.
 *
 * A duplex back mirrors its front column for column — a sheet turned over on
 * its long edge swaps left and right, and nothing else — so the back of the
 * card at row r, column c is drawn at row r, column (cols − 1 − c). A short
 * last row is mirrored the same way, into the columns behind its fronts.
 */
function id_card_sheet_pages(int $count, string $layout, string $sides, array $g): array
{
    if ($count === 0) {
        return [];
    }
    $pages = [];
    foreach (array_chunk(range(0, $count - 1), $g['per']) as $n => $chunk) {
        $sheet = $n + 1;
        if ($layout === 'pairs') {
            $cells = [];
            foreach ($chunk as $k => $i) {
                $cells[] = [$i, 'front', $k, 0];
                $cells[] = [$i, 'back', $k, 1];
            }
            $pages[] = ['kind' => 'pairs', 'sheet' => $sheet, 'cells' => $cells];
            continue;
        }
        $front = $back = [];
        foreach ($chunk as $k => $i) {
            $r = intdiv($k, $g['cols']);
            $c = $k % $g['cols'];
            $front[] = [$i, 'front', $r, $c];
            $back[]  = [$i, 'back', $r, $g['cols'] - 1 - $c];
        }
        $pages[] = ['kind' => 'front', 'sheet' => $sheet, 'cells' => $front];
        if ($sides === 'both') {
            $pages[] = ['kind' => 'back', 'sheet' => $sheet, 'cells' => $back];
        }
    }
    return $pages;
}

/** A length in millimetres, as CSS. */
function sheet_mm(float $v): string
{
    return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . 'mm';
}

/**
 * Crop marks: short hairlines in the margin, in line with every edge a
 * trimmer has to cut, so each cut runs from a mark on one side of the sheet
 * to its twin on the other. They stay outside the cards — a mark printed on a
 * card is a mark on the card — which is why a sheet has them round its edge
 * and not in the gaps between.
 *
 * The line between a pair is the fold, not a cut, and is dashed so it is
 * never mistaken for one.
 */
function id_card_sheet_marks(array $g, int $rowsUsed, bool $pairs): void
{
    $off = 1.5;
    // As long as the margin allows, keeping clear of the few millimetres at
    // the paper's edge that most printers cannot reach.
    $len = max(2.5, min(5.0, min($g['left'], $g['top']) - $off - 3.0));
    $h   = $rowsUsed * $g['h'] + ($rowsUsed - 1) * $g['gy'];

    $xs = [];
    for ($c = 0; $c < $g['cols']; $c++) {
        $x0 = $c * ($g['w'] + $g['gx']);
        $xs[(string) round($x0, 3)] = $x0;
        $xs[(string) round($x0 + $g['w'], 3)] = $x0 + $g['w'];
    }
    foreach ($xs as $x) {
        $fold = $pairs && abs($x - $g['w']) < 0.01;
        $cls  = 'idcb-mark idcb-mark-v' . ($fold ? ' idcb-mark-fold' : '');
        echo '<i class="', $cls, '" style="left:', sheet_mm($x), ';top:', sheet_mm(-$off - $len), ';height:', sheet_mm($len), '"></i>';
        echo '<i class="', $cls, '" style="left:', sheet_mm($x), ';top:', sheet_mm($h + $off), ';height:', sheet_mm($len), '"></i>';
    }
    for ($r = 0; $r < $rowsUsed; $r++) {
        $y0 = $r * ($g['h'] + $g['gy']);
        foreach ([$y0, $y0 + $g['h']] as $y) {
            echo '<i class="idcb-mark idcb-mark-h" style="top:', sheet_mm($y), ';left:', sheet_mm(-$off - $len), ';width:', sheet_mm($len), '"></i>';
            echo '<i class="idcb-mark idcb-mark-h" style="top:', sheet_mm($y), ';left:', sheet_mm($g['width'] + $off), ';width:', sheet_mm($len), '"></i>';
        }
    }
}

/**
 * One A4 page of a run. $holders and $contexts are parallel lists: a context
 * belongs to its own holder (see id_card_face()), so every card is drawn from
 * the one made for it.
 *
 * Cutting guides go on the fronts only. On a duplex sheet the cut is made
 * from the front, and a guide on the back could only show how far the
 * printer's second pass has drifted from the first.
 */
function id_card_sheet_page(array $page, array $holders, array $contexts, array $g, string $guides, string $label): void
{
    $guided = $page['kind'] !== 'back' ? $guides : 'none';
    $rows   = 0;
    foreach ($page['cells'] as $cell) {
        $rows = max($rows, $cell[2] + 1);
    }
    ?>
    <p class="idcb-page-label p-noprint"><?= e($label) ?></p>
    <div class="idcb-page" data-guides="<?= e($guided) ?>" data-kind="<?= e($page['kind']) ?>">
      <div class="idcb-grid" style="left:<?= sheet_mm($g['left']) ?>;top:<?= sheet_mm($g['top']) ?>;width:<?= sheet_mm($g['width']) ?>;height:<?= sheet_mm($g['height']) ?>">
        <?php foreach ($page['cells'] as [$i, $face, $r, $c]): ?>
          <div class="idcb-cell" style="left:<?= sheet_mm($c * ($g['w'] + $g['gx'])) ?>;top:<?= sheet_mm($r * ($g['h'] + $g['gy'])) ?>">
            <?php id_card_face($holders[$i], $contexts[$i], $face, $contexts[$i]['holder_names']); ?>
          </div>
        <?php endforeach; ?>
        <?php if ($guided === 'marks') { id_card_sheet_marks($g, $rows, $page['kind'] === 'pairs'); } ?>
      </div>
    </div>
    <?php
}

/**
 * The page box for a run. Swapped in whole, because @page cannot be written
 * under a selector: A4 with no margin of its own for the sheets (the layout
 * places everything itself, and a margin added by the browser would move the
 * backs off their fronts), or a CR80 blank for a card printer.
 */
function id_card_bulk_page_rule(string $layout, string $orientation): string
{
    if ($layout !== 'card') {
        return '@page { size: A4 portrait; margin: 0; }';
    }
    return $orientation === 'landscape'
        ? '@page { size: 85.6mm 53.98mm; margin: 0; }'
        : '@page { size: 53.98mm 85.6mm; margin: 0; }';
}

/**
 * Printed cards are remembered — when, and a fingerprint of what the card
 * said (card_fingerprint()) — so the next run can be only the cards that are
 * due: never printed, or printed before their details changed.
 *
 * The two columns arrive through System → Update the database like every
 * other, and are also added here, because the bulk page is where they are
 * missed. That is tried once a session, not on every page view: a database
 * user without ALTER rights gets one attempt and a line in the error log,
 * and the page then prints without tracking rather than failing.
 */
function card_print_tracking(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    $has = static fn (): bool =>
        one("SHOW COLUMNS FROM users LIKE 'card_printed_at'") !== null
        && one("SHOW COLUMNS FROM users LIKE 'card_printed_sig'") !== null;
    if ($has()) {
        return $ok = true;
    }
    if (empty($_SESSION['card_print_columns_tried'])) {
        $_SESSION['card_print_columns_tried'] = true;
        foreach (['card_printed_at DATETIME NULL', 'card_printed_sig CHAR(40) NULL'] as $col) {
            try {
                db()->exec('ALTER TABLE users ADD COLUMN ' . $col);
            } catch (Throwable $e) {
                // "Duplicate column" for the one already there; anything else
                // is the reason tracking is off.
                error_log('users.' . strtok($col, ' ') . ' not added: ' . $e->getMessage());
            }
        }
        return $ok = $has();
    }
    return $ok = false;
}

/**
 * Records a run as printed: each card's date, and the fingerprint it had when
 * the page that was printed was drawn — sent back with the button, not worked
 * out again now, because a photo changed between printing and pressing the
 * button is on the database but not on the paper, and that card must stay
 * due. $cards is [holder id => fingerprint]; active accounts only, and a
 * value that is not a fingerprint is ignored. Returns how many were marked.
 */
function mark_cards_printed(array $cards): int
{
    $n = 0;
    foreach ($cards as $id => $sig) {
        if ((int) $id > 0 && is_string($sig) && preg_match('/^[0-9a-f]{40}$/', $sig)) {
            $n += q("UPDATE users SET card_printed_at = NOW(), card_printed_sig = ?
                      WHERE id = ? AND status = 'active'", [$sig, (int) $id])->rowCount();
        }
    }
    return $n;
}

/** The run as the mark-as-printed button carries it: "id:fingerprint,…". */
function card_run_encode(array $cards): string
{
    $out = [];
    foreach ($cards as $id => $sig) {
        $out[] = (int) $id . ':' . $sig;
    }
    return implode(',', $out);
}

/** And back: [id => fingerprint]. */
function card_run_decode(string $run): array
{
    $cards = [];
    foreach (explode(',', $run) as $pair) {
        [$id, $sig] = array_pad(explode(':', $pair, 2), 2, '');
        $cards[(int) $id] = $sig;
    }
    return $cards;
}
