jQuery(document).ready(function ($) {
    $('.wpchill-kb-color-picker').wpColorPicker();
});
document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.querySelector('.wpchill-kb-dropdown');
    const selected = dropdown.querySelector('.wpchill-kb-dropdown-selected');
    const options = dropdown.querySelector('.wpchill-kb-dropdown-options');
    const hiddenInput = document.getElementById('wpchill_kb_cat_icon');

    selected.addEventListener('click', function () {
        options.style.display = options.style.display === 'block' ? 'none' : 'block';
    });

    options.addEventListener('click', function (event) {
        const option = event.target.closest('.wpchill-kb-dropdown-option');
        if (option) {
            options.querySelectorAll('.wpchill-kb-dropdown-option').forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
            selected.querySelector('.dashicons').className = `dashicons ${option.getAttribute('data-value')}`;
            selected.querySelector('.selected-label').textContent = option.textContent.trim();
            hiddenInput.value = option.getAttribute('data-value');
            options.style.display = 'none';
        }
    });

    // Close dropdown if clicked outside
    document.addEventListener('click', function (event) {
        if (!dropdown.contains(event.target)) {
            options.style.display = 'none';
        }
    });
});
