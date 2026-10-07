<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';

/**
 * The Virtual Physics Lab at portal/virtual-lab/: the twenty-five B.Sc.
 * second-year physics practicals (PHY202), each with its theory, a virtual
 * bench and a record file a student submits to be marked.
 *
 * Who may open it is decided here and nowhere else — the rail, Admin →
 * Virtual lab and the lab's own pages all ask vlab_role():
 *
 *   - active students of the years in VLAB_YEARS;
 *   - administrators, who read and mark records like a lecturer;
 *   - the lecturers an administrator has ticked in Admin → Virtual lab.
 *
 * Every other account, a lecturer who has not been assigned among them, is
 * told so rather than shown the lab.
 */

/** Years of study whose students open the lab. */
const VLAB_YEARS = [2, 3];

/** The lecturers assigned to the lab, as user ids. */
function vlab_lecturer_ids(): array
{
    $ids = [];
    foreach (explode(',', (string) setting('vlab_lecturers', '')) as $id) {
        if ((int) $id > 0) {
            $ids[(int) $id] = (int) $id;
        }
    }
    return array_values($ids);
}

function vlab_save_lecturer_ids(array $ids): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0)));
    sort($ids);
    set_setting('vlab_lecturers', $ids ? implode(',', $ids) : null);
}

/**
 * The part this account plays in the lab: 'student' (takes readings and
 * submits records), 'lecturer' (also reads and marks them — an administrator
 * or an assigned lecturer), or null when it may not open the lab at all.
 */
function vlab_role(?array $user): ?string
{
    if (!$user || ($user['status'] ?? '') !== 'active') {
        return null;
    }
    switch ($user['role']) {
        case ROLE_ADMIN:
            return 'lecturer';
        case ROLE_LECTURER:
            return in_array((int) $user['id'], vlab_lecturer_ids(), true) ? 'lecturer' : null;
        case ROLE_STUDENT:
            return in_array((int) ($user['year_level'] ?? 0), VLAB_YEARS, true) ? 'student' : null;
    }
    return null;
}

/** Where the rail's Virtual lab item goes: the lab, or for an administrator the page that runs it. */
function vlab_nav_url(array $user): string
{
    return $user['role'] === ROLE_ADMIN
        ? portal_url('/admin/virtual-lab.php')
        : portal_url('/virtual-lab/');
}

/**
 * The submitted records. Created here as well as by sql/schema.sql, so that
 * the first record submitted after a deploy is saved even if nobody has run
 * Admin → System → Run database updates yet; keep the two definitions in step.
 * Tried once per request; false when the table is missing and could not be
 * made.
 */
function vlab_records_ready(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    if (one("SHOW TABLES LIKE 'vlab_records'") !== null) {
        return $ok = true;
    }
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS vlab_records (
               id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
               user_id            INT UNSIGNED NOT NULL,
               exp_no             TINYINT UNSIGNED NOT NULL,
               title              VARCHAR(200) NOT NULL,
               name               VARCHAR(80)  NOT NULL,
               roll               VARCHAR(30)  NULL,
               exp_date           VARCHAR(20)  NULL,
               result             MEDIUMTEXT   NULL,
               note               MEDIUMTEXT   NULL,
               body               MEDIUMTEXT   NULL,
               tables_json        MEDIUMTEXT   NULL,
               versions           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
               first_submitted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
               submitted_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
               mark_record        DECIMAL(4,1) NULL,
               mark_experiment    DECIMAL(4,1) NULL,
               mark_error         DECIMAL(4,1) NULL,
               mark_viva          DECIMAL(4,1) NULL,
               mark_total         DECIMAL(4,1) NULL,
               remark             VARCHAR(500) NULL,
               marked_by          INT UNSIGNED NULL,
               marked_at          DATETIME     NULL,
               previous_total     DECIMAL(4,1) NULL,
               PRIMARY KEY (id),
               UNIQUE KEY uq_vlab_record (user_id, exp_no),
               KEY idx_vlab_submitted (submitted_at),
               CONSTRAINT fk_vlab_user   FOREIGN KEY (user_id)   REFERENCES users (id) ON DELETE CASCADE,
               CONSTRAINT fk_vlab_marker FOREIGN KEY (marked_by) REFERENCES users (id) ON DELETE SET NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        error_log('vlab_records not created: ' . $e->getMessage());
        return $ok = false;
    }
    return $ok = true;
}
