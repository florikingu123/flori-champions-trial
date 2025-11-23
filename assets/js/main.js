// VolunteerHub Main JS - Updated: Fixed null reference errors - v2.0
(function() {
  "use strict";
  
  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
  function init() {


  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader) return;
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);


  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    if (mobileNavToggleBtn) {
      mobileNavToggleBtn.classList.toggle('bi-list');
      mobileNavToggleBtn.classList.toggle('bi-x');
    }
  }
  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
  }


  const navmenuLinks = document.querySelectorAll('#navmenu a');
  if (navmenuLinks.length > 0) {
    navmenuLinks.forEach(navmenu => {
      navmenu.addEventListener('click', () => {
        if (document.querySelector('.mobile-nav-active')) {
          mobileNavToogle();
        }
      });
    });
  }


  const toggleDropdowns = document.querySelectorAll('.navmenu .toggle-dropdown');
  if (toggleDropdowns.length > 0) {
    toggleDropdowns.forEach(navmenu => {
      navmenu.addEventListener('click', function(e) {
        e.preventDefault();
        if (this.parentNode) {
          this.parentNode.classList.toggle('active');
        }
        if (this.parentNode && this.parentNode.nextElementSibling) {
          this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
        }
        e.stopImmediatePropagation();
      });
    });
  }


  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }


  let scrollTop = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  if (scrollTop) {
    scrollTop.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);


  function aosInit() {
    if (typeof AOS !== 'undefined') {
      AOS.init({
        duration: 600,
        easing: 'ease-in-out',
        once: true,
        mirror: false
      });
    }
  }
  window.addEventListener('load', aosInit);


  const carouselIndicators = document.querySelectorAll('.carousel-indicators');
  if (carouselIndicators.length > 0) {
    carouselIndicators.forEach((carouselIndicator) => {
      const carousel = carouselIndicator.closest('.carousel');
      if (carousel) {
        carousel.querySelectorAll('.carousel-item').forEach((carouselItem, index) => {
          if (index === 0) {
            carouselIndicator.innerHTML += `<li data-bs-target="#${carousel.id}" data-bs-slide-to="${index}" class="active"></li>`;
          } else {
            carouselIndicator.innerHTML += `<li data-bs-target="#${carousel.id}" data-bs-slide-to="${index}"></li>`;
          }
        });
      }
    });
  }


  if (typeof GLightbox !== 'undefined') {
    const glightbox = GLightbox({
      selector: '.glightbox'
    });
  }


  if (typeof PureCounter !== 'undefined') {
    new PureCounter();
  }


  function initSwiper() {
    if (typeof Swiper === 'undefined') return;
    document.querySelectorAll(".init-swiper").forEach(function(swiperElement) {
      const swiperConfig = swiperElement.querySelector(".swiper-config");
      if (!swiperConfig) return;
      let config = JSON.parse(swiperConfig.innerHTML.trim());

      if (swiperElement.classList.contains("swiper-tab")) {
        if (typeof initSwiperWithCustomPagination === 'function') {
          initSwiperWithCustomPagination(swiperElement, config);
        }
      } else {
        new Swiper(swiperElement, config);
      }
    });
  }

  window.addEventListener("load", initSwiper);


  if (typeof Waypoint !== 'undefined') {
    let skillsAnimation = document.querySelectorAll('.skills-animation');
    skillsAnimation.forEach((item) => {
      new Waypoint({
        element: item,
        offset: '80%',
        handler: function(direction) {
          let progress = item.querySelectorAll('.progress .progress-bar');
          progress.forEach(el => {
            el.style.width = el.getAttribute('aria-valuenow') + '%';
          });
        }
      });
    });
  }


  if (typeof Isotope !== 'undefined' && typeof imagesLoaded !== 'undefined') {
    document.querySelectorAll('.isotope-layout').forEach(function(isotopeItem) {
      const isotopeContainer = isotopeItem.querySelector('.isotope-container');
      if (!isotopeContainer) return;
      
      let layout = isotopeItem.getAttribute('data-layout') ?? 'masonry';
      let filter = isotopeItem.getAttribute('data-default-filter') ?? '*';
      let sort = isotopeItem.getAttribute('data-sort') ?? 'original-order';

      let initIsotope;
      imagesLoaded(isotopeContainer, function() {
        initIsotope = new Isotope(isotopeContainer, {
          itemSelector: '.isotope-item',
          layoutMode: layout,
          filter: filter,
          sortBy: sort
        });
      });

      isotopeItem.querySelectorAll('.isotope-filters li').forEach(function(filters) {
        filters.addEventListener('click', function() {
          const filterActive = isotopeItem.querySelector('.isotope-filters .filter-active');
          if (filterActive) {
            filterActive.classList.remove('filter-active');
          }
          this.classList.add('filter-active');
          if (initIsotope) {
            initIsotope.arrange({
              filter: this.getAttribute('data-filter')
            });
          }
          if (typeof aosInit === 'function') {
            aosInit();
          }
        }, false);
      });

    });
  }
  
  } // End of init function

})();