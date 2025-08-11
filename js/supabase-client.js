/**
 * Supabase Client Configuration for Das House
 * This provides a centralized way to interact with Supabase across the application
 */

// Initialize Supabase client
const supabaseUrl = 'https://lvatvujwtyqwdsbqxjvm.supabase.co';
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imx2YXR2dWp3dHlxd2RzYnF4anZtIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTQ4MTI5MjcsImV4cCI6MjA3MDM4ODkyN30.mg5GMCY8LGGUfVNLCWUCnPX2Q5LDAbKgoAJczFPm6QI';

// For environments where Supabase is loaded via CDN
let supabase = null;

// Initialize Supabase client when available
if (typeof window !== 'undefined' && window.supabase) {
    supabase = window.supabase.createClient(supabaseUrl, supabaseKey);
} else if (typeof globalThis !== 'undefined' && globalThis.supabase) {
    supabase = globalThis.supabase.createClient(supabaseUrl, supabaseKey);
}

/**
 * Supabase API Helper Class
 * Provides convenient methods for common database operations
 */
class SupabaseAPI {
    constructor() {
        this.supabaseUrl = supabaseUrl;
        this.supabaseKey = supabaseKey;
        this.headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${supabaseKey}`,
            'apikey': supabaseKey
        };
    }

    /**
     * Make a REST API call to Supabase
     */
    async apiCall(endpoint, method = 'GET', data = null, queryParams = null) {
        let url = `${this.supabaseUrl}/rest/v1/${endpoint}`;
        
        // Add query parameters if provided
        if (queryParams) {
            const params = new URLSearchParams(queryParams);
            url += `?${params.toString()}`;
        }
        
        const options = {
            method: method,
            headers: this.headers
        };
        
        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            // For DELETE requests, return success status
            if (method === 'DELETE') {
                return { success: true };
            }
            
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Supabase API Error:', error);
            throw error;
        }
    }

    /**
     * Get all menu items with categories
     */
    async getMenuItems(categoryId = null, isActive = true) {
        let queryParams = {
            'is_active': `eq.${isActive}`,
            'select': '*,categories(*)'
        };
        
        if (categoryId) {
            queryParams['category_id'] = `eq.${categoryId}`;
        }
        
        return await this.apiCall('menu_items', 'GET', null, queryParams);
    }

    /**
     * Get all categories
     */
    async getCategories(isActive = true) {
        const queryParams = {
            'is_active': `eq.${isActive}`,
            'order': 'sort_order,name'
        };
        
        return await this.apiCall('categories', 'GET', null, queryParams);
    }

    /**
     * Get gift shop items
     */
    async getGiftShopItems(isActive = true) {
        const queryParams = {
            'active': `eq.${isActive}`,
            'order': 'sort_order,name'
        };
        
        return await this.apiCall('gift_shop_items', 'GET', null, queryParams);
    }

    /**
     * Add a new menu item
     */
    async addMenuItem(data) {
        return await this.apiCall('menu_items', 'POST', data);
    }

    /**
     * Update a menu item
     */
    async updateMenuItem(id, data) {
        const queryParams = { 'id': `eq.${id}` };
        return await this.apiCall('menu_items', 'PATCH', data, queryParams);
    }

    /**
     * Delete a menu item
     */
    async deleteMenuItem(id) {
        const queryParams = { 'id': `eq.${id}` };
        return await this.apiCall('menu_items', 'DELETE', null, queryParams);
    }

    /**
     * Add a new gift shop item
     */
    async addGiftShopItem(data) {
        return await this.apiCall('gift_shop_items', 'POST', data);
    }

    /**
     * Update a gift shop item
     */
    async updateGiftShopItem(id, data) {
        const queryParams = { 'id': `eq.${id}` };
        return await this.apiCall('gift_shop_items', 'PATCH', data, queryParams);
    }

    /**
     * Delete a gift shop item
     */
    async deleteGiftShopItem(id) {
        const queryParams = { 'id': `eq.${id}` };
        return await this.apiCall('gift_shop_items', 'DELETE', null, queryParams);
    }

    /**
     * Add a new category
     */
    async addCategory(data) {
        return await this.apiCall('categories', 'POST', data);
    }

    /**
     * Update a category
     */
    async updateCategory(id, data) {
        const queryParams = { 'id': `eq.${id}` };
        return await this.apiCall('categories', 'PATCH', data, queryParams);
    }

    /**
     * Delete a category
     */
    async deleteCategory(id) {
        const queryParams = { 'id': `eq.${id}` };
        return await this.apiCall('categories', 'DELETE', null, queryParams);
    }

    /**
     * Upload file to Supabase Storage
     */
    async uploadFile(bucket, path, file) {
        const uploadUrl = `${this.supabaseUrl}/storage/v1/object/${bucket}/${path}`;
        
        const formData = new FormData();
        formData.append('file', file);
        
        const response = await fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.supabaseKey}`,
                'apikey': this.supabaseKey
            },
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Upload failed: ${errorText}`);
        }
        
        return await response.json();
    }

    /**
     * Get public URL for a file
     */
    getPublicUrl(bucket, path) {
        return `${this.supabaseUrl}/storage/v1/object/public/${bucket}/${path}`;
    }
}

// Export for use in other scripts
if (typeof window !== 'undefined') {
    window.SupabaseAPI = SupabaseAPI;
    window.supabaseClient = supabase;
}

// Create global instance
const dasHouseAPI = new SupabaseAPI();

// Make it available globally
if (typeof window !== 'undefined') {
    window.dasHouseAPI = dasHouseAPI;
}