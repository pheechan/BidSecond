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
let slideIndex = 0;
showSlides();

function showSlides() {
    let i;
    let slides = document.getElementsByClassName("mySlides");
    let dots = document.getElementsByClassName("dot");
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";  
    }
    slideIndex++;
    if (slideIndex > slides.length) {slideIndex = 1}    
    for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    slides[slideIndex-1].style.display = "block";  
    dots[slideIndex-1].className += " active";
    setTimeout(showSlides, 6000); // Change image every 2 seconds
};1