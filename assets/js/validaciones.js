/**
 * Funciones de Validación Reutilizables
 * Sistema de Gestión de Compras
 */

// ========== FUNCIONES AUXILIARES ==========

// Función para mostrar un mensaje de error visualmente
function mostrarError(input, errorSpan, mensaje) {
    input.classList.add('error');
    input.classList.remove('success');
    if (errorSpan) {
        errorSpan.textContent = '✗ ' + mensaje;
        errorSpan.style.color = '#e74a3b';
        errorSpan.style.display = 'block';
    }
}

// Función para mostrar un mensaje de éxito visualmente
function mostrarExito(input, errorSpan, mensaje) {
    input.classList.remove('error');
    input.classList.add('success');
    if (errorSpan) {
        errorSpan.textContent = '✓ ' + mensaje;
        errorSpan.style.color = '#1cc88a';
        errorSpan.style.display = 'block';
    }
}

// Función para ocultar el mensaje de error/éxito
function ocultarMensaje(input, errorSpan) {
    input.classList.remove('error', 'success');
    if (errorSpan) {
        errorSpan.style.display = 'none';
    }
}

// ----------------------------------------------------
// ========== VALIDACIÓN DE TEXTO RESTRINGIDO (Función Modular) ==========
// ----------------------------------------------------

/**
 * Valida campos de texto contra un patrón específico y longitud mínima.
 * NOTA: Los <span> de error DEBEN tener un 'id' que coincida con el errorSelector.
 * @param {string} inputSelector - Selector CSS del input (ej: 'input[name="nombre"]').
 * @param {string} errorSelector - ID del span de error (ej: 'error-nombre').
 * @param {RegExp} patron - Expresión regular a usar.
 * @param {string} mensajeError - Mensaje de error de formato.
 * @param {number} minLength - Longitud mínima requerida (0 si es opcional).
 */
function validarTextoRestringido(inputSelector, errorSelector, patron, mensajeError, minLength = 0) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);

    if (!input) return;

    input.addEventListener('input', function() {
        const valor = this.value.trim();

        if (valor === '') {
            // Manejar campos opcionales vs obligatorios
            if (input.required) {
                mostrarError(this, errorSpan, 'Este campo es obligatorio');
            } else {
                ocultarMensaje(this, errorSpan);
            }
        } else if (valor.length > 0 && valor.length < minLength) {
             mostrarError(this, errorSpan, `Debe tener al menos ${minLength} caracteres`);
        } else if (!patron.test(valor)) {
            mostrarError(this, errorSpan, mensajeError);
        } else {
            mostrarExito(this, errorSpan, 'Campo válido');
        }
    });
}

// ----------------------------------------------------
// ========== FUNCIONES ESPECÍFICAS DEL FORMULARIO ==========
// ----------------------------------------------------

// CORREGIDO: Sólo letras, espacios, puntos y guiones. Excluye números.
function validarNombre(inputSelector, errorSelector) {
    // Expresión regular que NO incluye 0-9
    const patronSoloLetras = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-\.]+$/; 
    
    validarTextoRestringido(
        inputSelector, 
        errorSelector, 
        patronSoloLetras, 
        'Solo letras, espacios, puntos y guiones', 
        3 // Requerido mínimo de 3 caracteres
    );
}

// Razón Social: Permite letras, números, espacios, puntos y guiones (común en empresas)
function validarRazonSocial(inputSelector, errorSelector) {
    const patronRazonSocial = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]+$/; 
    
    validarTextoRestringido(
        inputSelector, 
        errorSelector, 
        patronRazonSocial, 
        'Solo letras, números, espacios, puntos y guiones'
    );
}

// Contacto: Solo letras y espacios
function validarContacto(inputSelector, errorSelector) {
    // Patron: Solo letras y espacios
    const patronSoloLetras = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/; 
    
    // Opcional: limpiar la entrada mientras se escribe
    const input = document.querySelector(inputSelector);
    if (input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ''); 
        });
    }

    validarTextoRestringido(
        inputSelector, 
        errorSelector, 
        patronSoloLetras, 
        'Solo letras y espacios'
    );
}


// ========== VALIDACIÓN DE CUIT (Mantenida) ==========
function validarCUIT(inputSelector, errorSelector) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!input) return;
    
    input.addEventListener('input', function() {
        // Permite guiones pero solo cuenta números
        this.value = this.value.replace(/[^0-9\-]/g, '');
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

// ========== VALIDACIÓN DE EMAIL (Mantenida) ==========
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

// ========== VALIDACIÓN DE TELÉFONO (Mantenida) ==========
function validarTelefono(inputSelector, errorSelector) {
    const input = document.querySelector(inputSelector);
    const errorSpan = document.getElementById(errorSelector);
    
    if (!input) return;
    
    // Patrón flexible para números de Argentina (con o sin +54, área, guiones)
    const patron = /^(\+?54\s?)?(\(?\d{2,4}\)?[\s\-]?)?\d{6,8}$/;
    
    input.addEventListener('input', function() {
        if (this.value === '') {
            ocultarMensaje(this, errorSpan);
        } else if (!patron.test(this.value)) {
            mostrarError(this, errorSpan, 'Formato inválido. Ej: 0387-4123456');
        } else {
            mostrarExito(this, errorSpan, 'Formato válido');
        }
    });
}

// ----------------------------------------------------
// ========== OTRAS FUNCIONES GENERALES (Mantenidas) ==========
// ----------------------------------------------------

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