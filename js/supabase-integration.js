/**
 * DasHouse Supabase Frontend Integration
 * This file demonstrates how to connect your frontend to the Supabase backend
 */

class DasHouseAPI {
    constructor() {
        this.baseURL = '/admin/api';
        this.menuItems = [];
        this.categories = [];
    }

    /**
     * Fetch all menu items from Supabase
     */
    async fetchMenuItems(categoryId = null) {
        try {
            let url = `${this.baseURL}/menu-items.php`;
            if (categoryId) {
                url += `?category_id=${categoryId}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.menuItems = data.data;
                return data.data;
            } else {
                console.error('Failed to fetch menu items:', data.error);
                return [];
            }
        } catch (error) {
            console.error('Error fetching menu items:', error);
            return [];
        }
    }

    /**
     * Fetch all categories from Supabase
     */
    async fetchCategories() {
        try {
            const response = await fetch(`${this.baseURL}/categories.php`);
            const data = await response.json();

            if (data.success) {
                this.categories = data.data;
                return data.data;
            } else {
                console.error('Failed to fetch categories:', data.error);
                return [];
            }
        } catch (error) {
            console.error('Error fetching categories:', error);
            return [];
        }
    }

    /**
     * Display menu items on the page
     */
    displayMenu(containerSelector = '#menu') {
        const container = document.querySelector(containerSelector);
        if (!container) return;

        // Group menu items by category
        const menuByCategory = {};
        this.menuItems.forEach(item => {
            if (!menuByCategory[item.category_name]) {
                menuByCategory[item.category_name] = [];
            }
            menuByCategory[item.category_name].push(item);
        });

        // Generate HTML for each category
        let menuHTML = '';
        Object.keys(menuByCategory).forEach(categoryName => {
            const items = menuByCategory[categoryName];
            
            menuHTML += `
                <div class="menu-category mb-5">
                    <h2 class="font-border display-5 ls1 fw-bold mb-4">${categoryName}</h2>
                    <div class="row">
            `;

            items.forEach(item => {
                const dietaryIcons = this.getDietaryIcons(item);
                
                menuHTML += `
                    <div class="col-md-6 mb-4">
                        <div class="menu-item-card p-3 border rounded h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0 font-weight-bold">${item.name}</h5>
                                <span class="badge bg-primary fs-6">€${parseFloat(item.price).toFixed(2)}</span>
                            </div>
                            ${item.description ? `<p class="text-muted mb-2">${item.description}</p>` : ''}
                            ${dietaryIcons ? `<div class="dietary-icons">${dietaryIcons}</div>` : ''}
                        </div>
                    </div>
                `;
            });

            menuHTML += `
                    </div>
                </div>
            `;
        });

        container.innerHTML = menuHTML;
    }

    /**
     * Get dietary restriction icons for a menu item
     */
    getDietaryIcons(item) {
        const icons = [];
        
        if (item.is_vegetarian) {
            icons.push('<span class="badge bg-success me-1" title="Vegetarian">🥬</span>');
        }
        if (item.is_vegan) {
            icons.push('<span class="badge bg-success me-1" title="Vegan">🌱</span>');
        }
        if (item.is_gluten_free) {
            icons.push('<span class="badge bg-warning me-1" title="Gluten Free">🌾</span>');
        }
        if (item.allergens) {
            icons.push('<span class="badge bg-info me-1" title="Allergens: ${item.allergens}">⚠️</span>');
        }

        return icons.join('');
    }

    /**
     * Create a reservation
     */
    async createReservation(reservationData) {
        try {
            const response = await fetch(`${this.baseURL}/reservations.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(reservationData)
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error creating reservation:', error);
            return { success: false, error: 'Network error' };
        }
    }

    /**
     * Search menu items
     */
    searchMenuItems(query) {
        if (!query.trim()) return this.menuItems;

        const searchTerm = query.toLowerCase();
        return this.menuItems.filter(item => 
            item.name.toLowerCase().includes(searchTerm) ||
            item.description.toLowerCase().includes(searchTerm) ||
            item.category_name.toLowerCase().includes(searchTerm)
        );
    }

    /**
     * Filter menu items by dietary preferences
     */
    filterByDietaryPreferences(preferences) {
        return this.menuItems.filter(item => {
            if (preferences.vegetarian && !item.is_vegetarian) return false;
            if (preferences.vegan && !item.is_vegan) return false;
            if (preferences.glutenFree && !item.is_gluten_free) return false;
            return true;
        });
    }

    /**
     * Initialize the menu system
     */
    async init() {
        try {
            // Fetch categories and menu items
            await Promise.all([
                this.fetchCategories(),
                this.fetchMenuItems()
            ]);

            // Display the menu
            this.displayMenu();

            // Add search functionality
            this.addSearchFunctionality();

            console.log('DasHouse menu system initialized successfully');
        } catch (error) {
            console.error('Failed to initialize menu system:', error);
        }
    }

    /**
     * Add search functionality to the page
     */
    addSearchFunctionality() {
        // Create search input if it doesn't exist
        let searchContainer = document.querySelector('#menu-search');
        if (!searchContainer) {
            searchContainer = document.createElement('div');
            searchContainer.id = 'menu-search';
            searchContainer.className = 'mb-4';
            searchContainer.innerHTML = `
                <div class="input-group">
                    <input type="text" class="form-control" id="menu-search-input" placeholder="Search menu items...">
                    <button class="btn btn-outline-secondary" type="button" id="menu-search-btn">Search</button>
                </div>
                <div class="mt-2">
                    <label class="me-3">
                        <input type="checkbox" id="filter-vegetarian" class="me-1"> Vegetarian
                    </label>
                    <label class="me-3">
                        <input type="checkbox" id="filter-vegan" class="me-1"> Vegan
                    </label>
                    <label class="me-3">
                        <input type="checkbox" id="filter-gluten-free" class="me-1"> Gluten Free
                    </label>
                </div>
            `;

            // Insert before the menu
            const menuSection = document.querySelector('#menu');
            if (menuSection) {
                menuSection.parentNode.insertBefore(searchContainer, menuSection);
            }
        }

        // Add event listeners
        const searchInput = document.getElementById('menu-search-input');
        const searchBtn = document.getElementById('menu-search-btn');
        const filterCheckboxes = document.querySelectorAll('input[type="checkbox"]');

        if (searchInput && searchBtn) {
            searchBtn.addEventListener('click', () => this.performSearch());
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.performSearch();
            });
        }

        filterCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.applyFilters());
        });
    }

    /**
     * Perform search based on input and filters
     */
    performSearch() {
        const searchInput = document.getElementById('menu-search-input');
        const query = searchInput ? searchInput.value : '';

        let results = this.searchMenuItems(query);
        
        // Apply dietary filters
        const preferences = this.getDietaryPreferences();
        if (Object.values(preferences).some(pref => pref)) {
            results = this.filterByDietaryPreferences(preferences);
        }

        this.displaySearchResults(results);
    }

    /**
     * Apply dietary filters
     */
    applyFilters() {
        this.performSearch();
    }

    /**
     * Get current dietary preferences from checkboxes
     */
    getDietaryPreferences() {
        return {
            vegetarian: document.getElementById('filter-vegetarian')?.checked || false,
            vegan: document.getElementById('filter-vegan')?.checked || false,
            glutenFree: document.getElementById('filter-gluten-free')?.checked || false
        };
    }

    /**
     * Display search results
     */
    displaySearchResults(results) {
        const container = document.querySelector('#menu');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <h3>No menu items found</h3>
                    <p class="text-muted">Try adjusting your search terms or filters</p>
                </div>
            `;
            return;
        }

        // Display filtered results
        this.menuItems = results;
        this.displayMenu();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a page with menu
    if (document.querySelector('#menu')) {
        const dasHouseAPI = new DasHouseAPI();
        dasHouseAPI.init();
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DasHouseAPI;
}
