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

    document.querySelectorAll('.wpchill-sidebar-toggle').forEach(button => {
        button.addEventListener('click', function () {
          const parentSidebar = this.closest('.wpchill-kb-sidebar');
      
          if (parentSidebar) {
            if (parentSidebar.classList.contains('open')) {
              parentSidebar.classList.remove('open');
            } else {
              document.querySelectorAll('.wpchill-kb-sidebar.open').forEach(sidebar => {
                sidebar.classList.remove('open');
              });
              parentSidebar.classList.add('open');
            }
          }
        });
      });
      
});
