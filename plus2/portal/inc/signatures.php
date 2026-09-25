<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/uploads.php';

/**
 * The signature library.
 *
 * A signature is not a picture like any other. Once it is on a page it stands
 * for the person who signed, so the question this file answers is not "where
 * is the file" but "who is allowed to put it on something". Uploading a
 * signature and applying one are deliberately two different acts, and only an
 * administrator performs either.
 *
 * Every signature is uploaded locked: held in the library, printed on nothing.
 * An administrator then releases it, and the scope they choose decides who can
 * cause it to be applied:
 *
 *   locked   — applied to nothing at all. Cards print a blank signature line.
 *   admin    — an administrator may apply it. It prints on a card an
 *              administrator prints from Admin → People; a student printing their own card still gets the blank
 *              line, and download.php will not hand them the image.
 *   everyone — it prints on every card, including one a student prints for
 *              themselves. The old behaviour, kept for a campus that wants it.
 *
 * Which signature is used where is separate again — a pair of settings, so a
 * signature can sit in the library, released, and still be applied to nothing
 * until the office points a use at it.
 */

/**
 * Whether the tables this feature needs are in place yet.
 *
 * A deploy carries the code before an administrator runs the database update,
 * and in between a page that simply fataled would be a mystery. Asked the same
 * way settings() asks: run a statement that reads nothing and see whether the
 * table objects to being named. That works on any engine, unlike SHOW TABLES.
 */
function signature_tables_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        try {
            scalar('SELECT 1 FROM signatures WHERE 1 = 0');
            $ready = true;
        } catch (PDOException $e) {
            $ready = false;
        }
    }
    return $ready;
}

/**
 * The uses a signature can be pointed at. Each is a setting: signature_<use>.
 * A student's card carries two: the Principal's, and the +2 Coordinator's
 * beside it.
 */
const SIGNATURE_USES = ['id_card', 'id_card_coordinator'];

/** Widest first, so a comparison can read left to right. */
function signature_scopes(): array
{
    return ['locked', 'admin', 'everyone'];
}

function signature_scope(?string $key): string
{
    return in_array($key, signature_scopes(), true) ? (string) $key : 'locked';
}

function signature_use(?string $key): ?string
{
    return in_array($key, SIGNATURE_USES, true) ? (string) $key : null;
}

/** Every signature held, newest first, with the name of whoever uploaded it. */
function signatures(): array
{
    return all(
        'SELECT s.*, u.full_name AS uploader, r.full_name AS releaser
           FROM signatures s
           LEFT JOIN users u ON u.id = s.uploaded_by
           LEFT JOIN users r ON r.id = s.released_by
          ORDER BY s.created_at DESC, s.id DESC'
    );
}

function signature_row(int $id): ?array
{
    return $id > 0 ? one('SELECT * FROM signatures WHERE id = ? LIMIT 1', [$id]) : null;
}

/**
 * The signature the office has pointed at one use, whatever its release scope.
 * Null when nothing is pointed there, or when the setting still names a
 * signature that has since been deleted.
 */
function signature_for_use(string $use): ?array
{
    if (signature_use($use) === null) {
        return null;
    }
    return signature_row((int) setting('signature_' . $use, '0'));
}

/**
 * The dispersal rule, and the only place it is decided. Everything that can
 * put a signature in front of somebody — the card, a document, download.php —
 * comes through here.
 */
function signature_released_to(?array $sig, ?array $viewer): bool
{
    if (!$sig) {
        return false;
    }
    return match ($sig['release_scope']) {
        'everyone' => true,
        'admin'    => ($viewer['role'] ?? '') === ROLE_ADMIN,
        default    => false,     // locked
    };
}

/**
 * The signature that may actually be applied to $use for this viewer — the one
 * the office pointed at that use, but only if its release reaches them.
 * Returning null is what makes a card print a blank signature line.
 */
function applied_signature(string $use, ?array $viewer): ?array
{
    $sig = signature_for_use($use);
    // A signature whose file is gone prints the blank line, not a broken
    // image: the Signatures page says which ones need uploading again.
    return signature_released_to($sig, $viewer) && signature_file_present($sig) ? $sig : null;
}

/** Whether a signature's image is actually on disk. */
function signature_file_present(?array $sig): bool
{
    return $sig !== null && resolve_upload($sig['file_path'] ?? null) !== null;
}

/**
 * True when a signature is being held back from this viewer rather than simply
 * not existing — the difference between "the office has not uploaded one" and
 * "the office keeps it", which is what the card page tells the holder.
 */
function signature_withheld(string $use, ?array $viewer): bool
{
    $sig = signature_for_use($use);
    return $sig !== null && !signature_released_to($sig, $viewer);
}

function signature_url(array $sig): string
{
    return portal_url('/download.php?signature=' . (int) $sig['id']);
}

/** The signer's name in the language the page is being read in. */
function signature_owner_name(array $sig): string
{
    if (is_nepali() && !empty($sig['owner_name_ne'])) {
        return (string) $sig['owner_name_ne'];
    }
    return (string) $sig['owner_name'];
}

/** Which uses currently point at this signature, as use keys. */
function signature_uses(int $id): array
{
    $uses = [];
    foreach (SIGNATURE_USES as $use) {
        if ((int) setting('signature_' . $use, '0') === $id) {
            $uses[] = $use;
        }
    }
    return $uses;
}

/**
 * Point a use at a signature, or at nothing when $id is 0. A signature still
 * locked is released to administrators at the same time: pointing a use at a
 * signature that prints nowhere is never what was meant, and leaving it locked
 * would fail silently on the next card.
 */
function assign_signature(string $use, int $id, int $actorId): void
{
    if (signature_use($use) === null) {
        return;
    }
    if ($id <= 0) {
        set_setting('signature_' . $use, null);
        log_activity($actorId, 'signature_unassign', $use);
        return;
    }
    $sig = signature_row($id);
    if (!$sig) {
        return;
    }
    set_setting('signature_' . $use, (string) $id);
    if ($sig['release_scope'] === 'locked') {
        release_signature($id, 'admin', $actorId);
    }
    log_activity($actorId, 'signature_assign', $use, $sig['label']);
}

/** Change a signature's release scope. The dispersal itself — admins only. */
function release_signature(int $id, string $scope, int $actorId): void
{
    $scope = signature_scope($scope);
    $sig   = signature_row($id);
    if (!$sig) {
        return;
    }
    q(
        'UPDATE signatures SET release_scope = ?, released_at = ?, released_by = ? WHERE id = ?',
        [$scope, $scope === 'locked' ? null : date('Y-m-d H:i:s'), $scope === 'locked' ? null : $actorId, $id]
    );
    log_activity($actorId, 'signature_release', $sig['label'], $scope);
}

/**
 * Delete a signature and its file, and clear any use pointing at it, so no
 * setting is left naming a row that is gone.
 */
function delete_signature(int $id, int $actorId): void
{
    $sig = signature_row($id);
    if (!$sig) {
        return;
    }
    foreach (signature_uses($id) as $use) {
        set_setting('signature_' . $use, null);
    }
    delete_upload($sig['file_path']);
    q('DELETE FROM signatures WHERE id = ?', [$id]);
    log_activity($actorId, 'signature_delete', $sig['label']);
}

/* ------------------------------------------------------------- the trail -- */

/**
 * Every action that puts a signature somewhere, or changes who it reaches.
 * They go into the same activity_log as approvals and role changes; this list
 * is what the Signatures page filters that log down to, so the office can see
 * at a glance what has been done with the campus's signatures and by whom.
 */
function signature_actions(): array
{
    return [
        'signature_upload', 'signature_release', 'signature_assign', 'signature_unassign',
        'signature_edit', 'signature_delete',
    ];
}

function signature_activity(int $limit = 30): array
{
    $actions = signature_actions();
    // LIMIT is interpolated because MySQL will not take a placeholder there.
    // It is clamped to an integer first and never comes from a request.
    $limit = max(1, min(200, $limit));
    return all(
        'SELECT l.*, u.full_name AS actor FROM activity_log l
           LEFT JOIN users u ON u.id = l.actor_id
          WHERE l.action IN (' . implode(',', array_fill(0, count($actions), '?')) . ')
          ORDER BY l.created_at DESC, l.id DESC
          LIMIT ' . $limit,
        $actions
    );
}

/** A logged action in words. An action with no string of its own prints as it is. */
function signature_action_label(string $action): string
{
    $label = t('act_' . $action);
    return $label === 'act_' . $action ? $action : $label;
}
