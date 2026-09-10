<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| Gancho previsto para el recordatorio a clientes con reserva confirmada
| que inicia al dia siguiente. Se deja comentado hasta que el comando
| exista: programar un comando inexistente hace fallar schedule:run.
|
| Schedule::command('reservas:recordatorios')->dailyAt('08:00');
|
*/
