// Pantalla "Estamos en mantenimiento" (errors/base-no-disponible): cada 15 s pregunta si la
// portada vuelve a responder (mientras la base no responde, la portada devuelve esta misma
// pantalla con 503) y recarga sola cuando sí. Va en un archivo aparte: la política de seguridad
// no admite scripts en línea.
(function () {
    var TOTAL = 15, resto = TOTAL, revisando = false;
    var valor = document.getElementById('cuenta-valor');
    var texto = document.getElementById('cuenta-texto');
    if (!valor || !texto) return;
    function pintar() {
        valor.style.strokeDashoffset = revisando ? 0 : 1 - resto / TOTAL;
        texto.textContent = revisando ? 'Comprobando…' : 'Reintento automático en ' + resto + ' s';
    }
    function revisar() {
        if (revisando) return;
        revisando = true; pintar();
        fetch('/', { method: 'HEAD', cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) { if (r.ok) { location.reload(); return; } throw new Error('sigue sin responder'); })
            .catch(function () { revisando = false; resto = TOTAL; pintar(); });
    }
    window.addEventListener('online', revisar);
    setInterval(function () { if (revisando) return; resto -= 1; if (resto <= 0) revisar(); else pintar(); }, 1000);
    pintar();
})();
