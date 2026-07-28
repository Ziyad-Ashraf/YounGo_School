(function () {
    document.documentElement.classList.add('youngo-ready');

    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var header = document.querySelector('.youngo-header');

    if (header) {
        var menuToggle = header.querySelector('.youngo-menu-toggle');
        var primaryNav = header.querySelector('.youngo-nav');
        var closeMenu = function () {
            header.classList.remove('is-menu-open');
            if (menuToggle) {
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.querySelector('i').className = 'fa-solid fa-bars';
            }
        };

        if (menuToggle && primaryNav) {
            menuToggle.addEventListener('click', function () {
                var isOpen = header.classList.toggle('is-menu-open');
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                menuToggle.querySelector('i').className = isOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            });
            primaryNav.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeMenu();
            });
        }

        var lastScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
        var isScrollTicking = false;
        var hideAfter = 120;
        var scrollDelta = 8;

        var updateHeaderState = function () {
            var currentScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
            var isPastTop = currentScrollY > 10;
            var isScrollingDown = currentScrollY > lastScrollY;

            header.classList.toggle('is-scrolled', isPastTop);

            if (Math.abs(currentScrollY - lastScrollY) >= scrollDelta) {
                if (isScrollingDown && currentScrollY > hideAfter && !header.matches(':focus-within')) {
                    header.classList.add('is-hidden');
                } else {
                    header.classList.remove('is-hidden');
                }

                lastScrollY = Math.max(currentScrollY, 0);
            }

            isScrollTicking = false;
        };

        window.addEventListener('scroll', function () {
            if (!isScrollTicking) {
                window.requestAnimationFrame(updateHeaderState);
                isScrollTicking = true;
            }
        }, { passive: true });
    }

    if (prefersReducedMotion) {
        return;
    }

    var revealTargets = document.querySelectorAll(
        '.youngo-hero__content, .youngo-hero__visual, .youngo-section__heading, .youngo-category-card, .youngo-course-card, .youngo-benefit-card, .youngo-testimonial-card, .youngo-blog-card, .youngo-faq-item, .youngo-final-cta__panel, .youngo-auth-shell'
    );

    if (!revealTargets.length) {
        return;
    }

    revealTargets.forEach(function (target) {
        target.classList.add('youngo-reveal');
    });

    if (!('IntersectionObserver' in window)) {
        revealTargets.forEach(function (target) {
            target.classList.add('is-visible');
        });
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        rootMargin: '0px 0px -8% 0px',
        threshold: 0.12
    });

    revealTargets.forEach(function (target) {
        observer.observe(target);
    });
}());

(function () {
    function parseJson(response) {
        if (typeof response !== 'string') {
            return response;
        }

        try {
            return JSON.parse(response);
        } catch (error) {
            return null;
        }
    }

    window.redirectTo = function (url) {
        window.location.href = url;
    };

    window.distributeServerResponse = function (response) {
        var data = parseJson(response);

        if (!data) {
            return;
        }

        if (data.reload) {
            window.location.reload();
            return;
        }

        if (data.redirectTo) {
            window.location.href = data.redirectTo;
            return;
        }

        if (data.show && window.jQuery && jQuery(data.show).length) {
            jQuery(data.show).css('display', 'inline-flex');
        }

        if (data.hide && window.jQuery && jQuery(data.hide).length) {
            jQuery(data.hide).hide();
        }

        if (data.fadeIn && window.jQuery && jQuery(data.fadeIn).length) {
            jQuery(data.fadeIn).fadeIn();
        }

        if (data.fadeOut && window.jQuery && jQuery(data.fadeOut).length) {
            jQuery(data.fadeOut).fadeOut();
        }

        if (data.addClass && window.jQuery && jQuery(data.addClass.elem).length) {
            jQuery(data.addClass.elem).addClass(data.addClass.content);
        }

        if (data.removeClass && window.jQuery && jQuery(data.removeClass.elem).length) {
            jQuery(data.removeClass.elem).removeClass(data.removeClass.content);
        }

        if (data.toggleClass && window.jQuery && jQuery(data.toggleClass.elem).length) {
            jQuery(data.toggleClass.elem).toggleClass(data.toggleClass.content);
        }

        if (data.text && window.jQuery && jQuery(data.text.elem).length) {
            jQuery(data.text.elem).text(data.text.content);
        }

        if (data.html && window.jQuery && jQuery(data.html.elem).length) {
            jQuery(data.html.elem).html(data.html.content);
            initAjaxForms();
        }

        if (data.load && window.jQuery && jQuery(data.load.elem).length) {
            jQuery(data.load.elem).html(data.load.content);
            initAjaxForms();
        }

        if (data.append && window.jQuery && jQuery(data.append.elem).length) {
            jQuery(data.append.elem).append(data.append.content);
        }

        if (data.prepend && window.jQuery && jQuery(data.prepend.elem).length) {
            jQuery(data.prepend.elem).prepend(data.prepend.content);
        }

        if (data.after && window.jQuery && jQuery(data.after.elem).length) {
            jQuery(data.after.elem).after(data.after.content);
        }

        if (data.pushState) {
            history.pushState({}, data.pushState.title, data.pushState.url);
        }

        if (data.error && window.toastr) {
            toastr.error(data.error);
        }

        if (data.success && window.toastr) {
            toastr.success(data.success);
        }
    };

    window.actionTo = function (url, type) {
        if (!window.jQuery) {
            window.location.href = url;
            return;
        }

        jQuery.ajax({
            type: type || 'get',
            url: url,
            success: function (response) {
                window.distributeServerResponse(response);
            }
        });
    };

    window.lesson_preview = function (url, title) {
        var modal = document.getElementById('lesson_preview');
        if (!modal || !window.jQuery) {
            window.location.href = url;
            return;
        }

        var titleEl = modal.querySelector('.title');
        var body = modal.querySelector('.youngo-modal__body');

        if (titleEl) {
            titleEl.textContent = title || '';
        }

        if (body) {
            body.innerHTML = '<div class="youngo-modal-loader">Loading...</div>';
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        jQuery.ajax({
            url: url,
            success: function (response) {
                if (body) {
                    body.innerHTML = response;
                }
            }
        });
    };

    function closeYounGoModal(modal) {
        if (!modal) {
            return;
        }

        if (window.player && typeof window.player.pause === 'function') {
            window.player.pause();
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        var body = modal.querySelector('.youngo-modal__body');
        if (body) {
            body.innerHTML = '';
        }
    }

    function initAjaxForms() {
        if (!window.jQuery) {
            return;
        }

        jQuery('.ajaxForm:not(.youngo-ajax-ready)').each(function () {
            var form = jQuery(this);
            form.addClass('youngo-ajax-ready');

            if (typeof form.ajaxForm === 'function') {
                form.ajaxForm({
                    complete: function (xhr) {
                        setTimeout(function () {
                            window.distributeServerResponse(xhr.responseText);
                        }, 250);
                    }
                });
                return;
            }

            form.on('submit', function (event) {
                event.preventDefault();
                jQuery.ajax({
                    type: form.attr('method') || 'post',
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function (response) {
                        window.distributeServerResponse(response);
                    }
                });
            });
        });
    }

    document.addEventListener('click', function (event) {
        var closeButton = event.target.closest('[data-youngo-modal-close]');
        if (closeButton) {
            closeYounGoModal(closeButton.closest('.youngo-modal'));
            return;
        }

        if (event.target.classList.contains('youngo-modal')) {
            closeYounGoModal(event.target);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        initAjaxForms();

        document.querySelectorAll('.checkPropagation').forEach(function (element) {
            element.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
            });
        });
    });
}());
