/**
 * DasHouse Dynamic Menu System
 * Integrates with Supabase backend for real-time menu management
 */

// DasHouse Menu JavaScript loaded

class DasHouseMenu {
    constructor() {
        this.menuData = {
            categories: [],
            menuItems: [],
            filteredItems: []
        };
        this.currentCategory = 'all';
        this.searchTerm = '';
    }

    async init() {
        try {
            await this.loadMenuData();
            this.setupEventListeners();
            this.renderMenu();
        } catch (error) {
            console.error('DasHouseMenu: Error during initialization:', error);
            this.showError('Failed to load menu: ' + error.message);
        }
    }

    async loadMenuData() {
        try {
            // Load categories and menu items from our API
            const [categoriesResponse, menuItemsResponse] = await Promise.all([
                fetch('/admin/api/categories.php'),
                fetch('/admin/api/menu-items.php')
            ]);

            if (!categoriesResponse.ok || !menuItemsResponse.ok) {
                console.warn('API request failed, using fallback data');
                this.loadFallbackData();
                return;
            }

            const categories = await categoriesResponse.json();
            const menuItems = await menuItemsResponse.json();

            this.menuData.categories = categories.data || [];
            this.menuData.menuItems = menuItems.data || [];
            this.menuData.filteredItems = [...this.menuData.menuItems];

        } catch (error) {
            console.error('Error loading menu data:', error);
            this.loadFallbackData();
        }
    }

    loadFallbackData() {
        // Fallback data for testing when API is unavailable
        this.menuData.categories = [
            { id: 1, name: 'Breakfast & Waffles', image_url: 'demos/burger/images/others/burger.png' },
            { id: 2, name: 'Snacks & Meze', image_url: 'demos/burger/images/others/snacks.png' },
            { id: 3, name: 'Beverages', image_url: 'demos/burger/images/others/beverage.png' }
        ];
        
        this.menuData.menuItems = [
            { id: 1, category_id: 1, name: 'Classic Waffle', description: 'Fluffy waffle with syrup', price: '8.50' },
            { id: 2, category_id: 2, name: 'Chicken Wings', description: 'Crispy wings with sauce', price: '12.00' },
            { id: 3, category_id: 3, name: 'Fresh Coffee', description: 'Aromatic coffee blend', price: '3.50' }
        ];
        
        this.menuData.filteredItems = [...this.menuData.menuItems];
    }

    setupEventListeners() {
        // Category filter buttons
        const categoryButtons = document.querySelectorAll('.category-filter');
        categoryButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const category = e.target.dataset.category;
                this.filterByCategory(category);
            });
        });

        // Search input
        const searchInput = document.querySelector('#menu-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchTerm = e.target.value.toLowerCase();
                this.applyFilters();
            });
        }

        // Sort options
        const sortSelect = document.querySelector('#menu-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.sortMenu(e.target.value);
            });
        }
    }

    filterByCategory(category) {
        this.currentCategory = category;
        
        // Update active category button
        document.querySelectorAll('.category-filter').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-category="${category}"]`).classList.add('active');
        
        this.applyFilters();
    }

    applyFilters() {
        let filtered = [...this.menuData.menuItems];

        // Filter by category
        if (this.currentCategory !== 'all') {
            const categoryId = this.menuData.categories.find(cat => cat.name === this.currentCategory)?.id;
            if (categoryId) {
                filtered = filtered.filter(item => item.category_id === categoryId);
            }
        }

        // Filter by search term
        if (this.searchTerm) {
            filtered = filtered.filter(item => 
                item.name.toLowerCase().includes(this.searchTerm) ||
                item.description.toLowerCase().includes(this.searchTerm)
            );
        }

        this.menuData.filteredItems = filtered;
        this.renderMenu();
    }

    sortMenu(sortBy) {
        const items = [...this.menuData.filteredItems];
        
        switch (sortBy) {
            case 'name':
                items.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'price-low':
                items.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
                break;
            case 'price-high':
                items.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
                break;
            case 'category':
                items.sort((a, b) => {
                    const catA = this.menuData.categories.find(cat => cat.id === a.category_id)?.name || '';
                    const catB = this.menuData.categories.find(cat => cat.id === b.category_id)?.name || '';
                    return catA.localeCompare(catB);
                });
                break;
        }

        this.menuData.filteredItems = items;
        this.renderMenu();
    }

    renderMenu() {
        // Hide loading state
        const loadingElement = document.getElementById('menu-loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        const menuContainer = document.getElementById('dynamic-menu-container');
        if (!menuContainer) {
            console.error('DasHouseMenu: Dynamic menu container not found');
            return;
        }
        
        if (!this.menuData.menuItems || this.menuData.menuItems.length === 0) {
            menuContainer.innerHTML = '<div class="text-center py-5"><p>No menu items available.</p></div>';
            return;
        }
        
        const groupedItems = this.groupItemsByCategory(this.menuData.menuItems);
        
        let menuHTML = '';
        Object.keys(groupedItems).forEach((categoryName, index) => {
            const items = groupedItems[categoryName];
            menuHTML += this.renderCategorySection(categoryName, items, index);
        });
        
        menuContainer.innerHTML = menuHTML;
        
        // Update the results count
        const countElement = document.querySelector('.menu-results .count');
        if (countElement) {
            countElement.textContent = this.menuData.menuItems.length;
        }
    }

    groupItemsByCategory(items) {
        const grouped = {};
        
        items.forEach(item => {
            const itemCategoryId = parseInt(item.category_id);
            const category = this.menuData.categories.find(cat => cat.id === itemCategoryId);
            const categoryName = category ? category.name : 'Other';
            
            if (!grouped[categoryName]) {
                grouped[categoryName] = [];
            }
            grouped[categoryName].push(item);
        });
        
        return grouped;
    }

    renderCategorySection(categoryName, items, index = 0) {
        const categoryIcon = this.getCategoryIcon(categoryName);
        const imageSrc = this.getCategoryImage(categoryName);
        
        // Alternate layout: even index = menu left, image right; odd index = image left, menu right
        const isEven = index % 2 === 0;
        const menuOrder = isEven ? "order-2 order-md-1" : "order-2 order-md-2";
        const imageOrder = isEven ? "order-1 order-md-2" : "order-1 order-md-1";
        
        let sectionHTML = `
            <div class="section mb-0" style="background: linear-gradient(to bottom, #101010, transparent, #101010), url('demos/burger/images/others/section-2.jpg') no-repeat center top / cover;">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-5 dark ${menuOrder}">
                            <div class="bottommargin">
                                <div class="before-heading font-secondary color mb-2">Our Menu</div>
                                <div class="d-flex align-items-center dotted-bg">
                                    <img src="demos/burger/images/svg/${categoryIcon}" alt="" width="60">
                                    <h1 class="font-border display-4 ls1 fw-bold mb-0 ms-3">${categoryName}</h1>
                                </div>
                            </div>
                            <div class="clear"></div>
        `;

        items.forEach(item => {
            sectionHTML += this.renderMenuItem(item);
        });

        sectionHTML += `
                        </div>
                        <div class="col-md-6 text-center ${imageOrder}">
                            <img src="${imageSrc}" alt="${categoryName}" class="img-fluid" style="max-width: 400px; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.3);">
                        </div>
                    </div>
                </div>
            </div>
        `;

        return sectionHTML;
    }

    renderMenuItem(item) {
        const badges = this.getDietaryBadges(item);
        
        return `
            <div class="price-menu-warp">
                <div class="price-header">
                    <div class="price-name color">${item.name}</div>
                    <div class="price-dots">
                        <span class="separator-dots"></span>
                    </div>
                    <div class="price-price">€${parseFloat(item.price).toFixed(2)}</div>
                </div>
                <p class="price-desc">${item.description}</p>
                ${badges ? `<div class="dietary-badges mt-2">${badges}</div>` : ''}
            </div>
        `;
    }

    getDietaryBadges(item) {
        const badges = [];
        
        if (item.is_vegetarian) {
            badges.push('<span class="badge bg-success me-1">Vegetarian</span>');
        }
        if (item.is_vegan) {
            badges.push('<span class="badge bg-primary me-1">Vegan</span>');
        }
        if (item.is_gluten_free) {
            badges.push('<span class="badge bg-warning me-1">Gluten Free</span>');
        }
        
        return badges.join('');
    }

    getCategoryIcon(categoryName) {
        // Use hardcoded SVG icons for category titles (small icons)
        const iconMap = {
            'Breakfast & Waffles': 'burger.svg',
            'Snacks & Meze': 'snacks.svg',
            'Beverages': 'drinks.svg',
            'Cocktails & Spirits': 'drinks.svg'
        };
        return iconMap[categoryName] || 'burger.svg';
    }

    getCategoryImage(categoryName) {
        const category = this.menuData.categories.find(cat => cat.name === categoryName);
        
        if (category && category.image_url) {
            if (category.image_url.startsWith('http')) {
                return category.image_url;
            } else if (category.image_url.startsWith('/') || category.image_url.includes('demos/')) {
                return category.image_url;
            }
        }
        
        // Use local fallback images
        const imageMap = {
            'Breakfast & Waffles': 'demos/burger/images/others/burger.png',
            'Snacks & Meze': 'demos/burger/images/others/snacks.png', 
            'Beverages': 'demos/burger/images/others/beverage.png',
            'Cocktails & Spirits': 'demos/burger/images/others/beverage-1.png'
        };
        return imageMap[categoryName] || 'demos/burger/images/others/burger.png';
    }

    showLoading(show = true) {
        const loadingEl = document.querySelector('#menu-loading');
        if (loadingEl) {
            loadingEl.style.display = show ? 'block' : 'none';
        }
    }

    showError(message) {
        const errorEl = document.querySelector('#menu-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
    }
}

// DasHouseMenu class is ready for initialization
