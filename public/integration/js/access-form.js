/* Formulario de acceso por correo: deshabilita el boton y muestra el spinner al enviar. */
(function () {
    var form = document.getElementById('accessForm');
    var submitBtn = document.getElementById('submitBtn');
    if (!form || !submitBtn) return;
    var btnLabel = submitBtn.querySelector('.btn-label');

    form.addEventListener('submit', function () {
        // Si el navegador bloquea el envio por validacion HTML5 el evento 'submit' no llega aqui.
        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');
        btnLabel.textContent = 'Enviando...';
    });
})();
