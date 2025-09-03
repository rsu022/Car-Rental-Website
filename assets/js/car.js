// Car-related JavaScript functionality

// Price calculation for booking
function calculatePrice(startDate, endDate, dailyRate) {
    if (!startDate || !endDate) return 0;
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    return days * dailyRate;
}

// Update price display
function updatePriceDisplay() {
    const startDate = document.getElementById('start_date')?.value;
    const endDate = document.getElementById('end_date')?.value;
    const dailyRate = parseFloat(document.getElementById('daily_rate')?.value || 0);
    const totalPriceElement = document.getElementById('totalPrice');
    const daysCalculation = document.getElementById('daysCalculation');
    const numberOfDays = document.getElementById('numberOfDays');

    if (startDate && endDate && totalPriceElement) {
        const totalPrice = calculatePrice(startDate, endDate, dailyRate);
        const days = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24));
        
        if (days > 0) {
            daysCalculation.style.display = 'flex';
            numberOfDays.textContent = days;
            totalPriceElement.textContent = `Rs. ${totalPrice.toFixed(2)}`;
        } else {
            daysCalculation.style.display = 'none';
            totalPriceElement.textContent = 'Rs. 0.00';
        }
    }
}

// Set minimum dates for booking
function initializeDateInputs() {
    const today = new Date().toISOString().split('T')[0];
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (startDateInput) {
        startDateInput.min = today;
        startDateInput.addEventListener('change', function() {
            if (endDateInput) {
                endDateInput.min = this.value;
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
                updatePriceDisplay();
            }
        });
    }

    if (endDateInput) {
        endDateInput.min = today;
        endDateInput.addEventListener('change', updatePriceDisplay);
    }
}

// Initialize car features display
function initializeCarFeatures() {
    const featureBadges = document.querySelectorAll('.feature-badge');
    featureBadges.forEach(badge => {
        badge.addEventListener('mouseover', function() {
            this.style.backgroundColor = '#e2e6ea';
        });
        badge.addEventListener('mouseout', function() {
            this.style.backgroundColor = '#e9ecef';
        });
    });
}

// Handle car image gallery
function initializeCarGallery() {
    const mainImage = document.querySelector('.car-gallery img');
    const thumbnails = document.querySelectorAll('.car-thumbnail');
    
    if (thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const newSrc = this.getAttribute('data-full-image');
                if (mainImage && newSrc) {
                    mainImage.src = newSrc;
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
    }
}

// Initialize all car-related functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeDateInputs();
    initializeCarFeatures();
    initializeCarGallery();
    
    // Add event listeners for price calculation
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput) startDateInput.addEventListener('change', updatePriceDisplay);
    if (endDateInput) endDateInput.addEventListener('change', updatePriceDisplay);
}); 