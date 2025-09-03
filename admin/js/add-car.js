document.addEventListener('DOMContentLoaded', function() {
    // Image preview
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.style.display = 'block';
                imagePreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Form validation
    const form = document.getElementById('addCarForm');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const name = form.querySelector('[name="name"]').value.trim();
        const brand = form.querySelector('[name="brand"]').value.trim();
        const model = form.querySelector('[name="model"]').value.trim();
        const year = parseInt(form.querySelector('[name="year"]').value);
        const price = parseFloat(form.querySelector('[name="price_per_day"]').value);
        const image = form.querySelector('[name="image"]').files[0];
        
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        
        // Validate name
        if (!name) {
            showError('name', 'Car name is required');
            isValid = false;
        }
        
        // Validate brand
        if (!brand) {
            showError('brand', 'Brand is required');
            isValid = false;
        }
        
        // Validate model
        if (!model) {
            showError('model', 'Model is required');
            isValid = false;
        }
        
        // Validate year
        const currentYear = new Date().getFullYear();
        if (isNaN(year) || year < 1900 || year > currentYear + 1) {
            showError('year', 'Please enter a valid year between 1900 and ' + (currentYear + 1));
            isValid = false;
        }
        
        // Validate price
        if (isNaN(price) || price <= 0) {
            showError('price_per_day', 'Please enter a valid price per day greater than 0');
            isValid = false;
        }
        
        // Validate image
        if (!image && !imagePreview.src) {
            showError('image', 'Please select an image');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    function showError(fieldName, message) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        const error = document.createElement('div');
        error.className = 'error-message text-danger mt-1';
        error.textContent = message;
        field.parentNode.appendChild(error);
    }
});
