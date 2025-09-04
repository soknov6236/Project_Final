// Theme Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Check for saved theme preference
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        body.classList.add('dark-mode');
    }

    themeToggle.addEventListener('click', (e) => {
        e.preventDefault();
        body.classList.toggle('dark-mode');
        
        // Save the preference to localStorage
        const theme = body.classList.contains('dark-mode') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
    });

    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const categorySidebar = document.querySelector('.category-sidebar');
    
    if (sidebarToggle && categorySidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            categorySidebar.classList.toggle('active');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!e.target.closest('.category-sidebar') && 
                !e.target.closest('#sidebar-toggle') &&
                categorySidebar.classList.contains('active')) {
                categorySidebar.classList.remove('active');
            }
        }
    });
});

// Cart functionality (if not already in sale_pos.php)
function initializeCart() {
    const cart = [];
    const cartButton = document.querySelector('.cart-button');
    const cartCount = document.querySelector('.cart-count');
    
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const item = this.closest('.menu-item');
            const itemId = item.dataset.id;
            const itemName = item.querySelector('h4').textContent;
            const itemPrice = parseFloat(item.querySelector('.price').textContent.replace('$', ''));
            const itemImage = item.querySelector('img').src;
            
            // Add to cart logic
            const existingItem = cart.find(product => product.id === itemId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    image: itemImage,
                    quantity: 1
                });
            }
            
            // Update cart count
            updateCartCount();
        });
    });
    
    function updateCartCount() {
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = count;
        cartButton.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeCart);