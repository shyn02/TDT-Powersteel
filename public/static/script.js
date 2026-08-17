document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================
    // 0. NAV ITEM TAGGING
    // ==========================================================
    // The active/inactive state itself is now decided server-side in
    // each template (class="{% if ... %}active{% endif %}"), based on
    // request.path -- that's correct for every page, including product
    // and blog subpages. This just tags .nav-item for the mobile-menu
    // and smooth-scroll logic further below, which query for it.
    (function tagNavItems() {
        const navLinkEls = document.querySelectorAll('#navLinks > a');
        navLinkEls.forEach(link => link.classList.add('nav-item'));
    })();

    // ==========================================================
    // 1. GLOBAL TOAST NOTIFICATION LOGIC (Consolidated & Fail-safe)
    // ==========================================================
    function showToast(message) {
        // Safe check: If container doesn't exist, create it dynamically
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        // Create a new toast element
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        
        // Insert checkmark icon, message container, and close button
        toast.innerHTML = `
            <div class="toast-icon">✓</div>
            <div class="toast-message">${message}</div>
            <button class="toast-close-btn" type="button">&times;</button>
        `;
        
        // Append to container
        container.appendChild(toast);
        
        // Trigger slide transition
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Setup manual close button
        const closeBtn = toast.querySelector('.toast-close-btn');
        closeBtn.addEventListener('click', () => {
            removeToast(toast);
        });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            removeToast(toast);
        }, 5000);
    }

    function removeToast(toast) {
        if (!toast) return;
        
        // Start the fade-out/slide-out animation
        toast.classList.remove('show');
        
        // Safety backup timer to force-remove it from the DOM after 500ms 
        const forceRemove = setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 500);

        // Keep the transitionend listener as primary, clear safety timer if it succeeds
        toast.addEventListener('transitionend', () => {
            clearTimeout(forceRemove);
            if (toast.parentNode) {
                toast.remove();
            }
        });
    }

    // ==========================================================
    // 2. MODAL FUNCTIONALITIES WITH ALL DYNAMIC SUB-PRODUCTS MAP
    // ==========================================================
    const modal = document.getElementById('quoteModal');
    const closeModalBtn = document.getElementById('closeModal');
    const quoteForm = document.getElementById('quoteForm');
    const selectedProductInput = document.getElementById('selectedProduct');
    const triggerBtns = document.querySelectorAll('.btn-quote-trigger');
    const headerQuoteBtn = document.getElementById('headerQuoteBtn');

    // Each product is { name, sizes }. "sizes" is optional — when present,
    // the Size / Specification dropdown appears and is populated with it.
    // Products without a defined discrete size list (e.g. only a mm/inch
    // range in the catalog) simply omit "sizes", and the size field stays
    // hidden so the person can note their exact requirement in "Estimated
    // Quantity Needed" instead.
    //
    // This used to be a hardcoded object here. It's now fetched from the
    // database (admin: Products > a product's "Sizes" field) so adding or
    // renaming a product/size shows up here automatically — no code edits.
    let subProductsData = {};
    let subProductsDataReady = fetch('/api/quote-product-data/')
        .then(res => res.json())
        .then(data => { subProductsData = data; })
        .catch(err => {
            console.error('Could not load quote product data:', err);
            subProductsData = {
                "General Steel Inquiry": [
                    { name: "Custom Steel Fabrication" },
                    { name: "Bulk Construction Project Supply" },
                    { name: "Other Steel Materials" }
                ]
            };
        });

    function getProductList(categoryName) {
        return subProductsData[categoryName] || subProductsData["General Steel Inquiry"];
    }

    function findProduct(categoryName, productName) {
        return getProductList(categoryName).find(p => p.name === productName);
    }

    function populateSizes(categoryName, productName) {
        const sizeGroup = document.getElementById('sizeGroup');
        const sizeSelect = document.getElementById('sizeSelect');
        if (!sizeGroup || !sizeSelect) return;

        const product = findProduct(categoryName, productName);
        const sizes = product && product.sizes ? product.sizes : [];

        sizeSelect.innerHTML = '';

        if (sizes.length === 0) {
            sizeGroup.style.display = 'none';
            return;
        }

        sizeGroup.style.display = '';
        sizes.forEach(size => {
            const option = document.createElement('option');
            option.value = size;
            option.textContent = size;
            sizeSelect.appendChild(option);
        });
    }

    function populateSubProducts(categoryName) {
        const subProductSelect = document.getElementById('subProductSelect');
        if (!subProductSelect) return;

        subProductSelect.innerHTML = '';
        const list = getProductList(categoryName);

        list.forEach(item => {
            const option = document.createElement('option');
            option.value = item.name;
            option.textContent = item.name;
            subProductSelect.appendChild(option);
        });

        // Prime the size dropdown for whichever product ends up selected first
        populateSizes(categoryName, subProductSelect.value);
    }

    function openModalWithProduct(productName) {
        if (!modal) return;
        const categoryName = productName || "General Steel Inquiry";
        selectedProductInput.value = categoryName;
        selectedProductInput.dataset.category = categoryName;

        modal.classList.add('active');
        document.body.classList.add('quote-modal-open'); 
        document.body.style.overflowY = 'hidden';

        // Product/size data loads once on page load; in the rare case a
        // click beats that fetch, wait for it before filling the dropdown.
        subProductsDataReady.then(() => populateSubProducts(categoryName));
    }

    const subProductSelectEl = document.getElementById('subProductSelect');
    if (subProductSelectEl) {
        subProductSelectEl.addEventListener('change', () => {
            const categoryName = selectedProductInput.dataset.category || selectedProductInput.value;
            populateSizes(categoryName, subProductSelectEl.value);
        });
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.classList.remove('quote-modal-open');
        document.body.style.overflowY = ''; 
        if (quoteForm) quoteForm.reset();
        const sizeGroup = document.getElementById('sizeGroup');
        if (sizeGroup) sizeGroup.style.display = 'none';
        const qHowHeardOtherGroupEl = document.getElementById('qHowHeardOtherGroup');
        if (qHowHeardOtherGroupEl) qHowHeardOtherGroupEl.style.display = 'none';
    }

    triggerBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const productName = btn.getAttribute('data-product');
            openModalWithProduct(productName);
        });
    });

    if (headerQuoteBtn) {
        headerQuoteBtn.addEventListener('click', () => {
            openModalWithProduct("General Steel Inquiry");
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }
    
    if (modal) {
        modal.addEventListener('click', (e) => { 
            if (e.target === modal) closeModal(); 
        });
    }

    // ==========================================================
    // 3. SUBMIT HANDLERS WITH SPINNER SIMULATION
    // ==========================================================
    
    // Modal Form
    const ADMIN_ENDPOINT = '/api/submit-quote/';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (quoteForm) {
        quoteForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('quoteSubmitBtn');
            const clientInput = document.getElementById('clientName');
            const subItemInput = document.getElementById('subProductSelect');
            const sizeInput = document.getElementById('sizeSelect');
            const sizeGroup = document.getElementById('sizeGroup');
            
            const client = clientInput ? clientInput.value : "Guest";
            const subItem = subItemInput ? subItemInput.value : "your items";
            const hasSize = sizeGroup && sizeGroup.style.display !== 'none' && sizeInput && sizeInput.value;
            const itemLabel = hasSize ? `${subItem} (${sizeInput.value})` : subItem;

            if(submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }

            try {
                const formData = new FormData(quoteForm);
                const response = await fetch(ADMIN_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    showToast(`Thank you, ${client}! We have successfully received your quote request for "${itemLabel}".`);
                    closeModal();
                } else {
                    showToast('May error sa pag-send. Subukan ulit.');
                }
            } catch (error) {
                console.error('Quote submit error:', error);
                showToast('Network error. Check your connection.');
            } finally {
                if(submitBtn) {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            }
        });
    }

    // Direct Contact Form
    const directForm = document.getElementById('directContactForm');

    // "How Did You Hear About Us" -> show "Please Specify" only on "Others"
    const cHowHeard = document.getElementById('cHowHeard');
    const cHowHeardOtherGroup = document.getElementById('cHowHeardOtherGroup');
    const cHowHeardOtherInput = document.getElementById('cHowHeardOther');

    if (cHowHeard && cHowHeardOtherGroup) {
        cHowHeard.addEventListener('change', function () {
            const isOthers = cHowHeard.value === 'others';
            cHowHeardOtherGroup.style.display = isOthers ? 'block' : 'none';
            if (cHowHeardOtherInput) {
                cHowHeardOtherInput.required = isOthers;
                if (!isOthers) cHowHeardOtherInput.value = '';
            }
        });
    }

    if (directForm) {
        directForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('contactSubmitBtn');
            const nameInput = document.getElementById('cName');
            const name = nameInput ? nameInput.value : "Guest";

            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }

            try {
                const formData = new FormData(directForm);
                const response = await fetch(ADMIN_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    showToast(`Message sent! Thank you, ${name}. Our sales coordinator will get in touch with you as soon as possible.`);
                    directForm.reset();
                    if (cHowHeardOtherGroup) cHowHeardOtherGroup.style.display = 'none';
                } else {
                    showToast('May error sa pag-send. Subukan ulit.');
                }
            } catch (error) {
                console.error('Contact submit error:', error);
                showToast('Network error. Check your connection.');
            } finally {
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            }
        });
    }

    // ==========================================================
    // REFERRAL FORM (Refer a Project page)
    // ==========================================================
    const referralForm = document.getElementById('referralForm');

    if (referralForm) {
        referralForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('referralSubmitBtn');
            const nameInput = document.getElementById('ref_fullname');
            const name = nameInput ? nameInput.value : 'there';

            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }

            try {
                const formData = new FormData(referralForm);
                const response = await fetch(ADMIN_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    showToast(`Thank you, ${name}! Your referral has been received. Our team will follow up with the referred company shortly.`);
                    referralForm.reset();
                } else {
                    showToast('May error sa pag-send. Subukan ulit.');
                }
            } catch (error) {
                console.error('Referral submit error:', error);
                showToast('Network error. Check your connection.');
            } finally {
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            }
        });
    }

    // ==========================================================
    // MOBILE "REQUEST A QUOTE" BUTTON -> OPENS HERO CARD AS POPUP
    // (No effect on desktop — the button and popup styles are
    // only active below the 991px breakpoint via CSS.)
    // ==========================================================
    const heroQuoteMobileBtn = document.getElementById('heroQuoteMobileBtn');
    const heroQuoteOverlay = document.getElementById('heroQuoteOverlay');
    const heroQuoteCloseBtn = document.getElementById('heroQuoteCloseBtn');

    function openHeroQuoteOverlay() {
        if (!heroQuoteOverlay) return;
        heroQuoteOverlay.classList.add('mobile-quote-active');
        document.body.style.overflowY = 'hidden';
    }

    function closeHeroQuoteOverlay() {
        if (!heroQuoteOverlay) return;
        heroQuoteOverlay.classList.remove('mobile-quote-active');
        document.body.style.overflowY = '';
    }

    if (heroQuoteMobileBtn) {
        heroQuoteMobileBtn.addEventListener('click', openHeroQuoteOverlay);
    }

    if (heroQuoteCloseBtn) {
        heroQuoteCloseBtn.addEventListener('click', closeHeroQuoteOverlay);
    }

    if (heroQuoteOverlay) {
        // Click on the dark backdrop (outside the card) closes it too
        heroQuoteOverlay.addEventListener('click', (e) => {
            if (e.target === heroQuoteOverlay) closeHeroQuoteOverlay();
        });
    }

    // Hero Section Form
    const heroQuoteForm = document.getElementById('heroQuoteForm');

    // "How Did You Hear About Us" on the hero form -> show "Please specify"
    // only when "Others" is selected (same pattern as the other forms).
    const heroHowHeard = document.getElementById('heroHowHeard');
    const heroHowHeardOtherGroup = document.getElementById('heroHowHeardOtherGroup');
    const heroHowHeardOtherInput = document.getElementById('heroHowHeardOther');

    if (heroHowHeard && heroHowHeardOtherGroup) {
        heroHowHeard.addEventListener('change', function () {
            const isOthers = heroHowHeard.value === 'others';
            heroHowHeardOtherGroup.style.display = isOthers ? 'block' : 'none';
            if (heroHowHeardOtherInput && !isOthers) {
                heroHowHeardOtherInput.value = '';
            }
        });
    }

    if (heroQuoteForm) {
        heroQuoteForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const nameInput = document.getElementById('heroName');
            const name = nameInput && nameInput.value.trim() !== "" ? nameInput.value.trim() : "Guest";
            const submitBtn = heroQuoteForm.querySelector('.btn-submit-hero');

            if (submitBtn) submitBtn.disabled = true;

            try {
                const formData = new FormData(heroQuoteForm);
                const response = await fetch(ADMIN_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    showToast(`Thank you, ${name}! We have successfully received your quote request.`);
                    heroQuoteForm.reset();
                    if (heroHowHeardOtherGroup) heroHowHeardOtherGroup.style.display = 'none';
                    closeHeroQuoteOverlay();
                } else {
                    showToast('May error sa pag-send. Subukan ulit.');
                }
            } catch (error) {
                console.error('Hero quote submit error:', error);
                showToast('Network error. Check your connection.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    } else {
        console.error("DEBUG: 'heroQuoteForm' was not found in your HTML! Check your form ID.");
    }

    // ==========================================================
    // 4. MOBILE MENU FUNCTIONALITIES
    // ==========================================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    const navOverlay = document.getElementById('navOverlay');
    const navItems = document.querySelectorAll('.nav-item');

    // Plain `overflow:hidden` on html/body doesn't reliably block scroll on
    // mobile Safari, which let the page (and the sticky header/X button)
    // drift away while the menu was open. Pinning the body in place at its
    // current scroll offset is the reliable fix; we restore that offset on
    // close.
    let scrollLockY = 0;

    function lockBodyScroll() {
        scrollLockY = window.scrollY || window.pageYOffset || 0;
        document.documentElement.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollLockY}px`;
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.width = '100%';
    }

    function unlockBodyScroll() {
        document.documentElement.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';
        window.scrollTo(0, scrollLockY);
    }

    function isMobileMenuOpen() {
        return navLinks && navLinks.classList.contains('mobile-active');
    }

    function openMobileMenu() {
        mobileMenuBtn.classList.add('open');
        navLinks.classList.add('mobile-active');
        if (navOverlay) navOverlay.classList.add('active');
        lockBodyScroll();
    }

    function closeMobileMenu() {
        mobileMenuBtn.classList.remove('open');
        navLinks.classList.remove('mobile-active');
        if (navOverlay) navOverlay.classList.remove('active');
        unlockBodyScroll();
    }

    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isMobileMenuOpen()) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });

        navItems.forEach(item => {
            item.addEventListener('click', closeMobileMenu);
        });

        // The nav links themselves aren't all tagged .nav-item, so also
        // close the menu whenever any link inside #navLinks is tapped.
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        // Tapping the dimmed overlay closes the menu.
        if (navOverlay) {
            navOverlay.addEventListener('click', closeMobileMenu);
        }

        // Belt-and-suspenders: close on ANY tap outside the panel/button,
        // regardless of what else is on top of the overlay at that point.
        document.addEventListener('click', (e) => {
            if (!isMobileMenuOpen()) return;
            if (navLinks.contains(e.target) || mobileMenuBtn.contains(e.target)) return;
            closeMobileMenu();
        });

        // Also close on Escape and on resize past the mobile breakpoint.
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isMobileMenuOpen()) closeMobileMenu();
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 991 && isMobileMenuOpen()) closeMobileMenu();
        });
    }

    // ==========================================================
    // 5. CUSTOM SMOOTH SCROLL WITH OFFSET LOGIC
    // ==========================================================
    const allNavLinks = document.querySelectorAll('.nav-item, .scroll-trigger');
    const mainHeader = document.getElementById('mainHeader') || document.querySelector('.main-header');

    allNavLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('href');

            if (!targetId || !targetId.startsWith('#')) {
                return; // Let normal links work as intended
            }

            e.preventDefault();
            const targetSection = document.querySelector(targetId);

            if (targetSection) {
                const headerHeight = mainHeader ? mainHeader.offsetHeight : 0;
                const targetPosition = targetSection.offsetTop - headerHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ==========================================================
    // 6. SCROLLSPY (ACTIVE LINK SWITCHER) LOGIC
    // ==========================================================
    const sections = document.querySelectorAll('section');
    const menuItems = document.querySelectorAll('.nav-item');

    if (document.getElementById('home')) {
        window.addEventListener('scroll', () => {
            let currentSectionId = '';
            const headerHeight = (mainHeader ? mainHeader.offsetHeight : 0) + 15; 

            sections.forEach(section => {
                const sectionTop = section.offsetTop - headerHeight;
                const sectionHeight = section.offsetHeight;
                
                if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                    currentSectionId = section.getAttribute('id');
                }
            });

            // Catch bottom of page for contact section
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 2) {
                currentSectionId = 'contact';
            }

            menuItems.forEach(item => {
                const href = item.getAttribute('href') || '';
                // Only touch anchor-hash links (this scrollspy's own concern).
                // Real page links keep whatever Django rendered server-side.
                if (!href.startsWith('#')) return;
                item.classList.remove('active');
                if (href === `#${currentSectionId}`) {
                    item.classList.add('active');
                }
            });
        });
    }

    // ==========================================================
    // 7. FAQ ACCORDION LOGIC
    // ==========================================================
    const faqQuestions = document.querySelectorAll('.faq-question');

    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            const answer = question.nextElementSibling;
            const icon = question.querySelector('.faq-icon');
            
            document.querySelectorAll('.faq-answer').forEach(ans => {
                if (ans !== answer) {
                    ans.style.maxHeight = null;
                    const relatedIcon = ans.parentElement.querySelector('.faq-icon');
                    if (relatedIcon) {
                        relatedIcon.style.transform = 'rotate(0deg)';
                        relatedIcon.textContent = '+';
                    }
                }
            });

            if (answer.style.maxHeight) {
                answer.style.maxHeight = null;
                if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                    icon.textContent = '+';
                }
            } else {
                answer.style.maxHeight = answer.scrollHeight + "px";
                if (icon) {
                    icon.style.transform = 'rotate(45deg)';
                    icon.textContent = '×';
                }
            }
        });
    });

    // ==========================================================
    // 8. BACK TO TOP BUTTON LOGIC
    // ==========================================================
    const backToTopBtn = document.getElementById("backToTopBtn");

    if (backToTopBtn) {
        window.addEventListener("scroll", () => {
            if (window.scrollY > 500) {
                backToTopBtn.style.opacity = "1";
                backToTopBtn.style.pointerEvents = "auto";
            } else {
                backToTopBtn.style.opacity = "0";
                backToTopBtn.style.pointerEvents = "none";
            }
        });

        backToTopBtn.addEventListener("click", () => {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });

        backToTopBtn.addEventListener("mouseenter", () => {
            backToTopBtn.style.transform = "translateY(-3px)";
            backToTopBtn.style.backgroundColor = "var(--tdt-orange)";
        });
        
        backToTopBtn.addEventListener("mouseleave", () => {
            backToTopBtn.style.transform = "translateY(0)";
            backToTopBtn.style.backgroundColor = "var(--tdt-dark)";
        });
    }

    // ==========================================================
    // 9. PRODUCTS CAROUSEL ARROW BUTTONS
    // ==========================================================
    // The prev/next buttons in the markup (#productsPrevBtn /
    // #productsNextBtn) had no click handlers, so on desktop -- where the
    // arrows are visible -- clicking them did nothing. Mobile worked fine
    // because touch swipe scrolls the track natively; this wires the
    // buttons up to do the same thing programmatically by scrolling the
    // track by roughly one card's width at a time.
    const productsTrack = document.getElementById("productsTrack");
    const productsPrevBtn = document.getElementById("productsPrevBtn");
    const productsNextBtn = document.getElementById("productsNextBtn");

    if (productsTrack && (productsPrevBtn || productsNextBtn)) {
        const getScrollAmount = () => {
            const firstCard = productsTrack.querySelector(".product-card");
            if (!firstCard) return productsTrack.clientWidth;

            const cardWidth = firstCard.getBoundingClientRect().width;
            const gap = parseFloat(window.getComputedStyle(productsTrack).columnGap || window.getComputedStyle(productsTrack).gap || 0) || 0;

            return cardWidth + gap;
        };

        productsPrevBtn?.addEventListener("click", () => {
            productsTrack.scrollBy({ left: -getScrollAmount(), behavior: "smooth" });
        });

        productsNextBtn?.addEventListener("click", () => {
            productsTrack.scrollBy({ left: getScrollAmount(), behavior: "smooth" });
        });
    }

    // ==========================================================
    // 9b. PRODUCTS CAROUSEL DOTS (mobile scroll indicator)
    // ==========================================================
    // The arrows are hidden on phones so touch swipe can use the full
    // width; these dots are the replacement cue that the row scrolls
    // sideways, and they track which card is currently in view.
    const productsDots = document.getElementById("productsDots");

    if (productsTrack && productsDots) {
        const cards = Array.from(productsTrack.querySelectorAll(".product-card"));

        if (cards.length > 1) {
            cards.forEach((card, i) => {
                const dot = document.createElement("button");
                dot.type = "button";
                dot.className = "dot";
                dot.setAttribute("aria-label", `Go to product ${i + 1}`);
                dot.addEventListener("click", () => {
                    card.scrollIntoView({ behavior: "smooth", inline: "start", block: "nearest" });
                });
                productsDots.appendChild(dot);
            });

            const dotEls = Array.from(productsDots.querySelectorAll(".dot"));

            const setActiveDot = () => {
                const trackRect = productsTrack.getBoundingClientRect();
                let closestIndex = 0;
                let closestDistance = Infinity;

                cards.forEach((card, i) => {
                    const distance = Math.abs(card.getBoundingClientRect().left - trackRect.left);
                    if (distance < closestDistance) {
                        closestDistance = distance;
                        closestIndex = i;
                    }
                });

                dotEls.forEach((dot, i) => dot.classList.toggle("active", i === closestIndex));
            };

            setActiveDot();

            let dotsScrollTimeout;
            productsTrack.addEventListener("scroll", () => {
                clearTimeout(dotsScrollTimeout);
                dotsScrollTimeout = setTimeout(setActiveDot, 100);
            }, { passive: true });
        }
    }
});

(function () {
    // HERO ENTRANCE ANIMATION (slide-door-left / slide-door-right)
    // Repeats every time the home section scrolls in/out of view, like the
    // .reveal elements below. The trick: the doors themselves get
    // translateX'd off-screen when hidden, so watching THEM with
    // IntersectionObserver is unreliable -- on narrow/mobile viewports the
    // translated element can land almost entirely outside the viewport
    // horizontally, so it can never cross the visibility threshold and
    // stays invisible forever. Instead we watch the parent .hero-section,
    // which never moves, and toggle the doors' 'revealed' class off that.
    const heroSection = document.querySelector('.hero-section');
    const doors = document.querySelectorAll('.slide-door-left, .slide-door-right');

    if (heroSection && doors.length) {
        if (!('IntersectionObserver' in window)) {
            doors.forEach(function (el) { el.classList.add('revealed'); });
        } else {
            const heroObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    doors.forEach(function (el) {
                        if (entry.isIntersecting) {
                            el.classList.add('revealed');
                        } else {
                            el.classList.remove('revealed');
                        }
                    });
                });
            }, { threshold: 0.15, rootMargin: '0px' });
            heroObserver.observe(heroSection);
        }
    }

    // SCROLL-TRIGGERED REVEAL for everything else (.reveal elements further
    // down the page, including the "dealt like a card" Why Choose cards).
    // Repeats every time each element scrolls in/out of view rather than
    // only playing once on first load.
    const items = document.querySelectorAll('.reveal');
    if (!items.length) return;

    if (!('IntersectionObserver' in window)) {
        items.forEach(function (el) { el.classList.add('revealed'); });
        return;
    }

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            } else {
                // Remove the class when the element scrolls out of view so
                // the slide-in/reveal transition plays again the next time
                // it re-enters.
                entry.target.classList.remove('revealed');
            }
        });
    }, { threshold: 0.15, rootMargin: '0px' });

    items.forEach(function (el) { observer.observe(el); });
})();