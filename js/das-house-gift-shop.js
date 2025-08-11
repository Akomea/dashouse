/**
 * Das House Gift Shop Gallery
 * Handles the display and management of gift shop items
 */
class DasHouseGiftShop {
    constructor() {
        this.giftShopContainer = null;
        this.loadingElement = null;
        this.errorElement = null;
        this.items = [];
    }

    /**
     * Initialize the gift shop
     */
    async init() {
        this.giftShopContainer = document.getElementById('gift-shop-gallery');
        this.loadingElement = document.getElementById('gift-shop-loading');
        this.errorElement = document.getElementById('gift-shop-error');

        if (!this.giftShopContainer) {
            console.error('Gift shop container not found');
            return;
        }

        this.showLoading();
        
        try {
            await this.loadGiftShopItems();
            this.renderGiftShop();
        } catch (error) {
            console.error('Failed to load gift shop:', error);
            this.showError('Failed to load gift shop items. Please try again later.');
        }
    }

    /**
     * Load gift shop items from the API
     */
    async loadGiftShopItems() {
        try {
            const response = await fetch('admin/api/gift-shop.php?is_active=1');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.items = data.data || [];
                console.log(`Loaded ${this.items.length} gift shop items`);
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }
        } catch (error) {
            console.error('Error loading gift shop items:', error);
            throw error;
        }
    }

    /**
     * Render the gift shop gallery
     */
    renderGiftShop() {
        this.hideLoading();
        this.hideError();

        if (this.items.length === 0) {
            this.showEmptyState();
            return;
        }

        // Sort items by sort_order and then by name
        const sortedItems = [...this.items].sort((a, b) => {
            if (a.sort_order !== b.sort_order) {
                return (a.sort_order || 0) - (b.sort_order || 0);
            }
            return a.name.localeCompare(b.name);
        });

        const giftShopHTML = `
            <div class="row">
                ${sortedItems.map(item => this.renderGiftItem(item)).join('')}
            </div>
        `;

        this.giftShopContainer.innerHTML = giftShopHTML;

        // Add click handlers for lightbox effect
        this.initializeLightbox();
        
        // Refresh AOS animations for new content
        if (typeof AOS !== 'undefined') {
            AOS.refresh();
        }
        
        // Debug: Check if images are loading
        const images = this.giftShopContainer.querySelectorAll('.gift-image');
        console.log(`Found ${images.length} gift images in DOM`);
        images.forEach((img, index) => {
            console.log(`Image ${index + 1}:`, {
                src: img.src,
                alt: img.alt,
                loaded: img.complete,
                naturalWidth: img.naturalWidth,
                naturalHeight: img.naturalHeight
            });
        });
    }

    /**
     * Render a single gift item
     */
    renderGiftItem(item) {
        return `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="gift-item-card">
                    <div class="gift-image-container">
                        <img src="${this.escapeHtml(item.image_url)}" 
                             alt="${this.escapeHtml(item.name)}"
                             class="gift-image"
                             loading="lazy"
                             data-bs-toggle="modal" 
                             data-bs-target="#giftModal"
                             data-gift-id="${item.id}"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkdpZnQgSXRlbTwvdGV4dD48L3N2Zz4='">
                        <div class="gift-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gift-content">
                        <h5 class="gift-title">${this.escapeHtml(item.name)}</h5>
                        ${item.description ? `<p class="gift-description">${this.escapeHtml(item.description)}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Initialize lightbox functionality
     */
    initializeLightbox() {
        const giftImages = this.giftShopContainer.querySelectorAll('.gift-image');
        const modal = document.getElementById('giftModal');
        const modalImage = document.getElementById('giftModalImage');
        const modalTitle = document.getElementById('giftModalTitle');
        const modalDescription = document.getElementById('giftModalDescription');

        if (!modal || !modalImage || !modalTitle) {
            console.error('Gift modal elements not found');
            return;
        }

        giftImages.forEach(image => {
            image.addEventListener('click', (e) => {
                const giftId = e.target.getAttribute('data-gift-id');
                const item = this.items.find(item => item.id === giftId);
                
                if (item) {
                    modalImage.src = item.image_url;
                    modalImage.alt = item.name;
                    modalTitle.textContent = item.name;
                    if (modalDescription) {
                        modalDescription.textContent = item.description || '';
                        modalDescription.style.display = item.description ? 'block' : 'none';
                    }
                }
            });
        });
    }

    /**
     * Show loading state
     */
    showLoading() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'block';
        }
        if (this.giftShopContainer) {
            this.giftShopContainer.style.display = 'none';
        }
    }

    /**
     * Hide loading state
     */
    hideLoading() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'none';
        }
        if (this.giftShopContainer) {
            this.giftShopContainer.style.display = 'block';
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        this.hideLoading();
        if (this.errorElement) {
            this.errorElement.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
            this.errorElement.style.display = 'block';
        }
    }

    /**
     * Hide error message
     */
    hideError() {
        if (this.errorElement) {
            this.errorElement.style.display = 'none';
        }
    }

    /**
     * Show empty state
     */
    showEmptyState() {
        this.giftShopContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-gifts fa-4x text-muted mb-4"></i>
                <h4 class="text-muted">Coming Soon!</h4>
                <p class="text-muted">We're working on adding some amazing merchandise to our gift shop.</p>
            </div>
        `;
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a page with gift shop elements
    if (document.getElementById('gift-shop-gallery')) {
        const giftShop = new DasHouseGiftShop();
        giftShop.init();
    }
});
