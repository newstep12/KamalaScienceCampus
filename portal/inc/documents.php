<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/idcard.php';
require_once __DIR__ . '/signatures.php';

/**
 * Official documents — the certificates and letters the campus office issues
 * over a signature: bonafide and character certificates, enrolment
 * confirmations and recommendations.
 *
 * Issuing one is an administrator's act and nobody else's. That is not only
 * because of what the documents say, but because issuing is what applies the
 * Campus Chief's signature to a page: the signature reaches the letter through
 * applied_signature('document', …), so it appears only while the office has a
 * signature released for documents, and only for the administrator issuing it.
 *
 * Every issue is written to the documents table as well as to the activity
 * log, so the office has a register it can answer questions from: what was
 * issued, to whom, on whose signature, by which administrator.
 */

function document_kinds(): array
{
    return ['bonafide', 'character', 'enrolment', 'recommendation', 'custom'];
}

function document_kind(?string $key): string
{
    return in_array($key, document_kinds(), true) ? (string) $key : 'bonafide';
}

/** The heading printed across the document. 'custom' carries its own. */
function document_heading(array $doc): string
{
    if ($doc['kind'] === 'custom') {
        return (string) ($doc['title'] ?: t('doc_kind_custom'));
    }
    return t('doc_title_' . $doc['kind']);
}

/**
 * The reference number, built from the academic session the office has set so
 * that a year's documents read as a series. Derived from the row id, so two
 * documents can never share one.
 */
function document_ref(int $id): string
{
    $session = mb_substr((string) (setting('id_card_session') ?: date('Y')), 0, 12);
    return sprintf('KSC/%s/%04d', $session, $id);
}

/**
 * The body of a standard document: three paragraphs built from the template
 * for its kind. The particulars — year, symbol number, date of birth — are not
 * folded into the prose; they print as a list above it, the way a campus
 * certificate is actually laid out, so the sentences stay readable in both
 * languages.
 *
 * @return string[] Paragraphs, already translated, never HTML.
 */
function document_paragraphs(array $doc): array
{
    if ($doc['kind'] === 'custom') {
        $body = trim((string) $doc['body']);
        if ($body === '') {
            return [];
        }
        return preg_split('/\n\s*\n/', $body) ?: [$body];
    }

    $session = (string) (setting('id_card_session') ?: '—');
    $purpose = trim((string) $doc['purpose']);
    $clause  = $purpose === '' ? t('doc_purpose_general') : t('doc_purpose_for', $purpose);

    $text = t(
        'doc_body_' . $doc['kind'],
        $doc['subject_name'],
        localize_digits($session),
        $clause
    );
    return preg_split('/\n\s*\n/', $text) ?: [$text];
}

/**
 * The particulars listed above the body: whatever the campus actually knows
 * about the person. A document issued for someone with no account carries the
 * name alone, which is honest — inventing rows for details nobody recorded is
 * exactly what an official document must not do.
 *
 * @return array<string,string> label => value
 */
function document_particulars(array $doc, ?array $subject): array
{
    $rows = [t('full_name') => (string) $doc['subject_name']];
    if (!$subject) {
        return $rows;
    }
    if (!empty($subject['full_name_ne'])) {
        $rows[t('full_name_ne')] = (string) $subject['full_name_ne'];
    }
    if ($subject['role'] === ROLE_STUDENT) {
        if (!empty($subject['year_level'])) {
            $rows[t('id_card_year')] = year_label((int) $subject['year_level']);
        }
        if (!empty($subject['symbol_no'])) {
            $rows[t('id_card_symbol')] = localize_digits((string) $subject['symbol_no']);
        }
    } else {
        $title = designation_label($subject['designation'] ?? null);
        if ($title !== '') {
            $rows[t('designation')] = $title;
        }
    }
    if (!empty($subject['date_of_birth'])) {
        $rows[t('date_of_birth')] = format_date($subject['date_of_birth']);
    }
    if (!empty($subject['address'])) {
        $rows[t('address')] = (string) $subject['address'];
    }
    $rows[t('id_card_no')] = id_card_number($subject);
    return $rows;
}

/**
 * Record an issue and return the stored row.
 *
 * The signature is resolved here rather than taken from the form: the caller
 * cannot name a signature the office has not released for documents, because
 * the only thing that reaches the row is what applied_signature() hands back.
 *
 * The reference number needs the row id, so it is written in a second
 * statement — inside a transaction, so a document never exists without one.
 */
function issue_document(array $in, array $actor): array
{
    $kind      = document_kind($in['kind'] ?? null);
    $subjectId = (int) ($in['subject_id'] ?? 0);
    $subject   = $subjectId > 0 ? one('SELECT * FROM users WHERE id = ? LIMIT 1', [$subjectId]) : null;
    $name      = $subject
        ? (string) $subject['full_name']
        : mb_substr(trim((string) ($in['subject_name'] ?? '')), 0, 120);
    $sig       = applied_signature('document', $actor);

    db()->beginTransaction();
    try {
        q(
            'INSERT INTO documents (ref_no, kind, subject_id, subject_name, title, body, purpose,
                                    issued_on, signature_id, issued_by)
             VALUES (\'\', ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $kind,
                $subject ? (int) $subject['id'] : null,
                $name,
                $kind === 'custom' ? mb_substr(trim((string) ($in['title'] ?? '')), 0, 190) : null,
                $kind === 'custom' ? trim((string) ($in['body'] ?? '')) : null,
                mb_substr(trim((string) ($in['purpose'] ?? '')), 0, 190) ?: null,
                parse_date((string) ($in['issued_on'] ?? '')) ?? date('Y-m-d'),
                $sig ? (int) $sig['id'] : null,
                (int) $actor['id'],
            ]
        );
        $id = (int) db()->lastInsertId();
        q('UPDATE documents SET ref_no = ? WHERE id = ?', [document_ref($id), $id]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    log_activity((int) $actor['id'], 'document_issue', $name, t('doc_kind_' . $kind));
    return document_row($id) ?? [];
}

function document_row(int $id): ?array
{
    return $id > 0
        ? one(
            'SELECT d.*, u.full_name AS issuer FROM documents d
               LEFT JOIN users u ON u.id = d.issued_by
              WHERE d.id = ? LIMIT 1',
            [$id]
        )
        : null;
}

/** The office register, newest first. */
function issued_documents(int $limit = 50): array
{
    // Clamped to an integer and never taken from a request: MySQL will not
    // accept a placeholder in LIMIT.
    $limit = max(1, min(200, $limit));
    return all(
        'SELECT d.*, u.full_name AS issuer, s.label AS signature_label
           FROM documents d
           LEFT JOIN users u ON u.id = d.issued_by
           LEFT JOIN signatures s ON s.id = d.signature_id
          ORDER BY d.created_at DESC, d.id DESC
          LIMIT ' . $limit
    );
}

/** Active accounts a document can be issued to, students first. */
function document_subjects(): array
{
    return all(
        'SELECT id, full_name, role, year_level, symbol_no FROM users
          WHERE status = \'active\'
          ORDER BY role = \'student\' DESC, full_name'
    );
}
