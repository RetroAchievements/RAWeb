@props(['page' => null])

<script>
(function() {
    const BANNER_HEIGHT = 300;
    let isScrollWatcherTicking = false;

    function getDesktopNav() {
        return document.querySelector('nav.z-20');
    }

    function updateNavbarOnScroll() {
        const desktopNav = getDesktopNav();
        if (!desktopNav?.classList.contains('has-banner')) {
            return;
        }

        desktopNav.classList.toggle('scrolled-past-banner', window.scrollY > BANNER_HEIGHT);
    }

    function setBannerState(hasBanner) {
        const desktopNav = getDesktopNav();
        if (!desktopNav) {
            return;
        }

        desktopNav.classList.toggle('has-banner', hasBanner);
        if (!hasBanner) {
            desktopNav.classList.remove('scrolled-past-banner');
        }
    }

    @if(!empty($page['props']['banner']['desktopMdWebp']))
        setBannerState(true);
    @endif
    updateNavbarOnScroll();

    document.addEventListener('inertia:navigate', (event) => {
        setBannerState(event.detail.page.props?.banner?.desktopMdWebp != null);
    });

    window.addEventListener('scroll', function() {
        if (!isScrollWatcherTicking) {
            window.requestAnimationFrame(function() {
                updateNavbarOnScroll();
                isScrollWatcherTicking = false;
            });
            isScrollWatcherTicking = true;
        }
    }, { passive: true });
})();
</script>
