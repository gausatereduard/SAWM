<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/guest.db.helper.php';

function createGuestMessage(string $user, string $textMessage, string $email): int
{
    $stmt = getGuestDB()->prepare(
        'INSERT INTO guest ("user", text_message, e_mail, data_time_message)
         VALUES (:user, :text_message, :e_mail, :data_time_message)'
    );
    $stmt->execute([
        ':user' => $user,
        ':text_message' => $textMessage,
        ':e_mail' => $email,
        ':data_time_message' => date('Y-m-d H:i:s'),
    ]);

    return (int) getGuestDB()->lastInsertId();
}

function getAllGuestMessages(): array
{
    return getGuestDB()
        ->query('SELECT id, "user", text_message, e_mail, data_time_message FROM guest ORDER BY id DESC')
        ->fetchAll();
}
