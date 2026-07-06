<?php
$flash = getFlashMessage();
?>
<div class="toast-container" id="toast-container">
    <?php if ($flash): ?>
        <div class="toast-message <?= e($flash['type']) ?>" id="global-toast">
            <div class="toast-icon">
                <?php if ($flash['type'] === 'success'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php elseif ($flash['type'] === 'error'): ?>
                    <i class="fa-solid fa-circle-xmark"></i>
                <?php elseif ($flash['type'] === 'warning'): ?>
                    <i class="fa-solid fa-circle-exclamation"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-info"></i>
                <?php endif; ?>
            </div>
            <div class="toast-text"><?= e($flash['text']) ?></div>
            <button class="toast-close" onclick="closeToast(this)" aria-label="Close message">&times;</button>
        </div>
    <?php endif; ?>
</div>

<script>
function closeToast(btn) {
    const toast = btn.closest('.toast-message');
    if (toast) {
        toast.classList.add('toast-fade-out');
        setTimeout(() => toast.remove(), 400);
    }
}

// Auto-close notifications after 5 seconds
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('global-toast');
    if (toast) {
        setTimeout(() => {
            toast.classList.add('toast-fade-out');
            setTimeout(() => toast.remove(), 400);
        }, 5000);
    }
});
</script>
