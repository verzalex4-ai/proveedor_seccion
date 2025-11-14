/* ==========================================
   SISTEMA DE GESTIÓN DE COMPRAS - ESTILOS RESPONSIVE
   ========================================== */

* {
    box-sizing: border-box;
}

body {
    font-family: 'Nunito', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f8f9fc;
    color: #333;
}

.main-container {
    display: flex;
    min-height: calc(100vh - 70px);
}

/* ========== NAVEGACIÓN ========== */
.navbar {
    background-color: white;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e3e6f0;
}

.navbar .logo {
    font-size: 1.1em;
    font-weight: 800;
    color: #4e73df;
}

.navbar .title {
    font-size: 1.5em;
    font-weight: 400;
    color: #5a5c69;
}

/* ========== SIDEBAR ========== */
.sidebar {
    width: 200px;
    background-color: #4e73df;
    color: white;
    padding-top: 20px;
    flex-shrink: 0;
}

.sidebar-heading {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.65rem;
    padding: 0 1rem;
    margin-bottom: 0.5rem;
    margin-top: 1rem;
    font-weight: 800;
}

.sidebar-link {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    padding: 10px 1rem;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 400;
    transition: background-color 0.2s;
    cursor: pointer;
}

.sidebar-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

/* ========== CONTENIDO PRINCIPAL ========== */
.main-content {
    flex-grow: 1;
    padding: 20px;
    overflow-x: auto;
}

/* ========== CARDS ========== */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: white;
    border-radius: 0.35rem;
    padding: 20px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-left: 0.25rem solid;
}

.stat-title, .stat-label {
    font-size: 0.75rem;
    font-weight: 800;
    margin: 0 0 8px 0;
    text-transform: uppercase;
    color: #858796;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #5a5c69;
}

.stat-card.primary { border-left-color: #4e73df; }
.stat-card.danger { border-left-color: #e74a3b; }
.stat-card.success { border-left-color: #1cc88a; }
.stat-card.info { border-left-color: #36b9cc; }
.stat-card.warning { border-left-color: #f6c23e; }

/* ========== FORMULARIOS ========== */
.form-container, .table-container {
    background-color: white;
    padding: 25px;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin: 20px auto;
    width: 100%;
    max-width: 1051px;
}

.form-header {
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e3e6f0;
}

.form-header h2 {
    color: #5a5c69;
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #5a5c69;
    font-weight: 600;
    font-size: 0.875rem;
}

.form-group label .required {
    color: #e74a3b;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    font-size: 0.875rem;
    color: #5a5c69;
    font-family: 'Nunito', sans-serif;
    min-height: 38px;
}

.form-control:focus {
    outline: none;
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.form-control.error {
    border-color: #e74a3b;
}

.form-control.success {
    border-color: #1cc88a;
}

.error-message {
    display: none;
    font-size: 0.75rem;
    margin-top: 3px;
}

select.form-control {
    cursor: pointer;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e3e6f0;
    flex-wrap: wrap;
}

/* ========== TABLAS ========== */
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.table-header h2 {
    color: #5a5c69;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    overflow-x: auto;
    display: block;
}

table thead,
table tbody {
    display: table;
    width: 100%;
    table-layout: fixed;
}

table thead {
    background-color: #f8f9fc;
}

table th {
    padding: 12px;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #4e73df;
    border-bottom: 2px solid #e3e6f0;
}

table td {
    padding: 12px;
    border-bottom: 1px solid #e3e6f0;
    color: #5a5c69;
    font-size: 0.875rem;
    word-wrap: break-word;
}

table tbody tr:hover {
    background-color: #f8f9fc;
}

/* ========== BADGES ========== */
.badge {
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.badge-success, .badge-activo, .badge-recibida, .badge-normal {
    background-color: #d4edda;
    color: #155724;
}

.badge-danger, .badge-inactivo, .badge-cancelada, .badge-vencido {
    background-color: #f8d7da;
    color: #721c24;
}

.badge-warning, .badge-pendiente, .badge-proximo {
    background-color: #fff3cd;
    color: #856404;
}

.badge-info, .badge-enviada {
    background-color: #d1ecf1;
    color: #0c5460;
}

/* ========== BOTONES ========== */
.btn-primary, .btn-danger, .btn-success, .btn-info {
    padding: 10px 15px;
    border: none;
    border-radius: 0.35rem;
    color: white;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.15s;
    text-decoration: none;
    display: inline-block;
    white-space: nowrap;
}

.btn-primary { background-color: #4e73df; }
.btn-primary:hover { background-color: #2e59d9; }
.btn-danger { background-color: #e74a3b; }
.btn-danger:hover { background-color: #d52a1a; }
.btn-success { background-color: #1cc88a; }
.btn-success:hover { background-color: #17a673; }
.btn-info { background-color: #36b9cc; }
.btn-info:hover { background-color: #2c9faf; }

.btn-small {
    padding: 5px 10px;
    font-size: 0.8rem;
    margin-right: 5px;
}

/* ========== ALERTAS ========== */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 0.35rem;
    font-size: 0.9rem;
    transition: opacity 0.3s;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #1cc88a;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #e74a3b;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #36b9cc;
}

/* ========== CAJAS INFORMATIVAS ========== */
.info-box {
    background-color: #f8f9fc;
    padding: 15px;
    border-radius: 0.35rem;
    margin: 20px 0;
    border-left: 4px solid #4e73df;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e3e6f0;
    font-size: 0.875rem;
    flex-wrap: wrap;
    gap: 10px;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row strong {
    color: #4e73df;
}

/* ========== FILTROS ========== */
.filters-box {
    background-color: #f8f9fc;
    padding: 15px;
    border-radius: 0.35rem;
    margin-bottom: 20px;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

/* ========== PRODUCTOS (Órdenes) ========== */
.productos-section {
    margin: 20px 0;
    padding: 20px;
    background-color: #f8f9fc;
    border-radius: 0.35rem;
}

.producto-item {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1.5fr 50px;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.btn-remove {
    background-color: #e74a3b;
    color: white;
    border: none;
    padding: 8px 10px;
    border-radius: 0.35rem;
    cursor: pointer;
    height: 38px;
}

.btn-add {
    background-color: #1cc88a;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 0.35rem;
    cursor: pointer;
}

.totales-box {
    background-color: #e7f3ff;
    padding: 15px;
    border-radius: 0.35rem;
    margin-top: 20px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.total-row.final {
    font-size: 1.1rem;
    font-weight: bold;
    color: #4e73df;
    padding-top: 8px;
    border-top: 2px solid #4e73df;
}

/* ========== UTILIDADES ========== */
.no-data {
    text-align: center;
    padding: 40px;
    color: #858796;
}

/* ========== FOOTER ========== */
.footer {
    background-color: white;
    color: #858796;
    text-align: center;
    padding: 15px 0;
    border-top: 1px solid #e3e6f0;
}

.footer p {
    margin: 0;
}

/* ==========================================
   RESPONSIVE DESIGN - CORRECCIONES
   ========================================== */

@media (max-width: 1024px) {
    .form-container, .table-container {
        max-width: 100%;
        padding: 20px;
    }
    
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .producto-item {
        grid-template-columns: 2fr 1fr 1fr 1fr 50px;
    }
}

@media (max-width: 768px) {
    .main-container {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        max-height: none;
        padding-bottom: 20px;
    }
    
    .navbar {
        flex-direction: column;
        gap: 10px;
    }
    
    .navbar .title {
        font-size: 1.2em;
    }
    
    .stat-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .producto-item {
        grid-template-columns: 1fr;
    }
    
    .btn-remove {
        width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions > * {
        width: 100%;
        text-align: center;
    }
    
    .table-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .table-header h2 {
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    
    /* Tabla responsive */
    table {
        font-size: 0.8rem;
    }
    
    table thead {
        display: none;
    }
    
    table tbody {
        display: block;
    }
    
    table tr {
        display: block;
        margin-bottom: 15px;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        padding: 10px;
    }
    
    table td {
        display: block;
        text-align: right;
        padding: 5px;
        border: none;
        position: relative;
        padding-left: 50%;
    }
    
    table td::before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 0.7rem;
        color: #4e73df;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .form-container, .table-container {
        padding: 15px;
    }
    
    .navbar {
        padding: 10px;
    }
    
    .navbar .logo {
        font-size: 0.9em;
    }
    
    .navbar .title {
        font-size: 1em;
    }
    
    .stat-value {
        font-size: 1.2rem;
    }
    
    .btn-primary, .btn-danger, .btn-success, .btn-info {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
}