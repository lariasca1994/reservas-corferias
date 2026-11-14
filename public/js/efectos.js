/* Efectos de Reservas de recinto ferial (web y PWA). Sin dependencias; si algo falla, la
   página funciona igual que siempre:
   - onda al tocar los botones, barra de "enviando" y temblor ante un error;
   - menú del panel (indicador con haz, fondo que sigue al ratón, compacto con etiqueta),
     indicador de la barra superior, barra de vidrio del teléfono y hoja "Más";
   - dirección del cambio de página (View Transitions);
   - segundo factor: seis casillas sobre el mismo campo #codigo, candado y anillo;
   - registro del service worker (solo guarda la cáscara). */
(function () {
    'use strict';
    var raiz = document.documentElement;
    var reducir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function leer(c) { try { return localStorage.getItem(c); } catch (e) { return null; } }
    function guardar(c, v) { try { localStorage.setItem(c, v); } catch (e) { /* sin almacenamiento */ } }
    function leerS(c) { try { return sessionStorage.getItem(c); } catch (e) { return null; } }
    function guardarS(c, v) { try { sessionStorage.setItem(c, v); } catch (e) { /* sin almacenamiento */ } }

    // Compacto antes de pintar el menú (se recuerda en este equipo).
    if (leer('reservas-menu') === 'compacto') raiz.setAttribute('data-menu', 'compacto');

    /* ───────── Dirección del cambio de página ───────── */
    var ORDEN = ['', 'escenarios', 'eventos', 'mis-reservas', 'login', 'registro', 'olvide', 'verificacion', 'admin', 'cuenta'];
    function rango(url) {
        try {
            var p = new URL(url, location.href).pathname.split('/').filter(Boolean)[0] || '';
            var i = ORDEN.indexOf(p);
            return i < 0 ? 50 : i;
        } catch (e) { return 50; }
    }
    // Una transición que se cancela (la otra página no participa o el usuario navega de nuevo)
    // rechaza sus promesas en ambos documentos: se atrapan para que no quede error en la consola.
    function calmar(t) { if (!t) return; [t.ready, t.finished, t.updateCallbackDone].forEach(function (pr) { if (pr) pr.catch(function () {}); }); }
    window.addEventListener('pageswap', function (e) { calmar(e.viewTransition); });
    window.addEventListener('pagereveal', function (e) {
        // Si la otra página no participa, la transición se cancela: no es un error.
        calmar(e.viewTransition);
        if (!e.viewTransition || !window.navigation || !navigation.activation || !navigation.activation.from) return;
        if (rango(location.href) < rango(navigation.activation.from.url)) e.viewTransition.types.add('atras');
    });

    /* ───────── Botones ───────── */
    function onda(e) {
        var b = e.target.closest && e.target.closest('.btn, .menu-op, .barra-movil__op');
        if (!b || reducir || b.disabled) return;
        var r = b.getBoundingClientRect();
        var o = document.createElement('span');
        o.className = b.classList.contains('btn-principal') ? 'onda' : 'onda onda--suave';
        o.style.setProperty('--x', (e.clientX - r.left) + 'px');
        o.style.setProperty('--y', (e.clientY - r.top) + 'px');
        o.style.setProperty('--d', Math.max(r.width, r.height) * 2.2 + 'px');
        b.appendChild(o);
        o.addEventListener('animationend', function () { o.remove(); });
    }
    function temblar(el) {
        if (!el || reducir) return;
        el.classList.remove('temblor');
        void el.offsetWidth;
        el.classList.add('temblor');
        el.addEventListener('animationend', function () { el.classList.remove('temblor'); }, { once: true });
    }
    // Envío normal al servidor: el botón muestra la barra de "enviando".
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!(f instanceof HTMLFormElement) || e.defaultPrevented || f.method.toLowerCase() !== 'post') return;
        f.setAttribute('data-enviando', '');
    });
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-enviando]').forEach(function (f) { f.removeAttribute('data-enviando'); });
    });

    /* ───────── Menús ───────── */
    function ubicar(capa, op) {
        capa.style.transform = 'translate(' + op.offsetLeft + 'px,' + op.offsetTop + 'px)';
        capa.style.width = op.offsetWidth + 'px';
        capa.style.height = op.offsetHeight + 'px';
    }
    function visible(el) { return el && el.offsetParent !== null; }

    function iniciarPanel() {
        var nav = document.querySelector('.menu-nav');
        if (!nav) return;
        var ind = nav.querySelector('.menu-ind');
        var hov = nav.querySelector('.menu-hov');
        var etiqueta = document.querySelector('.menu-etiqueta');
        var ops = Array.prototype.slice.call(nav.querySelectorAll('.menu-op'));
        var activa = nav.querySelector('.menu-op.activo');

        function colocar(sinAnim) {
            var a = nav.querySelector('.menu-op.activo');
            if (!visible(a)) a = nav.querySelector('.menu-mas.contiene-actual');
            if (!visible(a)) { ind.style.opacity = '0'; return; }
            ind.style.opacity = '1';
            if (sinAnim) nav.classList.add('menu-sin-anim');
            ubicar(ind, a);
            if (sinAnim) requestAnimationFrame(function () { requestAnimationFrame(function () { nav.classList.remove('menu-sin-anim'); }); });
        }
        // El indicador parte de la opción de la pantalla anterior y se desliza a la actual.
        var previo = leerS('reservas-menu-op');
        var origen = previo && ops.filter(function (o) { return o.getAttribute('href') === previo; })[0];
        if (!reducir && visible(origen) && visible(activa) && origen !== activa) {
            nav.classList.add('menu-sin-anim');
            ubicar(ind, origen);
            requestAnimationFrame(function () { requestAnimationFrame(function () { nav.classList.remove('menu-sin-anim'); colocar(false); }); });
        } else {
            colocar(true);
        }
        if (activa) guardarS('reservas-menu-op', activa.getAttribute('href'));
        if (!leerS('reservas-menu-visto')) {
            raiz.classList.add('menu-primera');
            guardarS('reservas-menu-visto', '1');
            setTimeout(function () { raiz.classList.remove('menu-primera'); }, 1200);
        }

        var panel = document.querySelector('.menu-panel');
        panel.addEventListener('mouseover', function (e) {
            var op = e.target.closest('.menu-op, .menu-cuenta, .menu-alternar');
            if (!op) return;
            if (op.classList.contains('menu-op') && nav.contains(op)) {
                if (!hov.classList.contains('listo')) {
                    nav.classList.add('menu-sin-anim');
                    ubicar(hov, op);
                    void hov.offsetWidth;
                    nav.classList.remove('menu-sin-anim');
                    hov.classList.add('listo');
                }
                ubicar(hov, op);
            }
            if (etiqueta) {
                var r = op.getBoundingClientRect();
                etiqueta.textContent = op.getAttribute('data-texto') || (op.querySelector('.menu-texto') || op).textContent.trim();
                etiqueta.style.top = (r.top + r.height / 2 - etiqueta.offsetHeight / 2) + 'px';
                etiqueta.classList.add('visible');
            }
        });
        panel.addEventListener('mouseleave', function () {
            hov.classList.remove('listo');
            if (etiqueta) etiqueta.classList.remove('visible');
        });

        var alternar = document.querySelector('.menu-alternar');
        function textoAlternar() {
            var compacto = raiz.getAttribute('data-menu') === 'compacto';
            alternar.setAttribute('aria-expanded', String(!compacto));
            alternar.setAttribute('data-texto', compacto ? 'Extender menú' : 'Compactar menú');
            alternar.querySelector('.menu-texto').textContent = compacto ? 'Extender menú' : 'Compactar menú';
        }
        if (alternar) {
            textoAlternar();
            alternar.addEventListener('click', function () {
                var compacto = raiz.getAttribute('data-menu') !== 'compacto';
                if (compacto) raiz.setAttribute('data-menu', 'compacto'); else raiz.removeAttribute('data-menu');
                guardar('reservas-menu', compacto ? 'compacto' : 'extendido');
                textoAlternar();
            });
        }
        if (window.ResizeObserver) new ResizeObserver(function () { colocar(true); }).observe(nav);
        window.addEventListener('resize', function () { colocar(true); });
        requestAnimationFrame(function () { raiz.classList.add('menu-listo'); });
        ops.forEach(function (op) {
            if (op.tagName !== 'A') return;
            op.addEventListener('click', function (e) {
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0 || op === activa) return;
                nav.querySelectorAll('.menu-op.activo').forEach(function (x) { x.classList.remove('activo'); });
                op.classList.add('activo');
                colocar(false);
            });
        });
    }

    // Barra superior del sitio público: una línea dorada se desliza bajo la sección actual.
    function iniciarBarraSuperior() {
        var lista = document.querySelector('.menu-superior');
        if (!lista) return;
        var ind = document.createElement('li');
        ind.className = 'menu-superior__ind';
        ind.setAttribute('aria-hidden', 'true');
        lista.appendChild(ind);
        function colocar() {
            var a = lista.querySelector('.nav-link.activo');
            if (!visible(a)) { ind.style.opacity = '0'; return; }
            var r = a.getBoundingClientRect(), base = lista.getBoundingClientRect();
            ind.style.opacity = '1';
            ind.style.width = Math.max(0, r.width - 24) + 'px';
            ind.style.transform = 'translateX(' + (r.left - base.left + 12) + 'px)';
        }
        colocar();
        window.addEventListener('resize', colocar);
        lista.addEventListener('mouseover', function (e) {
            var a = e.target.closest('.nav-link');
            if (!a || !visible(a)) return;
            var r = a.getBoundingClientRect(), base = lista.getBoundingClientRect();
            ind.style.opacity = '1';
            ind.style.width = Math.max(0, r.width - 24) + 'px';
            ind.style.transform = 'translateX(' + (r.left - base.left + 12) + 'px)';
        });
        lista.addEventListener('mouseleave', colocar);
    }

    // Barra de vidrio del sitio público.
    function iniciarBarraMovil() {
        var barra = document.querySelector('.barra-movil');
        if (!barra) return;
        var ind = barra.querySelector('.barra-movil__ind');
        function colocar() {
            var a = barra.querySelector('[aria-current="page"]') || barra.querySelector('.barra-movil__mas.contiene-actual');
            if (!visible(a)) { ind.style.opacity = '0'; return; }
            ind.style.opacity = '1';
            ind.style.transform = 'translateX(' + a.offsetLeft + 'px)';
            ind.style.width = a.offsetWidth + 'px';
        }
        colocar();
        window.addEventListener('resize', colocar);
    }

    // Hoja "Más" (panel y sitio público). Elegir una opción la cierra en el acto: la hoja
    // modal deja inerte el resto de la página mientras baja.
    function iniciarHoja() {
        var hoja = document.querySelector('.hoja-mas');
        var mas = document.querySelector('.menu-mas, .barra-movil__mas');
        if (!hoja || !mas || typeof hoja.showModal !== 'function') return;
        function cerrar() {
            if (reducir) { hoja.close(); return; }
            hoja.classList.add('cerrando');
            setTimeout(function () { hoja.classList.remove('cerrando'); hoja.close(); }, 200);
        }
        mas.addEventListener('click', function () { hoja.showModal(); mas.setAttribute('aria-expanded', 'true'); });
        hoja.addEventListener('close', function () { mas.setAttribute('aria-expanded', 'false'); });
        hoja.addEventListener('click', function (e) {
            if (e.target === hoja) cerrar();
            else if (e.target.closest('a')) hoja.close();
        });
        hoja.addEventListener('cancel', function (e) { e.preventDefault(); cerrar(); });
    }

    /* ───────── Segundo factor ───────── */
    var SVG_CANDADO = '<svg viewBox="0 0 48 48" aria-hidden="true"><path class="arco" d="M15 22v-7a9 9 0 0 1 18 0v7"/><rect class="cuerpo" x="9" y="21" width="30" height="21" rx="6"/><circle class="ojo" cx="24" cy="31.5" r="3.4"/></svg>';

    function iniciarCodigo() {
        var input = document.getElementById('codigo');
        if (!input || input.closest('.casillas-codigo')) return;
        var caja = document.createElement('div');
        caja.className = 'casillas-codigo';
        input.parentNode.insertBefore(caja, input);
        caja.appendChild(input);
        var vista = document.createElement('div');
        vista.className = 'casillas-vista';
        vista.setAttribute('aria-hidden', 'true');
        vista.innerHTML = '<span></span><span></span><span></span><span></span><span></span><span></span>';
        caja.appendChild(vista);
        var casillas = vista.children, anterior = '';
        function pintar() {
            var d = input.value.replace(/\D/g, '').slice(0, 6);
            var foco = document.activeElement === input;
            for (var i = 0; i < 6; i++) {
                casillas[i].textContent = d[i] || '';
                casillas[i].classList.toggle('llena', !!d[i]);
                casillas[i].classList.toggle('activa', foco && i === Math.min(d.length, 5));
                if (d.length > anterior.length && i === d.length - 1 && !reducir) {
                    casillas[i].classList.remove('pulso'); void casillas[i].offsetWidth; casillas[i].classList.add('pulso');
                }
            }
            anterior = d;
        }
        ['input', 'focus', 'blur', 'keyup'].forEach(function (ev) { input.addEventListener(ev, pintar); });
        pintar();

        // Anillo: cada 30 s con la app; con el correo, los minutos que dice la página.
        var tarjeta = input.closest('.tarjeta') || document;
        var textoPagina = Array.prototype.map.call(tarjeta.querySelectorAll('p'), function (p) { return p.textContent; }).join(' ');
        var m = textoPagina.match(/Vence en (\d+) minutos/);
        var modo = m ? 'correo' : (/app autenticadora/i.test(textoPagina) && !/Escanea/i.test(textoPagina) ? 'app' : null);
        if (modo) {
            var anillo = document.createElement('p');
            anillo.className = 'anillo';
            anillo.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle class="anillo-fondo" cx="12" cy="12" r="9"/><circle class="anillo-valor" cx="12" cy="12" r="9" pathLength="1"/></svg><span></span>';
            caja.parentNode.insertBefore(anillo, caja.nextSibling);
            var valor = anillo.querySelector('.anillo-valor'), texto = anillo.querySelector('span');
            var inicio = Date.now(), minutos = m ? parseInt(m[1], 10) : 0;
            var tic = function () {
                var resto, total;
                if (modo === 'app') {
                    total = 30; resto = 30 - (Math.floor(Date.now() / 1000) % 30);
                    texto.textContent = 'Tu app muestra un código nuevo en ' + resto + ' s';
                } else {
                    total = minutos * 60; resto = Math.max(0, Math.round((inicio + total * 1000 - Date.now()) / 1000));
                    texto.textContent = resto ? 'El código vence en ' + Math.floor(resto / 60) + ':' + String(resto % 60).padStart(2, '0') : 'El código venció. Pide otro.';
                }
                valor.style.strokeDashoffset = 1 - resto / total;
                anillo.classList.toggle('urgente', resto <= (modo === 'app' ? 5 : 60));
            };
            tic();
            setInterval(tic, 1000);
        }

        // Candado sobre el título.
        var titulo = tarjeta.querySelector && tarjeta.querySelector('.antetitulo');
        if (!titulo) return;
        var candado = document.createElement('span');
        candado.className = 'candado';
        candado.setAttribute('aria-hidden', 'true');
        candado.setAttribute('data-estado', 'normal');
        candado.innerHTML = SVG_CANDADO;
        titulo.parentNode.insertBefore(candado, titulo);
        // Si la página llegó con un error del código, el candado y las casillas tiemblan.
        if (tarjeta.querySelector('.alert-danger')) {
            candado.setAttribute('data-estado', 'error');
            caja.classList.add('error');
            setTimeout(function () { candado.setAttribute('data-estado', 'normal'); caja.classList.remove('error'); }, 700);
        }
        // Al enviar, el candado gira mientras el servidor verifica (el formulario se envía
        // normal; si el código es correcto, la siguiente pantalla llega directamente).
        if (input.form) input.form.addEventListener('submit', function () { candado.setAttribute('data-estado', 'verificando'); });
    }

    function iniciar() {
        iniciarPanel();
        iniciarBarraSuperior();
        iniciarBarraMovil();
        iniciarHoja();
        iniciarCodigo();
        // Errores que llegaron con la página: tiembla el botón de envío de su formulario.
        var error = document.querySelector('.alert-danger, .is-invalid');
        if (error) {
            var f = error.closest('form') || (error.closest('.tarjeta') || document).querySelector('form');
            if (f) temblar(f.querySelector('[type="submit"]'));
        }
    }

    document.addEventListener('pointerdown', onda, { passive: true });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();

    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {}); });
    }
})();
