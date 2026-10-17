<?php
route('POST', 'mail', function () {
    require_role('communication');
    $b = body();
    $gid = (int) ($b['group_id'] ?? 0);
    $subject = trim($b['subject'] ?? '');
    $text = trim($b['body'] ?? '');
    if (!$gid || $subject === '' || $text === '') throw new HttpError('Gruppe, Betreff und Text erforderlich', 422);
    $to = array_values(array_unique(array_filter(array_column(group_persons($gid), 'email'))));
    if (!$to) throw new HttpError('Keine Empfänger mit E-Mail', 422);
    $from = Db::config()['mail_from'];
    if (preg_match('/[\r\n]/', $subject)) throw new HttpError('Ungültiger Betreff', 422);
    $hdr = "From: $from\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $ok = 0;
    foreach ($to as $addr) { // einzeln versenden: keine Adressen für andere sichtbar
        if (@mail($addr, '=?UTF-8?B?' . base64_encode($subject) . '?=', $text, $hdr)) $ok++;
    }
    Db::exec('INSERT INTO mail_log (sent_by, group_id, subject, body, recipients) VALUES (?,?,?,?,?)', [me()['id'], $gid, $subject, $text, $ok]);
    return ['sent' => $ok, 'total' => count($to)];
});

route('GET', 'mail-recipients/{id}', function (int $gid) {
    require_role('communication');
    $p = group_persons($gid);
    return ['total' => count($p), 'with_email' => count(array_filter($p, fn($x) => $x['email']))];
});
