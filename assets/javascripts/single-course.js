jQuery(document).ready(function () {
    swiper_sliders();
});

function swiper_sliders() {
    var swiper = new Swiper(".swiper-testimonial", {
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });
}