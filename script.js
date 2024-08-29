document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('offset-slider');
    const image = document.getElementById('offset-image');

    slider.addEventListener('input', function() {
        const offsetValue = slider.value;
        image.style.transform = `translate(${offsetValue / 10}px)`;
    });
});
