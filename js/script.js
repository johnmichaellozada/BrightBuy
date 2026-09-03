/* =========================================================
   BRIGHTBUY JAVASCRIPT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       PRODUCT CART BUTTON
    ===================================================== */

    const cartButtons = document.querySelectorAll(".add-cart");

    const cartCount = document.querySelector(".cart-count");



    cartButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            cartItems++;

            if (cartCount) {
                cartCount.textContent = cartItems;
            }


            const originalIcon = button.innerHTML;

            button.innerHTML =
                '<i class="bi bi-check-lg"></i>';

            button.classList.add("added");


            setTimeout(function () {

                button.innerHTML = originalIcon;

                button.classList.remove("added");

            }, 1000);

        });

    });


    /* =====================================================
       WISHLIST
    ===================================================== */

    const wishlistButtons =
        document.querySelectorAll(".product-wishlist");


    wishlistButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const icon = button.querySelector("i");

            if (!icon) {
                return;
            }


            if (icon.classList.contains("bi-heart")) {

                icon.classList.remove("bi-heart");

                icon.classList.add("bi-heart-fill");

                button.classList.add("selected");

            } else {

                icon.classList.remove("bi-heart-fill");

                icon.classList.add("bi-heart");

                button.classList.remove("selected");

            }

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

        previous.addEventListener("click", function () {

            track.scrollBy({
                left: -300,
                behavior: "smooth"
            });

        });


        next.addEventListener("click", function () {

            track.scrollBy({
                left: 300,
                behavior: "smooth"
            });

        });

    }


    /* =====================================================
       BACK TO TOP
    ===================================================== */

    const backToTop =
        document.getElementById("backToTop");


    if (backToTop) {

        window.addEventListener("scroll", function () {

            if (window.scrollY > 500) {

                backToTop.classList.add("show");

            } else {

                backToTop.classList.remove("show");

            }

        });


        backToTop.addEventListener("click", function () {

            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });

        });

    }


    /* =====================================================
       ACTIVE NAVIGATION
    ===================================================== */

    const currentPage =
        window.location.pathname.split("/").pop();


    const navLinks =
        document.querySelectorAll(".nav-menu a");


    navLinks.forEach(function (link) {

        const linkPage =
            link.getAttribute("href")
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

            navLinks.forEach(function (item) {
                item.classList.remove("active");
            });

            link.classList.add("active");

        }

    });


    /* =====================================================
       SEARCH
    ===================================================== */

    const searchInput =
        document.querySelector(".search-box input");


    if (searchInput) {

        searchInput.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Enter") {

                    if (searchInput.value.trim() === "") {

                        event.preventDefault();

                    }

                }

            }
        );

    }


    /* =====================================================
       PREVENT IMAGE DRAGGING
    ===================================================== */

    document.querySelectorAll("img").forEach(function (image) {

        image.addEventListener("dragstart", function (event) {

            event.preventDefault();

        });

    });


          /* =====================================================
   SPECIAL OFFER SLIDER
===================================================== */

const offerSlides = document.querySelectorAll(".offer-slide");
const offerDots = document.querySelectorAll(".offer-dot");

let currentOfferSlide = 0;
let offerInterval = null;

function showOfferSlide(index) {

    if (offerSlides.length === 0) {
        return;
    }

    /* Make sure index stays within range */
    if (index < 0) {
        index = offerSlides.length - 1;
    }

    if (index >= offerSlides.length) {
        index = 0;
    }

    /* Remove active state from ALL slides */
    offerSlides.forEach(function (slide) {
        slide.classList.remove("active");
    });

    /* Remove active state from ALL dots */
    offerDots.forEach(function (dot) {
        dot.classList.remove("active");
    });

    /* Activate selected slide */
    offerSlides[index].classList.add("active");

    /* Activate matching dot */
    if (offerDots[index]) {
        offerDots[index].classList.add("active");
    }

    currentOfferSlide = index;
}


/* =====================================================
   NEXT SLIDE
===================================================== */

function nextOfferSlide() {

    let nextSlide = currentOfferSlide + 1;

    if (nextSlide >= offerSlides.length) {
        nextSlide = 0;
    }

    showOfferSlide(nextSlide);
}


/* =====================================================
   START AUTOMATIC SLIDER
===================================================== */

function startOfferSlider() {

    /* Stop previous timer */
    if (offerInterval !== null) {
        clearInterval(offerInterval);
    }

    /* Don't start if there is only one slide */
    if (offerSlides.length <= 1) {
        return;
    }

    /* Change slide every 4 seconds */
    offerInterval = setInterval(function () {
        nextOfferSlide();
    }, 4000);
}


/* =====================================================
   DOT CONTROLS
===================================================== */

offerDots.forEach(function (dot, index) {

    dot.addEventListener("click", function () {

        showOfferSlide(index);

        /* Restart the 4-second timer */
        startOfferSlider();

    });

});


/* =====================================================
   START SLIDER
===================================================== */

if (offerSlides.length > 0) {

    /* Start with Slide 1 */
    showOfferSlide(0);

    /* Start automatic movement */
    startOfferSlider();

}

});

document.addEventListener("DOMContentLoaded", function () {

    const wishlistButtons = document.querySelectorAll(".product-wishlist");

    wishlistButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const productId = this.dataset.productId;
            const icon = this.querySelector("i");

            const formData = new FormData();

            formData.append("product_id", productId);

            fetch("pages/toggle-wishlist.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {

                if (data.logged_in === false) {
                    window.location.href = "pages/login.php";
                    return;
                }

                if (!data.success) {
                    alert(data.message || "Something went wrong.");
                    return;
                }

                if (data.in_wishlist) {

                    button.classList.add("active");

                    icon.classList.remove("bi-heart");
                    icon.classList.add("bi-heart-fill");

                    button.setAttribute(
                        "aria-label",
                        "Remove from wishlist"
                    );

                } else {

                    button.classList.remove("active");

                    icon.classList.remove("bi-heart-fill");
                    icon.classList.add("bi-heart");

                    button.setAttribute(
                        "aria-label",
                        "Add to wishlist"
                    );
                }

            })
            .catch(error => {

                console.error("Wishlist error:", error);

                alert("Unable to update wishlist.");

            });

        });

    });

});
