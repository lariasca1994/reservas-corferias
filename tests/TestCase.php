<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las pruebas nunca avisan por Telegram, aunque el equipo tenga
        // TELEGRAM_BOT_TOKEN como variable de entorno del sistema.
        config(['services.telegram.token' => null, 'services.telegram.chat_id' => null]);
    }
}
