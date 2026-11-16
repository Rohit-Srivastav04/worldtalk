<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WorldTalk\Services\AuthService;

final class AuthTicketTest extends TestCase
{
    public function testTicketRoundTrip(): void
    {
        $_ENV['APP_KEY'] = 'unit-test-key-worldtalk-32chars!!';
        putenv('APP_KEY=unit-test-key-worldtalk-32chars!!');
        $payload = [
            'exp' => time() + 60,
            'uid' => 1,
            'gid' => null,
            'name' => 'Tester',
            'type' => 'user',
            'role' => 'user',
            'nonce' => 'abc',
        ];
        $body = base64_encode(json_encode($payload));
        $sig = hash_hmac('sha256', $body, 'unit-test-key-worldtalk-32chars!!');
        $ticket = $body . '.' . $sig;
        $out = AuthService::verifyWsTicket($ticket);
        $this->assertNotNull($out);
        $this->assertSame('Tester', $out['name']);
    }
}
