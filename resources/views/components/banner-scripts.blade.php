@props(['page' => null])

@php
    $hasCustomBanner = !empty($page['props']['banner']['desktopMdWebp'] ?? null);
    $isGamePage = ($page['component'] ?? '') === 'game/[game]';
    $isDesktop = ($page['props']['ziggy']['device'] ?? '') === 'desktop';

    $hasBanner = $hasCustomBanner || ($isGamePage && $isDesktop);
@endphp

<script>
(function() {
    const BANNER_HEIGHT = 300;
    const FALLBACK_BANNER_HEIGHT = 212;
    let isScrollWatcherTicking = false;

    function getDesktopNav() {
        return document.querySelector('nav.z-30');
    }

    function getMobileAndDesktopNavs() {
        return document.querySelectorAll('nav.z-30, nav.z-20');
    }

    function updateNavbarOnScroll(bannerHeight) {
        const desktopNav = getDesktopNav();
        if (!desktopNav?.classList.contains('has-banner')) {
            return;
        }

        desktopNav.classList.toggle('scrolled-past-banner', window.scrollY > bannerHeight);
    }

    function setBannerState(hasBanner) {
        const navs = getMobileAndDesktopNavs();

        navs.forEach(function(nav) {
            nav.classList.toggle('has-banner', hasBanner);
            nav.classList.toggle('!bg-transparent', hasBanner);
            if (!hasBanner) {
                nav.classList.remove('scrolled-past-banner');
            }
        });
    }

    @if($hasBanner)
        setBannerState(true);
    @endif

    let currentBannerHeight = {{ $hasCustomBanner ? 'BANNER_HEIGHT' : 'FALLBACK_BANNER_HEIGHT' }};
    updateNavbarOnScroll(currentBannerHeight);

    document.addEventListener('inertia:navigate', (event) => {
        const props = event.detail.page.props || {};
        const hasCustomBannerNav = props.banner?.desktopMdWebp != null;
        const isGamePageNav = event.detail.page.component === 'game/[game]';
        const isDesktopNav = props.ziggy?.device === 'desktop';

        // Desktop game pages always have a banner area.
        const hasBannerArea = hasCustomBannerNav || (isGamePageNav && isDesktopNav);
        setBannerState(hasBannerArea);

        // Update banner height for scroll detection.
        currentBannerHeight = hasCustomBannerNav ? BANNER_HEIGHT : FALLBACK_BANNER_HEIGHT;
    });

    window.addEventListener('scroll', function() {
        if (!isScrollWatcherTicking) {
            window.requestAnimationFrame(function() {
                updateNavbarOnScroll(currentBannerHeight);
                isScrollWatcherTicking = false;
            });
            isScrollWatcherTicking = true;
        }
    }, { passive: true });
})();
</script>
