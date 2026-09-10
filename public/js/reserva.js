/**
 * Formulario de reserva: aviso de cruce y cálculo del costo.
 *
 * Es una ayuda para el usuario, no un control de seguridad. La validación
 * que decide es la del servidor, en GuardarReservaRequest y en
 * DisponibilidadService. Este script solo evita que alguien complete todo
 * el formulario para enterarse al final de que las fechas estaban tomadas.
 *
 * El archivo de 2019 hacía justo lo contrario: validaba el correo con una
 * expresión regular en jQuery y el servidor no comprobaba nada.
 */
(function () {
    'use strict';

    const datos = window.datosReserva;
    if (!datos) return;

    const inicio  = document.getElementById('fecha_inicio');
    const fin     = document.getElementById('fecha_fin');
    const aviso   = document.getElementById('aviso-disponibilidad');
    const resumen = document.getElementById('resumen-costo');
    const dias    = document.getElementById('resumen-dias');
    const total   = document.getElementById('resumen-total');

    if (!inicio || !fin) return;

    const pesos = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    });

    /** Dos rangos se cruzan si a1 <= b2 y a2 >= b1. */
    const seCruza = (desde, hasta) =>
        datos.ocupados.find(r => desde <= r.fin && hasta >= r.inicio);

    const formatear = (iso) => {
        const [a, m, d] = iso.split('-');
        return `${d}/${m}/${a}`;
    };

    function revisar() {
        const desde = inicio.value;
        const hasta = fin.value;

        // La fecha final nunca puede quedar antes de la inicial.
        if (desde) fin.min = desde;

        aviso.classList.add('d-none');
        resumen.classList.add('d-none');

        if (!desde || !hasta || hasta < desde) return;

        const cruce = seCruza(desde, hasta);

        if (cruce) {
            aviso.textContent =
                `Estas fechas se cruzan con una reserva del ${formatear(cruce.inicio)} ` +
                `al ${formatear(cruce.fin)}. Elige otro rango.`;
            aviso.classList.remove('d-none');
            return;
        }

        const unDia    = 86400000;
        const cantidad = Math.round((new Date(hasta) - new Date(desde)) / unDia) + 1;

        dias.textContent  = `${cantidad} ${cantidad === 1 ? 'día' : 'días'} de reserva`;
        total.textContent = pesos.format(cantidad * datos.precioDia);
        resumen.classList.remove('d-none');
    }

    inicio.addEventListener('change', revisar);
    fin.addEventListener('change', revisar);
    revisar();
})();
