<?php
/**
 * Footer Global - SIMPLIFICADO
 * Sin JavaScript redundante de submenús
 */
?>

</main>
</div>

<footer class="footer">
    <p>&copy; 2025 Sistema de Gestión de Compras y Proveedores.</p>
</footer>

<script>
// Auto-cerrar alertas después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>

<?php if (isset($extra_js)): ?>
<script><?php echo $extra_js; ?></script>
<?php endif; ?>

</body>
</html>