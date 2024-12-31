document.addEventListener('DOMContentLoaded', function () {
    const categories = document.querySelectorAll('.wpchill-kb-category');

    categories.forEach(category => {
        const toggle = category.querySelector('.wpchill-kb-category-name .toggle-icon');

        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                const list = category.querySelector('.wpchill-kb-articles-list');
                
                if (list) {
                    list.classList.toggle('active');
                }
                category.classList.toggle('active');
            });
        }
    });
});
