const sections =
    document.querySelectorAll('section[id]');

const navLinks =
    document.querySelectorAll('.disease-nav a');

window.addEventListener(
    'scroll',
    () => {

        let current = '';

        sections.forEach(section => {

            const top =
                section.offsetTop - 120;

            if (window.scrollY >= top) {

                current =
                    section.id;

            }

        });

        navLinks.forEach(link => {

            link.classList.remove(
                'btn-primary'
            );

            link.classList.add(
                'btn-outline-primary'
            );

            if (
                link.getAttribute('href') ===
                '#' + current
            ) {

                link.classList.remove(
                    'btn-outline-primary'
                );

                link.classList.add(
                    'btn-primary'
                );

            }

        });

    }
);

document
.querySelectorAll(
'.accordion-collapse'
)
.forEach(item => {

    item.addEventListener(

        'shown.bs.collapse',

        function () {

            this.scrollIntoView({

                behavior: 'smooth',

                block: 'nearest'

            });

        }

    );

});