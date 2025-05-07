// This file contains the main JavaScript logic for the application, handling user interactions, bid submissions, and dynamic updates to the user interface.

document.addEventListener('DOMContentLoaded', () => {
    const bidForm = document.getElementById('bid-form');
    const bidInput = document.getElementById('bid-input');
    const bidList = document.getElementById('bid-list');

    bidForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const bidValue = bidInput.value;

        if (bidValue) {
            addBidToList(bidValue);
            bidInput.value = '';
        }
    });

    function addBidToList(bid) {
        const listItem = document.createElement('li');
        listItem.textContent = `Bid: ${bid}`;
        bidList.appendChild(listItem);
    }
});

// Slideshow
let slideIndex = 0;
showSlides();

function showSlides() {
    let slides = document.getElementsByClassName("mySlides");
    let dots = document.getElementsByClassName("dot");
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    slideIndex++;
    if (slideIndex > slides.length) { slideIndex = 1; }
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    slides[slideIndex - 1].style.display = "block";
    dots[slideIndex - 1].className += " active";
    setTimeout(showSlides, 3000); // Change slide every 3 seconds
}

// Hot Bids Scrolling
function scrollHotBids(direction) {
    const container = document.querySelector(".hot-bids-container");
    container.scrollBy({
        left: direction * 200,
        behavior: "smooth"
    });
}