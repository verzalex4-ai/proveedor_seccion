/**
 * Funciones de Validación Reutilizables
 * Sistema de Gestión de Compras
 */

// ========== VALIDACIÓN DE NOMBRE ==========
function validarNombre(inputSelector, errorSelector) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!input) return;
    
    input.addEventListener('input', function() {
        if (this.value.trim() === '') {
            mostrarError(this, errorSpan, 'El nombre es obligatorio');
        } else {
            mostrarExito(this, errorSpan, 'Nombre válido');
        }
    });
}

// ========== VALIDACIÓN DE CUIT ==========
function validarCUIT(inputSelector, errorSelector) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!input) return;
    
    input.addEventListener('input', function() {
        const cuit = this.value.replace(/[^0-9]/g, '');
        
        if (this.value === '') {
            ocultarMensaje(this, errorSpan);
        } else if (cuit.length !== 11) {
            mostrarError(this, errorSpan, `Debe tener 11 dígitos (actualmente: ${cuit.length})`);
        } else {
            mostrarExito(this, errorSpan, 'CUIT válido (11 dígitos)');
        }
    });
}

// ========== VALIDACIÓN DE EMAIL ==========
function validarEmail(inputSelector, errorSelector) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!input) return;
    
    const patron = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    input.addEventListener('input', function() {
        if (this.value === '') {
            ocultarMensaje(this, errorSpan);
        } else if (!patron.test(this.value)) {
            mostrarError(this, errorSpan, 'Email inválido');
        } else {
            mostrarExito(this, errorSpan, 'Email válido');
        }
    });
}

// ========== VALIDACIÓN DE TELÉFONO ==========
function validarTelefono(inputSelector, errorSelector) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!input) return;
    
    const patron = /^(\+?54\s?)?(\(?\d{2,4}\)?[\s\-]?)?\d{6,8}$/;
    
    input.addEventListener('input', function() {
        if (this.value === '') {
            ocultarMensaje(this, errorSpan);
        } else if (!patron.test(this.value)) {
            mostrarError(this, errorSpan, 'Formato inválido');
        } else {
            mostrarExito(this, errorSpan, 'Formato válido');
        }
    });
}

// ========== VALIDACIÓN DE NÚMERO POSITIVO ==========
function validarNumeroPositivo(inputSelector, errorSelector, minimo = 0) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!input) return;
    
    input.addEventListener('input', function() {
        const valor = parseFloat(this.value);
        
        if (this.value === '') {
            ocultarMensaje(this, errorSpan);
        } else if (isNaN(valor) || valor < minimo) {
            mostrarError(this, errorSpan, `Debe ser mayor o igual a ${minimo}`);
        } else {
            mostrarExito(this, errorSpan, 'Valor válido');
        }
    });
}

// ========== FUNCIONES AUXILIARES ==========
function mostrarError(input, errorSpan, mensaje) {
    input.classList.add('error');
    input.classList.remove('success');
    if (errorSpan) {
        errorSpan.textContent = '✗ ' + mensaje;
        errorSpan.style.color = '#e74a3b';
        errorSpan.style.display = 'block';
    }
}

function mostrarExito(input, errorSpan, mensaje) {
    input.classList.remove('error');
    input.classList.add('success');
    if (errorSpan) {
        errorSpan.textContent = '✓ ' + mensaje;
        errorSpan.style.color = '#1cc88a';
        errorSpan.style.display = 'block';
    }
}

function ocultarMensaje(input, errorSpan) {
    input.classList.remove('error', 'success');
    if (errorSpan) {
        errorSpan.style.display = 'none';
    }
}

// ========== VALIDACIÓN DE FECHAS LÓGICAS ==========
function validarFechaRango(inputInicioSelector, inputFinSelector, errorSelector) {
    const inputInicio = document.querySelector(inputInicioSelector);
    const inputFin = document.querySelector(inputFinSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!inputInicio || !inputFin) return;
    
    const validar = () => {
        if (inputInicio.value && inputFin.value) {
            const fechaInicio = new Date(inputInicio.value);
            const fechaFin = new Date(inputFin.value);
            
            if (fechaFin < fechaInicio) {
                mostrarError(inputFin, errorSpan, 'La fecha final debe ser posterior a la inicial');
            } else {
                mostrarExito(inputFin, errorSpan, 'Rango de fechas válido');
            }
        }
    };
    
    inputInicio.addEventListener('change', validar);
    inputFin.addEventListener('change', validar);
}