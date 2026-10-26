/*
 * Formulario de exportes (export_invoice, export_invoice_360, export_purchase, export_inventory).
 * Se configura con atributos data- del <form data-export-form>:
 *   data-require-new-emails="1"  -> las filas de correo agregadas son required
 *   data-period="1"              -> el formulario trae pestanas mes completo / rango manual
 */
(function () {
    var form = document.querySelector('form[data-export-form]');
    if (!form) return;

    var emailsContainer = document.getElementById('emailsContainer');
    var addEmailBtn = document.getElementById('addEmailBtn');
    var formError = document.getElementById('formError');
    var requireNewEmails = form.dataset.requireNewEmails === '1';

    // ---- Emails dinamicos ----
    function buildEmailRow() {
        var row = document.createElement('div');
        row.className = 'email-row';
        row.innerHTML = '' +
            '<input type="email" name="emails[]" class="excel-field-input email-input" placeholder="nombre@correo.com"' + (requireNewEmails ? ' required' : '') + '>' +
            '<button type="button" class="email-remove-btn" title="Quitar correo">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button>';
        return row;
    }

    function updateRemoveButtonsState() {
        var rows = emailsContainer.querySelectorAll('.email-row');
        emailsContainer.querySelectorAll('.email-remove-btn').forEach(function (btn) {
            btn.disabled = rows.length <= 1;
        });
    }

    addEmailBtn.addEventListener('click', function () {
        emailsContainer.appendChild(buildEmailRow());
        updateRemoveButtonsState();
    });

    emailsContainer.addEventListener('click', function (e) {
        var btn = e.target.closest('.email-remove-btn');
        if (!btn || btn.disabled) return;
        btn.closest('.email-row').remove();
        updateRemoveButtonsState();
    });

    // ---- Periodo: mes completo vs rango manual ----
    if (form.dataset.period === '1') {
        var modeTabs = document.querySelectorAll('.mode-tab');
        var monthPanel = document.getElementById('monthPanel');
        var rangePanel = document.getElementById('rangePanel');
        var monthInput = document.getElementById('monthInput');
        var createdStart = document.getElementById('createdStart');
        var createdEnd = document.getElementById('createdEnd');

        var setMode = function (mode) {
            modeTabs.forEach(function (tab) { tab.classList.toggle('active', tab.dataset.mode === mode); });

            if (mode === 'month') {
                monthPanel.classList.add('active');
                rangePanel.classList.remove('active');
                createdStart.value = '';
                createdEnd.value = '';
                createdStart.disabled = true;
                createdEnd.disabled = true;
                monthInput.disabled = false;
            } else {
                rangePanel.classList.add('active');
                monthPanel.classList.remove('active');
                monthInput.value = '';
                monthInput.disabled = true;
                createdStart.disabled = false;
                createdEnd.disabled = false;
            }
        };

        modeTabs.forEach(function (tab) {
            tab.addEventListener('click', function () { setMode(tab.dataset.mode); });
        });

        // Si el usuario vuelve de una validacion fallida con datos de rango, deja esa pestana activa
        setMode(createdStart.value || createdEnd.value ? 'range' : 'month');

        form.addEventListener('submit', function (e) {
            formError.classList.remove('show');

            if (rangePanel.classList.contains('active')) {
                var hasStart = createdStart.value.trim() !== '';
                var hasEnd = createdEnd.value.trim() !== '';

                if (!hasStart || !hasEnd) {
                    e.preventDefault();
                    formError.textContent = 'Debes diligenciar la fecha de inicio y la fecha fin del rango manual.';
                    formError.classList.add('show');
                    return;
                }

                if (new Date(createdStart.value) > new Date(createdEnd.value)) {
                    e.preventDefault();
                    formError.textContent = 'La fecha de inicio no puede ser mayor a la fecha fin.';
                    formError.classList.add('show');
                }
            }
        });
    }

    updateRemoveButtonsState();
})();
