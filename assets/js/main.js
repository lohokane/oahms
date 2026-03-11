document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash]');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.35s ease';
            flash.style.opacity = '0';
        }, 3000);
    }
});

