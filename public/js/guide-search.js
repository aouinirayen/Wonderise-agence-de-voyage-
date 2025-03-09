document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('guide-search');
    const guidesList = document.getElementById('guides-list');
    let searchTimeout;
    let allGuides = [];
    let currentGuideId = null;
    let currentRating = 0;

    // Load saved data from localStorage
    const savedRatings = JSON.parse(localStorage.getItem('guideRatings')) || {};
    const savedLikes = JSON.parse(localStorage.getItem('guideLikes')) || {};

    // Store all initial guide cards
    document.querySelectorAll('.col-md-4').forEach(card => {
        allGuides.push({
            element: card,
            searchText: card.textContent.toLowerCase()
        });
    });

    // Rating Modal
    const ratingModal = new bootstrap.Modal(document.getElementById('ratingModal'));
    const stars = document.querySelectorAll('.star-rating');
    const submitRatingBtn = document.getElementById('submitRating');

    // Initialize like buttons and load saved likes
    function initializeLikeButtons() {
        document.querySelectorAll('.like-btn').forEach(button => {
            const guideId = button.getAttribute('data-guide-id');
            
            // Load saved like state if exists
            if (savedLikes[guideId]) {
                button.classList.add('liked');
                const badge = button.querySelector('.badge');
                badge.textContent = '1';
            }

            if (!button.hasListener) {
                button.hasListener = true;
                button.addEventListener('click', function() {
                    const badge = this.querySelector('.badge');
                    const currentLikes = parseInt(badge.textContent);
                    
                    if (this.classList.contains('liked')) {
                        this.classList.remove('liked');
                        badge.textContent = currentLikes - 1;
                        // Remove like from localStorage
                        delete savedLikes[guideId];
                    } else {
                        this.classList.add('liked');
                        badge.textContent = currentLikes + 1;
                        // Save like to localStorage
                        savedLikes[guideId] = true;
                    }
                    
                    // Update localStorage
                    localStorage.setItem('guideLikes', JSON.stringify(savedLikes));
                });
            }
        });
    }

    // Initialize rating buttons and load saved ratings
    function initializeRatingButtons() {
        document.querySelectorAll('.rating-btn').forEach(button => {
            const guideId = button.getAttribute('data-guide-id');
            const badge = button.querySelector('.badge');
            
            // Load saved rating if exists
            if (savedRatings[guideId]) {
                badge.textContent = savedRatings[guideId];
                button.classList.add('rated');
            }

            if (!button.hasListener) {
                button.hasListener = true;
                button.addEventListener('click', function() {
                    currentGuideId = guideId;
                    resetStars();
                    
                    // If there's a saved rating, show it in the modal
                    if (savedRatings[guideId]) {
                        currentRating = savedRatings[guideId];
                        highlightStars(currentRating);
                        submitRatingBtn.disabled = false;
                        document.querySelector('.rating-text').textContent = 
                            `Votre note actuelle : ${currentRating} étoile${currentRating > 1 ? 's' : ''}`;
                    }
                    
                    ratingModal.show();
                });
            }
        });
    }

    // Reset stars to initial state
    function resetStars() {
        stars.forEach(star => {
            star.classList.remove('fas');
            star.classList.add('far');
        });
        currentRating = 0;
        submitRatingBtn.disabled = true;
        document.querySelector('.rating-text').textContent = 'Cliquez sur une étoile pour noter';
    }

    // Handle star hover and click
    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const rating = this.getAttribute('data-rating');
            highlightStars(rating);
        });

        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            currentRating = rating;
            submitRatingBtn.disabled = false;
            highlightStars(rating);
            document.querySelector('.rating-text').textContent = 
                `Vous avez choisi ${rating} étoile${rating > 1 ? 's' : ''}`;
        });
    });

    // Highlight stars up to selected rating
    function highlightStars(rating) {
        stars.forEach(star => {
            const starRating = parseInt(star.getAttribute('data-rating'));
            if (starRating <= rating) {
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }

    // Save rating to localStorage
    function saveRating(guideId, rating) {
        savedRatings[guideId] = rating;
        localStorage.setItem('guideRatings', JSON.stringify(savedRatings));
    }

    // Handle rating submission
    submitRatingBtn.addEventListener('click', function() {
        if (currentGuideId && currentRating) {
            const ratingBtn = document.querySelector(`.rating-btn[data-guide-id="${currentGuideId}"]`);
            const badge = ratingBtn.querySelector('.badge');
            badge.textContent = currentRating;
            ratingBtn.classList.add('rated');
            
            // Save rating to localStorage
            saveRating(currentGuideId, currentRating);
            
            ratingModal.hide();
            
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '5';
            toast.innerHTML = `
                <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            Merci pour votre avis ! (${currentRating} étoile${currentRating > 1 ? 's' : ''})
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            const toastElement = new bootstrap.Toast(toast.querySelector('.toast'));
            toastElement.show();
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    });

    // Reset stars when modal is closed
    document.getElementById('ratingModal').addEventListener('hidden.bs.modal', function() {
        if (savedRatings[currentGuideId]) {
            currentRating = savedRatings[currentGuideId];
            highlightStars(currentRating);
            submitRatingBtn.disabled = false;
            document.querySelector('.rating-text').textContent = 
                `Votre note actuelle : ${currentRating} étoile${currentRating > 1 ? 's' : ''}`;
        } else {
            resetStars();
        }
    });

    // Mouse leave event for stars container
    document.querySelector('.stars-container').addEventListener('mouseleave', function() {
        if (currentRating > 0) {
            highlightStars(currentRating);
        } else {
            resetStars();
        }
    });

    function filterGuides(query) {
        query = query.toLowerCase().trim();
        
        if (query === '') {
            allGuides.forEach(guide => {
                guide.element.style.display = '';
            });
            return;
        }

        allGuides.forEach(guide => {
            if (guide.searchText.includes(query)) {
                guide.element.style.display = '';
            } else {
                guide.element.style.display = 'none';
            }
        });

        const visibleGuides = allGuides.filter(guide => guide.element.style.display !== 'none');
        if (visibleGuides.length === 0) {
            if (!document.querySelector('.no-results')) {
                const noResults = document.createElement('div');
                noResults.className = 'col-12 text-center no-results';
                noResults.innerHTML = `
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-search mb-2"></i>
                        <p class="mb-0">Aucun guide trouvé</p>
                    </div>
                `;
                guidesList.appendChild(noResults);
            }
        } else {
            const noResults = document.querySelector('.no-results');
            if (noResults) {
                noResults.remove();
            }
        }
    }

    // Search input event listener
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value;
        searchTimeout = setTimeout(() => filterGuides(query), 300);
    });

    // Initialize buttons
    initializeLikeButtons();
    initializeRatingButtons();
});
