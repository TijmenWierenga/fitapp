/* Scroll-triggered reveal animations */
document.addEventListener('DOMContentLoaded', function () {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Reveal elements on scroll */
    const revealElements = document.querySelectorAll('.traiq-reveal');
    if (revealElements.length > 0 && !prefersReducedMotion) {
        const revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        revealElements.forEach(function (el) {
            revealObserver.observe(el);
        });
    } else {
        /* If reduced motion or no elements, make all visible immediately */
        revealElements.forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    /* Sticky nav background change on scroll */
    const nav = document.getElementById('traiq-nav');
    if (nav) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                nav.classList.add('traiq-nav-scrolled');
            } else {
                nav.classList.remove('traiq-nav-scrolled');
            }
        }, { passive: true });
    }
});
