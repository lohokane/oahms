document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash]');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.35s ease';
            flash.style.opacity = '0';
        }, 3000);
    }

    // Invoice form: auto-fill rent from resident monthly fee
    const residentSelect = document.querySelector('#resident_id');
    const roomRentInput = document.querySelector('#room_rent');
    if (residentSelect && roomRentInput) {
        const syncRent = () => {
            const opt = residentSelect.options[residentSelect.selectedIndex];
            if (!opt) return;
            const fee = opt.getAttribute('data-monthly-fee');
            if (!fee) return;
            if (roomRentInput.value === '' || Number(roomRentInput.value) === 0) {
                roomRentInput.value = fee;
            }
        };
        residentSelect.addEventListener('change', syncRent);
        syncRent();
    }
});

