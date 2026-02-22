/**
 * Business Information Loader
 * Fetches business information from the admin API and updates the frontend
 */

class BusinessInfoLoader {
    constructor() {
        this.apiUrl = '/admin/api/business-info.php';
        this.businessInfo = null;
        this.init();
    }

    async init() {
        try {
            await this.loadBusinessInfo();
            this.updateContactSection();
            this.updatePageTitle();
        } catch (error) {
            console.error('Failed to load business information:', error);
            // Fallback to default values if API fails
            this.useDefaultInfo();
        }
    }

    async loadBusinessInfo() {
        try {
            const response = await fetch(this.apiUrl);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.success && data.data) {
                this.businessInfo = data.data;
                return data.data;
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error('Error fetching business info:', error);
            throw error;
        }
    }

    updateContactSection() {
        if (!this.businessInfo) return;

        // Update address
        this.updateElement('business-address', this.formatAddress());
        
        // Update phone
        this.updateElement('business-phone', this.businessInfo.phone);
        this.updatePhoneLink(this.businessInfo.phone);
        
        // Update email
        this.updateElement('business-email', this.businessInfo.email);
        this.updateEmailLink(this.businessInfo.email);
        
        // Update operating hours
        this.updateOperatingHours();
        
        // Update business name in various places
        this.updateBusinessName();
        
        // Update map address if it exists
        this.updateMapAddress();
    }

    updateElement(elementId, value) {
        const element = document.getElementById(elementId);
        if (element && value) {
            element.textContent = value;
        }
    }

    updatePhoneLink(phone) {
        if (!phone) return;
        
        // Update phone link in navigation
        const phoneNav = document.querySelector('a[href^="tel:"]');
        if (phoneNav) {
            phoneNav.href = `tel:${phone.replace(/\s+/g, '')}`;
            phoneNav.innerHTML = `<div>${phone}</div>`;
        }
        
        // Update phone link in contact section
        const phoneContact = document.querySelector('#contact a[href^="tel:"]');
        if (phoneContact) {
            phoneContact.href = `tel:${phone.replace(/\s+/g, '')}`;
            phoneContact.textContent = phone;
        }
        
        // Update phone link in gift shop page
        const giftShopPhone = document.querySelector('#gift-shop-phone');
        if (giftShopPhone) {
            giftShopPhone.href = `tel:${phone.replace(/\s+/g, '')}`;
            giftShopPhone.innerHTML = `<div>${phone}</div>`;
        }
    }

    updateEmailLink(email) {
        if (!email) return;
        
        // Update email link in contact section
        const emailContact = document.querySelector('#contact a[href^="mailto:"]');
        if (emailContact) {
            emailContact.href = `mailto:${email}?Subject=Hello%20from%20Das%20House`;
            emailContact.textContent = email;
        }
    }

    updateBusinessName() {
        if (!this.businessInfo.business_name) return;
        
        // Update page title
        document.title = `${this.businessInfo.business_name} | Restaurant & Café`;
        
        // Update hero section if it exists
        const heroTitle = document.querySelector('.emphasis-title .before-heading');
        if (heroTitle) {
            heroTitle.textContent = this.businessInfo.business_name;
        }
        
        // Update map content if it exists
        const mapContent = document.querySelector('#map');
        if (mapContent && this.businessInfo.business_name) {
            const mapDataContent = mapContent.getAttribute('data-content');
            if (mapDataContent) {
                const updatedContent = mapDataContent.replace(
                    /<h4[^>]*>Hi, we are <span>[^<]*<\/span><\/h4>/,
                    `<h4 class="text-dark" style="margin-bottom: 8px;">Hi, we are <span>${this.businessInfo.business_name}</span></h4>`
                );
                mapContent.setAttribute('data-content', updatedContent);
            }
        }
    }

    updateMapAddress() {
        if (!this.businessInfo.address) return;
        
        const mapElement = document.querySelector('#map');
        if (mapElement) {
            // Use just the street address for the map
            mapElement.setAttribute('data-address', this.businessInfo.address);
        }
    }

    formatAddress() {
        if (!this.businessInfo.address) return '';
        
        // Just return the street address
        return this.businessInfo.address;
    }

    updateOperatingHours() {
        if (!this.businessInfo) return;
        
        // Target the business-hours div directly
        const timeSection = document.getElementById('business-hours');
        if (!timeSection) return;
        
        // Clear existing hours
        timeSection.innerHTML = '';
        
        // Generate hours HTML
        const days = [
            { key: 'monday', label: 'Monday' },
            { key: 'tuesday', label: 'Tuesday' },
            { key: 'wednesday', label: 'Wednesday' },
            { key: 'thursday', label: 'Thursday' },
            { key: 'friday', label: 'Friday' },
            { key: 'saturday', label: 'Saturday' },
            { key: 'sunday', label: 'Sunday' }
        ];
        
        // Group consecutive days with same hours for cleaner display
        const groupedHours = this.groupConsecutiveDays(days);
        
        groupedHours.forEach(group => {
            if (group.isClosed) {
                const closedElement = document.createElement('span');
                closedElement.className = 'text-uppercase text-white ls1 fw-normal font-primary';
                closedElement.textContent = `${group.label} Closed`;
                timeSection.appendChild(closedElement);
            } else {
                const dayElement = document.createElement('div');
                dayElement.className = 'h6 text-white ls1 fw-normal font-primary';
                dayElement.textContent = `${group.label} ${this.formatTime(group.openTime)} - ${this.formatTime(group.closeTime)}`;
                timeSection.appendChild(dayElement);
            }
        });
    }

    groupConsecutiveDays(days) {
        const groups = [];
        let currentGroup = null;
        
        days.forEach(day => {
            const openKey = `${day.key}_open`;
            const closeKey = `${day.key}_close`;
            
            const openTime = this.businessInfo[openKey];
            const closeTime = this.businessInfo[closeKey];
            
            const isClosed = !openTime || !closeTime;
            const timeKey = isClosed ? 'closed' : `${openTime}-${closeTime}`;
            
            if (currentGroup && currentGroup.timeKey === timeKey) {
                // Extend current group
                currentGroup.days.push(day.label);
                currentGroup.label = this.formatDayRange(currentGroup.days);
            } else {
                // Start new group
                currentGroup = {
                    timeKey,
                    days: [day.label],
                    label: day.label,
                    isClosed,
                    openTime,
                    closeTime
                };
                groups.push(currentGroup);
            }
        });
        
        return groups;
    }
    
    formatDayRange(days) {
        if (days.length === 1) {
            return days[0];
        } else if (days.length === 2) {
            return `${days[0]}-${days[1]}`;
        } else {
            return `${days[0]}-${days[days.length - 1]}`;
        }
    }

    formatTime(timeString) {
        if (!timeString) return '';
        
        try {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'pm' : 'am';
            const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
            return `${displayHour}:${minutes}${ampm}`;
        } catch (error) {
            return timeString;
        }
    }

    updatePageTitle() {
        if (this.businessInfo && this.businessInfo.business_name) {
            document.title = `${this.businessInfo.business_name} | Restaurant & Café`;
        }
    }

    useDefaultInfo() {
        // Fallback to default values if API fails
        console.log('Using default business information');
        this.businessInfo = {
            business_name: 'Das House',
            phone: '(43) 677 634 238 81',
            email: 'info@dashouse.at',
            address: 'Gumpendorfer strasse 51',
            city: 'Vienna',
            state: 'Austria'
        };
    }

    // Public method to refresh business info
    async refresh() {
        try {
            await this.loadBusinessInfo();
            this.updateContactSection();
            this.updatePageTitle();
        } catch (error) {
            console.error('Failed to refresh business information:', error);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.businessInfoLoader = new BusinessInfoLoader();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BusinessInfoLoader;
}
