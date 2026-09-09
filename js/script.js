/* =========================================================
   BRIGHTBUY JAVASCRIPT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       PRODUCT CART BUTTON
    ===================================================== */

    const cartButtons = document.querySelectorAll(".add-cart");
    const cartCount = document.querySelector(".cart-count");

    if (typeof window.cartItems === "undefined") {
        window.cartItems = 0;
    }

    cartButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            window.cartItems++;

            if (cartCount) {
                cartCount.textContent = window.cartItems;
            }

            const originalIcon = button.innerHTML;

            button.innerHTML = '<i class="bi bi-check-lg"></i>';

            button.classList.add("added");

            setTimeout(function () {

                button.innerHTML = originalIcon;
                button.classList.remove("added");

            }, 1000);

        });

    });


    /* =====================================================
       WISHLIST
       WORKS ON BOTH:
       - index.php
       - pages/shop.php
       - pages/deals.php
       - pages/new-arrivals.php
    ===================================================== */

    function getWishlistURL() {

        /*
         * index.php is in the root folder:
         *     ../actions/  would be WRONG
         *
         * pages/shop.php is inside /pages/:
         *     ../actions/  is CORRECT
         */

        const path = window.location.pathname;

        if (
            path.includes("/pages/") ||
            path.endsWith("/pages")
        ) {
            return "../actions/toggle-wishlist.php";
        }

        return "actions/toggle-wishlist.php";
    }


    /* =====================================================
       WISHLIST BUTTON HANDLER
       EVENT DELEGATION
       
       This allows wishlist buttons to work even if they
       are generated dynamically.
    ===================================================== */

    document.addEventListener("click", function (event) {

        const button = event.target.closest(
            ".wishlist-button, .wishlist-btn, .product-wishlist"
        );

        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        /* ---------------------------------------------
           PREVENT DOUBLE CLICK
        --------------------------------------------- */

        if (button.dataset.loading === "true") {
            return;
        }

        button.dataset.loading = "true";


        /* ---------------------------------------------
           GET PRODUCT ID
        --------------------------------------------- */

        const productId =
            button.getAttribute("data-product-id") ||
            button.dataset.productId;


        if (!productId) {

            console.error(
                "Wishlist Error: Product ID is missing."
            );

            alert("Unable to update wishlist.");

            button.dataset.loading = "false";

            return;
        }


        /* ---------------------------------------------
           GET HEART ICON
        --------------------------------------------- */

        const icon = button.querySelector("i");


        /* ---------------------------------------------
           CREATE FORM DATA
        --------------------------------------------- */

        const formData = new FormData();

        formData.append(
            "product_id",
            productId
        );


        /* ---------------------------------------------
           GET CORRECT PHP URL
        --------------------------------------------- */

        const wishlistURL = getWishlistURL();

        console.log(
            "Wishlist URL:",
            wishlistURL
        );

        console.log(
            "Product ID:",
            productId
        );


        /* ---------------------------------------------
           SEND REQUEST
        --------------------------------------------- */

        fetch(wishlistURL, {

            method: "POST",

            body: formData,

            credentials: "same-origin"

        })

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "Server returned HTTP " +
                    response.status
                );

            }

            return response.text();

        })

        .then(function (responseText) {

            console.log(
                "Wishlist Response:",
                responseText
            );


            /* -----------------------------------------
               PARSE JSON
            ----------------------------------------- */

            let data;

            try {

                data = JSON.parse(responseText);

            } catch (error) {

                console.error(
                    "Invalid JSON returned by wishlist PHP:",
                    responseText
                );

                throw new Error(
                    "Server returned invalid JSON."
                );

            }


            /* -----------------------------------------
               LOGIN CHECK
            ----------------------------------------- */

            if (data.logged_in === false) {

                /*
                 * If current page is inside /pages/
                 * login.php is directly available.
                 *
                 * If current page is index.php
                 * login is inside /pages/.
                 */

                const currentPath =
                    window.location.pathname;

                if (
                    currentPath.includes("/pages/")
                ) {

                    window.location.href =
                        "login.php";

                } else {

                    window.location.href =
                        "pages/login.php";

                }

                return;

            }


            /* -----------------------------------------
               CHECK SUCCESS
            ----------------------------------------- */

            if (data.success !== true) {

                alert(
                    data.message ||
                    "Unable to update wishlist."
                );

                return;

            }


            /* -----------------------------------------
               ADDED TO WISHLIST
            ----------------------------------------- */

            if (data.in_wishlist === true) {

                button.classList.add("active");
                button.classList.add("selected");

                button.setAttribute(
                    "aria-label",
                    "Remove from wishlist"
                );

                button.setAttribute(
                    "title",
                    "Remove from Wishlist"
                );


                if (icon) {

                    /*
                     * Bootstrap Icons
                     */

                    icon.classList.remove(
                        "bi-heart"
                    );

                    icon.classList.add(
                        "bi-heart-fill"
                    );


                    /*
                     * Font Awesome
                     * In case some buttons use FA
                     */

                    icon.classList.remove(
                        "fa-regular"
                    );

                    icon.classList.add(
                        "fa-solid"
                    );

                }


                console.log(
                    "Added to wishlist."
                );

            }


            /* -----------------------------------------
               REMOVED FROM WISHLIST
            ----------------------------------------- */

            else {

                button.classList.remove("active");
                button.classList.remove("selected");

                button.setAttribute(
                    "aria-label",
                    "Add to wishlist"
                );

                button.setAttribute(
                    "title",
                    "Add to Wishlist"
                );


                if (icon) {

                    /*
                     * Bootstrap Icons
                     */

                    icon.classList.remove(
                        "bi-heart-fill"
                    );

                    icon.classList.add(
                        "bi-heart"
                    );


                    /*
                     * Font Awesome
                     */

                    icon.classList.remove(
                        "fa-solid"
                    );

                    icon.classList.add(
                        "fa-regular"
                    );

                }


                console.log(
                    "Removed from wishlist."
                );

            }

        })

        .catch(function (error) {

            console.error(
                "Wishlist request failed:",
                error
            );

            alert(
                "Unable to update wishlist. Please try again."
            );

        })

        .finally(function () {

            button.dataset.loading = "false";

        });

    });


    /* =====================================================
       PRODUCT SLIDER
    ===================================================== */

    const track =
        document.getElementById("productsTrack");

    const previous =
        document.querySelector(".product-prev");

    const next =
        document.querySelector(".product-next");


    if (track && previous && next) {

        previous.addEventListener(
            "click",
            function () {

                track.scrollBy({

                    left: -300,

                    behavior: "smooth"

                });

            }
        );


        next.addEventListener(
            "click",
            function () {

                track.scrollBy({

                    left: 300,

                    behavior: "smooth"

                });

            }
        );

    }


    /* =====================================================
       BACK TO TOP
    ===================================================== */

    const backToTop =
        document.getElementById("backToTop");


    if (backToTop) {

        window.addEventListener(
            "scroll",
            function () {

                if (window.scrollY > 500) {

                    backToTop.classList.add(
                        "show"
                    );

                } else {

                    backToTop.classList.remove(
                        "show"
                    );

                }

            }
        );


        backToTop.addEventListener(
            "click",
            function () {

                window.scrollTo({

                    top: 0,

                    behavior: "smooth"

                });

            }
        );

    }


    /* =====================================================
       ACTIVE NAVIGATION
    ===================================================== */

    const currentPage =
        window.location.pathname
            .split("/")
            .pop();


    const navLinks =
        document.querySelectorAll(
            ".nav-menu a"
        );


    navLinks.forEach(
        function (link) {

            const href =
                link.getAttribute("href");


            if (!href) {
                return;
            }


            const linkPage =
                href
                    .split("/")
                    .pop()
                    .split("?")[0];


            if (
                currentPage === linkPage ||
                (
                    currentPage === "" &&
                    linkPage === "index.php"
                )
            ) {

                navLinks.forEach(
                    function (item) {

                        item.classList.remove(
                            "active"
                        );

                    }
                );


                link.classList.add(
                    "active"
                );

            }

        }
    );


    /* =====================================================
       SEARCH
    ===================================================== */

    const searchInput =
        document.querySelector(
            ".search-box input"
        );


    if (searchInput) {

        searchInput.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Enter") {

                    if (
                        searchInput.value.trim() === ""
                    ) {

                        event.preventDefault();

                    }

                }

            }
        );

    }


    /* =====================================================
       PREVENT IMAGE DRAGGING
    ===================================================== */

    document
        .querySelectorAll("img")
        .forEach(
            function (image) {

                image.addEventListener(
                    "dragstart",
                    function (event) {

                        event.preventDefault();

                    }
                );

            }
        );


    /* =====================================================
       SPECIAL OFFER SLIDER
    ===================================================== */

    const offerSlides =
        document.querySelectorAll(
            ".offer-slide"
        );


    const offerDots =
        document.querySelectorAll(
            ".offer-dot"
        );


    let currentOfferSlide = 0;

    let offerInterval = null;


    function showOfferSlide(index) {

        if (offerSlides.length === 0) {
            return;
        }


        /* ---------------------------------------------
           KEEP INDEX WITHIN RANGE
        --------------------------------------------- */

        if (index < 0) {

            index =
                offerSlides.length - 1;

        }


        if (
            index >=
            offerSlides.length
        ) {

            index = 0;

        }


        /* ---------------------------------------------
           REMOVE ACTIVE FROM SLIDES
        --------------------------------------------- */

        offerSlides.forEach(
            function (slide) {

                slide.classList.remove(
                    "active"
                );

            }
        );


        /* ---------------------------------------------
           REMOVE ACTIVE FROM DOTS
        --------------------------------------------- */

        offerDots.forEach(
            function (dot) {

                dot.classList.remove(
                    "active"
                );

            }
        );


        /* ---------------------------------------------
           ACTIVATE SELECTED SLIDE
        --------------------------------------------- */

        offerSlides[index]
            .classList.add(
                "active"
            );


        /* ---------------------------------------------
           ACTIVATE MATCHING DOT
        --------------------------------------------- */

        if (offerDots[index]) {

            offerDots[index]
                .classList.add(
                    "active"
                );

        }


        currentOfferSlide = index;

    }


    /* =====================================================
       NEXT OFFER SLIDE
    ===================================================== */

    function nextOfferSlide() {

        let nextSlide =
            currentOfferSlide + 1;


        if (
            nextSlide >=
            offerSlides.length
        ) {

            nextSlide = 0;

        }


        showOfferSlide(
            nextSlide
        );

    }


    /* =====================================================
       START OFFER SLIDER
    ===================================================== */

    function startOfferSlider() {

        if (offerInterval !== null) {

            clearInterval(
                offerInterval
            );

        }


        if (offerSlides.length <= 1) {

            return;

        }


        offerInterval =
            setInterval(
                function () {

                    nextOfferSlide();

                },
                4000
            );

    }


    /* =====================================================
       OFFER DOT CONTROLS
    ===================================================== */

    offerDots.forEach(
        function (dot, index) {

            dot.addEventListener(
                "click",
                function () {

                    showOfferSlide(
                        index
                    );

                    startOfferSlider();

                }
            );

        }
    );


    /* =====================================================
       START OFFER SLIDER
    ===================================================== */

    if (offerSlides.length > 0) {

        showOfferSlide(0);

        startOfferSlider();

    }

});